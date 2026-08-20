import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                background: 'var(--background)',
                foreground: 'var(--foreground)',
                card: { DEFAULT: 'var(--card)', foreground: 'var(--card-foreground)' },
                primary: { DEFAULT: 'var(--primary)', foreground: 'var(--primary-foreground)' },
                secondary: { DEFAULT: 'var(--secondary)', foreground: 'var(--secondary-foreground)' },
                muted: { DEFAULT: 'var(--muted)', foreground: 'var(--muted-foreground)' },
                accent: { DEFAULT: 'var(--accent)', foreground: 'var(--accent-foreground)' },
                success: 'var(--success)',
                destructive: 'var(--destructive)',
                border: 'var(--border)',
                input: 'var(--input)',
                ring: 'var(--ring)',
            },
            borderRadius: {
                xl: '0.75rem',
                '2xl': '1rem',
                '3xl': '1.25rem',
            },
            height: { 13: '3.25rem' },
            minHeight: { 44: '11rem', 48: '12rem', 52: '13rem', 56: '14rem' },
        },
    },

    plugins: [forms],
};
