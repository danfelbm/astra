<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import UserLayout from '@modules/Core/Resources/js/layouts/UserLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@modules/Core/Resources/js/components/ui/tabs';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@modules/Core/Resources/js/components/ui/select';
import { Progress } from '@modules/Core/Resources/js/components/ui/progress';
import { Calendar, Clock, Target, Users, CheckCircle, XCircle, AlertCircle, ArrowRight } from 'lucide-vue-next';
import type { Hito } from '@modules/Proyectos/Resources/js/types/hitos';
import HitoCard from '@modules/Proyectos/Resources/js/components/HitoCard.vue';
import HitoTimeline from '@modules/Proyectos/Resources/js/components/HitoTimeline.vue';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import type { PageProps } from '@/types';

interface Props {
    hitos: {
        data: Hito[];
        links: any;
        meta: any;
    };
    filters: {
        search?: string;
        estado?: string;
        prioridad?: string;
        proyecto?: string;
    };
    proyectos: Array<{
        id: number;
        nombre: string;
    }>;
    estadisticas: {
        total: number;
        pendientes: number;
        en_progreso: number;
        completados: number;
        vencidos: number;
        proximos_vencer: number;
    };
}

const props = defineProps<Props>();
const page = usePage<PageProps>();

// Filtros reactivos
const search = ref(props.filters.search || '');
const estadoFilter = ref(props.filters.estado || 'todos');
const proyectoFilter = ref(props.filters.proyecto || '');

// Tab activo
const activeTab = ref('lista');

// Función para aplicar filtros
const applyFilters = () => {
    router.get(route('user.mis-hitos.index'), {
        search: search.value,
        estado: estadoFilter.value !== 'todos' ? estadoFilter.value : undefined,
        proyecto: proyectoFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Watchers para aplicar filtros automáticamente
watch([search, estadoFilter, proyectoFilter], () => {
    applyFilters();
}, { debounce: 300 });

// Función para obtener color del estado
const getEstadoColor = (estado: string) => {
    const colors: Record<string, string> = {
        pendiente: 'bg-gray-100 text-gray-800',
        en_progreso: 'bg-blue-100 text-blue-800',
        completado: 'bg-green-100 text-green-800',
        cancelado: 'bg-red-100 text-red-800',
    };
    return colors[estado] || 'bg-gray-100 text-gray-800';
};

// Función para obtener icono del estado
const getEstadoIcon = (estado: string) => {
    switch (estado) {
        case 'pendiente':
            return AlertCircle;
        case 'en_progreso':
            return Clock;
        case 'completado':
            return CheckCircle;
        case 'cancelado':
            return XCircle;
        default:
            return AlertCircle;
    }
};

// Función para ver detalle del hito
const verHito = (hito: Hito) => {
    router.visit(route('user.mis-hitos.show', hito.id));
};

// Función para formatear fecha
const formatDate = (date: string | null) => {
    if (!date) return 'No definida';
    return format(new Date(date), 'dd MMM yyyy', { locale: es });
};

// Función para calcular días restantes
const getDiasRestantes = (fechaFin: string | null) => {
    if (!fechaFin) return null;
    const dias = Math.ceil((new Date(fechaFin).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));
    if (dias < 0) return `Vencido hace ${Math.abs(dias)} días`;
    if (dias === 0) return 'Vence hoy';
    if (dias === 1) return 'Vence mañana';
    return `${dias} días restantes`;
};

// Hitos agrupados por estado para el timeline
const hitosAgrupados = computed(() => {
    return {
        pendientes: props.hitos.data.filter(h => h.estado === 'pendiente'),
        en_progreso: props.hitos.data.filter(h => h.estado === 'en_progreso'),
        completados: props.hitos.data.filter(h => h.estado === 'completado'),
    };
});
</script>

<template>
    <UserLayout>
        <Head title="Mis Hitos" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4">
                <h1 class="text-2xl font-bold tracking-tight">Mis Hitos</h1>
                <p class="text-muted-foreground">
                    Gestiona y da seguimiento a los hitos y entregables asignados a ti
                </p>
            </div>

            <!-- Estadísticas -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-6">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total</CardTitle>
                        <Target class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ estadisticas.total }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Pendientes</CardTitle>
                        <AlertCircle class="h-4 w-4 text-yellow-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ estadisticas.pendientes }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">En Progreso</CardTitle>
                        <Clock class="h-4 w-4 text-blue-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ estadisticas.en_progreso }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Completados</CardTitle>
                        <CheckCircle class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ estadisticas.completados }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Vencidos</CardTitle>
                        <XCircle class="h-4 w-4 text-red-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ estadisticas.vencidos }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Por Vencer</CardTitle>
                        <AlertCircle class="h-4 w-4 text-orange-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-orange-600">{{ estadisticas.proximos_vencer }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filtros -->
            <Card>
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-4">
                        <div>
                            <Input
                                v-model="search"
                                placeholder="Buscar hitos..."
                                class="w-full"
                            />
                        </div>

                        <Select v-model="estadoFilter">
                            <SelectTrigger>
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todos">Todos los estados</SelectItem>
                                <SelectItem value="pendiente">Pendiente</SelectItem>
                                <SelectItem value="en_progreso">En Progreso</SelectItem>
                                <SelectItem value="completado">Completado</SelectItem>
                                <SelectItem value="cancelado">Cancelado</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select v-model="proyectoFilter">
                            <SelectTrigger>
                                <SelectValue placeholder="Proyecto" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">Todos los proyectos</SelectItem>
                                <SelectItem v-for="proyecto in proyectos" :key="proyecto.id" :value="String(proyecto.id)">
                                    {{ proyecto.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Button @click="applyFilters" class="w-full md:w-auto">
                            Aplicar Filtros
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Tabs para diferentes vistas -->
            <Tabs v-model="activeTab" class="space-y-4">
                <TabsList>
                    <TabsTrigger value="lista">Vista de Lista</TabsTrigger>
                    <TabsTrigger value="tarjetas">Vista de Tarjetas</TabsTrigger>
                    <TabsTrigger value="timeline">Timeline</TabsTrigger>
                </TabsList>

                <!-- Vista de Lista -->
                <TabsContent value="lista" class="space-y-4">
                    <Card v-for="hito in hitos.data" :key="hito.id" class="hover:shadow-lg transition-shadow cursor-pointer" @click="verHito(hito)">
                        <CardHeader>
                            <div class="flex items-start justify-between">
                                <div class="space-y-1">
                                    <CardTitle class="flex items-center gap-2">
                                        {{ hito.nombre }}
                                        <Badge :class="getEstadoColor(hito.estado)">
                                            {{ hito.estado }}
                                        </Badge>
                                    </CardTitle>
                                    <CardDescription>
                                        {{ hito.proyecto?.nombre || 'Sin proyecto' }}
                                    </CardDescription>
                                </div>
                                <ArrowRight class="h-5 w-5 text-muted-foreground" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-4 md:grid-cols-4">
                                <div>
                                    <p class="text-sm text-muted-foreground">Progreso</p>
                                    <div class="flex items-center gap-2">
                                        <Progress :model-value="hito.porcentaje_completado" class="flex-1" />
                                        <span class="text-sm font-medium">{{ hito.porcentaje_completado }}%</span>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm text-muted-foreground">Fecha Inicio</p>
                                    <p class="text-sm font-medium">{{ formatDate(hito.fecha_inicio) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-muted-foreground">Fecha Fin</p>
                                    <p class="text-sm font-medium">{{ formatDate(hito.fecha_fin) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-muted-foreground">Tiempo Restante</p>
                                    <p class="text-sm font-medium" :class="{ 'text-red-600': hito.estado !== 'completado' && getDiasRestantes(hito.fecha_fin)?.includes('Vencido') }">
                                        {{ getDiasRestantes(hito.fecha_fin) || 'Sin fecha límite' }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center gap-4 text-sm text-muted-foreground">
                                <span class="flex items-center gap-1">
                                    <Users class="h-4 w-4" />
                                    {{ hito.responsable?.name || 'Sin responsable' }}
                                </span>
                                <span>
                                    {{ hito.entregables?.length || 0 }} entregables
                                </span>
                                <span>
                                    {{ hito.entregables?.filter(e => e.estado === 'completado').length || 0 }} completados
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Mensaje si no hay hitos -->
                    <Card v-if="hitos.data.length === 0">
                        <CardContent class="text-center py-8">
                            <Target class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                            <p class="text-muted-foreground">No tienes hitos asignados</p>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Vista de Tarjetas -->
                <TabsContent value="tarjetas" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <HitoCard
                            v-for="hito in hitos.data"
                            :key="hito.id"
                            :hito="hito"
                            @click="verHito(hito)"
                        />
                    </div>

                    <!-- Mensaje si no hay hitos -->
                    <Card v-if="hitos.data.length === 0">
                        <CardContent class="text-center py-8">
                            <Target class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                            <p class="text-muted-foreground">No tienes hitos asignados</p>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Vista Timeline -->
                <TabsContent value="timeline" class="space-y-4">
                    <HitoTimeline :hitos="hitos.data" />
                </TabsContent>
            </Tabs>

            <!-- Paginación -->
            <div v-if="hitos.meta && hitos.meta.last_page > 1" class="flex justify-center">
                <nav class="flex items-center space-x-2">
                    <Button
                        v-for="link in hitos.links"
                        :key="link.label"
                        :disabled="!link.url"
                        @click="link.url && router.visit(link.url)"
                        :variant="link.active ? 'default' : 'outline'"
                        v-html="link.label"
                    />
                </nav>
            </div>
        </div>
    </UserLayout>
</template>