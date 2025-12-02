<script setup lang="ts">
/**
 * EntregableDetallesModal - Modal de detalles completos de un Entregable
 *
 * Muestra 5 tabs: detalles, equipo, evidencias, comentarios, actividad.
 * Carga datos via API usando el composable useEntregableDetalles.
 * Soporta deeplinks para tab y paginación de comentarios.
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
    ExternalLink, MessageSquare, Activity, RefreshCw, Image, AlertCircle, CheckCircle
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

// Composable
import { useEntregableDetalles } from '../../composables/useEntregableDetalles';

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

// Tabs válidos
const validTabs = ['detalles', 'equipo', 'evidencias', 'comentarios', 'actividad'];

// Estado del tab activo
const activeTab = ref(validTabs.includes(props.initialTab) ? props.initialTab : 'detalles');

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
        <DialogContent class="max-w-4xl max-h-[85vh] flex flex-col p-0 gap-0">
            <!-- Header fijo -->
            <DialogHeader class="flex-shrink-0 px-6 pt-6 pb-4 border-b">
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
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <DialogTitle class="text-xl truncate flex items-center gap-2">
                                <FileText class="h-5 w-5 flex-shrink-0" />
                                {{ entregable.nombre }}
                            </DialogTitle>
                            <DialogDescription class="mt-1">
                                Hito: {{ hito?.nombre }} · Proyecto: {{ proyecto?.nombre }}
                            </DialogDescription>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <Badge :class="getEstadoColor(entregable.estado)">
                                {{ entregable.estado_label || entregable.estado }}
                            </Badge>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="handleRefresh"
                                :disabled="loading"
                                title="Actualizar"
                            >
                                <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
                            </Button>
                        </div>
                    </div>

                    <!-- Resumen rápido -->
                    <div class="flex flex-wrap gap-4 mt-3 text-sm text-muted-foreground">
                        <div class="flex items-center gap-1.5">
                            <Flag class="h-4 w-4" :class="getPrioridadColor(entregable.prioridad)" />
                            <span class="capitalize">{{ entregable.prioridad }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <Progress :model-value="entregable.porcentaje_completado || 0" class="w-16 h-2" />
                            <span>{{ entregable.porcentaje_completado || 0 }}%</span>
                        </div>
                        <div v-if="diasRestantes !== null" class="flex items-center gap-1" :class="{ 'text-red-600': estaVencido }">
                            <Clock class="h-4 w-4" />
                            <span>
                                {{ estaVencido ? `Vencido hace ${Math.abs(diasRestantes)} días` :
                                   diasRestantes === 0 ? 'Vence hoy' : `${diasRestantes} días` }}
                            </span>
                        </div>
                        <div v-if="entregable.responsable" class="flex items-center gap-1">
                            <User class="h-4 w-4" />
                            <span>{{ entregable.responsable.name }}</span>
                        </div>
                    </div>
                </template>
            </DialogHeader>

            <!-- Contenido con scroll -->
            <div class="flex-1 overflow-hidden min-h-0">
                <Tabs v-model="activeTab" class="h-full flex flex-col">
                    <!-- TabsList fijo -->
                    <TabsList class="flex-shrink-0 mx-6 mt-4 justify-start flex-wrap">
                        <TabsTrigger value="detalles">
                            <FileText class="h-4 w-4 mr-1.5" />
                            Detalles
                        </TabsTrigger>
                        <TabsTrigger value="equipo">
                            <Users class="h-4 w-4 mr-1.5" />
                            Equipo
                        </TabsTrigger>
                        <TabsTrigger value="evidencias">
                            <Image class="h-4 w-4 mr-1.5" />
                            Evidencias
                            <Badge v-if="entregable?.evidencias?.length" variant="secondary" class="ml-1.5 h-5 px-1.5 text-xs">
                                {{ entregable.evidencias.length }}
                            </Badge>
                        </TabsTrigger>
                        <TabsTrigger value="comentarios">
                            <MessageSquare class="h-4 w-4 mr-1.5" />
                            Comentarios
                            <Badge v-if="entregable?.total_comentarios" variant="secondary" class="ml-1.5 h-5 px-1.5 text-xs">
                                {{ entregable.total_comentarios }}
                            </Badge>
                        </TabsTrigger>
                        <TabsTrigger value="actividad">
                            <Activity class="h-4 w-4 mr-1.5" />
                            Actividad
                        </TabsTrigger>
                    </TabsList>

                    <!-- Contenido de tabs con scroll -->
                    <ScrollArea class="flex-1 px-6 py-4">
                        <!-- Loading skeleton -->
                        <div v-if="loading && !entregable" class="space-y-4">
                            <Skeleton class="h-32 w-full" />
                            <Skeleton class="h-32 w-full" />
                        </div>

                        <!-- Tab Detalles -->
                        <TabsContent value="detalles" class="mt-0 space-y-4">
                            <template v-if="entregable">
                                <!-- Información General -->
                                <Card>
                                    <CardHeader class="pb-3">
                                        <CardTitle class="text-base">Información General</CardTitle>
                                    </CardHeader>
                                    <CardContent class="space-y-4">
                                        <div v-if="entregable.descripcion">
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Descripción</h4>
                                            <p class="text-sm whitespace-pre-wrap">{{ entregable.descripcion }}</p>
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <h4 class="text-sm font-medium text-muted-foreground mb-1">Fecha de Inicio</h4>
                                                <div class="flex items-center gap-1.5 text-sm">
                                                    <Calendar class="h-4 w-4" />
                                                    {{ formatDate(entregable.fecha_inicio) }}
                                                </div>
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-medium text-muted-foreground mb-1">Fecha de Fin</h4>
                                                <div class="flex items-center gap-1.5 text-sm" :class="{ 'text-red-600': estaVencido }">
                                                    <Calendar class="h-4 w-4" />
                                                    {{ formatDate(entregable.fecha_fin) }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Notas -->
                                        <div v-if="entregable.notas">
                                            <h4 class="text-sm font-medium text-muted-foreground mb-1">Notas</h4>
                                            <Alert>
                                                <AlertCircle class="h-4 w-4" />
                                                <AlertDescription>
                                                    {{ entregable.notas }}
                                                </AlertDescription>
                                            </Alert>
                                        </div>

                                        <!-- Info de completado -->
                                        <div v-if="entregable.estado === 'completado'" class="bg-green-50 border border-green-200 rounded-lg p-3">
                                            <div class="flex items-center gap-2 text-green-700 mb-2">
                                                <CheckCircle class="h-5 w-5" />
                                                <span class="font-medium">Completado</span>
                                            </div>
                                            <div class="text-sm text-green-600 space-y-1">
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

                                <!-- Etiquetas -->
                                <Card v-if="entregable.etiquetas && entregable.etiquetas.length > 0">
                                    <CardHeader class="pb-3">
                                        <div class="flex items-center gap-2">
                                            <Tag class="h-4 w-4" />
                                            <CardTitle class="text-base">Etiquetas</CardTitle>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="flex flex-wrap gap-2">
                                            <Badge
                                                v-for="etiqueta in entregable.etiquetas"
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
                                    descripcion="Información adicional del entregable"
                                    :columns="2"
                                />

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
                                <!-- Responsable Principal -->
                                <Card>
                                    <CardHeader class="pb-3">
                                        <CardTitle class="text-base">Responsable Principal</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div v-if="entregable.responsable" class="flex items-center gap-3">
                                            <Avatar class="h-10 w-10">
                                                <AvatarImage v-if="entregable.responsable.avatar" :src="entregable.responsable.avatar" />
                                                <AvatarFallback>{{ entregable.responsable.name.substring(0, 2).toUpperCase() }}</AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <p class="font-medium">{{ entregable.responsable.name }}</p>
                                                <p class="text-sm text-muted-foreground">{{ entregable.responsable.email }}</p>
                                            </div>
                                        </div>
                                        <p v-else class="text-muted-foreground">No hay responsable asignado</p>
                                    </CardContent>
                                </Card>

                                <!-- Colaboradores -->
                                <Card>
                                    <CardHeader class="pb-3">
                                        <CardTitle class="text-base">Colaboradores</CardTitle>
                                        <CardDescription>{{ data.usuariosAsignados?.length || 0 }} usuarios asignados</CardDescription>
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
                                        <p v-else class="text-muted-foreground">No hay colaboradores asignados</p>
                                    </CardContent>
                                </Card>
                            </template>
                        </TabsContent>

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
                            <ComentariosPanel
                                v-if="entregableId"
                                commentable-type="entregables"
                                :commentable-id="entregableId"
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
                    </ScrollArea>
                </Tabs>
            </div>

            <!-- Footer fijo -->
            <DialogFooter class="flex-shrink-0 px-6 py-4 border-t bg-muted/30">
                <div class="flex items-center justify-between w-full gap-2">
                    <div class="flex gap-2">
                        <Link v-if="entregableShowUrl" :href="entregableShowUrl">
                            <Button variant="outline" size="sm">
                                <ExternalLink class="h-4 w-4 mr-1.5" />
                                Abrir página completa
                            </Button>
                        </Link>
                        <Button
                            v-if="(canEdit || data.canEdit) && entregable"
                            variant="outline"
                            size="sm"
                            @click="emit('edit-entregable')"
                        >
                            <Edit class="h-4 w-4 mr-1.5" />
                            Editar
                        </Button>
                    </div>
                    <Button variant="ghost" size="sm" @click="handleClose">
                        Cerrar
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
