<script setup lang="ts">
/**
 * HitosEntregablesPanel - Componente orquestador para visualización de entregables
 * Maneja los diferentes modos de vista (lista, tabs, kanban) y los modales compartidos
 */
import { ref, toRef } from 'vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@modules/Core/Resources/js/components/ui/alert-dialog';
import type { Entregable, EstadoEntregable } from '@modules/Proyectos/Resources/js/types/hitos';
import type { UploadedFile } from '@modules/Comentarios/Resources/js/types/comentarios';
import { useEntregablesView } from '@modules/Proyectos/Resources/js/composables/useEntregablesView';
import StatusChangeModal from '../StatusChangeModal.vue';
import HitosViewModeToggle from './HitosViewModeToggle.vue';
import HitosEntregablesList from './HitosEntregablesList.vue';
import HitosEntregablesTabs from './HitosEntregablesTabs.vue';
import HitosEntregablesKanban from './HitosEntregablesKanban.vue';

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
    'edit': [entregable: Entregable];
    'delete': [entregable: Entregable];
    'complete': [entregable: Entregable, observaciones: string, archivos: UploadedFile[]];
    'update-status': [entregable: Entregable, estado: string, observaciones: string, archivos: UploadedFile[]];
}>();

// Composable
const entregablesRef = toRef(props, 'entregables');
const { viewMode, confirmOnDrag } = useEntregablesView(entregablesRef);

// Estado del modal de cambio de estado
const statusChangeModalOpen = ref(false);
const entregableToChange = ref<Entregable | null>(null);
const nuevoEstadoPendiente = ref<EstadoEntregable>('pendiente');
const statusChangeLoading = ref(false);

// Estado del diálogo de confirmación de eliminación
const deleteDialogOpen = ref(false);
const entregableToDelete = ref<Entregable | null>(null);

// Handlers de navegación
const handleEdit = (entregable: Entregable) => {
    emit('edit', entregable);
};

// Handler de eliminación
const handleDelete = (entregable: Entregable) => {
    entregableToDelete.value = entregable;
    deleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (entregableToDelete.value) {
        emit('delete', entregableToDelete.value);
    }
    deleteDialogOpen.value = false;
    entregableToDelete.value = null;
};

// Handler de completar (abre modal)
const handleComplete = (entregable: Entregable) => {
    entregableToChange.value = entregable;
    nuevoEstadoPendiente.value = 'completado';
    statusChangeModalOpen.value = true;
};

// Handler de cambio de estado (desde botones, abre modal)
const handleChangeStatus = (entregable: Entregable, nuevoEstado: EstadoEntregable) => {
    entregableToChange.value = entregable;
    nuevoEstadoPendiente.value = nuevoEstado;
    statusChangeModalOpen.value = true;
};

// Handler de cambio de estado por drag (desde kanban)
const handleDragChangeStatus = (entregable: Entregable, nuevoEstado: EstadoEntregable) => {
    if (confirmOnDrag.value) {
        // Abrir modal para confirmar
        entregableToChange.value = entregable;
        nuevoEstadoPendiente.value = nuevoEstado;
        statusChangeModalOpen.value = true;
    } else {
        // Cambiar directamente sin modal
        emit('update-status', entregable, nuevoEstado, '', []);
    }
};

// Confirmar cambio de estado desde modal
const confirmStatusChange = (observaciones: string, archivos: UploadedFile[]) => {
    if (!entregableToChange.value) return;

    if (nuevoEstadoPendiente.value === 'completado') {
        emit('complete', entregableToChange.value, observaciones, archivos);
    } else {
        emit('update-status', entregableToChange.value, nuevoEstadoPendiente.value, observaciones, archivos);
    }

    // Cerrar modal y limpiar estado
    statusChangeModalOpen.value = false;
    entregableToChange.value = null;
};
</script>

<template>
    <div class="space-y-4">
        <!-- Toggle de modo de vista -->
        <HitosViewModeToggle
            v-model="viewMode"
            :confirm-on-drag="confirmOnDrag"
            @update:confirm-on-drag="confirmOnDrag = $event"
        />

        <!-- Vista según modo seleccionado -->
        <HitosEntregablesList
            v-if="viewMode === 'list'"
            :entregables="entregables"
            :can-edit="canEdit"
            :can-delete="canDelete"
            :can-complete="canComplete"
            @edit="handleEdit"
            @delete="handleDelete"
            @complete="handleComplete"
            @change-status="handleChangeStatus"
        />

        <HitosEntregablesTabs
            v-else-if="viewMode === 'tabs'"
            :entregables="entregables"
            :can-edit="canEdit"
            :can-delete="canDelete"
            :can-complete="canComplete"
            @edit="handleEdit"
            @delete="handleDelete"
            @complete="handleComplete"
            @change-status="handleChangeStatus"
        />

        <HitosEntregablesKanban
            v-else-if="viewMode === 'kanban'"
            :entregables="entregables"
            :can-edit="canEdit"
            :can-delete="canDelete"
            :can-complete="canComplete"
            :confirm-on-drag="confirmOnDrag"
            @edit="handleEdit"
            @delete="handleDelete"
            @complete="handleComplete"
            @change-status="handleChangeStatus"
            @drag-change-status="handleDragChangeStatus"
        />

        <!-- Modal de cambio de estado -->
        <StatusChangeModal
            v-model:open="statusChangeModalOpen"
            :entregable-nombre="entregableToChange?.nombre"
            :estado-actual="entregableToChange?.estado as EstadoEntregable"
            :nuevo-estado="nuevoEstadoPendiente"
            :loading="statusChangeLoading"
            @confirm="confirmStatusChange"
        />

        <!-- Diálogo de confirmación de eliminación -->
        <AlertDialog v-model:open="deleteDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>¿Estás seguro?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Esta acción no se puede deshacer. Se eliminará permanentemente el entregable
                        "{{ entregableToDelete?.nombre }}".
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-red-600 hover:bg-red-700"
                        @click="confirmDelete"
                    >
                        Eliminar
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
