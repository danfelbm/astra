import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { defineConfig } from 'vite';
import { glob } from 'glob';

// Archivo principal de Core (ya no está en resources/js)
const coreApp = 'modules/Core/Resources/js/app.ts';

// Encontrar otros assets de módulos si los hay
const moduleAssets = glob.sync('modules/*/Resources/js/app.{js,ts}').filter(file => !file.includes('Core/'));

export default defineConfig({
    plugins: [
        laravel({
            input: [
                coreApp, // App principal desde módulo Core
                ...moduleAssets // Incluir otros assets de módulos
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
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            '@modules': path.resolve(__dirname, './modules'), // Nuevo alias para módulos
        },
    },
    optimizeDeps: {
        exclude: ['lightningcss']
    },
    build: {
        rollupOptions: {
            external: (id) => {
                // Excluir dependencias opcionales problemáticas durante el build
                return id.includes('lightningcss') && id.includes('.node');
            }
        }
    },
    // 🚀 clave para multi-dominio
    base: '/build/',
});
