<template>
    <div class="flex flex-wrap gap-1.5">
        <!-- Etiquetas visibles -->
        <template v-for="(etiqueta, index) in etiquetasVisibles" :key="etiqueta.id">
            <HoverCard :open-delay="200" :close-delay="100">
                <HoverCardTrigger asChild>
                    <Badge
                        :variant="getBadgeVariant(etiqueta)"
                        :class="[
                            sizeClasses,
                            interactive ? 'cursor-pointer hover:opacity-80 transition-opacity' : '',
                            'inline-flex items-center gap-1'
                        ]"
                        @click="interactive && handleClick(etiqueta)"
                    >
                        <!-- Indicador de jerarquía si tiene padre -->
                        <ChevronRight
                            v-if="etiqueta.parent_id"
                            :class="[iconSizeClasses, 'opacity-50']"
                        />

                        <!-- Icono de la categoría si existe -->
                        <component
                            v-if="etiqueta.categoria?.icono && showCategoria"
                            :is="getIcon(etiqueta.categoria.icono)"
                            :class="iconSizeClasses"
                        />

                        <!-- Nombre de la etiqueta -->
                        <span>{{ etiqueta.nombre }}</span>

                        <!-- Indicador si tiene hijos -->
                        <span v-if="etiqueta.tiene_hijos" class="ml-1">
                            <Layers :class="[iconSizeClasses, 'opacity-50']" />
                        </span>

                        <!-- Mostrar categoría si está habilitado -->
                        <span v-if="showCategoria && etiqueta.categoria" class="opacity-60 text-xs">
                            ({{ etiqueta.categoria.nombre }})
                        </span>
                    </Badge>
                </HoverCardTrigger>

                <!-- Contenido del HoverCard con breadcrumb -->
                <HoverCardContent
                    v-if="etiqueta.parent || etiqueta.children || etiqueta.descripcion"
                    class="w-80 p-3"
                    side="top"
                >
                    <!-- Breadcrumb de jerarquía -->
                    <div v-if="etiqueta.ruta_completa || etiqueta.parent" class="mb-2">
                        <div class="text-xs text-muted-foreground mb-1">Jerarquía:</div>
                        <div class="flex items-center gap-1 text-sm">
                            <!-- Ruta completa si está disponible -->
                            <template v-if="etiqueta.ruta_completa">
                                <span class="font-medium">{{ etiqueta.ruta_completa }}</span>
                            </template>
                            <!-- O construir desde parent -->
                            <template v-else-if="etiqueta.parent">
                                <span class="text-muted-foreground">{{ etiqueta.parent.nombre }}</span>
                                <ChevronRight class="h-3 w-3" />
                                <span class="font-medium">{{ etiqueta.nombre }}</span>
                            </template>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div v-if="etiqueta.descripcion" class="text-sm text-muted-foreground">
                        {{ etiqueta.descripcion }}
                    </div>

                    <!-- Información adicional -->
                    <div class="flex gap-4 mt-2 text-xs text-muted-foreground">
                        <div v-if="etiqueta.categoria">
                            <span class="font-medium">Categoría:</span> {{ etiqueta.categoria.nombre }}
                        </div>
                        <div v-if="etiqueta.usos_count > 0">
                            <span class="font-medium">Usos:</span> {{ etiqueta.usos_count }}
                        </div>
                        <div v-if="etiqueta.children && etiqueta.children.length > 0">
                            <span class="font-medium">Hijos:</span> {{ etiqueta.children.length }}
                        </div>
                    </div>
                </HoverCardContent>
            </HoverCard>
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
import {
    Popover as HoverCard,
    PopoverContent as HoverCardContent,
    PopoverTrigger as HoverCardTrigger
} from '@modules/Core/Resources/js/components/ui/popover';
import { router } from '@inertiajs/vue3';
import type { Etiqueta } from '../types/etiquetas';
import {
    Tag, Hash, Bookmark, Flag, Star, Heart,
    Zap, Target, Award, TrendingUp, Folder,
    Package, Box, Layers, Grid, ChevronRight
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