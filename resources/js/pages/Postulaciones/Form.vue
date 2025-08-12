<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import DynamicFormRenderer from '@/components/forms/DynamicFormRenderer.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { 
    Calendar, 
    Clock, 
    MapPin, 
    Users, 
    CheckCircle, 
    XCircle, 
    AlertCircle, 
    Save,
    Send,
    ArrowLeft,
    FileText,
    User
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Convocatoria {
    id: number;
    nombre: string;
    descripcion: string;
    cargo: string;
    periodo: string;
    formulario_postulacion: any[];
    estado_temporal: string;
    estado_temporal_label: string;
    rango_fechas: string;
    ubicacion: string;
    tiene_perfil_candidatura: boolean;
}

interface Postulacion {
    id: number;
    formulario_data: Record<string, any>;
    candidatura_snapshot: any;
    estado: string;
    estado_label: string;
    comentarios_admin: string | null;
    puede_editar: boolean;
}

interface CandidaturaAprobada {
    id: number;
    formulario_data: Record<string, any>;
    version: number;
    fecha_aprobacion: string;
}

interface Props {
    convocatoria: Convocatoria;
    postulacion: Postulacion | null;
    candidatura_aprobada: CandidaturaAprobada | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Postulaciones',
        href: '/postulaciones',
    },
    {
        title: props.convocatoria.nombre,
        href: `/convocatorias/${props.convocatoria.id}`,
    },
];

const formRenderer = ref<InstanceType<typeof DynamicFormRenderer>>();

// Preparar candidaturas aprobadas para el selector
const candidaturasAprobadas = computed(() => {
    if (!props.candidatura_aprobada) return [];
    
    return [{
        id: props.candidatura_aprobada.id,
        version: props.candidatura_aprobada.version,
        fecha_aprobacion: props.candidatura_aprobada.fecha_aprobacion,
        resumen: `Perfil v${props.candidatura_aprobada.version} - Aprobado ${props.candidatura_aprobada.fecha_aprobacion}`,
    }];
});

// Formulario
const form = useForm({
    formulario_data: props.postulacion?.formulario_data || {},
    candidatura_id: props.postulacion?.candidatura_snapshot ? props.candidatura_aprobada?.id : null,
    enviar: false,
});

const candidaturaSeleccionada = ref<number | null>(
    props.postulacion?.candidatura_snapshot ? props.candidatura_aprobada?.id || null : null
);

const esEdicion = computed(() => !!props.postulacion);
const puedeEditar = computed(() => {
    // Si no hay postulación (es nueva), se puede editar
    if (!props.postulacion) return true;
    // Si ya existe, verificar si puede editar
    return props.postulacion.puede_editar;
});
const tieneComentariosAdmin = computed(() => !!props.postulacion?.comentarios_admin);

const getEstadoTemporalIcon = (estadoTemporal: string) => {
    switch (estadoTemporal) {
        case 'abierta':
            return CheckCircle;
        case 'futura':
            return Calendar;
        case 'cerrada':
            return XCircle;
        default:
            return AlertCircle;
    }
};

const guardarBorrador = () => {
    form.enviar = false;
    form.candidatura_id = candidaturaSeleccionada.value;
    form.post(`/convocatorias/${props.convocatoria.id}/postular`, {
        preserveScroll: true,
    });
};

// Estado para mensajes de error
const errorMessage = ref<string | null>(null);

const enviarPostulacion = () => {
    // Limpiar mensaje de error previo
    errorMessage.value = null;
    
    // Validar que el formulario esté completo
    if (!formRenderer.value?.formularioCompleto) {
        // Obtener información sobre campos incompletos
        const camposIncompletos = formRenderer.value?.camposIncompletos || [];
        const perfilRequerido = formRenderer.value?.perfilCandidaturaRequerido;
        
        // Mostrar mensaje de error específico
        if (camposIncompletos.length > 0) {
            errorMessage.value = `Formulario incompleto: Por favor completa todos los campos requeridos (${camposIncompletos.length} campos faltantes).`;
        } else if (perfilRequerido && !candidaturaSeleccionada.value) {
            errorMessage.value = 'Debes seleccionar tu perfil de candidatura aprobado para continuar.';
        } else {
            errorMessage.value = 'Por favor completa todos los campos requeridos del formulario.';
        }
        
        // Hacer scroll al primer campo incompleto o al top del formulario
        const tabsElement = document.querySelector('[role="tabpanel"]');
        if (tabsElement) {
            tabsElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        return;
    }
    
    form.enviar = true;
    form.candidatura_id = candidaturaSeleccionada.value;
    form.post(`/convocatorias/${props.convocatoria.id}/postular`, {
        preserveScroll: true,
        onError: (errors) => {
            // Si hay errores del servidor, mostrarlos
            const firstError = Object.values(errors)[0];
            if (firstError) {
                errorMessage.value = Array.isArray(firstError) ? firstError[0] : firstError;
            } else {
                errorMessage.value = 'Error al enviar la postulación. Por favor revisa los datos e intenta nuevamente.';
            }
        }
    });
};

const formatearDatosFormulario = (data: Record<string, any>, campos: any[]) => {
    const resultado: Record<string, any> = {};
    
    for (const [key, value] of Object.entries(data)) {
        const campo = campos.find(c => c.id === key);
        if (campo) {
            resultado[campo.title] = Array.isArray(value) ? value.join(', ') : value;
        }
    }
    
    return resultado;
};

const datosFormularioFormateados = computed(() => {
    if (!props.postulacion?.formulario_data) return {};
    return formatearDatosFormulario(props.postulacion.formulario_data, props.convocatoria.formulario_postulacion);
});

const datosCandidaturaSnapshot = computed(() => {
    if (!props.postulacion?.candidatura_snapshot) return null;
    
    const snapshot = props.postulacion.candidatura_snapshot;
    return {
        usuario: snapshot.usuario?.name || 'N/A',
        version: snapshot.candidatura?.version_original || 'N/A',
        fecha_aprobacion: snapshot.candidatura?.fecha_aprobacion || 'N/A',
        aprobado_por: snapshot.candidatura?.aprobado_por?.name || 'N/A',
    };
});
</script>

<template>
    <Head :title="`${esEdicion ? 'Editar' : 'Nueva'} Postulación - ${convocatoria.nombre}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <Button variant="ghost" size="sm" @click="router.get('/postulaciones')">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Volver
                    </Button>
                </div>
                <h1 class="text-3xl font-bold tracking-tight">
                    {{ esEdicion ? 'Editar Postulación' : 'Nueva Postulación' }}
                </h1>
                <p class="text-muted-foreground">{{ convocatoria.nombre }}</p>
            </div>
            <Badge :class="convocatoria.estado_temporal === 'abierta' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'">
                <component :is="getEstadoTemporalIcon(convocatoria.estado_temporal)" class="mr-1 h-3 w-3" />
                {{ convocatoria.estado_temporal_label }}
            </Badge>
        </div>

        <!-- Estado actual de la postulación (si existe) -->
        <Card v-if="postulacion" :class="{
            'border-yellow-500': postulacion.estado === 'borrador',
            'border-blue-500': postulacion.estado === 'enviada',
            'border-purple-500': postulacion.estado === 'en_revision',
            'border-green-500': postulacion.estado === 'aceptada',
            'border-red-500': postulacion.estado === 'rechazada'
        }">
            <CardContent class="pt-6">
                <div class="flex items-start justify-between">
                    <div class="flex items-start gap-3">
                        <div :class="{
                            'bg-yellow-100 text-yellow-600': postulacion.estado === 'borrador',
                            'bg-blue-100 text-blue-600': postulacion.estado === 'enviada',
                            'bg-purple-100 text-purple-600': postulacion.estado === 'en_revision',
                            'bg-green-100 text-green-600': postulacion.estado === 'aceptada',
                            'bg-red-100 text-red-600': postulacion.estado === 'rechazada'
                        }" class="rounded-full p-2">
                            <component :is="postulacion.estado === 'borrador' ? Save :
                                        postulacion.estado === 'enviada' ? Send :
                                        postulacion.estado === 'en_revision' ? Clock :
                                        postulacion.estado === 'aceptada' ? CheckCircle :
                                        XCircle" 
                                class="h-5 w-5" 
                            />
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h3 class="font-semibold">Estado actual:</h3>
                                <Badge :class="postulacion.estado === 'borrador' ? 'bg-yellow-100 text-yellow-800' :
                                            postulacion.estado === 'enviada' ? 'bg-blue-100 text-blue-800' :
                                            postulacion.estado === 'en_revision' ? 'bg-purple-100 text-purple-800' :
                                            postulacion.estado === 'aceptada' ? 'bg-green-100 text-green-800' :
                                            'bg-red-100 text-red-800'">
                                    {{ postulacion.estado_label }}
                                </Badge>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                <span v-if="postulacion.estado === 'borrador' && tieneComentariosAdmin">
                                    Tu postulación fue devuelta a borrador. Revisa los comentarios del administrador abajo y realiza los ajustes necesarios.
                                </span>
                                <span v-else-if="postulacion.estado === 'borrador'">
                                    Puedes editar y enviar tu postulación cuando esté completa.
                                </span>
                                <span v-else-if="postulacion.estado === 'enviada'">
                                    Tu postulación ha sido enviada y está pendiente de revisión.
                                </span>
                                <span v-else-if="postulacion.estado === 'en_revision'">
                                    Tu postulación está siendo revisada por el administrador.
                                </span>
                                <span v-else-if="postulacion.estado === 'aceptada'">
                                    ¡Felicidades! Tu postulación ha sido aceptada.
                                </span>
                                <span v-else-if="postulacion.estado === 'rechazada'">
                                    Tu postulación fue rechazada. Revisa los comentarios del administrador abajo y realiza los ajustes necesarios.
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Comentarios de la comisión si existen - SIEMPRE VISIBLES -->
                <div v-if="tieneComentariosAdmin" 
                     :class="{
                         'bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800': postulacion.estado === 'rechazada',
                         'bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800': postulacion.estado === 'borrador',
                         'bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800': postulacion.estado === 'enviada' || postulacion.estado === 'en_revision',
                         'bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800': postulacion.estado === 'aceptada'
                     }"
                     class="mt-4 p-4 rounded-lg">
                    <div class="flex items-start gap-2">
                        <AlertCircle :class="{
                            'text-red-600 dark:text-red-400': postulacion.estado === 'rechazada',
                            'text-amber-600 dark:text-amber-400': postulacion.estado === 'borrador',
                            'text-blue-600 dark:text-blue-400': postulacion.estado === 'enviada' || postulacion.estado === 'en_revision',
                            'text-green-600 dark:text-green-400': postulacion.estado === 'aceptada'
                        }" class="h-5 w-5 mt-0.5 flex-shrink-0" />
                        <div class="flex-1">
                            <p class="font-semibold mb-1" :class="{
                                'text-red-900 dark:text-red-100': postulacion.estado === 'rechazada',
                                'text-amber-900 dark:text-amber-100': postulacion.estado === 'borrador',
                                'text-blue-900 dark:text-blue-100': postulacion.estado === 'enviada' || postulacion.estado === 'en_revision',
                                'text-green-900 dark:text-green-100': postulacion.estado === 'aceptada'
                            }">
                                Comentarios de la comisión:
                            </p>
                            <p class="text-sm whitespace-pre-wrap" :class="{
                                'text-red-800 dark:text-red-200': postulacion.estado === 'rechazada',
                                'text-amber-800 dark:text-amber-200': postulacion.estado === 'borrador',
                                'text-blue-800 dark:text-blue-200': postulacion.estado === 'enviada' || postulacion.estado === 'en_revision',
                                'text-green-800 dark:text-green-200': postulacion.estado === 'aceptada'
                            }">{{ postulacion.comentarios_admin }}</p>
                            <p class="text-xs mt-2 opacity-75">
                                <span v-if="postulacion.estado === 'rechazada' || (postulacion.estado === 'borrador' && tieneComentariosAdmin)">
                                    Por favor, atiende estos comentarios antes de reenviar tu postulación.
                                </span>
                                <span v-else-if="postulacion.estado === 'aceptada'">
                                    Tu postulación fue aceptada con estos comentarios.
                                </span>
                                <span v-else>
                                    Estos comentarios fueron agregados durante la revisión.
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Información de la convocatoria -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <FileText class="h-5 w-5" />
                    Información de la Convocatoria
                </CardTitle>
                <CardDescription>{{ convocatoria.descripcion }}</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-muted-foreground">Cargo:</span>
                        <p class="font-medium">{{ convocatoria.cargo }}</p>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Periodo:</span>
                        <p class="font-medium">{{ convocatoria.periodo }}</p>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Fechas:</span>
                        <p class="font-medium text-xs">{{ convocatoria.rango_fechas }}</p>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Ubicación:</span>
                        <p class="font-medium">{{ convocatoria.ubicacion }}</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Tabs para formulario y resumen -->
        <Tabs default-value="formulario" class="space-y-4">
            <TabsList class="grid w-full grid-cols-2">
                <TabsTrigger value="formulario">
                    Formulario de Postulación
                </TabsTrigger>
                <TabsTrigger value="resumen" :disabled="!esEdicion">
                    Resumen Actual
                </TabsTrigger>
            </TabsList>

            <!-- Tab: Formulario -->
            <TabsContent value="formulario" class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Completa tu Postulación</CardTitle>
                        <CardDescription>
                            Completa todos los campos requeridos para enviar tu postulación.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DynamicFormRenderer
                            ref="formRenderer"
                            :fields="convocatoria.formulario_postulacion"
                            v-model="form.formulario_data"
                            v-model:candidatura-seleccionada="candidaturaSeleccionada"
                            :candidaturas-aprobadas="candidaturasAprobadas"
                            :errors="form.errors"
                            :disabled="!puedeEditar"
                        />
                    </CardContent>
                </Card>

                <!-- Mensaje de error si existe -->
                <div v-if="errorMessage" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-start gap-2">
                        <AlertCircle class="h-5 w-5 text-red-600 mt-0.5" />
                        <p class="text-sm text-red-800">{{ errorMessage }}</p>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="flex justify-end gap-3" v-if="puedeEditar">
                    <Button
                        type="button"
                        variant="outline"
                        @click="guardarBorrador"
                        :disabled="form.processing"
                    >
                        <Save class="mr-2 h-4 w-4" />
                        Guardar Borrador
                    </Button>
                    <Button
                        type="button"
                        @click="enviarPostulacion"
                        :disabled="form.processing"
                    >
                        <Send class="mr-2 h-4 w-4" />
                        Enviar Postulación
                    </Button>
                </div>
            </TabsContent>

            <!-- Tab: Resumen (solo en edición) -->
            <TabsContent value="resumen" class="space-y-4" v-if="esEdicion">
                <Card>
                    <CardHeader>
                        <CardTitle>Estado de la Postulación</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Estado:</span>
                            <Badge :class="postulacion!.estado === 'aceptada' ? 'bg-green-100 text-green-800' : 
                                         postulacion!.estado === 'rechazada' ? 'bg-red-100 text-red-800' :
                                         'bg-blue-100 text-blue-800'">
                                {{ postulacion!.estado_label }}
                            </Badge>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Perfil vinculado:</span>
                            <span>{{ datosCandidaturaSnapshot ? 'Sí' : 'No' }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Datos del formulario -->
                <Card>
                    <CardHeader>
                        <CardTitle>Respuestas del Formulario</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-for="(valor, campo) in datosFormularioFormateados" :key="campo" class="flex justify-between">
                            <span class="text-muted-foreground">{{ campo }}:</span>
                            <span class="font-medium text-right max-w-xs">{{ valor }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Perfil de candidatura vinculado -->
                <Card v-if="datosCandidaturaSnapshot">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <User class="h-5 w-5" />
                            Perfil de Candidatura Vinculado
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-muted-foreground">Usuario:</span>
                                <p class="font-medium">{{ datosCandidaturaSnapshot.usuario }}</p>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Versión:</span>
                                <p class="font-medium">v{{ datosCandidaturaSnapshot.version }}</p>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Fecha aprobación:</span>
                                <p class="font-medium">{{ datosCandidaturaSnapshot.fecha_aprobacion }}</p>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Aprobado por:</span>
                                <p class="font-medium">{{ datosCandidaturaSnapshot.aprobado_por }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>
        </Tabs>
        </div>
        </div>
    </AppLayout>
</template>