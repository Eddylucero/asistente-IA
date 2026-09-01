import Swal from 'sweetalert2';
import { showLoader } from './loader';

const WIDE_SCREEN = '(min-width: 640px)';

const BASE_OPTIONS = {
    buttonsStyling: false,
    reverseButtons: true,
    customClass: {
        popup: 'shadow-[0_20px_60px_rgba(0,0,0,0.12)]',
        title: 'font-headline-md text-headline-md',
        htmlContainer: 'font-body-md text-body-md text-on-surface-variant',
        confirmButton: 'px-6 py-3 mx-1 rounded-[1.25rem] bg-primary text-on-primary font-label-md text-label-md shadow-md hover:shadow-lg transition-all cursor-pointer',
        cancelButton: 'px-6 py-3 mx-1 rounded-[1.25rem] bg-surface-container text-on-surface font-label-md text-label-md hover:bg-surface-container-high transition-colors cursor-pointer',
    },
};

const TIMER = 3200;

export function toast(icon, title) {
    if (window.matchMedia(WIDE_SCREEN).matches) {
        return Swal.fire({
            ...BASE_OPTIONS,
            toast: true,
            position: 'top-end',
            icon,
            title,
            showConfirmButton: false,
            timer: TIMER,
            timerProgressBar: true,
            customClass: {
                ...BASE_OPTIONS.customClass,
                popup: 'shadow-[0_10px_30px_rgba(0,0,0,0.12)]',
                title: 'font-label-md text-label-md',
            },
        });
    }

    return Swal.fire({
        ...BASE_OPTIONS,
        position: 'center',
        width: '17rem',
        icon,
        title,
        showConfirmButton: false,
        timer: TIMER,
        timerProgressBar: true,
        customClass: {
            ...BASE_OPTIONS.customClass,
            popup: 'shadow-[0_20px_60px_rgba(0,0,0,0.16)]',
            title: 'font-label-md text-label-md',
        },
    });
}

export function success(title, text = '') {
    return Swal.fire({ ...BASE_OPTIONS, icon: 'success', title, text });
}

export function error(title, text = '') {
    return Swal.fire({ ...BASE_OPTIONS, icon: 'error', title, text });
}

export function initFlashAlerts() {
    const node = document.getElementById('swal-flash');

    if (! node) {
        return;
    }

    let payload;

    try {
        payload = JSON.parse(node.textContent);
    } catch {
        return;
    }

    if (! payload?.title) {
        return;
    }

    if (payload.toast === false) {
        Swal.fire({ ...BASE_OPTIONS, icon: payload.icon ?? 'success', title: payload.title, text: payload.text ?? '' });
    } else {
        toast(payload.icon ?? 'success', payload.title);
    }
}

function confirmBeforeSubmit(selector, { title, text, confirmButtonText }) {
    document.querySelectorAll(selector).forEach((form) => {
        form.addEventListener('submit', async (event) => {
            if (form.dataset.confirmed === 'true') {
                return;
            }

            event.preventDefault();

            const result = await Swal.fire({
                ...BASE_OPTIONS,
                icon: 'question',
                title,
                text,
                showCancelButton: true,
                confirmButtonText,
                cancelButtonText: 'Cancelar',
            });

            if (result.isConfirmed) {
                form.dataset.confirmed = 'true';
                showLoader();
                form.submit();
            }
        });
    });
}

export function initLogoutConfirm() {
    confirmBeforeSubmit('form[data-confirm-logout]', {
        title: '¿Cerrar sesión?',
        text: 'Tendrás que iniciar sesión de nuevo para volver a entrar.',
        confirmButtonText: 'Sí, cerrar sesión',
    });
}

export function initResendConfirm() {
    confirmBeforeSubmit('form[data-confirm-resend]', {
        title: '¿Enviar otro código?',
        text: 'El código anterior dejará de funcionar y te llegará uno nuevo.',
        confirmButtonText: 'Sí, reenviar',
    });
}

export function initConversationDeleteConfirm() {
    confirmBeforeSubmit('form[data-confirm-conversation-delete]', {
        title: '¿Eliminar conversación?',
        text: 'Esta acción eliminará la conversación y todos sus mensajes.',
        confirmButtonText: 'Sí, eliminar',
    });
}

export function initAccountDeleteConfirm() {
    confirmBeforeSubmit('form[data-confirm-account-delete]', {
        title: '¿Eliminar tu cuenta?',
        text: 'Se borrarán tus conversaciones y mensajes. Esta acción no se puede deshacer.',
        confirmButtonText: 'Sí, eliminar mi cuenta',
    });
}

export { Swal };
