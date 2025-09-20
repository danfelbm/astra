<script setup lang="ts">
import GuestLayout from "@modules/Core/Resources/js/layouts/GuestLayout.vue";
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from "@modules/Core/Resources/js/components/ui/select";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import ProyectoCard from "@modules/Proyectos/Resources/js/components/ProyectoCard.vue";
import { Search, Filter, FolderOpen, Calendar, User, Flag } from 'lucide-vue-next';
import { ref, watch } from 'vue';

// Interfaces
interface Responsable {
    id: number;
    name: string;
}

interface Proyecto {
    id: number;
    nombre: string;
    descripcion?: string;
    estado: string;
    prioridad: string;
    fecha_inicio: string;
    fecha_fin?: string;
    responsable?: Responsable;
    created_at: string;
}

interface PaginatedData {
    data: Proyecto[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Props {
    proyectos: PaginatedData;
    filters: {
        search?: string;
        estado?: string;
    };
}

const props = defineProps<Props>();

// Filtros locales
const searchFilter = ref(props.filters.search || '');
const estadoFilter = ref(props.filters.estado || '');

// Función para aplicar filtros
const applyFilters = () => {
    router.get('/proyectos-publicos', {
        search: searchFilter.value,
        estado: estadoFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Watch para aplicar filtros con debounce en búsqueda
let searchTimeout: NodeJS.Timeout;
watch(searchFilter, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

// Aplicar filtros inmediatamente en select
watch(estadoFilter, () => {
    applyFilters();
});

// Función para obtener color del estado
const getEstadoColor = (estado: string) => {
    const colors: Record<string, string> = {
        'en_progreso': 'bg-yellow-100 text-yellow-800',
        'completado': 'bg-green-100 text-green-800',
    };
    return colors[estado] || 'bg-gray-100 text-gray-800';
};

// Función para obtener label del estado
const getEstadoLabel = (estado: string) => {
    const labels: Record<string, string> = {
        'en_progreso': 'En Progreso',
        'completado': 'Completado',
    };
    return labels[estado] || estado;
};

// Función para obtener color de prioridad
const getPrioridadColor = (prioridad: string) => {
    const colors: Record<string, string> = {
        'baja': 'bg-gray-100 text-gray-800',
        'media': 'bg-blue-100 text-blue-800',
        'alta': 'bg-orange-100 text-orange-800',
        'critica': 'bg-red-100 text-red-800',
    };
    return colors[prioridad] || 'bg-gray-100 text-gray-800';
};

// Función para obtener label de prioridad
const getPrioridadLabel = (prioridad: string) => {
    const labels: Record<string, string> = {
        'baja': 'Baja',
        'media': 'Media',
        'alta': 'Alta',
        'critica': 'Crítica',
    };
    return labels[prioridad] || prioridad;
};

// Función para formatear fecha
const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// Preparar proyectos con formato completo para ProyectoCard
const proyectosFormateados = props.proyectos.data.map(p => ({
    ...p,
    estado_label: getEstadoLabel(p.estado),
    prioridad_label: getPrioridadLabel(p.prioridad),
    porcentaje_completado: p.estado === 'completado' ? 100 : 50,
    duracion_dias: p.fecha_fin ? Math.ceil((new Date(p.fecha_fin).getTime() - new Date(p.fecha_inicio).getTime()) / (1000 * 60 * 60 * 24)) : null
}));
</script>

<template>
    <Head title="Proyectos Públicos" />

    <GuestLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        Proyectos en Curso
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        Conoce los proyectos que estamos desarrollando
                    </p>
                </div>

                <!-- Filtros -->
                <Card class="mb-8">
                    <CardContent class="p-6">
                        <div class="flex flex-col sm:flex-row gap-4">
                            <!-- Búsqueda -->
                            <div class="relative flex-1">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                <Input
                                    v-model="searchFilter"
                                    type="text"
                                    placeholder="Buscar proyectos..."
                                    class="pl-10"
                                />
                            </div>

                            <!-- Filtro por estado -->
                            <Select v-model="estadoFilter">
                                <SelectTrigger class="w-full sm:w-[180px]">
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos</SelectItem>
                                    <SelectItem value="en_progreso">En Progreso</SelectItem>
                                    <SelectItem value="completado">Completados</SelectItem>
                                </SelectContent>
                            </Select>

                            <!-- Limpiar filtros -->
                            <Button
                                variant="outline"
                                @click="searchFilter = ''; estadoFilter = ''; applyFilters()"
                            >
                                <Filter class="mr-2 h-4 w-4" />
                                Limpiar
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Estadísticas rápidas -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Total de Proyectos</p>
                                    <p class="text-2xl font-bold">{{ proyectos.total }}</p>
                                </div>
                                <FolderOpen class="h-8 w-8 text-blue-500" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">En Progreso</p>
                                    <p class="text-2xl font-bold text-yellow-600">
                                        {{ proyectos.data.filter(p => p.estado === 'en_progreso').length }}
                                    </p>
                                </div>
                                <div class="h-8 w-8 rounded-full bg-yellow-100 dark:bg-yellow-900"></div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Completados</p>
                                    <p class="text-2xl font-bold text-green-600">
                                        {{ proyectos.data.filter(p => p.estado === 'completado').length }}
                                    </p>
                                </div>
                                <div class="h-8 w-8 rounded-full bg-green-100 dark:bg-green-900"></div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Grid de proyectos -->
                <div v-if="proyectos.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="proyecto in proyectosFormateados" :key="proyecto.id">
                        <ProyectoCard
                            :proyecto="proyecto"
                            :show-actions="false"
                        />
                    </div>
                </div>

                <!-- Mensaje cuando no hay proyectos -->
                <div v-else>
                    <Card>
                        <CardContent class="py-16">
                            <div class="flex flex-col items-center justify-center text-center">
                                <FolderOpen class="h-16 w-16 text-gray-400 mb-4" />
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                    No hay proyectos públicos disponibles
                                </h3>
                                <p class="text-gray-500 max-w-md">
                                    No se encontraron proyectos que coincidan con los filtros seleccionados.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Paginación -->
                <div v-if="proyectos.last_page > 1" class="flex justify-center gap-2 mt-8">
                    <template v-for="link in proyectos.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-state
                            preserve-scroll
                        >
                            <Button
                                :variant="link.active ? 'default' : 'outline'"
                                size="sm"
                                v-html="link.label"
                            />
                        </Link>
                        <Button
                            v-else
                            variant="outline"
                            size="sm"
                            disabled
                            v-html="link.label"
                        />
                    </template>
                </div>

                <!-- Resumen de registros -->
                <div v-if="proyectos.total > 0" class="text-center text-sm text-gray-500 mt-4">
                    Mostrando {{ proyectos.data.length }} de {{ proyectos.total }} proyectos públicos
                </div>
            </div>
        </div>
    </GuestLayout>
</template>