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
import { Calendar, DollarSign, FileText, MoreHorizontal, Plus, Search, Filter, Download, AlertCircle } from 'lucide-vue-next';
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
        'borrador': 'bg-gray-100 text-gray-800',
        'activo': 'bg-green-100 text-green-800',
        'finalizado': 'bg-blue-100 text-blue-800',
        'cancelado': 'bg-red-100 text-red-800',
    };
    return clases[estado] || 'bg-gray-100 text-gray-800';
};

const getTipoBadgeClass = (tipo: string) => {
    const clases = {
        'servicio': 'bg-purple-100 text-purple-800',
        'obra': 'bg-orange-100 text-orange-800',
        'suministro': 'bg-indigo-100 text-indigo-800',
        'consultoria': 'bg-pink-100 text-pink-800',
        'otro': 'bg-gray-100 text-gray-800',
    };
    return clases[tipo] || 'bg-gray-100 text-gray-800';
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
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">
                        Contratos
                        <span v-if="proyecto" class="text-sm text-gray-600">
                            del proyecto: {{ proyecto.nombre }}
                        </span>
                    </h1>
                    <p class="text-gray-600 mt-1">Gestiona los contratos de los proyectos</p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="exportarContratos">
                        <Download class="h-4 w-4 mr-2" />
                        Exportar
                    </Button>
                    <Link
                        :href="proyecto
                            ? route('admin.proyectos.contratos.create', proyecto.id)
                            : route('admin.contratos.create')"
                    >
                        <Button>
                            <Plus class="h-4 w-4 mr-2" />
                            Nuevo Contrato
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total</CardTitle>
                        <FileText class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ estadisticas.total }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Activos</CardTitle>
                        <FileText class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ estadisticas.activos }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Vencidos</CardTitle>
                        <AlertCircle class="h-4 w-4 text-red-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ estadisticas.vencidos }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Próximos a Vencer</CardTitle>
                        <AlertCircle class="h-4 w-4 text-yellow-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-yellow-600">{{ estadisticas.proximos_vencer }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Monto Total</CardTitle>
                        <DollarSign class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ estadisticas.monto_total }}</div>
                    </CardContent>
                </Card>
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

                    <div class="flex gap-2 mt-4">
                        <Link :href="route('admin.contratos.vencidos')">
                            <Button variant="outline" size="sm">
                                <AlertCircle class="h-4 w-4 mr-2 text-red-600" />
                                Ver Vencidos
                            </Button>
                        </Link>
                        <Link :href="route('admin.contratos.proximos-vencer')">
                            <Button variant="outline" size="sm">
                                <AlertCircle class="h-4 w-4 mr-2 text-yellow-600" />
                                Próximos a Vencer
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <!-- Tabla de contratos -->
            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Proyecto</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Fecha Inicio</TableHead>
                                <TableHead>Fecha Fin</TableHead>
                                <TableHead>Monto</TableHead>
                                <TableHead>Responsable</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="contrato in contratos.data" :key="contrato.id">
                                <TableCell class="font-medium">
                                    <div class="flex items-center gap-2">
                                        {{ contrato.nombre }}
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
                                </TableCell>
                                <TableCell>
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
                                <TableCell>
                                    <Badge :class="getTipoBadgeClass(contrato.tipo)">
                                        {{ contrato.tipo }}
                                    </Badge>
                                </TableCell>
                                <TableCell>{{ formatDate(contrato.fecha_inicio) }}</TableCell>
                                <TableCell>{{ formatDate(contrato.fecha_fin) }}</TableCell>
                                <TableCell>{{ contrato.monto_formateado || '-' }}</TableCell>
                                <TableCell>{{ contrato.responsable?.name || '-' }}</TableCell>
                                <TableCell class="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="sm">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuLabel>Acciones</DropdownMenuLabel>
                                            <DropdownMenuSeparator />
                                            <Link :href="route('admin.contratos.show', contrato.id)">
                                                <DropdownMenuItem>Ver detalles</DropdownMenuItem>
                                            </Link>
                                            <Link :href="route('admin.contratos.edit', contrato.id)">
                                                <DropdownMenuItem>Editar</DropdownMenuItem>
                                            </Link>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                v-if="contrato.estado === 'borrador'"
                                                @click="cambiarEstado(contrato, 'activo')"
                                            >
                                                Activar
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                v-if="contrato.estado === 'activo'"
                                                @click="cambiarEstado(contrato, 'finalizado')"
                                            >
                                                Finalizar
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="duplicarContrato(contrato)">
                                                Duplicar
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                class="text-red-600"
                                                @click="deleteContrato(contrato)"
                                            >
                                                Eliminar
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="contratos.data.length === 0">
                                <TableCell colspan="9" class="text-center py-8 text-gray-500">
                                    No se encontraron contratos
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Paginación -->
            <div v-if="contratos.last_page > 1" class="flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    Mostrando {{ contratos.from }} - {{ contratos.to }} de {{ contratos.total }} contratos
                </p>
                <div class="flex gap-2">
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