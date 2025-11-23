<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@modules/Core/Resources/js/components/ui/select';
import { Plus, Search, Download } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import { useToast } from '@modules/Core/Resources/js/composables/useToast';
import { debounce } from 'lodash';
import ContratosList from '@modules/Proyectos/Resources/js/components/ContratosList.vue';
import type { Contrato, EstadoContrato } from '@modules/Proyectos/Resources/js/types/contratos';

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

const cambiarEstado = (contrato: Contrato, nuevoEstado: EstadoContrato) => {
    router.post(route('admin.contratos.cambiar-estado', contrato.id), {
        estado: nuevoEstado
    }, {
        onSuccess: () => {
            toast.success('Estado actualizado exitosamente');
            router.reload({ only: ['contratos'] });
        },
        onError: () => {
            toast.error('Error al cambiar el estado');
        }
    });
};

const duplicarContrato = (contrato: Contrato) => {
    router.post(route('admin.contratos.duplicar', contrato.id), {}, {
        onSuccess: () => {
            toast.success('Contrato duplicado exitosamente');
            router.reload({ only: ['contratos'] });
        },
        onError: () => {
            toast.error('Error al duplicar el contrato');
        }
    });
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

            <!-- Tabla de contratos usando componente reutilizable -->
            <ContratosList
                :contratos="contratos.data"
                :show-proyecto="!proyecto"
                :show-actions="true"
                actions-style="buttons"
                @change-status="cambiarEstado"
                @duplicate="duplicarContrato"
                @delete="deleteContrato"
            />

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