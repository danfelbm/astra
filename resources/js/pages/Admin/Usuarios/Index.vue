<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Plus, Edit, Trash, Power, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import AdvancedFilters from '@/components/filters/AdvancedFilters.vue';
import type { AdvancedFilterConfig } from '@/types/filters';
import { type BreadcrumbItemType } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'usuario';
    documento_identidad?: string;
    telefono?: string;
    direccion?: string;
    activo: boolean;
    created_at: string;
    territorio?: { id: number; nombre: string };
    departamento?: { id: number; nombre: string };
    municipio?: { id: number; nombre: string };
    localidad?: { id: number; nombre: string };
    cargo?: { id: number; nombre: string };
}

interface Props {
    users: {
        data: User[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters: {
        search?: string;
        role?: string;
        territorio_id?: number;
        departamento_id?: number;
        advanced_filters?: string;
    };
    filterFieldsConfig: any[];
}

const props = defineProps<Props>();
const { route } = window as any;

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Usuarios', href: '/admin/usuarios' },
];

const showDeleteDialog = ref(false);
const userToDelete = ref<User | null>(null);

// Configuración para el componente de filtros avanzados
const filterConfig: AdvancedFilterConfig = {
    fields: props.filterFieldsConfig || [],
    showQuickSearch: true,
    quickSearchPlaceholder: 'Buscar por nombre, email o documento...',
    quickSearchFields: ['name', 'email', 'documento_identidad', 'telefono'],
    maxNestingLevel: 2,
    allowSaveFilters: true,
    debounceTime: 500,
    autoApply: false,
};

const toggleUserStatus = (user: User) => {
    router.post(route('admin.usuarios.toggle-active', user.id), {}, {
        preserveScroll: true,
    });
};

const confirmDelete = (user: User) => {
    userToDelete.value = user;
    showDeleteDialog.value = true;
};

const deleteUser = () => {
    if (userToDelete.value) {
        router.delete(route('admin.usuarios.destroy', userToDelete.value.id), {
            preserveScroll: true,
        });
    }
    showDeleteDialog.value = false;
    userToDelete.value = null;
};

const getRoleBadgeVariant = (role: string) => {
    return role === 'admin' ? 'default' : 'secondary';
};

const getStatusBadgeVariant = (activo: boolean) => {
    return activo ? 'default' : 'destructive';
};

const formatLocation = (user: User) => {
    const parts = [];
    if (user.localidad) parts.push(user.localidad.nombre);
    if (user.municipio) parts.push(user.municipio.nombre);
    if (user.departamento) parts.push(user.departamento.nombre);
    if (user.territorio) parts.push(user.territorio.nombre);
    return parts.join(', ') || 'Sin ubicación';
};

const changePage = (page: number) => {
    router.get(route('admin.usuarios.index'), {
        ...props.filters,
        page,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Usuarios" />
    
    <AppLayout :breadcrumbs="breadcrumbs">

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Usuarios</h1>
                    <p class="text-muted-foreground">
                        Administra los usuarios del sistema
                    </p>
                </div>
                <Link :href="route('admin.usuarios.create')">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Usuario
                    </Button>
                </Link>
            </div>

            <!-- Advanced Filters -->
            <AdvancedFilters
                :config="filterConfig"
                :route="route('admin.usuarios.index')"
                :initial-filters="{
                    quickSearch: filters.search,
                    rootGroup: filters.advanced_filters ? JSON.parse(filters.advanced_filters) : undefined
                }"
            />

            <!-- Table -->
            <Card>
                <CardContent class="pt-6">
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Usuario</TableHead>
                                    <TableHead>Rol</TableHead>
                                    <TableHead>Ubicación</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Registro</TableHead>
                                    <TableHead class="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="user in users.data" :key="user.id">
                                    <TableCell>
                                        <div>
                                            <div class="font-medium">{{ user.name }}</div>
                                            <div class="text-sm text-muted-foreground">{{ user.email }}</div>
                                            <div v-if="user.documento_identidad" class="text-xs text-muted-foreground">
                                                Doc: {{ user.documento_identidad }}
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getRoleBadgeVariant(user.role)">
                                            {{ user.role === 'admin' ? 'Administrador' : 'Usuario' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="max-w-[200px] truncate text-sm" :title="formatLocation(user)">
                                            {{ formatLocation(user) }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusBadgeVariant(user.activo)">
                                            {{ user.activo ? 'Activo' : 'Inactivo' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm text-muted-foreground">
                                            {{ new Date(user.created_at).toLocaleDateString() }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex justify-end gap-1">
                                            <Link :href="route('admin.usuarios.edit', user.id)">
                                                <Button variant="ghost" size="icon" class="h-8 w-8">
                                                    <Edit class="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <Button 
                                                variant="ghost" 
                                                size="icon" 
                                                class="h-8 w-8"
                                                @click="toggleUserStatus(user)"
                                                :title="user.activo ? 'Desactivar' : 'Activar'"
                                            >
                                                <Power class="h-4 w-4" :class="user.activo ? 'text-green-600' : 'text-gray-400'" />
                                            </Button>
                                            <Button 
                                                variant="ghost" 
                                                size="icon" 
                                                class="h-8 w-8 hover:text-destructive"
                                                @click="confirmDelete(user)"
                                            >
                                                <Trash class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="users.data.length === 0">
                                    <TableCell colspan="6" class="text-center">
                                        <div class="py-12 text-muted-foreground">
                                            No se encontraron usuarios
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="users.last_page > 1" class="flex items-center justify-between mt-4">
                        <div class="text-sm text-muted-foreground">
                            Mostrando {{ users.from }} a {{ users.to }} de {{ users.total }} resultados
                        </div>
                        <div class="flex items-center space-x-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="users.current_page === 1"
                                @click="changePage(users.current_page - 1)"
                            >
                                <ChevronLeft class="h-4 w-4" />
                                Anterior
                            </Button>
                            <div class="text-sm">
                                Página {{ users.current_page }} de {{ users.last_page }}
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="users.current_page === users.last_page"
                                @click="changePage(users.current_page + 1)"
                            >
                                Siguiente
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Delete Confirmation Dialog -->
        <AlertDialog v-model:open="showDeleteDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>¿Estás seguro?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Esta acción no se puede deshacer. Se eliminará permanentemente el usuario
                        <strong v-if="userToDelete">{{ userToDelete.name }}</strong> del sistema.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="deleteUser" class="bg-destructive text-destructive-foreground">
                        Eliminar
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>