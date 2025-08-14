<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Clock, Eye, Settings, UserCheck, AlertCircle } from 'lucide-vue-next';
import AdvancedFilters from '@/components/filters/AdvancedFilters.vue';
import Pagination from '@/components/ui/pagination/Pagination.vue';
import type { AdvancedFilterConfig } from '@/types/filters';
import { ref, watch } from 'vue';

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface Candidatura {
    id: number;
    usuario: Usuario;
    estado: string;
    estado_label: string;
    estado_color: string;
    version: number;
    comentarios_admin?: string;
    aprobado_por?: Usuario;
    fecha_aprobacion?: string;
    created_at: string;
    updated_at: string;
    tiene_datos: boolean;
    campos_llenados: number;
    total_campos: number;
    porcentaje_completado: number;
    esta_pendiente: boolean;
}

interface Props {
    candidaturas: {
        data: Candidatura[];
        links: any[];
        current_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        estado?: string;
        search?: string;
    
        advanced_filters?: string;};
    filterFieldsConfig: any[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Candidaturas', href: '#' },
];

// Filtros reactivos
const filters = ref({
    estado: props.filters.estado || 'null',
    search: props.filters.search || '',
});

// Aplicar filtros cuando cambien
watch(filters, (newFilters) => {
    // Convertir "null" strings a strings vacíos para el backend
    const cleanFilters = Object.entries(newFilters).reduce((acc, [key, value]) => {
        acc[key] = value === 'null' ? '' : value;
        return acc;
    }, {} as Record<string, string>);
    
    router.get('/admin/candidaturas', cleanFilters, {
        preserveState: true,
        replace: true,
    });
}, { deep: true });

// Configuración para el componente de filtros avanzados
const filterConfig: AdvancedFilterConfig = {
    fields: props.filterFieldsConfig || [],
    showQuickSearch: true,
    quickSearchPlaceholder: 'Buscar por nombre o email...',
    quickSearchFields: ['user.name', 'user.email'],
    maxNestingLevel: 2,
    allowSaveFilters: true,
    debounceTime: 500,
    autoApply: false,
};

// Helper para obtener route
const { route } = window as any;

// Código de estadísticas removido - se moverá a un dashboard unificado

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
    <Head title="Candidaturas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Candidaturas</h1>
                    <p class="text-muted-foreground">
                        Revisa y gestiona los perfiles de candidatura de los usuarios
                    </p>
                </div>
                <Link href="/admin/candidaturas/configuracion">
                    <Button>
                        <Settings class="mr-2 h-4 w-4" />
                        Configuración
                    </Button>
                </Link>
            </div>

            <!-- Advanced Filters -->
            <AdvancedFilters
                :config="filterConfig"
                :route="route('admin.candidaturas.index')"
                :initial-filters="{
                    quickSearch: filters.search,
                    rootGroup: filters.advanced_filters ? JSON.parse(filters.advanced_filters) : undefined
                }"
            />

            <!-- Lista de Candidaturas -->
            <Card>
                <CardContent class="pt-6">
                    <div v-if="candidaturas.data.length === 0" class="text-center py-8">
                        <UserCheck class="mx-auto h-12 w-12 text-muted-foreground" />
                        <h3 class="mt-4 text-lg font-medium">No hay candidaturas</h3>
                        <p class="text-muted-foreground">No se encontraron candidaturas con los filtros aplicados.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="candidatura in candidaturas.data"
                            :key="candidatura.id"
                            class="border rounded-lg p-4 hover:bg-muted/50 transition-colors"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="text-lg font-semibold">{{ candidatura.usuario.name }}</h3>
                                        <Badge :class="candidatura.estado_color">
                                            {{ candidatura.estado_label }}
                                        </Badge>
                                        <Badge v-if="candidatura.esta_pendiente" variant="outline" class="bg-blue-50 text-blue-700">
                                            Pendiente Revisión
                                        </Badge>
                                    </div>
                                    
                                    <p class="text-muted-foreground mb-3">
                                        {{ candidatura.usuario.email }}
                                    </p>

                                    <div class="grid gap-2 md:grid-cols-2 lg:grid-cols-4 text-sm">
                                        <div class="flex items-center gap-2">
                                            <Clock class="h-4 w-4 text-muted-foreground" />
                                            <span class="font-medium">Versión:</span>
                                            <span>{{ candidatura.version }}</span>
                                        </div>
                                        
                                        <div class="flex items-center gap-2">
                                            <AlertCircle class="h-4 w-4 text-muted-foreground" />
                                            <span class="font-medium">Datos:</span>
                                            <span v-if="candidatura.total_campos > 0">
                                                {{ candidatura.campos_llenados }} / {{ candidatura.total_campos }}
                                                <span class="text-muted-foreground">({{ candidatura.porcentaje_completado }}%)</span>
                                            </span>
                                            <span v-else>Sin configuración</span>
                                        </div>
                                        
                                        <div v-if="candidatura.aprobado_por" class="flex items-center gap-2">
                                            <UserCheck class="h-4 w-4 text-muted-foreground" />
                                            <span class="font-medium">Aprobado por:</span>
                                            <span>{{ candidatura.aprobado_por.name }}</span>
                                        </div>
                                        
                                        <div class="flex items-center gap-2">
                                            <Clock class="h-4 w-4 text-muted-foreground" />
                                            <span class="font-medium">Actualizado:</span>
                                            <span>{{ formatearFecha(candidatura.updated_at) }}</span>
                                        </div>
                                    </div>

                                    <div v-if="candidatura.comentarios_admin" class="mt-3 p-2 bg-blue-50 dark:bg-blue-950/20 rounded border-l-4 border-blue-200 dark:border-blue-800">
                                        <p class="text-sm text-blue-800 dark:text-blue-200">
                                            <span class="font-medium">Comentarios de la comisión:</span>
                                            {{ candidatura.comentarios_admin }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 ml-4">
                                    <Link :href="`/admin/candidaturas/${candidatura.id}`">
                                        <Button variant="outline" size="sm">
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paginación -->
                    <Pagination :links="candidaturas.links" />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>