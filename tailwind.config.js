import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
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
            },
        },
    },

    safelist: [
        'bg-sky-50', 'bg-sky-500', 'border-sky-200', 'text-sky-700', 'text-sky-800', 'text-sky-900', 'hover:bg-sky-50/60', 'hover:text-sky-900',
        'bg-violet-50', 'bg-violet-100', 'border-violet-200', 'text-violet-900', 'hover:bg-violet-50/60', 'hover:text-violet-900',
        'bg-teal-500',
    ],

    plugins: [forms],
};
