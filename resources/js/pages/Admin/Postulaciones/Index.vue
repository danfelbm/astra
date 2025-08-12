<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { 
    Search, 
    Filter, 
    Eye, 
    Users, 
    CheckCircle, 
    XCircle, 
    Clock, 
    Edit, 
    Send,
    FileText,
    User
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import AdvancedFilters from '@/components/filters/AdvancedFilters.vue';
import type { AdvancedFilterConfig } from '@/types/filters';

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface Convocatoria {
    id: number;
    nombre: string;
    cargo: string;
    periodo: string;
}

interface Postulacion {
    id: number;
    usuario: Usuario;
    convocatoria: Convocatoria;
    estado: string;
    estado_label: string;
    estado_color: string;
    fecha_postulacion: string | null;
    tiene_candidatura_vinculada: boolean;
    comentarios_admin: string | null;
    revisado_por: { name: string; email: string } | null;
    fecha_revision: string | null;
    created_at: string;
}

interface ConvocatoriaFilter {
    id: number;
    nombre: string;
    cargo: string;
}

interface Props {
    postulaciones: {
        data: Postulacion[];
        links: any[];
        meta: any;
    };
    convocatorias: ConvocatoriaFilter[];
    filters: {
        convocatoria_id?: number;
        estado?: string;
        search?: string;
        tiene_candidatura?: boolean;
    
        advanced_filters?: string;};
    filterFieldsConfig: any[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin/dashboard',
    },
    {
        title: 'Postulaciones',
        href: '/admin/postulaciones',
    },
];

// Filtros reactivos
const filters = ref({
    convocatoria_id: props.filters.convocatoria_id || 'all',
    estado: props.filters.estado || 'all',
    search: props.filters.search || '',
    tiene_candidatura: props.filters.tiene_candidatura !== undefined ? props.filters.tiene_candidatura.toString() : 'all',
});

// Código de estadísticas removido - se moverá a un dashboard unificado

const getEstadoIcon = (estado: string) => {
    switch (estado) {
        case 'borrador':
            return Edit;
        case 'enviada':
            return Send;
        case 'en_revision':
            return Clock;
        case 'aceptada':
            return CheckCircle;
        case 'rechazada':
            return XCircle;
        default:
            return FileText;
    }
};

// Aplicar filtros con debounce
let timeout: NodeJS.Timeout;
watch(filters, () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        const params = new URLSearchParams();
        
        if (filters.value.convocatoria_id && filters.value.convocatoria_id !== 'all') {
            params.set('convocatoria_id', filters.value.convocatoria_id);
        }
        if (filters.value.estado && filters.value.estado !== 'all') {
            params.set('estado', filters.value.estado);
        }
        if (filters.value.search) {
            params.set('search', filters.value.search);
        }
        if (filters.value.tiene_candidatura && filters.value.tiene_candidatura !== 'all') {
            params.set('tiene_candidatura', filters.value.tiene_candidatura);
        }
        
        router.get('/admin/postulaciones', Object.fromEntries(params), {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
}, { deep: true });

// Configuración para el componente de filtros avanzados
const filterConfig: AdvancedFilterConfig = {
    fields: props.filterFieldsConfig || [],
    showQuickSearch: true,
    quickSearchPlaceholder: 'Buscar por nombre o email...',
    quickSearchFields: ['user.name', 'user.email', 'convocatoria.nombre'],
    maxNestingLevel: 2,
    allowSaveFilters: true,
    debounceTime: 500,
    autoApply: false,
};

// Helper para obtener route
const { route } = window as any;

const limpiarFiltros = () => {
    filters.value = {
        convocatoria_id: 'all',
        estado: 'all',
        search: '',
        tiene_candidatura: 'all',
    };
};
</script>

<template>
    <Head title="Gestión de Postulaciones" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Gestión de Postulaciones</h1>
            <p class="text-muted-foreground">
                Revisa y gestiona las postulaciones a convocatorias electorales.
            </p>
        </div>

        <!-- Advanced Filters -->
            <AdvancedFilters
                :config="filterConfig"
                :route="route('admin.postulaciones.index')"
                :initial-filters="{
                    quickSearch: filters.search,
                    rootGroup: filters.advanced_filters ? JSON.parse(filters.advanced_filters) : undefined
                }"
            />

        <!-- Tabla de postulaciones -->
        <Card>
            <CardContent class="pt-6">
                <div class="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Usuario</TableHead>
                                <TableHead>Convocatoria</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Fecha Postulación</TableHead>
                                <TableHead>Perfil</TableHead>
                                <TableHead>Revisado por</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="(postulaciones?.data || []).length === 0">
                                <TableCell colspan="7" class="text-center text-muted-foreground py-8">
                                    No se encontraron postulaciones con los filtros aplicados.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="postulacion in (postulaciones?.data || [])" :key="postulacion.id">
                                <TableCell>
                                    <div>
                                        <p class="font-medium">{{ postulacion.usuario.name }}</p>
                                        <p class="text-sm text-muted-foreground">{{ postulacion.usuario.email }}</p>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div>
                                        <p class="font-medium">{{ postulacion.convocatoria.nombre }}</p>
                                        <p class="text-sm text-muted-foreground">
                                            {{ postulacion.convocatoria.cargo }} - {{ postulacion.convocatoria.periodo }}
                                        </p>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="postulacion.estado_color">
                                        <component :is="getEstadoIcon(postulacion.estado)" class="mr-1 h-3 w-3" />
                                        {{ postulacion.estado_label }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <p class="text-sm">{{ postulacion.fecha_postulacion || 'Borrador' }}</p>
                                    <p class="text-xs text-muted-foreground">{{ postulacion.created_at }}</p>
                                </TableCell>
                                <TableCell>
                                    <Badge v-if="postulacion.tiene_candidatura_vinculada" variant="secondary" class="bg-green-100 text-green-800">
                                        <User class="mr-1 h-3 w-3" />
                                        Vinculado
                                    </Badge>
                                    <span v-else class="text-sm text-muted-foreground">No vinculado</span>
                                </TableCell>
                                <TableCell>
                                    <div v-if="postulacion.revisado_por">
                                        <p class="text-sm font-medium">{{ postulacion.revisado_por.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ postulacion.fecha_revision }}</p>
                                    </div>
                                    <span v-else class="text-sm text-muted-foreground">Sin revisar</span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="router.get(`/admin/postulaciones/${postulacion.id}`)"
                                        :disabled="!postulacion.id"
                                    >
                                        <Eye class="mr-2 h-4 w-4" />
                                        Ver detalle
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Paginación -->
                <div v-if="(postulaciones?.links || []).length > 3" class="mt-4 flex justify-center">
                    <nav class="flex items-center gap-1">
                        <Button
                            v-for="link in (postulaciones?.links || [])"
                            :key="link.label"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            :disabled="!link.url"
                            @click="() => link.url && router.get(link.url)"
                            v-html="link.label"
                        />
                    </nav>
                </div>
            </CardContent>
        </Card>
        </div>
        </div>
    </AppLayout>
</template>