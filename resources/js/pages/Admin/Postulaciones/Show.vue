<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { Head, useForm, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { 
    ArrowLeft, 
    User, 
    FileText, 
    Calendar, 
    MapPin, 
    Users, 
    CheckCircle, 
    XCircle, 
    Clock, 
    Edit, 
    Send,
    AlertCircle,
    MessageSquare,
    History
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface Convocatoria {
    id: number;
    nombre: string;
    descripcion: string;
    cargo: string;
    periodo: string;
    formulario_postulacion: any[];
    rango_fechas: string;
    ubicacion: string;
}

interface HistorialRegistro {
    id: number;
    estado_anterior: string;
    estado_nuevo: string;
    estado_anterior_label: string;
    estado_nuevo_label: string;
    estado_anterior_color: string;
    estado_nuevo_color: string;
    resumen_cambio: string;
    comentarios: string | null;
    motivo_cambio: string | null;
    cambiado_por: { id: number; name: string; email: string } | null;
    fecha_cambio: string;
    tiempo_pasado: string;
    tipo_cambio_icon: string;
    tipo_cambio: string;
    metadatos: any;
}

interface Postulacion {
    id: number;
    usuario: Usuario;
    convocatoria: Convocatoria;
    formulario_data: Record<string, any>;
    candidatura_snapshot: any;
    tiene_candidatura_vinculada: boolean;
    estado: string;
    estado_label: string;
    estado_color: string;
    fecha_postulacion: string | null;
    comentarios_admin: string | null;
    revisado_por: { name: string; email: string } | null;
    fecha_revision: string | null;
    created_at: string;
    updated_at: string;
    historial: HistorialRegistro[];
}

interface Props {
    postulacion: Postulacion;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin/dashboard',
    },
    {
        title: 'Postulaciones',
        href: '/admin/postulaciones',
    },
    {
        title: `${props.postulacion.usuario.name}`,
        href: `/admin/postulaciones/${props.postulacion.id}`,
    },
];

const cambiarEstadoForm = useForm({
    estado: '',
    comentarios: '',
});

const mostrarFormularioCambioEstado = ref(false);
const estadoSeleccionado = ref('');

const getEstadoIcon = (estado: string) => {
    switch (estado) {
        case 'borrador':
            return Edit;
        case 'enviada':
            return Send;
        case 'en_revision':
            return Clock;
        case 'aceptada':
            return CheckCircle;
        case 'rechazada':
            return XCircle;
        default:
            return FileText;
    }
};

// Formatear datos del formulario con nombres legibles
const datosFormularioFormateados = computed(() => {
    const resultado: Record<string, any> = {};
    
    for (const [key, value] of Object.entries(props.postulacion.formulario_data)) {
        const campo = props.postulacion.convocatoria.formulario_postulacion.find(c => c.id === key);
        if (campo) {
            resultado[campo.title] = Array.isArray(value) ? value.join(', ') : value;
        }
    }
    
    return resultado;
});

// Datos del snapshot de candidatura
const datosCandidaturaSnapshot = computed(() => {
    if (!props.postulacion.candidatura_snapshot) return null;
    
    const snapshot = props.postulacion.candidatura_snapshot;
    return {
        usuario: snapshot.usuario?.name || 'N/A',
        version: snapshot.candidatura?.version_original || 'N/A',
        fecha_aprobacion: snapshot.candidatura?.fecha_aprobacion || 'N/A',
        aprobado_por: snapshot.candidatura?.aprobado_por?.name || 'N/A',
        formulario_data: snapshot.candidatura?.formulario_data || {},
        configuracion_campos: snapshot.configuracion_en_momento?.campos || [],
    };
});

// Formatear datos de candidatura con nombres legibles
const datosCandidaturaFormateados = computed(() => {
    if (!datosCandidaturaSnapshot.value) return {};
    
    const resultado: Record<string, any> = {};
    const { formulario_data, configuracion_campos } = datosCandidaturaSnapshot.value;
    
    for (const [key, value] of Object.entries(formulario_data)) {
        const campo = configuracion_campos.find((c: any) => c.id === key);
        if (campo) {
            resultado[campo.title] = Array.isArray(value) ? value.join(', ') : value;
        }
    }
    
    return resultado;
});

const iniciarCambioEstado = (nuevoEstado: string) => {
    estadoSeleccionado.value = nuevoEstado;
    cambiarEstadoForm.estado = nuevoEstado;
    cambiarEstadoForm.comentarios = '';
    mostrarFormularioCambioEstado.value = true;
};

const confirmarCambioEstado = () => {
    cambiarEstadoForm.post(`/admin/postulaciones/${props.postulacion.id}/cambiar-estado`, {
        onSuccess: () => {
            mostrarFormularioCambioEstado.value = false;
        },
    });
};

const cancelarCambioEstado = () => {
    mostrarFormularioCambioEstado.value = false;
    estadoSeleccionado.value = '';
    cambiarEstadoForm.reset();
};

// Lógica administrativa más flexible
const puedeMarcarEnRevision = computed(() => ['borrador', 'enviada', 'rechazada'].includes(props.postulacion.estado));
const puedeAceptar = computed(() => ['enviada', 'en_revision'].includes(props.postulacion.estado));
const puedeRechazar = computed(() => ['enviada', 'en_revision'].includes(props.postulacion.estado));
const puedeVolverABorrador = computed(() => ['enviada', 'en_revision', 'aceptada', 'rechazada'].includes(props.postulacion.estado));
const puedeMarcarEnviada = computed(() => props.postulacion.estado === 'borrador');

const getEstadoLabel = (estado: string) => {
    const labels: Record<string, string> = {
        'enviada': 'Enviada',
        'en_revision': 'En Revisión',
        'aceptada': 'Aceptada',
        'rechazada': 'Rechazada',
        'borrador': 'Borrador'
    };
    return labels[estado] || estado;
};

const requiereComentarios = computed(() => ['rechazada'].includes(estadoSeleccionado.value));

// Función para obtener componente de icono por nombre
const getIconComponent = (iconName: string) => {
    const iconMap: Record<string, any> = {
        CheckCircle,
        XCircle,
        Clock,
        Send,
        Edit,
        FileText,
    };
    return iconMap[iconName] || FileText;
};
</script>

<template>
    <Head :title="`Postulación de ${postulacion.usuario.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <Button variant="ghost" size="sm" @click="router.get('/admin/postulaciones')">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Volver
                    </Button>
                </div>
                <h1 class="text-3xl font-bold tracking-tight">
                    Postulación de {{ postulacion.usuario.name }}
                </h1>
                <p class="text-muted-foreground">{{ postulacion.convocatoria.nombre }}</p>
            </div>
            <Badge :class="postulacion.estado_color" class="text-base px-3 py-1">
                <component :is="getEstadoIcon(postulacion.estado)" class="mr-2 h-4 w-4" />
                {{ postulacion.estado_label }}
            </Badge>
        </div>

        <!-- Información general -->
        <div class="grid gap-6 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <User class="h-5 w-5" />
                        Información del Usuario
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div>
                        <Label class="text-muted-foreground">Nombre:</Label>
                        <p class="font-medium">{{ postulacion.usuario.name }}</p>
                    </div>
                    <div>
                        <Label class="text-muted-foreground">Email:</Label>
                        <p class="font-medium">{{ postulacion.usuario.email }}</p>
                    </div>
                    <div>
                        <Label class="text-muted-foreground">Fecha postulación:</Label>
                        <p class="font-medium">{{ postulacion.fecha_postulacion || 'Borrador' }}</p>
                    </div>
                    <div>
                        <Label class="text-muted-foreground">Fecha creación:</Label>
                        <p class="font-medium">{{ postulacion.created_at }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="h-5 w-5" />
                        Convocatoria
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div>
                        <Label class="text-muted-foreground">Nombre:</Label>
                        <p class="font-medium">{{ postulacion.convocatoria.nombre }}</p>
                    </div>
                    <div>
                        <Label class="text-muted-foreground">Cargo:</Label>
                        <p class="font-medium">{{ postulacion.convocatoria.cargo }}</p>
                    </div>
                    <div>
                        <Label class="text-muted-foreground">Periodo:</Label>
                        <p class="font-medium">{{ postulacion.convocatoria.periodo }}</p>
                    </div>
                    <div>
                        <Label class="text-muted-foreground">Ubicación:</Label>
                        <p class="font-medium">{{ postulacion.convocatoria.ubicacion }}</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Tabs para formulario y candidatura -->
        <Tabs default-value="formulario" class="space-y-4">
            <TabsList class="grid w-full grid-cols-2">
                <TabsTrigger value="formulario">
                    Respuestas del Formulario
                </TabsTrigger>
                <TabsTrigger value="candidatura" :disabled="!postulacion.tiene_candidatura_vinculada">
                    Perfil de Candidatura
                    <Badge v-if="postulacion.tiene_candidatura_vinculada" variant="secondary" class="ml-2">
                        Vinculado
                    </Badge>
                </TabsTrigger>
            </TabsList>

            <!-- Tab: Respuestas del formulario -->
            <TabsContent value="formulario" class="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Respuestas del Formulario de Postulación</CardTitle>
                        <CardDescription>
                            Respuestas proporcionadas por el usuario en el formulario de postulación.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="Object.keys(datosFormularioFormateados).length === 0" class="text-center py-8 text-muted-foreground">
                            <FileText class="mx-auto h-12 w-12 mb-2" />
                            <p>No hay respuestas del formulario disponibles.</p>
                        </div>
                        <div v-else class="space-y-4">
                            <div v-for="(valor, campo) in datosFormularioFormateados" :key="campo" class="border rounded-lg p-4">
                                <Label class="text-muted-foreground">{{ campo }}:</Label>
                                <p class="font-medium mt-1" v-if="typeof valor === 'string' && valor.length > 100">
                                    {{ valor }}
                                </p>
                                <p class="font-medium mt-1" v-else>
                                    {{ valor || 'Sin respuesta' }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- Tab: Perfil de candidatura -->
            <TabsContent value="candidatura" class="space-y-4">
                <Card v-if="!postulacion.tiene_candidatura_vinculada">
                    <CardContent class="text-center py-8">
                        <User class="mx-auto h-12 w-12 text-muted-foreground mb-2" />
                        <p class="text-muted-foreground">No hay perfil de candidatura vinculado a esta postulación.</p>
                    </CardContent>
                </Card>

                <div v-else class="space-y-4">
                    <!-- Información del perfil -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información del Perfil de Candidatura</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label class="text-muted-foreground">Usuario:</Label>
                                    <p class="font-medium">{{ datosCandidaturaSnapshot!.usuario }}</p>
                                </div>
                                <div>
                                    <Label class="text-muted-foreground">Versión:</Label>
                                    <p class="font-medium">v{{ datosCandidaturaSnapshot!.version }}</p>
                                </div>
                                <div>
                                    <Label class="text-muted-foreground">Fecha aprobación:</Label>
                                    <p class="font-medium">{{ datosCandidaturaSnapshot!.fecha_aprobacion }}</p>
                                </div>
                                <div>
                                    <Label class="text-muted-foreground">Aprobado por:</Label>
                                    <p class="font-medium">{{ datosCandidaturaSnapshot!.aprobado_por }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Datos del perfil de candidatura -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Datos del Perfil de Candidatura</CardTitle>
                            <CardDescription>
                                Información capturada del perfil de candidatura en el momento de la postulación.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div v-for="(valor, campo) in datosCandidaturaFormateados" :key="campo" class="border rounded-lg p-4">
                                    <Label class="text-muted-foreground">{{ campo }}:</Label>
                                    <p class="font-medium mt-1">{{ valor || 'Sin información' }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </TabsContent>
        </Tabs>

        <!-- Acciones de cambio de estado -->
        <Card>
            <CardHeader>
                <CardTitle>Gestión de Estado</CardTitle>
                <CardDescription>
                    Cambia el estado de la postulación según el flujo de revisión.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="!mostrarFormularioCambioEstado" class="flex flex-wrap gap-2">
                    <Button
                        v-if="puedeMarcarEnviada"
                        variant="outline"
                        @click="iniciarCambioEstado('enviada')"
                    >
                        <Send class="mr-2 h-4 w-4" />
                        Marcar como Enviada
                    </Button>
                    
                    <Button
                        v-if="puedeMarcarEnRevision"
                        variant="outline"
                        @click="iniciarCambioEstado('en_revision')"
                    >
                        <Clock class="mr-2 h-4 w-4" />
                        Marcar en Revisión
                    </Button>
                    
                    <Button
                        v-if="puedeAceptar"
                        @click="iniciarCambioEstado('aceptada')"
                    >
                        <CheckCircle class="mr-2 h-4 w-4" />
                        Aceptar
                    </Button>
                    
                    <Button
                        v-if="puedeRechazar"
                        variant="destructive"
                        @click="iniciarCambioEstado('rechazada')"
                    >
                        <XCircle class="mr-2 h-4 w-4" />
                        Rechazar
                    </Button>
                    
                    <Button
                        v-if="puedeVolverABorrador"
                        variant="outline"
                        @click="iniciarCambioEstado('borrador')"
                    >
                        <Edit class="mr-2 h-4 w-4" />
                        Volver a Borrador
                    </Button>
                </div>

                <!-- Formulario de cambio de estado -->
                <div v-else class="space-y-4">
                    <div class="flex items-center gap-2 p-3 bg-muted rounded-lg">
                        <AlertCircle class="h-5 w-5 text-amber-600" />
                        <p class="text-sm">
                            ¿Estás seguro de que quieres cambiar el estado a 
                            <strong>{{ getEstadoLabel(estadoSeleccionado) }}</strong>?
                        </p>
                    </div>

                    <div>
                        <Label for="comentarios">
                            Comentarios {{ requiereComentarios ? '(requerido)' : '(opcional)' }}
                        </Label>
                        <Textarea
                            id="comentarios"
                            v-model="cambiarEstadoForm.comentarios"
                            :placeholder="requiereComentarios ? 'Explica el motivo del rechazo...' : 'Comentarios adicionales...'"
                            rows="3"
                            :class="[cambiarEstadoForm.errors.comentarios ? 'border-red-300' : '']"
                        />
                        <p v-if="cambiarEstadoForm.errors.comentarios" class="text-sm text-red-600 mt-1">
                            {{ cambiarEstadoForm.errors.comentarios }}
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <Button
                            @click="confirmarCambioEstado"
                            :disabled="cambiarEstadoForm.processing"
                        >
                            Confirmar Cambio
                        </Button>
                        <Button
                            variant="outline"
                            @click="cancelarCambioEstado"
                            :disabled="cambiarEstadoForm.processing"
                        >
                            Cancelar
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Información de revisión -->
        <Card v-if="postulacion.revisado_por || postulacion.comentarios_admin">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <MessageSquare class="h-5 w-5" />
                    Información de Revisión
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div v-if="postulacion.revisado_por">
                    <Label class="text-muted-foreground">Revisado por:</Label>
                    <p class="font-medium">{{ postulacion.revisado_por.name }}</p>
                    <p class="text-sm text-muted-foreground">{{ postulacion.fecha_revision }}</p>
                </div>
                <div v-if="postulacion.comentarios_admin">
                    <Label class="text-muted-foreground">Comentarios:</Label>
                    <p class="font-medium">{{ postulacion.comentarios_admin }}</p>
                </div>
            </CardContent>
        </Card>

        <!-- Historial de cambios -->
        <Card v-if="postulacion.historial && postulacion.historial.length > 0">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <History class="h-5 w-5" />
                    Historial de Cambios
                </CardTitle>
                <CardDescription>
                    Cronología completa de todos los cambios de estado de esta postulación.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="space-y-4">
                    <div 
                        v-for="(registro, index) in postulacion.historial" 
                        :key="registro.id"
                        class="relative flex items-start gap-4 pb-4"
                        :class="{ 'border-b border-border': index < postulacion.historial.length - 1 }"
                    >
                        <!-- Línea vertical del timeline (excepto último elemento) -->
                        <div 
                            v-if="index < postulacion.historial.length - 1"
                            class="absolute left-6 top-12 w-px h-full bg-border"
                        />
                        
                        <!-- Icono del cambio -->
                        <div 
                            class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center"
                            :class="[
                                registro.tipo_cambio === 'positivo' ? 'bg-green-100 text-green-600' :
                                registro.tipo_cambio === 'negativo' ? 'bg-red-100 text-red-600' :
                                registro.tipo_cambio === 'progreso' ? 'bg-blue-100 text-blue-600' :
                                'bg-gray-100 text-gray-600'
                            ]"
                        >
                            <component 
                                :is="getIconComponent(registro.tipo_cambio_icon)" 
                                class="w-5 h-5" 
                            />
                        </div>
                        
                        <!-- Contenido del cambio -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <Badge :class="registro.estado_anterior_color" class="text-xs">
                                    {{ registro.estado_anterior_label }}
                                </Badge>
                                <span class="text-muted-foreground text-sm">→</span>
                                <Badge :class="registro.estado_nuevo_color" class="text-xs">
                                    {{ registro.estado_nuevo_label }}
                                </Badge>
                            </div>
                            
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-sm font-medium">
                                    {{ registro.motivo_cambio || 'Cambio de estado' }}
                                </h4>
                                <span class="text-xs text-muted-foreground">
                                    {{ registro.tiempo_pasado }}
                                </span>
                            </div>
                            
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 text-sm">
                                    <User class="w-4 h-4 text-muted-foreground" />
                                    <span class="text-muted-foreground">Por:</span>
                                    <span class="font-medium">
                                        {{ registro.cambiado_por?.name || 'Usuario eliminado' }}
                                    </span>
                                    <span class="text-muted-foreground">
                                        • {{ registro.fecha_cambio }}
                                    </span>
                                </div>
                                
                                <div v-if="registro.comentarios" class="text-sm">
                                    <Label class="text-muted-foreground">Comentarios:</Label>
                                    <p class="mt-1 p-2 bg-muted rounded text-sm">
                                        {{ registro.comentarios }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
        </div>
        </div>
    </AppLayout>
</template>