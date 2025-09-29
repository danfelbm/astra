<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import {
    ArrowLeft,
    Calendar,
    User,
    FileText,
    Download,
    Eye,
    Image,
    Video,
    Music,
    File,
    CheckCircle,
    Clock,
    XCircle,
    AlertTriangle
} from 'lucide-vue-next';
import type { Contrato } from '@modules/Proyectos/Resources/js/types/contratos';
import type { Evidencia } from '@modules/Proyectos/Resources/js/types/evidencias';

// Props
const props = defineProps<{
    contrato: Contrato & {
        proyecto?: {
            id: number;
            nombre: string;
        };
    };
    evidencia: Evidencia & {
        usuario: {
            id: number;
            name: string;
            email: string;
        };
        obligacion: {
            id: number;
            nombre: string;
            descripcion?: string;
        };
        entregables?: Array<{
            id: number;
            nombre: string;
            hito: {
                id: number;
                nombre: string;
            };
        }>;
        revisor?: {
            id: number;
            name: string;
            email: string;
        };
    };
}>();

// Breadcrumbs para navegación
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Contratos', href: '/admin/contratos' },
    { title: props.contrato.nombre, href: `/admin/contratos/${props.contrato.id}` },
    { title: 'Evidencia', href: '#' }
];

// Computed
const tipoIcon = computed(() => {
    const icons = {
        'imagen': Image,
        'video': Video,
        'audio': Music,
        'documento': File
    };
    return icons[props.evidencia.tipo_evidencia] || File;
});

const estadoConfig = computed(() => {
    const configs = {
        'pendiente': { color: 'secondary', icon: Clock, bgClass: 'bg-yellow-50', textClass: 'text-yellow-800' },
        'aprobada': { color: 'success', icon: CheckCircle, bgClass: 'bg-green-50', textClass: 'text-green-800' },
        'rechazada': { color: 'destructive', icon: XCircle, bgClass: 'bg-red-50', textClass: 'text-red-800' }
    };
    return configs[props.evidencia.estado] || { color: 'default', icon: Clock, bgClass: 'bg-gray-50', textClass: 'text-gray-800' };
});

const formatDate = (date: string) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatDateShort = (date: string) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const descargarArchivo = (url?: string) => {
    const archivoUrl = url || props.evidencia.archivo_url;
    if (archivoUrl) {
        window.open(archivoUrl, '_blank');
    }
};

const descargarTodosArchivos = () => {
    if (props.evidencia.archivos_urls?.length > 0) {
        props.evidencia.archivos_urls.forEach((url, index) => {
            setTimeout(() => {
                window.open(url, '_blank');
            }, index * 500); // Retraso de 500ms entre descargas
        });
    } else if (props.evidencia.archivo_url) {
        descargarArchivo();
    }
};
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex justify-between items-start">
                <div>
                    <Link
                        :href="`/admin/contratos/${contrato.id}`"
                        class="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-2"
                    >
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Volver al Contrato
                    </Link>
                    <h2 class="text-3xl font-bold tracking-tight">Evidencia</h2>
                    <div class="flex items-center gap-4 mt-2">
                        <Badge :variant="estadoConfig.color">
                            <component :is="estadoConfig.icon" class="w-3 h-3 mr-1" />
                            {{ evidencia.estado_label }}
                        </Badge>
                        <Badge variant="outline">
                            <component :is="tipoIcon" class="w-3 h-3 mr-1" />
                            {{ evidencia.tipo_evidencia_label }}
                        </Badge>
                        <Badge variant="secondary">
                            {{ evidencia.tiene_multiples_archivos ? `${evidencia.total_archivos} archivos` : '1 archivo' }}
                        </Badge>
                        <span class="text-muted-foreground">
                            Subida {{ formatDateShort(evidencia.created_at) }}
                        </span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button
                        v-if="evidencia.tiene_multiples_archivos"
                        @click="descargarTodosArchivos"
                        variant="outline"
                    >
                        <Download class="w-4 h-4 mr-2" />
                        Descargar Todos ({{ evidencia.total_archivos }})
                    </Button>
                    <Button
                        v-else-if="evidencia.archivo_url"
                        @click="descargarArchivo"
                        variant="outline"
                    >
                        <Download class="w-4 h-4 mr-2" />
                        Descargar
                    </Button>
                </div>
            </div>

            <!-- Alerta de estado -->
            <Alert v-if="evidencia.estado === 'rechazada'" class="border-red-200 bg-red-50">
                <XCircle class="h-4 w-4 text-red-600" />
                <AlertDescription class="text-red-800">
                    Esta evidencia ha sido rechazada.
                    <span v-if="evidencia.observaciones_admin">
                        Motivo: {{ evidencia.observaciones_admin }}
                    </span>
                </AlertDescription>
            </Alert>

            <Alert v-if="evidencia.estado === 'aprobada'" class="border-green-200 bg-green-50">
                <CheckCircle class="h-4 w-4 text-green-600" />
                <AlertDescription class="text-green-800">
                    Esta evidencia ha sido aprobada exitosamente.
                </AlertDescription>
            </Alert>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Columna principal: Vista del archivo -->
                <div class="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <component :is="tipoIcon" class="w-5 h-5" />
                                Archivo de Evidencia
                            </CardTitle>
                            <CardDescription>
                                {{ evidencia.archivo_nombre || 'Archivo subido' }}
                                <span v-if="evidencia.archivo_size_formatted" class="ml-2">
                                    ({{ evidencia.archivo_size_formatted }})
                                </span>
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <!-- Múltiples archivos -->
                                <div v-if="evidencia.archivos_info?.length > 0" class="space-y-4">
                                    <div
                                        v-for="(archivo, index) in evidencia.archivos_info"
                                        :key="archivo.indice"
                                        class="bg-muted/30 rounded-lg p-4 relative group"
                                    >
                                        <!-- Botón de descarga individual -->
                                        <Button
                                            @click="descargarArchivo(archivo.url)"
                                            variant="outline"
                                            size="sm"
                                            class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                        >
                                            <Download class="w-4 h-4" />
                                        </Button>

                                        <!-- Imagen -->
                                        <div v-if="evidencia.es_imagen" class="text-center">
                                            <img
                                                :src="archivo.url"
                                                :alt="archivo.nombre"
                                                class="max-w-full max-h-64 mx-auto rounded-lg shadow-sm"
                                            />
                                            <p class="text-sm text-muted-foreground mt-2">{{ archivo.nombre }}</p>
                                        </div>

                                        <!-- Video -->
                                        <div v-else-if="evidencia.es_video" class="text-center">
                                            <video
                                                :src="archivo.url"
                                                controls
                                                class="max-w-full max-h-64 mx-auto rounded-lg shadow-sm"
                                            >
                                                Tu navegador no soporta la reproducción de video.
                                            </video>
                                            <p class="text-sm text-muted-foreground mt-2">{{ archivo.nombre }}</p>
                                        </div>

                                        <!-- Audio -->
                                        <div v-else-if="evidencia.es_audio" class="flex flex-col items-center gap-4">
                                            <div class="flex items-center gap-3">
                                                <Music class="w-12 h-12 text-muted-foreground" />
                                                <div class="text-left">
                                                    <p class="font-medium">{{ archivo.nombre }}</p>
                                                    <p class="text-sm text-muted-foreground">Archivo de audio</p>
                                                </div>
                                            </div>
                                            <audio
                                                :src="archivo.url"
                                                controls
                                                class="w-full max-w-md"
                                            >
                                                Tu navegador no soporta la reproducción de audio.
                                            </audio>
                                        </div>

                                        <!-- Documento -->
                                        <div v-else class="flex items-center gap-4 py-4">
                                            <File class="w-12 h-12 text-muted-foreground flex-shrink-0" />
                                            <div class="flex-1">
                                                <p class="font-medium">{{ archivo.nombre }}</p>
                                                <p class="text-sm text-muted-foreground">
                                                    {{ evidencia.tipo_evidencia_label }}
                                                </p>
                                            </div>
                                            <Button @click="descargarArchivo(archivo.url)" variant="outline" size="sm">
                                                <Download class="w-4 h-4 mr-2" />
                                                Descargar
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Archivo único (retrocompatibilidad) -->
                                <div v-else-if="evidencia.archivo_url" class="bg-muted/30 rounded-lg p-4">
                                    <!-- Imagen -->
                                    <div v-if="evidencia.es_imagen" class="text-center">
                                        <img
                                            :src="evidencia.archivo_url"
                                            :alt="evidencia.descripcion || 'Evidencia'"
                                            class="max-w-full max-h-96 mx-auto rounded-lg shadow-sm"
                                        />
                                    </div>

                                    <!-- Video -->
                                    <div v-else-if="evidencia.es_video" class="text-center">
                                        <video
                                            :src="evidencia.archivo_url"
                                            controls
                                            class="max-w-full max-h-96 mx-auto rounded-lg shadow-sm"
                                        >
                                            Tu navegador no soporta la reproducción de video.
                                        </video>
                                    </div>

                                    <!-- Audio -->
                                    <div v-else-if="evidencia.es_audio" class="flex flex-col items-center gap-4">
                                        <Music class="w-16 h-16 text-muted-foreground" />
                                        <audio
                                            :src="evidencia.archivo_url"
                                            controls
                                            class="w-full max-w-md"
                                        >
                                            Tu navegador no soporta la reproducción de audio.
                                        </audio>
                                    </div>

                                    <!-- Documento -->
                                    <div v-else class="flex flex-col items-center gap-4 py-8">
                                        <File class="w-16 h-16 text-muted-foreground" />
                                        <div class="text-center">
                                            <p class="font-medium">{{ evidencia.archivo_nombre }}</p>
                                            <p class="text-sm text-muted-foreground">
                                                {{ evidencia.tipo_evidencia_label }}
                                                <span v-if="evidencia.archivo_size_formatted">
                                                    • {{ evidencia.archivo_size_formatted }}
                                                </span>
                                            </p>
                                        </div>
                                        <Button @click="descargarArchivo" variant="outline">
                                            <Download class="w-4 h-4 mr-2" />
                                            Descargar para ver
                                        </Button>
                                    </div>
                                </div>

                                <!-- Sin archivo -->
                                <div v-else class="text-center py-8 text-muted-foreground">
                                    <AlertTriangle class="w-12 h-12 mx-auto mb-3" />
                                    <p>No se pudo cargar el archivo</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Descripción -->
                    <Card v-if="evidencia.descripcion">
                        <CardHeader>
                            <CardTitle>Descripción</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="whitespace-pre-wrap">{{ evidencia.descripcion }}</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Columna lateral: Información adicional -->
                <div class="space-y-6">
                    <!-- Información del contrato -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información del Contrato</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Contrato</p>
                                <p class="font-medium">{{ contrato.nombre }}</p>
                                <p v-if="contrato.numero_contrato" class="text-sm text-muted-foreground">
                                    # {{ contrato.numero_contrato }}
                                </p>
                            </div>
                            <div v-if="contrato.proyecto">
                                <p class="text-sm font-medium text-muted-foreground">Proyecto</p>
                                <Link
                                    :href="`/admin/proyectos/${contrato.proyecto.id}`"
                                    class="text-sm text-primary hover:underline"
                                >
                                    {{ contrato.proyecto.nombre }}
                                </Link>
                            </div>
                            <Button
                                :href="`/admin/contratos/${contrato.id}`"
                                :as="Link"
                                variant="outline"
                                size="sm"
                                class="w-full"
                            >
                                <Eye class="w-4 h-4 mr-2" />
                                Ver Contrato
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Información de la obligación -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Obligación Contractual</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <p class="font-medium">{{ evidencia.obligacion.nombre }}</p>
                                <p v-if="evidencia.obligacion.descripcion" class="text-sm text-muted-foreground mt-1">
                                    {{ evidencia.obligacion.descripcion }}
                                </p>
                            </div>
                            <Button
                                :href="`/admin/obligaciones/${evidencia.obligacion.id}`"
                                :as="Link"
                                variant="outline"
                                size="sm"
                                class="w-full"
                            >
                                <Eye class="w-4 h-4 mr-2" />
                                Ver Obligación
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Entregables asociados -->
                    <Card v-if="evidencia.entregables?.length > 0">
                        <CardHeader>
                            <CardTitle>Entregables Asociados</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <div
                                    v-for="entregable in evidencia.entregables"
                                    :key="entregable.id"
                                    class="p-3 bg-muted/30 rounded-lg"
                                >
                                    <p class="font-medium text-sm">{{ entregable.nombre }}</p>
                                    <p class="text-xs text-muted-foreground">{{ entregable.hito.nombre }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Información del archivo -->
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                {{ evidencia.tiene_multiples_archivos ? 'Detalles de los Archivos' : 'Detalles del Archivo' }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Tipo</p>
                                <p class="text-sm">{{ evidencia.tipo_evidencia_label }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Cantidad</p>
                                <p class="text-sm">{{ evidencia.total_archivos || 1 }} archivo(s)</p>
                            </div>
                            <div v-if="evidencia.archivos_info?.length > 0">
                                <p class="text-sm font-medium text-muted-foreground">Archivos</p>
                                <div class="space-y-2 max-h-32 overflow-y-auto">
                                    <div
                                        v-for="archivo in evidencia.archivos_info"
                                        :key="archivo.indice"
                                        class="flex items-center justify-between text-xs p-2 bg-muted/30 rounded"
                                    >
                                        <span class="truncate flex-1 mr-2">{{ archivo.nombre }}</span>
                                        <Button @click="descargarArchivo(archivo.url)" size="sm" variant="ghost" class="h-6 w-6 p-0">
                                            <Download class="w-3 h-3" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                            <div v-else-if="evidencia.archivo_size_formatted">
                                <p class="text-sm font-medium text-muted-foreground">Tamaño</p>
                                <p class="text-sm">{{ evidencia.archivo_size_formatted }}</p>
                            </div>
                            <div v-if="evidencia.metadata?.mime_type">
                                <p class="text-sm font-medium text-muted-foreground">Formato</p>
                                <p class="text-sm">{{ evidencia.metadata.mime_type }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Subido por</p>
                                <p class="text-sm">{{ evidencia.usuario.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ evidencia.usuario.email }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Fecha de subida</p>
                                <p class="text-sm">{{ formatDate(evidencia.created_at) }}</p>
                            </div>
                            <div v-if="evidencia.revisado_at">
                                <p class="text-sm font-medium text-muted-foreground">Revisado</p>
                                <p class="text-sm">{{ formatDate(evidencia.revisado_at) }}</p>
                                <p v-if="evidencia.revisor" class="text-xs text-muted-foreground">
                                    por {{ evidencia.revisor.name }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Observaciones del admin -->
                    <Card v-if="evidencia.observaciones_admin">
                        <CardHeader>
                            <CardTitle>Observaciones de Revisión</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm whitespace-pre-wrap">{{ evidencia.observaciones_admin }}</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>