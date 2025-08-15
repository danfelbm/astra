<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import ZoomMeeting from '@/components/ZoomMeeting.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    ArrowLeft, 
    Calendar, 
    Clock,
    MapPin, 
    Users, 
    CheckCircle,
    XCircle,
    FileText,
    Info,
    UserCheck,
    Video
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';

interface Territorio {
    id: number;
    nombre: string;
}

interface Departamento {
    id: number;
    nombre: string;
}

interface Municipio {
    id: number;
    nombre: string;
}

interface Localidad {
    id: number;
    nombre: string;
}

interface Participante {
    id: number;
    name: string;
    email?: string;
    tipo_participacion: 'asistente' | 'moderador' | 'secretario';
    asistio: boolean;
    hora_registro?: string;
}

interface Asamblea {
    id: number;
    nombre: string;
    descripcion?: string;
    tipo: 'ordinaria' | 'extraordinaria';
    tipo_label: string;
    estado: 'programada' | 'en_curso' | 'finalizada' | 'cancelada';
    estado_label: string;
    estado_color: string;
    fecha_inicio: string;
    fecha_fin: string;
    lugar?: string;
    territorio?: Territorio;
    departamento?: Departamento;
    municipio?: Municipio;
    localidad?: Localidad;
    ubicacion_completa: string;
    quorum_minimo?: number;
    acta_url?: string;
    duracion: string;
    tiempo_restante: string;
    rango_fechas: string;
    alcanza_quorum: boolean;
    asistentes_count: number;
    participantes_count: number;
    // Campos de videoconferencia
    zoom_enabled?: boolean;
    zoom_meeting_id?: string;
    zoom_meeting_password?: string;
    zoom_estado?: string;
    zoom_estado_mensaje?: string;
}

interface Props {
    asamblea: Asamblea;
    esParticipante: boolean;
    esDesuTerritorio: boolean;
    miParticipacion?: {
        tipo: 'asistente' | 'moderador' | 'secretario';
        asistio: boolean;
        hora_registro?: string;
    };
    participantes?: Participante[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Inicio', href: '/dashboard' },
    { title: 'Asambleas', href: '/asambleas' },
    { title: props.asamblea.nombre, href: '#' },
];

// Helper para obtener route
const { route } = window as any;

// Tab activo
const activeTab = ref('informacion');

// Formatear fecha completa
const formatearFechaCompleta = (fecha: string) => {
    if (!fecha) return '';
    return format(new Date(fecha), 'PPPp', { locale: es });
};

// Formatear fecha
const formatearFecha = (fecha: string) => {
    if (!fecha) return '';
    return format(new Date(fecha), 'PPP', { locale: es });
};

// Formatear hora
const formatearHora = (fecha: string) => {
    if (!fecha) return '';
    return format(new Date(fecha), 'p', { locale: es });
};

// Obtener badge para tipo de participación
const getTipoParticipacionBadge = (tipo: string) => {
    switch (tipo) {
        case 'moderador':
            return 'bg-purple-100 text-purple-800';
        case 'secretario':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

// Obtener label para tipo de participación
const getTipoParticipacionLabel = (tipo: string) => {
    switch (tipo) {
        case 'moderador':
            return 'Moderador';
        case 'secretario':
            return 'Secretario';
        default:
            return 'Asistente';
    }
};

// Estadísticas de participantes
const estadisticas = computed(() => {
    if (!props.participantes) return null;
    
    const moderadores = props.participantes.filter(p => p.tipo_participacion === 'moderador').length;
    const secretarios = props.participantes.filter(p => p.tipo_participacion === 'secretario').length;
    const asistentes = props.participantes.filter(p => p.tipo_participacion === 'asistente').length;
    const presentes = props.participantes.filter(p => p.asistio).length;
    
    return {
        moderadores,
        secretarios,
        asistentes,
        presentes,
        total: props.participantes.length,
    };
});

// Volver al listado
const volver = () => {
    router.visit(route('asambleas.index'));
};
</script>

<template>
    <Head :title="`Asamblea: ${asamblea.nombre}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">{{ asamblea.nombre }}</h1>
                    <div class="mt-2 flex items-center gap-2">
                        <Badge :class="asamblea.estado_color">
                            {{ asamblea.estado_label }}
                        </Badge>
                        <Badge :class="asamblea.tipo === 'ordinaria' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'">
                            {{ asamblea.tipo_label }}
                        </Badge>
                        <Badge v-if="esParticipante" class="bg-green-100 text-green-800">
                            <UserCheck class="mr-1 h-3 w-3" />
                            Participante
                        </Badge>
                    </div>
                </div>
                <Button variant="outline" @click="volver">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver
                </Button>
            </div>

            <!-- Mi Participación -->
            <Alert v-if="esParticipante && miParticipacion" class="border-green-200">
                <CheckCircle class="h-4 w-4 text-green-600" />
                <AlertTitle>Tu Participación</AlertTitle>
                <AlertDescription>
                    <div class="mt-2 space-y-1">
                        <p>Estás registrado como <strong>{{ getTipoParticipacionLabel(miParticipacion.tipo) }}</strong> en esta asamblea.</p>
                        <p v-if="miParticipacion.asistio">
                            ✓ Asistencia confirmada el {{ formatearFechaCompleta(miParticipacion.hora_registro) }}
                        </p>
                        <p v-else-if="asamblea.estado === 'en_curso'">
                            La asamblea está en curso. Tu asistencia será registrada por el moderador.
                        </p>
                    </div>
                </AlertDescription>
            </Alert>

            <Alert v-else-if="esDesuTerritorio" class="border-blue-200">
                <Info class="h-4 w-4 text-blue-600" />
                <AlertTitle>Asamblea de tu Territorio</AlertTitle>
                <AlertDescription>
                    Esta es una asamblea pública de tu territorio. Puedes ver la información general pero no la lista de participantes.
                </AlertDescription>
            </Alert>

            <!-- Navegación con Tabs -->
            <Tabs v-model="activeTab" class="w-full">
                <TabsList class="grid w-full grid-cols-3">
                    <TabsTrigger value="informacion">Información</TabsTrigger>
                    <TabsTrigger value="participantes" :disabled="!esParticipante">Participantes</TabsTrigger>
                    <TabsTrigger 
                        value="videoconferencia" 
                        :disabled="!asamblea.zoom_enabled"
                        class="flex items-center gap-2"
                    >
                        <Video class="h-4 w-4" />
                        Videoconferencia
                    </TabsTrigger>
                </TabsList>

                <!-- Tab de Información General -->
                <TabsContent value="informacion" class="space-y-4 mt-6">

            <!-- Información General -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Fechas y Horarios</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-muted-foreground">Inicio</p>
                                <div class="flex items-center gap-2 text-sm">
                                    <Calendar class="h-4 w-4 text-muted-foreground" />
                                    <span>{{ formatearFecha(asamblea.fecha_inicio) }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm">
                                    <Clock class="h-4 w-4 text-muted-foreground" />
                                    <span>{{ formatearHora(asamblea.fecha_inicio) }}</span>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Fin</p>
                                <div class="flex items-center gap-2 text-sm">
                                    <Calendar class="h-4 w-4 text-muted-foreground" />
                                    <span>{{ formatearFecha(asamblea.fecha_fin) }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm">
                                    <Clock class="h-4 w-4 text-muted-foreground" />
                                    <span>{{ formatearHora(asamblea.fecha_fin) }}</span>
                                </div>
                            </div>
                            <div class="pt-2 border-t">
                                <p class="text-sm font-medium">Duración: {{ asamblea.duracion }}</p>
                                <p class="text-sm text-muted-foreground">{{ asamblea.tiempo_restante }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Ubicación</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div v-if="asamblea.lugar">
                                <p class="text-xs text-muted-foreground">Lugar</p>
                                <div class="flex items-start gap-2 text-sm">
                                    <MapPin class="h-4 w-4 text-muted-foreground mt-0.5" />
                                    <span>{{ asamblea.lugar }}</span>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Ubicación Geográfica</p>
                                <p class="text-sm">{{ asamblea.ubicacion_completa }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Estado de la Asamblea</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-muted-foreground">Quórum</p>
                                <div class="flex items-center gap-2">
                                    <Users class="h-4 w-4 text-muted-foreground" />
                                    <span class="text-lg font-semibold">
                                        {{ asamblea.asistentes_count }} / {{ asamblea.quorum_minimo || '∞' }}
                                    </span>
                                </div>
                                <Badge v-if="asamblea.alcanza_quorum" class="mt-2 bg-green-100 text-green-800">
                                    <CheckCircle class="mr-1 h-3 w-3" />
                                    Quórum alcanzado
                                </Badge>
                                <Badge v-else-if="asamblea.quorum_minimo" class="mt-2 bg-yellow-100 text-yellow-800">
                                    <XCircle class="mr-1 h-3 w-3" />
                                    Quórum no alcanzado
                                </Badge>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Total de participantes</p>
                                <p class="text-lg font-semibold">{{ asamblea.participantes_count }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Descripción -->
            <Card v-if="asamblea.descripcion">
                <CardHeader>
                    <CardTitle>Descripción</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm whitespace-pre-wrap">{{ asamblea.descripcion }}</p>
                </CardContent>
            </Card>

                </TabsContent>

                <!-- Tab de Participantes -->
                <TabsContent value="participantes" class="space-y-4 mt-6">

            <!-- Lista de Participantes (solo si es participante) -->
            <Card v-if="esParticipante && participantes">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Participantes</CardTitle>
                            <CardDescription>
                                Lista de participantes registrados en la asamblea
                            </CardDescription>
                        </div>
                        <div v-if="estadisticas" class="flex gap-2">
                            <Badge variant="outline">
                                Moderadores: {{ estadisticas.moderadores }}
                            </Badge>
                            <Badge variant="outline">
                                Secretarios: {{ estadisticas.secretarios }}
                            </Badge>
                            <Badge variant="outline">
                                Asistentes: {{ estadisticas.asistentes }}
                            </Badge>
                            <Badge variant="outline">
                                Presentes: {{ estadisticas.presentes }}
                            </Badge>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Tipo de Participación</TableHead>
                                <TableHead>Asistencia</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="participante in participantes" :key="participante.id">
                                <TableCell class="font-medium">{{ participante.name }}</TableCell>
                                <TableCell>
                                    <Badge :class="getTipoParticipacionBadge(participante.tipo_participacion)">
                                        {{ getTipoParticipacionLabel(participante.tipo_participacion) }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge v-if="participante.asistio" class="bg-green-100 text-green-800">
                                        <CheckCircle class="mr-1 h-3 w-3" />
                                        Presente
                                    </Badge>
                                    <Badge v-else-if="asamblea.estado === 'finalizada'" class="bg-red-100 text-red-800">
                                        <XCircle class="mr-1 h-3 w-3" />
                                        Ausente
                                    </Badge>
                                    <Badge v-else variant="outline">
                                        Pendiente
                                    </Badge>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Acta (si está disponible y es participante o finalizada) -->
            <Card v-if="asamblea.acta_url && (esParticipante || asamblea.estado === 'finalizada')">
                <CardHeader>
                    <CardTitle>Documentos de la Asamblea</CardTitle>
                </CardHeader>
                <CardContent>
                    <a 
                        :href="asamblea.acta_url" 
                        target="_blank"
                        class="inline-flex items-center gap-2 text-blue-600 hover:underline"
                    >
                        <FileText class="h-4 w-4" />
                        Ver Acta de la Asamblea
                    </a>
                </CardContent>
            </Card>

                </TabsContent>

                <!-- Tab de Videoconferencia -->
                <TabsContent value="videoconferencia" class="space-y-4 mt-6">
                    <ZoomMeeting 
                        v-if="asamblea.zoom_enabled && asamblea.zoom_meeting_id"
                        :asamblea-id="asamblea.id"
                        :meeting-id="asamblea.zoom_meeting_id"
                    />
                    
                    <Alert v-else-if="!asamblea.zoom_enabled">
                        <Info class="h-4 w-4" />
                        <AlertTitle>Videoconferencia No Habilitada</AlertTitle>
                        <AlertDescription>
                            Esta asamblea no tiene videoconferencia habilitada.
                        </AlertDescription>
                    </Alert>
                </TabsContent>

            </Tabs>

            <!-- Información adicional para no participantes (fuera de tabs) -->
            <Alert v-if="!esParticipante && esDesuTerritorio" class="mt-6">
                <Info class="h-4 w-4" />
                <AlertTitle>Información Limitada</AlertTitle>
                <AlertDescription>
                    Como no eres participante registrado, solo puedes ver la información general de esta asamblea.
                    Para participar, debes ser añadido por el administrador.
                </AlertDescription>
            </Alert>

        </div>
    </AppLayout>
</template>