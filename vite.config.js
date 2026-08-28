import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    build: {
        // TinyMCE is one big chunk by nature. It is already split out and only
        // fetched on pages that hold an editor, so the warning is noise.
        chunkSizeWarningLimit: 1500,
    },
    plugins: [
        laravel({
            input: [
                'resources/js/admin.js',
                'resources/js/storefront.js',
            ],
            refresh: true,
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
});
