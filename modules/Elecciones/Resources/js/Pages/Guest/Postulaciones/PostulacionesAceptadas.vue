<script setup lang="ts">
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@modules/Core/Resources/js/components/ui/table";
import { Alert, AlertDescription, AlertTitle } from "@modules/Core/Resources/js/components/ui/alert";
import { Skeleton } from "@modules/Core/Resources/js/components/ui/skeleton";
import AdvancedFilters from "@modules/Core/Resources/js/components/filters/AdvancedFilters.vue";
import type { AdvancedFilterConfig } from "@modules/Core/Resources/js/types/filters";
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { 
    Award,
    MapPin, 
    Calendar,
    Users,
    Info,
    ChevronLeft,
    ChevronRight,
    Search,
    ArrowLeft,
    CheckCircle,
    Briefcase,
    CalendarDays,
    FileCheck,
} from 'lucide-vue-next';
import { computed, ref, onMounted } from 'vue';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { useGeographicFilters } from "@modules/Core/Resources/js/composables/useGeographicFilters";

interface Ubicacion {
    territorio?: string;
    departamento?: string;
    municipio?: string;
    localidad?: string;
}

interface Convocatoria {
    id: number;
    nombre: string;
    cargo?: string;
    periodo?: string;
}

interface Postulante {
    nombre: string;
}

interface Postulacion {
    id: number;
    postulante: Postulante;
    convocatoria: Convocatoria;
    ubicacion: Ubicacion;
    fecha_aceptacion?: string;
    fecha_postulacion?: string;
}

interface Props {
    filterFieldsConfig?: any[];
}

const props = defineProps<Props>();

// Estado para postulaciones paginadas
const postulaciones = ref<Postulacion[]>([]);
const postulacionesPagination = ref<any>({
    current_page: 1,
    last_page: 1,
    per_page: 50,
    total: 0,
    from: 0,
    to: 0,
});
const loadingPostulaciones = ref(false);
const currentFilters = ref({});
const filterFieldsConfig = ref<any[]>(props.filterFieldsConfig || []);

// Composable de filtros geográficos con endpoints públicos
const geographicFilters = useGeographicFilters({
    prefix: 'users.',  // Prefijo para filtrar por ubicación del usuario
    endpoints: {
        territorios: `${window.location.origin}/api/public/geographic/territorios`,
        departamentos: `${window.location.origin}/api/public/geographic/departamentos`,
        municipios: `${window.location.origin}/api/public/geographic/municipios`,
        localidades: `${window.location.origin}/api/public/geographic/localidades`,
    },
});

// Configuración para el componente de filtros avanzados
const filterConfig = computed<AdvancedFilterConfig>(() => {
    // Combinar campos básicos con campos geográficos
    const allFields = [
        ...filterFieldsConfig.value || [],
        ...geographicFilters.generateFilterFields(),
    ];
    
    return {
        fields: allFields,
        showQuickSearch: true,
        quickSearchPlaceholder: 'Buscar por nombre, cédula o convocatoria...',
        quickSearchFields: ['users.name', 'users.documento_identidad', 'convocatorias.nombre'],
        maxNestingLevel: 1, // Limitar profundidad para vista pública
        allowSaveFilters: false, // No permitir guardar filtros en vista pública
    };
});

// Formatear fecha
const formatearFecha = (fecha?: string) => {
    if (!fecha) return 'No disponible';
    try {
        return format(new Date(fecha), 'dd/MM/yyyy', { locale: es });
    } catch {
        return fecha;
    }
};

// Obtener ubicación formateada
const getUbicacionFormateada = (ubicacion: Ubicacion) => {
    const partes = [];
    if (ubicacion.localidad) partes.push(ubicacion.localidad);
    if (ubicacion.municipio) partes.push(ubicacion.municipio);
    if (ubicacion.departamento) partes.push(ubicacion.departamento);
    if (ubicacion.territorio) partes.push(ubicacion.territorio);
    
    return partes.length > 0 ? partes.join(', ') : 'No especificada';
};

// Cargar postulaciones
const loadPostulaciones = async (filters: any = {}, page: number = 1) => {
    loadingPostulaciones.value = true;
    try {
        const params = {
            ...filters,
            page,
        };

        const response = await axios.get('/public-api/postulaciones-aceptadas', {
            params,
        });

        postulaciones.value = response.data.postulaciones.data;
        postulacionesPagination.value = {
            current_page: response.data.postulaciones.current_page,
            last_page: response.data.postulaciones.last_page,
            per_page: response.data.postulaciones.per_page,
            total: response.data.postulaciones.total,
            from: response.data.postulaciones.from,
            to: response.data.postulaciones.to,
        };

        // Actualizar configuración de filtros si viene del backend
        if (response.data.filterFieldsConfig) {
            filterFieldsConfig.value = response.data.filterFieldsConfig;
        }
    } catch (error: any) {
        console.error('Error cargando postulaciones:', error);
        if (error.response?.status === 404) {
            // Redirigir o mostrar mensaje de error
            window.location.href = '/';
        }
    } finally {
        loadingPostulaciones.value = false;
    }
};

// Aplicar filtros
const applyFilters = (filters: any) => {
    currentFilters.value = filters;
    loadPostulaciones(filters, 1);
};

// Cambiar página
const changePage = (page: number) => {
    loadPostulaciones(currentFilters.value, page);
};

// Cargar postulaciones al montar
onMounted(async () => {
    // Inicializar filtros geográficos
    await geographicFilters.initialize();
    // Cargar postulaciones
    loadPostulaciones();
});
</script>

<template>
    <div>
        <Head title="Postulaciones Aceptadas" />
        
        <div class="min-h-screen bg-gray-50">
            <!-- Header público -->
            <div class="bg-white shadow-sm border-b">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div class="flex items-start justify-between">
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center gap-3">
                                <Award class="h-8 w-8 text-green-600" />
                                <h1 class="text-2xl font-bold text-gray-900">
                                    Postulaciones Aceptadas
                                </h1>
                            </div>
                            <p class="text-gray-600">
                                Consulta pública de postulaciones aprobadas a convocatorias
                            </p>
                        </div>
                        <a href="/">
                            <Button variant="outline" size="sm">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Volver al inicio
                            </Button>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Información -->
                <Alert class="mb-6">
                    <Info class="h-4 w-4" />
                    <AlertTitle>Información Pública</AlertTitle>
                    <AlertDescription>
                        Esta página muestra únicamente las postulaciones que han sido aceptadas.
                        La información mostrada es de carácter público y cumple con las políticas de privacidad.
                    </AlertDescription>
                </Alert>

                <!-- Filtros avanzados -->
                <Card class="mb-6">
                    <CardHeader>
                        <CardTitle>
                            <Search class="inline-block mr-2 h-5 w-5" />
                            Filtros de Búsqueda
                        </CardTitle>
                        <CardDescription>
                            Utiliza los filtros para encontrar postulaciones específicas
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <AdvancedFilters
                            :config="filterConfig"
                            @apply="applyFilters"
                            @clear="loadPostulaciones"
                        />
                    </CardContent>
                </Card>

                <!-- Tabla de postulaciones -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>
                                    <CheckCircle class="inline-block mr-2 h-5 w-5 text-green-600" />
                                    Listado de Postulaciones Aceptadas
                                </CardTitle>
                                <CardDescription v-if="!loadingPostulaciones">
                                    {{ postulacionesPagination.total }} {{ postulacionesPagination.total === 1 ? 'postulación encontrada' : 'postulaciones encontradas' }}
                                </CardDescription>
                            </div>
                            <Badge variant="outline" class="bg-green-50 text-green-700 border-green-300">
                                <CheckCircle class="h-3 w-3 mr-1" />
                                Todas Aceptadas
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <!-- Loading skeleton -->
                        <div v-if="loadingPostulaciones" class="space-y-4">
                            <Skeleton class="h-12 w-full" />
                            <Skeleton class="h-12 w-full" />
                            <Skeleton class="h-12 w-full" />
                            <Skeleton class="h-12 w-full" />
                            <Skeleton class="h-12 w-full" />
                        </div>

                        <!-- Tabla de datos -->
                        <div v-else class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Postulante</TableHead>
                                        <TableHead>Convocatoria</TableHead>
                                        <TableHead>Cargo</TableHead>
                                        <TableHead>Periodo</TableHead>
                                        <TableHead>Ubicación</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="postulacion in postulaciones" :key="postulacion.id">
                                        <TableCell class="font-medium">
                                            <div class="flex items-center gap-2">
                                                <Users class="h-4 w-4 text-gray-400" />
                                                {{ postulacion.postulante.nombre }}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="max-w-[200px]">
                                                <p class="font-medium truncate">{{ postulacion.convocatoria.nombre }}</p>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-1" v-if="postulacion.convocatoria.cargo">
                                                <Briefcase class="h-4 w-4 text-gray-400" />
                                                <span class="text-sm">{{ postulacion.convocatoria.cargo }}</span>
                                            </div>
                                            <span v-else class="text-gray-400 text-sm">No especificado</span>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-1" v-if="postulacion.convocatoria.periodo">
                                                <CalendarDays class="h-4 w-4 text-gray-400" />
                                                <span class="text-sm">{{ postulacion.convocatoria.periodo }}</span>
                                            </div>
                                            <span v-else class="text-gray-400 text-sm">No especificado</span>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-1">
                                                <MapPin class="h-4 w-4 text-gray-400 flex-shrink-0" />
                                                <span class="text-sm truncate max-w-[200px]">
                                                    {{ getUbicacionFormateada(postulacion.ubicacion) }}
                                                </span>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="postulaciones.length === 0">
                                        <TableCell :colspan="5" class="text-center py-8">
                                            <div class="flex flex-col items-center gap-2 text-muted-foreground">
                                                <Award class="h-8 w-8" />
                                                <p>No se encontraron postulaciones con los filtros aplicados</p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- Paginación -->
                        <div v-if="postulacionesPagination.last_page > 1" class="mt-6 flex items-center justify-between">
                            <p class="text-sm text-muted-foreground">
                                Mostrando {{ postulacionesPagination.from || 0 }} a 
                                {{ postulacionesPagination.to || 0 }} de 
                                {{ postulacionesPagination.total }} postulaciones
                            </p>
                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="postulacionesPagination.current_page === 1"
                                    @click="changePage(postulacionesPagination.current_page - 1)"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                    Anterior
                                </Button>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm">
                                        Página {{ postulacionesPagination.current_page }} de {{ postulacionesPagination.last_page }}
                                    </span>
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="postulacionesPagination.current_page === postulacionesPagination.last_page"
                                    @click="changePage(postulacionesPagination.current_page + 1)"
                                >
                                    Siguiente
                                    <ChevronRight class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Footer informativo -->
                <div class="mt-6 text-center text-sm text-gray-500">
                    <p>La información mostrada es de carácter público y cumple con las políticas de privacidad.</p>
                    <p class="mt-1">
                        Para más información sobre el proceso de postulación, 
                        <Link href="/login" class="text-blue-600 hover:underline">inicie sesión</Link>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>