import { browserVoiceId, chooseBrowserVoice, spanishVoices } from './speech-voices.js';
import { playLocalSpeech } from './local-speech.js';
import { prepareLocalVoice } from './speech-preload.js';

const SAMPLE = 'Hola, soy Lumi. Estoy aquí para escucharte y acompañarte. Podemos conversar con calma, a tu ritmo.';

export function initVoicePreferences() {
    const root = document.querySelector('[data-voice-preferences]');
    if (! root) return;
    const select = root.querySelector('[data-voice-select]');
    const preview = root.querySelector('[data-voice-preview]');
    const stopButton = root.querySelector('[data-voice-preview-stop]');
    const status = root.querySelector('[data-voice-preview-status]');
    const synth = window.speechSynthesis;
    const localVoices = JSON.parse(root.dataset.localVoices || '[]');
    let preferred = root.dataset.currentVoice || 'auto';
    let localPlayback;
    let previewGeneration = 0;
    let previewTimer;

    const populate = () => {
        select.replaceChildren(new Option('Automática (recomendada)', 'auto'));
        if (localVoices.length) {
            const group = document.createElement('optgroup');
            group.label = 'Voces instaladas de Lumi';
            localVoices.forEach((voice) => group.append(new Option(voice.name, voice.id)));
            select.append(group);
        }
        const browserVoices = spanishVoices(synth).sort((a, b) => a.name.localeCompare(b.name, 'es'));
        if (browserVoices.length) {
            const group = document.createElement('optgroup');
            group.label = 'Voces de tu navegador';
            browserVoices.forEach((voice) => group.append(new Option(`${voice.name} (${voice.lang})`, browserVoiceId(voice))));
            select.append(group);
        }
        if (! [...select.options].some((option) => option.value === preferred)) {
            const missing = new Option('Voz guardada (no disponible aquí)', preferred);
            missing.disabled = true;
            select.append(missing);
            status.textContent = 'Tu voz guardada no está disponible aquí. Puedes elegir otra.';
        }
        select.value = preferred;
        preview.disabled = ! localVoices.length && ! (synth && window.SpeechSynthesisUtterance);
    };

    const stop = () => {
        clearTimeout(previewTimer);
        previewGeneration += 1;
        localPlayback?.cancel();
        localPlayback = null;
        synth?.cancel();
        stopButton.hidden = true;
        status.textContent = 'Escucha una muestra antes de guardar.';
    };
    preview.addEventListener('click', () => {
        stop();
        const current = previewGeneration;
        const selected = select.value === 'auto' ? root.dataset.defaultVoice : select.value;
        const finish = () => { if (current === previewGeneration) { clearTimeout(previewTimer); stopButton.hidden = true; status.textContent = 'Puedes guardar esta voz o probar otra.'; } };
        const failure = () => { if (current === previewGeneration) { stop(); status.textContent = 'No pude reproducir esta voz. Prueba otra opción.'; } };
        stopButton.hidden = false;
        status.textContent = 'Preparando muestra...';
        if (selected?.startsWith('piper:')) {
            localPlayback = playLocalSpeech({ endpoint: root.dataset.endpoint, voice: selected, text: SAMPLE,
                onStart: () => { if (current === previewGeneration) status.textContent = 'Escuchando muestra...'; },
                onEnd: finish, onError: failure });
            return;
        }
        if (! synth || ! window.SpeechSynthesisUtterance) { failure(); return; }
        const utterance = new window.SpeechSynthesisUtterance(SAMPLE);
        previewTimer = setTimeout(failure, 10000);
        utterance.voice = chooseBrowserVoice(synth, selected);
        utterance.lang = utterance.voice?.lang || 'es-EC';
        utterance.rate = 1;
        utterance.onstart = () => { if (current === previewGeneration) { clearTimeout(previewTimer); status.textContent = 'Escuchando muestra...'; } };
        utterance.onend = finish;
        utterance.onerror = failure;
        try { synth.speak(utterance); } catch { failure(); }
    });
    stopButton.addEventListener('click', () => { stop(); status.textContent = 'Muestra detenida.'; });
    select.addEventListener('change', () => {
        stop();
        preferred = select.value;
        prepareLocalVoice(preferred);
        status.textContent = 'Escucha una muestra antes de guardar.';
    });
    root.closest('dialog')?.addEventListener('close', stop);
    root.addEventListener('submit', stop);
    window.addEventListener('pagehide', stop);
    document.addEventListener('visibilitychange', () => { if (document.hidden) stop(); });
    synth?.addEventListener?.('voiceschanged', populate);
    populate();
}
