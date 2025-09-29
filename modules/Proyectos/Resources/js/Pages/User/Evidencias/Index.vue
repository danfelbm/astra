<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router, Head } from '@inertiajs/vue3';
import UserLayout from '@modules/Core/Resources/js/layouts/UserLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@modules/Core/Resources/js/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@modules/Core/Resources/js/components/ui/table';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@modules/Core/Resources/js/components/ui/tabs';
import {
    Upload,
    Camera,
    FileText,
    Video,
    AudioLines,
    Search,
    Filter,
    Eye,
    Edit,
    Trash2,
    Download,
    CheckCircle,
    XCircle,
    Clock,
    ArrowLeft
} from 'lucide-vue-next';
import type { Evidencia } from '@modules/Proyectos/Resources/js/types/evidencias';
import type { Contrato } from '@modules/Proyectos/Resources/js/types/contratos';
import type { ObligacionContrato } from '@modules/Proyectos/Resources/js/types/obligaciones';

// Props
const props = defineProps<{
    contrato: Contrato & {
        obligaciones?: ObligacionContrato[];
    };
    evidencias: {
        data: Evidencia[];
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
    };
    filters?: {
        search?: string;
        tipo?: string;
        estado?: string;
        obligacion_id?: number;
    };
    estadisticas?: {
        total: number;
        pendientes: number;
        aprobadas: number;
        rechazadas: number;
        por_tipo?: Record<string, number>;
        porcentaje_aprobacion?: number;
    };
    authPermissions?: string[];
}>();

// Estado local
const searchTerm = ref(props.filters?.search || '');
const selectedTipo = ref(props.filters?.tipo || 'all');
const selectedEstado = ref(props.filters?.estado || 'all');
const selectedObligacion = ref(props.filters?.obligacion_id || 'all');

// Computed
const tipoIcon = (tipo: string) => {
    const icons = {
        'imagen': Camera,
        'video': Video,
        'audio': AudioLines,
        'documento': FileText
    };
    return icons[tipo] || FileText;
};

const estadoBadgeVariant = (estado: string) => {
    const variants = {
        'pendiente': 'secondary',
        'aprobada': 'success',
        'rechazada': 'destructive'
    };
    return variants[estado] || 'default';
};

const estadoIcon = (estado: string) => {
    const icons = {
        'pendiente': Clock,
        'aprobada': CheckCircle,
        'rechazada': XCircle
    };
    return icons[estado] || Clock;
};

const canEdit = computed(() => props.authPermissions?.includes('evidencias.edit_own') || false);
const canDelete = computed(() => props.authPermissions?.includes('evidencias.delete_own') || false);
const canCreate = computed(() => props.authPermissions?.includes('evidencias.create_own') || false);

// Métodos
const applyFilters = () => {
    router.get(route('user.mis-contratos.evidencias.index', props.contrato.id), {
        search: searchTerm.value,
        tipo: selectedTipo.value === 'all' ? '' : selectedTipo.value,
        estado: selectedEstado.value === 'all' ? '' : selectedEstado.value,
        obligacion_id: selectedObligacion.value === 'all' ? '' : selectedObligacion.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    searchTerm.value = '';
    selectedTipo.value = 'all';
    selectedEstado.value = 'all';
    selectedObligacion.value = 'all';
    applyFilters();
};

const deleteEvidencia = (id: number) => {
    if (confirm('¿Estás seguro de que deseas eliminar esta evidencia?')) {
        router.delete(route('user.mis-contratos.evidencias.destroy', [props.contrato.id, id]), {
            preserveScroll: true,
            onSuccess: () => {
                // Mensaje de éxito manejado por el backend
            }
        });
    }
};

const formatDate = (date: string) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

// Breadcrumbs
const breadcrumbs = [
    { title: 'Dashboard', href: '/miembro/dashboard' },
    { title: 'Mis Contratos', href: '/miembro/mis-contratos' },
    { title: props.contrato.nombre, href: `/miembro/mis-contratos/${props.contrato.id}` },
    { title: 'Evidencias', href: '#' }
];
</script>

<template>
    <UserLayout :title="`Evidencias - ${contrato.nombre}`" :breadcrumbs="breadcrumbs">
        <Head :title="`Evidencias - ${contrato.nombre}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Evidencias del Contrato</h1>
                    <p class="text-muted-foreground mt-2">
                        Gestione todas las evidencias de cumplimiento de {{ contrato.nombre }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button
                        :href="route('user.mis-contratos.show', contrato.id)"
                        :as="Link"
                        variant="outline"
                    >
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Volver al Contrato
                    </Button>
                    <Button
                        v-if="canCreate"
                        :href="route('user.mis-contratos.evidencias.create', contrato.id)"
                        :as="Link"
                    >
                        <Upload class="w-4 h-4 mr-2" />
                        Nueva Evidencia
                    </Button>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Evidencias</CardTitle>
                        <FileText class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ evidencias.total }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Aprobadas</CardTitle>
                        <CheckCircle class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ evidencias?.data?.filter(e => e.estado === 'aprobada').length || 0 }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Pendientes</CardTitle>
                        <Clock class="h-4 w-4 text-orange-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ evidencias?.data?.filter(e => e.estado === 'pendiente').length || 0 }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Rechazadas</CardTitle>
                        <XCircle class="h-4 w-4 text-red-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ evidencias?.data?.filter(e => e.estado === 'rechazada').length || 0 }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filtros -->
            <Card>
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                    <CardDescription>Filtre las evidencias por tipo, estado u obligación</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-5">
                        <div>
                            <Input
                                v-model="searchTerm"
                                placeholder="Buscar por descripción..."
                                @keyup.enter="applyFilters"
                            />
                        </div>

                        <div>
                            <Select v-model="selectedTipo">
                                <SelectTrigger>
                                    <SelectValue placeholder="Tipo de evidencia" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los tipos</SelectItem>
                                    <SelectItem value="imagen">Imagen</SelectItem>
                                    <SelectItem value="video">Video</SelectItem>
                                    <SelectItem value="audio">Audio</SelectItem>
                                    <SelectItem value="documento">Documento</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Select v-model="selectedEstado">
                                <SelectTrigger>
                                    <SelectValue placeholder="Estado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem value="pendiente">Pendiente</SelectItem>
                                    <SelectItem value="aprobada">Aprobada</SelectItem>
                                    <SelectItem value="rechazada">Rechazada</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Select v-model="selectedObligacion">
                                <SelectTrigger>
                                    <SelectValue placeholder="Obligación" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todas las obligaciones</SelectItem>
                                    <SelectItem
                                        v-for="obligacion in (contrato.obligaciones || [])"
                                        :key="obligacion.id"
                                        :value="obligacion.id.toString()"
                                    >
                                        {{ obligacion.nombre }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex gap-2">
                            <Button @click="applyFilters" class="flex-1">
                                <Filter class="w-4 h-4 mr-2" />
                                Filtrar
                            </Button>
                            <Button @click="clearFilters" variant="outline">
                                Limpiar
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Lista de evidencias -->
            <Card>
                <CardHeader>
                    <CardTitle>Evidencias Registradas</CardTitle>
                    <CardDescription>
                        Mostrando {{ evidencias?.data?.length || 0 }} de {{ evidencias?.total || 0 }} evidencias
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="!evidencias?.data?.length" class="text-center py-8">
                        <FileText class="w-12 h-12 mx-auto text-muted-foreground mb-3" />
                        <p class="text-muted-foreground">No se encontraron evidencias con los filtros aplicados</p>
                    </div>

                    <Table v-else>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Obligación</TableHead>
                                <TableHead>Descripción</TableHead>
                                <TableHead>Archivos</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Fecha</TableHead>
                                <TableHead>Tamaño</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="evidencia in (evidencias?.data || [])" :key="evidencia.id">
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <component :is="tipoIcon(evidencia.tipo_evidencia)" class="w-4 h-4 text-muted-foreground" />
                                        <span class="capitalize">{{ evidencia.tipo_evidencia }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <span class="text-sm">{{ evidencia.obligacion?.nombre || '-' }}</span>
                                </TableCell>
                                <TableCell>
                                    <span class="text-sm">{{ evidencia.descripcion || 'Sin descripción' }}</span>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <Badge variant="secondary" class="text-xs">
                                            {{ evidencia.total_archivos || 1 }}
                                        </Badge>
                                        <span class="text-xs text-muted-foreground">
                                            {{ (evidencia.total_archivos || 1) === 1 ? 'archivo' : 'archivos' }}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="estadoBadgeVariant(evidencia.estado)">
                                        <component :is="estadoIcon(evidencia.estado)" class="w-3 h-3 mr-1" />
                                        {{ evidencia.estado }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <span class="text-sm text-muted-foreground">{{ formatDate(evidencia.created_at) }}</span>
                                </TableCell>
                                <TableCell>
                                    <span class="text-sm text-muted-foreground">
                                        {{ formatFileSize(evidencia.metadata?.size || 0) }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            :href="route('user.mis-contratos.evidencias.show', [contrato.id, evidencia.id])"
                                            :as="Link"
                                            variant="ghost"
                                            size="sm"
                                        >
                                            <Eye class="w-4 h-4" />
                                        </Button>
                                        <Button
                                            v-if="canEdit && evidencia.estado === 'pendiente'"
                                            :href="route('user.mis-contratos.evidencias.edit', [contrato.id, evidencia.id])"
                                            :as="Link"
                                            variant="ghost"
                                            size="sm"
                                        >
                                            <Edit class="w-4 h-4" />
                                        </Button>
                                        <Button
                                            v-if="canDelete && evidencia.estado === 'pendiente'"
                                            @click="deleteEvidencia(evidencia.id)"
                                            variant="ghost"
                                            size="sm"
                                            class="text-destructive hover:text-destructive"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Paginación -->
                    <div v-if="(evidencias?.last_page || 1) > 1" class="mt-4 flex justify-center">
                        <nav class="flex gap-1">
                            <Button
                                v-for="page in (evidencias?.last_page || 1)"
                                :key="page"
                                @click="router.get(route('user.mis-contratos.evidencias.index', contrato.id), { ...filters, page })"
                                :variant="page === (evidencias?.current_page || 1) ? 'default' : 'outline'"
                                size="sm"
                            >
                                {{ page }}
                            </Button>
                        </nav>
                    </div>
                </CardContent>
            </Card>
        </div>
    </UserLayout>
</template>