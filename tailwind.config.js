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

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // --primary-color/--secondary-color hold "r g b" triplets (see
                // resources/css/app.css and useUserTheme.ts) so opacity modifiers
                // like `bg-secondary/20` work against user-supplied theme colors.
                primary: 'rgb(var(--primary-color) / <alpha-value>)',
                secondary: 'rgb(var(--secondary-color) / <alpha-value>)',
            },
        },
    },

    plugins: [forms],
};
