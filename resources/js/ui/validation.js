import $ from 'jquery';
import 'jquery-validation';
import { showLoader } from './loader';

function applySpanishMessages() {
    $.extend($.validator.messages, {
        required: 'Este campo es obligatorio.',
        email: 'Ingresa un correo electrónico válido.',
        equalTo: 'Las contraseñas no coinciden.',
        maxlength: $.validator.format('No puede tener más de {0} caracteres.'),
        minlength: $.validator.format('Debe tener al menos {0} caracteres.'),
        remote: 'Por favor corrige este campo.',
    });
}

const DEFAULTS = {
    errorClass: 'input-invalid',
    validClass: 'input-valid',
    errorElement: 'p',

    highlight(element, errorClass, validClass) {
        $(element).addClass(errorClass).removeClass(validClass);
    },

    unhighlight(element, errorClass, validClass) {
        $(element).removeClass(errorClass).addClass(validClass);
    },

    errorPlacement(errorEl, element) {
        errorEl.addClass('text-error text-xs mt-1');

        const wrapper = element.closest('.relative');

        if (wrapper.length) {
            errorEl.insertAfter(wrapper);
        } else {
            errorEl.insertAfter(element);
        }
    },

    submitHandler(form) {
        const email = form.querySelector('input[type="email"]');

        if (email) {
            email.value = email.value.trim().toLowerCase();
        }

        showLoader();
        form.submit();
    },
};

const RULES = {
    login: {
        email: { required: true, email: true },
        password: { required: true },
    },
    register: {
        name: { required: true, minlength: 3, maxlength: 255 },
        email: { required: true, email: true, maxlength: 255 },
        password: { required: true, minlength: 8 },
        password_confirmation: { required: true, equalTo: '#password' },
    },
    'forgot-password': {
        email: { required: true, email: true, maxlength: 255 },
    },
    'verify-code': {
        code: { required: true, digits: true, minlength: 6, maxlength: 6 },
    },
    'reset-password': {
        password: { required: true, minlength: 8 },
        password_confirmation: { required: true, equalTo: '#password' },
    },
    profile: {
        name: { required: true, minlength: 3, maxlength: 255 },
        email: { required: true, email: true, maxlength: 255 },
    },
    'profile-password': {
        current_password: { required: true },
        password: { required: true, minlength: 8 },
        password_confirmation: { required: true, equalTo: '#new_password' },
    },
};

const MESSAGES = {
    login: {
        email: {
            required: 'Ingresa tu correo electrónico.',
            email: 'Ingresa un correo electrónico válido.',
        },
        password: {
            required: 'Ingresa tu contraseña.',
        },
    },
    register: {
        name: {
            required: 'Ingresa tu nombre completo.',
            minlength: 'El nombre debe tener al menos 3 caracteres.',
        },
        email: {
            required: 'Ingresa tu correo electrónico.',
            email: 'Ingresa un correo electrónico válido.',
        },
        password: {
            required: 'Ingresa una contraseña.',
            minlength: 'La contraseña debe tener al menos 8 caracteres.',
        },
        password_confirmation: {
            required: 'Confirma tu contraseña.',
            equalTo: 'Las contraseñas no coinciden.',
        },
    },
    'forgot-password': {
        email: {
            required: 'Ingresa tu correo electrónico.',
            email: 'Ingresa un correo electrónico válido.',
        },
    },
    'verify-code': {
        code: {
            required: 'Ingresa el código que te enviamos.',
            digits: 'El código solo tiene números.',
            minlength: 'El código tiene 6 dígitos.',
            maxlength: 'El código tiene 6 dígitos.',
        },
    },
    'reset-password': {
        password: {
            required: 'Ingresa tu contraseña nueva.',
            minlength: 'La contraseña debe tener al menos 8 caracteres.',
        },
        password_confirmation: {
            required: 'Confirma tu contraseña nueva.',
            equalTo: 'Las contraseñas no coinciden.',
        },
    },
    profile: {
        name: {
            required: 'Ingresa tu nombre completo.',
            minlength: 'El nombre debe tener al menos 3 caracteres.',
        },
        email: {
            required: 'Ingresa tu correo electrónico.',
            email: 'Ingresa un correo electrónico válido.',
        },
    },
    'profile-password': {
        current_password: {
            required: 'Ingresa tu contraseña actual.',
        },
        password: {
            required: 'Ingresa tu contraseña nueva.',
            minlength: 'La contraseña debe tener al menos 8 caracteres.',
        },
        password_confirmation: {
            required: 'Confirma tu contraseña nueva.',
            equalTo: 'Las contraseñas no coinciden.',
        },
    },
};

function initCodeInput() {
    const $code = $('input[name="code"][maxlength="6"]');

    if (! $code.length) {
        return;
    }

    $code.on('input paste', function () {
        setTimeout(() => {
            const clean = this.value.replace(/\D/g, '').slice(0, 6);

            if (this.value !== clean) {
                this.value = clean;
            }

            if (clean.length === 6) {
                this.form.requestSubmit();
            }
        }, 0);
    });
}

export function initValidation() {
    if (! $.validator) {
        return;
    }

    applySpanishMessages();
    $.validator.setDefaults(DEFAULTS);

    $('form[data-validate]').each(function () {
        const name = $(this).data('validate');

        $(this).validate({
            rules: RULES[name] ?? {},
            messages: MESSAGES[name] ?? {},
        });
    });

    $('form[data-loader]:not([data-validate])').on('submit', showLoader);

    initCodeInput();
}
