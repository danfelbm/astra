<script setup lang="ts">
/**
 * EntregableDetallesModal - Modal de detalles completos de un Entregable
 *
 * Muestra 5 tabs: detalles, equipo, evidencias, comentarios, actividad.
 * Carga datos via API usando el composable useEntregableDetalles.
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
import { Avatar, AvatarFallback, AvatarImage } from '@modules/Core/Resources/js/components/ui/avatar';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import {
    Calendar, Clock, User, Edit, Flag, FileText, Tag, Users,
    ExternalLink, MessageSquare, Activity, RefreshCw, Image, AlertCircle, CheckCircle,
    ChevronDown, Pencil, Hash
} from 'lucide-vue-next';
import { format, parseISO, differenceInDays } from 'date-fns';
import { es } from 'date-fns/locale';

// Componentes del módulo
import EvidenciaFilters from '../EvidenciaFilters.vue';
import EvidenciasTable from '../EvidenciasTable.vue';
import ActivityFilters from '../ActivityFilters.vue';
import ActivityLog from '../ActivityLog.vue';
import CamposPersonalizadosDisplay from '../CamposPersonalizadosDisplay.vue';
import ComentariosPanel from '@modules/Comentarios/Resources/js/components/ComentariosPanel.vue';
import AddUsersModal from '@modules/Core/Resources/js/components/modals/AddUsersModal.vue';

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
import { useEntregableDetalles } from '../../composables/useEntregableDetalles';
import { useEntregableInlineEdit } from '../../composables/useInlineEdit';

// Props
interface Props {
    open: boolean;
    entregableId: number | null;
    proyectoId?: number;
    hitoId?: number;
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
    'edit-entregable': [];
    'refresh': [];
}>();

// Composable para cargar datos
const entregableIdRef = toRef(props, 'entregableId');
const { data, loading, error, cargar, reset } = useEntregableDetalles(entregableIdRef);

// Composable para edición inline
const {
    loadingField,
    updateField,
    updateResponsable,
    updateColaboradores,
    updateEtiquetas,
    updateCampoPersonalizado,
} = useEntregableInlineEdit(entregableIdRef, {
    onSuccess: async () => {
        // Recargar datos después de actualizar
        await cargar();
    }
});

// Computed: verificar si se puede editar
const canEditEffective = computed(() => props.canEdit || data.value.canEdit);

// Referencias a componentes inline
const inlineRefs = ref<Record<string, any>>({});

// Modal para agregar colaboradores
const showColaboradoresModal = ref(false);

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

const handleSaveEtiquetas = async (etiquetaIds: number[]) => {
    await updateEtiquetas(etiquetaIds);
};

const handleSaveCampoPersonalizado = async (campoId: number, value: any) => {
    await updateCampoPersonalizado(campoId, value);
};

// Handler para guardar colaboradores desde el modal
const handleSaveColaboradores = async (submitData: { userIds: number[]; extraData?: Record<string, any> }) => {
    const usuarios = submitData.userIds.map(id => ({
        user_id: id,
        rol: submitData.extraData?.[`${id}_rol`] || 'colaborador'
    }));
    await updateColaboradores(usuarios);
    showColaboradoresModal.value = false;
};

// Tabs válidos
const validTabs = ['detalles', 'equipo', 'evidencias', 'comentarios', 'actividad'];

// Estado del tab activo
const activeTab = ref(validTabs.includes(props.initialTab) ? props.initialTab : 'detalles');

// Estado para controlar si el header está expandido en móvil
const headerExpanded = ref(false);

// Filtros de evidencias
const filtrosEvidencias = ref({
    contrato_id: null as number | null,
    fecha_inicio: null as string | null,
    fecha_fin: null as string | null,
    tipo: null as string | null,
    estado: null as string | null,
    usuario_id: null as number | null
});

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
    if (isOpen && props.entregableId) {
        await cargar();
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

// Computed
const entregable = computed(() => data.value.entregable);
const proyecto = computed(() => data.value.proyecto);
const hito = computed(() => data.value.hito);

// Días restantes
const diasRestantes = computed(() => {
    if (!entregable.value?.fecha_fin) return null;
    return differenceInDays(new Date(entregable.value.fecha_fin), new Date());
});

const estaVencido = computed(() => {
    if (!entregable.value) return false;
    if (entregable.value.estado === 'completado' || entregable.value.estado === 'cancelado') return false;
    return diasRestantes.value !== null && diasRestantes.value < 0;
});

// Computed: evidencias filtradas
const evidenciasFiltradas = computed(() => {
    let result = entregable.value?.evidencias || [];

    if (filtrosEvidencias.value.contrato_id) {
        result = result.filter(e => e.obligacion?.contrato_id === filtrosEvidencias.value.contrato_id);
    }

    if (filtrosEvidencias.value.tipo) {
        result = result.filter(e => e.tipo_evidencia === filtrosEvidencias.value.tipo);
    }

    if (filtrosEvidencias.value.estado) {
        result = result.filter(e => e.estado === filtrosEvidencias.value.estado);
    }

    if (filtrosEvidencias.value.usuario_id) {
        result = result.filter(e => e.usuario?.id === filtrosEvidencias.value.usuario_id);
    }

    return result;
});

// Evidencias agrupadas por contrato
const evidenciasAgrupadasPorContrato = computed(() => {
    const grupos: Record<number, any> = {};

    evidenciasFiltradas.value.forEach(evidencia => {
        if (evidencia.obligacion?.contrato) {
            const contratoId = evidencia.obligacion.contrato.id;
            if (!grupos[contratoId]) {
                grupos[contratoId] = {
                    contrato: evidencia.obligacion.contrato,
                    evidencias: []
                };
            }
            grupos[contratoId].evidencias.push({
                ...evidencia,
                obligacion_titulo: evidencia.obligacion.titulo
            });
        }
    });

    return Object.values(grupos);
});

// Computed: actividades filtradas
const actividadesFiltradas = computed(() => {
    let result = data.value.actividades || [];

    if (filtrosActividades.value.usuario_id) {
        result = result.filter(a => a.causer?.id === filtrosActividades.value.usuario_id);
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

const getPrioridadColor = (prioridad: string) => {
    const colors: Record<string, string> = {
        baja: 'text-gray-500',
        media: 'text-yellow-500',
        alta: 'text-red-500',
    };
    return colors[prioridad] || '';
};

const formatDate = (date: string | null | undefined) => {
    if (!date) return 'No definida';
    return format(parseISO(date), 'dd MMM yyyy', { locale: es });
};

const formatDateTime = (date: string | null | undefined) => {
    if (!date) return 'No definida';
    return format(parseISO(date), "dd MMM yyyy 'a las' HH:mm", { locale: es });
};

// URL para ver entregable en página completa
const entregableShowUrl = computed(() => {
    const pId = props.proyectoId || proyecto.value?.id;
    const hId = props.hitoId || hito.value?.id;
    if (!pId || !hId || !props.entregableId) return null;
    return `/admin/proyectos/${pId}/hitos/${hId}/entregables/${props.entregableId}`;
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
                <template v-if="loading && !entregable">
                    <Skeleton class="h-7 w-3/4 mb-2" />
                    <Skeleton class="h-4 w-1/2" />
                </template>

                <!-- Error -->
                <template v-else-if="error">
                    <DialogTitle class="text-destructive">Error al cargar</DialogTitle>
                    <DialogDescription>{{ error }}</DialogDescription>
                </template>

                <!-- Contenido real -->
                <template v-else-if="entregable">
                    <!-- Fila principal: Título + Badge + Refresh (siempre visible) -->
                    <div class="flex items-center gap-2 sm:gap-4">
                        <div class="flex-1 min-w-0">
                            <DialogTitle class="text-lg sm:text-xl flex items-center gap-2">
                                <FileText class="h-4 w-4 sm:h-5 sm:w-5 flex-shrink-0" />
                                <span class="truncate">{{ entregable.nombre }}</span>
                            </DialogTitle>
                        </div>
                        <Badge :class="getEstadoColor(entregable.estado)" class="flex-shrink-0 text-xs">
                            {{ entregable.estado_label || entregable.estado }}
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

                    <!-- Descripción (siempre visible) -->
                    <DialogDescription class="mt-1 text-xs sm:text-sm">
                        Hito: {{ hito?.nombre }} · Proyecto: {{ proyecto?.nombre }}
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
                                    <Flag class="h-3.5 w-3.5 sm:h-4 sm:w-4" :class="getPrioridadColor(entregable.prioridad)" />
                                    <span class="capitalize">{{ entregable.prioridad }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <Progress :model-value="entregable.porcentaje_completado || 0" class="w-14 sm:w-16 h-2" />
                                    <span>{{ entregable.porcentaje_completado || 0 }}%</span>
                                </div>
                                <div v-if="diasRestantes !== null" class="flex items-center gap-1" :class="{ 'text-red-600': estaVencido }">
                                    <Clock class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                    <span>
                                        {{ estaVencido ? `Vencido hace ${Math.abs(diasRestantes)} días` :
                                           diasRestantes === 0 ? 'Vence hoy' : `${diasRestantes} días` }}
                                    </span>
                                </div>
                                <div v-if="entregable.responsable" class="flex items-center gap-1">
                                    <User class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                    <span>{{ entregable.responsable.name }}</span>
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
                        <TabsTrigger value="equipo" label="Equipo" :icon="Users">
                            <Users class="h-4 w-4 mr-1.5" />
                            Equipo
                        </TabsTrigger>
                        <TabsTrigger value="evidencias" label="Evidencias" :icon="Image" :badge="entregable?.evidencias?.length">
                            <Image class="h-4 w-4 mr-1.5" />
                            Evidencias
                            <Badge v-if="entregable?.evidencias?.length" variant="secondary" class="ml-1.5 h-5 px-1.5 text-xs">
                                {{ entregable.evidencias.length }}
                            </Badge>
                        </TabsTrigger>
                        <TabsTrigger value="comentarios" label="Comentarios" :icon="MessageSquare" :badge="entregable?.total_comentarios">
                            <MessageSquare class="h-4 w-4 mr-1.5" />
                            Comentarios
                            <Badge v-if="entregable?.total_comentarios" variant="secondary" class="ml-1.5 h-5 px-1.5 text-xs">
                                {{ entregable.total_comentarios }}
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
                        <div v-if="loading && !entregable" class="space-y-4">
                            <Skeleton class="h-32 w-full" />
                            <Skeleton class="h-32 w-full" />
                        </div>

                        <!-- Tab Detalles -->
                        <TabsContent value="detalles" class="mt-0 space-y-4">
                            <template v-if="entregable">
                                <!-- Información General (editable) -->
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
                                                :model-value="entregable.nombre"
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
                                                :model-value="entregable.descripcion"
                                                :can-edit="canEditEffective"
                                                :loading="loadingField === 'descripcion'"
                                                label="descripción"
                                                placeholder="Sin descripción"
                                                @save="(v: string) => handleSaveField('descripcion', v, 'Descripción')"
                                            />
                                        </div>

                                        <!-- Estado y Prioridad en grid -->
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <h4 class="text-sm font-medium text-muted-foreground mb-1">Estado</h4>
                                                <InlineEditSelect
                                                    :ref="(el: any) => inlineRefs['estado'] = el"
                                                    :model-value="entregable.estado"
                                                    :options="data.estados || []"
                                                    :can-edit="canEditEffective"
                                                    :loading="loadingField === 'estado'"
                                                    label="estado"
                                                    show-badge
                                                    @save="(v: string) => handleSaveField('estado', v, 'Estado')"
                                                />
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-medium text-muted-foreground mb-1">Prioridad</h4>
                                                <InlineEditSelect
                                                    :ref="(el: any) => inlineRefs['prioridad'] = el"
                                                    :model-value="entregable.prioridad"
                                                    :options="data.prioridades || []"
                                                    :can-edit="canEditEffective"
                                                    :loading="loadingField === 'prioridad'"
                                                    label="prioridad"
                                                    show-badge
                                                    @save="(v: string) => handleSaveField('prioridad', v, 'Prioridad')"
                                                />
                                            </div>
                                        </div>

                                        <!-- Fechas en grid -->
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <h4 class="text-sm font-medium text-muted-foreground mb-1">Fecha de Inicio</h4>
                                                <div class="flex items-center gap-1.5 text-sm">
                                                    <Calendar class="h-4 w-4 flex-shrink-0" />
                                                    <InlineEditDate
                                                        :ref="(el: any) => inlineRefs['fecha_inicio'] = el"
                                                        :model-value="entregable.fecha_inicio"
                                                        :can-edit="canEditEffective"
                                                        :loading="loadingField === 'fecha_inicio'"
                                                        label="fecha de inicio"
                                                        @save="(v: string | null) => handleSaveField('fecha_inicio', v, 'Fecha de inicio')"
                                                    />
                                                </div>
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-medium text-muted-foreground mb-1">Fecha de Fin</h4>
                                                <div class="flex items-center gap-1.5 text-sm" :class="{ 'text-red-600': estaVencido }">
                                                    <Calendar class="h-4 w-4 flex-shrink-0" />
                                                    <InlineEditDate
                                                        :ref="(el: any) => inlineRefs['fecha_fin'] = el"
                                                        :model-value="entregable.fecha_fin"
                                                        :can-edit="canEditEffective"
                                                        :loading="loadingField === 'fecha_fin'"
                                                        label="fecha de fin"
                                                        :min-date="entregable.fecha_inicio"
                                                        @save="(v: string | null) => handleSaveField('fecha_fin', v, 'Fecha de fin')"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Notas (editable) -->
                                        <div>
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Notas</h4>
                                            <InlineEditTextarea
                                                :ref="(el: any) => inlineRefs['notas'] = el"
                                                :model-value="entregable.notas"
                                                :can-edit="canEditEffective"
                                                :loading="loadingField === 'notas'"
                                                label="notas"
                                                placeholder="Sin notas adicionales"
                                                @save="(v: string) => handleSaveField('notas', v, 'Notas')"
                                            />
                                        </div>

                                        <!-- Orden (editable) -->
                                        <div>
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Orden de Visualización</h4>
                                            <div class="flex items-center gap-1.5 text-sm">
                                                <Hash class="h-4 w-4 flex-shrink-0" />
                                                <InlineEditNumber
                                                    :ref="(el: any) => inlineRefs['orden'] = el"
                                                    :model-value="entregable.orden"
                                                    :can-edit="canEditEffective"
                                                    :loading="loadingField === 'orden'"
                                                    label="orden"
                                                    placeholder="1"
                                                    :min="1"
                                                    @save="(v: number | null) => handleSaveField('orden', v, 'Orden')"
                                                />
                                            </div>
                                        </div>

                                        <!-- Info de completado (solo lectura) -->
                                        <div v-if="entregable.estado === 'completado'" class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg p-3">
                                            <div class="flex items-center gap-2 text-green-700 dark:text-green-400 mb-2">
                                                <CheckCircle class="h-5 w-5" />
                                                <span class="font-medium">Completado</span>
                                            </div>
                                            <div class="text-sm text-green-600 dark:text-green-500 space-y-1">
                                                <div v-if="entregable.completado_at">
                                                    Fecha: {{ formatDateTime(entregable.completado_at) }}
                                                </div>
                                                <div v-if="entregable.completado_por_usuario">
                                                    Por: {{ entregable.completado_por_usuario.name }}
                                                </div>
                                                <div v-if="entregable.observaciones_estado" class="italic mt-2">
                                                    "{{ entregable.observaciones_estado }}"
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
                                            :model-value="entregable.etiquetas?.map((e: any) => e.id) || []"
                                            :etiquetas="entregable.etiquetas || []"
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
                                        <CardDescription>Información adicional del entregable</CardDescription>
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

                                <!-- Metadata -->
                                <Card>
                                    <CardHeader class="pb-3">
                                        <CardTitle class="text-base">Información del Sistema</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="grid gap-3 md:grid-cols-2 text-sm">
                                            <div>
                                                <span class="text-muted-foreground">Creado: </span>
                                                {{ formatDateTime(entregable.created_at) }}
                                            </div>
                                            <div>
                                                <span class="text-muted-foreground">Actualizado: </span>
                                                {{ formatDateTime(entregable.updated_at) }}
                                            </div>
                                            <div>
                                                <span class="text-muted-foreground">Orden: </span>
                                                {{ entregable.orden }}
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </template>
                        </TabsContent>

                        <!-- Tab Equipo -->
                        <TabsContent value="equipo" class="mt-0 space-y-4">
                            <template v-if="entregable">
                                <!-- Responsable Principal (editable) -->
                                <Card>
                                    <CardHeader class="pb-3">
                                        <CardTitle class="text-base">Responsable Principal</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <InlineEditUser
                                            :model-value="entregable.responsable"
                                            :can-edit="canEditEffective"
                                            :loading="loadingField === 'responsable_id'"
                                            label="responsable"
                                            :search-endpoint="data.searchUsersEndpoint || '/admin/proyectos/search-users'"
                                            modal-title="Seleccionar Responsable Principal"
                                            modal-description="Busca y selecciona el responsable principal del entregable"
                                            @save="(userId: number | null) => handleSaveResponsable(userId)"
                                        />
                                    </CardContent>
                                </Card>

                                <!-- Colaboradores (editable) -->
                                <Card>
                                    <CardHeader class="pb-3">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <CardTitle class="text-base">Colaboradores</CardTitle>
                                                <CardDescription>{{ data.usuariosAsignados?.length || 0 }} usuarios asignados</CardDescription>
                                            </div>
                                            <Button
                                                v-if="canEditEffective"
                                                variant="outline"
                                                size="sm"
                                                @click="showColaboradoresModal = true"
                                            >
                                                <Pencil class="h-4 w-4 mr-1.5" />
                                                Editar
                                            </Button>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <div v-if="data.usuariosAsignados && data.usuariosAsignados.length > 0" class="space-y-2">
                                            <div
                                                v-for="asignado in data.usuariosAsignados"
                                                :key="asignado.user_id"
                                                class="flex items-center justify-between p-3 bg-muted rounded-lg"
                                            >
                                                <div class="flex items-center gap-3">
                                                    <Avatar class="h-8 w-8">
                                                        <AvatarImage v-if="asignado.user.avatar" :src="asignado.user.avatar" />
                                                        <AvatarFallback>{{ asignado.user.name.substring(0, 2).toUpperCase() }}</AvatarFallback>
                                                    </Avatar>
                                                    <div>
                                                        <p class="font-medium text-sm">{{ asignado.user.name }}</p>
                                                        <p class="text-xs text-muted-foreground">{{ asignado.user.email }}</p>
                                                    </div>
                                                </div>
                                                <Badge variant="outline" class="capitalize">
                                                    {{ asignado.rol }}
                                                </Badge>
                                            </div>
                                        </div>
                                        <div v-else class="text-center py-4">
                                            <p class="text-muted-foreground mb-2">No hay colaboradores asignados</p>
                                            <Button
                                                v-if="canEditEffective"
                                                variant="outline"
                                                size="sm"
                                                @click="showColaboradoresModal = true"
                                            >
                                                <Users class="h-4 w-4 mr-1.5" />
                                                Agregar colaboradores
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            </template>
                        </TabsContent>

                        <!-- Modal para agregar colaboradores -->
                        <AddUsersModal
                            v-model:open="showColaboradoresModal"
                            title="Gestionar Colaboradores"
                            description="Busca y selecciona los colaboradores del entregable"
                            :selected-users="data.usuariosAsignados?.map((a: any) => a.user_id) || []"
                            :search-endpoint="data.searchUsersEndpoint || '/admin/proyectos/search-users'"
                            :loading="loadingField === 'usuarios'"
                            @submit="handleSaveColaboradores"
                        />

                        <!-- Tab Evidencias -->
                        <TabsContent value="evidencias" class="mt-0 space-y-4">
                            <template v-if="entregable">
                                <!-- Filtros -->
                                <EvidenciaFilters
                                    v-if="entregable.evidencias && entregable.evidencias.length > 0"
                                    v-model="filtrosEvidencias"
                                    :contratos="data.contratosRelacionados"
                                    :evidencias="entregable.evidencias"
                                />

                                <!-- Tabla de evidencias -->
                                <EvidenciasTable
                                    v-if="entregable.evidencias && entregable.evidencias.length > 0"
                                    mode="grouped"
                                    :evidencias-agrupadas="evidenciasAgrupadasPorContrato"
                                    :format-date="formatDate"
                                />

                                <!-- Estado vacío -->
                                <Card v-else>
                                    <CardContent class="text-center py-8">
                                        <Image class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                                        <p class="text-muted-foreground">No hay evidencias asociadas a este entregable</p>
                                    </CardContent>
                                </Card>
                            </template>
                        </TabsContent>

                        <!-- Tab Comentarios -->
                        <TabsContent value="comentarios" class="mt-0">
                            <!-- Si llegó al modal (pasó verificación de acceso), puede comentar -->
                            <ComentariosPanel
                                v-if="entregableId"
                                commentable-type="entregables"
                                :commentable-id="entregableId"
                                :can-create="true"
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
                                context-level="entregable"
                            />

                            <!-- Log de actividades -->
                            <ActivityLog
                                :activities="actividadesFiltradas"
                                title="Historial de Actividad"
                                description="Registro de cambios del entregable"
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
                        <Link v-if="entregableShowUrl" :href="entregableShowUrl">
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
