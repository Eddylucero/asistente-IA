import test from 'node:test';
import assert from 'node:assert/strict';
import { initVoice } from '../../resources/js/ui/voice.js';

function element() {
    const events = {};
    return { attributes: {}, textContent: '', hidden: false, classList: { toggle() {} },
        setAttribute(name, value) { this.attributes[name] = value; },
        addEventListener(name, fn) { events[name] = fn; },
        click() { if (! this.disabled) events.click?.(); },
    };
}
function setup(t, supported = true, secure = true) {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const selectors = Object.fromEntries(['voice-button', 'voice-icon', 'voice-transcript', 'voice-waves', 'voice-stop', 'voice-retry']
        .map((name) => [`[data-${name}]`, element()]));
    const events = {}, documentEvents = {}, recognizers = [], requests = [], readings = [];
    class Recognition {
        constructor() { recognizers.push(this); this.stops = 0; this.aborts = 0; }
        start() { this.onstart(); }
        stop() { this.stops++; }
        abort() { this.aborts++; }
        result(...parts) {
            this.onresult({ results: parts.map(([text, final = false]) => Object.assign([{ transcript: text }], { isFinal: final })) });
        }
    }
    const root = { dataset: { endpoint: '/voz/mensajes', conversation: '7', chatUrl: '/conversacion' },
        querySelector: (name) => selectors[name], querySelectorAll: () => [],
    };
    globalThis.window = { isSecureContext: secure, SpeechRecognition: supported ? Recognition : undefined,
        addEventListener(name, fn) { events[name] = fn; } };
    globalThis.document = { hidden: false, querySelector: () => ({ content: 'csrf' }),
        createElement() { assert.fail('Voice must not render chat messages'); },
        addEventListener(name, fn) { documentEvents[name] = fn; } };
    let reply = async () => ({ ok: true, json: async () => ({ conversation: { id: 7 }, messages: [
        { id: 1, role: 'user', content: 'Hola Lumi' }, { id: 2, role: 'assistant', content: 'Hola, te escucho.' },
    ] }) });
    t.mock.method(globalThis, 'fetch', async (url, options) => { requests.push({ url, ...options }); return reply(); });
    let silence;
    initVoice(root, {
        monitorSilence: (callbacks) => { silence = callbacks; return () => {}; },
        createSpeech: (_root, options) => {
            assert.equal(options.inline, true);
            return { stop() {}, speak(content, callbacks) { readings.push({ content, ...callbacks }); } };
        },
    });
    t.after(() => events.pagehide());
    return { root, requests, readings, recognizers, documentEvents, silence: () => silence,
        button: selectors['[data-voice-button]'], stop: selectors['[data-voice-stop]'],
        transcript: selectors['[data-voice-transcript]'], retry: selectors['[data-voice-retry]'],
        respond(fn) { reply = fn; },
    };
}
const flush = async () => { for (let i = 0; i < 12; i++) await Promise.resolve(); };

test('browser recognition transcribes live and sends only text to the assistant after the phrase ends', async (t) => {
    const ui = setup(t);
    ui.button.click();
    const mic = ui.recognizers[0];
    mic.result(['Hola', false]);
    assert.equal(ui.transcript.textContent, 'Hola');
    mic.result(['Hola Lumi', true]);
    assert.equal(mic.stops, 0);
    mic.onspeechend();
    t.mock.timers.tick(1800);
    assert.equal(mic.stops, 1);
    mic.onend();
    await flush();
    assert.equal(ui.requests.length, 1);
    assert.equal(ui.requests[0].url, '/voz/mensajes');
    assert.deepEqual(JSON.parse(ui.requests[0].body), { content: 'Hola Lumi', conversation_id: 7, new_conversation: false });
    assert.equal(ui.readings[0].content, 'Hola, te escucho.');
    ui.readings[0].onReady();
    assert.equal(ui.recognizers.length, 1);
    ui.readings[0].onEnd();
    assert.equal(ui.recognizers.length, 2);
});

test('silence allows twelve seconds to start speaking and never sends an empty message', async (t) => {
    const ui = setup(t);
    ui.button.click();
    t.mock.timers.tick(3500);
    assert.equal(ui.recognizers[0].stops, 0);
    t.mock.timers.tick(8500);
    assert.equal(ui.recognizers[0].stops, 1);
    ui.recognizers[0].onend();
    await flush();
    assert.equal(ui.requests.length, 0);
    assert.equal(ui.recognizers.length, 1);
});

test('a short pause between finalized segments keeps the complete sentence together', async (t) => {
    const ui = setup(t);
    ui.button.click();
    const mic = ui.recognizers[0];
    mic.result(['Quiero contarte', true]);
    mic.onspeechend();
    t.mock.timers.tick(1000);
    mic.onspeechstart();
    t.mock.timers.tick(1000);
    assert.equal(mic.stops, 0);
    mic.result(['Quiero contarte', true], ['cómo me siento hoy', true]);
    mic.onspeechend();
    t.mock.timers.tick(1800);
    mic.onend();
    await flush();
    assert.equal(JSON.parse(ui.requests[0].body).content, 'Quiero contarte cómo me siento hoy');
});

test('pausing discards late recognition and page suspension cannot restart the microphone', async (t) => {
    const ui = setup(t);
    ui.button.click();
    ui.stop.click();
    ui.recognizers[0].result(['No enviar', true]);
    ui.recognizers[0].onend();
    assert.equal(ui.requests.length, 0);
    ui.button.click();
    ui.recognizers[1].result(['Hola Lumi', true]);
    ui.recognizers[1].onend();
    await flush();
    document.hidden = true;
    ui.documentEvents.visibilitychange();
    ui.readings[0].onEnd();
    assert.equal(ui.recognizers.length, 2);
});

test('recognition service errors explain the browser limitation and retain words for retry', async (t) => {
    const ui = setup(t);
    ui.button.click();
    ui.recognizers[0].result(['Frase recuperada', true]);
    ui.recognizers[0].onerror({ error: 'network' });
    assert.match(ui.transcript.textContent, /servicio de reconocimiento/);
    assert.equal(ui.retry.hidden, false);
    ui.retry.click();
    await flush();
    assert.equal(JSON.parse(ui.requests[0].body).content, 'Frase recuperada');
});

test('silence retains the last interim if the browser never produces a final result', async (t) => {
    const ui = setup(t);
    ui.button.click();
    ui.recognizers[0].result(['Mi frase provisional', false]);
    ui.silence().onSilence();
    t.mock.timers.tick(1200);
    await flush();
    assert.equal(JSON.parse(ui.requests[0].body).content, 'Mi frase provisional');
    ui.recognizers[0].onend();
    assert.equal(ui.requests.length, 1);
});

test('permission errors survive the end event', (t) => {
    const ui = setup(t);
    ui.button.click();
    ui.recognizers[0].onerror({ error: 'not-allowed' });
    ui.recognizers[0].onend();
    assert.match(ui.transcript.textContent, /Permite el acceso/);
    assert.equal(ui.requests.length, 0);
});

test('unsupported browsers remain disabled', (t) => {
    const unsupported = setup(t, false);
    assert.equal(unsupported.button.disabled, true);
});
