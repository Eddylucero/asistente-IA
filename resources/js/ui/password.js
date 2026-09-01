export function initPasswordToggles() {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);
        const icon = button.querySelector('.material-symbols-outlined');

        if (! input || ! icon) {
            return;
        }

        button.addEventListener('click', () => {
            const hidden = input.type === 'password';

            input.type = hidden ? 'text' : 'password';
            icon.textContent = hidden ? 'visibility_off' : 'visibility';
        });
    });
}
