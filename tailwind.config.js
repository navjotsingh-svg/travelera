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
                sans: ['Roboto', ...defaultTheme.fontFamily.sans],
                heading: ['Roboto', ...defaultTheme.fontFamily.sans],
                menu: ['Plus Jakarta Sans', 'Roboto', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#eef4ff',
                    100: '#d9e6ff',
                    500: '#2563eb',
                    600: '#1d4ed8',
                    700: '#0033a0',
                    800: '#00287d',
                    900: '#001d5c',
                },
            },
        },
    },

    plugins: [forms],
};
