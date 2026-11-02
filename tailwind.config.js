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
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                eec: {
                    50:  '#edfcfd',
                    100: '#d1f6f9',
                    200: '#a8ecf3',
                    300: '#6ddde9',
                    400: '#2cc5d5',
                    500: '#0ba3b2',
                    600: '#0d7a8a',
                    700: '#0e6272',
                    800: '#115060',
                    900: '#124350',
                    950: '#072b35',
                },
            },
            backgroundImage: {
                'eec-gradient':   'linear-gradient(135deg, #0ba3b2 0%, #0d7a8a 50%, #085f6c 100%)',
                'eec-gradient-r': 'linear-gradient(to right, #0ba3b2, #07c6d6)',
                'eec-dark':       'linear-gradient(180deg, #0b1e2d 0%, #072b35 100%)',
            },
            boxShadow: {
                'eec':    '0 4px 24px 0 rgba(11,163,178,0.18)',
                'eec-lg': '0 8px 40px 0 rgba(11,163,178,0.25)',
                'card':   '0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px 0 rgba(0,0,0,0.04)',
            },
            animation: {
                'shimmer':       'shimmer 2s linear infinite',
                'slide-in-left': 'slideInLeft 0.3s ease both',
                'slide-in-up':   'slideInUp 0.35s ease both',
                'fade-in':       'fadeIn 0.25s ease both',
                'pulse-ring':    'pulseRing 2s infinite',
                'gradient-shift':'gradientShift 8s ease infinite',
            },
        },
    },

    plugins: [forms],
};
