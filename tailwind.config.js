import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

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
                sans: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                xxs: ['10px', '16px'],
                xs: ['12px', '18px'],
                sm: ['14px', '20px'],
                base: ['16px', '24px'],
                lg: ['20px', '28px'],
                xl: ['24px', '32px'],
                "2xl": ['28px', '36px'],
                xxl: ['30px', '42px'],
            },
            boxShadow: {
                'input': '0 1px 2px 0px rgba(12, 17, 29, 0.05)',
                'dialog': '0 12px 24px -4px rgba(12, 17, 29, 0.10)',
                'toast': '0 4px 20px 0 rgba(12, 17, 29, 0.08)',
                'box': '0 8px 16px -4px rgba(12, 17, 29, 0.08)',
                'table': '0 2px 8px 0 rgba(12, 17, 29, 0.05)',
                'dropdown': '0px 8px 16px -4px rgba(12, 17, 29, 0.08)',
            },
            screens: {
                'xs': '320px',
                'sm': '640px',
                'md': '768px',
                'lg': '1024px',
                'xl': '1280px',
                '2xl': '1536px',
                '3xl': '1792px',
            },
            typography: ({ theme }) => ({
                DEFAULT: {
                    css: {
                        '--tw-prose-counters': theme('colors.zinc[500]'),
                        h1: {
                            fontSize: '24px',
                            lineHeight: '32px',
                            margin: '0 auto',
                        },
                        h2: {
                            fontSize: '20px',
                            lineHeight: '28px',
                            margin: '0 auto',
                        },
                        p: {
                            fontSize: '14px',
                            lineHeight: '20px',
                            margin: '0 auto',
                        },
                        'li > p': {
                            marginTop: '0',
                            marginBottom: '0',
                        },
                        li: {
                            marginTop: '0',
                            marginBottom: '0',
                        },
                        maxWidth: '100ch',
                    },
                },
            }),
        },
    },

    plugins: [forms, require('tailwindcss-primeui'), typography],
};
