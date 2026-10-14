import { createAvatarLoop } from './avatar-loop.js';

const FINISH_HOLD_MS = 450;
const MODAL_EXIT_MS = 360;

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

export function speechSegments(content) {
    const phrases = speechText(content).match(/[\s\S]+?(?:[,;:!?…]+|\.+)["'”’)]*(?=\s|$)|[\s\S]+$/g) || [];
    const segments = [];
    let pending = '';
    phrases.forEach((phrase, index) => {
        pending = `${pending} ${phrase.trim()}`.trim();
        // Keep common titles attached to the name; decimals are not split.
        if (/\b(?:Dra?|Sra?|Srta|Lic|Ing|Av|Ud|Uds|etc|aprox)\.$/i.test(pending)
            && index < phrases.length - 1) return;
        const chunks = pending.match(/.{1,220}(?:\s|$)|\S{1,220}/g) || [pending];
        chunks.forEach((chunk, chunkIndex) => {
            const text = chunk.trim();
            let pauseMs = 0;
            if (chunkIndex === chunks.length - 1) {
                if (/[,]["'”’)]*$/.test(text)) pauseMs = 180;
                else if (/[;:]["'”’)]*$/.test(text)) pauseMs = 240;
                else if (/[.!?…]["'”’)]*$/.test(text)) pauseMs = 320;
            }
            segments.push({ text, pauseMs });
        });
        pending = '';
    });
    return segments;
}

export function initChatSpeech(root) {
    const synth = window.speechSynthesis;
    const stopButton = root.querySelector('[data-chat-speech-stop]');
    const dialog = root.querySelector('[data-chat-speech-dialog]');
    const readTarget = root.querySelector('[data-chat-messages]');
    const videoError = root.querySelector('[data-chat-video-error]');
    const statuses = root.querySelectorAll('[data-chat-speech-status]');
    const avatars = [...root.querySelectorAll('[data-chat-avatar]')].map((avatar) => ({
        greeting: createAvatarLoop(avatar.querySelector('[data-avatar-greeting]')),
        talking: createAvatarLoop(avatar.querySelector('[data-avatar-talking]')),
    }));
    let enabled = true;
    let generation = 0;
    let activeUtterance = null;
    let startTimer;
    let closeTimer;
    let finishTimer;
    let phraseTimer;
    let speaking = false;
    let motionPaused = false;
    let mediaGeneration = 0;

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

    const setTalkingMotion = (moving) => {
        if (! speaking || motionPaused === ! moving) return;
        motionPaused = ! moving;
        const currentMedia = mediaGeneration;
        avatars.forEach(({ talking }) => {
            if (! moving) talking.pauseSmoothly();
            else talking.resumeSmoothly().catch(() => {
                if (currentMedia === mediaGeneration) videoError.hidden = false;
            });
        });
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
        generation += 1;
        const current = generation;
        clearTimeout(startTimer);
        clearTimeout(finishTimer);
        clearTimeout(phraseTimer);
        activeUtterance = null;
        synth?.cancel();
        closeDialog(() => {
            if (current !== generation) return;
            returnToGreeting();
            afterClose?.();
        }, immediate);
        stopButton.hidden = true;
    };

    const finish = () => {
        const current = generation;
        activeUtterance = null;
        clearTimeout(startTimer);
        clearTimeout(finishTimer);
        stopButton.hidden = true;
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

    if (! synth || ! window.SpeechSynthesisUtterance) {
        stopButton.disabled = true;
        status('Tu navegador no admite lectura en voz alta');
        return {
            speak() {}, stop() {},
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

    const play = (content) => {
        stop();
        const segments = speechSegments(content);
        if (! segments.length) return;

        const current = generation;
        stopButton.hidden = false;
        status('Preparando voz...');

        const speakNext = (index) => {
            if (current !== generation) return;
            if (index === segments.length) {
                finish();
                return;
            }

            const utterance = new window.SpeechSynthesisUtterance(segments[index].text);
            activeUtterance = utterance;
            const voices = synth.getVoices().filter((voice) => /^es(?:[-_]|$)/i.test(voice.lang));
            utterance.voice = voices.find((voice) => /^es[-_](EC|MX|US)/i.test(voice.lang))
                || voices.find((voice) => voice.default) || voices[0] || null;
            utterance.lang = utterance.voice?.lang || 'es-EC';
            utterance.rate = 0.95;

            utterance.onstart = () => {
                if (current !== generation || activeUtterance !== utterance) return;
                clearTimeout(startTimer);
                status('Lumi está hablando...');
                openDialog();
                if (! speaking) animateAvatars(true);
                else setTalkingMotion(true);
            };
            utterance.onpause = () => {
                if (current !== generation || activeUtterance !== utterance) return;
                setTalkingMotion(false);
                status('Lumi hace una pausa...');
            };
            utterance.onresume = () => {
                if (current !== generation || activeUtterance !== utterance) return;
                setTalkingMotion(true);
                status('Lumi está hablando...');
            };
            utterance.onend = () => {
                if (current !== generation || activeUtterance !== utterance) return;
                clearTimeout(startTimer);
                activeUtterance = null;
                if (index === segments.length - 1) {
                    finish();
                    return;
                }
                setTalkingMotion(false);
                status('Lumi hace una pausa...');
                phraseTimer = setTimeout(() => speakNext(index + 1), segments[index].pauseMs);
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

        speakNext(0);
    };

    const preferReading = () => {
        enabled = false;
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
    stopButton.addEventListener('click', stop);
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
            button.addEventListener('click', () => play(content));
        },
        speak(content) {
            if (enabled) play(content);
        },
    };
}
