import defaultTheme from 'tailwindcss/defaultTheme';

/**
 * Market ArahInn — design tokens.
 *
 * Palet diambil langsung dari logo: royal blue (#0B5FB0) dan gold orange (#F59300),
 * di atas permukaan gelap (#06080C) yang sama dengan latar logo.
 */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },

            colors: {
                /* Biru — warna utama, diambil dari huruf "A" pada logo */
                brand: {
                    50: '#EEF6FF',
                    100: '#D8EAFF',
                    200: '#B4D6FF',
                    300: '#82BBFF',
                    400: '#4A98F5',
                    500: '#1E7AD6',
                    600: '#0B5FB0',
                    700: '#084B8E',
                    800: '#0A3D72',
                    900: '#0C335E',
                    950: '#06203E',
                },

                /* Oranye — warna aksen, diambil dari sapuan panah pada logo */
                accent: {
                    50: '#FFF8EB',
                    100: '#FFEDC7',
                    200: '#FFD98A',
                    300: '#FFC14D',
                    400: '#FBAA24',
                    500: '#F59300',
                    600: '#D97400',
                    700: '#B45509',
                    800: '#92420E',
                    900: '#78370F',
                    950: '#451B03',
                },

                /* Netral gelap — permukaan premium (navbar, footer, panel admin) */
                ink: {
                    50: '#F5F7FA',
                    100: '#E9EDF3',
                    200: '#CED6E2',
                    300: '#A6B3C6',
                    400: '#6F8099',
                    500: '#4C5B70',
                    600: '#374357',
                    700: '#2A3446',
                    800: '#1B2231',
                    900: '#111725',
                    950: '#06080C',
                },
            },

            boxShadow: {
                brand: '0 18px 40px -18px rgba(11, 95, 176, 0.55)',
                accent: '0 18px 40px -18px rgba(245, 147, 0, 0.55)',
                elevate: '0 24px 60px -28px rgba(6, 32, 62, 0.35)',
            },

            backgroundImage: {
                'brand-gradient': 'linear-gradient(120deg, #06203E 0%, #0B5FB0 48%, #F59300 100%)',
                'brand-sheen': 'linear-gradient(120deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.35) 50%, rgba(255,255,255,0) 100%)',
            },

            keyframes: {
                'sheen-sweep': {
                    '0%': { transform: 'translateX(-120%)' },
                    '100%': { transform: 'translateX(220%)' },
                },
                'float-soft': {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-8px)' },
                },
            },

            animation: {
                'sheen-sweep': 'sheen-sweep 2.6s ease-in-out infinite',
                'float-soft': 'float-soft 6s ease-in-out infinite',
            },
        },
    },

    plugins: [require('@tailwindcss/forms')],
};
