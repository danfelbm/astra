<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Edit, Plus, Search, Trash2, ChevronRight, ChevronDown, Folder, Briefcase, List, GitBranch } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import AdvancedFilters from '@/components/filters/AdvancedFilters.vue';
import type { AdvancedFilterConfig } from '@/types/filters';

interface Cargo {
    id: number;
    nombre: string;
    descripcion?: string;
    parent_id?: number;
    parent?: Cargo;
    children?: Cargo[];
    es_cargo: boolean;
    activo: boolean;
    created_at: string;
}

interface Props {
    cargos: {
        data: Cargo[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    arbolCargos: Cargo[];
    filters: {
        tipo?: string;
        activo?: string;
        search?: string;
        advanced_filters?: string;
    };
    filterFieldsConfig: any[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Cargos', href: '/admin/cargos' },
];

// Estado de expansión del árbol
const expandedNodes = ref<Set<number>>(new Set());

// Vista actual (lista o árbol)
const currentView = ref<'lista' | 'arbol'>('lista');

// Configuración para el componente de filtros avanzados
const filterConfig: AdvancedFilterConfig = {
    fields: props.filterFieldsConfig || [],
    showQuickSearch: true,
    quickSearchPlaceholder: 'Buscar por nombre o descripción...',
    quickSearchFields: ['nombre', 'descripcion'],
    maxNestingLevel: 2,
    allowSaveFilters: true,
    debounceTime: 500,
    autoApply: false,
};

// Helper para obtener route
const { route } = window as any;

// Cambiar entre vistas
const toggleView = (view: 'lista' | 'arbol') => {
    currentView.value = view;
};

// Eliminar cargo
const deleteCargo = (id: number) => {
    router.delete(`/admin/cargos/${id}`, {
        onSuccess: () => {
            // El mensaje de éxito se maneja en el backend
        },
    });
};

// Toggle expansión de nodo
const toggleNode = (cargoId: number) => {
    if (expandedNodes.value.has(cargoId)) {
        expandedNodes.value.delete(cargoId);
    } else {
        expandedNodes.value.add(cargoId);
    }
};

// Obtener clase para badge de tipo
const getTipoBadgeClass = (esCargo: boolean) => {
    return esCargo ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800';
};

// Obtener icono para tipo
const getTipoIcon = (esCargo: boolean) => {
    return esCargo ? Briefcase : Folder;
};

// Obtener badge de estado
const getEstadoBadge = (activo: boolean) => {
    return activo ? 
        { class: 'bg-green-100 text-green-800', text: 'Activo' } :
        { class: 'bg-red-100 text-red-800', text: 'Inactivo' };
};
</script>

<template>
    <Head title="Gestión de Cargos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Gestión de Cargos</h1>
                    <p class="text-muted-foreground">
                        Administra la estructura jerárquica de cargos electorales
                    </p>
                </div>
                <Link :href="route('admin.cargos.create')">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Cargo
                    </Button>
                </Link>
            </div>

            <!-- Advanced Filters con botones de vista -->
            <div class="space-y-4">
                <!-- Controles de vista -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center justify-between">
                            <span>Opciones de Visualización</span>
                            <div class="flex gap-2">
                                <Button 
                                    variant="outline" 
                                    size="sm"
                                    :class="{ 'bg-primary text-primary-foreground': currentView === 'lista' }"
                                    @click="toggleView('lista')"
                                >
                                    <List class="h-4 w-4 mr-2" />
                                    Vista Lista
                                </Button>
                                <Button 
                                    variant="outline" 
                                    size="sm"
                                    :class="{ 'bg-primary text-primary-foreground': currentView === 'arbol' }"
                                    @click="toggleView('arbol')"
                                >
                                    <GitBranch class="h-4 w-4 mr-2" />
                                    Vista Árbol
                                </Button>
                            </div>
                        </CardTitle>
                    </CardHeader>
                </Card>
                
                <!-- Filtros avanzados -->
                <AdvancedFilters
                    :config="filterConfig"
                    :route="route('admin.cargos.index')"
                    :initial-filters="{
                        quickSearch: filters.search,
                        rootGroup: filters.advanced_filters ? JSON.parse(filters.advanced_filters) : undefined
                    }"
                />
                        </div>

            <!-- Vista Lista -->
            <Card v-if="currentView === 'lista'">
                <CardContent class="pt-6">
                    <div v-if="cargos.total === 0" class="text-center py-8 text-muted-foreground">
                        <p>No se encontraron cargos</p>
                        <p class="text-sm">Crea el primer cargo para comenzar</p>
                    </div>
                    <div v-else>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Tipo</TableHead>
                                    <TableHead>Cargo Padre</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Creado</TableHead>
                                    <TableHead class="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="cargo in cargos.data" :key="cargo.id">
                                    <TableCell class="font-medium">
                                        <div>
                                            <p>{{ cargo.nombre }}</p>
                                            <p v-if="cargo.descripcion" class="text-sm text-muted-foreground">
                                                {{ cargo.descripcion }}
                                            </p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="getTipoBadgeClass(cargo.es_cargo)">
                                            <component :is="getTipoIcon(cargo.es_cargo)" class="mr-1 h-3 w-3" />
                                            {{ cargo.es_cargo ? 'Cargo' : 'Categoría' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <span v-if="cargo.parent" class="text-sm">
                                            {{ cargo.parent.nombre }}
                                        </span>
                                        <span v-else class="text-sm text-muted-foreground">
                                            Raíz
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="getEstadoBadge(cargo.activo).class">
                                            {{ getEstadoBadge(cargo.activo).text }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ new Date(cargo.created_at).toLocaleDateString('es-ES') }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex gap-2 justify-end">
                                            <Link :href="route('admin.cargos.edit', cargo.id)">
                                                <Button variant="ghost" size="sm">
                                                    <Edit class="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <AlertDialog>
                                                <AlertDialogTrigger asChild>
                                                    <Button variant="ghost" size="sm">
                                                        <Trash2 class="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>¿Eliminar cargo?</AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            Esta acción no se puede deshacer. Se eliminará permanentemente el cargo "{{ cargo.nombre }}".
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                                        <AlertDialogAction @click="deleteCargo(cargo.id)">
                                                            Eliminar
                                                        </AlertDialogAction>
                                                    </AlertDialogFooter>
                                                </AlertDialogContent>
                                            </AlertDialog>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <!-- Paginación -->
                        <div v-if="cargos.last_page > 1" class="flex items-center justify-center mt-6">
                            <div class="flex gap-2">
                                <Link
                                    v-for="link in cargos.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    :class="[
                                        'px-3 py-2 text-sm rounded-md',
                                        link.active
                                            ? 'bg-primary text-primary-foreground'
                                            : 'bg-muted hover:bg-muted/80',
                                        !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                    ]"
                                    v-html="link.label"
                                    :disabled="!link.url"
                                />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Vista Árbol -->
            <Card v-if="currentView === 'arbol'">
                <CardHeader>
                    <CardTitle>Estructura Jerárquica</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="arbolCargos.length === 0" class="text-center py-8 text-muted-foreground">
                        <p>No hay cargos raíz configurados</p>
                        <p class="text-sm">Crea el primer cargo sin padre para comenzar la jerarquía</p>
                    </div>
                    <div v-else class="space-y-2">
                        <!-- Componente recursivo para árbol será implementado aquí -->
                        <div v-for="cargo in arbolCargos" :key="cargo.id" class="border rounded-lg p-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Button 
                                        v-if="cargo.children && cargo.children.length > 0"
                                        variant="ghost" 
                                        size="sm"
                                        @click="toggleNode(cargo.id)"
                                    >
                                        <ChevronRight v-if="!expandedNodes.has(cargo.id)" class="h-4 w-4" />
                                        <ChevronDown v-else class="h-4 w-4" />
                                    </Button>
                                    <component :is="getTipoIcon(cargo.es_cargo)" class="h-4 w-4 text-muted-foreground" />
                                    <span class="font-medium">{{ cargo.nombre }}</span>
                                    <Badge :class="getTipoBadgeClass(cargo.es_cargo)" class="text-xs">
                                        {{ cargo.es_cargo ? 'Cargo' : 'Categoría' }}
                                    </Badge>
                                </div>
                                <div class="flex gap-2">
                                    <Link :href="route('admin.cargos.edit', cargo.id)">
                                        <Button variant="ghost" size="sm">
                                            <Edit class="h-3 w-3" />
                                        </Button>
                                    </Link>
                                    <AlertDialog>
                                        <AlertDialogTrigger asChild>
                                            <Button variant="ghost" size="sm">
                                                <Trash2 class="h-3 w-3 text-destructive" />
                                            </Button>
                                        </AlertDialogTrigger>
                                        <AlertDialogContent>
                                            <AlertDialogHeader>
                                                <AlertDialogTitle>¿Eliminar cargo?</AlertDialogTitle>
                                                <AlertDialogDescription>
                                                    Esta acción no se puede deshacer. Se eliminará permanentemente el cargo "{{ cargo.nombre }}".
                                                </AlertDialogDescription>
                                            </AlertDialogHeader>
                                            <AlertDialogFooter>
                                                <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                                <AlertDialogAction @click="deleteCargo(cargo.id)">
                                                    Eliminar
                                                </AlertDialogAction>
                                            </AlertDialogFooter>
                                        </AlertDialogContent>
                                    </AlertDialog>
                                </div>
                            </div>
                            <!-- Hijos expandidos -->
                            <div v-if="expandedNodes.has(cargo.id) && cargo.children" class="ml-6 mt-2 space-y-2">
                                <div 
                                    v-for="hijo in cargo.children" 
                                    :key="hijo.id" 
                                    class="border rounded-lg p-2 bg-muted/20"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <component :is="getTipoIcon(hijo.es_cargo)" class="h-3 w-3 text-muted-foreground" />
                                            <span class="text-sm">{{ hijo.nombre }}</span>
                                            <Badge :class="getTipoBadgeClass(hijo.es_cargo)" class="text-xs">
                                                {{ hijo.es_cargo ? 'Cargo' : 'Categoría' }}
                                            </Badge>
                                        </div>
                                        <div class="flex gap-1">
                                            <Link :href="route('admin.cargos.edit', hijo.id)">
                                                <Button variant="ghost" size="sm">
                                                    <Edit class="h-3 w-3" />
                                                </Button>
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>