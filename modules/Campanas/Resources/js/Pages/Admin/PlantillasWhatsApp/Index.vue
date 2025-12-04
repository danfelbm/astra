<script setup lang="ts">
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@modules/Core/Resources/js/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@modules/Core/Resources/js/components/ui/table";
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
} from "@modules/Core/Resources/js/components/ui/alert-dialog";
import { type BreadcrumbItemType } from '@/types';
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { Head, Link, router } from '@inertiajs/vue3';
import { Edit, Plus, Search, Trash2, MessageSquare, Copy, Eye, Calendar, User, CheckCircle, XCircle, Smartphone } from 'lucide-vue-next';
import AdvancedFilters from "@modules/Core/Resources/js/components/filters/AdvancedFilters.vue";
import type { AdvancedFilterConfig } from "@modules/Core/Resources/js/types/filters";
import { ref } from 'vue';
import { toast } from 'vue-sonner';

interface PlantillaWhatsApp {
    id: number;
    nombre: string;
    descripcion?: string;
    contenido: string;
    variables_usadas?: string[];
    usa_formato: boolean;
    es_activa: boolean;
    created_by?: number;
    created_at: string;
    updated_at: string;
    creator?: {
        id: number;
        nombre: string;
    };
    campanas_count?: number;
}

interface Props {
    plantillas: {
        data: PlantillaWhatsApp[];
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
    filters: {
        search?: string;
        es_activa?: string;
        usa_formato?: string;
        advanced_filters?: string;
    };
    filterFieldsConfig?: any[];
    canCreate?: boolean;
    canEdit?: boolean;
    canDelete?: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Campañas', href: '/admin/envio-campanas' },
    { title: 'Plantillas WhatsApp', href: '/admin/campanas/plantillas-whatsapp' },
];

// Configuración para filtros avanzados
const filterConfig: AdvancedFilterConfig = {
    fields: props.filterFieldsConfig || [],
    showQuickSearch: true,
    quickSearchPlaceholder: 'Buscar plantillas...',
    quickSearchFields: ['nombre', 'descripcion', 'contenido'],
    maxNestingLevel: 2,
    allowSaveFilters: true,
    debounceTime: 500,
    autoApply: false,
};

const searchQuery = ref(props.filters.search || '');
const selectedActiva = ref(props.filters.es_activa || 'all');
const selectedFormato = ref(props.filters.usa_formato || 'all');

// Aplicar filtros
const applyFilters = () => {
    router.get('/admin/campanas/plantillas-whatsapp', {
        search: searchQuery.value,
        es_activa: selectedActiva.value !== 'all' ? selectedActiva.value : undefined,
        usa_formato: selectedFormato.value !== 'all' ? selectedFormato.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Duplicar plantilla
const duplicatePlantilla = (id: number) => {
    router.post(`/admin/campanas/plantillas-whatsapp/${id}/duplicate`, {}, {
        onSuccess: () => {
            toast.success('Plantilla duplicada exitosamente');
        },
        onError: () => {
            toast.error('Error al duplicar la plantilla');
        }
    });
};

// Eliminar plantilla
const deletePlantilla = (id: number) => {
    router.delete(`/admin/campanas/plantillas-whatsapp/${id}`, {
        onSuccess: () => {
            toast.success('Plantilla eliminada exitosamente');
        },
        onError: () => {
            toast.error('Error al eliminar la plantilla');
        }
    });
};

// Preview de plantilla
const previewPlantilla = (plantilla: PlantillaWhatsApp) => {
    router.post(`/admin/campanas/plantillas-whatsapp/${plantilla.id}/preview`, {}, {
        onSuccess: (response: any) => {
            // Mostrar preview en modal
            toast.info(`Vista previa: ${response.preview}`);
        }
    });
};

// Formatear fecha
const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

// Contar variables
const countVariables = (variables?: string[]) => {
    return variables?.length || 0;
};

// Truncar contenido para preview
const truncateContent = (content: string, maxLength: number = 100) => {
    if (content.length <= maxLength) return content;
    return content.substring(0, maxLength) + '...';
};
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Plantillas de WhatsApp" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header con título y botón de crear -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Plantillas de WhatsApp</h1>
                    <p class="text-muted-foreground mt-1">
                        Gestiona las plantillas de mensajes de WhatsApp para tus campañas
                    </p>
                </div>
                <Link 
                    v-if="canCreate"
                    :href="'/admin/campanas/plantillas-whatsapp/create'"
                    class="inline-flex"
                >
                    <Button>
                        <Plus class="w-4 h-4 mr-2" />
                        Nueva Plantilla
                    </Button>
                </Link>
            </div>

            <!-- Filtros -->
            <Card>
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-2">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground w-4 h-4" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Buscar por nombre o contenido..."
                                    class="pl-10"
                                    @keyup.enter="applyFilters"
                                />
                            </div>
                        </div>
                        <Select v-model="selectedActiva" @update:modelValue="applyFilters">
                            <SelectTrigger>
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todas</SelectItem>
                                <SelectItem value="1">Activas</SelectItem>
                                <SelectItem value="0">Inactivas</SelectItem>
                            </SelectContent>
                        </Select>
                        <Select v-model="selectedFormato" @update:modelValue="applyFilters">
                            <SelectTrigger>
                                <SelectValue placeholder="Formato" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="1">Con formato</SelectItem>
                                <SelectItem value="0">Sin formato</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            <!-- Tabla de plantillas -->
            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Contenido</TableHead>
                                <TableHead>Variables</TableHead>
                                <TableHead>Formato</TableHead>
                                <TableHead>Campañas</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Creado</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="plantilla in plantillas.data" :key="plantilla.id">
                                <TableCell class="font-medium">
                                    <div>
                                        <div class="font-medium">{{ plantilla.nombre }}</div>
                                        <div v-if="plantilla.descripcion" class="text-sm text-muted-foreground">
                                            {{ plantilla.descripcion }}
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-start gap-2">
                                        <MessageSquare class="w-4 h-4 text-green-600 mt-0.5 flex-shrink-0" />
                                        <span class="text-sm text-muted-foreground">
                                            {{ truncateContent(plantilla.contenido) }}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary">
                                        {{ countVariables(plantilla.variables_usadas) }} variables
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="plantilla.usa_formato ? 'default' : 'outline'">
                                        <Smartphone class="w-3 h-3 mr-1" />
                                        {{ plantilla.usa_formato ? 'Con formato' : 'Sin formato' }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <span v-if="plantilla.campanas_count && plantilla.campanas_count > 0" 
                                          class="text-sm">
                                        {{ plantilla.campanas_count }} campañas
                                    </span>
                                    <span v-else class="text-sm text-muted-foreground">
                                        Sin uso
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="plantilla.es_activa ? 'default' : 'secondary'">
                                        <CheckCircle v-if="plantilla.es_activa" class="w-3 h-3 mr-1" />
                                        <XCircle v-else class="w-3 h-3 mr-1" />
                                        {{ plantilla.es_activa ? 'Activa' : 'Inactiva' }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm">
                                        <div>{{ formatDate(plantilla.created_at) }}</div>
                                        <div v-if="plantilla.creator" class="text-muted-foreground flex items-center gap-1">
                                            <User class="w-3 h-3" />
                                            {{ plantilla.creator.nombre }}
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="previewPlantilla(plantilla)"
                                            title="Vista previa"
                                        >
                                            <Eye class="w-4 h-4" />
                                        </Button>
                                        <Button
                                            v-if="canEdit"
                                            variant="ghost"
                                            size="sm"
                                            @click="duplicatePlantilla(plantilla.id)"
                                            title="Duplicar"
                                        >
                                            <Copy class="w-4 h-4" />
                                        </Button>
                                        <Link
                                            v-if="canEdit"
                                            :href="`/admin/campanas/plantillas-whatsapp/${plantilla.id}/edit`"
                                        >
                                            <Button variant="ghost" size="sm" title="Editar">
                                                <Edit class="w-4 h-4" />
                                            </Button>
                                        </Link>
                                        <AlertDialog v-if="canDelete && (!plantilla.campanas_count || plantilla.campanas_count === 0)">
                                            <AlertDialogTrigger asChild>
                                                <Button variant="ghost" size="sm" title="Eliminar">
                                                    <Trash2 class="w-4 h-4 text-destructive" />
                                                </Button>
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>¿Eliminar plantilla?</AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        Esta acción no se puede deshacer. La plantilla "{{ plantilla.nombre }}" 
                                                        será eliminada permanentemente.
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                                    <AlertDialogAction @click="deletePlantilla(plantilla.id)">
                                                        Eliminar
                                                    </AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="plantillas.data.length === 0">
                                <TableCell colspan="8" class="text-center py-8">
                                    <div class="text-muted-foreground">
                                        <MessageSquare class="w-12 h-12 mx-auto mb-3 opacity-50" />
                                        <p>No se encontraron plantillas de WhatsApp</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Paginación -->
            <div v-if="plantillas.last_page > 1" class="flex justify-center">
                <nav class="flex gap-1">
                    <Link
                        v-for="link in plantillas.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-2 text-sm rounded-md',
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-background hover:bg-muted',
                            !link.url && 'opacity-50 cursor-not-allowed'
                        ]"
                        v-html="link.label"
                    />
                </nav>
            </div>
        </div>
    </AdminLayout>
</template>