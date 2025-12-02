<script setup lang="ts">
/**
 * HitosKanbanColumn - Columna individual del tablero kanban
 */
import { computed } from 'vue';
import { ScrollArea } from '@modules/Core/Resources/js/components/ui/scroll-area';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { AlertCircle, Clock, CheckCircle, XCircle } from 'lucide-vue-next';
import type { Entregable, EstadoEntregable } from '@modules/Proyectos/Resources/js/types/hitos';
import { ESTADO_CONFIG } from '@modules/Proyectos/Resources/js/composables/useEntregablesView';
import HitosEntregableCard from './HitosEntregableCard.vue';

// Props
interface Props {
    estado: EstadoEntregable;
    entregables: Entregable[];
    canEdit?: boolean;
    canDelete?: boolean;
    canComplete?: boolean;
    canDrag?: boolean; // Controla si se puede arrastrar cards (solo gestores)
    isDragOver?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canEdit: false,
    canDelete: false,
    canComplete: false,
    canDrag: false,
    isDragOver: false,
});

// Emits
const emit = defineEmits<{
    'view': [entregable: Entregable];
    'edit': [entregable: Entregable];
    'delete': [entregable: Entregable];
    'complete': [entregable: Entregable];
    'change-status': [entregable: Entregable, nuevoEstado: EstadoEntregable];
}>();

// Configuración del estado
const config = computed(() => ESTADO_CONFIG[props.estado]);

// Icono según estado
const iconComponent = computed(() => {
    switch (props.estado) {
        case 'completado': return CheckCircle;
        case 'en_progreso': return Clock;
        case 'cancelado': return XCircle;
        default: return AlertCircle;
    }
});

// Clases dinámicas
const columnClasses = computed(() => {
    const base = 'flex flex-col rounded-lg border bg-muted/30 min-h-[300px] transition-colors duration-150';
    const dragOverClasses = props.isDragOver
        ? 'border-primary border-dashed bg-primary/5'
        : 'border-border';
    return `${base} ${dragOverClasses}`;
});
</script>

<template>
    <div
        :class="columnClasses"
        :data-estado="estado"
    >
        <!-- Header de columna -->
        <div class="p-3 border-b bg-background/50 rounded-t-lg">
            <div class="flex items-center gap-2">
                <component
                    :is="iconComponent"
                    class="h-4 w-4 flex-shrink-0"
                    :class="config.color"
                />
                <span class="font-medium text-sm">{{ config.label }}</span>
                <Badge variant="secondary" class="ml-auto text-xs">
                    {{ entregables.length }}
                </Badge>
            </div>
        </div>

        <!-- Contenido de la columna (cards) -->
        <ScrollArea class="flex-1 p-2">
            <div
                class="kanban-column-cards space-y-2 min-h-[200px]"
                :data-estado="estado"
            >
                <HitosEntregableCard
                    v-for="entregable in entregables"
                    :key="entregable.id"
                    :entregable="entregable"
                    :can-edit="canEdit"
                    :can-delete="canDelete"
                    :can-complete="canComplete"
                    :draggable="canDrag"
                    variant="kanban"
                    @view="emit('view', entregable)"
                    @edit="emit('edit', entregable)"
                    @delete="emit('delete', entregable)"
                    @complete="emit('complete', entregable)"
                    @change-status="(nuevoEstado) => emit('change-status', entregable, nuevoEstado)"
                />

                <!-- Placeholder cuando está vacío -->
                <div
                    v-if="entregables.length === 0"
                    class="h-20 border-2 border-dashed border-muted-foreground/20 rounded-lg
                           flex items-center justify-center text-sm text-muted-foreground"
                >
                    Sin entregables
                </div>
            </div>
        </ScrollArea>
    </div>
</template>
