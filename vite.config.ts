import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import inertia from '@inertiajs/vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { visualizer } from 'rollup-plugin-visualizer';
import { defineConfig } from 'vite';

export default defineConfig(({ isSsrBuild }) => ({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            refresh: true,
        }),
        inertia({
            ssr: {
                cluster: true,
                host: '127.0.0.1',
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        // Bundle analyzer - generates stats.html after build
        visualizer({
            filename: 'stats.html',
            gzipSize: true,
            brotliSize: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                // manualChunks only applies to client builds; SSR externalizes these modules
                ...(!isSsrBuild && {
                    manualChunks: {
                        // Core Vue ecosystem
                        'vendor-vue': ['vue', '@inertiajs/vue3'],
                        // UI component libraries
                        'vendor-ui': [
                            'reka-ui',
                            'class-variance-authority',
                            'clsx',
                            'tailwind-merge',
                        ],
                        // Rich text editor (heavy)
                        'vendor-editor': [
                            '@tiptap/core',
                            '@tiptap/starter-kit',
                            '@tiptap/vue-3',
                            '@tiptap/extension-link',
                            '@tiptap/extension-image',
                            '@tiptap/extension-placeholder',
                            '@tiptap/extension-text-align',
                            '@tiptap/extension-underline',
                            '@tiptap/extension-code-block-lowlight',
                        ],
                        // Icons only - let vueuse chunk automatically to avoid initialization order issues
                        'vendor-icons': ['lucide-vue-next'],
                    },
                }),
            },
        },
    },
}));
