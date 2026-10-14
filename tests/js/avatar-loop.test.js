import test from 'node:test';
import assert from 'node:assert/strict';
import { createAvatarLoop } from '../../resources/js/ui/avatar-loop.js';

function setup() {
    const frames = new Map();
    let id = 0;
    let now = 0;
    globalThis.window = {
        performance: { now: () => now },
        requestAnimationFrame(callback) { frames.set(++id, callback); return id; },
        cancelAnimationFrame(key) { frames.delete(key); },
    };
    const media = () => ({
        duration: 5, currentTime: 0, readyState: 4, style: {}, paused: true,
        play() { this.paused = false; return Promise.resolve(); },
        pause() { this.paused = true; },
        removeAttribute() {}, cloneNode: () => media(),
    });
    const videos = [media()];
    const layer = { querySelector: () => videos[0], appendChild: (video) => videos.push(video) };
    const player = createAvatarLoop(layer);
    const frame = (time) => {
        now = time;
        const callbacks = [...frames.values()];
        frames.clear();
        callbacks.forEach((callback) => callback(time));
    };
    return { player, videos, frame, frames };
}

test('overlaps the loop boundary without a blank frame and primes the next cycle', async () => {
    const { player, videos, frame } = setup();
    await player.play();
    const [outgoing, incoming] = videos;
    outgoing.currentTime = 4.5;
    frame(4500);
    await Promise.resolve();
    assert.equal(incoming.paused, false);
    frame(4750);
    assert.equal(Number(incoming.style.opacity), 0.5);
    assert.equal(outgoing.style.opacity, '1', 'outgoing stays underneath throughout the dissolve');
    assert.equal(outgoing.paused, false);
    frame(5000);
    assert.equal(incoming.style.opacity, '1');
    assert.equal(outgoing.style.opacity, '0');
    assert.equal(outgoing.paused, true);
    assert.equal(outgoing.currentTime, 0);
    incoming.currentTime = 4.5;
    frame(9500);
    await Promise.resolve();
    frame(10000);
    assert.equal(outgoing.style.opacity, '1', 'players swap again for the next loop');
    player.pause();
});

test('smooth pauses slow the clip, freeze the blended pose and resume without a cut', async () => {
    const { player, videos, frame } = setup();
    await player.play();
    videos[0].currentTime = 4.5;
    frame(4500);
    await Promise.resolve();
    frame(4600);
    const blendedOpacity = videos[1].style.opacity;
    player.pauseSmoothly();
    frame(4660);
    assert.ok(videos[0].playbackRate < 1 && videos[0].playbackRate > 0.25);
    frame(4720);
    assert.ok(videos.every((video) => video.paused));
    const pausedOpacity = videos[1].style.opacity;
    assert.notEqual(pausedOpacity, '0');
    assert.notEqual(pausedOpacity, '1');
    assert.notEqual(pausedOpacity, blendedOpacity);
    frame(5000);
    assert.equal(videos[1].style.opacity, pausedOpacity);
    await player.resumeSmoothly();
    assert.ok(videos.every((video) => !video.paused));
    frame(5060);
    assert.ok(videos[0].playbackRate > 0.25 && videos[0].playbackRate < 1);
    frame(5120);
    assert.equal(videos[0].playbackRate, 1);
    player.pause();
});

test('resuming during a slowdown cancels the pending pause', async () => {
    const { player, videos, frame } = setup();
    await player.play();
    player.pauseSmoothly();
    frame(60);
    await player.resumeSmoothly();
    frame(180);
    assert.equal(videos[0].paused, false);
    assert.equal(videos[0].playbackRate, 1);
    player.pause();
});

test('pausing cancels pending frames and resuming retains the current pose', async () => {
    const { player, videos, frame, frames } = setup();
    await player.play();
    videos[0].currentTime = 4.5;
    frame(4500);
    await Promise.resolve();
    videos[1].currentTime = 0.3;
    frame(4800);
    player.pause();
    assert.equal(frames.size, 0);
    assert.ok(videos.every((video) => video.paused));
    await player.play();
    assert.equal(videos[1].currentTime, 0.3);
    assert.equal(videos[1].paused, false);
    player.pause();
});

test('a standby failure leaves native looping available and never blanks the active clip', async () => {
    const { player, videos, frame } = setup();
    videos[1].play = () => Promise.reject(new Error('decode failed'));
    await player.play();
    videos[0].currentTime = 4.5;
    frame(4500);
    await Promise.resolve();
    await Promise.resolve();
    assert.equal(videos[0].loop, true);
    assert.equal(videos[0].paused, false);
    assert.equal(videos[0].style.opacity, '1');
    player.pause();
});
