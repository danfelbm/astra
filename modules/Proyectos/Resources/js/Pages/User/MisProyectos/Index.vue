<script setup lang="ts">
import UserLayout from "@modules/Core/Resources/js/layouts/UserLayout.vue";
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from "@modules/Core/Resources/js/components/ui/select";
import ProyectoCard from "@modules/Proyectos/Resources/js/components/ProyectoCard.vue";
import { Plus, Search, Filter, FolderOpen } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import type { Etiqueta } from '@modules/Proyectos/Resources/js/types/etiquetas';

interface Proyecto {
    id: number;
    nombre: string;
    descripcion?: string;
    fecha_inicio: string;
    fecha_fin?: string;
    estado: string;
    estado_label: string;
    prioridad: string;
    prioridad_label: string;
    responsable?: {
        id: number;
        name: string;
    };
    etiquetas?: Etiqueta[];
    porcentaje_completado: number;
    duracion_dias?: number;
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

// Contrato asociado al usuario por proyecto
interface ContratoUsuario {
    id: number;
    proyecto_id: number;
}

interface Props {
    proyectos: PaginatedData;
    filters: {
        search?: string;
        estado?: string;
    };
    canCreate?: boolean;
    canCreateEvidencia?: boolean;
    contratosDelUsuario?: Record<number, ContratoUsuario>;
}

const props = defineProps<Props>();

// Helper para obtener el contrato del usuario para un proyecto específico
const getContratoIdParaProyecto = (proyectoId: number): number | null => {
    if (!props.contratosDelUsuario) return null;
    const contrato = props.contratosDelUsuario[proyectoId];
    return contrato ? contrato.id : null;
};

// Filtros locales
const searchFilter = ref(props.filters.search || '');
const estadoFilter = ref(props.filters.estado || '');

// Función para aplicar filtros
const applyFilters = () => {
    router.get('/miembro/mis-proyectos', {
        search: searchFilter.value,
        estado: estadoFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
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

// Aplicar filtros inmediatamente en selects
watch(estadoFilter, () => {
    applyFilters();
});
</script>

<template>
    <Head title="Mis Proyectos" />

    <UserLayout>
        <div class="flex h-full flex-1 flex-col rounded-xl p-4">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Mis Proyectos
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Gestiona y da seguimiento a tus proyectos asignados
                    </p>
                </div>
                <Link v-if="canCreate" href="/miembro/mis-proyectos/create">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Proyecto
                    </Button>
                </Link>
            </div>

            <!-- Filtros -->
            <Card class="mb-6">
                <CardContent class="p-4">
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
                            <SelectTrigger class="w-full sm:w-[200px]">
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

                        <!-- Botón limpiar filtros -->
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

            <!-- Grid de proyectos -->
            <div v-if="proyectos.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <ProyectoCard
                    v-for="proyecto in proyectos.data"
                    :key="proyecto.id"
                    :proyecto="proyecto"
                    :link-url="`/miembro/mis-proyectos/${proyecto.id}`"
                    :evidencia-url="canCreateEvidencia && getContratoIdParaProyecto(proyecto.id)
                        ? `/miembro/mis-contratos/${getContratoIdParaProyecto(proyecto.id)}/evidencias/create`
                        : null"
                    :clickable="true"
                />
            </div>

            <!-- Mensaje cuando no hay proyectos -->
            <div v-else class="flex flex-col items-center justify-center py-12">
                <FolderOpen class="h-16 w-16 text-gray-400 mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    No hay proyectos disponibles
                </h3>
                <p class="text-gray-500 text-center max-w-md">
                    No tienes proyectos asignados que coincidan con los filtros seleccionados.
                </p>
                <Link v-if="canCreate" href="/miembro/mis-proyectos/create">
                    <Button class="mt-4">
                        <Plus class="mr-2 h-4 w-4" />
                        Crear mi primer proyecto
                    </Button>
                </Link>
            </div>

            <!-- Paginación -->
            <div v-if="proyectos.last_page > 1" class="flex justify-center gap-2 mt-6">
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
                Mostrando {{ proyectos.data.length }} de {{ proyectos.total }} proyectos
            </div>
        </div>
    </UserLayout>
</template>