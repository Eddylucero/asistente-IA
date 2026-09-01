export function initVoice() {
    const root = document.querySelector('[data-voice]');

    if (! root) {
        return;
    }

    const endpoint = root.dataset.endpoint;
    const button = root.querySelector('[data-voice-button]');
    const icon = root.querySelector('[data-voice-icon]');
    const transcript = root.querySelector('[data-voice-transcript]');
    const waves = root.querySelector('[data-voice-waves]');

    const setStatus = (text) => root.querySelectorAll('[data-connection-status]')
        .forEach((node) => {
            node.textContent = text;
        });

    const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (! Recognition) {
        button.disabled = true;
        button.classList.add('cursor-not-allowed', 'opacity-50');
        transcript.textContent = 'Tu navegador no admite reconocimiento de voz.';
        setStatus('Usa la conversación escrita');

        return;
    }

    const recognition = new Recognition();
    recognition.lang = 'es-ES';
    recognition.continuous = false;
    recognition.interimResults = true;

    let finalTranscript = '';
    let listening = false;

    const stopUi = () => {
        listening = false;
        icon.textContent = 'mic';
        button.setAttribute('aria-label', 'Comenzar a escuchar');
        button.classList.remove('scale-110');
    };

    const save = async (content) => {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ content }),
        });

        if (! response.ok) {
            throw new Error('No se pudo guardar el mensaje');
        }
    };

    button.addEventListener('click', () => {
        if (listening) {
            recognition.stop();

            return;
        }

        finalTranscript = '';
        transcript.textContent = 'Te estoy escuchando...';
        recognition.start();
    });

    recognition.onstart = () => {
        listening = true;
        icon.textContent = 'mic_off';
        button.setAttribute('aria-label', 'Detener escucha');
        button.classList.add('scale-110');
        waves?.classList.add('opacity-100');
        setStatus('Escuchando...');
    };

    recognition.onresult = (event) => {
        let interim = '';

        for (let index = event.resultIndex; index < event.results.length; index++) {
            const text = event.results[index][0].transcript;

            if (event.results[index].isFinal) {
                finalTranscript += text;
            } else {
                interim += text;
            }
        }

        transcript.textContent = `${finalTranscript} ${interim}`.trim();
    };

    recognition.onend = async () => {
        stopUi();
        setStatus('Procesando transcripción...');

        const content = finalTranscript.trim();

        if (! content) {
            transcript.textContent = 'No recibí ninguna frase. Pulsa el micrófono para intentarlo de nuevo.';
            setStatus('Lista para escucharte');

            return;
        }

        try {
            await save(content);
            transcript.textContent = `"${content}"`;
            setStatus('Mensaje guardado');
        } catch {
            transcript.textContent = 'No pude guardar el mensaje. Inténtalo de nuevo.';
            setStatus('Error de conexión');
        }
    };

    recognition.onerror = (event) => {
        stopUi();
        setStatus('Lista para escucharte');
        transcript.textContent = event.error === 'not-allowed'
            ? 'Permite el acceso al micrófono para poder escucharte.'
            : 'No pude escuchar esa frase. Inténtalo de nuevo.';
    };
}
