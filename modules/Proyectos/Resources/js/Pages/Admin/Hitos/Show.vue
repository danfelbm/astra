<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Progress } from "@modules/Core/Resources/js/components/ui/progress";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@modules/Core/Resources/js/components/ui/tabs";
import { Avatar, AvatarFallback, AvatarImage } from "@modules/Core/Resources/js/components/ui/avatar";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger
} from "@modules/Core/Resources/js/components/ui/alert-dialog";
import {
    ArrowLeft, Edit, Trash2, Plus, Calendar, User, Target,
    Clock, CheckCircle, XCircle, AlertCircle, Copy, FileText, Tag,
    Eye, Download
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';
import type { BreadcrumbItem } from '@/types';
import type { Hito, Entregable } from '@modules/Proyectos/Resources/js/types/hitos';
import EntregablesTable from '@modules/Proyectos/Resources/js/components/EntregablesTable.vue';
import EntregablesFilters from '@modules/Proyectos/Resources/js/components/EntregablesFilters.vue';
import ActivityFilters from '@modules/Proyectos/Resources/js/components/ActivityFilters.vue';
import ActivityLog from '@modules/Proyectos/Resources/js/components/ActivityLog.vue';
import CamposPersonalizadosDisplay from '@modules/Proyectos/Resources/js/components/CamposPersonalizadosDisplay.vue';

interface Usuario {
    id: number;
    name: string;
    email: string;
    avatar?: string;
}

interface Actividad {
    id: number;
    description: string;
    causer: Usuario;
    created_at: string;
    subject_type?: string;
    event?: string;
    properties?: {
        attributes?: any;
        old?: any;
    };
}

interface Proyecto {
    id: number;
    nombre: string;
}

interface CampoPersonalizado {
    id: number;
    nombre: string;
    tipo: string;
    es_requerido: boolean;
    descripcion?: string;
    opciones?: any[];
}

interface Props {
    hito: Hito;
    proyecto: Proyecto;
    canEdit: boolean;
    canDelete: boolean;
    canManageEntregables: boolean;
    estadisticas: {
        total_entregables: number;
        entregables_completados: number;
        entregables_pendientes: number;
        entregables_en_progreso: number;
        dias_restantes: number | null;
        esta_vencido: boolean;
    };
    actividades?: Actividad[];
    usuariosActividades?: Usuario[];
    camposPersonalizados?: CampoPersonalizado[];
    valoresCamposPersonalizados?: Record<number, any>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Proyectos', href: '/admin/proyectos' },
    { title: props.proyecto.nombre, href: `/admin/proyectos/${props.proyecto.id}` },
    { title: 'Hitos', href: `/admin/proyectos/${props.proyecto.id}/hitos` },
    { title: props.hito.nombre, href: '#' },
];

// Tabs válidos para validación
const validTabs = ['detalles', 'entregables', 'actividad'];

// Estado para el tab activo - leer de URL query params
const getInitialTab = (): string => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabFromUrl = urlParams.get('tab');
    return tabFromUrl && validTabs.includes(tabFromUrl) ? tabFromUrl : 'detalles';
};

const activeTab = ref(getInitialTab());

// Sincronizar tab con URL usando query params
watch(activeTab, (newTab) => {
    const url = `/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}?tab=${newTab}`;
    router.get(url, {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: []
    });
});

// Estado para filtros de actividades
const filtrosActividades = ref({
    usuario_id: null as number | null,
    tipo_entidad: null as string | null,
    tipo_accion: null as string | null,
    fecha_inicio: null as string | null,
    fecha_fin: null as string | null
});

// Estado para filtros de entregables
const filtrosEntregables = ref({
    search: null as string | null,
    estado: null as string | null,
    prioridad: null as string | null,
    responsable_id: null as number | null,
    fecha_inicio: null as string | null,
    fecha_fin: null as string | null
});

// Funciones de navegación
const editarHito = () => {
    router.visit(`/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}/edit`);
};

const eliminarHito = () => {
    router.delete(`/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}`, {
        onSuccess: () => {
            toast.success('Hito eliminado exitosamente');
        },
        onError: () => {
            toast.error('Error al eliminar el hito');
        }
    });
};

const duplicarHito = () => {
    router.post(`/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}/duplicar`, {}, {
        onSuccess: () => {
            toast.success('Hito duplicado exitosamente');
        },
        onError: () => {
            toast.error('Error al duplicar el hito');
        }
    });
};

const crearEntregable = () => {
    router.visit(`/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}/entregables/create`);
};

const verEntregable = (entregable: Entregable) => {
    router.visit(`/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}/entregables/${entregable.id}`);
};

const editarEntregable = (entregable: Entregable) => {
    router.visit(`/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}/entregables/${entregable.id}/edit`);
};

const eliminarEntregable = (entregable: Entregable) => {
    router.delete(`/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}/entregables/${entregable.id}`, {
        onSuccess: () => {
            toast.success('Entregable eliminado exitosamente');
        },
        onError: () => {
            toast.error('Error al eliminar el entregable');
        }
    });
};

const completarEntregable = (entregable: Entregable) => {
    router.post(`/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}/entregables/${entregable.id}/completar`, {}, {
        onSuccess: () => {
            toast.success('Entregable completado exitosamente');
        },
        onError: () => {
            toast.error('Error al completar el entregable');
        }
    });
};

const marcarEnProgreso = (entregable: Entregable) => {
    router.post(`/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}/entregables/${entregable.id}/actualizar-estado`, {
        estado: 'en_progreso'
    }, {
        onSuccess: () => {
            toast.success('Entregable marcado como en progreso');
        },
        onError: () => {
            toast.error('Error al actualizar el estado');
        }
    });
};

// Funciones de utilidad
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
        baja: 'bg-gray-100 text-gray-600',
        media: 'bg-yellow-100 text-yellow-800',
        alta: 'bg-red-100 text-red-800',
    };
    return colors[prioridad] || 'bg-gray-100 text-gray-600';
};

const formatDate = (date: string | null) => {
    if (!date) return 'No definida';
    return format(parseISO(date), 'dd MMM yyyy', { locale: es });
};

const formatDateTime = (date: string | null) => {
    if (!date) return 'No definida';
    return format(parseISO(date), "dd MMM yyyy 'a las' HH:mm", { locale: es });
};

// Formatear valor de campo personalizado según su tipo
const formatCampoPersonalizado = (campo: CampoPersonalizado, valor: any) => {
    if (valor === null || valor === undefined || valor === '') {
        return 'No especificado';
    }

    switch (campo.tipo) {
        case 'date':
            return formatDate(valor);
        case 'checkbox':
            return valor ? 'Sí' : 'No';
        case 'select':
        case 'radio':
            // Buscar la etiqueta en las opciones
            const opcion = campo.opciones?.find((opt: any) => opt.value === valor);
            return opcion?.label || valor;
        default:
            return valor;
    }
};

// Entregables filtrados
const entregablesFiltrados = computed(() => {
    let result = props.hito.entregables || [];

    // Filtrar por búsqueda
    if (filtrosEntregables.value.search) {
        const searchLower = filtrosEntregables.value.search.toLowerCase();
        result = result.filter(e =>
            e.nombre.toLowerCase().includes(searchLower) ||
            (e.descripcion && e.descripcion.toLowerCase().includes(searchLower))
        );
    }

    // Filtrar por estado
    if (filtrosEntregables.value.estado) {
        result = result.filter(e => e.estado === filtrosEntregables.value.estado);
    }

    // Filtrar por prioridad
    if (filtrosEntregables.value.prioridad) {
        result = result.filter(e => e.prioridad === filtrosEntregables.value.prioridad);
    }

    // Filtrar por responsable
    if (filtrosEntregables.value.responsable_id) {
        result = result.filter(e => e.responsable?.id === filtrosEntregables.value.responsable_id);
    }

    // Filtrar por rango de fechas
    if (filtrosEntregables.value.fecha_inicio || filtrosEntregables.value.fecha_fin) {
        result = result.filter(e => {
            if (!e.fecha_fin) return false;
            const fecha = new Date(e.fecha_fin);
            if (filtrosEntregables.value.fecha_inicio) {
                const fechaInicio = new Date(filtrosEntregables.value.fecha_inicio);
                if (fecha < fechaInicio) return false;
            }
            if (filtrosEntregables.value.fecha_fin) {
                const fechaFin = new Date(filtrosEntregables.value.fecha_fin);
                fechaFin.setHours(23, 59, 59, 999);
                if (fecha > fechaFin) return false;
            }
            return true;
        });
    }

    return result;
});

// Obtener usuarios únicos de los entregables para los filtros
const usuariosEntregables = computed(() => {
    const usuarios = new Map();
    (props.hito.entregables || []).forEach(e => {
        if (e.responsable?.id) {
            usuarios.set(e.responsable.id, {
                id: e.responsable.id,
                name: e.responsable.name,
                email: e.responsable.email
            });
        }
    });
    return Array.from(usuarios.values());
});

// Actividades filtradas
const actividadesFiltradas = computed(() => {
    let result = props.actividades || [];

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
</script>

<template>
    <Head :title="`Hito: ${hito.nombre}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ hito.nombre }}</h1>
                    <p class="text-muted-foreground">Proyecto: {{ proyecto.nombre }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="`/admin/proyectos/${proyecto.id}/hitos`">
                        <Button variant="outline" size="sm">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Volver
                        </Button>
                    </Link>
                    <Button
                        v-if="canEdit"
                        @click="editarHito"
                        size="sm"
                        variant="outline"
                    >
                        <Edit class="h-4 w-4 mr-2" />
                        Editar
                    </Button>
                    <Button
                        @click="duplicarHito"
                        size="sm"
                        variant="outline"
                    >
                        <Copy class="h-4 w-4 mr-2" />
                        Duplicar
                    </Button>
                    <AlertDialog v-if="canDelete">
                        <AlertDialogTrigger asChild>
                            <Button variant="destructive" size="sm">
                                <Trash2 class="h-4 w-4 mr-2" />
                                Eliminar
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>¿Estás seguro?</AlertDialogTitle>
                                <AlertDialogDescription>
                                    Esta acción no se puede deshacer. Se eliminará permanentemente el hito
                                    y todos sus entregables asociados.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                <AlertDialogAction @click="eliminarHito">
                                    Eliminar
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Estado</CardTitle>
                        <Target class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <Badge :class="getEstadoColor(hito.estado)" class="mt-1">
                            {{ hito.estado }}
                        </Badge>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Progreso</CardTitle>
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ hito.porcentaje_completado }}%</div>
                        <Progress :model-value="hito.porcentaje_completado" class="mt-2 h-1" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Entregables</CardTitle>
                        <FileText class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ estadisticas.total_entregables }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ estadisticas.entregables_completados }} completados
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Fecha Fin</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-sm font-medium">{{ formatDate(hito.fecha_fin) }}</div>
                        <p v-if="estadisticas.dias_restantes !== null" class="text-xs" :class="estadisticas.esta_vencido ? 'text-red-600' : 'text-muted-foreground'">
                            {{ estadisticas.esta_vencido ? `Vencido hace ${Math.abs(estadisticas.dias_restantes)} días` : `${estadisticas.dias_restantes} días restantes` }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Responsable</CardTitle>
                        <User class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-sm font-medium">
                            {{ hito.responsable?.name || 'Sin asignar' }}
                        </div>
                        <p v-if="hito.responsable" class="text-xs text-muted-foreground">
                            {{ hito.responsable.email }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Tabs -->
            <Tabs v-model="activeTab" class="flex-1">
                <TabsList>
                    <TabsTrigger value="detalles">Detalles</TabsTrigger>
                    <TabsTrigger value="entregables">
                        Entregables
                        <Badge variant="secondary" class="ml-2">
                            {{ estadisticas.total_entregables }}
                        </Badge>
                    </TabsTrigger>
                    <TabsTrigger value="actividad">Actividad</TabsTrigger>
                </TabsList>

                <!-- Tab Detalles -->
                <TabsContent value="detalles" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Información General</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-if="hito.descripcion">
                                <h3 class="font-semibold mb-2">Descripción</h3>
                                <p class="text-sm text-muted-foreground whitespace-pre-wrap">{{ hito.descripcion }}</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <h3 class="font-semibold mb-2">Fecha de Inicio</h3>
                                    <p class="text-sm text-muted-foreground">{{ formatDate(hito.fecha_inicio) }}</p>
                                </div>
                                <div>
                                    <h3 class="font-semibold mb-2">Fecha de Fin</h3>
                                    <p class="text-sm text-muted-foreground">{{ formatDate(hito.fecha_fin) }}</p>
                                </div>
                                <div>
                                    <h3 class="font-semibold mb-2">Orden</h3>
                                    <p class="text-sm text-muted-foreground">{{ hito.orden }}</p>
                                </div>
                                <div>
                                    <h3 class="font-semibold mb-2">Estado</h3>
                                    <Badge :class="getEstadoColor(hito.estado)">
                                        {{ hito.estado }}
                                    </Badge>
                                </div>
                            </div>

                            <!-- Resumen de entregables -->
                            <div>
                                <h3 class="font-semibold mb-2">Resumen de Entregables</h3>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-muted-foreground">Pendientes</span>
                                        <Badge variant="outline">{{ estadisticas.entregables_pendientes }}</Badge>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-muted-foreground">En Progreso</span>
                                        <Badge variant="outline" class="bg-blue-50">{{ estadisticas.entregables_en_progreso }}</Badge>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-muted-foreground">Completados</span>
                                        <Badge variant="outline" class="bg-green-50">{{ estadisticas.entregables_completados }}</Badge>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Etiquetas -->
                    <Card v-if="hito.etiquetas && hito.etiquetas.length > 0">
                        <CardHeader>
                            <div class="flex items-center gap-2">
                                <Tag class="h-5 w-5" />
                                <CardTitle>Etiquetas</CardTitle>
                            </div>
                            <CardDescription>
                                Etiquetas asignadas a este hito para su organización y categorización
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="etiqueta in hito.etiquetas"
                                    :key="etiqueta.id"
                                    variant="outline"
                                    class="px-3 py-1.5"
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

                    <!-- Jerarquía -->
                    <Card v-if="hito.parent_id || hito.ruta_completa">
                        <CardHeader>
                            <CardTitle>Jerarquía</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div v-if="hito.ruta_completa">
                                    <h3 class="font-semibold mb-2">Ruta Completa</h3>
                                    <p class="text-sm text-muted-foreground">{{ hito.ruta_completa }}</p>
                                </div>
                                <div v-if="hito.nivel !== undefined">
                                    <h3 class="font-semibold mb-2">Nivel</h3>
                                    <Badge variant="outline">Nivel {{ hito.nivel }}</Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Campos Personalizados -->
                    <CamposPersonalizadosDisplay
                        v-if="camposPersonalizados && camposPersonalizados.length > 0"
                        :campos="camposPersonalizados"
                        :valores-campos="valoresCamposPersonalizados"
                        descripcion="Información adicional específica del hito"
                        :columns="2"
                    />

                    <!-- Metadata -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Información del Sistema</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-4 md:grid-cols-2 text-sm">
                                <div>
                                    <span class="text-muted-foreground">Creado el:</span>
                                    <p class="font-medium">{{ formatDateTime(hito.created_at) }}</p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Última actualización:</span>
                                    <p class="font-medium">{{ formatDateTime(hito.updated_at) }}</p>
                                </div>
                                <div v-if="hito.created_by_usuario">
                                    <span class="text-muted-foreground">Creado por:</span>
                                    <p class="font-medium">{{ hito.created_by_usuario.name }}</p>
                                </div>
                                <div v-if="hito.updated_by_usuario">
                                    <span class="text-muted-foreground">Actualizado por:</span>
                                    <p class="font-medium">{{ hito.updated_by_usuario.name }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Tab Entregables -->
                <TabsContent value="entregables" class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-semibold">Lista de Entregables</h2>
                        <Button
                            v-if="canManageEntregables"
                            @click="crearEntregable"
                            size="sm"
                        >
                            <Plus class="h-4 w-4 mr-2" />
                            Nuevo Entregable
                        </Button>
                    </div>

                    <!-- Filtros de entregables -->
                    <EntregablesFilters
                        v-if="hito.entregables && hito.entregables.length > 0"
                        v-model="filtrosEntregables"
                        :usuarios="usuariosEntregables"
                    />

                    <!-- Tabla de entregables -->
                    <Card>
                        <CardContent class="p-0">
                            <EntregablesTable
                                :entregables="entregablesFiltrados"
                                :proyecto-id="proyecto.id"
                                :hito-id="hito.id"
                                :can-edit="canManageEntregables"
                                :can-delete="canManageEntregables"
                                :can-complete="canManageEntregables"
                                :show-checkbox="false"
                                @view="verEntregable"
                                @edit="editarEntregable"
                                @delete="eliminarEntregable"
                                @complete="completarEntregable"
                                @mark-in-progress="marcarEnProgreso"
                            />
                        </CardContent>
                    </Card>

                    <!-- Mensaje si no hay entregables -->
                    <Card v-if="!hito.entregables || hito.entregables.length === 0">
                        <CardContent class="text-center py-8">
                            <FileText class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                            <p class="text-muted-foreground mb-4">No hay entregables asignados a este hito</p>
                            <Button
                                v-if="canManageEntregables"
                                @click="crearEntregable"
                                variant="outline"
                            >
                                <Plus class="h-4 w-4 mr-2" />
                                Crear Primer Entregable
                            </Button>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Tab Actividad -->
                <TabsContent value="actividad" class="space-y-4">
                    <!-- Filtros de actividades -->
                    <ActivityFilters
                        v-if="actividades && actividades.length > 0"
                        v-model="filtrosActividades"
                        :usuarios="usuariosActividades"
                        context-level="hito"
                    />

                    <!-- Log de actividades -->
                    <ActivityLog
                        :activities="actividadesFiltradas"
                        title="Historial de Actividad"
                        description="Registro de cambios y actividades del hito y sus entregables"
                        empty-message="No hay actividad registrada"
                    />
                </TabsContent>
            </Tabs>
        </div>
    </AdminLayout>
</template>