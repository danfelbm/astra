<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Edit, Plus, Search, Trash2, Calendar, Clock, CheckCircle } from 'lucide-vue-next';
import AdvancedFilters from '@/components/filters/AdvancedFilters.vue';
import type { AdvancedFilterConfig } from '@/types/filters';
import { ref } from 'vue';

interface PeriodoElectoral {
    id: number;
    nombre: string;
    descripcion?: string;
    fecha_inicio: string;
    fecha_fin: string;
    activo: boolean;
    created_at: string;
    estado: 'vigente' | 'futuro' | 'pasado';
    estado_label: string;
    estado_color: string;
    duracion: string;
    dias_restantes: number;
    rango_fechas: string;
}

interface Props {
    periodos: {
        data: PeriodoElectoral[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    filters: {
        estado?: string;
        activo?: string;
        search?: string;
    
        advanced_filters?: string;};
    filterFieldsConfig: any[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Periodos Electorales', href: '/admin/periodos-electorales' },
];

// Configuración para el componente de filtros avanzados
const filterConfig: AdvancedFilterConfig = {
    fields: props.filterFieldsConfig || [],
    showQuickSearch: true,
    quickSearchPlaceholder: 'Buscar...',
    quickSearchFields: ['nombre', 'name', 'titulo', 'descripcion'],
    maxNestingLevel: 2,
    allowSaveFilters: true,
    debounceTime: 500,
    autoApply: false,
};

// Helper para obtener route
const { route } = window as any;

const searchQuery = ref(props.filters.search || '');
const selectedEstado = ref(props.filters.estado || 'all');
const selectedActivo = ref(props.filters.activo || 'all');

// Estados disponibles
const estadosTemporales = [
    { value: 'vigente', label: 'Vigente' },
    { value: 'futuro', label: 'Próximo' },
    { value: 'pasado', label: 'Finalizado' },
];


// Eliminar periodo
const deletePeriodo = (id: number) => {
    router.delete(`/admin/periodos-electorales/${id}`, {
        onSuccess: () => {
            // El mensaje de éxito se maneja en el backend
        },
    });
};

// Obtener icono para estado temporal
const getEstadoIcon = (estado: string) => {
    switch (estado) {
        case 'vigente':
            return CheckCircle;
        case 'futuro':
            return Clock;
        case 'pasado':
            return Calendar;
        default:
            return Calendar;
    }
};

// Obtener badge de estado activo
const getActivoBadge = (activo: boolean) => {
    return activo ? 
        { class: 'bg-green-100 text-green-800', text: 'Activo' } :
        { class: 'bg-red-100 text-red-800', text: 'Inactivo' };
};

// Formatear fechas para mostrar
const formatearFecha = (fecha: string) => {
    return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Periodos Electorales" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Periodos Electorales</h1>
                    <p class="text-muted-foreground">
                        Gestiona los marcos temporales para procesos electorales
                    </p>
                </div>
                <Link :href="route('admin.periodos-electorales.create')">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Periodo
                    </Button>
                </Link>
            </div>

            <!-- Advanced Filters -->
            <AdvancedFilters
                :config="filterConfig"
                :route="route('admin.periodos-electorales.index')"
                :initial-filters="{
                    quickSearch: filters.search,
                    rootGroup: filters.advanced_filters ? JSON.parse(filters.advanced_filters) : undefined
                }"
            />

            <!-- Lista de Periodos -->
            <Card>
                <CardContent class="pt-6">
                    <div v-if="periodos.total === 0" class="text-center py-8 text-muted-foreground">
                        <Calendar class="mx-auto h-8 w-8 mb-2" />
                        <p>No se encontraron periodos electorales</p>
                        <p class="text-sm">Crea el primer periodo para comenzar</p>
                    </div>
                    <div v-else>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Estado Temporal</TableHead>
                                    <TableHead>Fechas</TableHead>
                                    <TableHead>Duración</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Creado</TableHead>
                                    <TableHead class="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="periodo in periodos.data" :key="periodo.id">
                                    <TableCell class="font-medium">
                                        <div>
                                            <p>{{ periodo.nombre }}</p>
                                            <p v-if="periodo.descripcion" class="text-sm text-muted-foreground">
                                                {{ periodo.descripcion }}
                                            </p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="periodo.estado_color">
                                            <component :is="getEstadoIcon(periodo.estado)" class="mr-1 h-3 w-3" />
                                            {{ periodo.estado_label }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm">
                                            <div class="font-medium">
                                                {{ formatearFecha(periodo.fecha_inicio) }}
                                            </div>
                                            <div class="text-muted-foreground">
                                                {{ formatearFecha(periodo.fecha_fin) }}
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm">
                                            <div>{{ periodo.duracion }}</div>
                                            <div v-if="periodo.dias_restantes > 0 && periodo.estado !== 'pasado'" class="text-muted-foreground">
                                                {{ periodo.dias_restantes }} días restantes
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="getActivoBadge(periodo.activo).class">
                                            {{ getActivoBadge(periodo.activo).text }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ new Date(periodo.created_at).toLocaleDateString('es-ES') }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex gap-2 justify-end">
                                            <Link :href="route('admin.periodos-electorales.edit', periodo.id)">
                                                <Button variant="ghost" size="sm">
                                                    <Edit class="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <AlertDialog>
                                                <AlertDialogTrigger asChild>
                                                    <Button variant="ghost" size="sm">
                                                        <Trash2 class="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>¿Eliminar periodo electoral?</AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            Esta acción no se puede deshacer. Se eliminará permanentemente el periodo "{{ periodo.nombre }}".
                                                            <br><br>
                                                            <strong>Nota:</strong> No se puede eliminar un periodo que tenga postulaciones asociadas.
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                                        <AlertDialogAction @click="deletePeriodo(periodo.id)">
                                                            Eliminar
                                                        </AlertDialogAction>
                                                    </AlertDialogFooter>
                                                </AlertDialogContent>
                                            </AlertDialog>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <!-- Paginación -->
                        <div v-if="periodos.last_page > 1" class="flex items-center justify-center mt-6">
                            <div class="flex gap-2">
                                <Link
                                    v-for="link in periodos.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    :class="[
                                        'px-3 py-2 text-sm rounded-md',
                                        link.active
                                            ? 'bg-primary text-primary-foreground'
                                            : 'bg-muted hover:bg-muted/80',
                                        !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                    ]"
                                    v-html="link.label"
                                    :disabled="!link.url"
                                />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>