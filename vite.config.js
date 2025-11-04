import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            // Only watch what you actually edit
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
        }),

        // TailwindCSS plugin
        tailwindcss(),
    ],

    // Speed up dependency processing
    optimizeDeps: {
        include: ['axios', 'lodash', 'chart.js'],
    },

    // Faster dev server settings
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost',
        },
        // Preload JS so dashboard loads quicker
        warmup: {
            clientFiles: ['./resources/js/app.js'],
        },
    },

    // Optional: better build output organization
    build: {
        manifest: true,
        outDir: 'public/build',
        emptyOutDir: true,
    },
});
