import { initChatSpeech } from './chat-speech.js';
import { monitorVoiceSilence } from './voice-silence.js';

const INITIAL_SILENCE_MS = 12000;
const END_OF_TURN_MS = 1800;
const TRANSCRIPT_WAIT_MS = 4000;

export function initVoice(root = document.querySelector('[data-voice]'), {
    createSpeech = initChatSpeech, monitorSilence = monitorVoiceSilence,
} = {}) {
    if (! root) return;
    const button = root.querySelector('[data-voice-button]');
    const icon = root.querySelector('[data-voice-icon]');
    const transcript = root.querySelector('[data-voice-transcript]');
    const waves = root.querySelector('[data-voice-waves]');
    const stopButton = root.querySelector('[data-voice-stop]');
    const retryButton = root.querySelector('[data-voice-retry]');
    const speech = createSpeech(root, { inline: true });
    const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const secureContext = window.isSecureContext !== false;
    const canListen = secureContext && Boolean(Recognition);
    let session = false, capture = null, state = 'idle', playback = 0, request = null, retryContent = '';

    const setState = (value, status) => {
        state = value;
        root.dataset.voiceState = value;
        const listening = value === 'listening';
        button.disabled = ! canListen || ['starting', 'stopping', 'processing'].includes(value);
        icon.textContent = listening ? 'mic_off' : value === 'speaking' ? 'stop' : 'mic';
        button.setAttribute('aria-pressed', String(listening));
        button.setAttribute('aria-label', listening ? 'Terminar frase' : value === 'speaking' ? 'Interrumpir y hablar' : 'Comenzar a escuchar');
        button.classList.toggle('scale-110', listening);
        waves?.classList.toggle('opacity-100', listening);
        waves?.classList.toggle('opacity-30', ! listening);
        if (stopButton) stopButton.hidden = ! session && ! ['preparing', 'speaking'].includes(value);
        root.querySelectorAll('[data-connection-status]').forEach((node) => { node.textContent = status; });
    };
    const pause = () => {
        session = false;
        playback += 1;
        const current = capture;
        capture = null;
        current?.dispose();
        speech.stop({ immediate: true });
        setState(request ? 'processing' : 'idle', request ? 'Guardando conversación...' : 'Conversación en pausa');
        transcript.textContent = 'Micrófono apagado. Pulsa para volver a hablar.';
    };
    const playReply = (content) => {
        const current = ++playback;
        speech.stop({ immediate: true });
        setState('preparing', 'Preparando voz...');
        const failed = () => {
            if (current !== playback) return;
            session = false;
            setState('idle', 'Respuesta disponible en el chat');
            transcript.textContent = 'No pude reproducir la voz. Puedes leer la respuesta en el chat de texto.';
        };
        Promise.resolve(speech.speak(content, {
            onReady: () => {
                if (current !== playback) return;
                setState('speaking', 'Lumi está hablando...');
                transcript.textContent = 'Micrófono apagado mientras Lumi responde.';
            },
            onEnd: () => {
                if (current !== playback) return;
                if (session && ! document.hidden) startListening();
                else setState('idle', 'Lista para escucharte');
            },
            onUnavailable: failed, onError: failed,
        })).catch(failed);
    };
    const send = async (content) => {
        if (request) return;
        retryContent = content;
        if (retryButton) retryButton.hidden = true;
        setState('processing', 'Lumi está pensando...');
        const controller = new AbortController();
        request = controller;
        const timer = setTimeout(() => controller.abort(), 60000);
        try {
            const response = await fetch(root.dataset.endpoint, {
                method: 'POST', signal: controller.signal,
                headers: { 'Content-Type': 'application/json', Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ content, conversation_id: Number(root.dataset.conversation) || null,
                    new_conversation: root.dataset.startNewConversation === 'true' }),
            });
            const data = await response.json();
            if (! response.ok) throw new Error(data.message || 'No se pudo enviar la frase.');
            root.dataset.conversation = String(data.conversation.id);
            root.dataset.startNewConversation = 'false';
            root.querySelectorAll('[data-voice-chat-link]').forEach((link) => {
                link.href = `${root.dataset.chatUrl}/${data.conversation.id}`;
            });
            retryContent = '';
            request = null;
            const answer = data.messages.filter((message) => message.role === 'assistant').map((message) => message.content).join('\n');
            if (session && ! document.hidden && answer) playReply(answer);
            else setState('idle', 'Respuesta guardada en el chat');
        } catch (error) {
            session = false;
            setState('idle', 'No se pudo completar la conversación');
            transcript.textContent = `${error.name === 'AbortError' ? 'La respuesta tardó demasiado.' : error.message} Tu frase: “${content}”`;
            if (retryButton) retryButton.hidden = false;
        } finally {
            clearTimeout(timer);
            if (request === controller) request = null;
        }
    };

    function startListening({ emptyRetries = 1 } = {}) {
        if (! canListen || capture || request || document.hidden) return;
        playback += 1;
        speech.stop({ immediate: true });
        session = true;
        delete root.dataset.voiceError;
        if (retryButton) retryButton.hidden = true;
        const recognition = new Recognition();
        recognition.lang = 'es-EC';
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.maxAlternatives = 1;
        let idleTimer, silenceTimer, limitTimer, endTimer;
        let stopMonitor = () => {};
        let stopping = false, invalid = false, disposed = false, parts = [];
        const content = () => parts.filter(Boolean).join(' ').trim();
        const clearTimers = () => [idleTimer, silenceTimer, limitTimer, endTimer].forEach(clearTimeout);
        const current = {
            dispose() {
                disposed = true;
                clearTimers();
                stopMonitor();
                try { recognition.abort(); } catch { /* Already ended. */ }
            },
        };
        capture = current;
        const active = () => capture === current && ! disposed;
        const heardSpeech = () => {
            if (! active() || stopping) return;
            clearTimeout(idleTimer);
            clearTimeout(silenceTimer);
            if (! content()) idleTimer = setTimeout(stop, INITIAL_SILENCE_MS);
            else silenceTimer = setTimeout(stop, TRANSCRIPT_WAIT_MS);
        };
        const complete = () => {
            if (! active()) return;
            const text = content();
            capture = null;
            current.dispose();
            if (invalid) return;
            if (! text) {
                if (! stopping && emptyRetries > 0 && session && ! document.hidden) {
                    startListening({ emptyRetries: emptyRetries - 1 });
                    return;
                }
                session = false;
                setState('idle', 'Esperando para escucharte');
                transcript.textContent = 'No escuché ninguna frase. Micrófono apagado; pulsa cuando quieras hablar.';
                return;
            }
            transcript.textContent = text;
            if (text.length > 5000) {
                session = false;
                setState('idle', 'La frase es demasiado larga');
                transcript.textContent = 'Habla en frases más cortas para poder responderte.';
                return;
            }
            send(text);
        };
        const stop = () => {
            if (! active() || stopping) return;
            stopping = true;
            clearTimers();
            stopMonitor();
            setState('stopping', 'Micrófono apagado. Terminando transcripción...');
            endTimer = setTimeout(complete, 1200);
            try { recognition.stop(); } catch { complete(); }
        };
        current.stop = stop;
        setState('starting', 'Activando micrófono...');
        transcript.textContent = 'Te estoy escuchando...';
        recognition.onstart = () => {
            if (! active() || stopping) return;
            clearTimeout(limitTimer);
            setState('listening', 'Escuchando...');
            idleTimer = setTimeout(stop, INITIAL_SILENCE_MS);
            limitTimer = setTimeout(stop, 60000);
            stopMonitor = monitorSilence({ onSpeech: heardSpeech, onSilence: () => { if (content()) stop(); } });
        };
        recognition.onspeechstart = () => { if (active() && ! stopping) heardSpeech(); };
        recognition.onspeechend = () => {
            if (! active() || stopping || ! content()) return;
            clearTimeout(silenceTimer);
            silenceTimer = setTimeout(stop, END_OF_TURN_MS);
        };
        recognition.onresult = (event) => {
            if (! active() || invalid) return;
            parts = Array.from(event.results, (result) => result[0].transcript.trim());
            transcript.textContent = content();
            // A finalized segment can be followed by more speech in the same turn.
            if (! stopping) heardSpeech();
        };
        recognition.onend = complete;
        recognition.onerror = (event) => {
            if (! active()) return;
            if (event.error === 'no-speech' || (event.error === 'aborted' && stopping)) { complete(); return; }
            invalid = true;
            session = false;
            capture = null;
            current.dispose();
            root.dataset.voiceError = event.error;
            setState('idle', 'Micrófono apagado');
            const errors = {
                'not-allowed': 'Permite el acceso al micrófono en los ajustes de este sitio.',
                'service-not-allowed': 'Tu navegador bloqueó el servicio de reconocimiento de voz.',
                'audio-capture': 'No encuentro un micrófono disponible. Revisa el dispositivo de entrada.',
                network: 'El servicio de reconocimiento de este navegador no está disponible. Prueba con otro navegador.',
                aborted: 'El navegador interrumpió la escucha. Vuelve a activar el micrófono.',
                'language-not-supported': 'El servicio de este navegador no admite el reconocimiento en español.',
            };
            transcript.textContent = errors[event.error] || 'El navegador no pudo reconocer la voz. Puedes continuar en el chat de texto.';
            if (content()) {
                retryContent = content();
                if (retryButton) retryButton.hidden = false;
                transcript.textContent += ` Frase recuperada: “${retryContent}”.`;
            }
        };
        limitTimer = setTimeout(() => recognition.onerror({ error: 'audio-capture' }), 10000);
        try { recognition.start(); } catch { recognition.onerror({ error: 'audio-capture' }); }
    }
    button.addEventListener('click', () => {
        if (state === 'listening') { capture?.stop(); return; }
        if (['speaking', 'preparing'].includes(state)) pause();
        if (! request && ! capture) startListening();
    });
    stopButton?.addEventListener('click', pause);
    retryButton?.addEventListener('click', () => {
        if (! retryContent || request) return;
        pause();
        session = true;
        send(retryContent);
    });
    window.addEventListener('pagehide', pause);
    document.addEventListener('visibilitychange', () => { if (document.hidden) pause(); });
    setState('idle', canListen ? 'Lista para escucharte' : 'Usa la conversación escrita');
    if (! secureContext) transcript.textContent = 'Abre la aplicación por HTTPS o en localhost para permitir el acceso al micrófono.';
    else if (! canListen) transcript.textContent = 'Tu navegador no admite reconocimiento de voz. Usa un navegador compatible o continúa en el chat de texto.';
}
