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
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ref, computed } from 'vue';
import { Target, Users, MoreHorizontal, Plus, Edit, Trash2, Eye, RefreshCw, Trash } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Segment {
    id: number;
    name: string;
    description?: string;
    model_type: string;
    is_dynamic: boolean;
    cache_duration: number;
    user_count?: number;
    metadata?: {
        contacts_count: number;
        last_calculated_at?: string;
    };
    roles?: any[];
    created_by?: {
        id: number;
        name: string;
    };
    created_at: string;
    updated_at: string;
}

interface Props {
    segments: {
        data: Segment[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        is_dynamic?: string;
    };
    filterFieldsConfig: any[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Segmentos', href: '/admin/segments' },
];

const searchQuery = ref(props.filters.search || '');
const isDynamicFilter = ref(props.filters.is_dynamic || 'all');
const showAdvancedFilters = ref(false);

const getTypeBadgeVariant = (isDynamic: boolean): string => {
    return isDynamic ? 'default' : 'secondary';
};

const getTypeLabel = (isDynamic: boolean): string => {
    return isDynamic ? 'Dinámico' : 'Estático';
};

const formatModelType = (modelType: string): string => {
    return modelType.split('\\').pop() || modelType;
};

const formatLastCalculated = (metadata?: any): string => {
    if (!metadata?.last_calculated_at) return 'Nunca';
    return new Date(metadata.last_calculated_at).toLocaleString();
};

const formatCacheDuration = (seconds: number): string => {
    if (seconds < 60) return `${seconds}s`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m`;
    return `${Math.floor(seconds / 3600)}h`;
};

const handleSearch = () => {
    const params: any = {
        search: searchQuery.value,
    };
    
    // Solo incluir is_dynamic si no es 'all'
    if (isDynamicFilter.value !== 'all') {
        params.is_dynamic = isDynamicFilter.value;
    }
    
    router.get('/admin/segments', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleDelete = (segment: Segment) => {
    if (confirm(`¿Estás seguro de eliminar el segmento "${segment.name}"?`)) {
        router.delete(`/admin/segments/${segment.id}`);
    }
};

const handleEvaluateSegment = async (segment: Segment) => {
    try {
        const response = await fetch(`/admin/segments/${segment.id}/evaluate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        const result = await response.json();
        
        if (result.success) {
            // Actualizar la página para mostrar los nuevos datos
            router.reload();
        }
    } catch (error) {
        console.error('Error evaluating segment:', error);
    }
};

const handleClearCache = (segment: Segment) => {
    router.post(`/admin/segments/${segment.id}/clear-cache`);
};

const canDelete = (segment: Segment): boolean => {
    return !segment.roles || segment.roles.length === 0;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Segmentos" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Segmentos de Usuarios</h2>
                    <p class="text-muted-foreground">
                        Gestiona segmentos dinámicos basados en filtros avanzados
                    </p>
                </div>
                <Link :href="route('admin.segments.create')">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Segmento
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
                        <div class="w-48">
                            <Select v-model="isDynamicFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="Tipo de segmento" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos</SelectItem>
                                    <SelectItem value="true">Dinámicos</SelectItem>
                                    <SelectItem value="false">Estáticos</SelectItem>
                                </SelectContent>
                            </Select>
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
                            baseUrl="/admin/segments"
                        />
                    </div>
                </CardContent>
            </Card>

            <!-- Lista de Segmentos -->
            <Card>
                <CardHeader>
                    <CardTitle>Segmentos Registrados</CardTitle>
                    <CardDescription>
                        Total de {{ props.segments.total }} segmentos configurados
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Segmento</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Modelo</TableHead>
                                <TableHead>Usuarios</TableHead>
                                <TableHead>Cache</TableHead>
                                <TableHead>Última Evaluación</TableHead>
                                <TableHead>Roles</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="segment in props.segments.data" :key="segment.id">
                                <TableCell class="font-medium">
                                    <div class="flex items-center gap-2">
                                        <Target class="h-4 w-4 text-muted-foreground" />
                                        <div>
                                            <p class="font-semibold">{{ segment.name }}</p>
                                            <p class="text-xs text-muted-foreground max-w-xs truncate">
                                                {{ segment.description || 'Sin descripción' }}
                                            </p>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="getTypeBadgeVariant(segment.is_dynamic)">
                                        {{ getTypeLabel(segment.is_dynamic) }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <code class="text-xs">{{ formatModelType(segment.model_type) }}</code>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-1">
                                        <Users class="h-4 w-4 text-muted-foreground" />
                                        <span class="text-sm font-medium">
                                            {{ segment.metadata?.contacts_count || segment.user_count || 0 }}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="ghost">
                                        {{ formatCacheDuration(segment.cache_duration) }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <span class="text-xs text-muted-foreground">
                                        {{ formatLastCalculated(segment.metadata) }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline">
                                        {{ segment.roles?.length || 0 }} rol{{ (segment.roles?.length || 0) !== 1 ? 'es' : '' }}
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
                                            <Link :href="`/admin/segments/${segment.id}`">
                                                <DropdownMenuItem>
                                                    <Eye class="mr-2 h-4 w-4" />
                                                    Ver Usuarios
                                                </DropdownMenuItem>
                                            </Link>
                                            <DropdownMenuItem @click="handleEvaluateSegment(segment)">
                                                <RefreshCw class="mr-2 h-4 w-4" />
                                                Recalcular
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="handleClearCache(segment)">
                                                <Trash class="mr-2 h-4 w-4" />
                                                Limpiar Cache
                                            </DropdownMenuItem>
                                            <Link :href="`/admin/segments/${segment.id}/edit`">
                                                <DropdownMenuItem>
                                                    <Edit class="mr-2 h-4 w-4" />
                                                    Editar
                                                </DropdownMenuItem>
                                            </Link>
                                            <DropdownMenuItem
                                                v-if="canDelete(segment)"
                                                @click="handleDelete(segment)"
                                                class="text-destructive"
                                            >
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                Eliminar
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="props.segments.data.length === 0">
                                <TableCell colspan="8" class="text-center py-8 text-muted-foreground">
                                    No se encontraron segmentos
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Paginación -->
                    <div v-if="props.segments.last_page > 1" class="flex items-center justify-center space-x-2 mt-6">
                        <nav class="flex gap-1">
                            <Link
                                v-for="link in props.segments.links"
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