<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { type BreadcrumbItemType } from '@/types';
import { type FormField } from '@/types/forms';
import HistorialCandidatura from '@/components/forms/HistorialCandidatura.vue';
import AprobacionCampo from '@/components/AprobacionCampo.vue';
import FileFieldDisplay from '@/components/display/FileFieldDisplay.vue';
import RepeaterFieldDisplay from '@/components/display/RepeaterFieldDisplay.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle, Clock, User, XCircle, MessageSquare, AlertTriangle, Undo2, CheckSquare, XSquare } from 'lucide-vue-next';
import { ref, computed, reactive } from 'vue';

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface Candidatura {
    id: number;
    usuario: Usuario;
    formulario_data: Record<string, any>;
    estado: string;
    estado_label: string;
    estado_color: string;
    version: number;
    comentarios_admin?: string;
    aprobado_por?: Usuario;
    fecha_aprobacion?: string;
    created_at: string;
    updated_at: string;
}

interface CampoAprobacion {
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

interface ResumenAprobaciones {
    total: number;
    aprobados: number;
    rechazados: number;
    pendientes: number;
    porcentaje_aprobado: number;
}

interface Props {
    candidatura: Candidatura;
    configuracion_campos: FormField[];
    campo_aprobaciones?: Record<string, CampoAprobacion>;
    resumen_aprobaciones?: ResumenAprobaciones;
    puede_aprobar_campos?: boolean;
}

const props = defineProps<Props>();

// Estado reactivo para las aprobaciones
const campoAprobaciones = reactive<Record<string, CampoAprobacion>>(
    props.campo_aprobaciones || {}
);

// Función para actualizar aprobación de campo
const actualizarAprobacionCampo = (aprobacion: CampoAprobacion) => {
    campoAprobaciones[aprobacion.campo_id] = aprobacion;
    // Actualizar la página para refrescar el resumen
    router.reload({ only: ['resumen_aprobaciones'] });
};

// Computed para mostrar el modo de vista
const mostrarModoAprobacion = ref(false);

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Candidaturas', href: '/admin/candidaturas' },
    { title: props.candidatura.usuario.name, href: '#' },
];

// Estado para mostrar formularios de aprobación/rechazo
const showApprovalForm = ref(false);
const showRejectionForm = ref(false);
const showRevertForm = ref(false);

// Formularios
const approvalForm = useForm({
    comentarios: '',
});

const rejectionForm = useForm({
    comentarios: '',
});

const revertForm = useForm({
    motivo: '',
});

// Computadas
const canApprove = computed(() => {
    return props.candidatura.estado === 'pendiente' && !empty(props.candidatura.formulario_data);
});

const canReject = computed(() => {
    return props.candidatura.estado === 'pendiente';
});

const canRevert = computed(() => {
    return props.candidatura.estado === 'aprobado' || props.candidatura.estado === 'rechazado';
});

// Función para verificar si un objeto está vacío
const empty = (obj: any) => {
    return !obj || Object.keys(obj).length === 0;
};

// Métodos
const aprobar = () => {
    approvalForm.post(`/admin/candidaturas/${props.candidatura.id}/aprobar`, {
        onSuccess: () => {
            showApprovalForm.value = false;
        }
    });
};

const rechazar = () => {
    if (!rejectionForm.comentarios.trim()) {
        return;
    }
    
    rejectionForm.post(`/admin/candidaturas/${props.candidatura.id}/rechazar`, {
        onSuccess: () => {
            showRejectionForm.value = false;
        }
    });
};

const cancelApproval = () => {
    approvalForm.reset();
    showApprovalForm.value = false;
};

const cancelRejection = () => {
    rejectionForm.reset();
    showRejectionForm.value = false;
};

const volverABorrador = () => {
    revertForm.post(`/admin/candidaturas/${props.candidatura.id}/volver-borrador`, {
        onSuccess: () => {
            showRevertForm.value = false;
        }
    });
};

const cancelRevert = () => {
    revertForm.reset();
    showRevertForm.value = false;
};

// Función para obtener valor formateado de un campo
const getFieldValue = (campo: FormField, value: any) => {
    if (!value) return 'No especificado';
    
    switch (campo.type) {
        case 'checkbox':
            return Array.isArray(value) ? value.join(', ') : value;
        case 'date':
        case 'datepicker':
            try {
                return new Date(value).toLocaleDateString('es-ES', {
                    year: 'numeric',
                    month: 'long', 
                    day: 'numeric'
                });
            } catch {
                return value;
            }
        case 'disclaimer':
            // Formatear campo disclaimer con accepted y timestamp
            if (typeof value === 'object' && value !== null) {
                if (value.accepted === true) {
                    try {
                        const fecha = new Date(value.timestamp);
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
            return value;
        case 'file':
            // Los archivos se manejan con el componente FileFieldDisplay
            return null;
        case 'repeater':
            // Los repetidores se manejan con el componente RepeaterFieldDisplay  
            return null;
        case 'textarea':
            return value;
        default:
            return value;
    }
};

// Función para formatear fecha
const formatearFecha = (fecha: string) => {
    return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head :title="`Candidatura - ${candidatura.usuario.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">{{ candidatura.usuario.name }}</h1>
                    <p class="text-muted-foreground">
                        Candidatura {{ candidatura.estado_label }} - Versión {{ candidatura.version }}
                    </p>
                </div>
                <Button variant="outline" @click="router.visit('/admin/candidaturas')">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver a Lista
                </Button>
            </div>

            <!-- Estado y Acciones -->
            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2">
                                <Badge :class="candidatura.estado_color" class="text-sm">
                                    {{ candidatura.estado_label }}
                                </Badge>
                                <span class="text-sm text-muted-foreground">
                                    Versión {{ candidatura.version }}
                                </span>
                            </div>

                            <div v-if="candidatura.aprobado_por" class="flex items-center gap-2 text-sm">
                                <User class="h-4 w-4" />
                                <span>{{ candidatura.aprobado_por.name }}</span>
                                <span class="text-muted-foreground">- {{ candidatura.fecha_aprobacion }}</span>
                            </div>
                        </div>

                        <div v-if="!showApprovalForm && !showRejectionForm && !showRevertForm" class="flex gap-2">
                            <Button
                                v-if="canApprove"
                                @click="showApprovalForm = true"
                                class="bg-green-600 hover:bg-green-700"
                            >
                                <CheckCircle class="mr-2 h-4 w-4" />
                                Aprobar
                            </Button>
                            <Button
                                v-if="canReject"
                                variant="destructive"
                                @click="showRejectionForm = true"
                            >
                                <XCircle class="mr-2 h-4 w-4" />
                                Rechazar
                            </Button>
                            <Button
                                v-if="canRevert"
                                variant="outline"
                                @click="showRevertForm = true"
                                class="border-orange-300 text-orange-700 hover:bg-orange-50"
                            >
                                <Undo2 class="mr-2 h-4 w-4" />
                                Volver a Borrador
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Formulario de Aprobación -->
            <Card v-if="showApprovalForm" class="border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/20">
                <CardHeader>
                    <CardTitle class="text-green-800 dark:text-green-200 flex items-center gap-2">
                        <CheckCircle class="h-5 w-5" />
                        Aprobar Candidatura
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div>
                        <Label for="approval_comments">Comentarios (opcional)</Label>
                        <Textarea
                            id="approval_comments"
                            v-model="approvalForm.comentarios"
                            placeholder="Comentarios adicionales para el usuario..."
                            rows="3"
                        />
                    </div>
                    
                    <div class="flex justify-end gap-2">
                        <Button variant="outline" @click="cancelApproval" :disabled="approvalForm.processing">
                            Cancelar
                        </Button>
                        <Button 
                            @click="aprobar" 
                            :disabled="approvalForm.processing"
                            class="bg-green-600 hover:bg-green-700"
                        >
                            {{ approvalForm.processing ? 'Aprobando...' : 'Confirmar Aprobación' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Formulario de Rechazo -->
            <Card v-if="showRejectionForm" class="border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/20">
                <CardHeader>
                    <CardTitle class="text-red-800 dark:text-red-200 flex items-center gap-2">
                        <XCircle class="h-5 w-5" />
                        Rechazar Candidatura
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div>
                        <Label for="rejection_comments">Motivo del rechazo *</Label>
                        <Textarea
                            id="rejection_comments"
                            v-model="rejectionForm.comentarios"
                            placeholder="Explica al usuario qué debe corregir..."
                            rows="4"
                            :class="{ 'border-destructive': rejectionForm.errors.comentarios }"
                        />
                        <p v-if="rejectionForm.errors.comentarios" class="text-sm text-destructive mt-1">
                            {{ rejectionForm.errors.comentarios }}
                        </p>
                    </div>
                    
                    <div class="flex justify-end gap-2">
                        <Button variant="outline" @click="cancelRejection" :disabled="rejectionForm.processing">
                            Cancelar
                        </Button>
                        <Button 
                            variant="destructive"
                            @click="rechazar" 
                            :disabled="!rejectionForm.comentarios.trim() || rejectionForm.processing"
                        >
                            {{ rejectionForm.processing ? 'Rechazando...' : 'Confirmar Rechazo' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Formulario de Volver a Borrador -->
            <Card v-if="showRevertForm" class="border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-950/20">
                <CardHeader>
                    <CardTitle class="text-orange-800 dark:text-orange-200 flex items-center gap-2">
                        <Undo2 class="h-5 w-5" />
                        Volver Candidatura a Borrador
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="p-4 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                        <div class="flex items-start gap-3">
                            <AlertTriangle class="h-5 w-5 text-orange-600 dark:text-orange-400 mt-0.5" />
                            <div class="text-sm text-orange-800 dark:text-orange-200">
                                <p class="font-medium">Esta acción volverá la candidatura al estado "Borrador"</p>
                                <p class="mt-1">El usuario podrá editar su candidatura nuevamente y deberá ser revisada otra vez.</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <Label for="revert_reason">Motivo (opcional)</Label>
                        <Textarea
                            id="revert_reason"
                            v-model="revertForm.motivo"
                            placeholder="Explica al usuario por qué su candidatura volvió a borrador..."
                            rows="3"
                            :class="{ 'border-destructive': revertForm.errors.motivo }"
                        />
                        <p v-if="revertForm.errors.motivo" class="text-sm text-destructive mt-1">
                            {{ revertForm.errors.motivo }}
                        </p>
                    </div>
                    
                    <div class="flex justify-end gap-2">
                        <Button variant="outline" @click="cancelRevert" :disabled="revertForm.processing">
                            Cancelar
                        </Button>
                        <Button 
                            @click="volverABorrador" 
                            :disabled="revertForm.processing"
                            class="bg-orange-600 hover:bg-orange-700"
                        >
                            {{ revertForm.processing ? 'Procesando...' : 'Confirmar' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Información del Usuario -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <User class="h-5 w-5" />
                        Información del Usuario
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label class="text-sm font-medium">Nombre</Label>
                            <p class="text-muted-foreground">{{ candidatura.usuario.name }}</p>
                        </div>
                        <div>
                            <Label class="text-sm font-medium">Email</Label>
                            <p class="text-muted-foreground">{{ candidatura.usuario.email }}</p>
                        </div>
                        <div>
                            <Label class="text-sm font-medium">Fecha de creación</Label>
                            <p class="text-muted-foreground">{{ formatearFecha(candidatura.created_at) }}</p>
                        </div>
                        <div>
                            <Label class="text-sm font-medium">Última actualización</Label>
                            <p class="text-muted-foreground">{{ formatearFecha(candidatura.updated_at) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Resumen de Aprobaciones de Campos -->
            <Card v-if="resumen_aprobaciones && puede_aprobar_campos" class="border-indigo-200 dark:border-indigo-800">
                <CardHeader>
                    <CardTitle class="flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <CheckSquare class="h-5 w-5" />
                            Aprobación de Campos
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="mostrarModoAprobacion = !mostrarModoAprobacion"
                        >
                            {{ mostrarModoAprobacion ? 'Ocultar Aprobaciones' : 'Mostrar Aprobaciones' }}
                        </Button>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <p class="text-2xl font-bold">{{ resumen_aprobaciones.total }}</p>
                            <p class="text-xs text-muted-foreground">Total Campos</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-600">{{ resumen_aprobaciones.aprobados }}</p>
                            <p class="text-xs text-muted-foreground">Aprobados</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-red-600">{{ resumen_aprobaciones.rechazados }}</p>
                            <p class="text-xs text-muted-foreground">Rechazados</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-yellow-600">{{ resumen_aprobaciones.pendientes }}</p>
                            <p class="text-xs text-muted-foreground">Pendientes</p>
                        </div>
                    </div>
                    
                    <!-- Barra de progreso -->
                    <div class="mt-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span>Progreso de Aprobación</span>
                            <span>{{ resumen_aprobaciones.porcentaje_aprobado }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div 
                                class="bg-green-600 h-2 rounded-full transition-all"
                                :style="{ width: `${resumen_aprobaciones.porcentaje_aprobado}%` }"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Comentarios de la Comisión -->
            <Card v-if="candidatura.comentarios_admin" class="border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/20">
                <CardHeader>
                    <CardTitle class="text-blue-800 dark:text-blue-200 flex items-center gap-2">
                        <MessageSquare class="h-5 w-5" />
                        Comentarios de la comisión
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-blue-700 dark:text-blue-300">{{ candidatura.comentarios_admin }}</p>
                </CardContent>
            </Card>

            <!-- Datos del Formulario -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Clock class="h-5 w-5" />
                        Datos de la Candidatura
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="empty(candidatura.formulario_data)" class="text-center py-8">
                        <AlertTriangle class="mx-auto h-12 w-12 text-yellow-500" />
                        <h3 class="mt-4 text-lg font-medium">Candidatura vacía</h3>
                        <p class="text-muted-foreground">El usuario aún no ha completado su perfil de candidatura.</p>
                    </div>

                    <div v-else class="space-y-6">
                        <!-- Modo normal sin aprobaciones -->
                        <div v-if="!mostrarModoAprobacion">
                            <div
                                v-for="campo in configuracion_campos"
                                :key="campo.id"
                                class="border-b pb-4 last:border-b-0"
                            >
                                <Label class="text-sm font-medium flex items-center gap-1">
                                    {{ campo.title }}
                                    <span v-if="campo.required" class="text-red-500">*</span>
                                </Label>
                                <p v-if="campo.description" class="text-xs text-muted-foreground mb-2">
                                    {{ campo.description }}
                                </p>
                                <div class="mt-2">
                                    <!-- Componente especial para archivos -->
                                    <FileFieldDisplay 
                                        v-if="campo.type === 'file'"
                                        :value="candidatura.formulario_data[campo.id]"
                                        :label="campo.title"
                                    />
                                    <!-- Componente especial para repetidores -->
                                    <RepeaterFieldDisplay 
                                        v-else-if="campo.type === 'repeater'"
                                        :value="candidatura.formulario_data[campo.id]"
                                        :label="campo.title"
                                        :fields="campo.repeaterConfig?.fields"
                                        :item-name="campo.repeaterConfig?.itemName || 'Elemento'"
                                    />
                                    <!-- Valor normal para otros tipos de campos -->
                                    <p v-else class="text-muted-foreground">
                                        {{ getFieldValue(campo, candidatura.formulario_data[campo.id]) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modo con aprobaciones de campos -->
                        <div v-else class="space-y-4">
                            <AprobacionCampo
                                v-for="campo in configuracion_campos"
                                :key="campo.id"
                                :candidatura-id="candidatura.id"
                                :campo-id="campo.id"
                                :campo-title="campo.title"
                                :valor="candidatura.formulario_data[campo.id]"
                                :aprobacion="campoAprobaciones[campo.id]"
                                :puede-aprobar="puede_aprobar_campos"
                                :readonly="candidatura.estado === 'aprobado'"
                                @campo-actualizado="actualizarAprobacionCampo"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Historial de Cambios -->
            <HistorialCandidatura 
                :candidatura-id="candidatura.id"
                :version-actual="candidatura.version"
                :is-admin="true"
            />
        </div>
    </AppLayout>
</template>