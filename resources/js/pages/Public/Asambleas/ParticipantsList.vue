<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import AdvancedFilters from '@/components/filters/AdvancedFilters.vue';
import type { AdvancedFilterConfig } from '@/types/filters';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { 
    Calendar,
    MapPin, 
    Users,
    Info,
    ChevronLeft,
    ChevronRight,
    Search
} from 'lucide-vue-next';
import { computed, ref, onMounted } from 'vue';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { useGeographicFilters } from '@/composables/useGeographicFilters';

interface Participante {
    id: number;
    name: string;
    territorio_nombre?: string;
    departamento_nombre?: string;
    municipio_nombre?: string;
    localidad_nombre?: string;
}

interface Asamblea {
    id: number;
    nombre: string;
    descripcion?: string;
    fecha_inicio: string;
    fecha_fin: string;
    lugar?: string;
    ubicacion_completa: string;
    public_participants_mode: 'list' | 'search';
}

interface Props {
    asamblea: Asamblea;
    filterFieldsConfig?: any[];
}

const props = defineProps<Props>();

// Estado para participantes paginados
const participantes = ref<Participante[]>([]);
const participantesPagination = ref<any>({
    current_page: 1,
    last_page: 1,
    per_page: 50,
    total: 0,
    from: 0,
    to: 0,
});
const loadingParticipantes = ref(false);
const currentFilters = ref({});
const filterFieldsConfig = ref<any[]>(props.filterFieldsConfig || []);

// Composable de filtros geográficos con endpoints públicos
const geographicFilters = useGeographicFilters({
    prefix: 'users.',  // Prefijo para evitar ambigüedad SQL
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
        quickSearchPlaceholder: 'Buscar por nombre o cédula...',
        quickSearchFields: ['users.name', 'users.documento_identidad'],
        maxNestingLevel: 1, // Limitar profundidad para vista pública
        allowSaveFilters: false, // No permitir guardar filtros en vista pública
    };
});

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

// Cargar participantes
const loadParticipantes = async (filters: any = {}, page: number = 1) => {
    loadingParticipantes.value = true;
    try {
        const params = {
            ...filters,
            page,
        };

        const response = await axios.get(`/api/asambleas/${props.asamblea.id}/participantes-publico`, {
            params,
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

        // Actualizar configuración de filtros si viene del backend
        if (response.data.filterFieldsConfig) {
            filterFieldsConfig.value = response.data.filterFieldsConfig;
        }
    } catch (error: any) {
        console.error('Error cargando participantes:', error);
        if (error.response?.status === 404) {
            // Redirigir o mostrar mensaje de error
            window.location.href = '/';
        }
    } finally {
        loadingParticipantes.value = false;
    }
};

// Aplicar filtros
const applyFilters = (filters: any) => {
    currentFilters.value = filters;
    loadParticipantes(filters, 1);
};

// Cambiar página
const changePage = (page: number) => {
    loadParticipantes(currentFilters.value, page);
};

// Cargar participantes al montar
onMounted(async () => {
    // Inicializar filtros geográficos
    await geographicFilters.initialize();
    // Cargar participantes
    loadParticipantes();
});
</script>

<template>
    <div>
        <Head :title="`Participantes - ${asamblea.nombre}`" />
        
        <div class="min-h-screen bg-gray-50">
            <!-- Header público -->
            <div class="bg-white shadow-sm border-b">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div class="flex flex-col gap-4">
                        <h1 class="text-2xl font-bold text-gray-900">
                            {{ asamblea.nombre }}
                        </h1>
                        <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                            <div class="flex items-center gap-1">
                                <Calendar class="h-4 w-4" />
                                <span>{{ formatearFecha(asamblea.fecha_inicio) }} - {{ formatearFecha(asamblea.fecha_fin) }}</span>
                            </div>
                            <div v-if="asamblea.lugar" class="flex items-center gap-1">
                                <MapPin class="h-4 w-4" />
                                <span>{{ asamblea.ubicacion_completa }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Información -->
                <Alert class="mb-6">
                    <Info class="h-4 w-4" />
                    <AlertTitle>Consulta Pública de Participantes</AlertTitle>
                    <AlertDescription>
                        Esta es una vista pública de los participantes registrados en la asamblea.
                        La información mostrada es limitada para proteger la privacidad de los participantes.
                    </AlertDescription>
                </Alert>

                <!-- Card de participantes -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>
                                    <Users class="inline-block mr-2 h-5 w-5" />
                                    Lista de Participantes
                                </CardTitle>
                                <CardDescription>
                                    {{ participantesPagination.total }} participantes registrados
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <!-- Filtros -->
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

                        <!-- Tabla de Participantes -->
                        <div v-else>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nombre</TableHead>
                                        <TableHead>Territorio</TableHead>
                                        <TableHead>Departamento</TableHead>
                                        <TableHead>Municipio</TableHead>
                                        <TableHead>Localidad</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="participante in participantes" :key="participante.id">
                                        <TableCell class="font-medium">{{ participante.name }}</TableCell>
                                        <TableCell>{{ participante.territorio_nombre || 'N/A' }}</TableCell>
                                        <TableCell>{{ participante.departamento_nombre || 'N/A' }}</TableCell>
                                        <TableCell>{{ participante.municipio_nombre || 'N/A' }}</TableCell>
                                        <TableCell>{{ participante.localidad_nombre || 'N/A' }}</TableCell>
                                    </TableRow>
                                    <TableRow v-if="participantes.length === 0">
                                        <TableCell :colspan="5" class="text-center py-8">
                                            <div class="flex flex-col items-center gap-2 text-muted-foreground">
                                                <Search class="h-8 w-8" />
                                                <p>No se encontraron participantes</p>
                                                <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                                            </div>
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

                <!-- Footer informativo -->
                <div class="mt-6 text-center text-sm text-gray-500">
                    <p>Esta información es de carácter público y está sujeta a las políticas de privacidad.</p>
                    <p class="mt-1">
                        Para más información sobre la asamblea, contacte a la organización.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>