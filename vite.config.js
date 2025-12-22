import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
            // Avoid creating `public/hot` by placing the hot file in storage
            hotFile: 'storage/vite.hot',
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        middlewareMode: false,
        cors: {
            origin: '*',
            credentials: true,
            methods: ['GET', 'HEAD', 'PUT', 'PATCH', 'POST', 'DELETE'],
        },
        hmr: {
            host: process.env.VITE_HMR_HOST || 'localhost',
            port: process.env.VITE_HMR_PORT || 5173,
            protocol: process.env.VITE_HMR_PROTOCOL || 'ws',
        },
    },
});
