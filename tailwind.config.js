import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Satoshi', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                bg: 'var(--color-bg)',
                surface: 'var(--color-surface)',
                ink: 'var(--color-text)',
                divider: 'var(--color-divider)',
                accent: {
                    DEFAULT: 'var(--color-accent)',
                    100: 'var(--color-accent-100)',
                    200: 'var(--color-accent-200)',
                    300: 'var(--color-accent-300)',
                    400: 'var(--color-accent-400)',
                    500: 'var(--color-accent-500)',
                    600: 'var(--color-accent-600)',
                    700: 'var(--color-accent-700)',
                    800: 'var(--color-accent-800)',
                    900: 'var(--color-accent-900)',
                },
                accent2: {
                    DEFAULT: 'var(--color-accent-2)',
                    100: 'var(--color-accent-2-100)',
                    200: 'var(--color-accent-2-200)',
                    300: 'var(--color-accent-2-300)',
                    400: 'var(--color-accent-2-400)',
                    500: 'var(--color-accent-2-500)',
                    600: 'var(--color-accent-2-600)',
                    700: 'var(--color-accent-2-700)',
                    800: 'var(--color-accent-2-800)',
                    900: 'var(--color-accent-2-900)',
                },
                muted: {
                    100: 'var(--color-neutral-100)',
                    200: 'var(--color-neutral-200)',
                    300: 'var(--color-neutral-300)',
                    400: 'var(--color-neutral-400)',
                    500: 'var(--color-neutral-500)',
                    600: 'var(--color-neutral-600)',
                    700: 'var(--color-neutral-700)',
                    800: 'var(--color-neutral-800)',
                    900: 'var(--color-neutral-900)',
                },
                danger: {
                    DEFAULT: 'var(--color-danger)',
                    bg: 'var(--color-danger-bg)',
                },
            },
            borderRadius: {
                sm: 'var(--radius-sm)',
                md: 'var(--radius-md)',
                lg: 'var(--radius-lg)',
            },
            boxShadow: {
                sm: 'var(--shadow-sm)',
                md: 'var(--shadow-md)',
                lg: 'var(--shadow-lg)',
            },
        },
    },

    plugins: [forms],
};
