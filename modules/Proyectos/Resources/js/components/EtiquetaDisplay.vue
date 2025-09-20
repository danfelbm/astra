<template>
    <div class="flex flex-wrap gap-1.5">
        <!-- Etiquetas visibles -->
        <template v-for="(etiqueta, index) in etiquetasVisibles" :key="etiqueta.id">
            <Badge
                :variant="getBadgeVariant(etiqueta)"
                :class="[
                    sizeClasses,
                    interactive ? 'cursor-pointer hover:opacity-80 transition-opacity' : '',
                    'inline-flex items-center gap-1'
                ]"
                @click="interactive && handleClick(etiqueta)"
            >
                <!-- Icono de la categoría si existe -->
                <component
                    v-if="etiqueta.categoria?.icono && showCategoria"
                    :is="getIcon(etiqueta.categoria.icono)"
                    :class="iconSizeClasses"
                />

                <!-- Nombre de la etiqueta -->
                <span>{{ etiqueta.nombre }}</span>

                <!-- Mostrar categoría si está habilitado -->
                <span v-if="showCategoria && etiqueta.categoria" class="opacity-60 text-xs">
                    ({{ etiqueta.categoria.nombre }})
                </span>
            </Badge>
        </template>

        <!-- Indicador de más etiquetas -->
        <Badge
            v-if="tieneEtiquetasOcultas"
            variant="outline"
            :class="[sizeClasses, 'text-muted-foreground']"
        >
            +{{ etiquetasOcultas }} más
        </Badge>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { router } from '@inertiajs/vue3';
import type { Etiqueta } from '../types/etiquetas';
import {
    Tag, Hash, Bookmark, Flag, Star, Heart,
    Zap, Target, Award, TrendingUp, Folder,
    Package, Box, Layers, Grid
} from 'lucide-vue-next';

// Props del componente
interface Props {
    etiquetas: Etiqueta[];
    showCategoria?: boolean;
    interactive?: boolean;
    maxVisible?: number;
    size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    showCategoria: false,
    interactive: false,
    maxVisible: undefined,
    size: 'md'
});

// Computed para etiquetas visibles y ocultas
const etiquetasVisibles = computed(() => {
    if (!props.maxVisible || props.etiquetas.length <= props.maxVisible) {
        return props.etiquetas;
    }
    return props.etiquetas.slice(0, props.maxVisible);
});

const tieneEtiquetasOcultas = computed(() => {
    return props.maxVisible && props.etiquetas.length > props.maxVisible;
});

const etiquetasOcultas = computed(() => {
    if (!props.maxVisible) return 0;
    return props.etiquetas.length - props.maxVisible;
});

// Clases según el tamaño
const sizeClasses = computed(() => {
    const sizes = {
        sm: 'text-xs px-2 py-0.5',
        md: 'text-sm px-2.5 py-0.5',
        lg: 'text-base px-3 py-1'
    };
    return sizes[props.size];
});

const iconSizeClasses = computed(() => {
    const sizes = {
        sm: 'h-3 w-3',
        md: 'h-3.5 w-3.5',
        lg: 'h-4 w-4'
    };
    return sizes[props.size];
});

// Función para obtener el variant del Badge según el color
function getBadgeVariant(etiqueta: Etiqueta): string {
    const color = etiqueta.color || etiqueta.categoria?.color || 'default';

    // Mapeo de colores a variants de Badge
    const colorMap: Record<string, string> = {
        gray: 'secondary',
        red: 'destructive',
        green: 'success',
        blue: 'default',
        yellow: 'warning',
        // Para colores no mapeados directamente, usar outline
        default: 'outline'
    };

    return colorMap[color] || 'outline';
}

// Función para obtener el componente de icono
function getIcon(iconName: string) {
    const icons: Record<string, any> = {
        Tag, Hash, Bookmark, Flag, Star, Heart,
        Zap, Target, Award, TrendingUp, Folder,
        Package, Box, Layers, Grid
    };

    return icons[iconName] || Tag;
}

// Función para manejar clicks en etiquetas
function handleClick(etiqueta: Etiqueta) {
    if (!props.interactive) return;

    // Navegar a la lista de proyectos filtrada por esta etiqueta
    router.get('/admin/proyectos', {
        etiquetas: [etiqueta.id]
    }, {
        preserveState: true,
        preserveScroll: true
    });
}
</script>

<style scoped>
/* Animación suave para las etiquetas */
.badge-enter-active,
.badge-leave-active {
    transition: all 0.3s ease;
}

.badge-enter-from {
    opacity: 0;
    transform: scale(0.8);
}

.badge-leave-to {
    opacity: 0;
    transform: scale(0.8);
}
</style>