
const STORAGE_KEY = 'theme';
const ROOT = document.documentElement;

export function currentTheme() {
    const saved = localStorage.getItem(STORAGE_KEY);

    if (saved === 'dark' || saved === 'light') {
        return saved;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function paint(theme) {
    ROOT.classList.toggle('dark', theme === 'dark');
    syncButtons(theme);
}

function syncButtons(theme) {
    const isDark = theme === 'dark';

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-pressed', String(isDark));
        button.setAttribute(
            'aria-label',
            isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro',
        );
        button.setAttribute(
            'title',
            isDark ? 'Modo claro' : 'Modo oscuro',
        );
    });
}

export function toggleTheme() {
    const next = currentTheme() === 'dark' ? 'light' : 'dark';

    localStorage.setItem(STORAGE_KEY, next);

    ROOT.classList.add('theme-switching');
    paint(next);

    window.setTimeout(() => ROOT.classList.remove('theme-switching'), 580);

    return next;
}

export function initTheme() {
    paint(currentTheme());

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => toggleTheme());
    });

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
        if (! localStorage.getItem(STORAGE_KEY)) {
            paint(event.matches ? 'dark' : 'light');
        }
    });
}
