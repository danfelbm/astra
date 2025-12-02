<script setup lang="ts">
/**
 * HitosEntregablesList - Vista de entregables en formato lista agrupada por estado
 */
import { ref, toRef } from 'vue';
import { AlertCircle, Clock, CheckCircle, XCircle, FileText } from 'lucide-vue-next';
import type { Entregable, EstadoEntregable } from '@modules/Proyectos/Resources/js/types/hitos';
import type { UploadedFile } from '@modules/Comentarios/Resources/js/types/comentarios';
import { useEntregablesView, ESTADO_CONFIG } from '@modules/Proyectos/Resources/js/composables/useEntregablesView';
import HitosEntregableCard from './HitosEntregableCard.vue';

// Props
interface Props {
    entregables: Entregable[];
    canEdit?: boolean;
    canDelete?: boolean;
    canComplete?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canEdit: false,
    canDelete: false,
    canComplete: false,
});

// Emits
const emit = defineEmits<{
    'view': [entregable: Entregable];
    'edit': [entregable: Entregable];
    'delete': [entregable: Entregable];
    'complete': [entregable: Entregable];
    'change-status': [entregable: Entregable, nuevoEstado: EstadoEntregable];
    'show-comentarios': [entregable: Entregable];
    'show-actividad': [entregable: Entregable];
}>();

// Composable
const entregablesRef = toRef(props, 'entregables');
const { entregablesAgrupados } = useEntregablesView(entregablesRef);

// Estado local de expansión
const expandedIds = ref<Set<number>>(new Set());

const toggleExpand = (id: number) => {
    if (expandedIds.value.has(id)) {
        expandedIds.value.delete(id);
    } else {
        expandedIds.value.add(id);
    }
};

const isExpanded = (id: number) => expandedIds.value.has(id);

// Handlers
const handleComplete = (entregable: Entregable) => {
    emit('complete', entregable);
};

const handleChangeStatus = (entregable: Entregable, nuevoEstado: EstadoEntregable) => {
    emit('change-status', entregable, nuevoEstado);
};

// Configuración de secciones
const secciones = [
    { key: 'pendientes', icon: AlertCircle, iconClass: 'text-yellow-600', label: 'Pendientes' },
    { key: 'en_progreso', icon: Clock, iconClass: 'text-blue-600', label: 'En Progreso' },
    { key: 'completados', icon: CheckCircle, iconClass: 'text-green-600', label: 'Completados' },
    { key: 'cancelados', icon: XCircle, iconClass: 'text-red-600', label: 'Cancelados' },
] as const;
</script>

<template>
    <div class="space-y-6">
        <!-- Secciones por estado -->
        <template v-for="seccion in secciones" :key="seccion.key">
            <div v-if="entregablesAgrupados[seccion.key].length > 0">
                <h3 class="font-semibold mb-3 flex items-center gap-2">
                    <component :is="seccion.icon" class="h-5 w-5" :class="seccion.iconClass" />
                    {{ seccion.label }} ({{ entregablesAgrupados[seccion.key].length }})
                </h3>
                <div class="space-y-2">
                    <HitosEntregableCard
                        v-for="entregable in entregablesAgrupados[seccion.key]"
                        :key="entregable.id"
                        :entregable="entregable"
                        :can-edit="canEdit"
                        :can-delete="canDelete"
                        :can-complete="canComplete"
                        :expanded="isExpanded(entregable.id)"
                        variant="default"
                        @toggle-expand="toggleExpand(entregable.id)"
                        @view="emit('view', entregable)"
                        @edit="emit('edit', entregable)"
                        @delete="emit('delete', entregable)"
                        @complete="handleComplete(entregable)"
                        @change-status="(estado) => handleChangeStatus(entregable, estado)"
                        @show-comentarios="emit('show-comentarios', entregable)"
                        @show-actividad="emit('show-actividad', entregable)"
                    />
                </div>
            </div>
        </template>

        <!-- Estado vacío -->
        <div v-if="entregables.length === 0" class="text-center py-8">
            <FileText class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
            <p class="text-muted-foreground">No hay entregables asignados</p>
        </div>
    </div>
</template>
