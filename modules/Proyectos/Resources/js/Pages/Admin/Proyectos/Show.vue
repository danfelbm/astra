<script setup lang="ts">
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Progress } from "@modules/Core/Resources/js/components/ui/progress";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@modules/Core/Resources/js/components/ui/tabs";
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
    Activity,
    Eye,
    Download
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import EtiquetaDisplay from '@modules/Proyectos/Resources/js/components/EtiquetaDisplay.vue';
import EtiquetaSelector from '@modules/Proyectos/Resources/js/components/EtiquetaSelector.vue';
import ContratosList from '@modules/Proyectos/Resources/js/components/ContratosList.vue';
import ContratoTimeline from '@modules/Proyectos/Resources/js/components/ContratoTimeline.vue';
import EvidenciasDisplay from "@modules/Proyectos/Resources/js/components/EvidenciasDisplay.vue";
import ActivityFilters from "@modules/Proyectos/Resources/js/components/ActivityFilters.vue";
import ActivityLog from "@modules/Proyectos/Resources/js/components/ActivityLog.vue";
import { useEtiquetas } from '@modules/Proyectos/Resources/js/composables/useEtiquetas';
import { ref, computed, watch } from 'vue';
import type { Etiqueta, CategoriaEtiqueta } from '@modules/Proyectos/Resources/js/types/etiquetas';
import type { Contrato } from '@modules/Proyectos/Resources/js/types/contratos';
import type { Hito, Entregable } from '@modules/Proyectos/Resources/js/types/hitos';
import type { UploadedFile } from '@modules/Comentarios/Resources/js/types/comentarios';
import HitoCard from '@modules/Proyectos/Resources/js/components/HitoCard.vue';
import HitosDashboard from '@modules/Proyectos/Resources/js/components/HitosDashboard.vue';
import EntregableItem from '@modules/Proyectos/Resources/js/components/EntregableItem.vue';
import CamposPersonalizadosDisplay from '@modules/Proyectos/Resources/js/components/CamposPersonalizadosDisplay.vue';
import ProyectoEstadoCard from '@modules/Proyectos/Resources/js/components/ProyectoEstadoCard.vue';
import ProyectoInfoCard from '@modules/Proyectos/Resources/js/components/ProyectoInfoCard.vue';

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
    subject_type?: string;
    event?: string;
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

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface Props {
    proyecto: Proyecto;
    categorias?: CategoriaEtiqueta[];
    activities?: Activity[];
    usuariosActividades?: Usuario[];
    authPermissions?: string[];
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

// Tabs válidos para validación
const validTabs = ['general', 'contratos', 'hitos', 'evidencias', 'actividad'];

// Estado para el tab activo - leer de URL query params
const getInitialTab = (): string => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabFromUrl = urlParams.get('tab');
    // Validar que el tab existe, si no usar 'general' como default
    return tabFromUrl && validTabs.includes(tabFromUrl) ? tabFromUrl : 'general';
};

const activeTab = ref(getInitialTab());

// Sincronizar tab con URL usando query params
watch(activeTab, (newTab) => {
    // Construir URL con query param
    const url = `/admin/proyectos/${props.proyecto.id}?tab=${newTab}`;

    // Navegar sin recargar el componente ni cambiar el scroll
    router.get(url, {}, {
        preserveState: true,    // No recargar el componente
        preserveScroll: true,   // Mantener posición de scroll
        replace: true,          // Reemplazar en historial (no agregar entrada)
        only: []                // No recargar ningún prop desde el servidor
    });
});

// Computed para verificar si el usuario puede gestionar evidencias
const puedeGestionarEvidencias = computed(() => {
    return props.authPermissions?.includes('evidencias.aprobar') ||
           props.authPermissions?.includes('evidencias.rechazar') ||
           false;
});

// Estado para filtros de actividades
const filtrosActividades = ref({
    usuario_id: null as number | null,
    tipo_entidad: null as string | null,
    tipo_accion: null as string | null,
    fecha_inicio: null as string | null,
    fecha_fin: null as string | null
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

// Handler para completar un entregable (incluye observaciones y archivos)
const handleCompleteEntregable = (entregable: Entregable, observaciones: string, archivos: UploadedFile[]) => {
    router.post(`/admin/proyectos/${props.proyecto.id}/hitos/${entregable.hito_id}/entregables/${entregable.id}/completar`, {
        notas: observaciones,
        archivos: archivos,
        agregar_comentario: !!observaciones
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Entregable marcado como completado');
        },
        onError: () => {
            toast.error('Error al completar el entregable');
        }
    });
};

// Handler para actualizar estado de un entregable (incluye observaciones y archivos)
// Nota: La ruta admin usa POST a /actualizar-estado (diferente a la ruta user que usa PUT a /estado)
const handleUpdateEntregableStatus = (entregable: Entregable, estado: string, observaciones: string, archivos: UploadedFile[]) => {
    router.post(`/admin/proyectos/${props.proyecto.id}/hitos/${entregable.hito_id}/entregables/${entregable.id}/actualizar-estado`, {
        estado,
        observaciones,
        archivos: archivos,
        agregar_comentario: !!observaciones
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Estado del entregable actualizado');
        },
        onError: () => {
            toast.error('Error al actualizar el estado');
        }
    });
};

// Handler para editar un entregable
const handleEditEntregable = (entregable: Entregable, hito: Hito) => {
    router.visit(`/admin/proyectos/${props.proyecto.id}/hitos/${hito.id}/entregables/${entregable.id}/edit`);
};

// Handler para eliminar un entregable
const handleDeleteEntregable = (entregable: Entregable, hito: Hito) => {
    if (confirm(`¿Estás seguro de eliminar el entregable "${entregable.nombre}"?`)) {
        router.delete(`/admin/proyectos/${props.proyecto.id}/hitos/${hito.id}/entregables/${entregable.id}`, {
            onSuccess: () => {
                toast.success('Entregable eliminado exitosamente');
            },
            onError: () => {
                toast.error('Error al eliminar el entregable');
            }
        });
    }
};

// Handler para ver detalle de un entregable
const handleViewEntregable = (entregable: Entregable, hito: Hito) => {
    router.visit(`/admin/proyectos/${props.proyecto.id}/hitos/${hito.id}/entregables/${entregable.id}`);
};

// Actividades filtradas
const actividadesFiltradas = computed(() => {
    let result = props.activities || [];

    // Filtrar por usuario
    if (filtrosActividades.value.usuario_id) {
        result = result.filter(a => a.causer?.id === filtrosActividades.value.usuario_id);
    }

    // Filtrar por tipo de entidad
    if (filtrosActividades.value.tipo_entidad) {
        result = result.filter(a => a.subject_type === filtrosActividades.value.tipo_entidad);
    }

    // Filtrar por tipo de acción
    if (filtrosActividades.value.tipo_accion) {
        result = result.filter(a => a.event === filtrosActividades.value.tipo_accion);
    }

    // Filtrar por rango de fechas
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
                    <TabsTrigger value="hitos">
                        <Milestone class="mr-2 h-4 w-4" />
                        Hitos y Entregables
                        <Badge v-if="totales?.hitos" class="ml-2 h-5 px-1.5" variant="secondary">
                            {{ totales.hitos }}
                        </Badge>
                    </TabsTrigger>
                    <TabsTrigger value="evidencias">
                        <Image class="mr-2 h-4 w-4" />
                        Evidencias
                        <Badge v-if="totales?.evidencias" class="ml-2 h-5 px-1.5" variant="secondary">
                            {{ totales.evidencias }}
                        </Badge>
                    </TabsTrigger>
                    <TabsTrigger value="contratos">
                        <FileText class="mr-2 h-4 w-4" />
                        Contratos
                        <Badge v-if="totales?.contratos" class="ml-2 h-5 px-1.5" variant="secondary">
                            {{ totales.contratos }}
                        </Badge>
                    </TabsTrigger>
                    <TabsTrigger value="actividad">
                        <Activity class="mr-2 h-4 w-4" />
                        Actividad
                    </TabsTrigger>
                </TabsList>

                <!-- Tab de Información General -->
                <TabsContent value="general" class="space-y-4 mt-6">
                    <!-- Información del Proyecto (componente reutilizable) -->
                    <ProyectoInfoCard
                        :proyecto-id="proyecto.id"
                        :activo="proyecto.activo"
                        :responsable="proyecto.responsable"
                        :creador="proyecto.creador"
                        :created-at="proyecto.created_at"
                        :updated-at="proyecto.updated_at"
                        :show-id="true"
                        :show-activo="true"
                    />

                    <!-- Estado del Proyecto (componente reutilizable) -->
                    <ProyectoEstadoCard
                        :estado="proyecto.estado"
                        :estado-label="proyecto.estado_label"
                        :prioridad="proyecto.prioridad"
                        :prioridad-label="proyecto.prioridad_label"
                        :porcentaje-completado="proyecto.porcentaje_completado"
                        :fecha-inicio="proyecto.fecha_inicio"
                        :fecha-fin="proyecto.fecha_fin"
                        :duracion-dias="proyecto.duracion_dias"
                    />

                    <!-- Etiquetas del Proyecto -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle>
                                Etiquetas
                            </CardTitle>
                            <Button
                                v-if="canManageTags && !editingTags"
                                variant="ghost"
                                size="sm"
                                @click="editingTags = true"
                            >
                                <Edit class="mr-1 h-4 w-4" />
                                Editar
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
                    <CamposPersonalizadosDisplay
                        v-if="proyecto.campos_personalizados && proyecto.campos_personalizados.length > 0"
                        :valores="proyecto.campos_personalizados"
                    />

                    <!-- Actividad Reciente -->
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
                                    :show-proyecto="false"
                                    :show-actions="true"
                                    actions-style="buttons"
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

                <!-- Tab de Hitos y Entregables -->
                <TabsContent value="hitos" class="space-y-4 mt-6">
                    <HitosDashboard
                        v-if="canViewHitos"
                        :hitos="proyecto.hitos || []"
                        :proyecto-id="proyecto.id"
                        :can-edit="canEditHitos"
                        :can-delete="canDeleteHitos"
                        :can-manage-deliverables="canManageEntregables"
                        :can-complete="canManageEntregables"
                        base-url="/admin/proyectos"
                        @view-hito="navigateToHito"
                        @edit-hito="navigateToEditHito"
                        @delete-hito="confirmDeleteHito"
                        @add-entregable="navigateToAddEntregable"
                        @view-entregable="handleViewEntregable"
                        @complete-entregable="handleCompleteEntregable"
                        @update-entregable-status="handleUpdateEntregableStatus"
                        @edit-entregable="handleEditEntregable"
                        @delete-entregable="handleDeleteEntregable"
                    />
                </TabsContent>

                <!-- Tab de Evidencias -->
                <TabsContent value="evidencias" class="space-y-4 mt-6">
                    <EvidenciasDisplay
                        :contratos="proyecto.contratos || []"
                        :proyecto-id="proyecto.id"
                        modo="admin"
                        :puede-gestionar-estado="puedeGestionarEvidencias"
                        :format-date="formatDate"
                    />
                </TabsContent>

                <!-- Tab de Actividad -->
                <TabsContent value="actividad" class="space-y-4 mt-6">
                    <!-- Filtros de actividades -->
                    <ActivityFilters
                        v-if="activities && activities.length > 0"
                        v-model="filtrosActividades"
                        :usuarios="usuariosActividades"
                        context-level="proyecto"
                    />

                    <!-- Log de actividades -->
                    <ActivityLog
                        :activities="actividadesFiltradas"
                        title="Historial de Actividad"
                        description="Registro completo de cambios y eventos del proyecto, hitos y entregables"
                        empty-message="No hay actividad registrada"
                    />
                </TabsContent>
            </Tabs>
        </div>
    </AdminLayout>
</template>