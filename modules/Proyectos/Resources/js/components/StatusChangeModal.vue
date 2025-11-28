<script setup lang="ts">
/**
 * StatusChangeModal - Modal para cambio de estado con observaciones
 *
 * Extiende la funcionalidad de ConfirmModal añadiendo un campo de texto
 * para observaciones opcionales al cambiar el estado de un entregable.
 *
 * Uso:
 * <StatusChangeModal
 *   v-model:open="showModal"
 *   :entregable="entregable"
 *   :nuevo-estado="'completado'"
 *   @confirm="handleStatusChange"
 * />
 */
import { ref, computed, watch } from 'vue';
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
import { Textarea } from '@modules/Core/Resources/js/components/ui/textarea';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
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
    (e: 'confirm', observaciones: string): void;
    (e: 'cancel'): void;
}>();

// Estado interno para las observaciones
const observaciones = ref('');

// Limpiar observaciones cuando se cierra el modal
watch(() => props.open, (newValue) => {
    if (!newValue) {
        observaciones.value = '';
    }
});

// Configuración por estado
const estadoConfig = computed(() => {
    const configs: Record<EstadoEntregable, {
        icon: typeof CheckCircle;
        iconClass: string;
        buttonClass: string;
        title: string;
        description: string;
        confirmText: string;
    }> = {
        completado: {
            icon: CheckCircle,
            iconClass: 'text-green-600',
            buttonClass: 'bg-green-600 hover:bg-green-700 !text-white',
            title: '¿Marcar como completado?',
            description: 'El entregable se marcará como completado y se registrará la fecha y hora.',
            confirmText: 'Completar',
        },
        en_progreso: {
            icon: Clock,
            iconClass: 'text-blue-600',
            buttonClass: 'bg-blue-600 hover:bg-blue-700 !text-white',
            title: '¿Marcar en progreso?',
            description: 'El entregable se marcará como en progreso.',
            confirmText: 'En Progreso',
        },
        pendiente: {
            icon: AlertCircle,
            iconClass: 'text-yellow-600',
            buttonClass: 'bg-yellow-600 hover:bg-yellow-700 !text-white',
            title: '¿Reabrir entregable?',
            description: 'El entregable volverá a estado pendiente.',
            confirmText: 'Reabrir',
        },
        cancelado: {
            icon: XCircle,
            iconClass: 'text-red-600',
            buttonClass: 'bg-red-600 hover:bg-red-700 !text-white',
            title: '¿Cancelar entregable?',
            description: 'El entregable se marcará como cancelado.',
            confirmText: 'Cancelar Entregable',
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

// Handler para confirmar
const handleConfirm = () => {
    emit('confirm', observaciones.value);
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
    <AlertDialog :open="open" @update:open="emit('update:open', $event)">
        <AlertDialogContent class="sm:max-w-md">
            <AlertDialogHeader>
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
                        <AlertDialogTitle>{{ title }}</AlertDialogTitle>
                        <AlertDialogDescription class="mt-2">
                            {{ estadoConfig.description }}
                        </AlertDialogDescription>
                    </div>
                </div>
            </AlertDialogHeader>

            <!-- Campo de observaciones -->
            <div class="py-4">
                <Label for="observaciones" class="text-sm font-medium mb-2 block">
                    Observaciones (opcional)
                </Label>
                <Textarea
                    id="observaciones"
                    v-model="observaciones"
                    placeholder="Añade un comentario sobre este cambio de estado..."
                    class="min-h-[80px] resize-none"
                    :disabled="loading"
                />
                <p class="text-xs text-muted-foreground mt-1">
                    Las observaciones quedarán registradas en el historial de cambios.
                </p>
            </div>

            <AlertDialogFooter>
                <AlertDialogCancel
                    @click="handleCancel"
                    :disabled="loading"
                >
                    Cancelar
                </AlertDialogCancel>

                <AlertDialogAction
                    @click.prevent="handleConfirm"
                    :class="estadoConfig.buttonClass"
                    :disabled="loading"
                >
                    <Loader2
                        v-if="loading"
                        class="mr-2 h-4 w-4 animate-spin"
                        aria-hidden="true"
                    />
                    {{ estadoConfig.confirmText }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
