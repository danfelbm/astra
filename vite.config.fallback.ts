import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { defineConfig } from 'vite';

// Configuración de fallback sin TailwindCSS Vite plugin para evitar lightningcss
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
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
        // Sin tailwindcss() plugin para evitar dependencias nativas problemáticas
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    css: {
        postcss: {
            plugins: [
                require('@tailwindcss/postcss'),
                require('autoprefixer'),
            ],
        },
    },
    build: {
        cssCodeSplit: false,
        rollupOptions: {
            output: {
                manualChunks: undefined,
            }
        }
    }
});