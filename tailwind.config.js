import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                'headline-md': ['Manrope'],
                'headline-lg-mobile': ['Manrope'],
                'body-lg': ['Inter'],
                'label-sm': ['Inter'],
                'body-md': ['Inter'],
                'display-lg': ['Manrope'],
                'label-md': ['Inter'],
                'headline-lg': ['Manrope']
            },
            // Cada color apunta a su variable en resources/css/app.css.
            // <alpha-value> deja que Tailwind genere tambien bg-primary/70 etc.
            colors: {
                "surface-variant": "rgb(var(--color-surface-variant) / <alpha-value>)",
                "surface-bright": "rgb(var(--color-surface-bright) / <alpha-value>)",
                "tertiary-container": "rgb(var(--color-tertiary-container) / <alpha-value>)",
                "surface-tint": "rgb(var(--color-surface-tint) / <alpha-value>)",
                "error": "rgb(var(--color-error) / <alpha-value>)",
                "outline-variant": "rgb(var(--color-outline-variant) / <alpha-value>)",
                "surface-container-low": "rgb(var(--color-surface-container-low) / <alpha-value>)",
                "on-error-container": "rgb(var(--color-on-error-container) / <alpha-value>)",
                "primary-fixed": "rgb(var(--color-primary-fixed) / <alpha-value>)",
                "on-background": "rgb(var(--color-on-background) / <alpha-value>)",
                "on-primary-fixed": "rgb(var(--color-on-primary-fixed) / <alpha-value>)",
                "secondary-container": "rgb(var(--color-secondary-container) / <alpha-value>)",
                "on-secondary-container": "rgb(var(--color-on-secondary-container) / <alpha-value>)",
                "on-primary": "rgb(var(--color-on-primary) / <alpha-value>)",
                "on-tertiary": "rgb(var(--color-on-tertiary) / <alpha-value>)",
                "on-surface": "rgb(var(--color-on-surface) / <alpha-value>)",
                "primary-container": "rgb(var(--color-primary-container) / <alpha-value>)",
                "tertiary-fixed-dim": "rgb(var(--color-tertiary-fixed-dim) / <alpha-value>)",
                "inverse-primary": "rgb(var(--color-inverse-primary) / <alpha-value>)",
                "secondary-fixed": "rgb(var(--color-secondary-fixed) / <alpha-value>)",
                "on-error": "rgb(var(--color-on-error) / <alpha-value>)",
                "inverse-on-surface": "rgb(var(--color-inverse-on-surface) / <alpha-value>)",
                "error-container": "rgb(var(--color-error-container) / <alpha-value>)",
                "surface-dim": "rgb(var(--color-surface-dim) / <alpha-value>)",
                "inverse-surface": "rgb(var(--color-inverse-surface) / <alpha-value>)",
                "on-tertiary-fixed": "rgb(var(--color-on-tertiary-fixed) / <alpha-value>)",
                "surface-container-high": "rgb(var(--color-surface-container-high) / <alpha-value>)",
                "secondary-fixed-dim": "rgb(var(--color-secondary-fixed-dim) / <alpha-value>)",
                "on-tertiary-container": "rgb(var(--color-on-tertiary-container) / <alpha-value>)",
                "on-secondary": "rgb(var(--color-on-secondary) / <alpha-value>)",
                "on-surface-variant": "rgb(var(--color-on-surface-variant) / <alpha-value>)",
                "outline": "rgb(var(--color-outline) / <alpha-value>)",
                "tertiary": "rgb(var(--color-tertiary) / <alpha-value>)",
                "on-primary-container": "rgb(var(--color-on-primary-container) / <alpha-value>)",
                "surface-container-lowest": "rgb(var(--color-surface-container-lowest) / <alpha-value>)",
                "on-secondary-fixed": "rgb(var(--color-on-secondary-fixed) / <alpha-value>)",
                "primary-fixed-dim": "rgb(var(--color-primary-fixed-dim) / <alpha-value>)",
                "primary": "rgb(var(--color-primary) / <alpha-value>)",
                "surface": "rgb(var(--color-surface) / <alpha-value>)",
                "on-primary-fixed-variant": "rgb(var(--color-on-primary-fixed-variant) / <alpha-value>)",
                "on-tertiary-fixed-variant": "rgb(var(--color-on-tertiary-fixed-variant) / <alpha-value>)",
                "secondary": "rgb(var(--color-secondary) / <alpha-value>)",
                "surface-container": "rgb(var(--color-surface-container) / <alpha-value>)",
                "surface-container-highest": "rgb(var(--color-surface-container-highest) / <alpha-value>)",
                "background": "rgb(var(--color-background) / <alpha-value>)",
                "on-secondary-fixed-variant": "rgb(var(--color-on-secondary-fixed-variant) / <alpha-value>)",
                "tertiary-fixed": "rgb(var(--color-tertiary-fixed) / <alpha-value>)"
            },
            borderRadius: {
                "DEFAULT": "0.25rem",
                "lg": "0.5rem",
                "xl": "0.75rem",
                "full": "9999px"
            },
            spacing: {
                "margin-desktop": "64px",
                "stack-sm": "12px",
                "stack-md": "24px",
                "stack-lg": "48px",
                "container-max": "1200px",
                "margin-mobile": "20px",
                "unit": "8px",
                "gutter": "24px"
            },
            // Movimiento "sereno": la app es de acompañamiento psicológico, así
            // que los desplazamientos van lentos y frenan de forma suave, sin
            // rebotes ni arranques bruscos. Se usan como `duration-calm ease-calm`.
            transitionDuration: {
                // Desplazamientos grandes (panel, cambio de tema).
                "calm": "520ms",
                // Respuesta al puntero: tiene que notarse al instante, así que
                // va mucho más corto que `calm`, pero con la misma curva.
                "soft": "260ms",
            },
            transitionTimingFunction: {
                "calm": "cubic-bezier(0.32, 0.72, 0, 1)",
            },
            fontSize: {
                "headline-md": ["24px", { "lineHeight": "32px", "fontWeight": "600" }],
                "headline-lg-mobile": ["24px", { "lineHeight": "32px", "fontWeight": "600" }],
                "body-lg": ["18px", { "lineHeight": "28px", "fontWeight": "400" }],
                "label-sm": ["12px", { "lineHeight": "16px", "fontWeight": "600" }],
                "body-md": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                "display-lg": ["48px", { "lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700" }],
                "label-md": ["14px", { "lineHeight": "20px", "letterSpacing": "0.01em", "fontWeight": "500" }],
                "headline-lg": ["32px", { "lineHeight": "40px", "letterSpacing": "-0.01em", "fontWeight": "600" }]
            }
        },
    },

    plugins: [forms],
};
