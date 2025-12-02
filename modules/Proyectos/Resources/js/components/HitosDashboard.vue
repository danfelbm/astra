<script setup lang="ts">
/**
 * HitosDashboard - Componente master-detail para visualización de hitos
 *
 * Layout con sidebar de hitos y panel principal con detalles + entregables.
 * Responsive: En móvil usa Select dropdown, en desktop usa sidebar.
 */
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';

// Componentes UI
import { Card, CardContent, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Progress } from '@modules/Core/Resources/js/components/ui/progress';
import { ScrollArea } from '@modules/Core/Resources/js/components/ui/scroll-area';
import { Separator } from '@modules/Core/Resources/js/components/ui/separator';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@modules/Core/Resources/js/components/ui/select';

// Iconos
import {
    Target,
    Calendar,
    Clock,
    User,
    Edit,
    Trash2,
    Plus,
    ChevronRight,
    ListTodo,
    ExternalLink,
    FolderOpen,
    MessageSquare,
    Activity,
} from 'lucide-vue-next';

// Inertia Link
import { Link } from '@inertiajs/vue3';

// Componentes del módulo
import { HitosEntregablesPanel } from './HitosDashboard';
import HitoDetallesModal from './HitosDashboard/HitoDetallesModal.vue';
import EntregableDetallesModal from './HitosDashboard/EntregableDetallesModal.vue';

// Tipos
import type { Hito, Entregable } from '@modules/Proyectos/Resources/js/types/hitos';

// Props
interface ProyectoOption {
    id: number;
    nombre: string;
}

interface Props {
    hitos: Hito[];
    proyectoId?: number; // Opcional cuando hay múltiples proyectos
    // Filtro por proyecto (para vista de múltiples proyectos)
    proyectos?: ProyectoOption[];
    selectedProyectoId?: number | null;
    // Permisos
    canEdit?: boolean;
    canDelete?: boolean;
    canManageDeliverables?: boolean;
    canComplete?: boolean;
    // UI
    showViewDetail?: boolean; // Mostrar botón "Ver Detalle Completo"
    showProyectoInHito?: boolean; // Mostrar nombre del proyecto en cada hito
    // URL base para navegación
    baseUrl?: string;
}

const props = withDefaults(defineProps<Props>(), {
    canEdit: false,
    canDelete: false,
    canManageDeliverables: false,
    canComplete: false,
    showViewDetail: true,
    showProyectoInHito: false,
    baseUrl: '/admin/proyectos',
});

import type { UploadedFile } from '@modules/Comentarios/Resources/js/types/comentarios';

// Emits
const emit = defineEmits<{
    'select-hito': [hitoId: number];
    'edit-hito': [hito: Hito];
    'delete-hito': [hito: Hito];
    'view-hito': [hito: Hito];
    'add-entregable': [hito: Hito];
    'view-entregable': [entregable: Entregable, hito: Hito];
    'complete-entregable': [entregable: Entregable, observaciones: string, archivos: UploadedFile[]];
    'update-entregable-status': [entregable: Entregable, estado: string, observaciones: string, archivos: UploadedFile[]];
    'edit-entregable': [entregable: Entregable, hito: Hito];
    'delete-entregable': [entregable: Entregable, hito: Hito];
    'filter-proyecto': [proyectoId: number | null];
}>();

// Filtro de proyecto reactivo ("_all" = todos los proyectos)
const proyectoFilter = ref<string>(props.selectedProyectoId?.toString() || '_all');

// Hitos filtrados por proyecto
const filteredHitos = computed(() => {
    if (proyectoFilter.value === '_all') {
        return props.hitos;
    }
    const proyectoId = parseInt(proyectoFilter.value, 10);
    return props.hitos.filter(h => h.proyecto_id === proyectoId);
});

// Handler para cambio de filtro de proyecto
const handleProyectoFilter = (value: string) => {
    proyectoFilter.value = value;
    selectedHitoId.value = null; // Resetear hito seleccionado
    const proyectoId = value !== '_all' ? parseInt(value, 10) : null;
    emit('filter-proyecto', proyectoId);
};

// Responsive
const isMobile = useMediaQuery('(max-width: 768px)');

// Estado - Leer hito inicial de URL
const getInitialHitoId = (): number | null => {
    const urlParams = new URLSearchParams(window.location.search);
    const hitoParam = urlParams.get('hito');
    return hitoParam ? parseInt(hitoParam, 10) : null;
};

// Leer parámetros de modal de URL para deeplink
// Usa 'modal_tab' para evitar conflictos con el 'tab' de la página principal
const getInitialModalState = (): {
    type: 'hito' | 'entregable' | null;
    entregableId?: number;
    tab: string;
} => {
    const urlParams = new URLSearchParams(window.location.search);
    const modalParam = urlParams.get('modal');
    const tabParam = urlParams.get('modal_tab') || 'detalles';

    if (modalParam === 'hito') {
        return { type: 'hito', tab: tabParam };
    } else if (modalParam?.startsWith('entregable_')) {
        const id = parseInt(modalParam.replace('entregable_', ''), 10);
        return { type: 'entregable', entregableId: id, tab: tabParam };
    }
    return { type: null, tab: 'detalles' };
};

const selectedHitoId = ref<number | null>(getInitialHitoId());
const initialModalState = getInitialModalState();

// Computed
const selectedHito = computed(() =>
    filteredHitos.value.find(h => h.id === selectedHitoId.value) ?? null
);

// Proyecto ID efectivo (de prop o del hito seleccionado)
const effectiveProyectoId = computed(() => {
    if (props.proyectoId) return props.proyectoId;
    if (selectedHito.value) return selectedHito.value.proyecto_id;
    return null;
});

// URL para ver todos los hitos del proyecto (solo admin)
const hitosIndexUrl = computed(() => {
    if (!effectiveProyectoId.value) return null;
    return `${props.baseUrl}/${effectiveProyectoId.value}/hitos`;
});

// URL para ver todos los entregables del hito (solo admin)
const entregablesUrl = computed(() => {
    if (!selectedHito.value || !effectiveProyectoId.value) return null;
    return `${props.baseUrl}/${effectiveProyectoId.value}/hitos/${selectedHito.value.id}/entregables`;
});

// Sincronizar selección con URL
watch(selectedHitoId, (newId) => {
    if (newId) {
        const currentPath = window.location.pathname;
        const currentParams = new URLSearchParams(window.location.search);
        currentParams.set('hito', String(newId));
        const url = `${currentPath}?${currentParams.toString()}`;
        router.get(url, {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: []
        });
        emit('select-hito', newId);
    }
});

// Métodos
const selectHito = (id: number) => {
    selectedHitoId.value = id;
};

// Para el Select de móvil que usa strings
const handleMobileSelect = (value: string) => {
    const id = parseInt(value, 10);
    if (!isNaN(id)) {
        selectHito(id);
    }
};

const formatDate = (date: string | null) => {
    if (!date) return 'Sin fecha';
    return format(new Date(date), 'dd MMM yyyy', { locale: es });
};

const getEstadoColor = (estado: string) => {
    const colors: Record<string, string> = {
        pendiente: 'bg-gray-100 text-gray-800',
        en_progreso: 'bg-blue-100 text-blue-800',
        completado: 'bg-green-100 text-green-800',
        cancelado: 'bg-red-100 text-red-800',
    };
    return colors[estado] || 'bg-gray-100 text-gray-800';
};

const getEstadoDot = (estado: string) => {
    const colors: Record<string, string> = {
        pendiente: 'bg-gray-400',
        en_progreso: 'bg-blue-500 animate-pulse',
        completado: 'bg-green-500',
        cancelado: 'bg-red-500',
    };
    return colors[estado] || 'bg-gray-400';
};

// Handlers de entregables
const handleViewEntregable = (entregable: Entregable) => {
    if (selectedHito.value) {
        emit('view-entregable', entregable, selectedHito.value);
    }
};

const handleCompleteEntregable = (entregable: Entregable, observaciones: string, archivos: UploadedFile[]) => {
    emit('complete-entregable', entregable, observaciones, archivos);
};

const handleUpdateEntregableStatus = (entregable: Entregable, estado: string, observaciones: string, archivos: UploadedFile[]) => {
    emit('update-entregable-status', entregable, estado, observaciones, archivos);
};

const handleEditEntregable = (entregable: Entregable) => {
    if (selectedHito.value) {
        emit('edit-entregable', entregable, selectedHito.value);
    }
};

const handleDeleteEntregable = (entregable: Entregable) => {
    if (selectedHito.value) {
        emit('delete-entregable', entregable, selectedHito.value);
    }
};

// Handlers de hito
const handleEditHito = () => {
    if (selectedHito.value) {
        emit('edit-hito', selectedHito.value);
    }
};

const handleDeleteHito = () => {
    if (selectedHito.value) {
        emit('delete-hito', selectedHito.value);
    }
};

const handleAddEntregable = () => {
    if (selectedHito.value) {
        emit('add-entregable', selectedHito.value);
    }
};

const handleViewHito = () => {
    if (selectedHito.value) {
        // Abrir modal de hito en tab detalles
        hitoModalTab.value = 'detalles';
        showHitoModal.value = true;
    }
};

// Estado del modal de hito (inicializar desde URL si hay deeplink)
const showHitoModal = ref(initialModalState.type === 'hito');
const hitoModalTab = ref(initialModalState.type === 'hito' ? initialModalState.tab : 'detalles');

// Estado del modal de entregable (inicializar desde URL si hay deeplink)
const showEntregableModal = ref(initialModalState.type === 'entregable');
const entregableModalId = ref<number | null>(initialModalState.entregableId || null);
const entregableModalTab = ref(initialModalState.type === 'entregable' ? initialModalState.tab : 'detalles');

// Función para actualizar URL con parámetros del modal
// Usa 'modal_tab' para evitar conflictos con el 'tab' de la página
const updateModalUrl = (
    type: 'hito' | 'entregable' | null,
    entregableId?: number,
    tab?: string
) => {
    const currentPath = window.location.pathname;
    const currentParams = new URLSearchParams(window.location.search);

    if (type === 'hito') {
        currentParams.set('modal', 'hito');
        if (tab) currentParams.set('modal_tab', tab);
    } else if (type === 'entregable' && entregableId) {
        currentParams.set('modal', `entregable_${entregableId}`);
        if (tab) currentParams.set('modal_tab', tab);
    } else {
        // Limpiar parámetros del modal al cerrar (no tocar 'tab' de la página)
        currentParams.delete('modal');
        currentParams.delete('modal_tab');
        currentParams.delete('pagina');
    }

    const url = `${currentPath}?${currentParams.toString()}`;
    router.get(url, {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: []
    });
};

// Watch para sincronizar modal de hito con URL
watch([showHitoModal, hitoModalTab], ([isOpen, tab]) => {
    if (isOpen) {
        updateModalUrl('hito', undefined, tab);
    } else {
        // Solo limpiar si el modal de hito estaba abierto
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('modal') === 'hito') {
            updateModalUrl(null);
        }
    }
});

// Watch para sincronizar modal de entregable con URL
watch([showEntregableModal, entregableModalTab], ([isOpen, tab]) => {
    if (isOpen && entregableModalId.value) {
        updateModalUrl('entregable', entregableModalId.value, tab);
    } else {
        // Solo limpiar si el modal de entregable estaba abierto
        const urlParams = new URLSearchParams(window.location.search);
        const modalParam = urlParams.get('modal');
        if (modalParam?.startsWith('entregable_')) {
            updateModalUrl(null);
        }
    }
});

// Handler para ver entregable en modal
const handleOpenEntregableModal = (entregable: Entregable, tab: string = 'detalles') => {
    entregableModalId.value = entregable.id;
    entregableModalTab.value = tab;
    showEntregableModal.value = true;
};

// Handler para abrir comentarios del hito en modal
const handleOpenHitoComentarios = () => {
    hitoModalTab.value = 'comentarios';
    showHitoModal.value = true;
};

// Handler para abrir actividad del hito en modal
const handleOpenHitoActividad = () => {
    hitoModalTab.value = 'actividad';
    showHitoModal.value = true;
};
</script>

<template>
    <div class="flex flex-col md:flex-row h-full min-h-[500px] overflow-hidden bg-background">
        <!-- Sidebar Desktop -->
        <aside class="hidden md:flex md:w-72 lg:w-80 flex-col border-r bg-muted/30">
            <!-- Header del sidebar -->
            <div class="p-4 border-b bg-background">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-sm text-muted-foreground uppercase tracking-wide flex items-center gap-2">
                        <Target class="h-4 w-4" />
                        Hitos ({{ filteredHitos.length }})
                    </h2>
                    <!-- Botón al index de hitos (solo admin) -->
                    <Link v-if="showViewDetail && hitosIndexUrl" :href="hitosIndexUrl">
                        <Button variant="outline" size="sm">
                            <ExternalLink class="h-3.5 w-3.5 mr-1" />
                            Ver todos
                        </Button>
                    </Link>
                </div>
                <!-- Filtro por proyecto (cuando hay múltiples proyectos) -->
                <div v-if="proyectos && proyectos.length > 0" class="mt-3">
                    <Select
                        :model-value="proyectoFilter"
                        @update:model-value="handleProyectoFilter"
                    >
                        <SelectTrigger class="w-full h-9 text-sm">
                            <SelectValue placeholder="Todos los proyectos" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="_all">Todos los proyectos</SelectItem>
                            <SelectItem
                                v-for="proyecto in proyectos"
                                :key="proyecto.id"
                                :value="proyecto.id.toString()"
                            >
                                {{ proyecto.nombre }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Lista de hitos -->
            <ScrollArea class="flex-1">
                <div class="p-2 space-y-1">
                    <!-- Estado vacío sidebar -->
                    <div v-if="filteredHitos.length === 0" class="p-4 text-center text-muted-foreground text-sm">
                        No hay hitos definidos
                    </div>

                    <!-- Items de hito -->
                    <button
                        v-for="hito in filteredHitos"
                        :key="hito.id"
                        :data-active="selectedHitoId === hito.id"
                        class="w-full text-left p-3 rounded-lg transition-colors duration-150
                               hover:bg-accent hover:text-accent-foreground
                               data-[active=true]:bg-primary/10
                               data-[active=true]:text-primary
                               data-[active=true]:border-l-2
                               data-[active=true]:border-primary
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        @click="selectHito(hito.id)"
                    >
                        <div class="flex items-center gap-2">
                            <div :class="getEstadoDot(hito.estado)" class="w-2 h-2 rounded-full flex-shrink-0" />
                            <span class="font-medium truncate flex-1">{{ hito.nombre }}</span>
                            <ChevronRight
                                v-if="selectedHitoId === hito.id"
                                class="h-4 w-4 flex-shrink-0 text-primary"
                            />
                        </div>
                        <!-- Nombre del proyecto (cuando showProyectoInHito es true) -->
                        <div v-if="showProyectoInHito && hito.proyecto" class="mt-1 ml-4 text-xs text-muted-foreground truncate">
                            {{ hito.proyecto.nombre }}
                        </div>
                        <div class="flex items-center gap-2 mt-2 ml-4 text-xs text-muted-foreground">
                            <Badge :class="getEstadoColor(hito.estado)" class="text-[10px] px-1.5 py-0">
                                {{ hito.estado_label || hito.estado }}
                            </Badge>
                            <span>{{ hito.porcentaje_completado }}%</span>
                        </div>
                        <!-- Mini progress bar -->
                        <div class="mt-2 ml-4">
                            <Progress :model-value="hito.porcentaje_completado" class="h-1" />
                        </div>
                    </button>
                </div>
            </ScrollArea>
        </aside>

        <!-- Panel Principal -->
        <main class="flex-1 flex flex-col min-h-0 min-w-0">
            <!-- Selector Móvil -->
            <div class="md:hidden mb-2 border-b bg-background sticky top-0 z-10 space-y-2">
                <!-- Filtro por proyecto móvil (cuando hay múltiples proyectos) -->
                <Select
                    v-if="proyectos && proyectos.length > 0"
                    :model-value="proyectoFilter"
                    @update:model-value="handleProyectoFilter"
                >
                    <SelectTrigger class="w-full h-9 text-sm">
                        <SelectValue placeholder="Todos los proyectos" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="_all">Todos los proyectos</SelectItem>
                        <SelectItem
                            v-for="proyecto in proyectos"
                            :key="proyecto.id"
                            :value="proyecto.id.toString()"
                        >
                            {{ proyecto.nombre }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <!-- Selector de hito -->
                <Select
                    :model-value="selectedHitoId?.toString() || ''"
                    @update:model-value="handleMobileSelect"
                >
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Selecciona un hito" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="hito in filteredHitos"
                            :key="hito.id"
                            :value="hito.id.toString()"
                        >
                            <div class="flex items-center gap-2">
                                <div :class="getEstadoDot(hito.estado)" class="w-2 h-2 rounded-full" />
                                <div class="flex flex-col">
                                    <span>{{ hito.nombre }}</span>
                                    <span v-if="showProyectoInHito && hito.proyecto" class="text-xs text-muted-foreground">
                                        {{ hito.proyecto.nombre }}
                                    </span>
                                </div>
                                <span class="text-muted-foreground">({{ hito.porcentaje_completado }}%)</span>
                            </div>
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Contenido -->
            <ScrollArea class="flex-1">
                <!-- Estado vacío -->
                <div v-if="!selectedHito" class="flex items-center justify-center h-full min-h-[400px]">
                    <div class="text-center space-y-3 max-w-sm p-6">
                        <Target class="h-16 w-16 mx-auto text-muted-foreground/50" />
                        <h3 class="font-medium text-lg">Selecciona un hito</h3>
                        <p class="text-sm text-muted-foreground">
                            Elige un hito de la lista para ver sus detalles y entregables
                        </p>
                    </div>
                </div>

                <!-- Detalle del Hito -->
                <div v-else class="p-0 md:p-6 space-y-6 overflow-x-hidden">
                    <!-- Card de información del hito -->
                    <Card>
                        <CardHeader class="pb-3">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div :class="getEstadoDot(selectedHito.estado)" class="w-2.5 h-2.5 rounded-full" />
                                        <CardTitle class="text-xl truncate">{{ selectedHito.nombre }}</CardTitle>
                                    </div>
                                    <!-- Nombre del proyecto (cuando showProyectoInHito es true) -->
                                    <div v-if="showProyectoInHito && selectedHito.proyecto" class="flex items-center gap-1.5 text-sm text-muted-foreground mt-1">
                                        <FolderOpen class="h-4 w-4" />
                                        <span>{{ selectedHito.proyecto.nombre }}</span>
                                    </div>
                                    <p v-if="selectedHito.descripcion" class="text-sm text-muted-foreground mt-2">
                                        {{ selectedHito.descripcion }}
                                    </p>
                                </div>
                                <Badge :class="getEstadoColor(selectedHito.estado)">
                                    {{ selectedHito.estado_label || selectedHito.estado }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Progreso -->
                            <div class="space-y-2">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-muted-foreground">Progreso</span>
                                    <span class="font-medium">{{ selectedHito.porcentaje_completado }}%</span>
                                </div>
                                <Progress :model-value="selectedHito.porcentaje_completado" class="h-2" />
                            </div>

                            <!-- Info adicional -->
                            <div class="flex flex-wrap gap-4 text-sm">
                                <div v-if="selectedHito.fecha_inicio" class="flex items-center gap-1.5 text-muted-foreground">
                                    <Calendar class="h-4 w-4" />
                                    <span>Inicio: {{ formatDate(selectedHito.fecha_inicio) }}</span>
                                </div>
                                <div v-if="selectedHito.fecha_fin" class="flex items-center gap-1.5 text-muted-foreground">
                                    <Clock class="h-4 w-4" />
                                    <span>Fin: {{ formatDate(selectedHito.fecha_fin) }}</span>
                                </div>
                                <div v-if="selectedHito.responsable" class="flex items-center gap-1.5 text-muted-foreground">
                                    <User class="h-4 w-4" />
                                    <span>{{ selectedHito.responsable.name }}</span>
                                </div>
                            </div>

                            <!-- Acciones del hito -->
                            <Separator />
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    v-if="canEdit"
                                    variant="outline"
                                    size="sm"
                                    @click="handleEditHito"
                                >
                                    <Edit class="h-4 w-4 mr-1.5" />
                                    Editar Hito
                                </Button>
                                <Button
                                    v-if="canManageDeliverables"
                                    variant="outline"
                                    size="sm"
                                    @click="handleAddEntregable"
                                >
                                    <Plus class="h-4 w-4 mr-1.5" />
                                    Añadir Entregable
                                </Button>
                                <Button
                                    v-if="showViewDetail"
                                    variant="ghost"
                                    size="sm"
                                    @click="handleViewHito"
                                >
                                    <ListTodo class="h-4 w-4 mr-1.5" />
                                    Ver detalles
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="handleOpenHitoComentarios"
                                >
                                    <MessageSquare class="h-4 w-4 mr-1.5" />
                                    Comentarios
                                    <Badge
                                        v-if="selectedHito.comentarios_count && selectedHito.comentarios_count > 0"
                                        variant="secondary"
                                        class="ml-1.5 text-xs"
                                    >
                                        {{ selectedHito.comentarios_count }}
                                    </Badge>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="handleOpenHitoActividad"
                                >
                                    <Activity class="h-4 w-4 mr-1.5" />
                                    Actividad
                                </Button>
                                <Button
                                    v-if="canDelete"
                                    variant="ghost"
                                    size="sm"
                                    class="text-red-600 hover:text-red-700 hover:bg-red-50"
                                    @click="handleDeleteHito"
                                >
                                    <Trash2 class="h-4 w-4 mr-1.5" />
                                    Eliminar
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Lista de Entregables -->
                    <div class="space-y-4 min-w-0">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold flex items-center gap-2">
                                <ListTodo class="h-5 w-5" />
                                Entregables
                                <Badge variant="secondary" class="ml-1">
                                    {{ selectedHito.entregables?.length || 0 }}
                                </Badge>
                            </h3>
                            <!-- Botón a la página completa de entregables (solo admin) -->
                            <Link v-if="showViewDetail && entregablesUrl" :href="entregablesUrl">
                                <Button variant="outline" size="sm">
                                    <ExternalLink class="h-3.5 w-3.5 mr-1" />
                                    Ver todos
                                </Button>
                            </Link>
                        </div>

                        <HitosEntregablesPanel
                            v-if="selectedHito.entregables && selectedHito.entregables.length > 0"
                            :entregables="selectedHito.entregables"
                            :can-edit="canEdit"
                            :can-delete="canDelete"
                            :can-complete="canComplete"
                            :initial-modal-entregable-id="initialModalState.type === 'entregable' ? initialModalState.entregableId : undefined"
                            :initial-modal-tab="initialModalState.type === 'entregable' ? initialModalState.tab : undefined"
                            @view="handleViewEntregable"
                            @complete="handleCompleteEntregable"
                            @update-status="handleUpdateEntregableStatus"
                            @edit="handleEditEntregable"
                            @delete="handleDeleteEntregable"
                            @open-modal="handleOpenEntregableModal"
                            @modal-tab-change="(id, tab) => { entregableModalId = id; entregableModalTab = tab; }"
                        />

                        <!-- Estado vacío de entregables -->
                        <div v-else class="text-center py-8 border rounded-lg bg-muted/30">
                            <ListTodo class="h-10 w-10 mx-auto text-muted-foreground/50 mb-3" />
                            <p class="text-muted-foreground text-sm">No hay entregables en este hito</p>
                            <Button
                                v-if="canManageDeliverables"
                                variant="outline"
                                size="sm"
                                class="mt-4"
                                @click="handleAddEntregable"
                            >
                                <Plus class="h-4 w-4 mr-1.5" />
                                Crear primer entregable
                            </Button>
                        </div>
                    </div>
                </div>
            </ScrollArea>
        </main>

        <!-- Modal de detalles del hito -->
        <HitoDetallesModal
            v-if="selectedHito"
            v-model:open="showHitoModal"
            :hito-id="selectedHito.id"
            :proyecto-id="effectiveProyectoId || 0"
            :initial-tab="hitoModalTab"
            :can-edit="canEdit"
            :can-delete="canDelete"
            :can-manage-entregables="canManageDeliverables"
            @update:tab="(tab) => hitoModalTab = tab"
            @edit-hito="handleEditHito"
            @view-entregable="handleViewEntregable"
            @edit-entregable="handleEditEntregable"
            @add-entregable="handleAddEntregable"
        />

        <!-- Modal de detalles del entregable -->
        <EntregableDetallesModal
            v-if="entregableModalId"
            v-model:open="showEntregableModal"
            :entregable-id="entregableModalId"
            :proyecto-id="effectiveProyectoId"
            :hito-id="selectedHito?.id"
            :initial-tab="entregableModalTab"
            :can-edit="canEdit"
            :can-delete="canDelete"
            @update:tab="(tab) => entregableModalTab = tab"
            @edit-entregable="() => selectedHito && handleEditEntregable({ id: entregableModalId } as Entregable)"
            @update:open="(v) => { if (!v) entregableModalId = null; }"
        />
    </div>
</template>
