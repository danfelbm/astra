<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Alert, AlertDescription } from '@/components/ui/alert';
import RichTextEditor from '@/components/ui/RichTextEditor.vue';
import { type BreadcrumbItemType } from '@/types';
import { type FormField } from '@/types/forms';
import HistorialCandidatura from '@/components/forms/HistorialCandidatura.vue';
import AprobacionCampo from '@/components/AprobacionCampo.vue';
import ComentariosHistorial from '@/components/candidaturas/ComentariosHistorial.vue';
import FileFieldDisplay from '@/components/display/FileFieldDisplay.vue';
import RepeaterFieldDisplay from '@/components/display/RepeaterFieldDisplay.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle, Clock, User, XCircle, MessageSquare, AlertTriangle, Undo2, CheckSquare, XSquare, Plus, History, CheckCircle2, Loader2, Wrench, ToggleLeft, ToggleRight } from 'lucide-vue-next';
import { ref, computed, reactive, Transition } from 'vue';
import axios from 'axios';

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
    subsanar: boolean;
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

interface Comentario {
    id: number;
    comentario: string;
    tipo: string;
    tipo_label: string;
    tipo_color: string;
    tipo_icon: string;
    version_candidatura: number;
    enviado_por_email: boolean;
    created_by?: {
        id: number;
        name: string;
        email: string;
    };
    fecha: string;
    fecha_formateada: string;
    fecha_relativa: string;
}

interface Props {
    candidatura: Candidatura;
    configuracion_campos: FormField[];
    campo_aprobaciones?: Record<string, CampoAprobacion>;
    resumen_aprobaciones?: ResumenAprobaciones;
    puede_aprobar_campos?: boolean;
    comentarios?: Comentario[];
}

const props = defineProps<Props>();

// Obtener usuario actual
const page = usePage<any>();
const currentUser = page.props.auth?.user || null;

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

// Estado para subsanación
const subsanarEstado = ref(props.candidatura.subsanar);
const toggleLoading = ref(false);
const toggleMessage = ref('');

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

// Estado para comentarios
const showComentarioForm = ref(false);
const comentariosReactivos = ref<Comentario[]>(props.comentarios || []);
const mostrarHistorialComentarios = ref(false);
const guardandoComentario = ref(false);
const mensajeExito = ref('');

// Formulario para nuevo comentario
const comentarioForm = useForm({
    comentario: '',
    tipo: 'general',
    enviar_email: false,
});

// Computadas
const canApprove = computed(() => {
    return props.candidatura.estado === 'pendiente' && !empty(props.candidatura.formulario_data);
});

const canReject = computed(() => {
    return props.candidatura.estado === 'pendiente';
});

const canRevert = computed(() => {
    return props.candidatura.estado === 'aprobado' || props.candidatura.estado === 'rechazado' || props.candidatura.estado === 'pendiente';
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
            
            // Agregar comentario de aprobación al historial reactivo
            if (approvalForm.comentarios) {
                const nuevoComentario = crearComentarioReactivo(approvalForm.comentarios, 'aprobacion');
                comentariosReactivos.value.unshift(nuevoComentario);
                
                // Actualizar el comentario actual mostrado
                props.candidatura.comentarios_admin = approvalForm.comentarios;
                
                // Mostrar historial automáticamente
                mostrarHistorialComentarios.value = true;
            }
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
            
            // Agregar comentario de rechazo al historial reactivo
            const nuevoComentario = crearComentarioReactivo(rejectionForm.comentarios, 'rechazo');
            comentariosReactivos.value.unshift(nuevoComentario);
            
            // Actualizar el comentario actual mostrado
            props.candidatura.comentarios_admin = rejectionForm.comentarios;
            
            // Mostrar historial automáticamente
            mostrarHistorialComentarios.value = true;
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
            
            // Actualizar automáticamente el estado de subsanar a true
            subsanarEstado.value = true;
            
            // Agregar comentario de vuelta a borrador al historial reactivo
            if (revertForm.motivo) {
                const nuevoComentario = crearComentarioReactivo(revertForm.motivo, 'borrador');
                comentariosReactivos.value.unshift(nuevoComentario);
                
                // Actualizar el comentario actual mostrado
                props.candidatura.comentarios_admin = revertForm.motivo;
                
                // Mostrar historial automáticamente
                mostrarHistorialComentarios.value = true;
            }
            
            // Mostrar mensaje de subsanación habilitada
            toggleMessage.value = 'Candidatura devuelta a borrador con subsanación habilitada';
            setTimeout(() => {
                toggleMessage.value = '';
            }, 3000);
        }
    });
};

const cancelRevert = () => {
    revertForm.reset();
    showRevertForm.value = false;
};

// Helper para crear objeto de comentario reactivo
const crearComentarioReactivo = (comentario: string, tipo: string): Comentario => {
    const now = new Date();
    const tipoLabels: Record<string, string> = {
        'general': 'Comentario General',
        'aprobacion': 'Aprobación',
        'rechazo': 'Rechazo',
        'borrador': 'Vuelta a Borrador',
        'nota_admin': 'Nota Administrativa',
    };
    
    const tipoColors: Record<string, string> = {
        'general': 'bg-blue-100 text-blue-800',
        'aprobacion': 'bg-green-100 text-green-800',
        'rechazo': 'bg-red-100 text-red-800',
        'borrador': 'bg-yellow-100 text-yellow-800',
        'nota_admin': 'bg-gray-100 text-gray-800',
    };
    
    const tipoIcons: Record<string, string> = {
        'general': 'message-circle',
        'aprobacion': 'check-circle',
        'rechazo': 'x-circle',
        'borrador': 'rotate-ccw',
        'nota_admin': 'sticky-note',
    };
    
    return {
        id: Date.now(), // ID temporal
        comentario: comentario,
        tipo: tipo,
        tipo_label: tipoLabels[tipo] || 'Desconocido',
        tipo_color: tipoColors[tipo] || 'bg-gray-100 text-gray-800',
        tipo_icon: tipoIcons[tipo] || 'message-square',
        version_candidatura: props.candidatura.version,
        enviado_por_email: true,
        created_by: currentUser ? {
            id: currentUser.id,
            name: currentUser.name,
            email: currentUser.email,
        } : undefined,
        fecha: now.toISOString(),
        fecha_formateada: now.toLocaleDateString('es-ES') + ' ' + now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }),
        fecha_relativa: 'hace unos segundos',
    };
};

// Métodos para comentarios
const agregarComentario = async () => {
    if (!comentarioForm.comentario.trim()) {
        return;
    }

    guardandoComentario.value = true;
    
    try {
        const response = await axios.post(`/admin/candidaturas/${props.candidatura.id}/comentarios`, {
            comentario: comentarioForm.comentario,
            tipo: comentarioForm.tipo,
            enviar_email: comentarioForm.enviar_email,
        });

        if (response.data.success) {
            // Agregar el nuevo comentario al inicio de la lista
            comentariosReactivos.value.unshift(response.data.comentario);
            
            // Actualizar el comentario actual mostrado en el card principal
            props.candidatura.comentarios_admin = response.data.comentario.comentario;
            
            // Limpiar formulario y cerrar modal
            comentarioForm.reset();
            showComentarioForm.value = false;
            
            // Mostrar automáticamente el historial de comentarios
            mostrarHistorialComentarios.value = true;
            
            // Mostrar mensaje de éxito
            mensajeExito.value = response.data.message || 'Comentario agregado exitosamente';
            setTimeout(() => {
                mensajeExito.value = '';
            }, 5000);
            
            // Scroll suave al historial de comentarios
            setTimeout(() => {
                const historialElement = document.querySelector('#historial-comentarios');
                if (historialElement) {
                    historialElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }, 100);
        }
    } catch (error) {
        console.error('Error al agregar comentario:', error);
        // Aquí podrías mostrar un mensaje de error si tienes un sistema de notificaciones
    } finally {
        guardandoComentario.value = false;
    }
};

const cancelComentario = () => {
    comentarioForm.reset();
    showComentarioForm.value = false;
};

// Función para toggle de subsanar
const toggleSubsanar = async () => {
    toggleLoading.value = true;
    toggleMessage.value = '';
    
    try {
        const response = await axios.post(`/admin/candidaturas/${props.candidatura.id}/toggle-subsanar`);
        
        if (response.data.success) {
            subsanarEstado.value = response.data.subsanar;
            toggleMessage.value = response.data.message;
            
            // Agregar comentario al historial reactivo
            const mensaje = response.data.subsanar 
                ? 'Se habilitó la subsanación para esta candidatura' 
                : 'Se deshabilitó la subsanación para esta candidatura';
            const nuevoComentario = crearComentarioReactivo(mensaje, 'nota_admin');
            comentariosReactivos.value.unshift(nuevoComentario);
            
            setTimeout(() => {
                toggleMessage.value = '';
            }, 3000);
        }
    } catch (error) {
        console.error('Error al cambiar estado de subsanación:', error);
        toggleMessage.value = 'Error al cambiar el estado de subsanación';
        setTimeout(() => {
            toggleMessage.value = '';
        }, 3000);
    } finally {
        toggleLoading.value = false;
    }
};

// Método para abrir el formulario de comentario y hacer scroll
const abrirFormularioComentario = () => {
    showComentarioForm.value = true;
    
    // Hacer scroll al formulario después de un pequeño delay para que Vue renderice el componente
    setTimeout(() => {
        const formularioElement = document.querySelector('#formulario-comentario');
        if (formularioElement) {
            formularioElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }, 100);
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

                        <div v-if="!showApprovalForm && !showRejectionForm && !showRevertForm && !showComentarioForm" class="flex gap-2">
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
                            
                            <!-- Toggle de Subsanación -->
                            <Button
                                @click="toggleSubsanar"
                                :disabled="toggleLoading"
                                :variant="subsanarEstado ? 'default' : 'outline'"
                                :class="subsanarEstado ? 'bg-blue-600 hover:bg-blue-700' : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
                            >
                                <Loader2 v-if="toggleLoading" class="mr-2 h-4 w-4 animate-spin" />
                                <component v-else :is="subsanarEstado ? ToggleRight : ToggleLeft" class="mr-2 h-4 w-4" />
                                {{ subsanarEstado ? 'Subsanación Habilitada' : 'Habilitar Subsanación' }}
                            </Button>
                            
                            <Button
                                variant="outline"
                                @click="abrirFormularioComentario"
                                class="border-indigo-300 text-indigo-600 hover:bg-indigo-50"
                            >
                                <MessageSquare class="mr-2 h-4 w-4" />
                                Agregar Comentario
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
                        <RichTextEditor
                            v-model="approvalForm.comentarios"
                            placeholder="Comentarios adicionales para el usuario..."
                            :rows="3"
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
                        <RichTextEditor
                            v-model="rejectionForm.comentarios"
                            placeholder="Explica al usuario qué debe corregir..."
                            :rows="4"
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
                        <RichTextEditor
                            v-model="revertForm.motivo"
                            placeholder="Explica al usuario por qué su candidatura volvió a borrador..."
                            :rows="3"
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

            <!-- Mensaje de éxito -->
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
            >
                <Alert v-if="mensajeExito" class="border-green-200 bg-green-50 dark:bg-green-950/20">
                    <CheckCircle2 class="h-4 w-4 text-green-600" />
                    <AlertDescription class="text-green-800 dark:text-green-200">
                        {{ mensajeExito }}
                    </AlertDescription>
                </Alert>
            </Transition>

            <!-- Mensaje de toggle subsanación -->
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
            >
                <Alert v-if="toggleMessage" class="border-blue-200 bg-blue-50 dark:bg-blue-950/20">
                    <Wrench class="h-4 w-4 text-blue-600" />
                    <AlertDescription class="text-blue-800 dark:text-blue-200">
                        {{ toggleMessage }}
                    </AlertDescription>
                </Alert>
            </Transition>

            <!-- Comentarios de la Comisión -->
            <Card v-if="candidatura.comentarios_admin" class="border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/20">
                <CardHeader>
                    <CardTitle class="text-blue-800 dark:text-blue-200 flex items-center gap-2">
                        <MessageSquare class="h-5 w-5" />
                        Comentarios de la comisión (actual)
                        <Transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="transform opacity-0 scale-75"
                            enter-to-class="transform opacity-100 scale-100"
                        >
                            <Badge v-if="comentariosReactivos.length > 1" variant="secondary" :key="comentariosReactivos.length">
                                +{{ comentariosReactivos.length - 1 }} históricos
                            </Badge>
                        </Transition>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-blue-700 dark:text-blue-300 prose prose-sm max-w-none" v-html="candidatura.comentarios_admin"></div>
                    
                    <!-- Botón para ver historial -->
                    <div v-if="comentariosReactivos.length > 0" class="mt-4 pt-4 border-t border-blue-200 dark:border-blue-700">
                        <Button 
                            @click="mostrarHistorialComentarios = !mostrarHistorialComentarios"
                            variant="ghost"
                            size="sm"
                            class="text-blue-600 hover:text-blue-700"
                        >
                            <History class="h-4 w-4 mr-2" />
                            {{ mostrarHistorialComentarios ? 'Ocultar' : 'Ver' }} historial de comentarios
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Formulario para nuevo comentario -->
            <Card id="formulario-comentario" v-if="showComentarioForm" class="border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/20">
                <CardHeader>
                    <CardTitle class="text-indigo-800 dark:text-indigo-200 flex items-center gap-2">
                        <Plus class="h-5 w-5" />
                        Agregar Comentario
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div>
                        <Label for="nuevo_comentario">Comentario *</Label>
                        <RichTextEditor
                            v-model="comentarioForm.comentario"
                            placeholder="Escribe tu comentario aquí..."
                            :rows="4"
                        />
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <Checkbox 
                            :id="'enviar-email'" 
                            v-model:checked="comentarioForm.enviar_email"
                        />
                        <Label :for="'enviar-email'" class="cursor-pointer">
                            Notificar al usuario por correo electrónico
                        </Label>
                    </div>
                    
                    <div class="flex justify-end gap-2">
                        <Button 
                            variant="outline" 
                            @click="cancelComentario"
                            :disabled="guardandoComentario"
                        >
                            Cancelar
                        </Button>
                        <Button 
                            @click="agregarComentario" 
                            :disabled="!comentarioForm.comentario.trim() || guardandoComentario"
                            class="bg-indigo-600 hover:bg-indigo-700"
                        >
                            <Loader2 v-if="guardandoComentario" class="mr-2 h-4 w-4 animate-spin" />
                            {{ guardandoComentario ? 'Guardando...' : 'Guardar comentario' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Historial de Comentarios -->
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="transform opacity-0 translate-y-4"
                enter-to-class="transform opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="transform opacity-100 translate-y-0"
                leave-to-class="transform opacity-0 translate-y-4"
            >
                <Card id="historial-comentarios" v-if="mostrarHistorialComentarios && comentariosReactivos.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <History class="h-5 w-5" />
                        Historial de Comentarios
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <ComentariosHistorial 
                        :comentarios="comentariosReactivos"
                        :puede-agregar="true"
                        @agregar-comentario="abrirFormularioComentario"
                    />
                </CardContent>
                </Card>
            </Transition>

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