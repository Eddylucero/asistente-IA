function closeAll(except = null) {
    document.querySelectorAll('[data-menu]').forEach((menu) => {
        if (menu === except) {
            return;
        }

        menu.classList.add('hidden');
        menu.parentElement?.querySelector('[data-menu-button]')?.setAttribute('aria-expanded', 'false');
    });
}

export function initMenus() {
    const buttons = document.querySelectorAll('[data-menu-button]');

    if (! buttons.length) {
        return;
    }

    buttons.forEach((button) => button.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        const menu = button.parentElement.querySelector('[data-menu]');

        closeAll(menu);
        menu.classList.toggle('hidden');
        button.setAttribute('aria-expanded', String(! menu.classList.contains('hidden')));
    }));

    document.addEventListener('click', () => closeAll());
    document.addEventListener('keydown', (event) => event.key === 'Escape' && closeAll());
}
