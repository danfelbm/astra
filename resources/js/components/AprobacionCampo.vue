<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import FileFieldDisplay from '@/components/display/FileFieldDisplay.vue';
import RepeaterFieldDisplay from '@/components/display/RepeaterFieldDisplay.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { 
    CheckCircle, 
    XCircle, 
    Clock, 
    MessageSquare,
    User,
    Calendar
} from 'lucide-vue-next';

interface Aprobacion {
    campo_id: string;
    aprobado: boolean;
    estado_label: string;
    estado_color: string;
    comentario?: string;
    aprobado_por?: {
        id: number;
        name: string;
        email: string;
    };
    fecha_aprobacion?: string;
}

interface Props {
    candidaturaId: number;
    campoId: string;
    campoTitle: string;
    valor: any;
    aprobacion?: Aprobacion;
    puedeAprobar: boolean;
    readonly?: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'campo-actualizado': [aprobacion: Aprobacion];
}>();

// Estado del componente
const showApprovalDialog = ref(false);
const showRejectionDialog = ref(false);
const comentario = ref('');
const processing = ref(false);

// Computed para determinar el estado del campo
const estadoCampo = computed(() => {
    if (!props.aprobacion) {
        return 'pendiente';
    }
    return props.aprobacion.aprobado ? 'aprobado' : 'rechazado';
});

const estadoColor = computed(() => {
    if (!props.aprobacion) {
        return 'bg-yellow-100 text-yellow-800';
    }
    return props.aprobacion.aprobado 
        ? 'bg-green-100 text-green-800' 
        : 'bg-red-100 text-red-800';
});

const estadoLabel = computed(() => {
    if (!props.aprobacion) {
        return 'Pendiente';
    }
    return props.aprobacion.aprobado ? 'Aprobado' : 'Rechazado';
});

const estadoIcon = computed(() => {
    if (!props.aprobacion) {
        return Clock;
    }
    return props.aprobacion.aprobado ? CheckCircle : XCircle;
});

// Métodos para aprobar/rechazar
const aprobarCampo = async () => {
    processing.value = true;
    
    try {
        const response = await fetch(`/admin/candidaturas/${props.candidaturaId}/campos/${props.campoId}/aprobar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                comentario: comentario.value || null,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            emit('campo-actualizado', data.aprobacion);
            showApprovalDialog.value = false;
            comentario.value = '';
        } else {
            console.error('Error al aprobar campo:', data);
        }
    } catch (error) {
        console.error('Error al aprobar campo:', error);
    } finally {
        processing.value = false;
    }
};

const rechazarCampo = async () => {
    if (!comentario.value.trim()) {
        return;
    }

    processing.value = true;
    
    try {
        const response = await fetch(`/admin/candidaturas/${props.candidaturaId}/campos/${props.campoId}/rechazar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                comentario: comentario.value,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            emit('campo-actualizado', data.aprobacion);
            showRejectionDialog.value = false;
            comentario.value = '';
        } else {
            console.error('Error al rechazar campo:', data);
        }
    } catch (error) {
        console.error('Error al rechazar campo:', error);
    } finally {
        processing.value = false;
    }
};

// Detectar si el valor es de tipo file
const isFileField = computed(() => {
    // Si es un array de strings con rutas de archivo
    if (Array.isArray(props.valor) && props.valor.length > 0) {
        const firstItem = props.valor[0];
        if (typeof firstItem === 'string' && (
            firstItem.includes('uploads/') || 
            firstItem.includes('.pdf') ||
            firstItem.includes('.doc') ||
            firstItem.includes('.jpg') ||
            firstItem.includes('.png')
        )) {
            return true;
        }
    }
    return false;
});

// Detectar si el valor es de tipo repeater
const isRepeaterField = computed(() => {
    // Si es un array de objetos con estructura de repetidor
    if (Array.isArray(props.valor) && props.valor.length > 0) {
        const firstItem = props.valor[0];
        if (typeof firstItem === 'object' && firstItem !== null) {
            // Verificar si tiene estructura de repetidor (id y data) o es un objeto de datos
            return firstItem.hasOwnProperty('id') && firstItem.hasOwnProperty('data') ||
                   (typeof firstItem === 'object' && !Array.isArray(firstItem));
        }
    }
    return false;
});

// Detectar si el valor es de tipo disclaimer
const isDisclaimerField = computed(() => {
    // Si es un objeto con estructura de disclaimer (accepted y timestamp)
    if (typeof props.valor === 'object' && props.valor !== null && !Array.isArray(props.valor)) {
        return props.valor.hasOwnProperty('accepted') && props.valor.hasOwnProperty('timestamp');
    }
    return false;
});

// Función para formatear el valor del campo (para campos normales)
const formatearValor = (valor: any): string => {
    if (isFileField.value || isRepeaterField.value) {
        return ''; // Se maneja con componentes especiales
    }
    
    // Manejar campos tipo disclaimer
    if (isDisclaimerField.value) {
        if (typeof valor === 'object' && valor !== null) {
            if (valor.accepted === true) {
                try {
                    const fecha = new Date(valor.timestamp);
                    return `✅ Aceptado el ${fecha.toLocaleDateString('es-ES', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    })}`;
                } catch {
                    return '✅ Aceptado';
                }
            } else {
                return '❌ No aceptado';
            }
        }
    }
    
    if (Array.isArray(valor)) {
        return valor.join(', ');
    }
    if (typeof valor === 'object' && valor !== null) {
        return JSON.stringify(valor);
    }
    return valor?.toString() || 'Sin valor';
};
</script>

<template>
    <div class="border rounded-lg p-4 space-y-3">
        <!-- Encabezado del campo -->
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h4 class="font-medium text-sm">{{ campoTitle }}</h4>
                <!-- Componente para archivos -->
                <div v-if="isFileField" class="mt-2">
                    <FileFieldDisplay 
                        :value="valor"
                        :label="campoTitle"
                    />
                </div>
                <!-- Componente para repetidores -->
                <div v-else-if="isRepeaterField" class="mt-2">
                    <RepeaterFieldDisplay 
                        :value="valor"
                        :label="campoTitle"
                    />
                </div>
                <!-- Valor normal para otros campos -->
                <p v-else class="text-sm text-muted-foreground mt-1">
                    {{ formatearValor(valor) }}
                </p>
            </div>

            <!-- Estado del campo -->
            <div class="flex items-center gap-2">
                <Badge :class="estadoColor" class="flex items-center gap-1">
                    <component :is="estadoIcon" class="h-3 w-3" />
                    {{ estadoLabel }}
                </Badge>
            </div>
        </div>

        <!-- Información de aprobación (si existe) -->
        <div v-if="aprobacion?.aprobado_por" class="pt-2 border-t">
            <div class="flex items-start gap-2 text-xs text-muted-foreground">
                <User class="h-3 w-3 mt-0.5" />
                <div class="flex-1">
                    <p>
                        {{ aprobacion.aprobado ? 'Aprobado' : 'Rechazado' }} por 
                        <span class="font-medium">{{ aprobacion.aprobado_por.name }}</span>
                    </p>
                    <p v-if="aprobacion.fecha_aprobacion" class="flex items-center gap-1 mt-0.5">
                        <Calendar class="h-3 w-3" />
                        {{ aprobacion.fecha_aprobacion }}
                    </p>
                </div>
            </div>

            <!-- Comentario del revisor -->
            <div v-if="aprobacion.comentario" class="mt-2 p-2 bg-muted rounded text-xs">
                <div class="flex items-start gap-1">
                    <MessageSquare class="h-3 w-3 mt-0.5 text-muted-foreground" />
                    <p class="flex-1">{{ aprobacion.comentario }}</p>
                </div>
            </div>
        </div>

        <!-- Botones de acción -->
        <div v-if="puedeAprobar && !readonly" class="flex gap-2 pt-2">
            <Button
                v-if="estadoCampo !== 'aprobado'"
                size="sm"
                variant="outline"
                class="text-green-700 border-green-300 hover:bg-green-50"
                @click="showApprovalDialog = true"
            >
                <CheckCircle class="h-3 w-3 mr-1" />
                Aprobar
            </Button>
            <Button
                v-if="estadoCampo !== 'rechazado'"
                size="sm"
                variant="outline"
                class="text-red-700 border-red-300 hover:bg-red-50"
                @click="showRejectionDialog = true"
            >
                <XCircle class="h-3 w-3 mr-1" />
                Rechazar
            </Button>
        </div>
    </div>

    <!-- Diálogo de aprobación -->
    <Dialog v-model:open="showApprovalDialog">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Aprobar Campo</DialogTitle>
                <DialogDescription>
                    Estás aprobando el campo "{{ campoTitle }}". 
                    Puedes agregar un comentario opcional.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <div>
                    <Label>Valor actual del campo</Label>
                    <!-- Componente para archivos -->
                    <div v-if="isFileField" class="mt-2 p-2 bg-muted rounded">
                        <FileFieldDisplay 
                            :value="valor"
                            :label="campoTitle"
                        />
                    </div>
                    <!-- Componente para repetidores -->
                    <div v-else-if="isRepeaterField" class="mt-2 p-2 bg-muted rounded">
                        <RepeaterFieldDisplay 
                            :value="valor"
                            :label="campoTitle"
                        />
                    </div>
                    <!-- Valor normal -->
                    <p v-else class="mt-1 p-2 bg-muted rounded text-sm">
                        {{ formatearValor(valor) }}
                    </p>
                </div>

                <div>
                    <Label for="comentario-aprobacion">
                        Comentario (opcional)
                    </Label>
                    <Textarea
                        id="comentario-aprobacion"
                        v-model="comentario"
                        placeholder="Agregar comentario..."
                        rows="3"
                    />
                </div>
            </div>

            <DialogFooter>
                <Button
                    variant="outline"
                    @click="showApprovalDialog = false"
                    :disabled="processing"
                >
                    Cancelar
                </Button>
                <Button
                    @click="aprobarCampo"
                    :disabled="processing"
                    class="bg-green-600 hover:bg-green-700"
                >
                    <CheckCircle class="h-4 w-4 mr-2" />
                    {{ processing ? 'Aprobando...' : 'Aprobar Campo' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Diálogo de rechazo -->
    <Dialog v-model:open="showRejectionDialog">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Rechazar Campo</DialogTitle>
                <DialogDescription>
                    Estás rechazando el campo "{{ campoTitle }}". 
                    Por favor, indica el motivo del rechazo.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <div>
                    <Label>Valor actual del campo</Label>
                    <!-- Componente para archivos -->
                    <div v-if="isFileField" class="mt-2 p-2 bg-muted rounded">
                        <FileFieldDisplay 
                            :value="valor"
                            :label="campoTitle"
                        />
                    </div>
                    <!-- Componente para repetidores -->
                    <div v-else-if="isRepeaterField" class="mt-2 p-2 bg-muted rounded">
                        <RepeaterFieldDisplay 
                            :value="valor"
                            :label="campoTitle"
                        />
                    </div>
                    <!-- Valor normal -->
                    <p v-else class="mt-1 p-2 bg-muted rounded text-sm">
                        {{ formatearValor(valor) }}
                    </p>
                </div>

                <div>
                    <Label for="comentario-rechazo">
                        Motivo del rechazo *
                    </Label>
                    <Textarea
                        id="comentario-rechazo"
                        v-model="comentario"
                        placeholder="Explica qué debe corregir el usuario..."
                        rows="4"
                        required
                    />
                </div>
            </div>

            <DialogFooter>
                <Button
                    variant="outline"
                    @click="showRejectionDialog = false"
                    :disabled="processing"
                >
                    Cancelar
                </Button>
                <Button
                    variant="destructive"
                    @click="rechazarCampo"
                    :disabled="!comentario.trim() || processing"
                >
                    <XCircle class="h-4 w-4 mr-2" />
                    {{ processing ? 'Rechazando...' : 'Rechazar Campo' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>