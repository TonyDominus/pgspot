/** @type {import('tailwindcss').Config} */
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

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
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                pg: {
                    primary: '#2E7D32',
                    'primary-dark': '#1B5E20',
                    secondary: '#00ACC1',
                    accent: '#FFB300',
                    background: '#F7F9FA',
                    surface: '#FFFFFF',
                    text: '#1C1C1C',
                    muted: '#6B7280',
                    success: '#43A047',
                    warning: '#FB8C00',
                    error: '#E53935',
                    fontanelle: '#1E88E5',
                    instagram: '#8E24AA',
                },
            },
            boxShadow: {
                sheet: '0 -4px 24px rgba(0,0,0,0.08)',
                card: '0 2px 12px rgba(0,0,0,0.06)',
                fab: '0 4px 16px rgba(46,125,50,0.35)',
            },
            borderRadius: {
                '4xl': '2rem',
            },
        },
    },

    plugins: [forms],
};
