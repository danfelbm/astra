<script setup lang="ts">
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Progress } from "@modules/Core/Resources/js/components/ui/progress";
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
    FileText
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import EtiquetaDisplay from '@modules/Proyectos/Resources/js/components/EtiquetaDisplay.vue';
import EtiquetaSelector from '@modules/Proyectos/Resources/js/components/EtiquetaSelector.vue';
import ContratosList from '@modules/Proyectos/Resources/js/components/ContratosList.vue';
import ContratoTimeline from '@modules/Proyectos/Resources/js/components/ContratoTimeline.vue';
import { useEtiquetas } from '@modules/Proyectos/Resources/js/composables/useEtiquetas';
import { ref, computed } from 'vue';
import type { Etiqueta, CategoriaEtiqueta } from '@modules/Proyectos/Resources/js/types/etiquetas';
import type { Contrato } from '@modules/Proyectos/Resources/js/types/contratos';

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
    porcentaje_completado: number;
    duracion_dias?: number;
    campos_personalizados?: CampoPersonalizado[];
    etiquetas?: Etiqueta[];
    contratos?: Contrato[];
    activities?: Activity[];
    created_at: string;
    updated_at: string;
    activo: boolean;
}

interface Props {
    proyecto: Proyecto;
    categorias?: CategoriaEtiqueta[];
    canEdit?: boolean;
    canDelete?: boolean;
    canManageTags?: boolean;
    canViewContracts?: boolean;
    canCreateContracts?: boolean;
}

const props = defineProps<Props>();

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

            <!-- Grid de información -->
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
        </div>
    </AdminLayout>
</template>