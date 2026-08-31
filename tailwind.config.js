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
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#fdf2f3',
                    100: '#fbe6e8',
                    200: '#f5c4c9',
                    300: '#ec9aa3',
                    400: '#de6675',
                    500: '#c73b4f',
                    600: '#a6192e',
                    700: '#8c1528',
                    800: '#70111f',
                    900: '#5a0e1a',
                    950: '#3a070f',
                },
            },
            boxShadow: {
                card: '0 1px 3px 0 rgb(0 0 0 / 0.05), 0 1px 2px -1px rgb(0 0 0 / 0.05)',
                lift: '0 4px 6px -1px rgb(0 0 0 / 0.08), 0 2px 4px -2px rgb(0 0 0 / 0.06)',
            },
        },
    },

    plugins: [forms],
};
