<script setup lang="ts">
/**
 * HitoDetallesModal - Modal de detalles completos de un Hito
 *
 * Muestra 3 tabs: detalles, comentarios, actividad.
 * Carga datos via API usando el composable useHitoDetalles.
 * Soporta deeplinks para tab y paginación de comentarios.
 * En responsive: header colapsable, tabs como select dropdown.
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
    ChevronDown
} from 'lucide-vue-next';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';

// Componentes del módulo
import ActivityFilters from '../ActivityFilters.vue';
import ActivityLog from '../ActivityLog.vue';
import CamposPersonalizadosDisplay from '../CamposPersonalizadosDisplay.vue';
import ComentariosPanel from '@modules/Comentarios/Resources/js/components/ComentariosPanel.vue';

// Composable
import { useHitoDetalles } from '../../composables/useHitoDetalles';

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
                                        <div v-if="hito.descripcion">
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Descripción</h4>
                                            <p class="text-sm whitespace-pre-wrap">{{ hito.descripcion }}</p>
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <h4 class="text-sm font-medium text-muted-foreground mb-1">Fecha de Inicio</h4>
                                                <div class="flex items-center gap-1.5 text-sm">
                                                    <Calendar class="h-4 w-4" />
                                                    {{ formatDate(hito.fecha_inicio) }}
                                                </div>
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-medium text-muted-foreground mb-1">Fecha de Fin</h4>
                                                <div class="flex items-center gap-1.5 text-sm" :class="{ 'text-red-600': estadisticas?.esta_vencido }">
                                                    <Calendar class="h-4 w-4" />
                                                    {{ formatDate(hito.fecha_fin) }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Resumen de entregables -->
                                        <div v-if="estadisticas">
                                            <h4 class="text-sm font-medium text-muted-foreground mb-2">Resumen de Entregables</h4>
                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                                <div class="bg-gray-50 rounded-lg p-2 text-center">
                                                    <div class="text-lg font-semibold">{{ estadisticas.entregables_pendientes }}</div>
                                                    <div class="text-xs text-muted-foreground">Pendientes</div>
                                                </div>
                                                <div class="bg-blue-50 rounded-lg p-2 text-center">
                                                    <div class="text-lg font-semibold text-blue-700">{{ estadisticas.entregables_en_progreso }}</div>
                                                    <div class="text-xs text-muted-foreground">En Progreso</div>
                                                </div>
                                                <div class="bg-green-50 rounded-lg p-2 text-center">
                                                    <div class="text-lg font-semibold text-green-700">{{ estadisticas.entregables_completados }}</div>
                                                    <div class="text-xs text-muted-foreground">Completados</div>
                                                </div>
                                                <div class="bg-red-50 rounded-lg p-2 text-center">
                                                    <div class="text-lg font-semibold text-red-700">{{ estadisticas.entregables_cancelados }}</div>
                                                    <div class="text-xs text-muted-foreground">Cancelados</div>
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <!-- Etiquetas -->
                                <Card v-if="hito.etiquetas && hito.etiquetas.length > 0">
                                    <CardHeader class="pb-3">
                                        <div class="flex items-center gap-2">
                                            <Tag class="h-4 w-4" />
                                            <CardTitle class="text-base">Etiquetas</CardTitle>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="flex flex-wrap gap-2">
                                            <Badge
                                                v-for="etiqueta in hito.etiquetas"
                                                :key="etiqueta.id"
                                                variant="outline"
                                                class="px-2.5 py-1"
                                                :style="{
                                                    borderColor: etiqueta.color || '#94a3b8',
                                                    color: etiqueta.color || '#64748b',
                                                    backgroundColor: `${etiqueta.color}15` || '#f1f5f9'
                                                }"
                                            >
                                                {{ etiqueta.nombre }}
                                            </Badge>
                                        </div>
                                    </CardContent>
                                </Card>

                                <!-- Campos Personalizados -->
                                <CamposPersonalizadosDisplay
                                    v-if="data.camposPersonalizados && data.camposPersonalizados.length > 0"
                                    :campos="data.camposPersonalizados"
                                    :valores-campos="data.valoresCamposPersonalizados"
                                    descripcion="Información adicional del hito"
                                    :columns="2"
                                />

                                <!-- Jerarquía -->
                                <Card v-if="hito.parent || hito.ruta_completa">
                                    <CardHeader class="pb-3">
                                        <CardTitle class="text-base">Jerarquía</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div v-if="hito.ruta_completa" class="text-sm">
                                            <span class="text-muted-foreground">Ruta: </span>
                                            {{ hito.ruta_completa }}
                                        </div>
                                        <div v-if="hito.nivel !== undefined" class="mt-2">
                                            <Badge variant="outline">Nivel {{ hito.nivel }}</Badge>
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
                        <Button
                            v-if="(canEdit || data.canEdit) && hito"
                            variant="outline"
                            size="sm"
                            class="text-xs sm:text-sm px-2 sm:px-3"
                            @click="emit('edit-hito')"
                        >
                            <Edit class="h-3.5 w-3.5 sm:h-4 sm:w-4 sm:mr-1.5" />
                            <span class="hidden sm:inline">Editar</span>
                        </Button>
                    </div>
                    <Button variant="ghost" size="sm" class="text-xs sm:text-sm" @click="handleClose">
                        Cerrar
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
