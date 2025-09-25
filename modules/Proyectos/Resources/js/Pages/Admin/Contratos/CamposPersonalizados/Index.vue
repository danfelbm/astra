<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Switch } from '@modules/Core/Resources/js/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@modules/Core/Resources/js/components/ui/table';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import {
    Plus,
    Edit,
    Trash2,
    GripVertical,
    Settings,
    Copy,
    AlertCircle,
    CheckCircle2
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import type { CampoPersonalizadoContrato } from '@modules/Proyectos/Resources/js/types/contratos';

// Props
const props = defineProps<{
    campos: {
        data: CampoPersonalizadoContrato[];
    };
    estadisticas: {
        total: number;
        activos: number;
        inactivos: number;
        requeridos: number;
        tipos: Record<string, number>;
    };
    authPermissions: string[];
}>();

// Breadcrumbs para navegación
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Contratos', href: '/admin/contratos' },
    { title: 'Campos Personalizados', href: '/admin/contratos/campos-personalizados' },
];

// Estado
const camposOrdenados = ref(props.campos.data);
const reordenando = ref(false);

// Computed
const canManageFields = computed(() =>
    props.authPermissions.includes('contratos.manage_fields')
);

// Métodos
const toggleActivo = (campo: CampoPersonalizadoContrato) => {
    router.post(
        route('admin.campos-personalizados-contrato.toggle-activo', campo.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Campo ${campo.activo ? 'desactivado' : 'activado'} correctamente`);
            }
        }
    );
};

const eliminarCampo = (campo: CampoPersonalizadoContrato) => {
    if (confirm(`¿Estás seguro de eliminar el campo "${campo.nombre}"? Esta acción no se puede deshacer.`)) {
        router.delete(
            route('admin.campos-personalizados-contrato.destroy', campo.id),
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Campo eliminado correctamente');
                },
                onError: (errors) => {
                    toast.error('Error al eliminar el campo');
                }
            }
        );
    }
};

const duplicarCampo = (campo: CampoPersonalizadoContrato) => {
    router.post(
        route('admin.campos-personalizados-contrato.duplicar', campo.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Campo duplicado correctamente');
            }
        }
    );
};

const iniciarReordenamiento = () => {
    reordenando.value = true;
};

const guardarOrden = () => {
    const orden = camposOrdenados.value.map((campo, index) => ({
        id: campo.id,
        orden: index + 1
    }));

    router.post(
        route('admin.campos-personalizados-contrato.reordenar'),
        { campos: orden },
        {
            preserveScroll: true,
            onSuccess: () => {
                reordenando.value = false;
                toast.success('Orden guardado correctamente');
            }
        }
    );
};

const cancelarReordenamiento = () => {
    camposOrdenados.value = [...props.campos.data];
    reordenando.value = false;
};

const moverCampo = (index: number, direccion: 'arriba' | 'abajo') => {
    const newIndex = direccion === 'arriba' ? index - 1 : index + 1;
    if (newIndex >= 0 && newIndex < camposOrdenados.value.length) {
        const temp = camposOrdenados.value[index];
        camposOrdenados.value[index] = camposOrdenados.value[newIndex];
        camposOrdenados.value[newIndex] = temp;
    }
};

const getTipoBadgeVariant = (tipo: string) => {
    const variants: Record<string, string> = {
        'text': 'default',
        'number': 'secondary',
        'date': 'outline',
        'textarea': 'default',
        'select': 'secondary',
        'checkbox': 'outline',
        'radio': 'outline',
        'file': 'destructive'
    };
    return variants[tipo] || 'default';
};

// Layout
defineOptions({
    layout: AdminLayout
});
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold tracking-tight">
                    Campos Personalizados de Contratos
                </h2>
                <p class="text-muted-foreground mt-2">
                    Gestiona los campos adicionales para los contratos
                </p>
            </div>
            <div class="flex gap-2">
                <Link
                    v-if="canManageFields"
                    :href="route('admin.campos-personalizados-contrato.create')"
                    as="button"
                >
                    <Button>
                        <Plus class="w-4 h-4 mr-2" />
                        Nuevo Campo
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="grid gap-4 md:grid-cols-4">
            <Card>
                <CardHeader class="pb-3">
                    <CardDescription>Total Campos</CardDescription>
                    <CardTitle class="text-2xl">{{ estadisticas.total }}</CardTitle>
                </CardHeader>
            </Card>

            <Card>
                <CardHeader class="pb-3">
                    <CardDescription>Campos Activos</CardDescription>
                    <CardTitle class="text-2xl text-green-600">
                        {{ estadisticas.activos }}
                    </CardTitle>
                </CardHeader>
            </Card>

            <Card>
                <CardHeader class="pb-3">
                    <CardDescription>Campos Requeridos</CardDescription>
                    <CardTitle class="text-2xl text-blue-600">
                        {{ estadisticas.requeridos }}
                    </CardTitle>
                </CardHeader>
            </Card>

            <Card>
                <CardHeader class="pb-3">
                    <CardDescription>Tipos Distintos</CardDescription>
                    <CardTitle class="text-2xl">
                        {{ Object.keys(estadisticas.tipos).length }}
                    </CardTitle>
                </CardHeader>
            </Card>
        </div>

        <!-- Tabla de campos -->
        <Card>
            <CardHeader>
                <div class="flex justify-between items-center">
                    <CardTitle>Campos Configurados</CardTitle>
                    <div v-if="reordenando" class="flex gap-2">
                        <Button
                            @click="guardarOrden"
                            size="sm"
                            variant="default"
                        >
                            Guardar Orden
                        </Button>
                        <Button
                            @click="cancelarReordenamiento"
                            size="sm"
                            variant="outline"
                        >
                            Cancelar
                        </Button>
                    </div>
                    <Button
                        v-else-if="canManageFields && campos.data.length > 1"
                        @click="iniciarReordenamiento"
                        size="sm"
                        variant="outline"
                    >
                        <GripVertical class="w-4 h-4 mr-2" />
                        Reordenar
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                <div v-if="campos.data.length === 0" class="text-center py-12">
                    <Settings class="w-12 h-12 mx-auto text-muted-foreground mb-4" />
                    <p class="text-muted-foreground">
                        No hay campos personalizados configurados
                    </p>
                    <Link
                        v-if="canManageFields"
                        :href="route('admin.campos-personalizados-contrato.create')"
                        class="mt-4 inline-block"
                    >
                        <Button>
                            <Plus class="w-4 h-4 mr-2" />
                            Crear Primer Campo
                        </Button>
                    </Link>
                </div>

                <div v-else class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead v-if="reordenando" class="w-12">Orden</TableHead>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Slug</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead class="text-center">Requerido</TableHead>
                                <TableHead class="text-center">Estado</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="(campo, index) in camposOrdenados"
                                :key="campo.id"
                                :class="{ 'opacity-50': !campo.activo }"
                            >
                                <!-- Columna de reordenamiento -->
                                <TableCell v-if="reordenando" class="w-12">
                                    <div class="flex flex-col gap-1">
                                        <Button
                                            @click="moverCampo(index, 'arriba')"
                                            size="sm"
                                            variant="ghost"
                                            :disabled="index === 0"
                                            class="h-6 w-6 p-0"
                                        >
                                            ↑
                                        </Button>
                                        <Button
                                            @click="moverCampo(index, 'abajo')"
                                            size="sm"
                                            variant="ghost"
                                            :disabled="index === camposOrdenados.length - 1"
                                            class="h-6 w-6 p-0"
                                        >
                                            ↓
                                        </Button>
                                    </div>
                                </TableCell>

                                <!-- Nombre -->
                                <TableCell class="font-medium">
                                    {{ campo.nombre }}
                                    <div v-if="campo.descripcion" class="text-xs text-muted-foreground mt-1">
                                        {{ campo.descripcion }}
                                    </div>
                                </TableCell>

                                <!-- Slug -->
                                <TableCell>
                                    <code class="text-xs bg-muted px-1 py-0.5 rounded">
                                        {{ campo.slug }}
                                    </code>
                                </TableCell>

                                <!-- Tipo -->
                                <TableCell>
                                    <Badge :variant="getTipoBadgeVariant(campo.tipo)">
                                        {{ campo.tipo }}
                                    </Badge>
                                </TableCell>

                                <!-- Requerido -->
                                <TableCell class="text-center">
                                    <Badge v-if="campo.es_requerido" variant="default">
                                        <CheckCircle2 class="w-3 h-3" />
                                    </Badge>
                                    <span v-else class="text-muted-foreground">-</span>
                                </TableCell>

                                <!-- Estado -->
                                <TableCell class="text-center">
                                    <Switch
                                        :checked="campo.activo"
                                        @update:checked="toggleActivo(campo)"
                                        :disabled="!canManageFields || reordenando"
                                    />
                                </TableCell>

                                <!-- Acciones -->
                                <TableCell class="text-right">
                                    <div v-if="!reordenando" class="flex justify-end gap-1">
                                        <Link
                                            v-if="canManageFields"
                                            :href="route('admin.campos-personalizados-contrato.edit', campo.id)"
                                            as="button"
                                        >
                                            <Button size="sm" variant="ghost">
                                                <Edit class="w-4 h-4" />
                                            </Button>
                                        </Link>

                                        <Button
                                            v-if="canManageFields"
                                            @click="duplicarCampo(campo)"
                                            size="sm"
                                            variant="ghost"
                                            title="Duplicar campo"
                                        >
                                            <Copy class="w-4 h-4" />
                                        </Button>

                                        <Button
                                            v-if="canManageFields"
                                            @click="eliminarCampo(campo)"
                                            size="sm"
                                            variant="ghost"
                                            class="text-destructive"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>

        <!-- Información adicional -->
        <Alert>
            <AlertCircle class="h-4 w-4" />
            <AlertDescription>
                Los campos personalizados permiten agregar información adicional a los contratos.
                Los cambios en los campos no afectan a los contratos existentes hasta que se actualicen.
            </AlertDescription>
        </Alert>
        </div>
    </AdminLayout>
</template>