<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Calendar, Clock, Eye, MapPin, Megaphone, Pencil, Plus, Trash2, Users } from 'lucide-vue-next';
import AdvancedFilters from '@/components/filters/AdvancedFilters.vue';
import type { AdvancedFilterConfig } from '@/types/filters';
import { ref, watch } from 'vue';

interface Cargo {
    id: number;
    nombre: string;
    ruta_jerarquica: string;
}

interface PeriodoElectoral {
    id: number;
    nombre: string;
}

interface Convocatoria {
    id: number;
    nombre: string;
    descripcion?: string;
    fecha_apertura: string;
    fecha_cierre: string;
    estado: string;
    activo: boolean;
    created_at: string;
    cargo: Cargo | null;
    periodo_electoral: PeriodoElectoral | null;
    estado_temporal: string;
    estado_temporal_label: string;
    estado_temporal_color: string;
    duracion: string;
    dias_restantes: number;
    rango_fechas: string;
    ubicacion_texto: string;
}

interface Props {
    convocatorias: {
        data: Convocatoria[];
        links: any[];
        current_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        estado_temporal?: string;
        estado?: string;
        activo?: string;
        cargo_id?: string;
        periodo_electoral_id?: string;
        search?: string;
    
        advanced_filters?: string;};
    filterFieldsConfig: any[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Convocatorias', href: '#' },
];

// Filtros reactivos
const filters = ref({
    estado_temporal: props.filters.estado_temporal || 'null',
    estado: props.filters.estado || 'null',
    activo: props.filters.activo || 'null',
    cargo_id: props.filters.cargo_id || 'null',
    periodo_electoral_id: props.filters.periodo_electoral_id || 'null',
    search: props.filters.search || '',
});

// Aplicar filtros cuando cambien
watch(filters, (newFilters) => {
    // Convertir "null" strings a strings vacíos para el backend
    const cleanFilters = Object.entries(newFilters).reduce((acc, [key, value]) => {
        acc[key] = value === 'null' ? '' : value;
        return acc;
    }, {} as Record<string, string>);
    
    router.get('/admin/convocatorias', cleanFilters, {
        preserveState: true,
        replace: true,
    });
}, { deep: true });

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

// Código de estadísticas removido - se moverá a un dashboard unificado

// Función para eliminar convocatoria
const eliminarConvocatoria = (convocatoria: Convocatoria) => {
    if (confirm(`¿Estás seguro de que quieres eliminar la convocatoria "${convocatoria.nombre}"?`)) {
        router.delete(`/admin/convocatorias/${convocatoria.id}`, {
            onSuccess: () => {
                // Recargar la página para actualizar la lista
            }
        });
    }
};

// Función para formatear fecha
const formatearFecha = (fecha: string) => {
    return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Convocatorias" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Convocatorias</h1>
                    <p class="text-muted-foreground">
                        Gestiona las convocatorias electorales y formularios de postulación
                    </p>
                </div>
                <Link href="/admin/convocatorias/create">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nueva Convocatoria
                    </Button>
                </Link>
            </div>

            <!-- Advanced Filters -->
            <AdvancedFilters
                :config="filterConfig"
                :route="route('admin.convocatorias.index')"
                :initial-filters="{
                    quickSearch: filters.search,
                    rootGroup: filters.advanced_filters ? JSON.parse(filters.advanced_filters) : undefined
                }"
            />

            <!-- Lista de Convocatorias -->
            <Card>
                <CardContent class="pt-6">
                    <div v-if="convocatorias.data.length === 0" class="text-center py-8">
                        <Megaphone class="mx-auto h-12 w-12 text-muted-foreground" />
                        <h3 class="mt-4 text-lg font-medium">No hay convocatorias</h3>
                        <p class="text-muted-foreground">Comienza creando tu primera convocatoria.</p>
                        <Link href="/admin/convocatorias/create" class="mt-4 inline-block">
                            <Button>
                                <Plus class="mr-2 h-4 w-4" />
                                Nueva Convocatoria
                            </Button>
                        </Link>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="convocatoria in convocatorias.data"
                            :key="convocatoria.id"
                            class="border rounded-lg p-4 hover:bg-muted/50 transition-colors"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="text-lg font-semibold">{{ convocatoria.nombre }}</h3>
                                        <Badge :class="convocatoria.estado_temporal_color">
                                            {{ convocatoria.estado_temporal_label }}
                                        </Badge>
                                        <Badge v-if="!convocatoria.activo" variant="secondary">
                                            Inactivo
                                        </Badge>
                                    </div>
                                    
                                    <p v-if="convocatoria.descripcion" class="text-muted-foreground mb-3">
                                        {{ convocatoria.descripcion }}
                                    </p>

                                    <div class="grid gap-2 md:grid-cols-2 lg:grid-cols-4 text-sm">
                                        <div class="flex items-center gap-2">
                                            <Users class="h-4 w-4 text-muted-foreground" />
                                            <span class="font-medium">Cargo:</span>
                                            <span>{{ convocatoria.cargo?.ruta_jerarquica || 'N/A' }}</span>
                                        </div>
                                        
                                        <div class="flex items-center gap-2">
                                            <Calendar class="h-4 w-4 text-muted-foreground" />
                                            <span class="font-medium">Periodo:</span>
                                            <span>{{ convocatoria.periodo_electoral?.nombre || 'N/A' }}</span>
                                        </div>
                                        
                                        <div class="flex items-center gap-2">
                                            <Clock class="h-4 w-4 text-muted-foreground" />
                                            <span class="font-medium">Duración:</span>
                                            <span>{{ convocatoria.duracion }}</span>
                                        </div>
                                        
                                        <div class="flex items-center gap-2">
                                            <MapPin class="h-4 w-4 text-muted-foreground" />
                                            <span class="font-medium">Ubicación:</span>
                                            <span>{{ convocatoria.ubicacion_texto }}</span>
                                        </div>
                                    </div>

                                    <div class="mt-3 text-sm text-muted-foreground">
                                        <span class="font-medium">Fechas:</span> {{ convocatoria.rango_fechas }}
                                        <span v-if="convocatoria.dias_restantes > 0" class="ml-4">
                                            ({{ convocatoria.dias_restantes }} días restantes)
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 ml-4">
                                    <Link :href="`/admin/convocatorias/${convocatoria.id}`">
                                        <Button variant="outline" size="sm">
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Link :href="`/admin/convocatorias/${convocatoria.id}/edit`">
                                        <Button variant="outline" size="sm">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="eliminarConvocatoria(convocatoria)"
                                        class="text-destructive hover:text-destructive"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paginación -->
                    <div v-if="convocatorias.links && convocatorias.links.length > 3" class="mt-6 flex justify-center">
                        <div class="flex items-center gap-2">
                            <template v-for="link in convocatorias.links" :key="link.label">
                                <Button
                                    v-if="link.url"
                                    :variant="link.active ? 'default' : 'outline'"
                                    size="sm"
                                    @click="router.get(link.url)"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="px-3 py-1 text-sm text-muted-foreground"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>