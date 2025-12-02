<script setup lang="ts">
/**
 * HitosEntregablesPanel - Componente orquestador para visualización de entregables
 * Maneja los diferentes modos de vista (lista, tabs, kanban) y los modales compartidos
 */
import { ref, toRef, watch, onMounted } from 'vue';
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
import ComentariosSheet from './ComentariosSheet.vue';
import ActividadSheet from './ActividadSheet.vue';

// Props
interface Props {
    entregables: Entregable[];
    canEdit?: boolean;
    canDelete?: boolean;
    canComplete?: boolean;
    // ID del entregable para abrir comentarios por deeplink
    initialComentariosEntregableId?: number;
    // ID del entregable para abrir actividad por deeplink
    initialActividadEntregableId?: number;
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
    'complete': [entregable: Entregable, observaciones: string, archivos: UploadedFile[]];
    'update-status': [entregable: Entregable, estado: string, observaciones: string, archivos: UploadedFile[]];
    // Eventos para deeplink de comentarios
    'comentarios-open': [entregableId: number];
    'comentarios-close': [];
    // Eventos para deeplink de actividad
    'actividad-open': [entregableId: number];
    'actividad-close': [];
}>();

// Composable (solo para viewMode y utilidades)
const entregablesRef = toRef(props, 'entregables');
const { viewMode } = useEntregablesView(entregablesRef);

// Clave de localStorage para confirmOnDrag
const CONFIRM_DRAG_KEY = 'entregables-confirm-drag';

// Función para leer confirmOnDrag de localStorage
const getStoredConfirmOnDrag = (): boolean => {
    const stored = localStorage.getItem(CONFIRM_DRAG_KEY);
    // Si no existe, default es true (mostrar modal)
    if (stored === null) return true;
    return stored === 'true';
};

// Estado local para confirmOnDrag (manejado directamente, no vía composable)
const confirmOnDrag = ref<boolean>(getStoredConfirmOnDrag());

// Handler para actualizar confirmOnDrag - guarda directamente a localStorage
const handleUpdateConfirmOnDrag = (value: boolean) => {
    confirmOnDrag.value = value;
    localStorage.setItem(CONFIRM_DRAG_KEY, String(value));
};

// Estado del modal de cambio de estado
const statusChangeModalOpen = ref(false);
const entregableToChange = ref<Entregable | null>(null);
const nuevoEstadoPendiente = ref<EstadoEntregable>('pendiente');
const statusChangeLoading = ref(false);

// Estado del diálogo de confirmación de eliminación
const deleteDialogOpen = ref(false);
const entregableToDelete = ref<Entregable | null>(null);

// Estado del sheet de comentarios
const showEntregableComentarios = ref(false);
const entregableParaComentarios = ref<Entregable | null>(null);

// Estado del sheet de actividad
const showEntregableActividad = ref(false);
const entregableParaActividad = ref<Entregable | null>(null);

// Handler para mostrar comentarios
const handleShowComentarios = (entregable: Entregable) => {
    entregableParaComentarios.value = entregable;
    showEntregableComentarios.value = true;
};

// Handler para mostrar actividad
const handleShowActividad = (entregable: Entregable) => {
    entregableParaActividad.value = entregable;
    showEntregableActividad.value = true;
};

// Watch para emitir eventos de deeplink cuando se abre/cierra el sheet de comentarios
watch(showEntregableComentarios, (isOpen) => {
    if (isOpen && entregableParaComentarios.value) {
        emit('comentarios-open', entregableParaComentarios.value.id);
    } else if (!isOpen) {
        emit('comentarios-close');
    }
});

// Watch para emitir eventos de deeplink cuando se abre/cierra el sheet de actividad
watch(showEntregableActividad, (isOpen) => {
    if (isOpen && entregableParaActividad.value) {
        emit('actividad-open', entregableParaActividad.value.id);
    } else if (!isOpen) {
        emit('actividad-close');
    }
});

// Inicializar desde deeplink
onMounted(() => {
    // Inicializar comentarios desde deeplink
    if (props.initialComentariosEntregableId) {
        const entregable = props.entregables.find(e => e.id === props.initialComentariosEntregableId);
        if (entregable) {
            entregableParaComentarios.value = entregable;
            showEntregableComentarios.value = true;
        }
    }
    // Inicializar actividad desde deeplink
    if (props.initialActividadEntregableId) {
        const entregable = props.entregables.find(e => e.id === props.initialActividadEntregableId);
        if (entregable) {
            entregableParaActividad.value = entregable;
            showEntregableActividad.value = true;
        }
    }
});

// Handlers de navegación
const handleView = (entregable: Entregable) => {
    emit('view', entregable);
};

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
    <div class="space-y-4 min-w-0">
        <!-- Toggle de modo de vista -->
        <HitosViewModeToggle
            v-model="viewMode"
            :confirm-on-drag="confirmOnDrag"
            @update:confirm-on-drag="handleUpdateConfirmOnDrag"
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
            @show-comentarios="handleShowComentarios"
            @show-actividad="handleShowActividad"
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
            @show-comentarios="handleShowComentarios"
            @show-actividad="handleShowActividad"
        />

        <HitosEntregablesKanban
            v-else-if="viewMode === 'kanban'"
            :entregables="entregables"
            :can-edit="canEdit"
            :can-delete="canDelete"
            :can-complete="canComplete"
            :can-drag="canComplete"
            :confirm-on-drag="confirmOnDrag"
            @view="handleView"
            @edit="handleEdit"
            @delete="handleDelete"
            @complete="handleComplete"
            @change-status="handleChangeStatus"
            @drag-change-status="handleDragChangeStatus"
            @show-comentarios="handleShowComentarios"
            @show-actividad="handleShowActividad"
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

        <!-- Sheet de comentarios del entregable -->
        <ComentariosSheet
            v-if="entregableParaComentarios"
            v-model:open="showEntregableComentarios"
            commentable-type="entregables"
            :commentable-id="entregableParaComentarios.id"
            :can-create="canEdit"
        />

        <!-- Sheet de actividad del entregable -->
        <ActividadSheet
            v-if="entregableParaActividad"
            v-model:open="showEntregableActividad"
            actividad-type="entregables"
            :actividad-id="entregableParaActividad.id"
        />
    </div>
</template>
