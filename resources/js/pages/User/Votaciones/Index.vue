<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type BreadcrumbItemType } from '@/types';
import UserLayout from "@/layouts/UserLayout.vue";
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Vote, CheckCircle, Clock, AlertCircle, BarChart3, ChevronUp, ChevronDown, Loader2 } from 'lucide-vue-next';
import AdvancedFilters from '@/components/filters/AdvancedFilters.vue';
import VoteProcessingModal from '@/components/voting/VoteProcessingModal.vue';
import type { AdvancedFilterConfig } from '@/types/filters';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';

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
    vote_processing: boolean; // Indica si el voto está siendo procesado
    vote_status: string | null; // Estado específico del voto (pending, processing, completed, error)
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
    hasProcessingVotes: boolean; // Indica si hay votos en procesamiento
    // Props de permisos de usuario
    canVote: boolean;
    canViewResults: boolean;
    canViewOwnVote: boolean;
}

const props = defineProps<Props>();
const page = usePage();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Dashboard', href: '/miembro/dashboard' },
    { title: 'Mis Votaciones', href: '/miembro/votaciones' },
];

// Estado para el modal de procesamiento
const showProcessingModal = ref(false);
const processingVotacionId = ref<number | null>(null);
const processingCheckUrl = ref<string>('');

// Switch para mostrar pasadas
const mostrarPasadas = ref(props.mostrar_pasadas || false);

// Helper para obtener route
const { route } = window as any;

// Polling para votos en procesamiento
const pollingInterval = ref<NodeJS.Timeout | null>(null);
const isPolling = ref(false);

// Computed para los filtros iniciales del componente AdvancedFilters
// Esto asegura que sea reactivo cuando cambien los props
const initialFiltersForAdvanced = computed(() => ({
    quickSearch: props.filters.search || '',
    rootGroup: props.filters.advanced_filters ? JSON.parse(props.filters.advanced_filters) : undefined
}));

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
    router.get('/miembro/votaciones', {
        ...props.filters,
        mostrar_pasadas: mostrarPasadas.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

// Función para navegar a votar
const irAVotar = (votacionId: number) => {
    router.get(`/miembro/votaciones/${votacionId}/votar`);
};

// Función para ver mi voto
const verMiVoto = (votacionId: number) => {
    // Por ahora redirige a una página placeholder
    // TODO: Implementar página de ver voto
    router.get(`/miembro/votaciones/${votacionId}/mi-voto`);
};

// Función para ver resultados
const verResultados = (votacionId: number) => {
    router.get(`/miembro/votaciones/${votacionId}/resultados`);
};

// Función para obtener el ícono según el estado de participación
const getStatusIcon = (votacion: Votacion) => {
    if (votacion.vote_processing) {
        return Loader2;
    }
    
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
    if (votacion.vote_processing) {
        return 'outline'; // Procesando
    }
    
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
    if (votacion.vote_processing) {
        return 'Firmando...';
    }
    
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

// Función para verificar el estado de los votos en procesamiento
const checkProcessingVotes = async () => {
    if (!props.hasProcessingVotes && !isPolling.value) {
        return;
    }
    
    // Verificar el estado de cada votación que está procesando
    const processingVotaciones = props.votaciones.data.filter(v => v.vote_processing);
    
    if (processingVotaciones.length === 0) {
        stopPolling();
        return;
    }
    
    try {
        // Verificar el estado de cada voto en procesamiento
        const checkPromises = processingVotaciones.map(votacion => 
            axios.get(route('user.votaciones.check-status', votacion.id))
        );
        
        const responses = await Promise.all(checkPromises);
        
        // Si algún voto se completó, recargar la página
        const hasCompleted = responses.some(r => r.data.completed);
        if (hasCompleted) {
            router.reload({
                only: ['votaciones', 'hasProcessingVotes'],
                preserveScroll: true,
                onSuccess: () => {
                    const completedCount = responses.filter(r => r.data.completed).length;
                    toast.success(`${completedCount} voto(s) procesado(s) exitosamente`, {
                        duration: 5000,
                    });
                }
            });
        }
    } catch (error) {
        console.error('Error verificando votos en procesamiento:', error);
    }
};

// Iniciar polling
const startPolling = () => {
    if (isPolling.value) return;
    
    isPolling.value = true;
    // Verificar cada 2 segundos
    pollingInterval.value = setInterval(checkProcessingVotes, 2000);
    
    // Primera verificación inmediata
    checkProcessingVotes();
};

// Detener polling
const stopPolling = () => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
        pollingInterval.value = null;
    }
    isPolling.value = false;
};

// Verificar si hay un voto en procesamiento al cargar la página
onMounted(() => {
    // Revisar si venimos de procesar un voto
    const flash = page.props.flash as any;
    if (flash?.processing_vote) {
        processingVotacionId.value = flash.processing_vote.votacion_id;
        processingCheckUrl.value = flash.processing_vote.check_status_url;
        showProcessingModal.value = true;
    }
    
    // Iniciar polling si hay votos en procesamiento
    if (props.hasProcessingVotes) {
        startPolling();
    }
    
    // Mostrar mensajes flash
    if (flash?.info) {
        toast.info(flash.info, {
            description: 'Tu voto está siendo firmado digitalmente',
            duration: 5000,
        });
    }
    
    if (flash?.success) {
        toast.success(flash.success, {
            duration: 5000,
        });
    }
    
    if (flash?.error) {
        toast.error(flash.error, {
            duration: 5000,
        });
    }
});

// Limpiar al desmontar el componente
onBeforeUnmount(() => {
    stopPolling();
});

// Manejar cuando el voto se completa
const handleVoteCompleted = (data: any) => {
    showProcessingModal.value = false;
    
    // Recargar la página para actualizar el estado
    router.reload({
        only: ['votaciones'],
        onSuccess: () => {
            toast.success('¡Voto registrado!', {
                description: 'Tu voto ha sido procesado exitosamente.',
                duration: 5000,
            });
        }
    });
};

// Manejar errores en el procesamiento
const handleVoteError = (data: any) => {
    showProcessingModal.value = false;
    
    toast.error('Error al procesar voto', {
        description: data.message || 'Hubo un problema al procesar tu voto. Por favor, intenta nuevamente.',
        duration: 7000,
    });
};
</script>

<template>
    <Head title="Mis Votaciones" />

    <UserLayout :breadcrumbs="breadcrumbs">
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
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 mb-4">
                <AdvancedFilters
                    :config="filterConfig"
                    :route="route('user.votaciones.index')"
                    :initial-filters="initialFiltersForAdvanced"
                    class="w-full sm:flex-1"
                />
                <div class="flex items-center space-x-2 w-full sm:w-auto justify-end">
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

            <!-- Tabla de Votaciones (Desktop) / Cards (Mobile) -->
            <div class="relative min-h-[50vh] flex-1">
                <!-- Vista Mobile: Cards -->
                <div class="block sm:hidden space-y-4">
                    <div v-if="props.votaciones.data.length === 0" class="text-center py-8 text-muted-foreground">
                        {{ mostrarPasadas ? 'No tienes historial de votaciones' : 'No tienes votaciones disponibles en este momento' }}
                    </div>
                    <Card 
                        v-for="votacion in sortedVotaciones" 
                        :key="votacion.id"
                        :class="{
                            'opacity-75': votacion.ha_finalizado,
                            'bg-green-50/50 dark:bg-green-950/10': votacion.ya_voto && !votacion.ha_finalizado,
                            'bg-orange-50/50 dark:bg-orange-950/10': votacion.vote_processing
                        }"
                    >
                        <CardContent class="p-4">
                            <!-- Encabezado del Card -->
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-base">{{ votacion.titulo }}</h3>
                                    <Badge variant="outline" class="mt-1">
                                        {{ votacion.categoria.nombre }}
                                    </Badge>
                                </div>
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
                            
                            <!-- Descripción si existe -->
                            <p v-if="votacion.descripcion" class="text-sm text-muted-foreground mb-3">
                                {{ votacion.descripcion?.substring(0, 100) }}{{ votacion.descripcion?.length > 100 ? '...' : '' }}
                            </p>
                            
                            <!-- Fechas -->
                            <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                                <div>
                                    <span class="text-muted-foreground">Apertura:</span>
                                    <div class="font-medium">{{ formatDate(votacion.fecha_inicio) }}</div>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Cierre:</span>
                                    <div class="font-medium">{{ formatDate(votacion.fecha_fin) }}</div>
                                </div>
                            </div>
                            
                            <!-- Estado del usuario -->
                            <div class="flex items-center justify-between mb-3 pb-3 border-b">
                                <div class="flex items-center gap-2">
                                    <component 
                                        :is="getStatusIcon(votacion)"
                                        class="h-4 w-4"
                                        :class="{
                                            'animate-spin text-orange-600': votacion.vote_processing,
                                            'text-green-600': votacion.ya_voto && !votacion.vote_processing,
                                            'text-orange-600': votacion.puede_votar && !votacion.vote_processing,
                                            'text-red-600': !votacion.puede_votar && !votacion.ya_voto && !votacion.vote_processing
                                        }"
                                    />
                                    <Badge :variant="getStatusBadgeVariant(votacion)">
                                        {{ getStatusText(votacion) }}
                                    </Badge>
                                </div>
                            </div>
                            
                            <!-- Acciones -->
                            <div class="flex flex-col gap-2">
                                <!-- Indicador de procesamiento -->
                                <div 
                                    v-if="votacion.vote_processing"
                                    class="flex items-center justify-center gap-2 px-3 py-2 rounded-md bg-orange-100 dark:bg-orange-900/20 text-orange-700 dark:text-orange-400 text-sm font-medium"
                                >
                                    <Loader2 class="h-4 w-4 animate-spin" />
                                    <span>Firmando digitalmente...</span>
                                </div>

                                <!-- Botones de acción -->
                                <Button 
                                    v-if="votacion.puede_votar && !votacion.vote_processing" 
                                    @click="irAVotar(votacion.id)"
                                    class="w-full"
                                >
                                    <Vote class="mr-2 h-4 w-4" />
                                    Votar
                                </Button>
                                
                                <Button 
                                    v-if="votacion.estado_visual === 'pendiente' && !votacion.vote_processing" 
                                    variant="outline"
                                    disabled
                                    class="w-full cursor-not-allowed"
                                >
                                    <Clock class="mr-2 h-4 w-4" />
                                    Próximamente
                                </Button>
                                
                                <Button 
                                    v-if="votacion.puede_ver_voto && !votacion.vote_processing" 
                                    @click="verMiVoto(votacion.id)"
                                    variant="outline"
                                    class="w-full"
                                >
                                    <CheckCircle class="mr-2 h-4 w-4" />
                                    Ver mi voto
                                </Button>
                                
                                <Button 
                                    v-if="votacion.resultados_visibles" 
                                    @click="verResultados(votacion.id)"
                                    variant="default"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white"
                                >
                                    <BarChart3 class="mr-2 h-4 w-4" />
                                    Ver Resultados
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Vista Desktop: Tabla -->
                <div class="hidden sm:block overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
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
                                                    'animate-spin text-orange-600': votacion.vote_processing,
                                                    'text-green-600': votacion.ya_voto && !votacion.vote_processing,
                                                    'text-orange-600': votacion.puede_votar && !votacion.vote_processing,
                                                    'text-red-600': !votacion.puede_votar && !votacion.ya_voto && !votacion.vote_processing
                                                }"
                                            />
                                            <Badge :variant="getStatusBadgeVariant(votacion)">
                                                {{ getStatusText(votacion) }}
                                            </Badge>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <!-- Indicador de procesamiento: cuando el voto está siendo procesado -->
                                            <div 
                                                v-if="votacion.vote_processing"
                                                class="flex items-center gap-2 px-3 py-1 rounded-md bg-orange-100 dark:bg-orange-900/20 text-orange-700 dark:text-orange-400 text-sm font-medium"
                                            >
                                                <Loader2 class="h-4 w-4 animate-spin" />
                                                <span>Firmando digitalmente...</span>
                                            </div>

                                            <!-- Botón Votar: solo para votaciones activas donde el usuario puede votar -->
                                            <Button 
                                                v-if="votacion.puede_votar && !votacion.vote_processing" 
                                                @click="irAVotar(votacion.id)"
                                                size="sm"
                                            >
                                                <Vote class="mr-2 h-4 w-4" />
                                                Votar
                                            </Button>
                                            
                                            <!-- Botón Próximamente: para votaciones activas que aún no han abierto -->
                                            <Button 
                                                v-if="votacion.estado_visual === 'pendiente' && !votacion.vote_processing" 
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
                                                v-if="votacion.puede_ver_voto && !votacion.vote_processing" 
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
                    </CardContent>
                </Card>
                </div>
            </div>
            
            <!-- Paginación (para ambas vistas) -->
            <div v-if="props.votaciones.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between mt-4 gap-4">
                <p class="text-sm text-muted-foreground text-center sm:text-left">
                    Mostrando {{ (props.votaciones.current_page - 1) * props.votaciones.per_page + 1 }} a 
                    {{ Math.min(props.votaciones.current_page * props.votaciones.per_page, props.votaciones.total) }} 
                    de {{ props.votaciones.total }} resultados
                </p>
                <div class="flex gap-2 flex-wrap justify-center">
                    <Button
                        v-for="link in props.votaciones.links"
                        :key="link.label"
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        :disabled="!link.url"
                        @click="link.url && router.visit(link.url)"
                        v-html="link.label"
                        class="min-w-[40px]"
                    />
                </div>
            </div>
        </div>
        
        <!-- Modal de procesamiento de voto -->
        <VoteProcessingModal
            v-if="showProcessingModal && processingVotacionId && processingCheckUrl"
            v-model="showProcessingModal"
            :votacion-id="processingVotacionId"
            :check-status-url="processingCheckUrl"
            @completed="handleVoteCompleted"
            @error="handleVoteError"
        />
    </UserLayout>
</template>