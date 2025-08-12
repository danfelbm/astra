<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { ArrowLeft, Edit, Shield, Users, Target, Lock, User, Calendar, Hash } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    created_at: string;
}

interface Segment {
    id: number;
    name: string;
    description?: string;
    user_count?: number;
    is_dynamic: boolean;
}

interface Role {
    id: number;
    name: string;
    display_name: string;
    description?: string;
    tenant_id?: number;
    permissions?: string[];
    allowed_modules?: string[];
    users?: User[];
    segments?: Segment[];
    created_at: string;
    updated_at: string;
}

interface Props {
    role: Role;
    userCount: number;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Roles y Permisos', href: '/admin/roles' },
    { title: props.role.display_name, href: '#' },
];

const isSystemRole = () => {
    return ['super_admin', 'admin', 'manager', 'user', 'end_customer'].includes(props.role.name);
};

const getInitials = (name: string): string => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const groupPermissionsByModule = () => {
    if (!props.role.permissions) return {};
    
    const grouped: Record<string, string[]> = {};
    props.role.permissions.forEach(perm => {
        const [module, action] = perm.split('.');
        if (!grouped[module]) {
            grouped[module] = [];
        }
        grouped[module].push(action);
    });
    return grouped;
};

const getModuleLabel = (module: string): string => {
    const labels: Record<string, string> = {
        users: 'Usuarios',
        votaciones: 'Votaciones',
        convocatorias: 'Convocatorias',
        postulaciones: 'Postulaciones',
        candidaturas: 'Candidaturas',
        reports: 'Reportes',
        settings: 'Configuración',
        dashboard: 'Dashboard',
        profile: 'Perfil',
    };
    return labels[module] || module;
};

const getActionBadgeVariant = (action: string): string => {
    const variants: Record<string, string> = {
        view: 'secondary',
        create: 'default',
        edit: 'outline',
        delete: 'destructive',
        export: 'ghost',
    };
    return variants[action] || 'outline';
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Rol: ${role.display_name}`" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-lg">
                        <Shield class="h-8 w-8 text-primary" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight">{{ role.display_name }}</h2>
                        <div class="flex items-center gap-2 mt-1">
                            <Badge variant="outline">{{ role.name }}</Badge>
                            <Badge v-if="isSystemRole()" variant="default">Rol del Sistema</Badge>
                            <Badge v-else variant="secondary">Rol Personalizado</Badge>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link :href="`/admin/roles/${role.id}/edit`">
                        <Button>
                            <Edit class="mr-2 h-4 w-4" />
                            Editar
                        </Button>
                    </Link>
                    <Link :href="route('admin.roles.index')">
                        <Button variant="outline">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Información General -->
            <div class="grid gap-6 md:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Usuarios Asignados</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ userCount }}</div>
                        <p class="text-xs text-muted-foreground">usuarios con este rol</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Permisos</CardTitle>
                        <Lock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ role.permissions?.includes('*') ? 'Todos' : (role.permissions?.length || 0) }}
                        </div>
                        <p class="text-xs text-muted-foreground">permisos asignados</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Segmentos</CardTitle>
                        <Target class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ role.segments?.length || 0 }}</div>
                        <p class="text-xs text-muted-foreground">segmentos asociados</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Tabs con detalles -->
            <Tabs defaultValue="details" class="space-y-4">
                <TabsList>
                    <TabsTrigger value="details">Detalles</TabsTrigger>
                    <TabsTrigger value="permissions">Permisos</TabsTrigger>
                    <TabsTrigger value="users">Usuarios</TabsTrigger>
                    <TabsTrigger value="segments">Segmentos</TabsTrigger>
                </TabsList>

                <!-- Tab Detalles -->
                <TabsContent value="details">
                    <Card>
                        <CardHeader>
                            <CardTitle>Información del Rol</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">ID del Rol</p>
                                    <p class="text-sm font-mono">{{ role.id }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Nombre Interno</p>
                                    <p class="text-sm font-mono">{{ role.name }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Creado</p>
                                    <p class="text-sm">{{ formatDate(role.created_at) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Última Actualización</p>
                                    <p class="text-sm">{{ formatDate(role.updated_at) }}</p>
                                </div>
                            </div>
                            <div v-if="role.description">
                                <p class="text-sm font-medium text-muted-foreground mb-2">Descripción</p>
                                <p class="text-sm">{{ role.description }}</p>
                            </div>
                            <div v-if="role.allowed_modules?.length">
                                <p class="text-sm font-medium text-muted-foreground mb-2">Módulos Permitidos</p>
                                <div class="flex flex-wrap gap-2">
                                    <Badge v-for="module in role.allowed_modules" :key="module" variant="outline">
                                        {{ module }}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Tab Permisos -->
                <TabsContent value="permissions">
                    <Card>
                        <CardHeader>
                            <CardTitle>Permisos Asignados</CardTitle>
                            <CardDescription>
                                Lista detallada de permisos organizados por módulo
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="role.permissions?.includes('*')" class="text-center py-8">
                                <Shield class="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                                <p class="text-lg font-semibold">Acceso Total</p>
                                <p class="text-sm text-muted-foreground">Este rol tiene todos los permisos del sistema</p>
                            </div>
                            <div v-else-if="role.permissions?.length" class="space-y-4">
                                <div
                                    v-for="(actions, module) in groupPermissionsByModule()"
                                    :key="module"
                                    class="border rounded-lg p-4"
                                >
                                    <h4 class="font-semibold mb-2">{{ getModuleLabel(module) }}</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <Badge
                                            v-for="action in actions"
                                            :key="`${module}.${action}`"
                                            :variant="getActionBadgeVariant(action)"
                                        >
                                            {{ action }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-muted-foreground">
                                No hay permisos asignados a este rol
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Tab Usuarios -->
                <TabsContent value="users">
                    <Card>
                        <CardHeader>
                            <CardTitle>Usuarios con este Rol</CardTitle>
                            <CardDescription>
                                Lista de usuarios que tienen asignado este rol
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table v-if="role.users?.length">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Usuario</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Fecha de Asignación</TableHead>
                                        <TableHead class="text-right">Acciones</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="user in role.users" :key="user.id">
                                        <TableCell>
                                            <div class="flex items-center gap-3">
                                                <Avatar class="h-8 w-8">
                                                    <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                                                    <AvatarFallback>{{ getInitials(user.name) }}</AvatarFallback>
                                                </Avatar>
                                                <span class="font-medium">{{ user.name }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>{{ user.email }}</TableCell>
                                        <TableCell>{{ formatDate(user.created_at) }}</TableCell>
                                        <TableCell class="text-right">
                                            <Link :href="`/admin/users/${user.id}`">
                                                <Button size="sm" variant="ghost">
                                                    Ver Usuario
                                                </Button>
                                            </Link>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <div v-else class="text-center py-8 text-muted-foreground">
                                No hay usuarios asignados a este rol
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Tab Segmentos -->
                <TabsContent value="segments">
                    <Card>
                        <CardHeader>
                            <CardTitle>Segmentos Asociados</CardTitle>
                            <CardDescription>
                                Segmentos que delimitan el alcance de datos para este rol
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="role.segments?.length" class="space-y-3">
                                <div
                                    v-for="segment in role.segments"
                                    :key="segment.id"
                                    class="flex items-start justify-between p-4 border rounded-lg"
                                >
                                    <div>
                                        <h4 class="font-semibold">{{ segment.name }}</h4>
                                        <p class="text-sm text-muted-foreground mt-1">
                                            {{ segment.description || 'Sin descripción' }}
                                        </p>
                                        <div class="flex gap-2 mt-2">
                                            <Badge variant="outline">
                                                {{ segment.user_count || 0 }} usuarios
                                            </Badge>
                                            <Badge :variant="segment.is_dynamic ? 'default' : 'secondary'">
                                                {{ segment.is_dynamic ? 'Dinámico' : 'Estático' }}
                                            </Badge>
                                        </div>
                                    </div>
                                    <Link :href="`/admin/segments/${segment.id}`">
                                        <Button size="sm" variant="ghost">
                                            Ver Segmento
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-muted-foreground">
                                No hay segmentos asociados a este rol
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>