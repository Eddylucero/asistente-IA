import { createAvatarLoop } from './avatar-loop.js';
import { chooseBrowserVoice } from './speech-voices.js';
import { playLocalSpeech } from './local-speech.js';
import { initChatGreeting } from './chat-greeting.js';
import { preparedVoicePreference } from './speech-preload.js';
import { showLoader, hideLoader } from './loader.js';

const FINISH_HOLD_MS = 450;
const MODAL_EXIT_MS = 360;
const VISUAL_PAUSE_MS = 90;

export function speechText(content) {
    return content
        .replace(/\[\[UTC_MAPA_[A-Z_]+\]\]/g, '')
        .replace(/\[([^\]]+)\]\(https?:\/\/[^\s)]+\)/g, '$1')
        .replace(/https?:\/\/\S+/g, '')
        .replace(/^\s*\|?[\s:|\-]+\|\s*$/gm, '')
        .replace(/^\s*(?:#{1,6}\s+|[-*]\s+|\d+[.)]\s+)/gm, '')
        .replace(/[*_`|]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

export function initChatSpeech(root) {
    const synth = window.speechSynthesis;
    let voicePreference = root.dataset?.ttsVoice || 'auto';
    const usesLocalVoice = voicePreference.startsWith('piper:');
    const dialog = root.querySelector('[data-chat-speech-dialog]');
    const readTarget = root.querySelector('[data-chat-messages]');
    const videoError = root.querySelector('[data-chat-video-error]');
    const statuses = root.querySelectorAll('[data-chat-speech-status]');
    const avatars = [...root.querySelectorAll('[data-chat-avatar]')].map((avatar) => ({
        element: avatar,
        greeting: createAvatarLoop(avatar.querySelector('[data-avatar-greeting]')),
        talking: createAvatarLoop(avatar.querySelector('[data-avatar-talking]')),
    }));
    let generation = 0;
    let activeUtterance = null;
    let localPlayback = null;
    let startTimer;
    let closeTimer;
    let finishTimer;
    let visualPauseTimer;
    let visualResumeTimer;
    let speaking = false;
    let motionPaused = false;
    let mediaGeneration = 0;
    let greetingIntro;
    let preferenceRequest;
    let pendingReady;
    let pendingUnavailable;
    let loadingButton;
    const audioCache = new Map();

    const completePreparation = (started = false) => {
        if (loadingButton) {
            const button = loadingButton;
            loadingButton = null;
            hideLoader();
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
        const complete = started ? pendingReady : pendingUnavailable;
        pendingReady = null;
        pendingUnavailable = null;
        complete?.();
    };

    const showButtonLoading = (button) => {
        if (! button) return;
        loadingButton = button;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        showLoader();
    };

    const status = (value) => statuses.forEach((node) => { node.textContent = value; });
    const showVideo = (video, visible) => {
        video.classList.toggle('opacity-0', ! visible);
    };

    const animateAvatars = (isSpeaking) => {
        speaking = isSpeaking;
        motionPaused = false;
        const currentMedia = ++mediaGeneration;
        videoError.hidden = true;
        avatars.forEach(({ greeting, talking }) => {
            const active = isSpeaking ? talking : greeting;
            const inactive = isSpeaking ? greeting : talking;
            inactive.pause();
            showVideo(inactive.element, false);
            // Make the video visible before play(): hidden media may be suspended.
            showVideo(active.element, true);
            active.play().catch(() => {
                if (currentMedia !== mediaGeneration) return;
                if (isSpeaking) videoError.hidden = false;
            });
        });
    };

    const setTalkingMotion = (moving, duration = 120) => {
        if (! speaking || motionPaused === ! moving) return;
        motionPaused = ! moving;
        const currentMedia = mediaGeneration;
        avatars.forEach(({ talking }) => {
            if (! moving) talking.pauseSmoothly(duration);
            else talking.resumeSmoothly(duration).catch(() => {
                if (currentMedia === mediaGeneration) videoError.hidden = false;
            });
        });
    };

    const clearVisualPause = () => {
        clearTimeout(visualPauseTimer);
        clearTimeout(visualResumeTimer);
    };

    const openDialog = () => {
        clearTimeout(closeTimer);
        dialog.classList.remove('is-closing');
        if (! dialog.open) dialog.showModal();
    };

    const closeDialog = (afterClose, immediate = false) => {
        clearTimeout(closeTimer);
        if (! dialog.open) {
            afterClose();
            return;
        }
        const complete = () => {
            dialog.close();
            dialog.classList.remove('is-closing');
            afterClose();
        };
        if (immediate) {
            complete();
            return;
        }
        dialog.classList.add('is-closing');
        const delay = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : MODAL_EXIT_MS;
        closeTimer = setTimeout(complete, delay);
    };

    const returnToGreeting = () => {
        if (speaking) animateAvatars(false);
        status('Lista para conversar');
    };

    const stop = ({ immediate = false, afterClose = null } = {}) => {
        preferenceRequest?.abort();
        greetingIntro?.dismiss(true);
        generation += 1;
        const current = generation;
        clearTimeout(startTimer);
        clearTimeout(finishTimer);
        clearVisualPause();
        activeUtterance = null;
        localPlayback?.cancel();
        localPlayback = null;
        synth?.cancel();
        completePreparation();
        closeDialog(() => {
            if (current !== generation) return;
            returnToGreeting();
            afterClose?.();
        }, immediate);
    };

    const finish = () => {
        completePreparation();
        const current = generation;
        activeUtterance = null;
        clearTimeout(startTimer);
        clearTimeout(finishTimer);
        clearVisualPause();
        status('Respuesta finalizada');
        setTalkingMotion(false);
        // Let final audio settle and keep the last pose visible through the exit.
        finishTimer = setTimeout(() => {
            if (current !== generation) return;
            closeDialog(() => {
                if (current === generation) returnToGreeting();
            });
        }, FINISH_HOLD_MS);
    };

    // The greeting runs from entry, even if TTS is unavailable or silenced.
    animateAvatars(false);
    greetingIntro = initChatGreeting(root, avatars);
    window.addEventListener('pageshow', (event) => {
        if (! event.persisted) return;
        greetingIntro?.dismiss(true);
        animateAvatars(false);
        greetingIntro = initChatGreeting(root, avatars);
    });

    if (! usesLocalVoice && (! synth || ! window.SpeechSynthesisUtterance)) {
        status('Tu navegador no admite lectura en voz alta');
        return {
            speak(_content, { onUnavailable } = {}) { onUnavailable?.(); }, stop() {},
            bindResponse(button) {
                button.disabled = true;
                button.title = 'Tu navegador no admite lectura en voz alta';
            },
        };
    }

    const reportSpeechFailure = (message) => {
        stop({ afterClose: () => status(message) });
        status(message);
    };

    const play = async (content, { onReady, onUnavailable, button } = {}) => {
        stop();
        pendingReady = onReady;
        pendingUnavailable = onUnavailable;
        const text = speechText(content);
        if (! text) { completePreparation(); return; }

        const current = generation;
        showButtonLoading(button);
        status('Preparando voz...');

        const preparedPreference = root.dataset?.voiceEndpoint ? await preparedVoicePreference() : null;
        if (current !== generation) return;
        if (preparedPreference) voicePreference = preparedPreference;

        if (root.dataset?.voiceEndpoint && ! preparedPreference) {
            const request = new AbortController();
            preferenceRequest = request;
            const timeout = setTimeout(() => request.abort(), 4000);
            try {
                const response = await fetch(root.dataset.voiceEndpoint, { signal: request.signal, headers: { Accept: 'application/json' }, cache: 'no-store' });
                if (response.ok) {
                    const data = await response.json();
                    if (current === generation && typeof data.voice === 'string') voicePreference = data.voice;
                }
            } catch { /* Keep the last confirmed preference if the network is unavailable. */ }
            finally { clearTimeout(timeout); if (preferenceRequest === request) preferenceRequest = null; }
            if (current !== generation) return;
        }

        if (voicePreference.startsWith('piper:')) {
            startTimer = setTimeout(() => {
                if (current === generation) reportSpeechFailure('No pude preparar la voz. Pulsa Escuchar respuesta para reintentar.');
            }, 65000);
            localPlayback = playLocalSpeech({
                endpoint: root.dataset.speechEndpoint, voice: voicePreference, text, cache: audioCache,
                onStart: () => {
                    if (current !== generation) return;
                    clearTimeout(startTimer);
                    completePreparation(true);
                    status('Lumi está hablando...');
                    openDialog();
                    if (! speaking) animateAvatars(true);
                    else setTalkingMotion(true);
                },
                onEnd: () => { if (current === generation) { localPlayback = null; finish(); } },
                onError: () => {
                    if (current === generation) reportSpeechFailure('No se pudo reproducir la voz. Pulsa Escuchar respuesta para reintentar.');
                },
                onPause: () => { if (current === generation) setTalkingMotion(false); },
                onMotion: (moving) => { if (current === generation) setTalkingMotion(moving, moving ? 60 : 40); },
            });
            return;
        }

        if (! synth || ! window.SpeechSynthesisUtterance) {
            reportSpeechFailure('Esta voz no está disponible en tu navegador. Elige otra voz en el perfil.');
            return;
        }

        // A single request lets the voice keep its natural rhythm and prosody.
        const utterance = new window.SpeechSynthesisUtterance(text);
        activeUtterance = utterance;
        const isCurrent = () => current === generation && activeUtterance === utterance;
        let voicePaused = false;
        let lastBoundary = -1;
        utterance.voice = chooseBrowserVoice(synth, voicePreference);
        utterance.lang = utterance.voice?.lang || 'es-EC';
        utterance.rate = 1;

        utterance.onstart = () => {
            if (! isCurrent()) return;
            clearTimeout(startTimer);
            completePreparation(true);
            status('Lumi está hablando...');
            openDialog();
            if (! speaking) animateAvatars(true);
            else setTalkingMotion(true);
        };
        utterance.onpause = () => {
            if (! isCurrent()) return;
            voicePaused = true;
            clearVisualPause();
            setTalkingMotion(false);
            status('Lumi hace una pausa...');
        };
        utterance.onresume = () => {
            if (! isCurrent()) return;
            voicePaused = false;
            clearVisualPause();
            setTalkingMotion(true);
            status('Lumi está hablando...');
        };
        utterance.onboundary = (event) => {
            if (! isCurrent() || voicePaused || (event.name && event.name !== 'word')) return;
            if (! Number.isInteger(event.charIndex) || event.charIndex <= lastBoundary
                || event.charIndex >= text.length) return;
            lastBoundary = event.charIndex;
            clearVisualPause();
            // A new spoken word always resumes the mouth immediately.
            setTalkingMotion(true, 60);
            const word = text.slice(event.charIndex).match(/^\s*(\S+(?:\s*[,;:!?…]+)?)/)?.[1] || '';
            if (! /[,;:.!?…]["'”’)]*$/.test(word)
                || /^(?:Dra?|Sra?|Srta|Lic|Ing|Av|Ud|Uds|etc|aprox)\.$/i.test(word)) return;
            // Approximate the end of this word, then briefly settle only the video.
            // Voices without word events simply keep the video moving.
            const wordDuration = Math.min(300, Math.max(100, word.replace(/[^\p{L}]/gu, '').length * 40));
            visualPauseTimer = setTimeout(() => {
                if (! isCurrent() || voicePaused) return;
                setTalkingMotion(false, 40);
                visualResumeTimer = setTimeout(() => {
                    if (isCurrent() && ! voicePaused) setTalkingMotion(true, 60);
                }, VISUAL_PAUSE_MS);
            }, wordDuration);
        };
        utterance.onend = () => {
            if (! isCurrent()) return;
            finish();
        };
        utterance.onerror = () => {
            if (current !== generation || activeUtterance !== utterance) return;
            reportSpeechFailure('No se pudo reproducir la voz. Pulsa Escuchar respuesta para reintentar.');
        };
        startTimer = setTimeout(() => {
            if (current !== generation || activeUtterance !== utterance) return;
            reportSpeechFailure('Pulsa Escuchar respuesta para iniciar la voz.');
        }, 8000);
        try {
            synth.speak(utterance);
        } catch {
            utterance.onerror();
        }
    };

    const preferReading = () => {
        stop({ afterClose: () => readTarget.focus({ preventScroll: true }) });
    };
    root.querySelectorAll('[data-chat-prefer-reading]').forEach((button) => {
        button.addEventListener('click', preferReading);
    });
    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        preferReading();
    });
    dialog.addEventListener('click', (event) => {
        if (event.target !== dialog) return;
        const bounds = dialog.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right
            || event.clientY < bounds.top || event.clientY > bounds.bottom) preferReading();
    });
    const suspend = () => {
        stop({ immediate: true });
        mediaGeneration += 1;
        avatars.forEach(({ greeting, talking }) => { greeting.pause(); talking.pause(); });
    };
    window.addEventListener('pagehide', suspend);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) suspend();
        else animateAvatars(false);
    });

    return {
        stop,
        bindResponse(button, content) {
            button.disabled = ! speechText(content);
            button.addEventListener('click', () => play(content, { button }));
        },
        speak(content, options = {}) {
            return play(content, options);
        },
    };
}
