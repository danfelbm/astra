<script setup lang="ts">
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Progress } from "@modules/Core/Resources/js/components/ui/progress";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@modules/Core/Resources/js/components/ui/tabs";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@modules/Core/Resources/js/components/ui/table";
import { Avatar, AvatarFallback, AvatarImage } from "@modules/Core/Resources/js/components/ui/avatar";
import { Separator } from "@modules/Core/Resources/js/components/ui/separator";
import {
    Edit,
    Trash2,
    Calendar,
    User,
    AlertCircle,
    Clock,
    Target,
    Flag,
    CheckCircle,
    XCircle,
    PauseCircle,
    PlayCircle,
    Ban,
    Tag,
    Plus,
    FileText,
    Target as TargetIcon,
    ListTodo,
    CheckSquare,
    Users as UsersIcon,
    Image,
    Milestone,
    Info,
    UserPlus,
    ExternalLink,
    Download
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import EtiquetaDisplay from '@modules/Proyectos/Resources/js/components/EtiquetaDisplay.vue';
import EtiquetaSelector from '@modules/Proyectos/Resources/js/components/EtiquetaSelector.vue';
import ContratosList from '@modules/Proyectos/Resources/js/components/ContratosList.vue';
import ContratoTimeline from '@modules/Proyectos/Resources/js/components/ContratoTimeline.vue';
import EvidenciaFilters from "@modules/Proyectos/Resources/js/components/EvidenciaFilters.vue";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@modules/Core/Resources/js/components/ui/accordion";
import { useEtiquetas } from '@modules/Proyectos/Resources/js/composables/useEtiquetas';
import { ref, computed } from 'vue';
import type { Etiqueta, CategoriaEtiqueta } from '@modules/Proyectos/Resources/js/types/etiquetas';
import type { Contrato } from '@modules/Proyectos/Resources/js/types/contratos';
import type { Hito } from '@modules/Proyectos/Resources/js/types/hitos';
import HitoCard from '@modules/Proyectos/Resources/js/components/HitoCard.vue';
import EntregableItem from '@modules/Proyectos/Resources/js/components/EntregableItem.vue';

// Interfaces
interface User {
    id: number;
    name: string;
    email: string;
}

interface CampoPersonalizado {
    id: number;
    nombre: string;
    slug: string;
    tipo: string;
    valor?: any;
    valor_formateado?: string;
}

interface Activity {
    id: number;
    description: string;
    causer?: User;
    created_at: string;
    properties?: any;
}

interface Participante extends User {
    pivot?: {
        rol: string;
    };
}

interface Proyecto {
    id: number;
    nombre: string;
    descripcion?: string;
    fecha_inicio: string;
    fecha_fin?: string;
    estado: string;
    estado_label: string;
    estado_color: string;
    prioridad: string;
    prioridad_label: string;
    prioridad_color: string;
    responsable?: User;
    creador?: User;
    participantes?: Participante[];
    porcentaje_completado: number;
    duracion_dias?: number;
    campos_personalizados?: CampoPersonalizado[];
    etiquetas?: Etiqueta[];
    contratos?: Contrato[];
    hitos?: Hito[];
    activities?: Activity[];
    created_at: string;
    updated_at: string;
    activo: boolean;
}

interface Props {
    proyecto: Proyecto;
    categorias?: CategoriaEtiqueta[];
    totales?: {
        usuarios: number;
        contratos: number;
        evidencias: number;
        hitos: number;
    };
    canEdit?: boolean;
    canDelete?: boolean;
    canManageTags?: boolean;
    canViewContracts?: boolean;
    canCreateContracts?: boolean;
    canViewHitos?: boolean;
    canCreateHitos?: boolean;
    canEditHitos?: boolean;
    canDeleteHitos?: boolean;
    canManageEntregables?: boolean;
    estadisticasHitos?: {
        total: number;
        pendientes: number;
        en_progreso: number;
        completados: number;
        vencidos: number;
        progreso_general: number;
    };
}

const props = defineProps<Props>();

// Estado para el tab activo
const activeTab = ref('general');

// Estado para filtros de evidencias
const filtrosEvidencias = ref({
    contrato_id: null,
    fecha_inicio: null,
    fecha_fin: null,
    tipo: null,
    estado: null,
    usuario_id: null
});

// Estado para gestión de etiquetas
const editingTags = ref(false);
const selectedTagIds = ref<number[]>([]);
const { syncEtiquetasProyecto, isLoading } = useEtiquetas();

// Inicializar etiquetas seleccionadas
if (props.proyecto.etiquetas) {
    selectedTagIds.value = props.proyecto.etiquetas.map(e => e.id);
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Proyectos', href: '/admin/proyectos' },
    { title: props.proyecto.nombre, href: `/admin/proyectos/${props.proyecto.id}` },
];

// Función para eliminar proyecto
const deleteProyecto = () => {
    if (confirm(`¿Estás seguro de eliminar el proyecto "${props.proyecto.nombre}"?`)) {
        router.delete(`/admin/proyectos/${props.proyecto.id}`, {
            onSuccess: () => {
                toast.success('Proyecto eliminado exitosamente');
            },
            onError: () => {
                toast.error('Error al eliminar el proyecto');
            }
        });
    }
};

// Función para obtener el ícono del estado
const getEstadoIcon = (estado: string) => {
    const icons = {
        'planificacion': Target,
        'en_progreso': PlayCircle,
        'pausado': PauseCircle,
        'completado': CheckCircle,
        'cancelado': Ban
    };
    return icons[estado] || AlertCircle;
};

// Función para obtener color del badge de estado
const getEstadoColor = (estado: string) => {
    const colors: Record<string, string> = {
        'planificacion': 'bg-blue-100 text-blue-800',
        'en_progreso': 'bg-yellow-100 text-yellow-800',
        'pausado': 'bg-orange-100 text-orange-800',
        'completado': 'bg-green-100 text-green-800',
        'cancelado': 'bg-red-100 text-red-800',
    };
    return colors[estado] || 'bg-gray-100 text-gray-800';
};

// Función para obtener color del badge de prioridad
const getPrioridadColor = (prioridad: string) => {
    const colors: Record<string, string> = {
        'baja': 'bg-gray-100 text-gray-800',
        'media': 'bg-blue-100 text-blue-800',
        'alta': 'bg-orange-100 text-orange-800',
        'critica': 'bg-red-100 text-red-800',
    };
    return colors[prioridad] || 'bg-gray-100 text-gray-800';
};

// Función para formatear fecha
const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Función para formatear fecha relativa
const formatRelativeDate = (date: string) => {
    const days = Math.floor((new Date(date).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));
    if (days === 0) return 'Hoy';
    if (days === 1) return 'Mañana';
    if (days === -1) return 'Ayer';
    if (days > 0) return `En ${days} días`;
    return `Hace ${Math.abs(days)} días`;
};

// Funciones para gestión de etiquetas
const saveEtiquetas = async () => {
    try {
        await syncEtiquetasProyecto(props.proyecto.id, selectedTagIds.value);
        editingTags.value = false;
        toast.success('Etiquetas actualizadas exitosamente');
        // Recargar la página para actualizar las etiquetas
        router.reload({ only: ['proyecto'] });
    } catch (error) {
        toast.error('Error al actualizar las etiquetas');
    }
};

const cancelEditingTags = () => {
    // Restaurar las etiquetas originales
    selectedTagIds.value = props.proyecto.etiquetas?.map(e => e.id) || [];
    editingTags.value = false;
};

// Funciones para gestión de hitos
const navigateToHito = (hito: Hito) => {
    router.visit(`/admin/proyectos/${props.proyecto.id}/hitos/${hito.id}`);
};

const navigateToEditHito = (hito: Hito) => {
    router.visit(`/admin/proyectos/${props.proyecto.id}/hitos/${hito.id}/edit`);
};

const confirmDeleteHito = (hito: Hito) => {
    if (confirm(`¿Estás seguro de eliminar el hito "${hito.nombre}"? Se eliminarán también todos sus entregables.`)) {
        router.delete(`/admin/proyectos/${props.proyecto.id}/hitos/${hito.id}`, {
            onSuccess: () => {
                toast.success('Hito eliminado exitosamente');
                router.reload({ only: ['proyecto'] });
            },
            onError: () => {
                toast.error('Error al eliminar el hito');
            }
        });
    }
};

const duplicateHito = (hito: Hito) => {
    router.post(`/admin/proyectos/${props.proyecto.id}/hitos/${hito.id}/duplicar`, {}, {
        onSuccess: () => {
            toast.success('Hito duplicado exitosamente');
            router.reload({ only: ['proyecto'] });
        },
        onError: () => {
            toast.error('Error al duplicar el hito');
        }
    });
};

const navigateToAddEntregable = (hito: Hito) => {
    router.visit(`/admin/proyectos/${props.proyecto.id}/hitos/${hito.id}/entregables/create`);
};

const navigateToEntregables = (hito: Hito) => {
    router.visit(`/admin/proyectos/${props.proyecto.id}/hitos/${hito.id}/entregables`);
};

// Función para obtener todas las evidencias de los contratos
const todasLasEvidencias = computed(() => {
    const evidencias: any[] = [];
    if (props.proyecto.contratos) {
        props.proyecto.contratos.forEach(contrato => {
            if (contrato.obligaciones) {
                contrato.obligaciones.forEach(obligacion => {
                    if (obligacion.evidencias) {
                        obligacion.evidencias.forEach(evidencia => {
                            evidencias.push({
                                ...evidencia,
                                contrato_id: contrato.id,
                                contrato_numero: contrato.numero_contrato,
                                contrato_nombre: contrato.nombre,
                                obligacion_titulo: obligacion.titulo,
                                // Incluir referencia al contrato completo para el accordion
                                _contrato: contrato
                            });
                        });
                    }
                });
            }
        });
    }
    return evidencias;
});

// Evidencias filtradas según los filtros activos
const evidenciasFiltradas = computed(() => {
    let result = todasLasEvidencias.value;

    // Filtrar por contrato
    if (filtrosEvidencias.value.contrato_id) {
        result = result.filter(e => e.contrato_id === filtrosEvidencias.value.contrato_id);
    }

    // Filtrar por tipo
    if (filtrosEvidencias.value.tipo) {
        result = result.filter(e => e.tipo_evidencia === filtrosEvidencias.value.tipo);
    }

    // Filtrar por estado
    if (filtrosEvidencias.value.estado) {
        result = result.filter(e => e.estado === filtrosEvidencias.value.estado);
    }

    // Filtrar por usuario
    if (filtrosEvidencias.value.usuario_id) {
        result = result.filter(e => e.usuario?.id === filtrosEvidencias.value.usuario_id);
    }

    // Filtrar por rango de fechas
    if (filtrosEvidencias.value.fecha_inicio || filtrosEvidencias.value.fecha_fin) {
        result = result.filter(e => {
            const fecha = new Date(e.created_at);
            if (filtrosEvidencias.value.fecha_inicio) {
                const fechaInicio = new Date(filtrosEvidencias.value.fecha_inicio);
                if (fecha < fechaInicio) return false;
            }
            if (filtrosEvidencias.value.fecha_fin) {
                const fechaFin = new Date(filtrosEvidencias.value.fecha_fin);
                fechaFin.setHours(23, 59, 59, 999); // Incluir todo el día
                if (fecha > fechaFin) return false;
            }
            return true;
        });
    }

    return result;
});

// Evidencias agrupadas por contrato (para el Accordion)
const evidenciasAgrupadasPorContrato = computed(() => {
    const grupos: Record<number, any> = {};

    evidenciasFiltradas.value.forEach(evidencia => {
        if (!grupos[evidencia.contrato_id]) {
            grupos[evidencia.contrato_id] = {
                contrato: evidencia._contrato,
                evidencias: []
            };
        }
        grupos[evidencia.contrato_id].evidencias.push(evidencia);
    });

    return Object.values(grupos);
});

// Función para obtener el inicial del nombre
const getInitials = (name: string) => {
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};
</script>

<template>
    <Head :title="`Proyecto: ${proyecto.nombre}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header con acciones -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ proyecto.nombre }}
                    </h1>
                    <p v-if="proyecto.descripcion" class="mt-2 text-gray-600 dark:text-gray-400">
                        {{ proyecto.descripcion }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link
                        v-if="canEdit"
                        :href="`/admin/proyectos/${proyecto.id}/edit`"
                    >
                        <Button>
                            <Edit class="mr-2 h-4 w-4" />
                            Editar
                        </Button>
                    </Link>
                    <Button
                        v-if="canDelete"
                        variant="destructive"
                        @click="deleteProyecto"
                    >
                        <Trash2 class="mr-2 h-4 w-4" />
                        Eliminar
                    </Button>
                </div>
            </div>

            <!-- Navegación con Tabs -->
            <Tabs v-model="activeTab" class="w-full">
                <TabsList class="grid w-full grid-cols-5">
                    <TabsTrigger value="general">
                        <Info class="mr-2 h-4 w-4" />
                        General
                    </TabsTrigger>
                    <TabsTrigger value="usuarios">
                        <UsersIcon class="mr-2 h-4 w-4" />
                        Usuarios
                        <Badge v-if="totales?.usuarios" class="ml-2 h-5 px-1.5" variant="secondary">
                            {{ totales.usuarios }}
                        </Badge>
                    </TabsTrigger>
                    <TabsTrigger value="contratos">
                        <FileText class="mr-2 h-4 w-4" />
                        Contratos
                        <Badge v-if="totales?.contratos" class="ml-2 h-5 px-1.5" variant="secondary">
                            {{ totales.contratos }}
                        </Badge>
                    </TabsTrigger>
                    <TabsTrigger value="evidencias">
                        <Image class="mr-2 h-4 w-4" />
                        Evidencias
                        <Badge v-if="totales?.evidencias" class="ml-2 h-5 px-1.5" variant="secondary">
                            {{ totales.evidencias }}
                        </Badge>
                    </TabsTrigger>
                    <TabsTrigger value="hitos">
                        <Milestone class="mr-2 h-4 w-4" />
                        Hitos y Entregables
                        <Badge v-if="totales?.hitos" class="ml-2 h-5 px-1.5" variant="secondary">
                            {{ totales.hitos }}
                        </Badge>
                    </TabsTrigger>
                </TabsList>

                <!-- Tab de Información General -->
                <TabsContent value="general" class="space-y-4 mt-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Información principal -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Estado y Progreso -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Estado del Proyecto</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <component 
                                        :is="getEstadoIcon(proyecto.estado)" 
                                        class="h-5 w-5 text-gray-500" 
                                    />
                                    <span class="font-medium">Estado:</span>
                                    <Badge :class="getEstadoColor(proyecto.estado)">
                                        {{ proyecto.estado_label }}
                                    </Badge>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Flag class="h-5 w-5 text-gray-500" />
                                    <span class="font-medium">Prioridad:</span>
                                    <Badge :class="getPrioridadColor(proyecto.prioridad)">
                                        {{ proyecto.prioridad_label }}
                                    </Badge>
                                </div>
                            </div>

                            <!-- Barra de progreso -->
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Progreso</span>
                                    <span class="font-medium">{{ proyecto.porcentaje_completado }}%</span>
                                </div>
                                <Progress :value="proyecto.porcentaje_completado" class="h-2" />
                            </div>

                            <!-- Fechas -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                <div class="flex items-start gap-3">
                                    <Calendar class="h-5 w-5 text-gray-500 mt-0.5" />
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Fecha de inicio</p>
                                        <p class="font-medium">{{ formatDate(proyecto.fecha_inicio) }}</p>
                                        <p class="text-xs text-gray-500">{{ formatRelativeDate(proyecto.fecha_inicio) }}</p>
                                    </div>
                                </div>
                                <div v-if="proyecto.fecha_fin" class="flex items-start gap-3">
                                    <Calendar class="h-5 w-5 text-gray-500 mt-0.5" />
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Fecha de fin</p>
                                        <p class="font-medium">{{ formatDate(proyecto.fecha_fin) }}</p>
                                        <p class="text-xs text-gray-500">{{ formatRelativeDate(proyecto.fecha_fin) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Duración -->
                            <div v-if="proyecto.duracion_dias" class="flex items-center gap-3 pt-2">
                                <Clock class="h-5 w-5 text-gray-500" />
                                <div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Duración total:</span>
                                    <span class="font-medium ml-2">{{ proyecto.duracion_dias }} días</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Etiquetas del Proyecto -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="flex items-center gap-2">
                                <Tag class="h-5 w-5" />
                                Etiquetas
                            </CardTitle>
                            <Button
                                v-if="canManageTags && !editingTags"
                                variant="ghost"
                                size="sm"
                                @click="editingTags = true"
                            >
                                <Edit class="h-4 w-4" />
                            </Button>
                        </CardHeader>
                        <CardContent>
                            <!-- Modo lectura -->
                            <div v-if="!editingTags">
                                <EtiquetaDisplay
                                    v-if="proyecto.etiquetas && proyecto.etiquetas.length > 0"
                                    :etiquetas="proyecto.etiquetas"
                                    :show-categoria="true"
                                    :interactive="true"
                                    size="md"
                                />
                                <p v-else class="text-sm text-muted-foreground">
                                    No hay etiquetas asignadas
                                </p>
                            </div>

                            <!-- Modo edición -->
                            <div v-else class="space-y-4">
                                <EtiquetaSelector
                                    v-if="categorias"
                                    v-model="selectedTagIds"
                                    :categorias="categorias"
                                    :max-etiquetas="10"
                                    placeholder="Seleccionar etiquetas..."
                                    description="Selecciona hasta 10 etiquetas para este proyecto"
                                />
                                <div class="flex gap-2">
                                    <Button
                                        size="sm"
                                        @click="saveEtiquetas"
                                        :disabled="isLoading"
                                    >
                                        Guardar
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="cancelEditingTags"
                                        :disabled="isLoading"
                                    >
                                        Cancelar
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Campos Personalizados -->
                    <Card v-if="proyecto.campos_personalizados && proyecto.campos_personalizados.length > 0">
                        <CardHeader>
                            <CardTitle>Campos Personalizados</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div 
                                    v-for="campo in proyecto.campos_personalizados" 
                                    :key="campo.id"
                                    class="flex justify-between py-2 border-b last:border-b-0"
                                >
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ campo.nombre }}</span>
                                    <span class="font-medium">{{ campo.valor_formateado || campo.valor || '-' }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Contratos del Proyecto -->
                    <Card v-if="canViewContracts">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="h-5 w-5" />
                                Contratos
                            </CardTitle>
                            <Link
                                v-if="canCreateContracts"
                                :href="`/admin/proyectos/${proyecto.id}/contratos/create`"
                            >
                                <Button size="sm">
                                    <Plus class="h-4 w-4 mr-2" />
                                    Nuevo Contrato
                                </Button>
                            </Link>
                        </CardHeader>
                        <CardContent>
                            <div v-if="proyecto.contratos && proyecto.contratos.length > 0" class="space-y-4">
                                <!-- Timeline de contratos -->
                                <ContratoTimeline
                                    :contratos="proyecto.contratos"
                                    :mostrar-monto="true"
                                    :compacto="true"
                                />

                                <!-- Lista de contratos -->
                                <div class="mt-4">
                                    <p class="text-sm text-muted-foreground mb-3">
                                        {{ proyecto.contratos.length }} contrato(s) asociado(s)
                                    </p>
                                    <div class="grid gap-2">
                                        <Link
                                            v-for="contrato in proyecto.contratos.slice(0, 3)"
                                            :key="contrato.id"
                                            :href="`/admin/contratos/${contrato.id}`"
                                            class="block p-3 rounded-lg border hover:bg-muted/50 transition-colors"
                                        >
                                            <div class="flex items-start justify-between">
                                                <div>
                                                    <p class="font-medium">{{ contrato.nombre }}</p>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <Badge
                                                            :variant="contrato.estado === 'activo' ? 'default' :
                                                                     contrato.estado === 'finalizado' ? 'secondary' :
                                                                     contrato.estado === 'cancelado' ? 'destructive' : 'outline'"
                                                        >
                                                            {{ contrato.estado }}
                                                        </Badge>
                                                        <span class="text-xs text-muted-foreground">
                                                            {{ new Date(contrato.fecha_inicio).toLocaleDateString() }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div v-if="contrato.monto_total" class="text-right">
                                                    <p class="font-semibold">${{ contrato.monto_total.toLocaleString() }}</p>
                                                    <p class="text-xs text-muted-foreground">{{ contrato.moneda || 'USD' }}</p>
                                                </div>
                                            </div>
                                        </Link>
                                    </div>

                                    <Link
                                        v-if="proyecto.contratos.length > 3"
                                        :href="`/admin/contratos?proyecto_id=${proyecto.id}`"
                                        class="block text-center mt-3 text-sm text-primary hover:underline"
                                    >
                                        Ver todos los contratos ({{ proyecto.contratos.length }})
                                    </Link>
                                </div>
                            </div>
                            <div v-else class="text-center py-6">
                                <FileText class="h-10 w-10 mx-auto text-muted-foreground mb-3" />
                                <p class="text-muted-foreground mb-3">No hay contratos asociados</p>
                                <Link
                                    v-if="canCreateContracts"
                                    :href="`/admin/proyectos/${proyecto.id}/contratos/create`"
                                >
                                    <Button variant="outline" size="sm">
                                        <Plus class="h-4 w-4 mr-2" />
                                        Crear Primer Contrato
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Hitos del Proyecto -->
                    <Card v-if="canViewHitos">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="flex items-center gap-2">
                                <TargetIcon class="h-5 w-5" />
                                Hitos y Entregables
                            </CardTitle>
                            <div class="flex items-center gap-2">
                                <Link
                                    v-if="proyecto.hitos && proyecto.hitos.length > 0"
                                    :href="`/admin/proyectos/${proyecto.id}/hitos`"
                                >
                                    <Button variant="outline" size="sm">
                                        <ListTodo class="h-4 w-4 mr-2" />
                                        Ver Todos
                                    </Button>
                                </Link>
                                <Link
                                    v-if="canCreateHitos"
                                    :href="`/admin/proyectos/${proyecto.id}/hitos/create`"
                                >
                                    <Button size="sm">
                                        <Plus class="h-4 w-4 mr-2" />
                                        Nuevo Hito
                                    </Button>
                                </Link>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <!-- Estadísticas de Hitos -->
                            <div v-if="estadisticasHitos && estadisticasHitos.total > 0" class="mb-6">
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                    <div class="text-center">
                                        <p class="text-2xl font-bold">{{ estadisticasHitos.total }}</p>
                                        <p class="text-xs text-muted-foreground">Total Hitos</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-2xl font-bold text-blue-500">{{ estadisticasHitos.en_progreso }}</p>
                                        <p class="text-xs text-muted-foreground">En Progreso</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-2xl font-bold text-green-500">{{ estadisticasHitos.completados }}</p>
                                        <p class="text-xs text-muted-foreground">Completados</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-2xl font-bold" :class="{'text-red-500': estadisticasHitos.vencidos > 0}">
                                            {{ estadisticasHitos.vencidos }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">Vencidos</p>
                                    </div>
                                </div>

                                <!-- Barra de progreso general -->
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-muted-foreground">Progreso General</span>
                                        <span class="font-medium">{{ estadisticasHitos.progreso_general }}%</span>
                                    </div>
                                    <Progress :value="estadisticasHitos.progreso_general" class="h-2" />
                                </div>
                            </div>

                            <!-- Lista de Hitos -->
                            <div v-if="proyecto.hitos && proyecto.hitos.length > 0" class="space-y-4">
                                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    <HitoCard
                                        v-for="hito in proyecto.hitos.slice(0, 6)"
                                        :key="hito.id"
                                        :hito="hito"
                                        :canEdit="canEditHitos"
                                        :canDelete="canDeleteHitos"
                                        :canManageDeliverables="canManageEntregables"
                                        :showActions="true"
                                        @view="navigateToHito"
                                        @edit="navigateToEditHito"
                                        @delete="confirmDeleteHito"
                                        @duplicate="duplicateHito"
                                        @add-entregable="navigateToAddEntregable"
                                        @view-entregables="navigateToEntregables"
                                    />
                                </div>

                                <Link
                                    v-if="proyecto.hitos.length > 6"
                                    :href="`/admin/proyectos/${proyecto.id}/hitos`"
                                    class="block text-center mt-4 text-sm text-primary hover:underline"
                                >
                                    Ver todos los hitos ({{ proyecto.hitos.length }})
                                </Link>
                            </div>
                            <div v-else class="text-center py-6">
                                <TargetIcon class="h-10 w-10 mx-auto text-muted-foreground mb-3" />
                                <p class="text-muted-foreground mb-3">No hay hitos definidos para este proyecto</p>
                                <Link
                                    v-if="canCreateHitos"
                                    :href="`/admin/proyectos/${proyecto.id}/hitos/create`"
                                >
                                    <Button variant="outline" size="sm">
                                        <Plus class="h-4 w-4 mr-2" />
                                        Crear Primer Hito
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Actividad reciente -->
                    <Card v-if="proyecto.activities && proyecto.activities.length > 0">
                        <CardHeader>
                            <CardTitle>Actividad Reciente</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div 
                                    v-for="activity in proyecto.activities" 
                                    :key="activity.id"
                                    class="flex items-start gap-3 pb-3 border-b last:border-b-0"
                                >
                                    <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                        <User class="h-4 w-4 text-gray-500" />
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm">
                                            <span class="font-medium">{{ activity.causer?.name || 'Sistema' }}</span>
                                            {{ activity.description }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ formatRelativeDate(activity.created_at) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar de información -->
                <div class="space-y-4">
                    <!-- Información del responsable -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Responsable</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="proyecto.responsable" class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                    <User class="h-5 w-5 text-gray-500" />
                                </div>
                                <div>
                                    <p class="font-medium">{{ proyecto.responsable.name }}</p>
                                    <p class="text-sm text-gray-500">{{ proyecto.responsable.email }}</p>
                                </div>
                            </div>
                            <p v-else class="text-gray-500">Sin asignar</p>
                        </CardContent>
                    </Card>

                    <!-- Información del sistema -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información del Sistema</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">ID del Proyecto</p>
                                <p class="font-mono">#{{ proyecto.id }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Estado</p>
                                <Badge :class="proyecto.activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                                    {{ proyecto.activo ? 'Activo' : 'Inactivo' }}
                                </Badge>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Creado por</p>
                                <p class="font-medium">{{ proyecto.creador?.name || 'Sistema' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Fecha de creación</p>
                                <p class="text-sm">{{ formatDate(proyecto.created_at) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Última actualización</p>
                                <p class="text-sm">{{ formatDate(proyecto.updated_at) }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Acciones rápidas -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Acciones Rápidas</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <Link href="/admin/proyectos">
                                <Button variant="outline" class="w-full justify-start">
                                    Volver al listado
                                </Button>
                            </Link>
                            <Link v-if="canEdit" :href="`/admin/proyectos/${proyecto.id}/edit`">
                                <Button variant="outline" class="w-full justify-start">
                                    <Edit class="mr-2 h-4 w-4" />
                                    Editar proyecto
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>
                </div>
                    </div>
                </TabsContent>

                <!-- Tab de Usuarios del Proyecto -->
                <TabsContent value="usuarios" class="space-y-4 mt-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Usuarios del Proyecto</CardTitle>
                            <CardDescription>
                                Personas asignadas y colaborando en este proyecto
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <!-- Responsable del proyecto -->
                                <div class="pb-4 border-b">
                                    <h4 class="text-sm font-medium mb-3">Responsable del Proyecto</h4>
                                    <div v-if="proyecto.responsable" class="flex items-center gap-3">
                                        <Avatar class="h-10 w-10">
                                            <AvatarImage :src="proyecto.responsable.avatar" />
                                            <AvatarFallback>{{ getInitials(proyecto.responsable.name) }}</AvatarFallback>
                                        </Avatar>
                                        <div>
                                            <p class="font-medium">{{ proyecto.responsable.name }}</p>
                                            <p class="text-sm text-gray-500">{{ proyecto.responsable.email }}</p>
                                        </div>
                                        <Badge class="ml-auto">Responsable</Badge>
                                    </div>
                                    <p v-else class="text-gray-500">Sin responsable asignado</p>
                                </div>

                                <!-- Participantes -->
                                <div v-if="proyecto.participantes && proyecto.participantes.length > 0">
                                    <h4 class="text-sm font-medium mb-3">Participantes</h4>
                                    <div class="space-y-2">
                                        <div
                                            v-for="participante in proyecto.participantes"
                                            :key="participante.id"
                                            class="flex items-center gap-3 p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800"
                                        >
                                            <Avatar class="h-8 w-8">
                                                <AvatarImage :src="participante.avatar" />
                                                <AvatarFallback>{{ getInitials(participante.name) }}</AvatarFallback>
                                            </Avatar>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium">{{ participante.name }}</p>
                                                <p class="text-xs text-gray-500">{{ participante.email }}</p>
                                            </div>
                                            <Badge v-if="participante.pivot?.rol" variant="outline" class="text-xs">
                                                {{ participante.pivot.rol }}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                                <div v-else>
                                    <p class="text-gray-500 text-center py-4">No hay participantes adicionales</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Tab de Contratos -->
                <TabsContent value="contratos" class="space-y-4 mt-6">
                    <div v-if="canCreateContracts" class="flex justify-end">
                        <Link :href="`/admin/contratos/create?proyecto_id=${proyecto.id}`">
                            <Button>
                                <Plus class="mr-2 h-4 w-4" />
                                Nuevo Contrato
                            </Button>
                        </Link>
                    </div>

                    <Card v-if="proyecto.contratos && proyecto.contratos.length > 0">
                        <CardHeader>
                            <CardTitle>Contratos del Proyecto</CardTitle>
                            <CardDescription>
                                Lista de contratos asociados a este proyecto
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <ContratosList
                                    :contratos="proyecto.contratos"
                                    :proyecto-id="proyecto.id"
                                    :can-edit="canEdit"
                                    :can-delete="canDelete"
                                />
                            </div>
                        </CardContent>
                    </Card>
                    <Card v-else>
                        <CardContent class="py-8">
                            <div class="text-center">
                                <FileText class="mx-auto h-12 w-12 text-gray-400" />
                                <p class="mt-2 text-sm text-gray-600">No hay contratos asociados</p>
                                <Link v-if="canCreateContracts" :href="`/admin/contratos/create?proyecto_id=${proyecto.id}`" class="mt-4 inline-block">
                                    <Button variant="outline">
                                        <Plus class="mr-2 h-4 w-4" />
                                        Crear primer contrato
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Tab de Evidencias -->
                <TabsContent value="evidencias" class="space-y-4 mt-6">
                    <!-- Filtros de evidencias -->
                    <EvidenciaFilters
                        v-if="todasLasEvidencias.length > 0"
                        v-model="filtrosEvidencias"
                        :contratos="proyecto.contratos || []"
                        :evidencias="todasLasEvidencias"
                    />

                    <!-- Evidencias agrupadas por contrato -->
                    <div v-if="evidenciasAgrupadasPorContrato.length > 0">
                        <Accordion type="multiple" class="space-y-4" collapsible>
                            <AccordionItem
                                v-for="grupo in evidenciasAgrupadasPorContrato"
                                :key="grupo.contrato.id"
                                :value="`contrato-${grupo.contrato.id}`"
                                class="border rounded-lg bg-card"
                            >
                                <AccordionTrigger class="px-4 py-3 hover:no-underline hover:bg-gray-50 dark:hover:bg-gray-800 rounded-t-lg">
                                    <div class="flex items-center justify-between w-full pr-4">
                                        <div class="flex items-center gap-3">
                                            <FileText class="h-5 w-5 text-gray-500" />
                                            <div class="text-left">
                                                <div class="font-semibold">
                                                    {{ grupo.contrato.nombre }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ grupo.contrato.numero_contrato || 'Sin número' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <Badge variant="secondary">
                                                {{ grupo.evidencias.length }} evidencia(s)
                                            </Badge>
                                            <Link
                                                :href="`/admin/contratos/${grupo.contrato.id}`"
                                                @click.stop
                                                class="text-blue-600 hover:text-blue-800 flex items-center gap-1"
                                            >
                                                Ver contrato
                                                <ExternalLink class="h-3 w-3" />
                                            </Link>
                                        </div>
                                    </div>
                                </AccordionTrigger>
                                <AccordionContent class="px-4 pb-4">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Tipo</TableHead>
                                                <TableHead>Obligación</TableHead>
                                                <TableHead>Descripción</TableHead>
                                                <TableHead>Usuario</TableHead>
                                                <TableHead>Estado</TableHead>
                                                <TableHead>Fecha</TableHead>
                                                <TableHead class="text-right">Acciones</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow
                                                v-for="evidencia in grupo.evidencias"
                                                :key="evidencia.id"
                                                class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
                                            >
                                                <Link
                                                    :href="`/admin/contratos/${evidencia.contrato_id}/evidencias/${evidencia.id}`"
                                                    class="contents"
                                                >
                                                    <TableCell>
                                                        <Badge variant="outline">{{ evidencia.tipo_evidencia }}</Badge>
                                                    </TableCell>
                                                    <TableCell>{{ evidencia.obligacion_titulo }}</TableCell>
                                                    <TableCell>
                                                        <span class="text-sm text-gray-600">{{ evidencia.descripcion || '-' }}</span>
                                                    </TableCell>
                                                    <TableCell>
                                                        <span class="text-sm">{{ evidencia.usuario?.name || '-' }}</span>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            :class="{
                                                                'bg-yellow-100 text-yellow-800': evidencia.estado === 'pendiente',
                                                                'bg-green-100 text-green-800': evidencia.estado === 'aprobada',
                                                                'bg-red-100 text-red-800': evidencia.estado === 'rechazada'
                                                            }"
                                                        >
                                                            {{ evidencia.estado }}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>{{ formatDate(evidencia.created_at) }}</TableCell>
                                                    <TableCell class="text-right">
                                                        <a
                                                            v-if="evidencia.archivo_url"
                                                            :href="evidencia.archivo_url"
                                                            target="_blank"
                                                            class="inline-flex items-center text-blue-600 hover:text-blue-800"
                                                            @click.stop
                                                        >
                                                            <Download class="h-4 w-4" />
                                                        </a>
                                                    </TableCell>
                                                </Link>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </AccordionContent>
                            </AccordionItem>
                        </Accordion>
                    </div>

                    <!-- Estado vacío -->
                    <Card v-else-if="todasLasEvidencias.length === 0">
                        <CardContent class="py-8">
                            <div class="text-center">
                                <Image class="mx-auto h-12 w-12 text-gray-400" />
                                <p class="mt-2 text-sm text-gray-600">No hay evidencias cargadas</p>
                                <p class="text-xs text-gray-500 mt-1">Las evidencias se cargan desde los contratos</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Sin resultados después de filtrar -->
                    <Card v-else>
                        <CardContent class="py-8">
                            <div class="text-center">
                                <Image class="mx-auto h-12 w-12 text-gray-400" />
                                <p class="mt-2 text-sm text-gray-600">No hay evidencias que coincidan con los filtros</p>
                                <p class="text-xs text-gray-500 mt-1">Intenta ajustar los criterios de búsqueda</p>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Tab de Hitos y Entregables -->
                <TabsContent value="hitos" class="space-y-4 mt-6">
                    <div v-if="canCreateHitos" class="flex justify-end">
                        <Link :href="`/admin/proyectos/${proyecto.id}/hitos/create`">
                            <Button>
                                <Plus class="mr-2 h-4 w-4" />
                                Nuevo Hito
                            </Button>
                        </Link>
                    </div>

                    <Card v-if="proyecto.hitos && proyecto.hitos.length > 0">
                        <CardHeader>
                            <CardTitle>Hitos y Entregables</CardTitle>
                            <CardDescription>
                                Seguimiento de los hitos y entregables del proyecto
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-6">
                                <HitoCard
                                    v-for="hito in proyecto.hitos"
                                    :key="hito.id"
                                    :hito="hito"
                                    :proyecto-id="proyecto.id"
                                    :can-edit="canEditHitos"
                                    :can-delete="canDeleteHitos"
                                    :can-manage-entregables="canManageEntregables"
                                    :show-entregables="true"
                                    @view="navigateToHito"
                                    @edit="navigateToEditHito"
                                    @delete="confirmDeleteHito"
                                    @duplicate="duplicateHito"
                                    @add-entregable="navigateToAddEntregable"
                                    @view-entregables="navigateToEntregables"
                                />
                            </div>
                        </CardContent>
                    </Card>
                    <Card v-else>
                        <CardContent class="py-8">
                            <div class="text-center">
                                <Milestone class="mx-auto h-12 w-12 text-gray-400" />
                                <p class="mt-2 text-sm text-gray-600">No hay hitos definidos</p>
                                <Link v-if="canCreateHitos" :href="`/admin/proyectos/${proyecto.id}/hitos/create`" class="mt-4 inline-block">
                                    <Button variant="outline">
                                        <Plus class="mr-2 h-4 w-4" />
                                        Crear primer hito
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AdminLayout>
</template>