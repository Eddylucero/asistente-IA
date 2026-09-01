const OVERLAY_ID = 'app-loading-overlay';

function overlay() {
    let el = document.getElementById(OVERLAY_ID);

    if (el) {
        return el;
    }

    el = document.createElement('div');
    el.id = OVERLAY_ID;
    el.setAttribute('role', 'status');
    el.setAttribute('aria-live', 'polite');
    el.setAttribute('aria-label', 'Cargando');
    el.className = 'fixed inset-0 z-[9999] hidden items-center justify-center bg-surface/80 backdrop-blur-sm';
    el.innerHTML = `
        <div class="flex flex-col items-center gap-4">
            <div class="w-14 h-14 rounded-full border-4 border-primary/20 border-t-primary animate-spin"></div>
            <p class="font-label-md text-label-md text-on-surface-variant">Cargando...</p>
        </div>
    `;

    document.body.appendChild(el);

    return el;
}

export function showLoader() {
    const el = overlay();
    el.classList.remove('hidden');
    el.classList.add('flex');
}

export function hideLoader() {
    const el = document.getElementById(OVERLAY_ID);

    if (! el) {
        return;
    }

    el.classList.add('hidden');
    el.classList.remove('flex');
}

export function initLoader() {
    window.addEventListener('pageshow', hideLoader);
}
