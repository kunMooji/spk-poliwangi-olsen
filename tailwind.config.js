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
                // Display badge-like face untuk headline & label kartu RIASEC —
                // dipakai hemat (headline, eyebrow, huruf besar) agar terasa
                // "sangar" tanpa mengorbankan keterbacaan body text.
                display: ['"Unbounded"', ...defaultTheme.fontFamily.sans],
                // Muka utilitas untuk angka/kode (statistik, kode Holland,
                // kode kriteria) — memberi kesan "dossier/sertifikat" data-driven.
                mono: ['"Space Mono"', ...defaultTheme.fontFamily.mono],
            },
            transitionTimingFunction: {
                // Easing kustom untuk hover/active pada tombol dan kartu —
                // lebih halus dari ease-in-out bawaan Tailwind.
                'brand-out': 'cubic-bezier(0.32, 0.72, 0, 1)',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0) scale(1)' },
                    '50%': { transform: 'translateY(-22px) scale(1.06)' },
                },
                shimmer: {
                    '0%': { backgroundPosition: '200% 0' },
                    '100%': { backgroundPosition: '-200% 0' },
                },
            },
            animation: {
                // Animasi masuk saat halaman dimuat — memakai easing yang
                // sama dengan `ease-brand-out` (nilai literal karena
                // `animation` shorthand tidak bisa merujuk ke utility class).
                'fade-up': 'fade-up 0.8s cubic-bezier(0.32, 0.72, 0, 1) both',
                float: 'float 10s cubic-bezier(0.45, 0.05, 0.55, 0.95) infinite',
                shimmer: 'shimmer 2.8s linear infinite',
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
                // Navy nyaris hitam — latar "mewah & sangar" untuk hero, band
                // gelap, dan kartu terbuka pada dek RIASEC.
                ink: {
                    50: '#eef4f6',
                    100: '#d7e4e8',
                    200: '#aec7cf',
                    300: '#7fa3ae',
                    400: '#4c7986',
                    500: '#2e5866',
                    600: '#1c3f4b',
                    700: '#152f39',
                    800: '#0f232b',
                    900: '#0a1a20',
                    950: '#050e13',
                },
                // Aksen kuningan/emas — sentuhan "mewah" pada badge, garis
                // pemisah, dan detail foil di kartu RIASEC & CTA.
                gold: {
                    50: '#fbf3e3',
                    100: '#f6e3bb',
                    200: '#eecf94',
                    300: '#e3b665',
                    400: '#d29a3f',
                    500: '#b17c2c',
                    600: '#8f6222',
                    700: '#704c1b',
                    800: '#553a15',
                    900: '#3c2a10',
                },
                // Krem hangat untuk latar terang — pengganti putih polos agar
                // mode terang tetap terasa premium, bukan default template.
                porcelain: {
                    50: '#faf8f3',
                    100: '#f2ede2',
                    200: '#e6ddc9',
                },
            },
        },
    },

    plugins: [forms],
};