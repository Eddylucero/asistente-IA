const LARGE_SCREEN = '(min-width: 1024px)';
const REDUCED_MOTION = '(prefers-reduced-motion: reduce)';

export function initParallax() {
    const target = document.querySelector('[data-parallax]');

    if (! target
        || ! window.matchMedia(LARGE_SCREEN).matches
        || window.matchMedia(REDUCED_MOTION).matches) {
        return;
    }

    const depth = Number(target.dataset.parallax) || 40;

    document.addEventListener('mousemove', (event) => {
        const x = (window.innerWidth / 2 - event.pageX) / depth;
        const y = (window.innerHeight / 2 - event.pageY) / depth;

        target.style.transform = `translate(${x}px, ${y}px)`;
    });

    document.addEventListener('mouseleave', () => {
        target.style.transform = 'translate(0px, 0px)';
    });
}
