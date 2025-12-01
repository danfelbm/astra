<script setup lang="ts">
/**
 * StatusChangeModal - Modal para cambio de estado con observaciones
 *
 * Incluye editor WYSIWYG con soporte para archivos adjuntos.
 * El comentario se guarda con contexto de estado (como en evidencias).
 *
 * Uso:
 * <StatusChangeModal
 *   v-model:open="showModal"
 *   :entregable-nombre="entregable.nombre"
 *   :nuevo-estado="'completado'"
 *   @confirm="handleStatusChange"
 * />
 */
import { ref, computed, watch } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@modules/Core/Resources/js/components/ui/dialog';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Switch } from '@modules/Core/Resources/js/components/ui/switch';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import ComentarioContextoInput from '@modules/Comentarios/Resources/js/components/ComentarioContextoInput.vue';
import type { UploadedFile } from '@modules/Comentarios/Resources/js/types/comentarios';
import {
    CheckCircle,
    Clock,
    AlertCircle,
    XCircle,
    Loader2
} from 'lucide-vue-next';

// Tipos de estados disponibles
type EstadoEntregable = 'pendiente' | 'en_progreso' | 'completado' | 'cancelado';

// Props del componente
interface Props {
    // Estado del modal (v-model:open)
    open?: boolean;
    // Nombre del entregable para mostrar en el título
    entregableNombre?: string;
    // Estado actual del entregable
    estadoActual?: EstadoEntregable;
    // Nuevo estado al que se cambiará
    nuevoEstado: EstadoEntregable;
    // Estado de carga
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
    loading: false,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm', observaciones: string, archivos: UploadedFile[]): void;
    (e: 'cancel'): void;
}>();

// Estado interno
const observaciones = ref('');
const archivos = ref<UploadedFile[]>([]);
const agregarComentario = ref(false);
const comentarioInputRef = ref<InstanceType<typeof ComentarioContextoInput> | null>(null);

// Configuración por estado (incluye si debe tener comentario por defecto)
const estadoConfig = computed(() => {
    const configs: Record<EstadoEntregable, {
        icon: typeof CheckCircle;
        iconClass: string;
        buttonClass: string;
        title: string;
        description: string;
        confirmText: string;
        defaultComentario: boolean;
    }> = {
        completado: {
            icon: CheckCircle,
            iconClass: 'text-green-600',
            buttonClass: 'bg-green-600 hover:bg-green-700',
            title: '¿Marcar como completado?',
            description: 'El entregable se marcará como completado y se registrará la fecha y hora.',
            confirmText: 'Completar',
            defaultComentario: false,
        },
        en_progreso: {
            icon: Clock,
            iconClass: 'text-blue-600',
            buttonClass: 'bg-blue-600 hover:bg-blue-700',
            title: '¿Marcar en progreso?',
            description: 'El entregable se marcará como en progreso.',
            confirmText: 'En Progreso',
            defaultComentario: false,
        },
        pendiente: {
            icon: AlertCircle,
            iconClass: 'text-yellow-600',
            buttonClass: 'bg-yellow-600 hover:bg-yellow-700',
            title: '¿Reabrir entregable?',
            description: 'El entregable volverá a estado pendiente.',
            confirmText: 'Reabrir',
            defaultComentario: false,
        },
        cancelado: {
            icon: XCircle,
            iconClass: 'text-red-600',
            buttonClass: 'bg-red-600 hover:bg-red-700',
            title: '¿Cancelar entregable?',
            description: 'El entregable se marcará como cancelado.',
            confirmText: 'Cancelar Entregable',
            defaultComentario: true, // Por defecto ON para cancelar
        },
    };
    return configs[props.nuevoEstado];
});

// Título dinámico con nombre del entregable
const title = computed(() => {
    if (props.entregableNombre) {
        return `${estadoConfig.value.title.replace('?', '')}: "${props.entregableNombre}"?`;
    }
    return estadoConfig.value.title;
});

// Limpiar formulario
const limpiarFormulario = () => {
    observaciones.value = '';
    archivos.value = [];
    comentarioInputRef.value?.clear();
};

// Resetear cuando se abre el modal
watch(() => props.open, (newValue) => {
    if (newValue) {
        // Resetear el switch al valor por defecto del estado
        agregarComentario.value = estadoConfig.value.defaultComentario;
    } else {
        limpiarFormulario();
        agregarComentario.value = false;
    }
});

// Handler para confirmar
const handleConfirm = () => {
    const comentarioFinal = agregarComentario.value ? observaciones.value : '';
    const archivosFinal = agregarComentario.value ? archivos.value : [];
    emit('confirm', comentarioFinal, archivosFinal);
    // No cerramos automáticamente si está en loading
    if (!props.loading) {
        emit('update:open', false);
    }
};

// Handler para cancelar
const handleCancel = () => {
    emit('cancel');
    emit('update:open', false);
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <!-- Header con icono y título -->
                <div class="flex gap-3">
                    <!-- Icono de estado -->
                    <component
                        :is="estadoConfig.icon"
                        class="h-5 w-5 mt-0.5 flex-shrink-0"
                        :class="estadoConfig.iconClass"
                        aria-hidden="true"
                    />
                    <div class="flex-1">
                        <DialogTitle>{{ title }}</DialogTitle>
                        <DialogDescription class="mt-2">
                            {{ estadoConfig.description }}
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <!-- Switch para agregar comentario -->
                <div class="flex items-center justify-between">
                    <Label for="agregar-comentario" class="text-sm">
                        Agregar comentario
                    </Label>
                    <Switch
                        id="agregar-comentario"
                        v-model="agregarComentario"
                        :disabled="loading"
                    />
                </div>

                <!-- Editor WYSIWYG para comentario (visible si switch activo) -->
                <div v-if="agregarComentario" class="space-y-2">
                    <ComentarioContextoInput
                        ref="comentarioInputRef"
                        v-model="observaciones"
                        placeholder="Agrega un comentario sobre este cambio de estado..."
                        :rows="3"
                        :show-file-upload="true"
                        upload-module="comentarios"
                        upload-field-id="entregable-comentario"
                        @update:archivos="archivos = $event"
                    />
                    <p class="text-xs text-muted-foreground">
                        Este comentario aparecerá con el badge del nuevo estado.
                    </p>
                </div>
            </div>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button
                    variant="outline"
                    @click="handleCancel"
                    :disabled="loading"
                >
                    Cancelar
                </Button>
                <Button
                    @click="handleConfirm"
                    :class="estadoConfig.buttonClass"
                    :disabled="loading"
                >
                    <Loader2
                        v-if="loading"
                        class="mr-2 h-4 w-4 animate-spin"
                        aria-hidden="true"
                    />
                    {{ estadoConfig.confirmText }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
