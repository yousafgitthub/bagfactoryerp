import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Outfit', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                erp: {
                    bg: 'rgb(var(--erp-bg) / <alpha-value>)',
                    surface: 'rgb(var(--erp-surface) / <alpha-value>)',
                    border: 'rgb(var(--erp-border) / <alpha-value>)',
                    primary: 'rgb(var(--erp-primary) / <alpha-value>)',
                    muted: 'rgb(var(--erp-text-muted) / <alpha-value>)',
                },
            },
        },
    },

    plugins: [forms],
};