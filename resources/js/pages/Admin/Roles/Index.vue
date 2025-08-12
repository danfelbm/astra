<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import AdvancedFilters from '@/components/filters/AdvancedFilters.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ref, computed } from 'vue';
import { Shield, Users, Settings, MoreHorizontal, Plus, Edit, Trash2, Eye, FileText } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Role {
    id: number;
    name: string;
    display_name: string;
    description?: string;
    tenant_id?: number;
    permissions?: string[];
    allowed_modules?: string[];
    users?: any[];
    segments?: any[];
    created_at: string;
    updated_at: string;
}

interface Props {
    roles: {
        data: Role[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    segments: any[];
    filters: {
        search?: string;
    };
    filterFieldsConfig: any[];
    availablePermissions: any;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Roles y Permisos', href: '/admin/roles' },
];

const searchQuery = ref(props.filters.search || '');
const showAdvancedFilters = ref(false);

const isSystemRole = (role: Role): boolean => {
    return ['super_admin', 'admin', 'manager', 'user', 'end_customer'].includes(role.name);
};

const getRoleBadgeVariant = (roleName: string): string => {
    const variants: Record<string, string> = {
        'super_admin': 'destructive',
        'admin': 'default',
        'manager': 'secondary',
        'user': 'outline',
        'end_customer': 'ghost',
    };
    return variants[roleName] || 'outline';
};

const formatPermissionCount = (permissions?: string[]): string => {
    if (!permissions || permissions.length === 0) return 'Sin permisos';
    if (permissions.includes('*')) return 'Todos los permisos';
    return `${permissions.length} permisos`;
};

const formatUserCount = (users?: any[]): string => {
    if (!users || users.length === 0) return 'Sin usuarios';
    return `${users.length} usuario${users.length !== 1 ? 's' : ''}`;
};

const formatSegmentCount = (segments?: any[]): string => {
    if (!segments || segments.length === 0) return 'Sin segmentos';
    return `${segments.length} segmento${segments.length !== 1 ? 's' : ''}`;
};

const handleSearch = () => {
    router.get('/admin/roles', {
        search: searchQuery.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleDelete = (role: Role) => {
    if (confirm(`¿Estás seguro de eliminar el rol "${role.display_name}"?`)) {
        router.delete(`/admin/roles/${role.id}`);
    }
};

const canEdit = (role: Role): boolean => {
    // Solo super admin puede editar roles del sistema
    if (isSystemRole(role)) {
        return false; // Por ahora simplificado, deberíamos verificar si el usuario es super admin
    }
    return true;
};

const canDelete = (role: Role): boolean => {
    // No se pueden eliminar roles del sistema
    return !isSystemRole(role) && (!role.users || role.users.length === 0);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Roles y Permisos" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Roles y Permisos</h2>
                    <p class="text-muted-foreground">
                        Gestiona los roles y permisos del sistema
                    </p>
                </div>
                <Link :href="route('admin.roles.create')">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Rol
                    </Button>
                </Link>
            </div>

            <!-- Filtros -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Filtros</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Búsqueda simple -->
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Input
                                v-model="searchQuery"
                                placeholder="Buscar por nombre o descripción..."
                                @keyup.enter="handleSearch"
                            />
                        </div>
                        <Button @click="handleSearch" variant="secondary">
                            Buscar
                        </Button>
                        <Button
                            @click="showAdvancedFilters = !showAdvancedFilters"
                            variant="outline"
                        >
                            {{ showAdvancedFilters ? 'Ocultar' : 'Mostrar' }} Filtros Avanzados
                        </Button>
                    </div>

                    <!-- Filtros avanzados -->
                    <div v-if="showAdvancedFilters">
                        <AdvancedFilters
                            :fieldsConfig="filterFieldsConfig"
                            baseUrl="/admin/roles"
                        />
                    </div>
                </CardContent>
            </Card>

            <!-- Lista de Roles -->
            <Card>
                <CardHeader>
                    <CardTitle>Roles del Sistema</CardTitle>
                    <CardDescription>
                        Total de {{ props.roles.total }} roles registrados
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Rol</TableHead>
                                <TableHead>Descripción</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Permisos</TableHead>
                                <TableHead>Usuarios</TableHead>
                                <TableHead>Segmentos</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="role in props.roles.data" :key="role.id">
                                <TableCell class="font-medium">
                                    <div class="flex items-center gap-2">
                                        <Shield class="h-4 w-4 text-muted-foreground" />
                                        <div>
                                            <p class="font-semibold">{{ role.display_name }}</p>
                                            <p class="text-xs text-muted-foreground">{{ role.name }}</p>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <p class="text-sm text-muted-foreground max-w-xs truncate">
                                        {{ role.description || 'Sin descripción' }}
                                    </p>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="isSystemRole(role) ? 'default' : 'secondary'">
                                        {{ isSystemRole(role) ? 'Sistema' : 'Personalizado' }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline">
                                        {{ formatPermissionCount(role.permissions) }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-1">
                                        <Users class="h-4 w-4 text-muted-foreground" />
                                        <span class="text-sm">{{ formatUserCount(role.users) }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="ghost">
                                        {{ formatSegmentCount(role.segments) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="icon">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <Link :href="`/admin/roles/${role.id}`">
                                                <DropdownMenuItem>
                                                    <Eye class="mr-2 h-4 w-4" />
                                                    Ver Detalles
                                                </DropdownMenuItem>
                                            </Link>
                                            <Link 
                                                v-if="canEdit(role)"
                                                :href="`/admin/roles/${role.id}/edit`"
                                            >
                                                <DropdownMenuItem>
                                                    <Edit class="mr-2 h-4 w-4" />
                                                    Editar
                                                </DropdownMenuItem>
                                            </Link>
                                            <DropdownMenuItem
                                                v-if="canDelete(role)"
                                                @click="handleDelete(role)"
                                                class="text-destructive"
                                            >
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                Eliminar
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="props.roles.data.length === 0">
                                <TableCell colspan="7" class="text-center py-8 text-muted-foreground">
                                    No se encontraron roles
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Paginación -->
                    <div v-if="props.roles.last_page > 1" class="flex items-center justify-center space-x-2 mt-6">
                        <nav class="flex gap-1">
                            <Link
                                v-for="link in props.roles.links"
                                :key="link.label"
                                :href="link.url"
                                :class="[
                                    'px-3 py-2 text-sm rounded-md',
                                    link.active
                                        ? 'bg-primary text-primary-foreground'
                                        : 'hover:bg-muted',
                                    !link.url && 'opacity-50 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                                :disabled="!link.url"
                            />
                        </nav>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>