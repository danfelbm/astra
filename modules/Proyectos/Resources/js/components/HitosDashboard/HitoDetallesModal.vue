<script setup lang="ts">
/**
 * HitoDetallesModal - Modal de detalles completos de un Hito
 *
 * Muestra 3 tabs: detalles, comentarios, actividad.
 * Carga datos via API usando el composable useHitoDetalles.
 * Soporta deeplinks para tab y paginación de comentarios.
 * En responsive: header colapsable, tabs como select dropdown.
 * Soporta edición inline de campos cuando canEdit es true.
 */
import { ref, computed, watch, toRef } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@modules/Core/Resources/js/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@modules/Core/Resources/js/components/ui/tabs';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@modules/Core/Resources/js/components/ui/collapsible';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Progress } from '@modules/Core/Resources/js/components/ui/progress';
import { ScrollArea } from '@modules/Core/Resources/js/components/ui/scroll-area';
import { Skeleton } from '@modules/Core/Resources/js/components/ui/skeleton';
import {
    Calendar, Clock, User, Edit, Target, FileText, Tag,
    ExternalLink, MessageSquare, Activity, RefreshCw,
    ChevronDown, GitBranch, Hash
} from 'lucide-vue-next';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';

// Componentes del módulo
import ActivityFilters from '../ActivityFilters.vue';
import ActivityLog from '../ActivityLog.vue';
import CamposPersonalizadosDisplay from '../CamposPersonalizadosDisplay.vue';
import ComentariosPanel from '@modules/Comentarios/Resources/js/components/ComentariosPanel.vue';

// Componentes de edición inline
import {
    InlineEditText,
    InlineEditTextarea,
    InlineEditDate,
    InlineEditSelect,
    InlineEditNumber,
    InlineEditUser,
    InlineEditEtiquetas,
    InlineEditCampoPersonalizado,
} from '../inline-edit';

// Composables
import { useHitoDetalles } from '../../composables/useHitoDetalles';
import { useHitoInlineEdit } from '../../composables/useInlineEdit';

// Props
interface Props {
    open: boolean;
    hitoId: number | null;
    proyectoId: number;
    initialTab?: string;
    canEdit?: boolean;
    canDelete?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    initialTab: 'detalles',
    canEdit: false,
    canDelete: false,
});

// Emits
const emit = defineEmits<{
    'update:open': [value: boolean];
    'update:tab': [tab: string];
    'edit-hito': [];
    'refresh': [];
}>();

// Composable para cargar datos
const hitoIdRef = toRef(props, 'hitoId');
const { data, loading, error, cargar, reset } = useHitoDetalles(hitoIdRef);

// Composable para edición inline
const {
    loadingField,
    updateField,
    updateResponsable,
    updatePadre,
    updateEtiquetas,
    updateCampoPersonalizado,
} = useHitoInlineEdit(hitoIdRef, {
    onSuccess: async () => {
        // Recargar datos después de actualizar
        await cargar();
    }
});

// Computed: verificar si se puede editar
const canEditEffective = computed(() => props.canEdit || data.value.canEdit);

// Referencias a componentes inline para cerrar después de guardar
const inlineRefs = ref<Record<string, any>>({});

// Handlers de guardado para campos inline
const handleSaveField = async (field: string, value: any, label?: string) => {
    const success = await updateField(field, value, label);
    if (success) {
        inlineRefs.value[field]?.closeAfterSave?.();
    }
};

const handleSaveResponsable = async (userId: number | null) => {
    await updateResponsable(userId);
};

const handleSavePadre = async (padreId: string) => {
    const id = padreId ? parseInt(padreId) : null;
    await updatePadre(id);
};

const handleSaveEtiquetas = async (etiquetaIds: number[]) => {
    await updateEtiquetas(etiquetaIds);
};

const handleSaveCampoPersonalizado = async (campoId: number, value: any) => {
    await updateCampoPersonalizado(campoId, value);
};

// Tabs válidos
const validTabs = ['detalles', 'comentarios', 'actividad'];

// Estado del tab activo
const activeTab = ref(validTabs.includes(props.initialTab) ? props.initialTab : 'detalles');

// Estado para controlar si el header está expandido en móvil
const headerExpanded = ref(false);

// Filtros de actividades
const filtrosActividades = ref({
    usuario_id: null as number | null,
    tipo_entidad: null as string | null,
    tipo_accion: null as string | null,
    fecha_inicio: null as string | null,
    fecha_fin: null as string | null
});

// Watch para cargar datos cuando se abre el modal
watch(() => props.open, async (isOpen) => {
    if (isOpen && props.hitoId) {
        await cargar();
        // Restablecer tab al inicial
        activeTab.value = validTabs.includes(props.initialTab) ? props.initialTab : 'detalles';
    }
}, { immediate: true });

// Watch para actualizar initialTab cuando cambia
watch(() => props.initialTab, (newTab) => {
    if (props.open && newTab && validTabs.includes(newTab)) {
        activeTab.value = newTab;
    }
});

// Watch para emitir cambio de tab
watch(activeTab, (newTab) => {
    emit('update:tab', newTab);
});

// Computed: hito
const hito = computed(() => data.value.hito);
const estadisticas = computed(() => data.value.estadisticas);

// Computed: actividades filtradas
const actividadesFiltradas = computed(() => {
    let result = data.value.actividades || [];

    if (filtrosActividades.value.usuario_id) {
        result = result.filter(a => a.causer?.id === filtrosActividades.value.usuario_id);
    }

    if (filtrosActividades.value.tipo_entidad) {
        result = result.filter(a => a.subject_type === filtrosActividades.value.tipo_entidad);
    }

    if (filtrosActividades.value.tipo_accion) {
        result = result.filter(a => a.event === filtrosActividades.value.tipo_accion);
    }

    if (filtrosActividades.value.fecha_inicio || filtrosActividades.value.fecha_fin) {
        result = result.filter(a => {
            const fecha = new Date(a.created_at);
            if (filtrosActividades.value.fecha_inicio) {
                const fechaInicio = new Date(filtrosActividades.value.fecha_inicio);
                if (fecha < fechaInicio) return false;
            }
            if (filtrosActividades.value.fecha_fin) {
                const fechaFin = new Date(filtrosActividades.value.fecha_fin);
                fechaFin.setHours(23, 59, 59, 999);
                if (fecha > fechaFin) return false;
            }
            return true;
        });
    }

    return result;
});

// Utilidades
const getEstadoColor = (estado: string) => {
    const colors: Record<string, string> = {
        pendiente: 'bg-gray-100 text-gray-800',
        en_progreso: 'bg-blue-100 text-blue-800',
        completado: 'bg-green-100 text-green-800',
        cancelado: 'bg-red-100 text-red-800',
    };
    return colors[estado] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date: string | null) => {
    if (!date) return 'No definida';
    return format(parseISO(date), 'dd MMM yyyy', { locale: es });
};

const formatDateTime = (date: string | null) => {
    if (!date) return 'No definida';
    return format(parseISO(date), "dd MMM yyyy 'a las' HH:mm", { locale: es });
};

// URL para ver hito en página completa
const hitoShowUrl = computed(() => {
    if (!props.proyectoId || !props.hitoId) return null;
    return `/admin/proyectos/${props.proyectoId}/hitos/${props.hitoId}`;
});

// Handlers
const handleClose = () => {
    emit('update:open', false);
};

const handleRefresh = async () => {
    await cargar();
    emit('refresh');
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="w-full sm:w-[95vw] max-w-full sm:!max-w-5xl h-[100dvh] sm:h-auto sm:max-h-[90vh] rounded-none sm:rounded-lg flex flex-col p-0 gap-0 overflow-hidden">
            <!-- Header fijo -->
            <DialogHeader class="flex-shrink-0 px-4 sm:px-6 pt-4 sm:pt-6 pb-3 sm:pb-4 border-b">
                <!-- Skeleton de carga -->
                <template v-if="loading && !hito">
                    <Skeleton class="h-7 w-3/4 mb-2" />
                    <Skeleton class="h-4 w-1/2" />
                </template>

                <!-- Error -->
                <template v-else-if="error">
                    <DialogTitle class="text-destructive">Error al cargar</DialogTitle>
                    <DialogDescription>{{ error }}</DialogDescription>
                </template>

                <!-- Contenido real -->
                <template v-else-if="hito">
                    <!-- Fila principal: Título + Badge + Refresh (siempre visible) -->
                    <div class="flex items-center gap-2 sm:gap-4">
                        <div class="flex-1 min-w-0">
                            <DialogTitle class="text-lg sm:text-xl flex items-center gap-2">
                                <Target class="h-4 w-4 sm:h-5 sm:w-5 flex-shrink-0" />
                                <span class="truncate">{{ hito.nombre }}</span>
                            </DialogTitle>
                        </div>
                        <Badge :class="getEstadoColor(hito.estado)" class="flex-shrink-0 text-xs">
                            {{ hito.estado_label || hito.estado }}
                        </Badge>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="flex-shrink-0 h-8 w-8 p-0"
                            @click="handleRefresh"
                            :disabled="loading"
                            title="Actualizar"
                        >
                            <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
                        </Button>
                    </div>

                    <!-- Descripción del proyecto (siempre visible) -->
                    <DialogDescription class="mt-1 text-xs sm:text-sm">
                        Proyecto: {{ hito.proyecto?.nombre }}
                    </DialogDescription>

                    <!-- Detalles colapsables en móvil, siempre visibles en desktop -->
                    <Collapsible v-model:open="headerExpanded" class="sm:!block">
                        <!-- Trigger solo visible en móvil -->
                        <CollapsibleTrigger class="sm:hidden w-full mt-2">
                            <div class="relative">
                                <!-- Efecto difuminado cuando está colapsado -->
                                <div
                                    v-if="!headerExpanded"
                                    class="absolute inset-0 bg-gradient-to-b from-transparent via-background/50 to-background pointer-events-none"
                                />
                                <button
                                    class="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition-colors w-full justify-center py-1"
                                >
                                    <span>{{ headerExpanded ? 'Ocultar detalles' : 'Ver detalles' }}</span>
                                    <ChevronDown
                                        class="h-3 w-3 transition-transform duration-200"
                                        :class="{ 'rotate-180': headerExpanded }"
                                    />
                                </button>
                            </div>
                        </CollapsibleTrigger>

                        <!-- Contenido colapsable (resumen rápido) -->
                        <CollapsibleContent class="sm:!block">
                            <div class="flex flex-wrap gap-3 sm:gap-4 mt-2 sm:mt-3 text-xs sm:text-sm text-muted-foreground">
                                <div class="flex items-center gap-1.5">
                                    <Progress :model-value="hito.porcentaje_completado" class="w-16 sm:w-20 h-2" />
                                    <span>{{ hito.porcentaje_completado }}%</span>
                                </div>
                                <div v-if="hito.fecha_fin" class="flex items-center gap-1">
                                    <Clock class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                    <span :class="{ 'text-red-600': estadisticas?.esta_vencido }">
                                        {{ formatDate(hito.fecha_fin) }}
                                    </span>
                                </div>
                                <div v-if="hito.responsable" class="flex items-center gap-1">
                                    <User class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                    <span>{{ hito.responsable.name }}</span>
                                </div>
                            </div>
                        </CollapsibleContent>
                    </Collapsible>
                </template>
            </DialogHeader>

            <!-- Contenido con scroll -->
            <div class="flex-1 min-h-0 overflow-hidden flex flex-col">
                <Tabs v-model="activeTab" class="flex-1 min-h-0 flex flex-col">
                    <!-- TabsList - automáticamente se convierte en Select en móvil -->
                    <TabsList class="flex-shrink-0 mx-4 sm:mx-6 mt-3 sm:mt-4 justify-start">
                        <TabsTrigger value="detalles" label="Detalles" :icon="FileText">
                            <FileText class="h-4 w-4 mr-1.5" />
                            Detalles
                        </TabsTrigger>
                        <TabsTrigger value="comentarios" label="Comentarios" :icon="MessageSquare" :badge="hito?.total_comentarios">
                            <MessageSquare class="h-4 w-4 mr-1.5" />
                            Comentarios
                            <Badge v-if="hito?.total_comentarios" variant="secondary" class="ml-1.5 h-5 px-1.5 text-xs">
                                {{ hito.total_comentarios }}
                            </Badge>
                        </TabsTrigger>
                        <TabsTrigger value="actividad" label="Actividad" :icon="Activity">
                            <Activity class="h-4 w-4 mr-1.5" />
                            Actividad
                        </TabsTrigger>
                    </TabsList>

                    <!-- Contenido de tabs con scroll -->
                    <ScrollArea class="flex-1 min-h-0 overflow-y-auto">
                        <div class="px-3 py-3 sm:px-6 sm:py-4">
                        <!-- Loading skeleton -->
                        <div v-if="loading && !hito" class="space-y-4">
                            <Skeleton class="h-32 w-full" />
                            <Skeleton class="h-32 w-full" />
                        </div>

                        <!-- Tab Detalles -->
                        <TabsContent value="detalles" class="mt-0 space-y-4">
                            <template v-if="hito">
                                <!-- Información General -->
                                <Card>
                                    <CardHeader class="pb-3">
                                        <CardTitle class="text-base">Información General</CardTitle>
                                    </CardHeader>
                                    <CardContent class="space-y-4">
                                        <!-- Nombre (editable) -->
                                        <div>
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Nombre</h4>
                                            <InlineEditText
                                                :ref="(el: any) => inlineRefs['nombre'] = el"
                                                :model-value="hito.nombre"
                                                :can-edit="canEditEffective"
                                                :loading="loadingField === 'nombre'"
                                                label="nombre"
                                                placeholder="Sin nombre"
                                                required
                                                @save="(v: string) => handleSaveField('nombre', v, 'Nombre')"
                                            />
                                        </div>

                                        <!-- Descripción (editable) -->
                                        <div>
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Descripción</h4>
                                            <InlineEditTextarea
                                                :ref="(el: any) => inlineRefs['descripcion'] = el"
                                                :model-value="hito.descripcion"
                                                :can-edit="canEditEffective"
                                                :loading="loadingField === 'descripcion'"
                                                label="descripción"
                                                placeholder="Sin descripción"
                                                @save="(v: string) => handleSaveField('descripcion', v, 'Descripción')"
                                            />
                                        </div>

                                        <!-- Estado (editable) -->
                                        <div>
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Estado</h4>
                                            <InlineEditSelect
                                                :ref="(el: any) => inlineRefs['estado'] = el"
                                                :model-value="hito.estado"
                                                :options="data.estados || []"
                                                :can-edit="canEditEffective"
                                                :loading="loadingField === 'estado'"
                                                label="estado"
                                                show-badge
                                                @save="(v: string) => handleSaveField('estado', v, 'Estado')"
                                            />
                                        </div>

                                        <!-- Fechas en grid -->
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <h4 class="text-sm font-medium text-muted-foreground mb-1">Fecha de Inicio</h4>
                                                <div class="flex items-center gap-1.5 text-sm">
                                                    <Calendar class="h-4 w-4 flex-shrink-0" />
                                                    <InlineEditDate
                                                        :ref="(el: any) => inlineRefs['fecha_inicio'] = el"
                                                        :model-value="hito.fecha_inicio"
                                                        :can-edit="canEditEffective"
                                                        :loading="loadingField === 'fecha_inicio'"
                                                        label="fecha de inicio"
                                                        @save="(v: string | null) => handleSaveField('fecha_inicio', v, 'Fecha de inicio')"
                                                    />
                                                </div>
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-medium text-muted-foreground mb-1">Fecha de Fin</h4>
                                                <div class="flex items-center gap-1.5 text-sm" :class="{ 'text-red-600': estadisticas?.esta_vencido }">
                                                    <Calendar class="h-4 w-4 flex-shrink-0" />
                                                    <InlineEditDate
                                                        :ref="(el: any) => inlineRefs['fecha_fin'] = el"
                                                        :model-value="hito.fecha_fin"
                                                        :can-edit="canEditEffective"
                                                        :loading="loadingField === 'fecha_fin'"
                                                        label="fecha de fin"
                                                        :min-date="hito.fecha_inicio"
                                                        @save="(v: string | null) => handleSaveField('fecha_fin', v, 'Fecha de fin')"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Responsable (editable) -->
                                        <div>
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Responsable</h4>
                                            <InlineEditUser
                                                :model-value="hito.responsable"
                                                :can-edit="canEditEffective"
                                                :loading="loadingField === 'responsable_id'"
                                                label="responsable"
                                                :search-endpoint="data.searchUsersEndpoint || '/admin/proyectos/search-users'"
                                                modal-title="Seleccionar Responsable"
                                                modal-description="Busca y selecciona el responsable del hito"
                                                @save="(userId: number | null) => handleSaveResponsable(userId)"
                                            />
                                        </div>

                                        <!-- Resumen de entregables (solo lectura) -->
                                        <div v-if="estadisticas">
                                            <h4 class="text-sm font-medium text-muted-foreground mb-2">Resumen de Entregables</h4>
                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-2 text-center">
                                                    <div class="text-lg font-semibold">{{ estadisticas.entregables_pendientes }}</div>
                                                    <div class="text-xs text-muted-foreground">Pendientes</div>
                                                </div>
                                                <div class="bg-blue-50 dark:bg-blue-900/30 rounded-lg p-2 text-center">
                                                    <div class="text-lg font-semibold text-blue-700 dark:text-blue-400">{{ estadisticas.entregables_en_progreso }}</div>
                                                    <div class="text-xs text-muted-foreground">En Progreso</div>
                                                </div>
                                                <div class="bg-green-50 dark:bg-green-900/30 rounded-lg p-2 text-center">
                                                    <div class="text-lg font-semibold text-green-700 dark:text-green-400">{{ estadisticas.entregables_completados }}</div>
                                                    <div class="text-xs text-muted-foreground">Completados</div>
                                                </div>
                                                <div class="bg-red-50 dark:bg-red-900/30 rounded-lg p-2 text-center">
                                                    <div class="text-lg font-semibold text-red-700 dark:text-red-400">{{ estadisticas.entregables_cancelados }}</div>
                                                    <div class="text-xs text-muted-foreground">Cancelados</div>
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <!-- Etiquetas (editable) -->
                                <Card>
                                    <CardHeader class="pb-3">
                                        <div class="flex items-center gap-2">
                                            <Tag class="h-4 w-4" />
                                            <CardTitle class="text-base">Etiquetas</CardTitle>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <InlineEditEtiquetas
                                            :model-value="hito.etiquetas?.map((e: any) => e.id) || []"
                                            :etiquetas="hito.etiquetas || []"
                                            :categorias="data.categorias || []"
                                            :can-edit="canEditEffective"
                                            :loading="loadingField === 'etiquetas'"
                                            label="etiquetas"
                                            @save="handleSaveEtiquetas"
                                        />
                                    </CardContent>
                                </Card>

                                <!-- Campos Personalizados (editable campo por campo) -->
                                <Card v-if="data.camposPersonalizados && data.camposPersonalizados.length > 0">
                                    <CardHeader class="pb-3">
                                        <CardTitle class="text-base">Campos Personalizados</CardTitle>
                                        <CardDescription>Información adicional del hito</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div
                                                v-for="campo in data.camposPersonalizados"
                                                :key="campo.id"
                                            >
                                                <h4 class="text-sm font-medium text-muted-foreground mb-1">{{ campo.nombre }}</h4>
                                                <InlineEditCampoPersonalizado
                                                    :campo="campo"
                                                    :model-value="data.valoresCamposPersonalizados[campo.id]"
                                                    :can-edit="canEditEffective"
                                                    :loading="loadingField === `campo_personalizado_${campo.id}`"
                                                    @save="(campoId: number, value: any) => handleSaveCampoPersonalizado(campoId, value)"
                                                />
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <!-- Jerarquía y Orden (editable) -->
                                <Card>
                                    <CardHeader class="pb-3">
                                        <div class="flex items-center gap-2">
                                            <GitBranch class="h-4 w-4" />
                                            <CardTitle class="text-base">Jerarquía y Orden</CardTitle>
                                        </div>
                                    </CardHeader>
                                    <CardContent class="space-y-4">
                                        <!-- Hito Padre (editable) -->
                                        <div>
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Hito Padre</h4>
                                            <InlineEditSelect
                                                :ref="(el: any) => inlineRefs['parent_id'] = el"
                                                :model-value="hito.parent_id?.toString() || ''"
                                                :options="[{ value: '', label: '(Sin padre - Nivel raíz)' }, ...(data.hitosDisponibles || [])]"
                                                :can-edit="canEditEffective"
                                                :loading="loadingField === 'parent_id'"
                                                label="hito padre"
                                                placeholder="Sin hito padre"
                                                @save="handleSavePadre"
                                            />
                                        </div>

                                        <!-- Ruta completa (solo lectura) -->
                                        <div v-if="hito.ruta_completa">
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Ruta Completa</h4>
                                            <p class="text-sm">{{ hito.ruta_completa }}</p>
                                        </div>

                                        <!-- Nivel (solo lectura) -->
                                        <div v-if="hito.nivel !== undefined">
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Nivel</h4>
                                            <Badge variant="outline">Nivel {{ hito.nivel }}</Badge>
                                        </div>

                                        <!-- Orden (editable) -->
                                        <div>
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Orden de Visualización</h4>
                                            <div class="flex items-center gap-1.5 text-sm">
                                                <Hash class="h-4 w-4 flex-shrink-0" />
                                                <InlineEditNumber
                                                    :ref="(el: any) => inlineRefs['orden'] = el"
                                                    :model-value="hito.orden"
                                                    :can-edit="canEditEffective"
                                                    :loading="loadingField === 'orden'"
                                                    label="orden"
                                                    placeholder="1"
                                                    :min="1"
                                                    @save="(v: number | null) => handleSaveField('orden', v, 'Orden')"
                                                />
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <!-- Metadata -->
                                <Card>
                                    <CardHeader class="pb-3">
                                        <CardTitle class="text-base">Información del Sistema</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="grid gap-3 md:grid-cols-2 text-sm">
                                            <div>
                                                <span class="text-muted-foreground">Creado: </span>
                                                {{ formatDateTime(hito.created_at) }}
                                            </div>
                                            <div>
                                                <span class="text-muted-foreground">Actualizado: </span>
                                                {{ formatDateTime(hito.updated_at) }}
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </template>
                        </TabsContent>

                        <!-- Tab Comentarios -->
                        <TabsContent value="comentarios" class="mt-0">
                            <ComentariosPanel
                                v-if="hitoId"
                                commentable-type="hitos"
                                :commentable-id="hitoId"
                                :can-create="canEdit || data.canEdit"
                                embedded
                            />
                        </TabsContent>

                        <!-- Tab Actividad -->
                        <TabsContent value="actividad" class="mt-0 space-y-4">
                            <!-- Filtros -->
                            <ActivityFilters
                                v-if="data.actividades && data.actividades.length > 0"
                                v-model="filtrosActividades"
                                :usuarios="data.usuariosActividades"
                                context-level="hito"
                            />

                            <!-- Log de actividades -->
                            <ActivityLog
                                :activities="actividadesFiltradas"
                                title="Historial de Actividad"
                                description="Registro de cambios del hito y sus entregables"
                                empty-message="No hay actividad registrada"
                                :show-card="false"
                            />
                        </TabsContent>
                        </div>
                    </ScrollArea>
                </Tabs>
            </div>

            <!-- Footer fijo -->
            <DialogFooter class="flex-shrink-0 px-3 py-3 sm:px-6 sm:py-4 border-t bg-muted/30">
                <div class="flex items-center justify-between w-full gap-2">
                    <div class="flex gap-1.5 sm:gap-2">
                        <Link v-if="hitoShowUrl" :href="hitoShowUrl">
                            <Button variant="outline" size="sm" class="text-xs sm:text-sm px-2 sm:px-3">
                                <ExternalLink class="h-3.5 w-3.5 sm:h-4 sm:w-4 sm:mr-1.5" />
                                <span class="hidden sm:inline">Abrir página completa</span>
                            </Button>
                        </Link>
                    </div>
                    <Button variant="ghost" size="sm" class="text-xs sm:text-sm" @click="handleClose">
                        Cerrar
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
