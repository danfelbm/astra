<script setup lang="ts">
/**
 * Componente de acciones rápidas para cambiar el estado de una evidencia.
 * Incluye modal con opción de agregar comentario contextual.
 */
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Switch } from '@modules/Core/Resources/js/components/ui/switch';
import { Textarea } from '@modules/Core/Resources/js/components/ui/textarea';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@modules/Core/Resources/js/components/ui/dialog';
import { CheckCircle, XCircle, Clock } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

interface Props {
    evidencia: {
        id: number;
        estado: string;
    };
    proyectoId: number;
    estadoActual: string;
}

const props = defineProps<Props>();

// Estado del modal
const showModal = ref(false);
const nuevoEstado = ref<'aprobada' | 'rechazada' | 'pendiente' | null>(null);
const comentario = ref('');
const agregarComentario = ref(false);
const procesando = ref(false);

// Configuración de estados
const estadosConfig = {
    aprobada: {
        label: 'Aprobar',
        icon: CheckCircle,
        color: 'text-green-600 hover:text-green-700 hover:bg-green-50',
        variant: 'ghost' as const,
        defaultComentario: false,
    },
    rechazada: {
        label: 'Rechazar',
        icon: XCircle,
        color: 'text-red-600 hover:text-red-700 hover:bg-red-50',
        variant: 'ghost' as const,
        defaultComentario: true, // Por defecto ON para rechazar
    },
    pendiente: {
        label: 'A Pendiente',
        icon: Clock,
        color: 'text-yellow-600 hover:text-yellow-700 hover:bg-yellow-50',
        variant: 'ghost' as const,
        defaultComentario: false,
    },
};

// Estados disponibles según el estado actual
const estadosDisponibles = computed(() => {
    const estados: ('aprobada' | 'rechazada' | 'pendiente')[] = [];
    if (props.estadoActual !== 'aprobada') estados.push('aprobada');
    if (props.estadoActual !== 'rechazada') estados.push('rechazada');
    if (props.estadoActual !== 'pendiente') estados.push('pendiente');
    return estados;
});

// Título del modal según el estado seleccionado
const tituloModal = computed(() => {
    if (!nuevoEstado.value) return '';
    const config = estadosConfig[nuevoEstado.value];
    return `${config.label} evidencia`;
});

// Descripción del modal
const descripcionModal = computed(() => {
    if (!nuevoEstado.value) return '';
    const mensajes = {
        aprobada: '¿Confirmas que deseas aprobar esta evidencia?',
        rechazada: '¿Confirmas que deseas rechazar esta evidencia?',
        pendiente: '¿Confirmas que deseas devolver esta evidencia a estado pendiente?',
    };
    return mensajes[nuevoEstado.value];
});

// Abrir modal con el estado seleccionado
const abrirModal = (estado: 'aprobada' | 'rechazada' | 'pendiente') => {
    nuevoEstado.value = estado;
    comentario.value = '';
    agregarComentario.value = estadosConfig[estado].defaultComentario;
    showModal.value = true;
};

// Confirmar el cambio de estado
const confirmarCambio = () => {
    if (!nuevoEstado.value) return;

    procesando.value = true;

    router.post(`/admin/proyectos/${props.proyectoId}/evidencias/${props.evidencia.id}/cambiar-estado`, {
        estado: nuevoEstado.value,
        comentario: agregarComentario.value ? comentario.value : null,
        agregar_comentario: agregarComentario.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Estado cambiado a ${nuevoEstado.value}`);
            showModal.value = false;
            comentario.value = '';
        },
        onError: (errors) => {
            const mensaje = Object.values(errors).flat().join(', ') || 'Error al cambiar estado';
            toast.error(mensaje);
        },
        onFinish: () => {
            procesando.value = false;
        },
    });
};

// Cancelar y cerrar modal
const cancelar = () => {
    showModal.value = false;
    nuevoEstado.value = null;
    comentario.value = '';
    agregarComentario.value = false;
};
</script>

<template>
    <div class="flex items-center gap-2">
        <!-- Botones de acción -->
        <Button
            v-for="estado in estadosDisponibles"
            :key="estado"
            :variant="estadosConfig[estado].variant"
            size="sm"
            :class="['h-8', estadosConfig[estado].color]"
            @click="abrirModal(estado)"
        >
            <component :is="estadosConfig[estado].icon" class="h-4 w-4 mr-1" />
            {{ estadosConfig[estado].label }}
        </Button>

        <!-- Modal de confirmación -->
        <Dialog v-model:open="showModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ tituloModal }}</DialogTitle>
                    <DialogDescription>{{ descripcionModal }}</DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <!-- Switch para agregar comentario -->
                    <div class="flex items-center justify-between">
                        <Label for="agregar-comentario" class="text-sm">
                            {{ nuevoEstado === 'rechazada' ? 'Agregar motivo' : 'Agregar comentario' }}
                        </Label>
                        <Switch
                            id="agregar-comentario"
                            v-model="agregarComentario"
                        />
                    </div>

                    <!-- Textarea para comentario (visible si switch activo) -->
                    <div v-if="agregarComentario" class="space-y-2">
                        <Textarea
                            v-model="comentario"
                            :placeholder="nuevoEstado === 'rechazada'
                                ? 'Describe el motivo del rechazo...'
                                : 'Agrega un comentario opcional...'"
                            rows="4"
                            class="resize-none"
                        />
                        <p class="text-xs text-muted-foreground">
                            Este comentario aparecerá con el badge del nuevo estado.
                        </p>
                    </div>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button
                        variant="outline"
                        @click="cancelar"
                        :disabled="procesando"
                    >
                        Cancelar
                    </Button>
                    <Button
                        :variant="nuevoEstado === 'rechazada' ? 'destructive' : 'default'"
                        @click="confirmarCambio"
                        :disabled="procesando"
                    >
                        {{ procesando ? 'Procesando...' : 'Confirmar' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
