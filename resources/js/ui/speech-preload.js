let savedVoice = null;
let preferenceRequest = null;

// Prepare only the selected model; the server loads it in the background.
async function requestPreparation(voice) {
    if (voice && voice !== 'auto' && ! voice.startsWith('piper:')) return;
    const endpoint = document.querySelector('meta[name="speech-prepare-endpoint"]')?.content;
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (! endpoint || ! token) return;
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 5000);
    try {
        const response = await fetch(endpoint, { method: 'POST', signal: controller.signal,
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ voice }),
        });
        const data = await response.json();
        return typeof data.voice === 'string' ? data.voice : null;
    } catch { /* Reading can still start the engine on demand. */ }
    finally { clearTimeout(timer); }
}

export function prepareLocalVoice(voice = null) {
    if (voice !== null) return requestPreparation(voice);
    if (preferenceRequest) return preferenceRequest;
    preferenceRequest = requestPreparation(null).then((voice) => {
        if (voice) savedVoice = voice;
        return savedVoice;
    }).finally(() => { preferenceRequest = null; });
    return preferenceRequest;
}

export function preparedVoicePreference() {
    return preferenceRequest || Promise.resolve(savedVoice);
}

export function initSpeechPreload() {
    if (! document.querySelector('meta[name="speech-prepare-endpoint"]')) return;
    const prepare = () => { if (! document.hidden) prepareLocalVoice(); };
    prepare();
    document.addEventListener('visibilitychange', prepare);
    window.addEventListener('pageshow', (event) => { if (event.persisted) prepare(); });
    window.addEventListener('focus', prepare);
    // Keep the selected voice ready during an active session, including long reads.
    window.setInterval(prepare, 4 * 60 * 1000);
}
