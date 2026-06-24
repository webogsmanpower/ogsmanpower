import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/frontend/js/app.js',
                'resources/frontend/js/public.js',
                'resources/frontend/sass/app.scss',
                'resources/frontend/app.css',
                'resources/frontend/public.css',
                'resources/backend/app.css'
            ],
            refresh: true,
        }),
    ],
    build: {
        outDir: '../public_html/build',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: [
                'resources/frontend/js/app.js',
                'resources/frontend/js/public.js'
            ]
        }
    }
});