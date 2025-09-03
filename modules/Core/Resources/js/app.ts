import '../../../../resources/css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../../../vendor/tightenco/ziggy';
import { initializeTheme } from './composables/useAppearance';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Pre-cargar todos los componentes de módulos (desde modules/Core/Resources/js/ navegar a otros módulos)
const modulePages = import.meta.glob<DefineComponent>('../../../*/Resources/js/Pages/**/*.vue');
const regularPages = import.meta.glob<DefineComponent>('./pages/**/*.vue');

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        // Detectar si es un módulo: Modules/NombreModulo/Componente
        const modulePattern = /^Modules\/([^\/]+)\/(.+)$/;
        const match = name.match(modulePattern);
        
        if (match) {
            const [, module, component] = match;
            // Construir la ruta del módulo desde modules/Core/Resources/js/
            const modulePath = `../../../${module}/Resources/js/Pages/${component}.vue`;
            
            // Buscar en los módulos pre-cargados
            if (modulePath in modulePages) {
                return modulePages[modulePath]();
            }
            
            // Si no se encuentra, intentar con el import directo como fallback
            console.warn(`Module component not found: ${modulePath}`);
        }
        
        // Si no es módulo, buscar en resources/js/pages normal
        return resolvePageComponent(`./pages/${name}.vue`, regularPages);
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
