<script setup lang="ts">
/**
 * Componente agnóstico para mostrar transiciones de estado en comentarios.
 * Recibe toda la información visual (labels, colores) desde el módulo origen.
 * El módulo Comentarios NO conoce los estados específicos de cada módulo.
 */
import { computed } from 'vue';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { ArrowRight } from 'lucide-vue-next';
import type { ComentarioContexto } from '../types/comentarios';

interface Props {
    contexto: ComentarioContexto;
    showTransition?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showTransition: true,
});

// Mapeo de colores a clases de Tailwind
const getColorClasses = (color: string | null | undefined) => {
    const colorMap: Record<string, string> = {
        green: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        red: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        gray: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
        orange: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        purple: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    };
    return colorMap[color || 'gray'] || colorMap.gray;
};

// Clases para el badge anterior
const anteriorClasses = computed(() => getColorClasses(props.contexto.color_anterior));

// Clases para el badge nuevo
const nuevoClasses = computed(() => getColorClasses(props.contexto.color_nuevo));

// Verificar si hay información suficiente para mostrar
const tieneInfoSuficiente = computed(() => {
    return props.contexto.label_nuevo || props.contexto.estado_nuevo;
});
</script>

<template>
    <div v-if="tieneInfoSuficiente" class="inline-flex items-center gap-1.5 flex-wrap">
        <!-- Badge estado anterior (opcional, solo si showTransition es true) -->
        <template v-if="showTransition && (contexto.label_anterior || contexto.estado_anterior)">
            <Badge
                variant="outline"
                :class="['text-xs h-5 font-normal', anteriorClasses]"
            >
                {{ contexto.label_anterior || contexto.estado_anterior }}
            </Badge>
            <ArrowRight class="h-3 w-3 text-muted-foreground flex-shrink-0" />
        </template>

        <!-- Badge estado nuevo -->
        <Badge
            variant="outline"
            :class="['text-xs h-5 font-medium', nuevoClasses]"
        >
            {{ contexto.label_nuevo || contexto.estado_nuevo }}
        </Badge>
    </div>
</template>
