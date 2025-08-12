<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle, Clock, XCircle, Eye, ArrowLeft, Search, FileText, Users } from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface CsvImport {
    id: number;
    original_filename: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    total_rows: number;
    processed_rows: number;
    successful_rows: number;
    failed_rows: number;
    progress_percentage: number;
    duration: string | null;
    created_at: string;
    created_by: {
        name: string;
    };
}

interface Votacion {
    id: number;
    titulo: string;
}

interface Props {
    votacion: Votacion;
    imports: {
        data: CsvImport[];
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
}

const props = defineProps<Props>();

const searchTerm = ref('');
const statusFilter = ref('all');

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Votaciones', href: '/admin/votaciones' },
    { title: props.votacion.titulo, href: `/admin/votaciones/${props.votacion.id}/edit` },
    { title: 'Historial de Importaciones', href: '#' },
];

// Estados disponibles para filtrar
const statusOptions = [
    { value: 'all', label: 'Todos' },
    { value: 'pending', label: 'Pendientes' },
    { value: 'processing', label: 'Procesando' },
    { value: 'completed', label: 'Completadas' },
    { value: 'failed', label: 'Fallidas' },
];

// Configuración de estados
const getStatusConfig = (status: CsvImport['status']) => {
    switch (status) {
        case 'pending':
            return {
                icon: Clock,
                label: 'Pendiente',
                class: 'bg-yellow-100 text-yellow-800 border-yellow-300'
            };
        case 'processing':
            return {
                icon: Clock,
                label: 'Procesando',
                class: 'bg-blue-100 text-blue-800 border-blue-300'
            };
        case 'completed':
            return {
                icon: CheckCircle,
                label: 'Completada',
                class: 'bg-green-100 text-green-800 border-green-300'
            };
        case 'failed':
            return {
                icon: XCircle,
                label: 'Fallida',
                class: 'bg-red-100 text-red-800 border-red-300'
            };
    }
};

// Funciones de navegación
const goBackToVotacion = () => {
    router.get(`/admin/votaciones/${props.votacion.id}/edit`);
};

const viewProgress = (importId: number) => {
    router.get(`/admin/imports/${importId}`);
};

// Formatear fecha
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Obtener resumen de resultados
const getResultSummary = (imp: CsvImport) => {
    if (imp.status === 'pending') return 'En cola';
    if (imp.status === 'processing') return `${imp.processed_rows}/${imp.total_rows} procesados`;
    if (imp.status === 'failed') return 'Falló durante procesamiento';
    
    const parts = [];
    if (imp.successful_rows > 0) parts.push(`${imp.successful_rows} exitosos`);
    if (imp.failed_rows > 0) parts.push(`${imp.failed_rows} errores`);
    
    return parts.join(', ') || 'Sin datos';
};
</script>

<template>
    <Head title="Historial de Importaciones" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Historial de Importaciones</h1>
                    <p class="text-muted-foreground">
                        Votación: {{ votacion.titulo }}
                    </p>
                </div>
                <Button variant="outline" @click="goBackToVotacion">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver a Votación
                </Button>
            </div>

            <!-- Resumen -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center space-x-2">
                            <FileText class="h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-2xl font-bold">{{ imports.total }}</p>
                                <p class="text-xs text-muted-foreground">Total Importaciones</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center space-x-2">
                            <CheckCircle class="h-5 w-5 text-green-600" />
                            <div>
                                <p class="text-2xl font-bold text-green-600">
                                    {{ imports.data.filter(i => i.status === 'completed').length }}
                                </p>
                                <p class="text-xs text-muted-foreground">Completadas</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center space-x-2">
                            <Clock class="h-5 w-5 text-blue-600" />
                            <div>
                                <p class="text-2xl font-bold text-blue-600">
                                    {{ imports.data.filter(i => ['pending', 'processing'].includes(i.status)).length }}
                                </p>
                                <p class="text-xs text-muted-foreground">Activas</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center space-x-2">
                            <XCircle class="h-5 w-5 text-red-600" />
                            <div>
                                <p class="text-2xl font-bold text-red-600">
                                    {{ imports.data.filter(i => i.status === 'failed').length }}
                                </p>
                                <p class="text-xs text-muted-foreground">Fallidas</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filtros -->
            <Card>
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="searchTerm"
                                    placeholder="Buscar por nombre de archivo..."
                                    class="pl-9"
                                />
                            </div>
                        </div>
                        <div class="w-48">
                            <Select v-model="statusFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="Estado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in statusOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Tabla de Importaciones -->
            <Card>
                <CardHeader>
                    <CardTitle>Importaciones ({{ imports.total }})</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="imports.data.length === 0" class="text-center py-8 text-muted-foreground">
                        <FileText class="mx-auto h-12 w-12 mb-4" />
                        <p>No hay importaciones registradas para esta votación</p>
                        <Button 
                            variant="outline" 
                            class="mt-4"
                            @click="goBackToVotacion"
                        >
                            Realizar Primera Importación
                        </Button>
                    </div>

                    <Table v-else>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Archivo</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Progreso</TableHead>
                                <TableHead>Resultados</TableHead>
                                <TableHead>Creado por</TableHead>
                                <TableHead>Fecha</TableHead>
                                <TableHead>Duración</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="import_ in imports.data" :key="import_.id">
                                <TableCell class="font-medium">
                                    {{ import_.original_filename }}
                                </TableCell>
                                <TableCell>
                                    <Badge :class="getStatusConfig(import_.status).class">
                                        <component 
                                            :is="getStatusConfig(import_.status).icon"
                                            class="mr-1 h-3 w-3"
                                        />
                                        {{ getStatusConfig(import_.status).label }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <div class="w-20 bg-gray-200 rounded-full h-2">
                                            <div 
                                                class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                                :style="{ width: `${import_.progress_percentage}%` }"
                                            ></div>
                                        </div>
                                        <span class="text-xs text-muted-foreground">
                                            {{ import_.progress_percentage }}%
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm">
                                        {{ getResultSummary(import_) }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    {{ import_.created_by.name }}
                                </TableCell>
                                <TableCell>
                                    {{ formatDate(import_.created_at) }}
                                </TableCell>
                                <TableCell>
                                    {{ import_.duration || '-' }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="viewProgress(import_.id)"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Paginación -->
                    <div v-if="imports.last_page > 1" class="mt-4 flex justify-center">
                        <div class="flex gap-2">
                            <Button
                                v-for="link in imports.links"
                                :key="link.label"
                                :variant="link.active ? 'default' : 'outline'"
                                size="sm"
                                :disabled="!link.url"
                                @click="link.url && router.get(link.url)"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>