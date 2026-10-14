// Overlap the end and beginning of a clip instead of exposing a hard loop cut.
export function createAvatarLoop(layer) {
    const first = layer.querySelector('video');
    const second = first.cloneNode(true);
    second.removeAttribute('autoplay');
    second.style.opacity = '0';
    layer.appendChild(second);
    const videos = [first, second];
    videos.forEach((video) => {
        video.muted = true;
        video.loop = true; // Native looping remains a fallback if a frame is delayed.
    });

    let active = first;
    let standby = second;
    let running = false;
    let frame;
    let generation = 0;
    let preparing = false;
    let fade = null;
    let pausedAt = null;
    let motionFrame;

    const cancelMotion = () => window.cancelAnimationFrame(motionFrame);
    const setRate = (rate) => videos.forEach((video) => { video.playbackRate = rate; });
    const easeRate = (target, after = () => {}, duration = 120) => {
        cancelMotion();
        if (duration <= 0 || window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches) {
            setRate(target);
            after();
            return;
        }
        const initial = active.playbackRate || 1;
        const start = window.performance.now();
        const step = (now) => {
            const progress = Math.min(1, (now - start) / duration);
            const eased = progress * progress * (3 - 2 * progress);
            setRate(initial + (target - initial) * eased);
            if (progress < 1) motionFrame = window.requestAnimationFrame(step);
            else after();
        };
        motionFrame = window.requestAnimationFrame(step);
    };

    const normalize = () => {
        active.style.opacity = '1';
        active.style.zIndex = '1';
        standby.style.opacity = '0';
        standby.style.zIndex = '2';
    };
    normalize();

    const tick = (now) => {
        if (! running) return;
        if (fade) {
            const progress = Math.min(1, (now - fade.start) / fade.duration);
            standby.style.opacity = String(progress * progress * (3 - 2 * progress));
            if (progress === 1) {
                active.pause();
                [active, standby] = [standby, active];
                normalize();
                standby.currentTime = 0;
                fade = null;
            }
        } else if (! preparing && Number.isFinite(active.duration) && active.duration > 0.2) {
            const overlap = Math.min(0.5, active.duration / 4);
            const remaining = active.duration - active.currentTime;
            if (remaining <= overlap && remaining > 0 && standby.readyState >= 2) {
                preparing = true;
                const current = generation;
                standby.currentTime = 0;
                standby.play().then(() => {
                    if (current !== generation || ! running) return;
                    preparing = false;
                    fade = {
                        start: window.performance.now(),
                        duration: Math.max(0.08, active.duration - active.currentTime) * 1000,
                    };
                    // Keep the overlap short even if the native clip has just wrapped.
                    fade.duration = Math.min(overlap * 1000, fade.duration);
                }).catch(() => {
                    if (current !== generation) return;
                    preparing = false;
                    standby.pause();
                });
            }
        }
        frame = window.requestAnimationFrame(tick);
    };

    const startPlayback = () => {
        if (! running) {
            if (fade && pausedAt !== null) fade.start += window.performance.now() - pausedAt;
            pausedAt = null;
            running = true;
            frame = window.requestAnimationFrame(tick);
        }
        return fade ? Promise.all([active.play(), standby.play()]) : active.play();
    };

    const pausePlayback = (preserveBlend = false) => {
        cancelMotion();
        running = false;
        generation += 1;
        window.cancelAnimationFrame(frame);
        pausedAt = window.performance.now();
        preparing = false;
        videos.forEach((video) => video.pause());
        if (! preserveBlend) {
            if (fade && Number(standby.style.opacity) >= 0.5) {
                [active, standby] = [standby, active];
            }
            fade = null;
            normalize();
            setRate(1);
        }
    };

    return {
        element: layer,
        play() {
            cancelMotion();
            setRate(1);
            return startPlayback();
        },
        pause() { pausePlayback(); },
        pauseSmoothly(duration = 120) {
            if (! running) return;
            easeRate(0.25, () => pausePlayback(true), duration);
        },
        resumeSmoothly(duration = 120) {
            cancelMotion();
            if (! running) setRate(0.25);
            const playback = startPlayback();
            easeRate(1, undefined, duration);
            return playback;
        },
    };
}
