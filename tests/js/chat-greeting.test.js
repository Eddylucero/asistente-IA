import test from 'node:test';
import assert from 'node:assert/strict';
import { initChatGreeting } from '../../resources/js/ui/chat-greeting.js';

function setup() {
    const box = { left: 500, top: 250, width: 400, height: 400 };
    const element = () => ({ style: {}, animations: [],
        addEventListener() {}, removeEventListener() {},
        animate(frames) { this.animations.push(frames); return { finished: new Promise(() => {}), cancel() {} }; },
        getBoundingClientRect: () => box, removeAttribute() {}, focus() {},
    });
    let resolvePlay;
    const video = { ...element(), videoWidth: 720, videoHeight: 405, currentTime: 0, paused: true,
        play() { return new Promise((resolve) => { resolvePlay = () => { this.paused = false; resolve(); }; }); },
        pause() { this.paused = true; },
    };
    const avatar = element();
    const home = { ...element(), querySelector: (selector) => selector === '[data-chat-avatar]' ? avatar : null };
    const stage = element();
    const dialog = { ...element(), open: false, showModal() { this.open = true; }, close() { this.open = false; } };
    const nodes = {
        '[data-chat-greeting-dialog]': dialog, '[data-chat-greeting-stage]': stage,
        '[data-chat-greeting-video]': video, '[data-chat-greeting-scrim]': element(),
        '[data-chat-greeting-copy]': element(), '[data-chat-messages]': element(),
    };
    const greeting = { pauses: 0, resumes: 0, seek(time) { this.time = time; },
        pause() { this.pauses++; }, play() { this.resumes++; return Promise.resolve(); } };
    globalThis.window = { addEventListener() {}, removeEventListener() {}, matchMedia: () => ({ matches: false }) };
    globalThis.getComputedStyle = () => ({ objectFit: 'contain', borderRadius: '24px' });
    globalThis.document = { hidden: false, body: {}, addEventListener() {}, removeEventListener() {} };
    document.activeElement = document.body;
    return { root: { querySelector: (name) => nodes[name], querySelectorAll: () => [home] },
        video, greeting, avatar, stage, dialog, startVideo: () => resolvePlay() };
}

test('the independent greeting video starts before the short hold and then docks', async (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = setup();
    initChatGreeting(ui.root, [{ element: ui.avatar, greeting: ui.greeting }]);
    assert.equal(ui.dialog.open, true);
    assert.equal(ui.greeting.pauses, 1);
    t.mock.timers.tick(2000);
    assert.equal(ui.stage.animations.length, 1, 'loading time does not count as the greeting');
    ui.startVideo();
    await Promise.resolve();
    ui.video.currentTime = 1.6;
    t.mock.timers.tick(1100);
    assert.equal(ui.stage.animations.length, 2);
    t.mock.timers.tick(900);
    assert.equal(ui.dialog.open, false);
    assert.equal(ui.video.paused, true);
    assert.equal(ui.greeting.resumes, 1);
    assert.equal(ui.greeting.time, 1.6);
});

test('a late playback promise cannot restart a dismissed greeting', async (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const ui = setup();
    const intro = initChatGreeting(ui.root, [{ element: ui.avatar, greeting: ui.greeting }]);
    intro.dismiss(true);
    ui.startVideo();
    await Promise.resolve();
    t.mock.timers.tick(9000);
    assert.equal(ui.dialog.open, false);
    assert.equal(ui.stage.animations.length, 1);
    assert.equal(ui.greeting.resumes, 1);
});
