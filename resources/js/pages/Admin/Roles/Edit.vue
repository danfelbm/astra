<script setup lang="ts">
import AdminLayout from "@/layouts/AdminLayout.vue";
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ref, computed, onMounted, watch } from 'vue';
import { ArrowLeft, Save, Shield, Users, Target, Lock, AlertCircle, Home } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem } from '@/types';

interface Role {
    id: number;
    name: string;
    display_name: string;
    description?: string;
    tenant_id?: number;
    is_administrative?: boolean;
    redirect_after_login?: string;
    permissions?: string[];
    segments?: { id: number; name: string; }[];
    created_at: string;
    updated_at: string;
}

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


interface Props {
    role: Role;
    segments: Segment[];
    availablePermissions: AvailablePermissions;
    selectedSegments: number[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Roles y Permisos', href: '/admin/roles' },
    { title: 'Editar Rol', href: '#' },
];

const form = useForm({
    name: props.role.name,
    display_name: props.role.display_name,
    description: props.role.description || '',
    is_administrative: Boolean(props.role.is_administrative),
    redirect_after_login: props.role.redirect_after_login || 'default',
    permissions: props.role.permissions || [],
    segment_ids: props.selectedSegments || [],
});

// Opciones de redirección disponibles
const redirectOptions = [
    { value: 'default', label: 'Por defecto (según tipo de rol)' },
    { value: 'admin.dashboard', label: 'Dashboard Administrativo', group: 'admin' },
    { value: 'admin.candidaturas.dashboard', label: 'Dashboard Candidaturas', group: 'admin' },
    { value: 'dashboard', label: 'Dashboard Usuario', group: 'user' },
    { value: 'votaciones.index', label: 'Votaciones', group: 'user' },
    { value: 'asambleas.index', label: 'Asambleas', group: 'user' },
    { value: 'formularios.index', label: 'Formularios', group: 'user' },
    { value: 'postulaciones.index', label: 'Postulaciones', group: 'user' },
    { value: 'admin.otp-dashboard', label: 'Dashboard OTP', group: 'admin' },
    { value: 'admin.configuracion.index', label: 'Configuración', group: 'admin' },
    { value: 'admin.users.index', label: 'Usuarios', group: 'admin' },
    { value: 'admin.roles.index', label: 'Roles y Permisos', group: 'admin' },
    { value: 'admin.candidaturas.index', label: 'Candidaturas (Lista)', group: 'admin' },
];

// Computed para filtrar opciones según el tipo de rol
const availableRedirectOptions = computed(() => {
    if (form.is_administrative) {
        return redirectOptions;
    }
    // Para roles no administrativos, no mostrar rutas admin
    return redirectOptions.filter(opt => opt.group !== 'admin' || opt.value === 'default');
});

const isSystemRole = computed(() => {
    return ['super_admin', 'admin', 'manager', 'user', 'end_customer'].includes(props.role.name);
});

// Computed para obtener los permisos según el tipo de rol
// CORREGIDO: Ahora siempre mostramos ambos grupos de permisos para evitar pérdida de datos
const currentPermissions = computed(() => {
    // Siempre retornar todos los permisos disponibles
    return {
        ...props.availablePermissions.administrative,
        ...props.availablePermissions.frontend
    };
});


// Computed para verificar si todos los permisos de un grupo están seleccionados
const isGroupFullySelected = (groupKey: string): boolean => {
    // Buscar el grupo en ambas secciones (administrative y frontend)
    const group = props.availablePermissions.administrative[groupKey] || 
                 props.availablePermissions.frontend[groupKey];
    if (!group) return false;
    
    const groupPermissions = Object.keys(group.permissions);
    return groupPermissions.every(perm => form.permissions.includes(perm));
};

// Computed para verificar si algunos permisos de un grupo están seleccionados
const isGroupPartiallySelected = (groupKey: string): boolean => {
    // Buscar el grupo en ambas secciones (administrative y frontend)
    const group = props.availablePermissions.administrative[groupKey] || 
                 props.availablePermissions.frontend[groupKey];
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
    // Buscar el grupo en ambas secciones (administrative y frontend)
    const group = props.availablePermissions.administrative[groupKey] || 
                 props.availablePermissions.frontend[groupKey];
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


const toggleSegment = (segmentId: number) => {
    const index = form.segment_ids.indexOf(segmentId);
    if (index > -1) {
        form.segment_ids.splice(index, 1);
    } else {
        form.segment_ids.push(segmentId);
    }
};

const handleSubmit = () => {
    form.put(`/admin/roles/${props.role.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            // Redirección manejada por el backend
        },
    });
};

const handleDelete = () => {
    if (confirm(`¿Estás seguro de eliminar el rol "${props.role.display_name}"? Esta acción no se puede deshacer.`)) {
        router.delete(`/admin/roles/${props.role.id}`);
    }
};

const selectAllPermissions = () => {
    const allPermissions: string[] = [];
    // Recopilar permisos administrativos
    Object.values(props.availablePermissions.administrative).forEach(group => {
        Object.keys(group.permissions).forEach(perm => {
            allPermissions.push(perm);
        });
    });
    // Recopilar permisos frontend
    Object.values(props.availablePermissions.frontend).forEach(group => {
        Object.keys(group.permissions).forEach(perm => {
            allPermissions.push(perm);
        });
    });
    form.permissions = allPermissions;
};

const clearAllPermissions = () => {
    form.permissions = [];
};

// ELIMINADO: Ya no limpiamos permisos al cambiar el tipo de rol
// Esto permite mantener permisos mixtos (administrativos y frontend) en un mismo rol

const getPermissionCount = computed(() => {
    return form.permissions.length;
});

const canDelete = computed(() => {
    return !isSystemRole.value;
});
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Editar Rol" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Editar Rol</h2>
                    <p class="text-muted-foreground">
                        Modifica los permisos y configuración del rol
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button
                        v-if="canDelete"
                        @click="handleDelete"
                        variant="destructive"
                        type="button"
                    >
                        Eliminar Rol
                    </Button>
                    <Link :href="route('admin.roles.index')">
                        <Button variant="outline">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Alerta para roles del sistema -->
            <Alert v-if="isSystemRole">
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>
                    Este es un rol del sistema. Algunas configuraciones pueden estar restringidas.
                </AlertDescription>
            </Alert>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <Tabs defaultValue="general" class="space-y-4">
                    <TabsList class="grid w-full grid-cols-3">
                        <TabsTrigger value="general">
                            <Shield class="mr-2 h-4 w-4" />
                            General
                        </TabsTrigger>
                        <TabsTrigger value="permissions">
                            <Lock class="mr-2 h-4 w-4" />
                            Permisos
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
                                        :disabled="isSystemRole"
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
                                            :disabled="isSystemRole"
                                        />
                                    </div>
                                    <InputError :message="form.errors.is_administrative" />
                                </div>

                                <div class="space-y-2">
                                    <Label htmlFor="redirect_after_login">
                                        <Home class="inline-block h-4 w-4 mr-1" />
                                        Página de Inicio Después del Login
                                    </Label>
                                    <Select v-model="form.redirect_after_login" :disabled="isSystemRole">
                                        <SelectTrigger id="redirect_after_login">
                                            <SelectValue placeholder="Seleccione la página de destino" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem 
                                                v-for="option in availableRedirectOptions" 
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p class="text-xs text-muted-foreground">
                                        Define a qué página se redirigirá al usuario después de autenticarse.
                                        Si no se especifica, usará el comportamiento por defecto.
                                    </p>
                                    <InputError :message="form.errors.redirect_after_login" />
                                </div>

                                <!-- Información adicional -->
                                <div class="pt-4 border-t space-y-2 text-sm text-muted-foreground">
                                    <p>ID del Rol: {{ role.id }}</p>
                                    <p>Creado: {{ new Date(role.created_at).toLocaleDateString() }}</p>
                                    <p>Última actualización: {{ new Date(role.updated_at).toLocaleDateString() }}</p>
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
                                    Selecciona los permisos que tendrá este rol. Los permisos están organizados en dos secciones: administrativos (acceso al panel admin) y frontend (funciones de usuario).
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
                                    <div class="space-y-8">
                                        <!-- Sección de Permisos Administrativos -->
                                        <div v-if="Object.keys(availablePermissions.administrative).length > 0">
                                            <h3 class="text-lg font-semibold mb-4 text-orange-600 dark:text-orange-400">
                                                Permisos Administrativos
                                            </h3>
                                            <div class="space-y-6 pl-4">
                                                <div
                                                    v-for="(group, groupKey) in availablePermissions.administrative"
                                                    :key="`admin-${groupKey}`"
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
                                        </div>

                                        <!-- Separador -->
                                        <div class="border-t-2 border-dashed"></div>

                                        <!-- Sección de Permisos Frontend -->
                                        <div v-if="Object.keys(availablePermissions.frontend).length > 0">
                                            <h3 class="text-lg font-semibold mb-4 text-blue-600 dark:text-blue-400">
                                                Permisos de Usuario (Frontend)
                                            </h3>
                                            <div class="space-y-6 pl-4">
                                                <div
                                                    v-for="(group, groupKey) in availablePermissions.frontend"
                                                    :key="`frontend-${groupKey}`"
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
                                        </div>
                                    </div>
                                </ScrollArea>
                                <InputError :message="form.errors.permissions" />
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
                        {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                    </Button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>