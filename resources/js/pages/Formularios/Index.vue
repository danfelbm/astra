<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Search, FileText, ArrowRight, Clock, CheckCircle, FileEdit, Info } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import type { BreadcrumbItem } from '@/types';

// Props
interface Props {
    formularios: {
        data: Array<{
            id: number;
            titulo: string;
            descripcion?: string;
            slug: string;
            categoria?: {
                id: number;
                nombre: string;
            };
            fecha_inicio?: string;
            fecha_fin?: string;
            limite_respuestas?: number;
            respuestas_count?: number;
            usuario_ha_respondido?: boolean;
            tiene_borrador?: boolean;
            created_at: string;
        }>;
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    categorias: Array<{
        id: number;
        nombre: string;
        descripcion?: string;
    }>;
    filters: {
        search?: string;
        categoria?: string;
    };
}

const props = defineProps<Props>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Formularios', href: '#' },
];

// Estado local
const search = ref(props.filters.search || '');
const categoriaFilter = ref(props.filters.categoria || 'all');

// Computed
const tieneFormularios = computed(() => props.formularios.data.length > 0);

// Métodos
const handleSearch = () => {
    router.get(route('formularios.index'), {
        search: search.value || undefined,
        categoria: categoriaFilter.value === 'all' ? undefined : categoriaFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleCategoriaChange = (value: string) => {
    categoriaFilter.value = value;
    handleSearch();
};

const limpiarFiltros = () => {
    search.value = '';
    categoriaFilter.value = 'all';
    handleSearch();
};

const getEstadoFormulario = (formulario: any) => {
    if (formulario.usuario_ha_respondido) {
        return { texto: 'Completado', color: 'success', icon: CheckCircle };
    }
    if (formulario.tiene_borrador) {
        return { texto: 'Borrador guardado', color: 'warning', icon: FileEdit };
    }
    
    // Verificar si está próximo a cerrar
    if (formulario.fecha_fin) {
        const diasRestantes = Math.ceil((new Date(formulario.fecha_fin).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));
        if (diasRestantes <= 3 && diasRestantes > 0) {
            return { texto: `Cierra en ${diasRestantes} días`, color: 'destructive', icon: Clock };
        }
    }
    
    return { texto: 'Disponible', color: 'default', icon: FileText };
};

const formatearFecha = (fecha: string) => {
    return new Date(fecha).toLocaleDateString('es-ES', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Formularios" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Encabezado -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold">Formularios Disponibles</h1>
                <p class="text-muted-foreground">
                    Completa los formularios que están disponibles para ti
                </p>
            </div>
            
            <!-- Filtros -->
            <Card class="mb-6">
                <CardContent class="pt-6">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                                <Input
                                    v-model="search"
                                    placeholder="Buscar formularios..."
                                    class="pl-10"
                                    @keyup.enter="handleSearch"
                                />
                            </div>
                        </div>
                        
                        <Select 
                            v-model="categoriaFilter"
                            @update:modelValue="handleCategoriaChange"
                        >
                            <SelectTrigger class="w-full md:w-[200px]">
                                <SelectValue placeholder="Todas las categorías" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todas las categorías</SelectItem>
                                <SelectItem 
                                    v-for="categoria in categorias" 
                                    :key="categoria.id"
                                    :value="categoria.id.toString()"
                                >
                                    {{ categoria.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        
                        <Button @click="handleSearch" variant="default">
                            <Search class="mr-2 h-4 w-4" />
                            Buscar
                        </Button>
                        
                        <Button 
                            v-if="search || (categoriaFilter && categoriaFilter !== 'all')"
                            @click="limpiarFiltros" 
                            variant="outline"
                        >
                            Limpiar
                        </Button>
                    </div>
                </CardContent>
            </Card>
            
            <!-- Lista de formularios -->
            <div v-if="tieneFormularios" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Card 
                    v-for="formulario in formularios.data" 
                    :key="formulario.id"
                    class="hover:shadow-lg transition-shadow cursor-pointer"
                    @click="router.visit(route('formularios.show', formulario.slug))"
                >
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <CardTitle class="line-clamp-2">{{ formulario.titulo }}</CardTitle>
                                <CardDescription v-if="formulario.descripcion" class="mt-2 line-clamp-2">
                                    {{ formulario.descripcion }}
                                </CardDescription>
                            </div>
                            <component 
                                :is="getEstadoFormulario(formulario).icon"
                                :class="[
                                    'h-5 w-5 ml-2',
                                    getEstadoFormulario(formulario).color === 'success' ? 'text-green-600' :
                                    getEstadoFormulario(formulario).color === 'warning' ? 'text-yellow-600' :
                                    getEstadoFormulario(formulario).color === 'destructive' ? 'text-red-600' :
                                    'text-muted-foreground'
                                ]"
                            />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <!-- Categoría -->
                            <div v-if="formulario.categoria" class="flex items-center gap-2">
                                <Badge variant="secondary">
                                    {{ formulario.categoria.nombre }}
                                </Badge>
                            </div>
                            
                            <!-- Estado del formulario -->
                            <div class="flex items-center gap-2">
                                <Badge 
                                    :variant="getEstadoFormulario(formulario).color === 'success' ? 'default' :
                                             getEstadoFormulario(formulario).color === 'warning' ? 'secondary' :
                                             getEstadoFormulario(formulario).color === 'destructive' ? 'destructive' :
                                             'outline'"
                                >
                                    {{ getEstadoFormulario(formulario).texto }}
                                </Badge>
                            </div>
                            
                            <!-- Fechas -->
                            <div v-if="formulario.fecha_fin" class="text-sm text-muted-foreground">
                                <Clock class="inline h-3 w-3 mr-1" />
                                Cierra el {{ formatearFecha(formulario.fecha_fin) }}
                            </div>
                            
                            <!-- Límite de respuestas -->
                            <div v-if="formulario.limite_respuestas" class="text-sm text-muted-foreground">
                                <Info class="inline h-3 w-3 mr-1" />
                                {{ formulario.respuestas_count || 0 }} / {{ formulario.limite_respuestas }} respuestas
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t">
                            <Button 
                                variant="ghost" 
                                class="w-full"
                                :disabled="formulario.usuario_ha_respondido"
                            >
                                {{ formulario.usuario_ha_respondido ? 'Ya respondido' : 
                                   formulario.tiene_borrador ? 'Continuar' : 'Responder' }}
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
            
            <!-- Estado vacío -->
            <Card v-else>
                <CardContent class="flex flex-col items-center justify-center py-12">
                    <FileText class="h-12 w-12 text-muted-foreground mb-4" />
                    <h3 class="text-lg font-semibold mb-2">No hay formularios disponibles</h3>
                    <p class="text-muted-foreground text-center max-w-md">
                        {{ search || (categoriaFilter && categoriaFilter !== 'all') ? 
                           'No se encontraron formularios con los filtros aplicados.' :
                           'No hay formularios disponibles en este momento. Vuelve más tarde.' }}
                    </p>
                    <Button 
                        v-if="search || (categoriaFilter && categoriaFilter !== 'all')"
                        @click="limpiarFiltros" 
                        class="mt-4"
                        variant="outline"
                    >
                        Limpiar filtros
                    </Button>
                </CardContent>
            </Card>
            
            <!-- Paginación -->
            <div v-if="formularios.last_page > 1" class="mt-6 flex justify-center gap-2">
                <Button
                    v-for="page in formularios.last_page"
                    :key="page"
                    :variant="page === formularios.current_page ? 'default' : 'outline'"
                    size="sm"
                    @click="router.get(route('formularios.index'), { ...filters, page }, { preserveState: true })"
                >
                    {{ page }}
                </Button>
            </div>
        </div>
    </AppLayout>
</template>