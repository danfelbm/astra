<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Vote, CheckCircle, Clock, AlertCircle, BarChart3, ChevronUp, ChevronDown } from 'lucide-vue-next';
import AdvancedFilters from '@/components/filters/AdvancedFilters.vue';
import type { AdvancedFilterConfig } from '@/types/filters';
import { ref, computed } from 'vue';

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
    created_at: string;
    votantes_count?: number;
    ya_voto: boolean;
    puede_votar: boolean;
    ha_finalizado: boolean;
    puede_ver_voto: boolean;
    resultados_visibles: boolean;
    estado_visual: 'activa' | 'finalizada' | 'pendiente' | 'inactiva';
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
        search?: string;
        advanced_filters?: string;
        mostrar_pasadas?: boolean;
    };
    mostrar_pasadas: boolean;
    filterFieldsConfig: any[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Mis Votaciones', href: '/votaciones' },
];

// Switch para mostrar pasadas
const mostrarPasadas = ref(props.mostrar_pasadas || false);

// Helper para obtener route
const { route } = window as any;

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

// Estado del sorting
const sortColumn = ref<keyof Votacion | 'fecha_inicio'>('fecha_inicio');
const sortDirection = ref<'asc' | 'desc'>('asc');

// Función para cambiar el sorting
const handleSort = (column: keyof Votacion | 'fecha_inicio') => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
};

// Computed para votaciones ordenadas
const sortedVotaciones = computed(() => {
    const votaciones = [...props.votaciones.data];
    
    return votaciones.sort((a, b) => {
        let aValue: any;
        let bValue: any;
        
        switch (sortColumn.value) {
            case 'fecha_inicio':
                aValue = new Date(a.fecha_inicio);
                bValue = new Date(b.fecha_inicio);
                break;
            case 'fecha_fin':
                aValue = new Date(a.fecha_fin);
                bValue = new Date(b.fecha_fin);
                break;
            case 'titulo':
                aValue = a.titulo.toLowerCase();
                bValue = b.titulo.toLowerCase();
                break;
            default:
                aValue = a[sortColumn.value];
                bValue = b[sortColumn.value];
        }
        
        if (aValue < bValue) {
            return sortDirection.value === 'asc' ? -1 : 1;
        }
        if (aValue > bValue) {
            return sortDirection.value === 'asc' ? 1 : -1;
        }
        return 0;
    });
});

// Manejar cambio de mostrar pasadas
const handleMostrarPasadasChange = () => {
    router.get('/votaciones', {
        ...props.filters,
        mostrar_pasadas: mostrarPasadas.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

// Función para navegar a votar
const irAVotar = (votacionId: number) => {
    router.get(`/votaciones/${votacionId}/votar`);
};

// Función para ver mi voto
const verMiVoto = (votacionId: number) => {
    // Por ahora redirige a una página placeholder
    // TODO: Implementar página de ver voto
    router.get(`/votaciones/${votacionId}/mi-voto`);
};

// Función para ver resultados
const verResultados = (votacionId: number) => {
    router.get(`/votaciones/${votacionId}/resultados`);
};

// Función para obtener el ícono según el estado de participación
const getStatusIcon = (votacion: Votacion) => {
    if (votacion.ya_voto) {
        return CheckCircle;
    }
    
    if (votacion.ha_finalizado) {
        return AlertCircle;
    }
    
    return Clock;
};

// Función para obtener el color del badge según el estado de participación
const getStatusBadgeVariant = (votacion: Votacion) => {
    if (votacion.ya_voto) {
        return 'default'; // Verde
    }
    
    if (votacion.ha_finalizado) {
        return 'destructive'; // Rojo
    }
    
    return 'secondary'; // Amarillo/gris
};

// Función para obtener el texto del estado
const getStatusText = (votacion: Votacion) => {
    if (votacion.ya_voto) {
        return votacion.ha_finalizado ? 'Voté (Finalizada)' : 'Ya voté';
    }
    
    if (votacion.ha_finalizado) {
        return 'Expirada';
    }
    
    return 'Disponible';
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
    <Head title="Mis Votaciones" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Mis Votaciones</h1>
                    <p class="text-muted-foreground">
                        Votaciones disponibles para mi participación
                    </p>
                </div>
            </div>

            <!-- Filtros Avanzados -->
            <div class="flex items-center gap-4 mb-4">
                <AdvancedFilters
                    :config="filterConfig"
                    :route="route('votaciones.index')"
                    :initial-filters="{
                        quickSearch: filters.search,
                        rootGroup: filters.advanced_filters ? JSON.parse(filters.advanced_filters) : undefined
                    }"
                    class="flex-1"
                />
                <div class="flex items-center space-x-2">
                    <Switch 
                        id="mostrar-pasadas" 
                        v-model="mostrarPasadas" 
                        @update:model-value="handleMostrarPasadasChange"
                    />
                    <label for="mostrar-pasadas" class="text-sm font-medium whitespace-nowrap">
                        Ver historial
                    </label>
                </div>
            </div>

            <!-- Tabla de Votaciones -->
            <div class="relative min-h-[50vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <Card class="border-0 shadow-none h-full">
                    <CardContent class="pt-6">
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead 
                                        class="cursor-pointer select-none hover:bg-muted/50"
                                        @click="handleSort('titulo')"
                                    >
                                        <div class="flex items-center gap-1">
                                            Título
                                            <div class="flex flex-col">
                                                <ChevronUp 
                                                    class="h-3 w-3" 
                                                    :class="{ 'text-primary': sortColumn === 'titulo' && sortDirection === 'asc' }"
                                                />
                                                <ChevronDown 
                                                    class="h-3 w-3 -mt-1" 
                                                    :class="{ 'text-primary': sortColumn === 'titulo' && sortDirection === 'desc' }"
                                                />
                                            </div>
                                        </div>
                                    </TableHead>
                                    <TableHead>Categoría</TableHead>
                                    <TableHead 
                                        class="cursor-pointer select-none hover:bg-muted/50"
                                        @click="handleSort('fecha_inicio')"
                                    >
                                        <div class="flex items-center gap-1">
                                            Fecha de Apertura
                                            <div class="flex flex-col">
                                                <ChevronUp 
                                                    class="h-3 w-3" 
                                                    :class="{ 'text-primary': sortColumn === 'fecha_inicio' && sortDirection === 'asc' }"
                                                />
                                                <ChevronDown 
                                                    class="h-3 w-3 -mt-1" 
                                                    :class="{ 'text-primary': sortColumn === 'fecha_inicio' && sortDirection === 'desc' }"
                                                />
                                            </div>
                                        </div>
                                    </TableHead>
                                    <TableHead 
                                        class="cursor-pointer select-none hover:bg-muted/50"
                                        @click="handleSort('fecha_fin')"
                                    >
                                        <div class="flex items-center gap-1">
                                            Fecha Límite
                                            <div class="flex flex-col">
                                                <ChevronUp 
                                                    class="h-3 w-3" 
                                                    :class="{ 'text-primary': sortColumn === 'fecha_fin' && sortDirection === 'asc' }"
                                                />
                                                <ChevronDown 
                                                    class="h-3 w-3 -mt-1" 
                                                    :class="{ 'text-primary': sortColumn === 'fecha_fin' && sortDirection === 'desc' }"
                                                />
                                            </div>
                                        </div>
                                    </TableHead>
                                    <TableHead>Mi Estado</TableHead>
                                    <TableHead class="text-right">Acción</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="props.votaciones.data.length === 0">
                                    <TableCell :colspan="6" class="text-center py-8 text-muted-foreground">
                                        {{ mostrarPasadas ? 'No tienes historial de votaciones' : 'No tienes votaciones disponibles en este momento' }}
                                    </TableCell>
                                </TableRow>
                                <TableRow 
                                    v-for="votacion in sortedVotaciones" 
                                    :key="votacion.id"
                                    :class="{
                                        'opacity-75': votacion.ha_finalizado,
                                        'bg-green-50/50 dark:bg-green-950/10': votacion.ya_voto && !votacion.ha_finalizado,
                                        'bg-blue-50/50 dark:bg-blue-950/10': votacion.puede_votar
                                    }"
                                >
                                    <TableCell>
                                        <div>
                                            <div class="flex items-center gap-2 mb-1">
                                                <p class="font-medium">{{ votacion.titulo }}</p>
                                                <Badge 
                                                    v-if="votacion.estado_visual === 'finalizada'" 
                                                    variant="secondary"
                                                    class="text-xs"
                                                >
                                                    Finalizada
                                                </Badge>
                                                <Badge 
                                                    v-else-if="votacion.estado_visual === 'activa'" 
                                                    variant="default"
                                                    class="text-xs"
                                                >
                                                    Activa
                                                </Badge>
                                                <Badge 
                                                    v-else-if="votacion.estado_visual === 'pendiente'" 
                                                    variant="outline"
                                                    class="text-xs"
                                                >
                                                    Pendiente
                                                </Badge>
                                            </div>
                                            <p v-if="votacion.descripcion" class="text-sm text-muted-foreground">
                                                {{ votacion.descripcion?.substring(0, 80) }}{{ votacion.descripcion?.length > 80 ? '...' : '' }}
                                            </p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline">
                                            {{ votacion.categoria.nombre }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm">
                                            {{ formatDate(votacion.fecha_inicio) }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm">
                                            {{ formatDate(votacion.fecha_fin) }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <component 
                                                :is="getStatusIcon(votacion)"
                                                class="h-4 w-4"
                                                :class="{
                                                    'text-green-600': votacion.ya_voto,
                                                    'text-orange-600': votacion.puede_votar,
                                                    'text-red-600': !votacion.puede_votar && !votacion.ya_voto
                                                }"
                                            />
                                            <Badge :variant="getStatusBadgeVariant(votacion)">
                                                {{ getStatusText(votacion) }}
                                            </Badge>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <!-- Botón Votar: solo para votaciones activas donde el usuario puede votar -->
                                            <Button 
                                                v-if="votacion.puede_votar" 
                                                @click="irAVotar(votacion.id)"
                                                size="sm"
                                            >
                                                <Vote class="mr-2 h-4 w-4" />
                                                Votar
                                            </Button>
                                            
                                            <!-- Botón Próximamente: para votaciones activas que aún no han abierto -->
                                            <Button 
                                                v-if="votacion.estado_visual === 'pendiente'" 
                                                variant="outline"
                                                size="sm"
                                                disabled
                                                class="cursor-not-allowed"
                                            >
                                                <Clock class="mr-2 h-4 w-4" />
                                                Próximamente
                                            </Button>
                                            
                                            <!-- Botón Ver mi voto: para votaciones donde el usuario ya votó -->
                                            <Button 
                                                v-if="votacion.puede_ver_voto" 
                                                @click="verMiVoto(votacion.id)"
                                                variant="outline"
                                                size="sm"
                                            >
                                                <CheckCircle class="mr-2 h-4 w-4" />
                                                Ver mi voto
                                            </Button>
                                            
                                            <!-- Botón Ver Resultados: para votaciones con resultados públicos visibles -->
                                            <Button 
                                                v-if="votacion.resultados_visibles" 
                                                @click="verResultados(votacion.id)"
                                                variant="default"
                                                size="sm"
                                                class="bg-blue-600 hover:bg-blue-700 text-white"
                                            >
                                                <BarChart3 class="mr-2 h-4 w-4" />
                                                Ver Resultados
                                            </Button>
                                            
                                            <!-- Estado para votaciones finalizadas sin participación -->
                                            <Button 
                                                v-if="votacion.ha_finalizado && !votacion.ya_voto" 
                                                variant="outline"
                                                size="sm"
                                                disabled
                                            >
                                                <AlertCircle class="mr-2 h-4 w-4" />
                                                No participé
                                            </Button>
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