<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdvancedFilters from '@/components/filters/AdvancedFilters.vue';
import { PlusCircle, Edit, Trash2, RefreshCw, Building2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import type { BreadcrumbItem } from '@/types';

interface Tenant {
    id: number;
    name: string;
    subdomain: string;
    active: boolean;
    subscription_plan: string;
    settings: any;
    limits: any;
    created_at: string;
    updated_at: string;
}

interface Props {
    tenants: {
        data: Tenant[];
        links: any;
        meta: any;
    };
    filters: {
        search?: string;
        active?: string;
    };
    filterFieldsConfig: any[];
    currentTenant: Tenant | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Tenants', href: '/admin/tenants' },
];

// Estado local para filtros
const localFilters = ref({
    search: props.filters.search || '',
    active: props.filters.active || 'all',
});

// Función para aplicar filtros
const applyFilters = () => {
    const params: any = {
        search: localFilters.value.search,
    };
    
    // Solo incluir active si no es 'all'
    if (localFilters.value.active !== 'all') {
        params.active = localFilters.value.active;
    }
    
    router.get('/admin/tenants', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Watch para búsqueda con debounce
let searchTimeout: any;
watch(() => localFilters.value.search, (newValue) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

// Watch para filtro de estado
watch(() => localFilters.value.active, () => {
    applyFilters();
});

// Función para eliminar tenant
const deleteTenant = (tenant: Tenant) => {
    if (confirm(`¿Estás seguro de eliminar el tenant "${tenant.name}"?`)) {
        router.delete(`/admin/tenants/${tenant.id}`);
    }
};

// Función para cambiar tenant actual
const switchTenant = (tenantId: number) => {
    router.post('/admin/tenants/switch', { tenant_id: tenantId });
};

// Función para obtener color del plan
const getPlanColor = (plan: string) => {
    switch (plan) {
        case 'basic': return 'default';
        case 'professional': return 'secondary';
        case 'enterprise': return 'destructive';
        default: return 'default';
    }
};

// Función para obtener label del plan
const getPlanLabel = (plan: string) => {
    switch (plan) {
        case 'basic': return 'Básico';
        case 'professional': return 'Profesional';
        case 'enterprise': return 'Empresarial';
        default: return plan;
    }
};
</script>

<template>
    <Head title="Gestión de Tenants" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header con título y botón de crear -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Gestión de Tenants</h2>
                    <p class="text-muted-foreground">
                        Administra las organizaciones del sistema multi-tenant
                    </p>
                </div>
                <Link :href="route('admin.tenants.create')">
                    <Button>
                        <PlusCircle class="mr-2 h-4 w-4" />
                        Nuevo Tenant
                    </Button>
                </Link>
            </div>

            <!-- Tenant actual -->
            <Card v-if="currentTenant">
                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Building2 class="h-5 w-5 text-muted-foreground" />
                            <CardTitle class="text-sm font-medium">Tenant Actual</CardTitle>
                        </div>
                        <Badge :variant="currentTenant.active ? 'default' : 'secondary'">
                            {{ currentTenant.active ? 'Activo' : 'Inactivo' }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-lg font-semibold">{{ currentTenant.name }}</p>
                            <p class="text-sm text-muted-foreground">{{ currentTenant.subdomain }}.votaciones.test</p>
                        </div>
                        <Badge :variant="getPlanColor(currentTenant.subscription_plan)">
                            {{ getPlanLabel(currentTenant.subscription_plan) }}
                        </Badge>
                    </div>
                </CardContent>
            </Card>

            <!-- Filtros -->
            <Card>
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                    <CardDescription>Busca y filtra los tenants del sistema</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-3">
                        <Input
                            v-model="localFilters.search"
                            placeholder="Buscar por nombre o subdominio..."
                            class="max-w-sm"
                        />
                        <Select v-model="localFilters.active">
                            <SelectTrigger>
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="true">Activos</SelectItem>
                                <SelectItem value="false">Inactivos</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Filtros avanzados -->
                    <div class="mt-4">
                        <AdvancedFilters
                            :filter-fields-config="filterFieldsConfig"
                            base-route="/admin/tenants"
                        />
                    </div>
                </CardContent>
            </Card>

            <!-- Tabla de tenants -->
            <Card>
                <CardHeader>
                    <CardTitle>Lista de Tenants</CardTitle>
                    <CardDescription>
                        {{ tenants.meta.total }} tenant(s) encontrado(s)
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="relative overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>ID</TableHead>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Subdominio</TableHead>
                                    <TableHead>Plan</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Usuarios</TableHead>
                                    <TableHead>Creado</TableHead>
                                    <TableHead class="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="tenant in tenants.data" :key="tenant.id">
                                    <TableCell class="font-medium">{{ tenant.id }}</TableCell>
                                    <TableCell>
                                        <div>
                                            <p class="font-medium">{{ tenant.name }}</p>
                                            <p v-if="tenant.id === currentTenant?.id" class="text-xs text-muted-foreground">
                                                (Actual)
                                            </p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <code class="text-sm">{{ tenant.subdomain }}</code>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getPlanColor(tenant.subscription_plan)">
                                            {{ getPlanLabel(tenant.subscription_plan) }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="tenant.active ? 'default' : 'secondary'">
                                            {{ tenant.active ? 'Activo' : 'Inactivo' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <span class="text-sm">
                                            {{ tenant.limits?.max_users || '∞' }}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <span class="text-sm text-muted-foreground">
                                            {{ new Date(tenant.created_at).toLocaleDateString() }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button
                                                v-if="tenant.id !== currentTenant?.id"
                                                @click="switchTenant(tenant.id)"
                                                size="sm"
                                                variant="outline"
                                                title="Cambiar a este tenant"
                                            >
                                                <RefreshCw class="h-4 w-4" />
                                            </Button>
                                            <Link :href="`/admin/tenants/${tenant.id}/edit`">
                                                <Button size="sm" variant="outline">
                                                    <Edit class="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <Button
                                                @click="deleteTenant(tenant)"
                                                size="sm"
                                                variant="outline"
                                                :disabled="tenant.id === 1"
                                                title="Eliminar tenant"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="tenants.data.length === 0">
                                    <TableCell colspan="8" class="text-center text-muted-foreground">
                                        No se encontraron tenants
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="tenants.links.length > 3" class="mt-4 flex justify-center">
                        <nav class="flex gap-1">
                            <template v-for="link in tenants.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'px-3 py-1 text-sm rounded-md',
                                        link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'
                                    ]"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    :class="[
                                        'px-3 py-1 text-sm rounded-md text-muted-foreground',
                                    ]"
                                    v-html="link.label"
                                />
                            </template>
                        </nav>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>