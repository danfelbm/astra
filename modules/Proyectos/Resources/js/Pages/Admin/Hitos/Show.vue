<script setup lang="ts">
import { ref } from 'vue';
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Progress } from "@modules/Core/Resources/js/components/ui/progress";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@modules/Core/Resources/js/components/ui/tabs";
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
    Clock, CheckCircle, XCircle, AlertCircle, Copy, FileText
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';
import type { BreadcrumbItem } from '@/types';
import type { Hito, Entregable } from '@modules/Proyectos/Resources/js/types/hitos';
import EntregablesList from '@modules/Proyectos/Resources/js/components/EntregablesList.vue';

interface Proyecto {
    id: number;
    nombre: string;
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
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Proyectos', href: '/admin/proyectos' },
    { title: props.proyecto.nombre, href: `/admin/proyectos/${props.proyecto.id}` },
    { title: 'Hitos', href: `/admin/proyectos/${props.proyecto.id}/hitos` },
    { title: props.hito.nombre, href: '#' },
];

// Estado local
const activeTab = ref('detalles');

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
                        <Progress :value="hito.porcentaje_completado" class="mt-2 h-1" />
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

                    <EntregablesList
                        :entregables="hito.entregables || []"
                        :can-edit="canManageEntregables"
                        :can-delete="canManageEntregables"
                        @edit="editarEntregable"
                        @delete="eliminarEntregable"
                    />

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
                    <Card>
                        <CardHeader>
                            <CardTitle>Historial de Actividad</CardTitle>
                            <CardDescription>
                                Registro de cambios y actividades relacionadas con este hito
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground text-center py-8">
                                El historial de actividad estará disponible próximamente
                            </p>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AdminLayout>
</template>