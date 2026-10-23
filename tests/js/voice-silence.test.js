import test from 'node:test';
import assert from 'node:assert/strict';
import { monitorVoiceSilence } from '../../resources/js/ui/voice-silence.js';

const flush = async () => { for (let i = 0; i < 8; i++) await Promise.resolve(); };
function media(t, getUserMedia) {
    const original = Object.getOwnPropertyDescriptor(globalThis, 'navigator');
    Object.defineProperty(globalThis, 'navigator', { configurable: true, value: { mediaDevices: { getUserMedia } } });
    t.after(() => original ? Object.defineProperty(globalThis, 'navigator', original) : delete globalThis.navigator);
}

test('soft speech tolerates brief pauses and releases the microphone after 1.8 seconds of silence', async (t) => {
    let frame, volume = 128, tracksStopped = 0, closed = 0, silences = 0;
    media(t, async () => ({
        getTracks: () => [{ stop: () => tracksStopped++ }],
    }));
    globalThis.window = {
        requestAnimationFrame(fn) { frame = fn; return 1; }, cancelAnimationFrame() {},
        AudioContext: class {
            state = 'running';
            resume() { return Promise.resolve(); }
            close() { closed++; return Promise.resolve(); }
            createMediaStreamSource() { return { connect() {} }; }
            createAnalyser() { return { fftSize: 1024, getByteTimeDomainData(data) { data.fill(volume); } }; }
        },
    };
    let speechDetected = 0;
    const stop = monitorVoiceSilence({ onSpeech() { speechDetected++; }, onSilence() { silences++; stop(); } });
    await flush();
    frame(0); // Initial silence never sends a phrase.
    assert.equal(silences, 0);
    volume = 130; // Softer than the previous amplitude threshold.
    frame(100);
    frame(250);
    assert.equal(speechDetected, 1);
    volume = 128;
    frame(899);
    assert.equal(silences, 0);
    frame(2049);
    assert.equal(silences, 0);
    frame(2050);
    assert.equal(silences, 1);
    assert.equal(tracksStopped, 1);
    assert.equal(closed, 1);
    stop();
});

test('microphone streams acquired after cancellation are immediately released', async (t) => {
    let resolve, stopped = 0;
    media(t, () => new Promise((done) => { resolve = done; }));
    globalThis.window = { AudioContext: class {}, cancelAnimationFrame() {} };
    const cancel = monitorVoiceSilence({ onSpeech() {}, onSilence() {} });
    cancel();
    resolve({ getTracks: () => [{ stop: () => stopped++ }] });
    await flush();
    assert.equal(stopped, 1);
});


