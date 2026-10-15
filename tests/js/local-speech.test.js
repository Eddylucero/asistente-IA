import test from 'node:test';
import assert from 'node:assert/strict';
import { playLocalSpeech } from '../../resources/js/ui/local-speech.js';

const settle = () => new Promise((resolve) => setImmediate(resolve));

function setup() {
    let audio;
    const events = [];
    globalThis.document = { querySelector: () => ({ content: 'token' }) };
    globalThis.window = {
        cancelAnimationFrame() {},
        Audio: class {
            constructor() { audio = this; this.paused = true; }
            play() { this.paused = false; this.onplaying(); return Promise.resolve(); }
            pause() { this.paused = true; this.onpause?.(); }
        },
    };
    const callbacks = {
        endpoint: '/voz/sintesis', voice: 'piper:es_AR-daniela-high', text: 'Hola',
        onStart: () => events.push('start'), onEnd: () => events.push('end'),
        onError: () => events.push('error'), onPause: () => events.push('pause'),
        onMotion: (moving) => events.push(moving),
    };
    return { callbacks, events, audio: () => audio };
}

test('authenticated audio request plays and releases the temporary URL on completion', async (t) => {
    const ui = setup();
    let request;
    const revoked = [];
    t.mock.method(URL, 'revokeObjectURL', (url) => revoked.push(url));
    t.mock.method(globalThis, 'fetch', async (url, options) => {
        request = { url, ...options };
        return { ok: true, blob: async () => new Blob(['audio']) };
    });
    playLocalSpeech(ui.callbacks);
    await settle();
    assert.equal(request.url, '/voz/sintesis');
    assert.equal(request.headers['X-CSRF-TOKEN'], 'token');
    assert.deepEqual(JSON.parse(request.body), { text: 'Hola', voice: 'piper:es_AR-daniela-high' });
    assert.deepEqual(ui.events, ['start', true]);
    ui.audio().ended = true;
    ui.audio().onended();
    assert.deepEqual(ui.events, ['start', true, 'end']);
    assert.equal(revoked.length, 1);
});

test('cancellation stops audio and ignores subsequent media events', async (t) => {
    const ui = setup();
    t.mock.method(globalThis, 'fetch', async () => ({ ok: true, blob: async () => new Blob(['audio']) }));
    const playback = playLocalSpeech(ui.callbacks);
    await settle();
    playback.cancel();
    assert.equal(ui.audio().paused, true);
    ui.audio().onplaying();
    ui.audio().onended();
    ui.audio().onerror();
    assert.deepEqual(ui.events, ['start', true]);
});

test('request and playback failures are reported once and release resources', async (t) => {
    const ui = setup();
    t.mock.method(globalThis, 'fetch', async () => ({ ok: false, json: async () => ({ message: 'Unavailable' }) }));
    playLocalSpeech(ui.callbacks);
    await settle();
    assert.deepEqual(ui.events, ['error']);
    assert.equal(ui.audio(), undefined);
});

test('silence pauses only the animation and sound resumes it without touching audio', async (t) => {
    const ui = setup();
    let frame;
    let sample = 128;
    let clock = 0;
    window.performance = { now: () => clock };
    window.requestAnimationFrame = (callback) => { frame = callback; return 1; };
    window.AudioContext = class {
        state = 'running';
        resume() { return Promise.resolve(); }
        close() { return Promise.resolve(); }
        createMediaElementSource() { return { connect() {} }; }
        createAnalyser() { return { connect() {}, getByteTimeDomainData(data) { data.fill(sample); } }; }
    };
    t.mock.method(globalThis, 'fetch', async () => ({ ok: true, blob: async () => new Blob(['audio']) }));
    const playback = playLocalSpeech(ui.callbacks);
    await settle();
    clock = 200;
    frame(clock);
    assert.deepEqual(ui.events, ['start', true, false]);
    assert.equal(ui.audio().paused, false);
    sample = 160;
    clock = 220;
    frame(clock);
    assert.deepEqual(ui.events, ['start', true, false, true]);
    assert.equal(ui.audio().paused, false);
    playback.cancel();
});

test('recent audio is reused for the same voice and text while a different voice generates its own audio', async (t) => {
    const ui = setup();
    const cache = new Map();
    let requests = 0;
    t.mock.method(globalThis, 'fetch', async () => {
        requests++;
        return { ok: true, blob: async () => new Blob(['audio']) };
    });
    for (const voice of ['piper:es_AR-daniela-high', 'piper:es_AR-daniela-high', 'piper:es_MX-claude-high']) {
        const playback = playLocalSpeech({ ...ui.callbacks, cache, voice });
        await settle();
        playback.cancel();
    }
    assert.equal(requests, 2);
    assert.equal(cache.size, 2);
    for (const text of ['one', 'two', 'three', 'four']) {
        const playback = playLocalSpeech({ ...ui.callbacks, cache, text });
        await settle();
        playback.cancel();
    }
    assert.equal(cache.size, 3);
});
