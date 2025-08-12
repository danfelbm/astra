<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { 
    ArrowLeft, 
    FileText, 
    Calendar, 
    MapPin, 
    Users, 
    CheckCircle, 
    Clock,
    Edit,
    Trash2,
    Info,
    Eye,
    User,
    Send,
    XCircle
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Cargo {
    id: number;
    nombre: string;
    getRutaJerarquica?: () => string;
}

interface PeriodoElectoral {
    id: number;
    nombre: string;
    fecha_inicio?: string;
    fecha_fin?: string;
}

interface Ubicacion {
    id: number;
    nombre: string;
}

interface Convocatoria {
    id: number;
    nombre: string;
    descripcion: string | null;
    fecha_apertura: string;
    fecha_cierre: string;
    cargo?: Cargo;
    periodoElectoral?: PeriodoElectoral;
    territorio_id?: number | null;
    departamento_id?: number | null;
    municipio_id?: number | null;
    localidad_id?: number | null;
    territorio?: Ubicacion;
    departamento?: Ubicacion;
    municipio?: Ubicacion;
    localidad?: Ubicacion;
    formulario_postulacion?: any[];
    estado: 'borrador' | 'activa' | 'cerrada';
    activo: boolean;
    created_at: string;
    updated_at: string;
}

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface Postulacion {
    id: number;
    usuario: Usuario;
    estado: string;
    estado_label: string;
    estado_color: string;
    fecha_postulacion: string | null;
    tiene_candidatura_vinculada: boolean;
    revisado_por: { name: string; email: string } | null;
    fecha_revision: string | null;
    created_at: string;
}

interface Props {
    convocatoria: Convocatoria;
    postulaciones?: Postulacion[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin/dashboard',
    },
    {
        title: 'Convocatorias',
        href: '/admin/convocatorias',
    },
    {
        title: props.convocatoria.nombre,
        href: `/admin/convocatorias/${props.convocatoria.id}`,
    },
];

// Función para obtener el icono del estado de convocatoria
const getEstadoIcon = (estado: string) => {
    switch (estado) {
        case 'borrador':
            return Edit;
        case 'activa':
            return CheckCircle;
        case 'cerrada':
            return Clock;
        default:
            return FileText;
    }
};

// Función para obtener el icono del estado de postulación
const getEstadoPostulacionIcon = (estado: string) => {
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

// Función para obtener el color del estado
const getEstadoColor = (estado: string) => {
    switch (estado) {
        case 'borrador':
            return 'bg-gray-100 text-gray-800';
        case 'activa':
            return 'bg-green-100 text-green-800';
        case 'cerrada':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

// Función para obtener la etiqueta del estado
const getEstadoLabel = (estado: string) => {
    switch (estado) {
        case 'borrador':
            return 'Borrador';
        case 'activa':
            return 'Activa';
        case 'cerrada':
            return 'Cerrada';
        default:
            return estado;
    }
};

// Formatear fechas
const formatearFecha = (fecha: string) => {
    if (!fecha) return 'No definida';
    const date = new Date(fecha);
    return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Determinar si la convocatoria está abierta
const estaAbierta = computed(() => {
    const ahora = new Date();
    const apertura = new Date(props.convocatoria.fecha_apertura);
    const cierre = new Date(props.convocatoria.fecha_cierre);
    return ahora >= apertura && ahora <= cierre && props.convocatoria.estado === 'activa';
});

// Determinar si es futura
const esFutura = computed(() => {
    const ahora = new Date();
    const apertura = new Date(props.convocatoria.fecha_apertura);
    return ahora < apertura;
});

// Obtener estado temporal
const estadoTemporal = computed(() => {
    if (estaAbierta.value) return 'Abierta';
    if (esFutura.value) return 'Futura';
    return 'Cerrada';
});

// Obtener color del estado temporal
const estadoTemporalColor = computed(() => {
    if (estaAbierta.value) return 'bg-green-100 text-green-800';
    if (esFutura.value) return 'bg-blue-100 text-blue-800';
    return 'bg-gray-100 text-gray-800';
});

// Función para editar
const handleEdit = () => {
    router.get(`/admin/convocatorias/${props.convocatoria.id}/edit`);
};

// Función para eliminar
const handleDelete = () => {
    if (confirm('¿Estás seguro de que deseas eliminar esta convocatoria?')) {
        router.delete(`/admin/convocatorias/${props.convocatoria.id}`);
    }
};
</script>

<template>
    <Head :title="`Convocatoria: ${convocatoria.nombre}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="space-y-6">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <Button variant="ghost" size="sm" @click="router.get('/admin/convocatorias')">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Volver
                            </Button>
                        </div>
                        <h1 class="text-3xl font-bold tracking-tight">
                            {{ convocatoria.nombre }}
                        </h1>
                        <div class="flex items-center gap-2 mt-2">
                            <Badge :class="getEstadoColor(convocatoria.estado)" class="text-sm px-2 py-1">
                                <component :is="getEstadoIcon(convocatoria.estado)" class="mr-1 h-3 w-3" />
                                {{ getEstadoLabel(convocatoria.estado) }}
                            </Badge>
                            <Badge :class="estadoTemporalColor" class="text-sm px-2 py-1">
                                {{ estadoTemporal }}
                            </Badge>
                            <Badge v-if="!convocatoria.activo" variant="secondary" class="text-sm px-2 py-1">
                                Inactiva
                            </Badge>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button @click="handleEdit" variant="outline">
                            <Edit class="h-4 w-4 mr-2" />
                            Editar
                        </Button>
                        <Button @click="handleDelete" variant="destructive">
                            <Trash2 class="h-4 w-4 mr-2" />
                            Eliminar
                        </Button>
                    </div>
                </div>

                <!-- Información general -->
                <div class="grid gap-6 md:grid-cols-2">
                    <!-- Información básica -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Info class="h-5 w-5" />
                                Información General
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label class="text-muted-foreground">Descripción:</Label>
                                <p class="font-medium">{{ convocatoria.descripcion || 'Sin descripción' }}</p>
                            </div>
                            <div>
                                <Label class="text-muted-foreground">Estado:</Label>
                                <p class="font-medium">{{ getEstadoLabel(convocatoria.estado) }}</p>
                            </div>
                            <div>
                                <Label class="text-muted-foreground">Activo:</Label>
                                <p class="font-medium">{{ convocatoria.activo ? 'Sí' : 'No' }}</p>
                            </div>
                            <div>
                                <Label class="text-muted-foreground">Creado:</Label>
                                <p class="font-medium">{{ formatearFecha(convocatoria.created_at) }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Fechas -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Calendar class="h-5 w-5" />
                                Fechas
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label class="text-muted-foreground">Fecha de Apertura:</Label>
                                <p class="font-medium">{{ formatearFecha(convocatoria.fecha_apertura) }}</p>
                            </div>
                            <div>
                                <Label class="text-muted-foreground">Fecha de Cierre:</Label>
                                <p class="font-medium">{{ formatearFecha(convocatoria.fecha_cierre) }}</p>
                            </div>
                            <div>
                                <Label class="text-muted-foreground">Estado Temporal:</Label>
                                <p class="font-medium">{{ estadoTemporal }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Cargo y Periodo -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Users class="h-5 w-5" />
                                Cargo y Periodo Electoral
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label class="text-muted-foreground">Cargo:</Label>
                                <p class="font-medium">{{ convocatoria.cargo?.nombre || 'No asignado' }}</p>
                            </div>
                            <div>
                                <Label class="text-muted-foreground">Periodo Electoral:</Label>
                                <p class="font-medium">{{ convocatoria.periodoElectoral?.nombre || 'No asignado' }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Ubicación -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <MapPin class="h-5 w-5" />
                                Ubicación Geográfica
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label class="text-muted-foreground">Territorio:</Label>
                                <p class="font-medium">{{ convocatoria.territorio?.nombre || 'No especificado' }}</p>
                            </div>
                            <div>
                                <Label class="text-muted-foreground">Departamento:</Label>
                                <p class="font-medium">{{ convocatoria.departamento?.nombre || 'No especificado' }}</p>
                            </div>
                            <div>
                                <Label class="text-muted-foreground">Municipio:</Label>
                                <p class="font-medium">{{ convocatoria.municipio?.nombre || 'No especificado' }}</p>
                            </div>
                            <div>
                                <Label class="text-muted-foreground">Localidad:</Label>
                                <p class="font-medium">{{ convocatoria.localidad?.nombre || 'No especificado' }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Formulario de Postulación -->
                <Card v-if="convocatoria.formulario_postulacion && convocatoria.formulario_postulacion.length > 0">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileText class="h-5 w-5" />
                            Formulario de Postulación
                        </CardTitle>
                        <CardDescription>
                            Campos configurados para el formulario de postulación de esta convocatoria.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div 
                                v-for="(campo, index) in convocatoria.formulario_postulacion" 
                                :key="index"
                                class="border rounded-lg p-4"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium">{{ campo.title || campo.label || `Campo ${index + 1}` }}</p>
                                        <p class="text-sm text-muted-foreground">Tipo: {{ campo.type || 'No especificado' }}</p>
                                        <p v-if="campo.required" class="text-sm text-red-600">Requerido</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Postulaciones -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Postulaciones
                        </CardTitle>
                        <CardDescription>
                            Lista de postulaciones recibidas para esta convocatoria.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Usuario</TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead>Fecha Postulación</TableHead>
                                        <TableHead>Perfil</TableHead>
                                        <TableHead>Revisado por</TableHead>
                                        <TableHead class="text-right">Acciones</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-if="!postulaciones || postulaciones.length === 0">
                                        <TableCell colspan="6" class="text-center text-muted-foreground py-8">
                                            No hay postulaciones para esta convocatoria.
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-for="postulacion in postulaciones" :key="postulacion.id">
                                        <TableCell>
                                            <div>
                                                <p class="font-medium">{{ postulacion.usuario.name }}</p>
                                                <p class="text-sm text-muted-foreground">{{ postulacion.usuario.email }}</p>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge :class="postulacion.estado_color">
                                                <component :is="getEstadoPostulacionIcon(postulacion.estado)" class="mr-1 h-3 w-3" />
                                                {{ postulacion.estado_label }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <p class="text-sm">{{ postulacion.fecha_postulacion || 'Borrador' }}</p>
                                            <p class="text-xs text-muted-foreground">{{ postulacion.created_at }}</p>
                                        </TableCell>
                                        <TableCell>
                                            <Badge v-if="postulacion.tiene_candidatura_vinculada" variant="secondary" class="bg-green-100 text-green-800">
                                                <User class="mr-1 h-3 w-3" />
                                                Vinculado
                                            </Badge>
                                            <span v-else class="text-sm text-muted-foreground">No vinculado</span>
                                        </TableCell>
                                        <TableCell>
                                            <div v-if="postulacion.revisado_por">
                                                <p class="text-sm font-medium">{{ postulacion.revisado_por.name }}</p>
                                                <p class="text-xs text-muted-foreground">{{ postulacion.fecha_revision }}</p>
                                            </div>
                                            <span v-else class="text-sm text-muted-foreground">Sin revisar</span>
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="router.get(`/admin/postulaciones/${postulacion.id}`)"
                                            >
                                                <Eye class="mr-2 h-4 w-4" />
                                                Ver detalle
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>