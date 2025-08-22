<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Checkbox } from '@/components/ui/checkbox';
import AdvancedFilters from '@/components/filters/AdvancedFilters.vue';
import type { AdvancedFilterConfig } from '@/types/filters';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { toast } from 'vue-sonner';
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
    Video,
    ChevronLeft,
    ChevronRight
} from 'lucide-vue-next';
import { computed, ref, onMounted, watch, defineAsyncComponent } from 'vue';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { useGeographicFilters } from '@/composables/useGeographicFilters';
import { useDebounce } from '@/composables/useDebounce';

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
    updated_by?: number;
    updated_by_name?: string;
    territorio_nombre?: string;
    departamento_nombre?: string;
    municipio_nombre?: string;
    localidad_nombre?: string;
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
    zoom_integration_type?: 'sdk' | 'api';
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
}

const props = defineProps<Props>();

// Componentes Zoom cargados dinámicamente para mejorar el rendimiento
const ZoomMeeting = defineAsyncComponent(() => import('@/components/ZoomMeeting.vue'));
const ZoomApiMeeting = defineAsyncComponent(() => import('@/components/ZoomApiMeeting.vue'));

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Inicio', href: '/dashboard' },
    { title: 'Asambleas', href: '/asambleas' },
    { title: props.asamblea.nombre, href: '#' },
];

// Tab activo - cambiar a "informacion" si no es participante
const activeTab = ref(props.esParticipante ? 'videoconferencia' : 'informacion');

// Control de lazy loading para optimizar rendimiento
const participantesCargados = ref(false);
const filtrosGeograficosCargados = ref(false);

// Estado para participantes paginados
const participantes = ref<Participante[]>([]);
const participantesPagination = ref<any>({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
    from: 0,
    to: 0,
});
const loadingParticipantes = ref(false);
const currentFilters = ref({});
const filterFieldsConfig = ref<any[]>([]);

// Composable de filtros geográficos con endpoints públicos
const geographicFilters = useGeographicFilters({
    prefix: 'users.',  // Prefijo para evitar ambigüedad SQL
    endpoints: {
        territorios: route('api.geographic.territorios'),
        departamentos: route('api.geographic.departamentos'),
        municipios: route('api.geographic.municipios'),
        localidades: route('api.geographic.localidades'),
    },
});

// Configuración para el componente de filtros avanzados
const filterConfig = computed<AdvancedFilterConfig>(() => {
    // Combinar campos básicos del backend con campos geográficos del composable
    const allFields = [
        ...filterFieldsConfig.value || [],
        ...geographicFilters.generateFilterFields(),
    ];

    return {
        fields: allFields,
        showQuickSearch: true,
        quickSearchPlaceholder: 'Buscar participantes...',
        quickSearchFields: ['name', 'email'],
        maxNestingLevel: 2,
        allowSaveFilters: true,
        saveKey: `asamblea_${props.asamblea.id}_participantes_filters`,
    };
});

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

// Formatear fecha y hora
const formatearFechaHora = (fecha: string) => {
    if (!fecha) return '';
    return format(new Date(fecha), 'PPPp', { locale: es });
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
    if (!participantes.value.length) return null;
    
    const moderadores = participantes.value.filter(p => p.tipo_participacion === 'moderador').length;
    const secretarios = participantes.value.filter(p => p.tipo_participacion === 'secretario').length;
    const asistentes = participantes.value.filter(p => p.tipo_participacion === 'asistente').length;
    const presentes = participantes.value.filter(p => p.asistio).length;
    
    return {
        moderadores,
        secretarios,
        asistentes,
        presentes,
        total: participantesPagination.value.total || 0,
    };
});

// Cargar participantes con filtros y paginación
const fetchParticipantes = async (page = 1, filters = {}) => {
    if (!props.esParticipante) {
        participantes.value = [];
        return;
    }

    loadingParticipantes.value = true;
    currentFilters.value = filters;
    
    try {
        const response = await axios.get(route('asambleas.participantes', props.asamblea.id), {
            params: {
                page,
                ...filters,
            },
        });

        participantes.value = response.data.participantes.data;
        participantesPagination.value = {
            current_page: response.data.participantes.current_page,
            last_page: response.data.participantes.last_page,
            per_page: response.data.participantes.per_page,
            total: response.data.participantes.total,
            from: response.data.participantes.from,
            to: response.data.participantes.to,
        };

        // Configurar filtros en la primera carga
        if (filterFieldsConfig.value.length === 0) {
            filterFieldsConfig.value = response.data.filterFieldsConfig;
        }
    } catch (error) {
        console.error('Error cargando participantes:', error);
        participantes.value = [];
    } finally {
        loadingParticipantes.value = false;
    }
};

// Cambiar página
const changePage = (page: number) => {
    if (page >= 1 && page <= participantesPagination.value.last_page) {
        fetchParticipantes(page, currentFilters.value);
    }
};

// Aplicar filtros con debounce para evitar llamadas excesivas
const { debounce } = useDebounce();
const applyFilters = debounce((filters: any) => {
    fetchParticipantes(1, filters);
}, 300);

// Estado para registrar asistencia
const registrandoAsistencia = ref<number | null>(null);

// Verificar si el usuario actual es moderador
const esModerador = computed(() => {
    return props.miParticipacion?.tipo === 'moderador';
});

// Verificar si se puede tomar asistencia
const puedoTomarAsistencia = computed(() => {
    return esModerador.value && props.asamblea.estado === 'en_curso';
});

// Registrar asistencia de un participante (solo para moderadores)
const registrarAsistencia = async (participanteId: number, asistio: boolean) => {
    // Evitar múltiples clicks mientras se procesa
    if (registrandoAsistencia.value === participanteId) return;
    
    registrandoAsistencia.value = participanteId;
    
    // Actualización optimista: actualizar el estado local inmediatamente
    const participante = participantes.value.find(p => p.id === participanteId);
    const nombreParticipante = participante?.name || 'Participante';
    
    // Guardar estado anterior para rollback en caso de error
    const estadoAnterior = participante?.asistio;
    const horaAnterior = participante?.hora_registro;
    
    if (participante) {
        participante.asistio = asistio;
        if (asistio) {
            participante.hora_registro = new Date().toISOString();
        } else {
            participante.hora_registro = undefined;
        }
    }
    
    try {
        const response = await axios.put(
            route('asambleas.marcar-asistencia-participante', {
                asamblea: props.asamblea.id,
                participante: participanteId
            }),
            { asistio: asistio }
        );
        
        if (response.data.success) {
            // Mostrar notificación de éxito
            if (asistio) {
                toast.success(`${nombreParticipante} marcado como presente`, {
                    duration: 2000,
                });
            } else {
                toast.info(`${nombreParticipante} marcado como ausente`, {
                    duration: 2000,
                });
            }
            
            // Actualizar con datos del servidor si están disponibles
            if (response.data.participante && participante) {
                participante.hora_registro = response.data.participante.hora_registro;
                participante.updated_by = response.data.participante.updated_by;
                participante.updated_by_name = response.data.participante.updated_by_name;
            }
        }
    } catch (error: any) {
        // Si hay error, revertir el cambio optimista
        if (participante) {
            participante.asistio = estadoAnterior ?? false;
            participante.hora_registro = horaAnterior;
        }
        
        // Mostrar mensaje de error
        const mensaje = error.response?.data?.message || 'Error al actualizar la asistencia';
        toast.error(mensaje, {
            description: 'Por favor intenta nuevamente',
            duration: 3000,
        });
    } finally {
        registrandoAsistencia.value = null;
    }
};

// Volver al listado
const volver = () => {
    router.visit(route('asambleas.index'));
};

// Watch para implementar lazy loading del tab de participantes
watch(activeTab, (newTab) => {
    // Solo cargar datos del tab de participantes cuando se active por primera vez
    if (newTab === 'participantes') {
        cargarDatosParticipantes();
    }
});

// Función para cargar datos del tab de participantes
const cargarDatosParticipantes = async () => {
    if (!props.esParticipante || participantesCargados.value) {
        return;
    }
    
    // Marcar como cargados para evitar recargas innecesarias
    participantesCargados.value = true;
    
    // Inicializar filtros geográficos solo cuando se necesiten
    if (!filtrosGeograficosCargados.value) {
        filtrosGeograficosCargados.value = true;
        await geographicFilters.initialize();
    }
    
    // Cargar participantes
    fetchParticipantes();
};

// onMounted simplificado - verifica si necesita cargar datos iniciales
onMounted(() => {
    // Si el tab inicial es participantes, cargar datos inmediatamente
    // Nota: Por defecto, los participantes ven primero videoconferencia, no participantes
    // Por lo que este caso rara vez ocurrirá, pero lo manejamos por completitud
    if (activeTab.value === 'participantes') {
        cargarDatosParticipantes();
    }
});
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
                <TabsList :class="esParticipante ? 'grid w-full grid-cols-3' : 'grid w-full grid-cols-2'">
                    <TabsTrigger 
                        v-if="esParticipante"
                        value="videoconferencia" 
                        :disabled="!asamblea.zoom_enabled"
                        class="flex items-center gap-2"
                    >
                        <Video class="h-4 w-4" />
                        Videoconferencia
                    </TabsTrigger>
                    <TabsTrigger value="informacion">Información</TabsTrigger>
                    <TabsTrigger value="participantes" :disabled="!esParticipante">Participantes</TabsTrigger>
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
                            <div v-if="esParticipante">
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
                            <div v-else>
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
            <Card v-if="esParticipante">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Participantes</CardTitle>
                            <CardDescription>
                                {{ participantesPagination.total }} participantes registrados en la asamblea
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
                    <!-- Filtros Avanzados -->
                    <div class="mb-4">
                        <AdvancedFilters
                            :config="filterConfig"
                            @apply="applyFilters"
                            @clear="applyFilters({})"
                        />
                    </div>

                    <!-- Loading state -->
                    <div v-if="loadingParticipantes" class="flex justify-center py-8">
                        <div class="text-muted-foreground">Cargando participantes...</div>
                    </div>

                    <!-- Mensaje informativo para moderadores -->
                    <Alert v-if="!loadingParticipantes && puedoTomarAsistencia" class="mb-4 border-blue-200">
                        <Info class="h-4 w-4 text-blue-600" />
                        <AlertTitle>Toma de Asistencia Habilitada</AlertTitle>
                        <AlertDescription>
                            Como moderador, puedes marcar la asistencia de los participantes usando los checkboxes en la tabla.
                        </AlertDescription>
                    </Alert>

                    <!-- Tabla de Participantes -->
                    <div v-if="!loadingParticipantes">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Departamento</TableHead>
                                    <TableHead>Municipio</TableHead>
                                    <TableHead>Localidad</TableHead>
                                    <TableHead>Tipo de Participación</TableHead>
                                    <TableHead>Asistencia</TableHead>
                                    <TableHead>Hora Registro</TableHead>
                                    <TableHead>Registrado por</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="participante in participantes" :key="participante.id">
                                    <TableCell class="font-medium">{{ participante.name }}</TableCell>
                                    <TableCell>{{ participante.departamento_nombre || 'N/A' }}</TableCell>
                                    <TableCell>{{ participante.municipio_nombre || 'N/A' }}</TableCell>
                                    <TableCell>{{ participante.localidad_nombre || 'N/A' }}</TableCell>
                                    <TableCell>
                                        <Badge :class="getTipoParticipacionBadge(participante.tipo_participacion)">
                                            {{ getTipoParticipacionLabel(participante.tipo_participacion) }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <!-- Checkbox para moderadores cuando la asamblea está en curso -->
                                        <div v-if="puedoTomarAsistencia" class="flex items-center gap-2">
                                            <div class="relative">
                                                <Checkbox
                                                    :checked="participante.asistio || false"
                                                    :disabled="registrandoAsistencia === participante.id"
                                                    @update:checked="(value) => registrarAsistencia(participante.id, value)"
                                                    :class="registrandoAsistencia === participante.id ? 'opacity-50' : ''"
                                                />
                                                <div v-if="registrandoAsistencia === participante.id" class="absolute inset-0 flex items-center justify-center">
                                                    <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-primary"></div>
                                                </div>
                                            </div>
                                            <span 
                                                v-if="participante.asistio" 
                                                class="text-green-600 transition-all duration-300"
                                            >
                                                Presente
                                            </span>
                                            <span 
                                                v-else 
                                                class="text-gray-400 transition-all duration-300"
                                            >
                                                Ausente
                                            </span>
                                        </div>
                                        <!-- Vista solo lectura para no moderadores o cuando la asamblea no está en curso -->
                                        <div v-else>
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
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {{ participante.hora_registro ? formatearFechaHora(participante.hora_registro) : '-' }}
                                    </TableCell>
                                    <TableCell>
                                        <span v-if="participante.updated_by">
                                            {{ participante.updated_by === participante.id ? 'Auto-registro' : (participante.updated_by_name || 'Admin') }}
                                        </span>
                                        <span v-else>-</span>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="participantes.length === 0">
                                    <TableCell :colspan="8" class="text-center py-8">
                                        <p class="text-muted-foreground">No hay participantes registrados</p>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <!-- Paginación -->
                        <div v-if="participantesPagination.last_page > 1" class="flex items-center justify-between mt-4">
                            <div class="text-sm text-muted-foreground">
                                Mostrando {{ participantesPagination.from }} a {{ participantesPagination.to }} de {{ participantesPagination.total }} resultados
                            </div>
                            <div class="flex items-center space-x-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="participantesPagination.current_page === 1"
                                    @click="changePage(participantesPagination.current_page - 1)"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                    Anterior
                                </Button>
                                <div class="text-sm">
                                    Página {{ participantesPagination.current_page }} de {{ participantesPagination.last_page }}
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="participantesPagination.current_page === participantesPagination.last_page"
                                    @click="changePage(participantesPagination.current_page + 1)"
                                >
                                    Siguiente
                                    <ChevronRight class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
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
                    <!-- Modo SDK (Zoom Meeting) -->
                    <ZoomMeeting 
                        v-if="asamblea.zoom_enabled && asamblea.zoom_integration_type === 'sdk' && asamblea.zoom_meeting_id"
                        :asamblea-id="asamblea.id"
                        :meeting-id="asamblea.zoom_meeting_id"
                    />
                    
                    <!-- Modo API (Zoom API Meeting) -->
                    <ZoomApiMeeting 
                        v-else-if="asamblea.zoom_enabled && asamblea.zoom_integration_type === 'api'"
                        :asamblea-id="asamblea.id"
                    />
                    
                    <!-- Zoom habilitado pero sin configuración completa -->
                    <Alert v-else-if="asamblea.zoom_enabled && !asamblea.zoom_meeting_id">
                        <Info class="h-4 w-4" />
                        <AlertTitle>Configuración Incompleta</AlertTitle>
                        <AlertDescription>
                            La videoconferencia está habilitada pero aún no está completamente configurada.
                        </AlertDescription>
                    </Alert>
                    
                    <!-- Videoconferencia no habilitada -->
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
                    <div class="space-y-2">
                        <p>Para participar completamente, debes ser añadido por el administrador.</p>
                    </div>
                </AlertDescription>
            </Alert>

        </div>
    </AppLayout>
</template>