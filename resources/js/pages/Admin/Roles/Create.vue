<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Switch } from '@/components/ui/switch';
import { ref, computed, watch } from 'vue';
import { ArrowLeft, Save, Shield, Users, Target, Lock } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem } from '@/types';

interface Segment {
    id: number;
    name: string;
    description?: string;
    user_count?: number;
}

interface PermissionGroup {
    label: string;
    permissions: Record<string, string>;
}

interface AvailablePermissions {
    administrative: Record<string, PermissionGroup>;
    frontend: Record<string, PermissionGroup>;
}

interface AvailableModules {
    administrative: Record<string, string>;
    frontend: Record<string, string>;
}

interface Props {
    segments: Segment[];
    availablePermissions: AvailablePermissions;
    modules: AvailableModules;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Roles y Permisos', href: '/admin/roles' },
    { title: 'Crear Rol', href: '#' },
];

const form = useForm({
    name: '',
    display_name: '',
    description: '',
    is_administrative: true,
    permissions: [] as string[],
    allowed_modules: [] as string[],
    segment_ids: [] as number[],
});

const selectAllInGroup = ref<Record<string, boolean>>({});

// Computed para obtener los permisos según el tipo de rol
const currentPermissions = computed(() => {
    return form.is_administrative 
        ? props.availablePermissions.administrative 
        : props.availablePermissions.frontend;
});

// Computed para obtener los módulos según el tipo de rol
const currentModules = computed(() => {
    return form.is_administrative 
        ? props.modules.administrative 
        : props.modules.frontend;
});

// Computed para verificar si todos los permisos de un grupo están seleccionados
const isGroupFullySelected = (groupKey: string): boolean => {
    const group = currentPermissions.value[groupKey];
    if (!group) return false;
    
    const groupPermissions = Object.keys(group.permissions);
    return groupPermissions.every(perm => form.permissions.includes(perm));
};

// Computed para verificar si algunos permisos de un grupo están seleccionados
const isGroupPartiallySelected = (groupKey: string): boolean => {
    const group = currentPermissions.value[groupKey];
    if (!group) return false;
    
    const groupPermissions = Object.keys(group.permissions);
    const selectedCount = groupPermissions.filter(perm => form.permissions.includes(perm)).length;
    return selectedCount > 0 && selectedCount < groupPermissions.length;
};

const togglePermission = (permission: string) => {
    const index = form.permissions.indexOf(permission);
    if (index > -1) {
        form.permissions.splice(index, 1);
    } else {
        form.permissions.push(permission);
    }
};

const toggleGroupPermissions = (groupKey: string) => {
    const group = currentPermissions.value[groupKey];
    if (!group) return;
    
    const groupPermissions = Object.keys(group.permissions);
    const allSelected = isGroupFullySelected(groupKey);
    
    if (allSelected) {
        // Deseleccionar todos
        form.permissions = form.permissions.filter(perm => !groupPermissions.includes(perm));
    } else {
        // Seleccionar todos
        groupPermissions.forEach(perm => {
            if (!form.permissions.includes(perm)) {
                form.permissions.push(perm);
            }
        });
    }
};

const toggleModule = (moduleKey: string) => {
    const index = form.allowed_modules.indexOf(moduleKey);
    if (index > -1) {
        form.allowed_modules.splice(index, 1);
    } else {
        form.allowed_modules.push(moduleKey);
    }
};

const toggleSegment = (segmentId: number) => {
    const index = form.segment_ids.indexOf(segmentId);
    if (index > -1) {
        form.segment_ids.splice(index, 1);
    } else {
        form.segment_ids.push(segmentId);
    }
};

const handleSubmit = () => {
    form.post('/admin/roles', {
        preserveScroll: true,
        onSuccess: () => {
            // Redirección manejada por el backend
        },
    });
};

const selectAllPermissions = () => {
    const allPermissions: string[] = [];
    Object.values(currentPermissions.value).forEach(group => {
        Object.keys(group.permissions).forEach(perm => {
            allPermissions.push(perm);
        });
    });
    form.permissions = allPermissions;
};

const clearAllPermissions = () => {
    form.permissions = [];
};

// Watch para limpiar permisos y módulos cuando cambia el tipo de rol
watch(() => form.is_administrative, (newValue, oldValue) => {
    // Solo limpiar si realmente cambió el valor y no es la carga inicial
    if (oldValue !== undefined && newValue !== oldValue) {
        // Limpiar permisos y módulos al cambiar el tipo de rol
        form.permissions = [];
        form.allowed_modules = [];
    }
});

const getPermissionCount = computed(() => {
    return form.permissions.length;
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Crear Rol" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Crear Nuevo Rol</h2>
                    <p class="text-muted-foreground">
                        Define un nuevo rol con permisos específicos
                    </p>
                </div>
                <Link :href="route('admin.roles.index')">
                    <Button variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                </Link>
            </div>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <Tabs defaultValue="general" class="space-y-4">
                    <TabsList class="grid w-full grid-cols-4">
                        <TabsTrigger value="general">
                            <Shield class="mr-2 h-4 w-4" />
                            General
                        </TabsTrigger>
                        <TabsTrigger value="permissions">
                            <Lock class="mr-2 h-4 w-4" />
                            Permisos
                        </TabsTrigger>
                        <TabsTrigger value="modules">
                            <Users class="mr-2 h-4 w-4" />
                            Módulos
                        </TabsTrigger>
                        <TabsTrigger value="segments">
                            <Target class="mr-2 h-4 w-4" />
                            Segmentos
                        </TabsTrigger>
                    </TabsList>

                    <!-- Tab General -->
                    <TabsContent value="general">
                        <Card>
                            <CardHeader>
                                <CardTitle>Información General</CardTitle>
                                <CardDescription>
                                    Define el nombre y descripción del rol
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="space-y-2">
                                    <Label htmlFor="name">Nombre del Rol (interno)</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        placeholder="ejemplo_rol"
                                        pattern="[a-z_]+"
                                        title="Solo letras minúsculas y guiones bajos"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Use solo letras minúsculas y guiones bajos (ej: admin_regional)
                                    </p>
                                    <InputError :message="form.errors.name" />
                                </div>

                                <div class="space-y-2">
                                    <Label htmlFor="display_name">Nombre a Mostrar</Label>
                                    <Input
                                        id="display_name"
                                        v-model="form.display_name"
                                        placeholder="Administrador Regional"
                                    />
                                    <InputError :message="form.errors.display_name" />
                                </div>

                                <div class="space-y-2">
                                    <Label htmlFor="description">Descripción</Label>
                                    <Textarea
                                        id="description"
                                        v-model="form.description"
                                        placeholder="Describe las responsabilidades de este rol..."
                                        rows="4"
                                    />
                                    <InputError :message="form.errors.description" />
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <div class="space-y-0.5">
                                            <Label htmlFor="is_administrative">Rol Administrativo</Label>
                                            <p class="text-xs text-muted-foreground">
                                                Los roles administrativos tienen acceso al panel de administración.
                                                Los roles frontend son para usuarios regulares del sistema.
                                            </p>
                                        </div>
                                        <Switch
                                            id="is_administrative"
                                            v-model="form.is_administrative"
                                        />
                                    </div>
                                    <InputError :message="form.errors.is_administrative" />
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- Tab Permisos -->
                    <TabsContent value="permissions">
                        <Card>
                            <CardHeader>
                                <CardTitle>Matriz de Permisos</CardTitle>
                                <CardDescription>
                                    {{ form.is_administrative ? 'Selecciona los permisos administrativos para este rol' : 'Selecciona los permisos de usuario para este rol' }}
                                </CardDescription>
                                <div class="flex gap-2 mt-4">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        @click="selectAllPermissions"
                                    >
                                        Seleccionar Todos
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        @click="clearAllPermissions"
                                    >
                                        Limpiar Todos
                                    </Button>
                                    <Badge variant="secondary" class="ml-auto">
                                        {{ getPermissionCount }} permisos seleccionados
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <ScrollArea class="h-[500px] pr-4">
                                    <div class="space-y-6">
                                        <div
                                            v-for="(group, groupKey) in currentPermissions"
                                            :key="groupKey"
                                            class="space-y-3"
                                        >
                                            <div class="flex items-center space-x-2 pb-2 border-b">
                                                <Checkbox
                                                    :checked="isGroupFullySelected(groupKey)"
                                                    :indeterminate="isGroupPartiallySelected(groupKey)"
                                                    @update:checked="toggleGroupPermissions(groupKey)"
                                                />
                                                <Label class="text-sm font-semibold">
                                                    {{ group.label }}
                                                </Label>
                                            </div>
                                            <div class="grid grid-cols-2 gap-3 pl-6">
                                                <div
                                                    v-for="(permLabel, permKey) in group.permissions"
                                                    :key="permKey"
                                                    class="flex items-center space-x-2"
                                                >
                                                    <Checkbox
                                                        :checked="form.permissions.includes(permKey)"
                                                        @update:checked="togglePermission(permKey)"
                                                    />
                                                    <Label class="text-sm font-normal cursor-pointer">
                                                        {{ permLabel }}
                                                    </Label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </ScrollArea>
                                <InputError :message="form.errors.permissions" />
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- Tab Módulos -->
                    <TabsContent value="modules">
                        <Card>
                            <CardHeader>
                                <CardTitle>Módulos Permitidos</CardTitle>
                                <CardDescription>
                                    {{ form.is_administrative ? 'Selecciona los módulos administrativos a los que tendrá acceso este rol' : 'Selecciona los módulos del portal público a los que tendrá acceso este rol' }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="grid grid-cols-2 gap-4">
                                    <div
                                        v-for="(moduleName, moduleKey) in currentModules"
                                        :key="moduleKey"
                                        class="flex items-center space-x-2"
                                    >
                                        <Checkbox
                                            :checked="form.allowed_modules.includes(moduleKey)"
                                            @update:checked="toggleModule(moduleKey)"
                                        />
                                        <Label class="text-sm font-normal cursor-pointer">
                                            {{ moduleName }}
                                        </Label>
                                    </div>
                                </div>
                                <InputError :message="form.errors.allowed_modules" />
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- Tab Segmentos -->
                    <TabsContent value="segments">
                        <Card>
                            <CardHeader>
                                <CardTitle>Segmentos de Datos</CardTitle>
                                <CardDescription>
                                    Asocia segmentos para limitar el alcance de datos visibles
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div v-if="segments.length > 0" class="space-y-3">
                                    <div
                                        v-for="segment in segments"
                                        :key="segment.id"
                                        class="flex items-start space-x-3 p-3 border rounded-lg hover:bg-muted/50"
                                    >
                                        <Checkbox
                                            :checked="form.segment_ids.includes(segment.id)"
                                            @update:checked="toggleSegment(segment.id)"
                                            class="mt-1"
                                        />
                                        <div class="flex-1">
                                            <Label class="text-sm font-medium cursor-pointer">
                                                {{ segment.name }}
                                            </Label>
                                            <p class="text-xs text-muted-foreground mt-1">
                                                {{ segment.description || 'Sin descripción' }}
                                            </p>
                                            <Badge variant="outline" class="mt-2" v-if="segment.user_count">
                                                {{ segment.user_count }} usuarios
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-8 text-muted-foreground">
                                    No hay segmentos disponibles
                                </div>
                                <InputError :message="form.errors.segment_ids" />
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>

                <!-- Botones de acción -->
                <div class="flex justify-end gap-4">
                    <Link :href="route('admin.roles.index')">
                        <Button type="button" variant="outline">
                            Cancelar
                        </Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Creando...' : 'Crear Rol' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>