<script setup lang="ts">
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from "@modules/Core/Resources/js/components/ui/table";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from "@modules/Core/Resources/js/components/ui/select";
import { Plus, Edit, Trash2, Search, Calendar, User } from 'lucide-vue-next';
import { ref, watch } from 'vue';

// Tipos de datos
interface Proyecto {
    id: number;
    nombre: string;
    descripcion?: string;
    fecha_inicio: string;
    fecha_fin?: string;
    estado: 'planificacion' | 'en_progreso' | 'pausado' | 'completado' | 'cancelado';
    prioridad: 'baja' | 'media' | 'alta' | 'critica';
    responsable?: {
        id: number;
        name: string;
    };
    activo: boolean;
    estado_label: string;
    prioridad_label: string;
    duracion_dias?: number;
    porcentaje_completado: number;
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

// Props del componente
interface Props {
    proyectos: PaginatedData;
    filters: {
        search?: string;
        estado?: string;
        prioridad?: string;
        responsable_id?: number;
    };
    canCreate?: boolean;
    canEdit?: boolean;
    canDelete?: boolean;
}

const props = defineProps<Props>();

// Breadcrumbs para navegación
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Proyectos', href: '/admin/proyectos' },
];

// Filtros locales
const searchFilter = ref(props.filters.search || '');
const estadoFilter = ref(props.filters.estado || '');
const prioridadFilter = ref(props.filters.prioridad || '');

// Función para aplicar filtros
const applyFilters = () => {
    router.get('/admin/proyectos', {
        search: searchFilter.value,
        estado: estadoFilter.value,
        prioridad: prioridadFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Watch para aplicar filtros con debounce en búsqueda
let searchTimeout: NodeJS.Timeout;
watch(searchFilter, (newValue) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

// Aplicar filtros inmediatamente en selects
watch([estadoFilter, prioridadFilter], () => {
    applyFilters();
});

// Función para eliminar proyecto
const deleteProyecto = (proyecto: Proyecto) => {
    if (confirm(`¿Estás seguro de eliminar el proyecto "${proyecto.nombre}"?`)) {
        router.delete(`/admin/proyectos/${proyecto.id}`, {
            preserveScroll: true,
        });
    }
};

// Función para obtener color del badge de estado
const getEstadoColor = (estado: string) => {
    const colors: Record<string, string> = {
        'planificacion': 'bg-blue-100 text-blue-800',
        'en_progreso': 'bg-yellow-100 text-yellow-800',
        'pausado': 'bg-orange-100 text-orange-800',
        'completado': 'bg-green-100 text-green-800',
        'cancelado': 'bg-red-100 text-red-800',
    };
    return colors[estado] || 'bg-gray-100 text-gray-800';
};

// Función para obtener color del badge de prioridad
const getPrioridadColor = (prioridad: string) => {
    const colors: Record<string, string> = {
        'baja': 'bg-gray-100 text-gray-800',
        'media': 'bg-blue-100 text-blue-800',
        'alta': 'bg-orange-100 text-orange-800',
        'critica': 'bg-red-100 text-red-800',
    };
    return colors[prioridad] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Gestión de Proyectos" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header con título y botón de crear -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Gestión de Proyectos
                </h1>
                <Link v-if="canCreate" href="/admin/proyectos/create">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Proyecto
                    </Button>
                </Link>
            </div>

            <!-- Filtros -->
            <Card>
                <CardContent class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Búsqueda -->
                        <div class="relative">
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
                            <SelectTrigger>
                                <SelectValue placeholder="Todos los estados" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos los estados</SelectItem>
                                <SelectItem value="planificacion">Planificación</SelectItem>
                                <SelectItem value="en_progreso">En Progreso</SelectItem>
                                <SelectItem value="pausado">Pausado</SelectItem>
                                <SelectItem value="completado">Completado</SelectItem>
                                <SelectItem value="cancelado">Cancelado</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Filtro por prioridad -->
                        <Select v-model="prioridadFilter">
                            <SelectTrigger>
                                <SelectValue placeholder="Todas las prioridades" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todas las prioridades</SelectItem>
                                <SelectItem value="baja">Baja</SelectItem>
                                <SelectItem value="media">Media</SelectItem>
                                <SelectItem value="alta">Alta</SelectItem>
                                <SelectItem value="critica">Crítica</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Botón limpiar filtros -->
                        <Button
                            variant="outline"
                            @click="searchFilter = ''; estadoFilter = ''; prioridadFilter = ''; applyFilters()"
                        >
                            Limpiar Filtros
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Tabla de proyectos -->
            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Responsable</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Prioridad</TableHead>
                                <TableHead>Fecha Inicio</TableHead>
                                <TableHead>Progreso</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="proyecto in proyectos.data" :key="proyecto.id">
                                <TableCell class="font-medium">
                                    {{ proyecto.nombre }}
                                </TableCell>
                                <TableCell>
                                    <div v-if="proyecto.responsable" class="flex items-center gap-2">
                                        <User class="h-4 w-4 text-gray-400" />
                                        {{ proyecto.responsable.name }}
                                    </div>
                                    <span v-else class="text-gray-400">Sin asignar</span>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="getEstadoColor(proyecto.estado)">
                                        {{ proyecto.estado_label }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="getPrioridadColor(proyecto.prioridad)">
                                        {{ proyecto.prioridad_label }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-1">
                                        <Calendar class="h-4 w-4 text-gray-400" />
                                        {{ proyecto.fecha_inicio }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <div class="w-20 bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                            <div
                                                class="bg-blue-600 h-2.5 rounded-full"
                                                :style="`width: ${proyecto.porcentaje_completado}%`"
                                            ></div>
                                        </div>
                                        <span class="text-sm text-gray-500">{{ proyecto.porcentaje_completado }}%</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            v-if="canEdit"
                                            :href="`/admin/proyectos/${proyecto.id}/edit`"
                                        >
                                            <Button variant="outline" size="sm">
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button
                                            v-if="canDelete"
                                            variant="destructive"
                                            size="sm"
                                            @click="deleteProyecto(proyecto)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <!-- Mensaje cuando no hay proyectos -->
                            <TableRow v-if="proyectos.data.length === 0">
                                <TableCell colspan="7" class="text-center py-8">
                                    <p class="text-gray-500">No se encontraron proyectos</p>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Paginación -->
            <div v-if="proyectos.last_page > 1" class="flex justify-center gap-2">
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
            <div class="text-center text-sm text-gray-500">
                Mostrando {{ proyectos.data.length }} de {{ proyectos.total }} proyectos
            </div>
        </div>
    </AdminLayout>
</template>