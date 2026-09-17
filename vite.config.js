import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
        host: '192.168.100.95',
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/main.css',
                'resources/css/modal.css',
                'resources/css/toast.css',
                'resources/css/homecity.css',
                'resources/css/homemrkt.css',
                'resources/css/reused.css',
                'resources/css/LargeCards.css',
                'resources/css/hub.css',
                'resources/css/calendar.css',


                'resources/js/reused.js',
                'resources/js/modal.js',
                'resources/js/cart.js',
                'resources/js/checkout.js',
                'resources/js/orderPayment.js',
                'resources/js/signup.js',
                'resources/js/select.js',
                'resources/js/app.js',
                'resources/js/map.js',
                'resources/js/riderLocation.js',
                'resources/js/hub.js',
                'resources/js/calendar.js',
            ],
            refresh: true,
        }),
    ],
});
