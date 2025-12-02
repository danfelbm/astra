<script setup lang="ts">
/**
 * Página de Mis Hitos - Usa HitosDashboard con filtro por proyecto
 *
 * Muestra todos los hitos a los que el usuario tiene acceso,
 * permitiendo filtrar por proyecto.
 */
import { computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import UserLayout from '@modules/Core/Resources/js/layouts/UserLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Target, Clock, CheckCircle, AlertCircle, XCircle } from 'lucide-vue-next';
import HitosDashboard from '@modules/Proyectos/Resources/js/components/HitosDashboard.vue';
import type { Hito, Entregable } from '@modules/Proyectos/Resources/js/types/hitos';
import type { UploadedFile } from '@modules/Comentarios/Resources/js/types/comentarios';
import type { PageProps } from '@/types';
import { toast } from 'vue-sonner';

// Props con tipos
interface ProyectoOption {
    id: number;
    nombre: string;
}

interface Props {
    hitos: Hito[];
    proyectos: ProyectoOption[];
    filters: {
        search?: string;
        estado?: string;
        proyecto_id?: string;
    };
    estadisticas: {
        total: number;
        pendientes: number;
        en_progreso: number;
        completados: number;
        vencidos: number;
        proximos_vencer: number;
    };
    canView: boolean;
    canEdit: boolean;
    canManageDeliverables: boolean;
    canComplete: boolean;
    canUpdateProgress: boolean;
}

const props = defineProps<Props>();
const page = usePage<PageProps>();

// Usuario actual
const currentUserId = computed(() => (page.props.auth as any)?.user?.id);

// Handler para editar un hito
const handleEditHito = (hito: Hito) => {
    if (!hito.proyecto_id) return;
    router.visit(`/miembro/mis-proyectos/${hito.proyecto_id}/hitos/${hito.id}/edit`);
};

// Handler para ver detalle de un hito
const handleViewHito = (hito: Hito) => {
    router.visit(`/miembro/mis-hitos/${hito.id}`);
};

// Handler para añadir entregable a un hito
const handleAddEntregable = (hito: Hito) => {
    if (!hito.proyecto_id) return;
    router.visit(`/miembro/mis-proyectos/${hito.proyecto_id}/hitos/${hito.id}/entregables/create`);
};

// Handler para completar un entregable
const handleCompleteEntregable = (entregable: Entregable, observaciones: string, archivos: UploadedFile[]) => {
    router.post(`/miembro/mis-hitos/${entregable.hito_id}/entregables/${entregable.id}/completar`, {
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

// Handler para actualizar estado de un entregable
const handleUpdateEntregableStatus = (entregable: Entregable, estado: string, observaciones: string, archivos: UploadedFile[]) => {
    router.put(`/miembro/mis-hitos/${entregable.hito_id}/entregables/${entregable.id}/estado`, {
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
    if (!hito.proyecto_id) return;
    router.visit(`/miembro/mis-proyectos/${hito.proyecto_id}/hitos/${hito.id}/entregables/${entregable.id}/edit`);
};

// Handler para ver detalle de un entregable
const handleViewEntregable = (entregable: Entregable, hito: Hito) => {
    if (!hito.proyecto_id) return;
    router.visit(`/miembro/mis-proyectos/${hito.proyecto_id}/hitos/${hito.id}/entregables/${entregable.id}`);
};

// Handler para filtro de proyecto
const handleFilterProyecto = (proyectoId: number | null) => {
    router.get('/miembro/mis-hitos', {
        ...props.filters,
        proyecto_id: proyectoId || undefined
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <UserLayout>
        <Head title="Mis Hitos" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4">
                <h1 class="text-2xl font-bold tracking-tight">Mis Hitos</h1>
                <p class="text-muted-foreground">
                    Gestiona y da seguimiento a los hitos y entregables de tus proyectos
                </p>
            </div>

            <!-- Estadísticas -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-6">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total</CardTitle>
                        <Target class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ estadisticas.total }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Pendientes</CardTitle>
                        <AlertCircle class="h-4 w-4 text-yellow-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ estadisticas.pendientes }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">En Progreso</CardTitle>
                        <Clock class="h-4 w-4 text-blue-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ estadisticas.en_progreso }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Completados</CardTitle>
                        <CheckCircle class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ estadisticas.completados }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Vencidos</CardTitle>
                        <XCircle class="h-4 w-4 text-red-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ estadisticas.vencidos }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Por Vencer</CardTitle>
                        <AlertCircle class="h-4 w-4 text-orange-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-orange-600">{{ estadisticas.proximos_vencer }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Dashboard de Hitos -->
            <HitosDashboard
                :hitos="hitos"
                :proyectos="proyectos"
                :selected-proyecto-id="filters.proyecto_id ? parseInt(filters.proyecto_id) : null"
                :can-edit="canEdit"
                :can-manage-deliverables="canManageDeliverables"
                :can-complete="canComplete"
                :show-view-detail="false"
                :show-proyecto-in-hito="true"
                base-url="/miembro/mis-proyectos"
                @edit-hito="handleEditHito"
                @view-hito="handleViewHito"
                @add-entregable="handleAddEntregable"
                @view-entregable="handleViewEntregable"
                @complete-entregable="handleCompleteEntregable"
                @update-entregable-status="handleUpdateEntregableStatus"
                @edit-entregable="handleEditEntregable"
                @filter-proyecto="handleFilterProyecto"
            />
        </div>
    </UserLayout>
</template>
