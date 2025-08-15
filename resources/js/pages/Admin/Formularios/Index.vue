<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Plus, Eye, Edit, Trash2, Download, FileText } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';
import { ref } from 'vue';

// Props
interface Props {
    formularios: {
        data: Array<{
            id: number;
            titulo: string;
            descripcion: string | null;
            slug: string;
            tipo_acceso: string;
            estado: string;
            activo: boolean;
            fecha_inicio: string | null;
            fecha_fin: string | null;
            created_at: string;
            categoria: {
                id: number;
                nombre: string;
                color: string | null;
            } | null;
            creador: {
                id: number;
                name: string;
            } | null;
            estado_temporal: string;
            estado_temporal_label: string;
            estado_temporal_color: string;
            estadisticas: {
                total_respuestas: number;
                respuestas_hoy: number;
                respuestas_semana: number;
                respuestas_mes: number;
                usuarios_unicos: number;
                visitantes_unicos: number;
            };
            url_publica: string;
        }>;
        links: any;
        meta: any;
    };
    categorias: Array<any>;
    filters: any;
    filterFieldsConfig: Array<any>;
}

const props = defineProps<Props>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Formularios', href: '#' },
];

// Funciones
const eliminarFormulario = (id: number) => {
    if (confirm('¿Estás seguro de eliminar este formulario?')) {
        router.delete(route('admin.formularios.destroy', id));
    }
};

const getEstadoColorClass = (color: string) => {
    const colorMap: Record<string, string> = {
        gray: 'bg-gray-100 text-gray-800',
        slate: 'bg-slate-100 text-slate-800',
        red: 'bg-red-100 text-red-800',
        blue: 'bg-blue-100 text-blue-800',
        orange: 'bg-orange-100 text-orange-800',
        yellow: 'bg-yellow-100 text-yellow-800',
        green: 'bg-green-100 text-green-800',
    };
    return colorMap[color] || 'bg-gray-100 text-gray-800';
};

const getTipoAccesoLabel = (tipo: string) => {
    const labels: Record<string, string> = {
        publico: 'Público',
        autenticado: 'Autenticado',
        con_permiso: 'Con Permiso',
    };
    return labels[tipo] || tipo;
};
</script>

<template>
    <Head title="Gestión de Formularios" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Encabezado -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Gestión de Formularios</h1>
                    <p class="text-muted-foreground">
                        Administra los formularios dinámicos del sistema
                    </p>
                </div>
                <Link :href="route('admin.formularios.create')">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Formulario
                    </Button>
                </Link>
            </div>

            <!-- Mensaje cuando no hay formularios -->
            <div v-if="formularios.data.length === 0" class="text-center py-12">
                <FileText class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-lg font-medium text-gray-900">No hay formularios</h3>
                <p class="mt-1 text-sm text-gray-500">Comienza creando un nuevo formulario.</p>
                <div class="mt-6">
                    <Link :href="route('admin.formularios.create')">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Crear primer formulario
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Grid de formularios -->
            <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="formulario in formularios.data" :key="formulario.id">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <CardTitle class="text-lg">{{ formulario.titulo }}</CardTitle>
                                <CardDescription v-if="formulario.descripcion" class="mt-1">
                                    {{ formulario.descripcion }}
                                </CardDescription>
                            </div>
                            <Badge :class="getEstadoColorClass(formulario.estado_temporal_color)">
                                {{ formulario.estado_temporal_label }}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <!-- Información del formulario -->
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Tipo de acceso:</span>
                                <Badge variant="outline">{{ getTipoAccesoLabel(formulario.tipo_acceso) }}</Badge>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Respuestas:</span>
                                <span class="font-medium">{{ formulario.estadisticas.total_respuestas }}</span>
                            </div>
                            <div v-if="formulario.categoria" class="flex items-center justify-between">
                                <span class="text-muted-foreground">Categoría:</span>
                                <span>{{ formulario.categoria.nombre }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Slug:</span>
                                <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">{{ formulario.slug }}</code>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="mt-4 flex gap-2">
                            <Link :href="route('admin.formularios.show', formulario.id)">
                                <Button variant="outline" size="sm">
                                    <Eye class="h-4 w-4" />
                                </Button>
                            </Link>
                            <Link :href="route('admin.formularios.edit', formulario.id)">
                                <Button variant="outline" size="sm">
                                    <Edit class="h-4 w-4" />
                                </Button>
                            </Link>
                            <Button
                                v-if="formulario.estadisticas.total_respuestas > 0"
                                variant="outline"
                                size="sm"
                                @click="router.visit(route('admin.formularios.exportar', formulario.id))"
                            >
                                <Download class="h-4 w-4" />
                            </Button>
                            <Button
                                v-if="formulario.estadisticas.total_respuestas === 0"
                                variant="outline"
                                size="sm"
                                @click="eliminarFormulario(formulario.id)"
                            >
                                <Trash2 class="h-4 w-4 text-destructive" />
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Paginación -->
            <div v-if="formularios.links && formularios.links.length > 3" class="mt-6">
                <nav class="flex items-center justify-center gap-1">
                    <template v-for="link in formularios.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'px-3 py-2 text-sm rounded-md',
                                link.active
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-white hover:bg-gray-50 border'
                            ]"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-2 text-sm text-gray-400"
                            v-html="link.label"
                        />
                    </template>
                </nav>
            </div>
        </div>
    </AppLayout>
</template>