import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
        host: '192.168.100.132',
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/main.css',
                'resources/css/modal.css',
                'resources/css/homecity.css',
                'resources/css/homemrkt.css',
                'resources/css/reused.css',
                'resources/css/LargeCards.css',


                'resources/js/reused.js',
                'resources/js/cart.js',
                'resources/js/modal.js',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
    ],
});
