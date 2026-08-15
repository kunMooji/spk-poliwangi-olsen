import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

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
                // Biru-teal institusional — menggantikan indigo bawaan Breeze
                // sebagai warna aksen utama (tombol, tautan, fokus, gradien).
                brand: {
                    50: '#eff9fb',
                    100: '#d7f0f5',
                    200: '#b3e3ec',
                    300: '#7ccddc',
                    400: '#3faec4',
                    500: '#2390aa',
                    600: '#15738c',
                    700: '#145d72',
                    800: '#164b5c',
                    900: '#163f4e',
                    950: '#0a2833',
                },
            },
        },
    },

    plugins: [forms],
};
