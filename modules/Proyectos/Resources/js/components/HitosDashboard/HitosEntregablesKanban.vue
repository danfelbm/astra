<script setup lang="ts">
/**
 * HitosEntregablesKanban - Vista kanban con drag & drop usando Sortable.js
 */
import { ref, toRef, onMounted, onUnmounted, nextTick, watch } from 'vue';
import Sortable from 'sortablejs';
import type { Entregable, EstadoEntregable } from '@modules/Proyectos/Resources/js/types/hitos';
import { useEntregablesView, ESTADOS_ORDENADOS } from '@modules/Proyectos/Resources/js/composables/useEntregablesView';
import HitosKanbanColumn from './HitosKanbanColumn.vue';

// Props
interface Props {
    entregables: Entregable[];
    canEdit?: boolean;
    canDelete?: boolean;
    canComplete?: boolean;
    confirmOnDrag?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canEdit: false,
    canDelete: false,
    canComplete: false,
    confirmOnDrag: true,
});

// Emits
const emit = defineEmits<{
    'edit': [entregable: Entregable];
    'delete': [entregable: Entregable];
    'complete': [entregable: Entregable];
    'change-status': [entregable: Entregable, nuevoEstado: EstadoEntregable];
    'drag-change-status': [entregable: Entregable, nuevoEstado: EstadoEntregable];
}>();

// Composable
const entregablesRef = toRef(props, 'entregables');
const { entregablesAgrupados } = useEntregablesView(entregablesRef);

// Estado para drag over
const dragOverEstado = ref<EstadoEntregable | null>(null);

// Instancias de Sortable
const sortableInstances = ref<Sortable[]>([]);

// Mapeo de estado a key del objeto agrupados
const estadoToKey: Record<EstadoEntregable, keyof typeof entregablesAgrupados.value> = {
    pendiente: 'pendientes',
    en_progreso: 'en_progreso',
    completado: 'completados',
    cancelado: 'cancelados',
};

// Obtener entregables por estado
const getEntregablesByEstado = (estado: EstadoEntregable): Entregable[] => {
    return entregablesAgrupados.value[estadoToKey[estado]];
};

// Configurar Sortable.js
const setupSortable = () => {
    // Limpiar instancias previas
    destroySortable();

    // Esperar al siguiente tick para asegurar que el DOM está listo
    nextTick(() => {
        const columns = document.querySelectorAll('.kanban-column-cards');

        columns.forEach((column) => {
            const sortable = Sortable.create(column as HTMLElement, {
                group: 'entregables-kanban',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                delay: 100, // Para mejor UX en touch
                delayOnTouchOnly: true,
                touchStartThreshold: 3,

                // Callbacks
                onStart: () => {
                    // Nada especial al iniciar
                },

                onEnd: (evt) => {
                    const entregableId = parseInt(evt.item.dataset.id || '0');
                    const nuevoEstado = evt.to.dataset.estado as EstadoEntregable;
                    const estadoAnterior = evt.from.dataset.estado as EstadoEntregable;

                    // Resetear estado visual
                    dragOverEstado.value = null;

                    // Si no cambió de columna, no hacer nada
                    if (nuevoEstado === estadoAnterior) {
                        return;
                    }

                    // Buscar el entregable
                    const entregable = props.entregables.find(e => e.id === entregableId);
                    if (!entregable) return;

                    // Emitir evento de cambio por drag
                    emit('drag-change-status', entregable, nuevoEstado);
                },

                onChange: (evt) => {
                    // Actualizar estado de drag over
                    const targetEstado = (evt.to as HTMLElement).dataset.estado as EstadoEntregable;
                    if (targetEstado) {
                        dragOverEstado.value = targetEstado;
                    }
                },
            });

            sortableInstances.value.push(sortable);
        });
    });
};

// Destruir instancias de Sortable
const destroySortable = () => {
    sortableInstances.value.forEach(instance => {
        instance.destroy();
    });
    sortableInstances.value = [];
};

// Lifecycle
onMounted(() => {
    setupSortable();
});

onUnmounted(() => {
    destroySortable();
});

// Re-inicializar cuando cambian los entregables
watch(() => props.entregables, () => {
    nextTick(() => {
        setupSortable();
    });
}, { deep: true });
</script>

<template>
    <div class="kanban-container flex gap-4 overflow-x-auto snap-x snap-mandatory pb-4 -mx-2 px-2">
        <div
            v-for="estado in ESTADOS_ORDENADOS"
            :key="estado"
            class="kanban-column flex-shrink-0 w-72 snap-center md:w-auto md:flex-1 md:min-w-[200px]"
        >
            <HitosKanbanColumn
                :estado="estado"
                :entregables="getEntregablesByEstado(estado)"
                :can-edit="canEdit"
                :can-delete="canDelete"
                :can-complete="canComplete"
                :is-drag-over="dragOverEstado === estado"
                @edit="emit('edit', $event)"
                @delete="emit('delete', $event)"
                @complete="emit('complete', $event)"
                @change-status="(entregable, nuevoEstado) => emit('change-status', entregable, nuevoEstado)"
            />
        </div>
    </div>
</template>

<style>
/* Estilos para scrollbar en kanban */
.kanban-container {
    scrollbar-width: thin;
    scrollbar-color: hsl(var(--muted-foreground) / 0.3) transparent;
}

.kanban-container::-webkit-scrollbar {
    height: 8px;
}

.kanban-container::-webkit-scrollbar-track {
    background: transparent;
}

.kanban-container::-webkit-scrollbar-thumb {
    background-color: hsl(var(--muted-foreground) / 0.3);
    border-radius: 4px;
}

.kanban-container::-webkit-scrollbar-thumb:hover {
    background-color: hsl(var(--muted-foreground) / 0.5);
}

/* Estilos para drag & drop de Sortable.js */
.sortable-ghost {
    opacity: 0.5 !important;
}

.sortable-chosen {
    outline: 2px solid hsl(var(--primary)) !important;
    outline-offset: 2px;
    transform: scale(1.02);
}

.sortable-drag {
    transform: rotate(2deg) !important;
    box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1) !important;
}
</style>
