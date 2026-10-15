import test from 'node:test';
import assert from 'node:assert/strict';
import { initChatSpeech, speechText } from '../../resources/js/ui/chat-speech.js';

function element() {
    const events = {};
    const classes = new Set();
    return {
        hidden: false, disabled: false, textContent: '',
        classList: { add: (value) => classes.add(value), remove: (value) => classes.delete(value), contains: (value) => classes.has(value), toggle: (value, force) => force ? classes.add(value) : classes.delete(value) },
        addEventListener: (name, callback) => { events[name] = callback; },
        setAttribute() {},
        click: () => events.click?.({}),
        dispatch: (name, event) => events[name]?.(event),
        focus() { this.focused = true; },
    };
}

function setup(supported = true, reducedMotion = true, dataset = {}) {
    const stop = element(), status = element();
    const preferReading = element(), readTarget = element(), videoError = element();
    const dialog = Object.assign(element(), { open: false, showModal() { this.open = true; }, close() { this.open = false; } });
    const media = () => Object.assign(element(), { readyState: 1, currentTime: 0, plays: 0, pauses: 0, paused: true, style: {},
        cloneNode: () => media(), removeAttribute() {},
        play() { this.plays += 1; this.paused = false; return Promise.resolve(); },
        pause() { this.pauses += 1; this.paused = true; },
    });
    const video = media(), greeting = media();
    const layer = (source) => ({ classList: source.classList, querySelector: () => source, appendChild() {} });
    const talkingLayer = layer(video), greetingLayer = layer(greeting);
    video.classList.add('opacity-0');
    const utterances = [];
    const pageEvents = {};
    const frames = new Map();
    let frameId = 0;
    let frameTime = 0;
    const synth = { cancels: 0, pauseCalls: 0, resumeCalls: 0,
        cancel() { this.cancels += 1; }, pause() { this.pauseCalls += 1; }, resume() { this.resumeCalls += 1; },
        getVoices: () => [{ lang: 'en-US' }, { lang: 'es-MX' }], speak: (value) => utterances.push(value) };
    globalThis.window = { speechSynthesis: supported ? synth : undefined,
        SpeechSynthesisUtterance: class { constructor(text) { this.text = text; } }, addEventListener(name, callback) { pageEvents[name] = callback; }, matchMedia: () => ({ matches: reducedMotion }),
        performance: { now: () => frameTime },
        requestAnimationFrame(callback) { frames.set(++frameId, callback); return frameId; },
        cancelAnimationFrame(id) { frames.delete(id); },
    };
    globalThis.document = { addEventListener() {} };
    const selectors = { '[data-chat-speech-stop]': stop,
        '[data-chat-speech-dialog]': dialog, '[data-chat-messages]': readTarget, '[data-chat-video-error]': videoError };
    const root = {
        dataset,
        querySelector: (selector) => selectors[selector],
        querySelectorAll: (selector) => selector === '[data-chat-speech-status]' ? [status]
            : selector === '[data-chat-prefer-reading]' ? [preferReading]
                : [{ querySelector: (name) => name === '[data-avatar-talking]' ? talkingLayer : greetingLayer }],
    };
    const speech = initChatSpeech(root);
    const createReplay = (content) => {
        const button = element();
        speech.bindResponse(button, content);
        return button;
    };
    const advanceFrame = (time) => {
        frameTime = time;
        const callbacks = [...frames.values()];
        frames.clear();
        callbacks.forEach((callback) => callback(time));
    };
    return { speech, createReplay, stop, status, video, greeting, dialog, preferReading, readTarget, videoError, utterances, synth, advanceFrame, pageEvents };
}

test('reads link labels and table contents without URLs, map markers or Markdown', () => {
    assert.equal(speechText('**Hola**\n- Visita [la UTC](https://utc.edu.ec).\n[[UTC_MAPA_MATRIZ]]\n| Sede | Ciudad |\n| --- | --- |\n| Matriz | Latacunga |'), 'Hola Visita la UTC. Sede Ciudad Matriz Latacunga');
});

test('returning from browser history resumes greeting videos after page suspension', () => {
    const ui = setup();
    ui.pageEvents.pagehide();
    assert.equal(ui.greeting.paused, true);
    ui.pageEvents.pageshow({ persisted: true });
    assert.equal(ui.greeting.paused, false);
    assert.equal(ui.greeting.plays, 2);
    assert.equal(ui.video.paused, true);
    ui.pageEvents.pageshow({ persisted: false });
    assert.equal(ui.greeting.plays, 2, 'a new page already initializes its greeting once');
    ui.speech.stop({ immediate: true });
});

test('greets on entry, shows talking video in the modal during speech and resumes the greeting', async (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = setup();
    ui.speech.speak('Hola.');
    const utterance = ui.utterances[0];
    assert.equal(utterance.lang, 'es-MX');
    assert.equal(ui.video.plays, 0);
    assert.equal(ui.greeting.plays, 1);
    assert.equal(ui.greeting.classList.contains('opacity-0'), false);
    utterance.onstart();
    await Promise.resolve();
    assert.equal(ui.video.muted, true);
    assert.equal(ui.video.classList.contains('opacity-0'), false);
    assert.equal(ui.greeting.classList.contains('opacity-0'), true);
    assert.equal(ui.dialog.open, true);
    utterance.onend();
    assert.equal(ui.video.classList.contains('opacity-0'), false);
    assert.equal(ui.dialog.open, true);
    t.mock.timers.tick(451);
    assert.equal(ui.video.classList.contains('opacity-0'), true);
    assert.equal(ui.greeting.classList.contains('opacity-0'), false);
    assert.equal(ui.dialog.open, false);
    assert.equal(ui.stop.hidden, true);
});

test('silencing cancels playback, ignores old events and allows manual replay', async (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = setup();
    ui.speech.speak('Primera respuesta.');
    const old = ui.utterances[0];
    old.onstart();
    ui.preferReading.click();
    t.mock.timers.tick(1);
    old.onend();
    assert.equal(ui.video.classList.contains('opacity-0'), true);
    ui.speech.speak('Nueva respuesta.');
    assert.equal(ui.utterances.length, 1);
    ui.createReplay('Nueva respuesta.').click();
    assert.equal(ui.utterances[1].text, 'Nueva respuesta.');
    ui.speech.stop({ immediate: true });
});

test('long responses are read in one continuous request and errors stop the animation', (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = setup();
    const text = 'Esta es una respuesta larga. '.repeat(30).trim();
    ui.speech.speak(text);
    ui.video.currentTime = 0.42;
    assert.equal(ui.utterances.length, 1);
    assert.equal(ui.utterances[0].text, text);
    assert.equal(ui.utterances[0].rate, 1);
    ui.utterances[0].onstart();
    ui.utterances[0].onend();
    assert.equal(ui.video.currentTime, 0.42, 'speech pauses never restart the talking clip');
    ui.speech.speak('Otra respuesta.');
    ui.utterances.at(-1).onerror();
    t.mock.timers.tick(1);
    assert.equal(ui.video.classList.contains('opacity-0'), true);
    assert.match(ui.status.textContent, /No se pudo/);
});

test('missing TTS support leaves the chat available', () => {
    const ui = setup(false);
    assert.doesNotThrow(() => { ui.speech.speak('Hola'); ui.speech.stop(); });
    assert.equal(ui.createReplay('Una respuesta.').disabled, true);
    assert.match(ui.status.textContent, /no admite/);
    assert.equal(ui.greeting.plays, 1);
});

test('Mejor leer cancels speech, closes the modal and silences future automatic replies', (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = setup();
    ui.speech.speak('Una respuesta.');
    ui.utterances[0].onstart();
    ui.preferReading.click();
    t.mock.timers.tick(1);
    assert.equal(ui.dialog.open, false);
    assert.equal(ui.readTarget.focused, true);
    assert.equal(ui.greeting.classList.contains('opacity-0'), false);
    ui.utterances[0].onstart();
    assert.equal(ui.dialog.open, false, 'late events cannot reopen the modal');
    ui.speech.speak('Segunda respuesta.');
    assert.equal(ui.utterances.length, 1);
    ui.createReplay('Segunda respuesta.').click();
    assert.equal(ui.utterances.length, 2);
    assert.equal(ui.utterances[1].text, 'Segunda respuesta.');
    ui.speech.stop({ immediate: true });
});

test('each listen button reads its own response even after newer replies arrive', () => {
    const ui = setup();
    const first = ui.createReplay('Primera respuesta del historial.');
    const second = ui.createReplay('Segunda respuesta del historial.');
    ui.speech.speak('Respuesta más reciente.');
    first.click();
    assert.equal(ui.utterances.at(-1).text, 'Primera respuesta del historial.');
    second.click();
    assert.equal(ui.utterances.at(-1).text, 'Segunda respuesta del historial.');
    assert.equal(ui.createReplay('[[UTC_MAPA_MATRIZ]]').disabled, true);
    ui.speech.stop({ immediate: true });
});

test('talking video is visible before play resolves and playback failure is reported', async () => {
    const ui = setup();
    let rejectPlay;
    ui.video.play = () => new Promise((_, reject) => { rejectPlay = reject; });
    ui.speech.speak('Hola.');
    ui.utterances[0].onstart();
    assert.equal(ui.video.classList.contains('opacity-0'), false);
    rejectPlay(new Error('Cannot play'));
    await Promise.resolve();
    await Promise.resolve();
    assert.equal(ui.videoError.hidden, false);
    assert.equal(ui.video.classList.contains('opacity-0'), false);
    assert.equal(ui.dialog.open, true);
    ui.speech.stop({ immediate: true });
});

test('natural completion preserves the last pose throughout the exit and never cancels the audio', (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = setup(true, false);
    ui.speech.speak('Esta es la última frase.');
    ui.utterances[0].onstart();
    const cancellations = ui.synth.cancels;
    ui.utterances[0].onend();
    assert.equal(ui.synth.cancels, cancellations);
    assert.equal(ui.video.paused, false);
    ui.advanceFrame(150);
    assert.equal(ui.video.paused, true);
    t.mock.timers.tick(449);
    assert.equal(ui.dialog.classList.contains('is-closing'), false);
    t.mock.timers.tick(1);
    assert.equal(ui.dialog.classList.contains('is-closing'), true);
    assert.equal(ui.video.paused, true);
    t.mock.timers.tick(359);
    assert.equal(ui.dialog.open, true);
    assert.equal(ui.video.paused, true);
    t.mock.timers.tick(1);
    assert.equal(ui.dialog.open, false);
    assert.equal(ui.video.paused, true);
    assert.equal(ui.greeting.paused, false);
    assert.equal(ui.synth.cancels, cancellations);
});

test('commas, sentences, decimal numbers and titles stay in the same voice request', (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = setup();
    const text = 'Soy la Dra. Lumi, respira durante 3.5 segundos. ¿Continuamos? Sí.';
    ui.speech.speak(text);
    assert.equal(ui.utterances.length, 1);
    assert.equal(ui.utterances[0].text, text);
    assert.equal(ui.utterances[0].rate, 1);
    ui.utterances[0].onstart();
    t.mock.timers.tick(60000);
    assert.equal(ui.utterances.length, 1);
    assert.equal(ui.video.paused, false, 'no invented pauses when the voice emits no word events');
    ui.speech.stop({ immediate: true });
});

test('word events briefly pause only the video while the same voice request continues', (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = setup();
    ui.speech.speak('Hola, respira con calma.');
    ui.utterances[0].onstart();
    ui.video.currentTime = 0.45;
    const cancellations = ui.synth.cancels;
    ui.utterances[0].onboundary({ name: 'word', charIndex: 0 });
    t.mock.timers.tick(160);
    assert.equal(ui.video.paused, true);
    assert.equal(ui.dialog.open, true);
    assert.equal(ui.greeting.classList.contains('opacity-0'), true);
    t.mock.timers.tick(89);
    assert.equal(ui.utterances.length, 1);
    assert.equal(ui.video.paused, true);
    t.mock.timers.tick(1);
    assert.equal(ui.utterances.length, 1);
    assert.equal(ui.video.paused, false);
    assert.equal(ui.video.currentTime, 0.45);
    assert.equal(ui.dialog.open, true);
    assert.equal(ui.synth.cancels, cancellations);
    assert.equal(ui.synth.pauseCalls, 0);
    assert.equal(ui.synth.resumeCalls, 0);
    ui.speech.stop({ immediate: true });
});

test('native voice pause and resume events also control the robot without restarting', (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = setup();
    ui.speech.speak('Una frase sin pausas intermedias.');
    const utterance = ui.utterances[0];
    utterance.onstart();
    ui.video.currentTime = 0.6;
    utterance.onpause();
    assert.equal(ui.video.paused, true);
    assert.equal(ui.dialog.open, true);
    utterance.onresume();
    assert.equal(ui.video.paused, false);
    assert.equal(ui.video.currentTime, 0.6);
    ui.speech.stop({ immediate: true });
    utterance.onresume();
    assert.equal(ui.video.paused, true, 'late resume events cannot reactivate a cancelled reply');
});

test('Mejor leer cancels pending visual timers and prevents late video resumes', (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = setup();
    ui.speech.speak('Hola, continuamos después.');
    ui.utterances[0].onstart();
    ui.utterances[0].onboundary({ name: 'word', charIndex: 0 });
    t.mock.timers.tick(160);
    ui.preferReading.click();
    t.mock.timers.tick(1000);
    assert.equal(ui.utterances.length, 1);
    assert.equal(ui.dialog.open, false);
    assert.equal(ui.video.paused, true);
});

test('the next word cancels a pending visual pause as soon as speech advances', (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = setup();
    ui.speech.speak('Hola, continuamos hablando.');
    ui.utterances[0].onstart();
    ui.utterances[0].onboundary({ name: 'word', charIndex: 0 });
    t.mock.timers.tick(100);
    ui.utterances[0].onboundary({ name: 'word', charIndex: 6 });
    t.mock.timers.tick(1000);
    assert.equal(ui.video.paused, false);
    assert.equal(ui.utterances.length, 1);
    ui.speech.stop({ immediate: true });
});

test('a native voice pause remains paused even when a visual resume was scheduled', (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = setup();
    ui.speech.speak('Hola, continuamos hablando.');
    const utterance = ui.utterances[0];
    utterance.onstart();
    utterance.onboundary({ name: 'word', charIndex: 0 });
    t.mock.timers.tick(160);
    utterance.onpause();
    t.mock.timers.tick(1000);
    assert.equal(ui.video.paused, true);
    utterance.onresume();
    assert.equal(ui.video.paused, false);
    ui.speech.stop({ immediate: true });
});

test('a new reply cancels an older pending finish without closing the new modal', (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = setup(true, false);
    ui.speech.speak('Primera respuesta.');
    ui.utterances[0].onstart();
    ui.utterances[0].onend();
    t.mock.timers.tick(200);
    ui.speech.speak('Segunda respuesta.');
    ui.utterances[1].onstart();
    t.mock.timers.tick(1000);
    assert.equal(ui.dialog.open, true);
    assert.equal(ui.dialog.classList.contains('is-closing'), false);
    assert.equal(ui.video.paused, false);
    ui.speech.stop({ immediate: true });
});

test('Mejor leer cancels the voice immediately and restores focus after the exit', (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = setup(true, false);
    ui.speech.speak('Una respuesta.');
    ui.utterances[0].onstart();
    const cancellations = ui.synth.cancels;
    ui.preferReading.click();
    assert.equal(ui.synth.cancels, cancellations + 1);
    assert.equal(ui.dialog.open, true);
    assert.equal(ui.video.paused, false);
    t.mock.timers.tick(360);
    assert.equal(ui.dialog.open, false);
    assert.equal(ui.readTarget.focused, true);
    assert.equal(ui.video.paused, true);
});

test('local speech works without browser TTS and Mejor leer cancels pending synthesis', async (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = setup(false, true, { ttsVoice: 'piper:es_MX-claude-high', speechEndpoint: '/voz/sintesis' });
    document.querySelector = () => ({ content: 'csrf' });
    let request;
    let resolveFetch;
    t.mock.method(globalThis, 'fetch', (_url, options) => {
        request = options;
        return new Promise((resolve) => { resolveFetch = resolve; });
    });
    ui.speech.speak('**Hola**, ¿cómo estás?');
    assert.deepEqual(JSON.parse(request.body), { text: 'Hola , ¿cómo estás?', voice: 'piper:es_MX-claude-high' });
    assert.equal(ui.createReplay('Hola').disabled, false);
    ui.preferReading.click();
    assert.equal(request.signal.aborted, true);
    resolveFetch({ ok: true, blob: async () => new Blob(['wav']) });
    await new Promise((resolve) => setImmediate(resolve));
    t.mock.timers.tick(70000);
    assert.equal(ui.dialog.open, false);
    assert.equal(ui.video.plays, 0);
    assert.equal(ui.greeting.paused, false);
    assert.equal(ui.utterances.length, 0);
});

test('local audio opens the robot modal and preserves its pose at natural completion', async (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = setup(false, false, { ttsVoice: 'piper:es_AR-daniela-high', speechEndpoint: '/voz/sintesis' });
    document.querySelector = () => ({ content: 'csrf' });
    let audio;
    window.Audio = class {
        constructor() { audio = this; }
        play() { this.onplaying(); return Promise.resolve(); }
        pause() { this.onpause?.(); }
    };
    t.mock.method(globalThis, 'fetch', async () => ({ ok: true, blob: async () => new Blob(['wav']) }));
    ui.speech.speak('Hola.');
    await new Promise((resolve) => setImmediate(resolve));
    assert.equal(ui.dialog.open, true);
    assert.equal(ui.video.paused, false);
    audio.onended();
    ui.advanceFrame(150);
    assert.equal(ui.video.paused, true);
    t.mock.timers.tick(450);
    assert.equal(ui.dialog.classList.contains('is-closing'), true);
    assert.equal(ui.video.classList.contains('opacity-0'), false);
    t.mock.timers.tick(360);
    assert.equal(ui.dialog.open, false);
    assert.equal(ui.greeting.paused, false);
});
