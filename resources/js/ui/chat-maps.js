const MAPS = {
    MATRIZ: ['Campus La Matriz', 'Universidad+Tecnica+de+Cotopaxi+Campus+Matriz+Latacunga'],
    SALACHE: ['Campus Salache', 'Universidad+Tecnica+de+Cotopaxi+Campus+Salache'],
    PUJILI: ['Extensión Pujilí', 'Universidad+Tecnica+de+Cotopaxi+Extension+Pujili'],
    LA_MANA: ['Extensión La Maná', 'Universidad+Tecnica+de+Cotopaxi+Extension+La+Mana'],
    SALCEDO: ['Campus Salcedo', 'Universidad+Tecnica+de+Cotopaxi+Campus+Salcedo'],
};

function locationInfo(location) {
    const id = Object.hasOwn(MAPS, location) ? location : 'MATRIZ';
    const [title, query] = MAPS[id];
    return { id, title, href: `https://www.google.com/maps?q=${query}` };
}

export function utcMapCard(location) {
    const { id, title, href } = locationInfo(location);
    return `<div class="message-map-card">
        <div class="flex items-center justify-between gap-3 p-3">
            <span class="inline-flex items-center gap-2 text-label-md font-label-md"><span class="material-symbols-outlined text-primary" aria-hidden="true">location_on</span>${title}</span>
            <a class="shrink-0 text-label-sm text-primary underline underline-offset-2" href="${href}" target="_blank" rel="noopener noreferrer">Abrir en Google Maps</a>
        </div>
        <div data-map-viewport>
            <button class="w-full bg-primary/10 px-4 py-4 text-label-md font-label-md text-primary hover:bg-primary/20" type="button" data-map-load="${id}">Ver mapa</button>
        </div>
    </div>`;
}

export function initChatMaps(root) {
    root.addEventListener('click', (event) => {
        const button = event.target.closest?.('[data-map-load]');
        if (! button || ! root.contains(button)) return;
        const { title, href } = locationInfo(button.dataset.mapLoad);
        const iframe = document.createElement('iframe');
        iframe.title = `Mapa de ${title} de la UTC`;
        iframe.src = `${href}&output=embed`;
        button.parentElement.replaceChildren(iframe);
    });
}
