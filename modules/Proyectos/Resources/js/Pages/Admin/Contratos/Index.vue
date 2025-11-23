<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@modules/Core/Resources/js/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@modules/Core/Resources/js/components/ui/select';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@modules/Core/Resources/js/components/ui/dropdown-menu';
import { Calendar, FileText, MoreHorizontal, Plus, Search, Filter, Download } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import { useToast } from '@modules/Core/Resources/js/composables/useToast';
import { debounce } from 'lodash';

// Tipos
interface Contrato {
    id: number;
    nombre: string;
    proyecto: {
        id: number;
        nombre: string;
    };
    fecha_inicio: string;
    fecha_fin?: string;
    estado: 'borrador' | 'activo' | 'finalizado' | 'cancelado';
    tipo: string;
    monto_total?: number;
    monto_formateado?: string;
    responsable?: {
        id: number;
        name: string;
    };
    dias_restantes?: number;
    esta_vencido?: boolean;
    esta_proximo_vencer?: boolean;
}

// Props
const props = defineProps<{
    contratos: {
        data: Contrato[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters: {
        search?: string;
        estado?: string;
        tipo?: string;
        proyecto_id?: number;
        vencidos?: boolean;
        proximos_vencer?: boolean;
    };
    proyecto?: {
        id: number;
        nombre: string;
    };
    estadisticas: {
        total: number;
        activos: number;
        vencidos: number;
        proximos_vencer: number;
        monto_total: string;
    };
}>();

// Breadcrumbs para navegación
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Contratos', href: '/admin/contratos' },
];

const page = usePage();
const toast = useToast();

// Estado local
const search = ref(props.filters.search || '');
const estadoFilter = ref(props.filters.estado || 'all');
const tipoFilter = ref(props.filters.tipo || 'all');

// Computed
const hasActiveFilters = computed(() => {
    return search.value || (estadoFilter.value && estadoFilter.value !== 'all') ||
           (tipoFilter.value && tipoFilter.value !== 'all') ||
           props.filters.vencidos || props.filters.proximos_vencer;
});

// Métodos
const getEstadoBadgeClass = (estado: string) => {
    const clases = {
        'borrador': 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
        'activo': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'finalizado': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'cancelado': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return clases[estado] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
};

const getTipoBadgeClass = (tipo: string) => {
    const clases = {
        'servicio': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        'obra': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        'suministro': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
        'consultoria': 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
        'otro': 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
    };
    return clases[tipo] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
};

const formatDate = (date: string) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// Búsqueda con debounce
const performSearch = debounce(() => {
    router.get(route('admin.contratos.index'), {
        search: search.value || undefined,
        estado: estadoFilter.value === 'all' ? undefined : estadoFilter.value,
        tipo: tipoFilter.value === 'all' ? undefined : tipoFilter.value,
        proyecto_id: props.proyecto?.id,
        page: 1,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

// Watchers
watch([search, estadoFilter, tipoFilter], () => {
    performSearch();
});

// Acciones
const clearFilters = () => {
    search.value = '';
    estadoFilter.value = 'all';
    tipoFilter.value = 'all';
    performSearch();
};

const deleteContrato = (contrato: Contrato) => {
    if (confirm(`¿Está seguro de eliminar el contrato "${contrato.nombre}"?`)) {
        router.delete(route('admin.contratos.destroy', contrato.id), {
            onSuccess: () => {
                toast.success('Contrato eliminado exitosamente');
            },
            onError: () => {
                toast.error('Error al eliminar el contrato');
            }
        });
    }
};

const cambiarEstado = (contrato: Contrato, nuevoEstado: string) => {
    router.post(route('admin.contratos.cambiar-estado', contrato.id), {
        estado: nuevoEstado
    }, {
        onSuccess: () => {
            toast.success('Estado actualizado exitosamente');
        },
        onError: () => {
            toast.error('Error al cambiar el estado');
        }
    });
};

const duplicarContrato = (contrato: Contrato) => {
    if (confirm(`¿Desea duplicar el contrato "${contrato.nombre}"?`)) {
        router.post(route('admin.contratos.duplicar', contrato.id), {}, {
            onSuccess: () => {
                toast.success('Contrato duplicado exitosamente');
            },
            onError: () => {
                toast.error('Error al duplicar el contrato');
            }
        });
    }
};

const exportarContratos = () => {
    window.location.href = route('admin.contratos.export', {
        search: search.value || undefined,
        estado: estadoFilter.value === 'all' ? undefined : estadoFilter.value,
        tipo: tipoFilter.value === 'all' ? undefined : tipoFilter.value,
    });
};
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:justify-between md:items-center">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold">
                        Contratos
                        <span v-if="proyecto" class="text-xs md:text-sm text-gray-600 block md:inline mt-1 md:mt-0">
                            del proyecto: {{ proyecto.nombre }}
                        </span>
                    </h1>
                    <p class="text-sm md:text-base text-gray-600 mt-1">Gestiona los contratos de los proyectos</p>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <Button variant="outline" @click="exportarContratos" class="flex-1 sm:flex-none">
                        <Download class="h-4 w-4 mr-2" />
                        <span class="hidden sm:inline">Exportar</span>
                    </Button>
                    <Link
                        :href="proyecto
                            ? route('admin.proyectos.contratos.create', proyecto.id)
                            : route('admin.contratos.create')"
                        class="flex-1 sm:flex-none"
                    >
                        <Button class="w-full">
                            <Plus class="h-4 w-4 mr-2" />
                            Nuevo Contrato
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filtros -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg">Filtros</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <div class="relative">
                                <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="search"
                                    placeholder="Buscar contratos..."
                                    class="pl-10"
                                />
                            </div>
                        </div>

                        <Select v-model="estadoFilter">
                            <SelectTrigger>
                                <SelectValue placeholder="Todos los estados" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos los estados</SelectItem>
                                <SelectItem value="borrador">Borrador</SelectItem>
                                <SelectItem value="activo">Activo</SelectItem>
                                <SelectItem value="finalizado">Finalizado</SelectItem>
                                <SelectItem value="cancelado">Cancelado</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select v-model="tipoFilter">
                            <SelectTrigger>
                                <SelectValue placeholder="Todos los tipos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos los tipos</SelectItem>
                                <SelectItem value="servicio">Servicio</SelectItem>
                                <SelectItem value="obra">Obra</SelectItem>
                                <SelectItem value="suministro">Suministro</SelectItem>
                                <SelectItem value="consultoria">Consultoría</SelectItem>
                                <SelectItem value="otro">Otro</SelectItem>
                            </SelectContent>
                        </Select>

                        <Button
                            v-if="hasActiveFilters"
                            variant="outline"
                            @click="clearFilters"
                        >
                            Limpiar Filtros
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Tabla de contratos -->
            <Card>
                <CardContent class="p-0">
                    <!-- Contenedor con scroll horizontal para móvil -->
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="min-w-[200px]">Nombre</TableHead>
                                    <TableHead class="hidden lg:table-cell min-w-[150px]">Proyecto</TableHead>
                                    <TableHead class="min-w-[100px]">Estado</TableHead>
                                    <TableHead class="hidden md:table-cell min-w-[100px]">Tipo</TableHead>
                                    <TableHead class="hidden xl:table-cell min-w-[120px]">Fecha Inicio</TableHead>
                                    <TableHead class="hidden xl:table-cell min-w-[120px]">Fecha Fin</TableHead>
                                    <TableHead class="hidden lg:table-cell min-w-[120px]">Monto</TableHead>
                                    <TableHead class="hidden xl:table-cell min-w-[150px]">Responsable</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="contrato in contratos.data" :key="contrato.id">
                                    <TableCell class="font-medium">
                                        <div class="flex flex-col gap-2">
                                            <span>{{ contrato.nombre }}</span>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <Badge
                                                    v-if="contrato.esta_vencido"
                                                    variant="destructive"
                                                    class="text-xs"
                                                >
                                                    Vencido
                                                </Badge>
                                                <Badge
                                                    v-else-if="contrato.esta_proximo_vencer"
                                                    variant="outline"
                                                    class="text-xs text-yellow-600 border-yellow-600"
                                                >
                                                    {{ contrato.dias_restantes }} días
                                                </Badge>
                                            </div>
                                            <!-- Info adicional visible solo en móvil -->
                                            <div class="flex flex-col gap-1 text-xs text-gray-500 lg:hidden">
                                                <span>Proyecto: {{ contrato.proyecto.nombre }}</span>
                                                <span class="md:hidden">Tipo: {{ contrato.tipo }}</span>
                                            </div>
                                            <!-- Acciones -->
                                            <div class="flex items-center gap-1 flex-wrap">
                                                <Link :href="route('admin.contratos.show', contrato.id)">
                                                    <Button variant="outline" size="sm" class="h-7 text-xs">
                                                        Ver detalles
                                                    </Button>
                                                </Link>
                                                <Link :href="route('admin.contratos.edit', contrato.id)">
                                                    <Button variant="outline" size="sm" class="h-7 text-xs">
                                                        Editar
                                                    </Button>
                                                </Link>
                                                <Link :href="route('admin.obligaciones.index', { contrato_id: contrato.id })">
                                                    <Button variant="outline" size="sm" class="h-7 text-xs">
                                                        Ver obligaciones
                                                    </Button>
                                                </Link>
                                                <Button
                                                    v-if="contrato.estado === 'borrador'"
                                                    variant="outline"
                                                    size="sm"
                                                    class="h-7 text-xs text-green-600 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-950"
                                                    @click="cambiarEstado(contrato, 'activo')"
                                                >
                                                    Activar
                                                </Button>
                                                <Button
                                                    v-if="contrato.estado === 'activo'"
                                                    variant="outline"
                                                    size="sm"
                                                    class="h-7 text-xs text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-950"
                                                    @click="cambiarEstado(contrato, 'finalizado')"
                                                >
                                                    Finalizar
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    class="h-7 text-xs text-purple-600 hover:text-purple-700 hover:bg-purple-50 dark:hover:bg-purple-950"
                                                    @click="duplicarContrato(contrato)"
                                                >
                                                    Duplicar
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    class="h-7 text-xs text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950"
                                                    @click="deleteContrato(contrato)"
                                                >
                                                    Eliminar
                                                </Button>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="hidden lg:table-cell">
                                        <Link
                                            :href="route('admin.proyectos.show', contrato.proyecto.id)"
                                            class="text-blue-600 hover:underline"
                                        >
                                            {{ contrato.proyecto.nombre }}
                                        </Link>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="getEstadoBadgeClass(contrato.estado)">
                                            {{ contrato.estado }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="hidden md:table-cell">
                                        <Badge :class="getTipoBadgeClass(contrato.tipo)">
                                            {{ contrato.tipo }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="hidden xl:table-cell">{{ formatDate(contrato.fecha_inicio) }}</TableCell>
                                    <TableCell class="hidden xl:table-cell">{{ formatDate(contrato.fecha_fin) }}</TableCell>
                                    <TableCell class="hidden lg:table-cell">{{ contrato.monto_formateado || '-' }}</TableCell>
                                    <TableCell class="hidden xl:table-cell">{{ contrato.responsable?.name || '-' }}</TableCell>
                                </TableRow>
                                <TableRow v-if="contratos.data.length === 0">
                                    <TableCell colspan="8" class="text-center py-8 text-gray-500">
                                        No se encontraron contratos
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <!-- Paginación -->
            <div v-if="contratos.last_page > 1" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs sm:text-sm text-gray-600 text-center sm:text-left">
                    Mostrando {{ contratos.from }} - {{ contratos.to }} de {{ contratos.total }} contratos
                </p>
                <div class="flex gap-2 flex-wrap justify-center sm:justify-end">
                    <Link
                        v-for="page in contratos.last_page"
                        :key="page"
                        :href="route('admin.contratos.index', {
                            ...props.filters,
                            page
                        })"
                        :class="[
                            'px-3 py-1 rounded text-sm',
                            page === contratos.current_page
                                ? 'bg-primary text-white'
                                : 'bg-gray-100 hover:bg-gray-200'
                        ]"
                    >
                        {{ page }}
                    </Link>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>