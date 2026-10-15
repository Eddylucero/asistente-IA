export function playLocalSpeech({ endpoint, voice, text, cache, onStart, onEnd, onError, onPause = () => {}, onMotion = () => {} }) {
    const controller = new AbortController();
    let cancelled = false;
    let audio;
    let url;
    let context;
    let frame;
    const cacheKey = JSON.stringify([endpoint, voice, text]);
    const remember = (blob) => {
        if (! cache || blob.size > 8 * 1024 * 1024) return;
        cache.delete(cacheKey);
        cache.set(cacheKey, blob);
        while (cache.size > 3 || [...cache.values()].reduce((bytes, item) => bytes + item.size, 0) > 8 * 1024 * 1024) {
            cache.delete(cache.keys().next().value);
        }
    };
    const startTimer = setTimeout(() => {
        controller.abort();
        fail(new Error('La voz tardó demasiado en prepararse. Intenta con otra voz.'));
    }, 45000);

    const release = () => {
        clearTimeout(startTimer);
        window.cancelAnimationFrame(frame);
        context?.close().catch(() => {});
        if (url) URL.revokeObjectURL(url);
        url = null;
    };
    const fail = (error) => {
        if (cancelled) return;
        cancelled = true;
        audio?.pause();
        release();
        onError(error);
    };

    const monitorSilence = async () => {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (! AudioContext) return;
        let routed = false;
        try {
            context = new AudioContext();
            await context.resume();
            if (cancelled || context.state !== 'running') { await context.close(); return; }
            const source = context.createMediaElementSource(audio);
            source.connect(context.destination);
            routed = true;
            const analyser = context.createAnalyser();
            analyser.fftSize = 512;
            source.connect(analyser);
            const samples = new Uint8Array(analyser.fftSize);
            let lastSound = window.performance.now();
            let moving = true;
            const measure = (now) => {
                if (cancelled) return;
                analyser.getByteTimeDomainData(samples);
                const rms = Math.sqrt(samples.reduce((sum, value) => sum + ((value - 128) / 128) ** 2, 0) / samples.length);
                if (rms > 0.012) lastSound = now;
                const next = ! audio.paused && now - lastSound < 140;
                if (next !== moving) { moving = next; onMotion(next); }
                frame = window.requestAnimationFrame(measure);
            };
            frame = window.requestAnimationFrame(measure);
        } catch {
            // Keep the direct audio connection if only the analysis failed.
            if (! routed) context?.close().catch(() => {});
        }
    };

    (async () => {
        try {
            let blob = cache?.get(cacheKey);
            if (! blob) {
                const response = await fetch(endpoint, {
                    method: 'POST', signal: controller.signal,
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ text, voice }),
                });
                if (! response.ok) {
                    const data = await response.json();
                    throw new Error(data.message || 'No se pudo generar la voz.');
                }
                blob = await response.blob();
            }
            if (cancelled) return;
            remember(blob);
            url = URL.createObjectURL(blob);
            audio = new window.Audio(url);
            let started = false;
            audio.onplaying = () => {
                if (cancelled) return;
                clearTimeout(startTimer);
                onStart();
                onMotion(true);
                if (! started) { started = true; monitorSilence(); }
            };
            audio.onpause = () => { if (! cancelled && ! audio.ended) onPause(); };
            audio.onended = () => {
                if (cancelled) return;
                cancelled = true;
                release();
                onEnd();
            };
            audio.onerror = () => { cache?.delete(cacheKey); fail(new Error('No se pudo reproducir el audio.')); };
            await audio.play();
        } catch (error) { fail(error); }
    })();

    return {
        cancel() {
            cancelled = true;
            controller.abort();
            audio?.pause();
            release();
        },
    };
}
