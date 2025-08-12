<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
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
import { Edit, Plus, Search, Trash2, Users, FileText, BarChart3 } from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';
import AdvancedFilters from '@/components/filters/AdvancedFilters.vue';
import type { AdvancedFilterConfig } from '@/types/filters';

interface Categoria {
    id: number;
    nombre: string;
    descripcion?: string;
    activa: boolean;
}

interface Votacion {
    id: number;
    titulo: string;
    descripcion?: string;
    categoria: Categoria;
    fecha_inicio: string;
    fecha_fin: string;
    estado: 'borrador' | 'activa' | 'finalizada';
    resultados_publicos: boolean;
    resultados_visibles?: boolean;
    created_at: string;
    votos_count?: number;
    votantes_count?: number;
}


interface Props {
    votaciones: {
        data: Votacion[];
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
    categorias: Categoria[];
    filters: {
        estado?: string;
        categoria_id?: string;
        search?: string;
        advanced_filters?: string;
    };
    filterFieldsConfig: any[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Votaciones', href: '/admin/votaciones' },
];

// Configuración para el componente de filtros avanzados
const filterConfig: AdvancedFilterConfig = {
    fields: props.filterFieldsConfig || [],
    showQuickSearch: true,
    quickSearchPlaceholder: 'Buscar por título o descripción...',
    quickSearchFields: ['titulo', 'descripcion'],
    maxNestingLevel: 2,
    allowSaveFilters: true,
    debounceTime: 500,
    autoApply: false,
};

// Función para navegar al historial de importaciones
const viewImportHistory = (votacionId: number) => {
    router.get(`/admin/votaciones/${votacionId}/imports`);
};

// Función para ver resultados
const verResultados = (votacionId: number) => {
    router.get(`/votaciones/${votacionId}/resultados`);
};

// Helper para obtener route
const { route } = window as any;

// Eliminar votación
const deleteVotacion = (id: number) => {
    router.delete(`/admin/votaciones/${id}`, {
        onSuccess: () => {
            // El mensaje de éxito se maneja en el backend
        },
    });
};

// Toggle status de votación
const toggleVotacionStatus = (votacion: Votacion) => {
    const nuevoEstado = votacion.estado === 'borrador' ? 'activa' : 'borrador';
    
    router.post(`/admin/votaciones/${votacion.id}/toggle-status`, {
        estado: nuevoEstado
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // El mensaje se maneja en el backend
        },
        onError: (errors) => {
            console.error('Error toggling status:', errors);
        }
    });
};

// Finalizar votación
const finalizarVotacion = (votacion: Votacion) => {
    router.post(`/admin/votaciones/${votacion.id}/toggle-status`, {
        estado: 'finalizada'
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // El mensaje se maneja en el backend
        },
        onError: (errors) => {
            console.error('Error finalizando votación:', errors);
        }
    });
};


// Función para obtener el color del badge según el estado
const getEstadoBadgeVariant = (estado: string) => {
    switch (estado) {
        case 'borrador':
            return 'secondary';
        case 'activa':
            return 'default';
        case 'finalizada':
            return 'outline';
        default:
            return 'secondary';
    }
};

// Formatear fechas
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Gestión de Votaciones" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Gestión de Votaciones</h1>
                    <p class="text-muted-foreground">
                        Administra todas las votaciones del sistema
                    </p>
                </div>
                <Button as-child>
                    <Link :href="route('admin.votaciones.create')">
                        <Plus class="mr-2 h-4 w-4" />
                        Nueva Votación
                    </Link>
                </Button>
            </div>

            <!-- Advanced Filters -->
            <AdvancedFilters
                :config="filterConfig"
                :route="route('admin.votaciones.index')"
                :initial-filters="{
                    quickSearch: filters.search,
                    rootGroup: filters.advanced_filters ? JSON.parse(filters.advanced_filters) : undefined
                }"
            />

            <!-- Tabla de Votaciones -->
            <div class="relative min-h-[50vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <Card class="border-0 shadow-none h-full">
                    <CardContent class="pt-6">
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Título</TableHead>
                                    <TableHead>Categoría</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Activar/Desactivar</TableHead>
                                    <TableHead>Fecha Inicio</TableHead>
                                    <TableHead>Fecha Fin</TableHead>
                                    <TableHead>Votantes</TableHead>
                                    <TableHead class="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="props.votaciones.data.length === 0">
                                    <TableCell :colspan="8" class="text-center py-8 text-muted-foreground">
                                        No se encontraron votaciones
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="votacion in props.votaciones.data" :key="votacion.id">
                                    <TableCell>
                                        <div>
                                            <p class="font-medium">{{ votacion.titulo }}</p>
                                            <p v-if="votacion.descripcion" class="text-sm text-muted-foreground">
                                                {{ votacion.descripcion?.substring(0, 60) }}{{ votacion.descripcion?.length > 60 ? '...' : '' }}
                                            </p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline">
                                            {{ votacion.categoria.nombre }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getEstadoBadgeVariant(votacion.estado)">
                                            {{ votacion.estado }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-3">
                                            <!-- Toggle para borrador <-> activa -->
                                            <div v-if="votacion.estado !== 'finalizada'" class="flex items-center gap-2">
                                                <AlertDialog>
                                                    <AlertDialogTrigger as-child>
                                                        <Switch 
                                                            :checked="votacion.estado === 'activa'" 
                                                            :disabled="false"
                                                        />
                                                    </AlertDialogTrigger>
                                                    <AlertDialogContent>
                                                        <AlertDialogHeader>
                                                            <AlertDialogTitle>
                                                                {{ votacion.estado === 'activa' ? 'Desactivar votación' : 'Activar votación' }}
                                                            </AlertDialogTitle>
                                                            <AlertDialogDescription>
                                                                <span v-if="votacion.estado === 'activa'">
                                                                    La votación "{{ votacion.titulo }}" será desactivada y volverá al estado borrador.
                                                                    Los votantes no podrán acceder hasta que la actives nuevamente.
                                                                </span>
                                                                <span v-else>
                                                                    La votación "{{ votacion.titulo }}" será activada y los votantes podrán empezar a votar.
                                                                    Asegúrate de que la configuración esté completa.
                                                                </span>
                                                            </AlertDialogDescription>
                                                        </AlertDialogHeader>
                                                        <AlertDialogFooter>
                                                            <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                                            <AlertDialogAction @click="toggleVotacionStatus(votacion)">
                                                                {{ votacion.estado === 'activa' ? 'Desactivar' : 'Activar' }}
                                                            </AlertDialogAction>
                                                        </AlertDialogFooter>
                                                    </AlertDialogContent>
                                                </AlertDialog>
                                                <span class="text-xs text-muted-foreground">
                                                    {{ votacion.estado === 'activa' ? 'Activa' : 'Inactiva' }}
                                                </span>
                                            </div>
                                            
                                            <!-- Botón finalizar para votaciones activas -->
                                            <div v-if="votacion.estado === 'activa'" class="ml-2">
                                                <AlertDialog>
                                                    <AlertDialogTrigger as-child>
                                                        <Button variant="outline" size="sm" class="text-orange-600 hover:text-orange-700">
                                                            Finalizar
                                                        </Button>
                                                    </AlertDialogTrigger>
                                                    <AlertDialogContent>
                                                        <AlertDialogHeader>
                                                            <AlertDialogTitle>Finalizar votación</AlertDialogTitle>
                                                            <AlertDialogDescription>
                                                                La votación "{{ votacion.titulo }}" será finalizada permanentemente.
                                                                Esta acción no se puede deshacer y los votantes ya no podrán votar.
                                                            </AlertDialogDescription>
                                                        </AlertDialogHeader>
                                                        <AlertDialogFooter>
                                                            <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                                            <AlertDialogAction 
                                                                @click="finalizarVotacion(votacion)"
                                                                class="bg-orange-600 text-white hover:bg-orange-700"
                                                            >
                                                                Finalizar
                                                            </AlertDialogAction>
                                                        </AlertDialogFooter>
                                                    </AlertDialogContent>
                                                </AlertDialog>
                                            </div>
                                            
                                            <!-- Mensaje para votaciones finalizadas -->
                                            <div v-if="votacion.estado === 'finalizada'" class="text-xs text-muted-foreground">
                                                Finalizada
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {{ formatDate(votacion.fecha_inicio) }}
                                    </TableCell>
                                    <TableCell>
                                        {{ formatDate(votacion.fecha_fin) }}
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-1">
                                            <Users class="h-4 w-4 text-muted-foreground" />
                                            <span>{{ votacion.votantes_count || 0 }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="viewImportHistory(votacion.id)"
                                                title="Ver historial de importaciones"
                                            >
                                                <FileText class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                v-if="votacion.resultados_publicos"
                                                variant="ghost"
                                                size="sm"
                                                @click="verResultados(votacion.id)"
                                                title="Ver resultados"
                                                class="text-blue-600 hover:text-blue-700"
                                            >
                                                <BarChart3 class="h-4 w-4" />
                                            </Button>
                                            <Button 
                                                v-if="votacion.estado === 'borrador'" 
                                                variant="ghost" 
                                                size="sm" 
                                                as-child
                                            >
                                                <Link :href="route('admin.votaciones.edit', votacion.id)">
                                                    <Edit class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <AlertDialog v-if="votacion.estado === 'borrador'">
                                                <AlertDialogTrigger as-child>
                                                    <Button variant="ghost" size="sm">
                                                        <Trash2 class="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>¿Eliminar votación?</AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            Esta acción no se puede deshacer. Se eliminará permanentemente
                                                            la votación "{{ votacion.titulo }}" y todos sus datos asociados.
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                                        <AlertDialogAction
                                                            @click="deleteVotacion(votacion.id)"
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
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="props.votaciones.last_page > 1" class="flex items-center justify-between mt-4">
                        <p class="text-sm text-muted-foreground">
                            Mostrando {{ (props.votaciones.current_page - 1) * props.votaciones.per_page + 1 }} a 
                            {{ Math.min(props.votaciones.current_page * props.votaciones.per_page, props.votaciones.total) }} 
                            de {{ props.votaciones.total }} resultados
                        </p>
                        <div class="flex gap-2">
                            <Button
                                v-for="link in props.votaciones.links"
                                :key="link.label"
                                :variant="link.active ? 'default' : 'outline'"
                                :disabled="!link.url"
                                size="sm"
                                @click="link.url && router.get(link.url)"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>