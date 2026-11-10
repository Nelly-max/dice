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
                'resources/css/tokens.css',
                'resources/css/homemarket-ui.css',
                'resources/css/flow.css',
                'resources/css/product-cards.css',
                'resources/css/smart-home.css',
                'resources/js/smart-home.js',
                'resources/css/responsive.css',
                'resources/css/auth.css',
                'resources/css/components.css',
                'resources/css/reused.css',
                'resources/css/LargeCards.css',
                'resources/css/hub.css',
                'resources/css/hub-ui.css',
                'resources/css/calendar.css',
                'resources/css/services-ui.css',   // Cooking Gas + Home City
                'resources/css/quick-switch.css',  // floating subdivision switcher
                'resources/css/shop-pages.css',    // category / offer pages of the product shops
                'resources/css/ui-controls.css',   // custom select + date picker (every page)


                'resources/js/softnav.js',
                'resources/js/hmsearch.js',
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
                'resources/js/rider-request.js',     // delivery requests on non-Hub pages
                'resources/js/hub.js',
                'resources/js/hubnav.js',
                'resources/js/calendar.js',
                'resources/js/deliveryMap.js',     // rider delivery map (layouts/delivery)
                'resources/js/toast.js',
                'resources/js/services-ui.js',     // Cooking Gas + Home City
                'resources/js/quick-switch.js',    // floating subdivision switcher
                'resources/js/shop-pages.js',      // countdowns, Load more, filters sheet
                'resources/js/ui-controls.js',     // custom select + date picker (every page)
            ],
            refresh: true,
        }),
    ],
});
