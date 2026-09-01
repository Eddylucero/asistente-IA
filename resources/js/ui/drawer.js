
const HIDDEN = '-translate-x-full';
const LARGE_SCREEN = '(min-width: 1024px)';

let drawer = null;
let scrim = null;
let toggles = [];

function isOpen() {
    return drawer !== null && ! drawer.classList.contains(HIDDEN);
}

function setExpanded(expanded) {
    toggles.forEach((button) => button.setAttribute('aria-expanded', String(expanded)));
}

export function openDrawer() {
    if (! drawer) {
        return;
    }

    drawer.classList.remove(HIDDEN);
    scrim?.classList.remove('opacity-0', 'pointer-events-none');
    document.body.classList.add('overflow-hidden');
    setExpanded(true);

    drawer.querySelector('[data-drawer-close]')?.focus();
}

export function closeDrawer({ restoreFocus = true } = {}) {
    if (! drawer) {
        return;
    }

    drawer.classList.add(HIDDEN);
    scrim?.classList.add('opacity-0', 'pointer-events-none');
    document.body.classList.remove('overflow-hidden');
    setExpanded(false);

    if (restoreFocus) {
        toggles[0]?.focus();
    }
}

export function toggleDrawer() {
    return isOpen() ? closeDrawer() : openDrawer();
}

export function initDrawer() {
    drawer = document.querySelector('[data-drawer]');

    if (! drawer) {
        return;
    }

    scrim = document.querySelector('[data-drawer-scrim]');
    toggles = Array.from(document.querySelectorAll('[data-drawer-toggle]'));

    toggles.forEach((button) => button.addEventListener('click', toggleDrawer));
    scrim?.addEventListener('click', () => closeDrawer());
    drawer.querySelector('[data-drawer-close]')?.addEventListener('click', () => closeDrawer());

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen()) {
            closeDrawer();
        }
    });

    window.matchMedia(LARGE_SCREEN).addEventListener('change', (event) => {
        if (event.matches) {
            closeDrawer({ restoreFocus: false });
        }
    });
}
