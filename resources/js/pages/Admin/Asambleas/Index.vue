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
import { Edit, Plus, Search, Trash2, Calendar, Users, MapPin, Eye, UserPlus } from 'lucide-vue-next';
import AdvancedFilters from '@/components/filters/AdvancedFilters.vue';
import type { AdvancedFilterConfig } from '@/types/filters';
import { ref } from 'vue';

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
    territorio?: { id: number; nombre: string };
    departamento?: { id: number; nombre: string };
    municipio?: { id: number; nombre: string };
    localidad?: { id: number; nombre: string };
    ubicacion_completa: string;
    quorum_minimo?: number;
    activo: boolean;
    acta_url?: string;
    created_at: string;
    estado_temporal: string;
    duracion: string;
    tiempo_restante: string;
    rango_fechas: string;
    participantes_count: number;
}

interface Props {
    asambleas: {
        data: Asamblea[];
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
        tipo?: string;
        estado?: string;
        activo?: string;
        search?: string;
        advanced_filters?: string;
    };
    filterFieldsConfig: any[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Asambleas', href: '/admin/asambleas' },
];

// Configuración para el componente de filtros avanzados
const filterConfig: AdvancedFilterConfig = {
    fields: props.filterFieldsConfig || [],
    showQuickSearch: true,
    quickSearchPlaceholder: 'Buscar...',
    quickSearchFields: ['nombre', 'descripcion', 'lugar'],
    maxNestingLevel: 2,
    allowSaveFilters: true,
    debounceTime: 500,
    autoApply: false,
};

// Helper para obtener route
const { route } = window as any;

const searchQuery = ref(props.filters.search || '');
const selectedTipo = ref(props.filters.tipo || 'all');
const selectedEstado = ref(props.filters.estado || 'all');
const selectedActivo = ref(props.filters.activo || 'all');

// Tipos disponibles
const tiposAsamblea = [
    { value: 'ordinaria', label: 'Ordinaria' },
    { value: 'extraordinaria', label: 'Extraordinaria' },
];

// Estados disponibles
const estadosAsamblea = [
    { value: 'programada', label: 'Programada' },
    { value: 'en_curso', label: 'En Curso' },
    { value: 'finalizada', label: 'Finalizada' },
    { value: 'cancelada', label: 'Cancelada' },
];

// Eliminar asamblea
const deleteAsamblea = (id: number) => {
    router.delete(`/admin/asambleas/${id}`, {
        onSuccess: () => {
            // El mensaje de éxito se maneja en el backend
        },
    });
};

// Obtener icono para estado
const getEstadoIcon = (estado: string) => {
    switch (estado) {
        case 'programada':
            return Calendar;
        case 'en_curso':
            return Users;
        case 'finalizada':
        case 'cancelada':
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

// Obtener badge de tipo
const getTipoBadge = (tipo: string) => {
    return tipo === 'ordinaria' ?
        { class: 'bg-blue-100 text-blue-800', text: 'Ordinaria' } :
        { class: 'bg-purple-100 text-purple-800', text: 'Extraordinaria' };
};
</script>

<template>
    <Head title="Asambleas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Asambleas</h1>
                    <p class="text-muted-foreground">
                        Gestiona las asambleas de la organización
                    </p>
                </div>
                <Link :href="route('admin.asambleas.create')">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nueva Asamblea
                    </Button>
                </Link>
            </div>

            <!-- Filtros avanzados -->
            <AdvancedFilters 
                :config="filterConfig"
                :route="route('admin.asambleas.index')"
                :currentFilters="props.filters"
            />

            <!-- Tabla -->
            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[250px]">Nombre</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Fechas</TableHead>
                                <TableHead>Ubicación</TableHead>
                                <TableHead>Participantes</TableHead>
                                <TableHead>Activo</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="asamblea in asambleas.data" :key="asamblea.id">
                                <TableCell class="font-medium">
                                    <div>
                                        <p class="font-semibold">{{ asamblea.nombre }}</p>
                                        <p v-if="asamblea.descripcion" class="text-sm text-muted-foreground">
                                            {{ asamblea.descripcion.substring(0, 50) }}{{ asamblea.descripcion.length > 50 ? '...' : '' }}
                                        </p>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="getTipoBadge(asamblea.tipo).class">
                                        {{ asamblea.tipo_label }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="space-y-1">
                                        <Badge :class="asamblea.estado_color">
                                            <component :is="getEstadoIcon(asamblea.estado)" class="mr-1 h-3 w-3" />
                                            {{ asamblea.estado_label }}
                                        </Badge>
                                        <p class="text-xs text-muted-foreground">
                                            {{ asamblea.tiempo_restante }}
                                        </p>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm">
                                        <p>{{ formatearFecha(asamblea.fecha_inicio) }}</p>
                                        <p class="text-muted-foreground">{{ asamblea.duracion }}</p>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm">
                                        <p v-if="asamblea.lugar" class="font-medium">{{ asamblea.lugar }}</p>
                                        <p class="text-muted-foreground">{{ asamblea.ubicacion_completa }}</p>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-1">
                                        <Users class="h-4 w-4 text-muted-foreground" />
                                        <span>{{ asamblea.participantes_count || 0 }}</span>
                                        <span v-if="asamblea.quorum_minimo" class="text-muted-foreground">
                                            / {{ asamblea.quorum_minimo }}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="getActivoBadge(asamblea.activo).class">
                                        {{ getActivoBadge(asamblea.activo).text }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link :href="route('admin.asambleas.show', asamblea.id)">
                                            <Button variant="ghost" size="sm">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Link :href="route('admin.asambleas.edit', asamblea.id)">
                                            <Button variant="ghost" size="sm">
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <AlertDialog>
                                            <AlertDialogTrigger asChild>
                                                <Button variant="ghost" size="sm">
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>¿Estás seguro?</AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        Esta acción no se puede deshacer. Se eliminará permanentemente la asamblea
                                                        "{{ asamblea.nombre }}" y toda su información asociada.
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                                    <AlertDialogAction 
                                                        @click="deleteAsamblea(asamblea.id)"
                                                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                                    >
                                                        Eliminar
                                                    </AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="asambleas.data.length === 0">
                                <TableCell colspan="8" class="text-center py-8">
                                    <p class="text-muted-foreground">No se encontraron asambleas</p>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Paginación -->
            <div v-if="asambleas.last_page > 1" class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    Mostrando {{ (asambleas.current_page - 1) * asambleas.per_page + 1 }} a 
                    {{ Math.min(asambleas.current_page * asambleas.per_page, asambleas.total) }} de 
                    {{ asambleas.total }} asambleas
                </p>
                <div class="flex gap-2">
                    <template v-for="link in asambleas.links" :key="link.label">
                        <Link 
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'px-3 py-1 text-sm border rounded',
                                link.active 
                                    ? 'bg-primary text-primary-foreground' 
                                    : 'bg-background hover:bg-accent'
                            ]"
                            v-html="link.label"
                        />
                        <span 
                            v-else
                            :class="[
                                'px-3 py-1 text-sm border rounded',
                                'bg-muted text-muted-foreground cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>