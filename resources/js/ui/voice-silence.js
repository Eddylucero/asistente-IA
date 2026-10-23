// Give normal pauses time to finish, including when the person speaks softly.
export function monitorVoiceSilence({ onSpeech, onSilence }) {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (! AudioContext || ! navigator.mediaDevices?.getUserMedia) return () => {};
    let stopped = false;
    let stream;
    let context;
    let frame;
    const stop = () => {
        stopped = true;
        window.cancelAnimationFrame(frame);
        stream?.getTracks().forEach((track) => track.stop());
        context?.close().catch(() => {});
        context = null;
    };
    (async () => {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ audio: {
                echoCancellation: true, noiseSuppression: true, autoGainControl: true,
            } });
            if (stopped) { stop(); return; }
            context = new AudioContext();
            await context.resume();
            if (stopped || context.state !== 'running') { stop(); return; }
            const source = context.createMediaStreamSource(stream);
            const analyser = context.createAnalyser();
            analyser.fftSize = 1024;
            source.connect(analyser);
            const samples = new Uint8Array(analyser.fftSize);
            let soundSince = null;
            let lastSound = null;
            let heardSpeech = false;
            let silenceNotified = false;
            const measure = (now) => {
                if (stopped) return;
                analyser.getByteTimeDomainData(samples);
                const rms = Math.sqrt(samples.reduce((sum, value) => sum + ((value - 128) / 128) ** 2, 0) / samples.length);
                if (rms > 0.008) {
                    soundSince ??= now;
                    lastSound = now;
                    silenceNotified = false;
                    if (now - soundSince >= 120) { heardSpeech = true; onSpeech(); }
                } else {
                    soundSince = null;
                    if (heardSpeech && ! silenceNotified && now - lastSound >= 1800) {
                        silenceNotified = true;
                        onSilence();
                        if (stopped) return;
                    }
                }
                frame = window.requestAnimationFrame(measure);
            };
            frame = window.requestAnimationFrame(measure);
        } catch { stop(); /* Native speech events still control recognition. */ }
    })();
    return stop;
}
