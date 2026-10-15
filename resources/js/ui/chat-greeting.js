const EASING = 'cubic-bezier(0.22, 1, 0.36, 1)';

export function initChatGreeting(root, avatars = []) {
    const dialog = root.querySelector('[data-chat-greeting-dialog]');
    const stage = root.querySelector('[data-chat-greeting-stage]');
    const video = root.querySelector('[data-chat-greeting-video]');
    if (! dialog?.showModal || ! stage || ! video) return { dismiss() {} };
    const scrim = root.querySelector('[data-chat-greeting-scrim]');
    const copy = root.querySelector('[data-chat-greeting-copy]');
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const animations = [];
    let phase = 'loading', timer, safetyTimer, previousFocus;
    const visibleHome = () => [...root.querySelectorAll('[data-chat-greeting-home]')].find((node) => {
        const box = node.getBoundingClientRect();
        return box.width > 0 && box.height > 0;
    });
    const animate = (node, frames, duration) => {
        if (reduced || ! node?.animate) return null;
        const animation = node.animate(frames, { duration, easing: EASING, fill: 'both' });
        animations.push(animation);
        return animation;
    };
    const fit = (box, cover = false) => {
        const ratio = video.videoWidth / video.videoHeight;
        if (! Number.isFinite(ratio) || ratio <= 0) return null;
        const width = (cover ? Math.max : Math.min)(box.width, box.height * ratio);
        const height = width / ratio;
        return { width: width + 'px', height: height + 'px', left: (box.width - width) / 2 + 'px', top: (box.height - height) / 2 + 'px' };
    };
    const finish = () => {
        if (phase === 'done') return;
        phase = 'done';
        clearTimeout(timer);
        clearTimeout(safetyTimer);
        video.pause();
        const homeAvatar = visibleHome()?.querySelector('[data-chat-avatar]');
        avatars.find((entry) => entry.element === homeAvatar)?.greeting.seek?.(video.currentTime);
        if (! document.hidden) avatars.forEach(({ greeting }) => greeting.play().catch(() => {}));
        dialog.removeEventListener('cancel', onCancel);
        dialog.removeEventListener('close', onClose);
        window.removeEventListener('pagehide', finish);
        document.removeEventListener('visibilitychange', onVisibility);
        video.removeEventListener('playing', ready);
        video.removeEventListener('error', finish);
        if (dialog.open) dialog.close();
        animations.forEach((animation) => animation.cancel());
        stage.removeAttribute('style');
        video.removeAttribute('style');
        if (! document.hidden) {
            const target = previousFocus?.isConnected && previousFocus !== document.body ? previousFocus : root.querySelector('[data-chat-messages]');
            target?.focus({ preventScroll: true });
        }
    };
    const dismiss = (immediate = false) => {
        if (phase === 'done') return;
        if (immediate || reduced || phase === 'loading') { finish(); return; }
        if (phase === 'docking') return;
        phase = 'docking';
        clearTimeout(timer);
        const home = visibleHome();
        if (! home) { finish(); return; }
        const start = stage.getBoundingClientRect(), end = home.getBoundingClientRect();
        const rectangle = (box) => ({ left: box.left + 'px', top: box.top + 'px', width: box.width + 'px', height: box.height + 'px' });
        Object.assign(stage.style, rectangle(start), { transform: 'none' });
        const motion = animate(stage, [
            { ...rectangle(start), borderRadius: '28px', boxShadow: '0 24px 80px rgb(0 0 0 / 0.2)' },
            { ...rectangle(end), borderRadius: getComputedStyle(home).borderRadius, boxShadow: '0 0 0 rgb(0 0 0 / 0)' },
        ], 750);
        const targetPlayer = home.querySelector('[data-avatar-greeting] video');
        const first = fit(start), last = fit(end, targetPlayer && getComputedStyle(targetPlayer).objectFit === 'cover');
        if (first && last) animate(video, [first, last], 750);
        animate(copy, [{ opacity: 1 }, { opacity: 0 }], 180);
        animate(scrim, [{ opacity: 1 }, { opacity: 0 }], 650);
        if (! motion) { finish(); return; }
        motion.finished.then(finish).catch(() => {});
        timer = setTimeout(finish, 900);
    };
    function ready() {
        if (phase !== 'loading' || video.paused) return;
        phase = 'showing';
        clearTimeout(safetyTimer);
        const fitted = fit(stage.getBoundingClientRect());
        if (fitted) Object.assign(video.style, fitted);
        timer = setTimeout(() => dismiss(), reduced ? 600 : 1100);
    }
    function onCancel(event) { event.preventDefault(); dismiss(); }
    function onClose() { if (! dialog.open) finish(); }
    function onVisibility() { if (document.hidden) finish(); }
    dialog.addEventListener('cancel', onCancel);
    dialog.addEventListener('close', onClose);
    video.addEventListener('playing', ready);
    video.addEventListener('error', finish);
    window.addEventListener('pagehide', finish);
    document.addEventListener('visibilitychange', onVisibility);
    previousFocus = document.activeElement;
    avatars.forEach(({ greeting }) => greeting.pause());
    video.muted = true;
    try { video.currentTime = 0; } catch {}
    try { dialog.showModal(); } catch { finish(); return { dismiss }; }
    animate(stage, [{ opacity: 0 }, { opacity: 1 }], 300);
    animate(scrim, [{ opacity: 0 }, { opacity: 1 }], 300);
    safetyTimer = setTimeout(finish, 8000);
    video.play().then(ready).catch(finish);
    return { dismiss };
}
