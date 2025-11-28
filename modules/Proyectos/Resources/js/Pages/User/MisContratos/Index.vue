<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import { type BreadcrumbItemType } from '@/types';
import UserLayout from '@modules/Core/Resources/js/layouts/UserLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@modules/Core/Resources/js/components/ui/tabs';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import ContratoCard from '@modules/Proyectos/Resources/js/components/ContratoCard.vue';
import {
    Search,
    Calendar,
    AlertTriangle,
    CheckCircle,
    XCircle,
    Clock,
    FileText,
    Filter,
    TrendingUp,
    DollarSign
} from 'lucide-vue-next';
import type { Contrato } from '@modules/Proyectos/Resources/js/types/contratos';

// Props
const props = defineProps<{
    contratos: {
        data: Contrato[];
        links: any;
        meta: any;
    };
    filtros: {
        search?: string;
        estado?: string;
        proyecto_id?: number;
    };
    estadisticas: {
        total: number;
        activos: number;
        finalizados: number;
        vencidos: number;
        proximos_vencer: number;
        monto_total: number;
    };
    proyectos: Array<{
        id: number;
        nombre: string;
    }>;
    authPermissions: string[];
}>();

// Breadcrumbs para navegación
const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Dashboard', href: '/miembro/dashboard' },
    { title: 'Mis Contratos', href: '/miembro/mis-contratos' },
];

// Estado
const searchTerm = ref(props.filtros.search || '');
const estadoSeleccionado = ref(props.filtros.estado || 'todos');
const proyectoSeleccionado = ref(props.filtros.proyecto_id || 0);

// Computed
const canViewContracts = computed(() => 
    props.authPermissions.includes('contratos.view_own')
);

const contratosPorEstado = computed(() => {
    const grupos = {
        todos: props.contratos.data,
        activos: props.contratos.data.filter(c => c.estado === 'activo'),
        finalizados: props.contratos.data.filter(c => c.estado === 'finalizado'),
        borradores: props.contratos.data.filter(c => c.estado === 'borrador'),
        cancelados: props.contratos.data.filter(c => c.estado === 'cancelado')
    };
    return grupos;
});

// Métodos
const buscar = () => {
    router.get(route('user.mis-contratos.index'), {
        search: searchTerm.value,
        estado: estadoSeleccionado.value !== 'todos' ? estadoSeleccionado.value : undefined,
        proyecto_id: proyectoSeleccionado.value || undefined
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const cambiarEstado = (nuevoEstado: string) => {
    estadoSeleccionado.value = nuevoEstado;
    buscar();
};

const cambiarProyecto = (proyectoId: number) => {
    proyectoSeleccionado.value = proyectoId;
    buscar();
};

</script>

<template>
    <UserLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4">
            <div>
                <h2 class="text-3xl font-bold tracking-tight">Mis Contratos</h2>
                <p class="text-muted-foreground mt-2">
                    Gestiona y consulta los contratos de tus proyectos
                </p>
            </div>

            <!-- Búsqueda y filtros -->
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1 relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input
                        v-model="searchTerm"
                        placeholder="Buscar contratos..."
                        class="pl-10"
                        @keyup.enter="buscar"
                    />
                </div>
                
                <select
                    v-model="proyectoSeleccionado"
                    @change="buscar"
                    class="px-3 py-2 border border-input rounded-md bg-background"
                >
                    <option :value="0">Todos los proyectos</option>
                    <option
                        v-for="proyecto in proyectos"
                        :key="proyecto.id"
                        :value="proyecto.id"
                    >
                        {{ proyecto.nombre }}
                    </option>
                </select>

                <Button @click="buscar">
                    <Filter class="w-4 h-4 mr-2" />
                    Filtrar
                </Button>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-6">
            <Card>
                <CardHeader class="pb-3">
                    <CardDescription>Total</CardDescription>
                    <CardTitle class="text-2xl">{{ estadisticas.total }}</CardTitle>
                </CardHeader>
            </Card>

            <Card>
                <CardHeader class="pb-3">
                    <CardDescription>Activos</CardDescription>
                    <CardTitle class="text-2xl text-green-600">
                        {{ estadisticas.activos }}
                    </CardTitle>
                </CardHeader>
            </Card>

            <Card>
                <CardHeader class="pb-3">
                    <CardDescription>Finalizados</CardDescription>
                    <CardTitle class="text-2xl text-blue-600">
                        {{ estadisticas.finalizados }}
                    </CardTitle>
                </CardHeader>
            </Card>

            <Card>
                <CardHeader class="pb-3">
                    <CardDescription>Vencidos</CardDescription>
                    <CardTitle class="text-2xl text-red-600">
                        {{ estadisticas.vencidos }}
                    </CardTitle>
                </CardHeader>
            </Card>

            <Card>
                <CardHeader class="pb-3">
                    <CardDescription>Por Vencer</CardDescription>
                    <CardTitle class="text-2xl text-orange-600">
                        {{ estadisticas.proximos_vencer }}
                    </CardTitle>
                </CardHeader>
            </Card>

            <Card>
                <CardHeader class="pb-3">
                    <CardDescription>Monto Total</CardDescription>
                    <CardTitle class="text-xl">
                        ${{ estadisticas.monto_total?.toLocaleString() || 0 }}
                    </CardTitle>
                </CardHeader>
            </Card>
        </div>

        <!-- Alertas -->
        <Alert v-if="estadisticas.vencidos > 0" class="border-red-200 bg-red-50">
            <AlertTriangle class="h-4 w-4 text-red-600" />
            <AlertDescription class="text-red-800">
                Tienes {{ estadisticas.vencidos }} contrato(s) vencido(s) que requieren atención.
                <Link
                    :href="route('user.mis-contratos.vencidos')"
                    class="font-medium underline ml-2"
                >
                    Ver contratos vencidos
                </Link>
            </AlertDescription>
        </Alert>

        <Alert v-if="estadisticas.proximos_vencer > 0" class="border-orange-200 bg-orange-50">
            <Clock class="h-4 w-4 text-orange-600" />
            <AlertDescription class="text-orange-800">
                {{ estadisticas.proximos_vencer }} contrato(s) vencerán en los próximos 30 días.
                <Link
                    :href="route('user.mis-contratos.proximos-vencer')"
                    class="font-medium underline ml-2"
                >
                    Ver próximos a vencer
                </Link>
            </AlertDescription>
        </Alert>

        <!-- Tabs de estados -->
        <Tabs :default-value="estadoSeleccionado" @update:model-value="cambiarEstado">
            <TabsList class="grid w-full grid-cols-5">
                <TabsTrigger value="todos">
                    Todos ({{ contratosPorEstado.todos.length }})
                </TabsTrigger>
                <TabsTrigger value="activos">
                    Activos ({{ contratosPorEstado.activos.length }})
                </TabsTrigger>
                <TabsTrigger value="finalizados">
                    Finalizados ({{ contratosPorEstado.finalizados.length }})
                </TabsTrigger>
                <TabsTrigger value="borradores">
                    Borradores ({{ contratosPorEstado.borradores.length }})
                </TabsTrigger>
                <TabsTrigger value="cancelados">
                    Cancelados ({{ contratosPorEstado.cancelados.length }})
                </TabsTrigger>
            </TabsList>

            <TabsContent value="todos" class="mt-6">
                <div v-if="contratos.data.length === 0" class="text-center py-12">
                    <FileText class="w-12 h-12 mx-auto text-muted-foreground mb-4" />
                    <p class="text-muted-foreground">
                        No tienes contratos asignados
                    </p>
                </div>
                <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <ContratoCard
                        v-for="contrato in contratos.data"
                        :key="contrato.id"
                        :contrato="contrato"
                        view-mode="user"
                    />
                </div>
            </TabsContent>

            <TabsContent value="activos" class="mt-6">
                <div v-if="contratosPorEstado.activos.length === 0" class="text-center py-12">
                    <CheckCircle class="w-12 h-12 mx-auto text-muted-foreground mb-4" />
                    <p class="text-muted-foreground">
                        No hay contratos activos
                    </p>
                </div>
                <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <ContratoCard
                        v-for="contrato in contratosPorEstado.activos"
                        :key="contrato.id"
                        :contrato="contrato"
                        view-mode="user"
                    />
                </div>
            </TabsContent>

            <TabsContent value="finalizados" class="mt-6">
                <div v-if="contratosPorEstado.finalizados.length === 0" class="text-center py-12">
                    <CheckCircle class="w-12 h-12 mx-auto text-muted-foreground mb-4" />
                    <p class="text-muted-foreground">
                        No hay contratos finalizados
                    </p>
                </div>
                <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <ContratoCard
                        v-for="contrato in contratosPorEstado.finalizados"
                        :key="contrato.id"
                        :contrato="contrato"
                        view-mode="user"
                    />
                </div>
            </TabsContent>

            <TabsContent value="borradores" class="mt-6">
                <div v-if="contratosPorEstado.borradores.length === 0" class="text-center py-12">
                    <FileText class="w-12 h-12 mx-auto text-muted-foreground mb-4" />
                    <p class="text-muted-foreground">
                        No hay contratos en borrador
                    </p>
                </div>
                <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <ContratoCard
                        v-for="contrato in contratosPorEstado.borradores"
                        :key="contrato.id"
                        :contrato="contrato"
                        view-mode="user"
                    />
                </div>
            </TabsContent>

            <TabsContent value="cancelados" class="mt-6">
                <div v-if="contratosPorEstado.cancelados.length === 0" class="text-center py-12">
                    <XCircle class="w-12 h-12 mx-auto text-muted-foreground mb-4" />
                    <p class="text-muted-foreground">
                        No hay contratos cancelados
                    </p>
                </div>
                <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <ContratoCard
                        v-for="contrato in contratosPorEstado.cancelados"
                        :key="contrato.id"
                        :contrato="contrato"
                        view-mode="user"
                    />
                </div>
            </TabsContent>
        </Tabs>

        <!-- Paginación -->
        <div v-if="contratos.links && contratos.links.length > 3" class="flex justify-center mt-6">
            <nav class="flex gap-2">
                <Link
                    v-for="link in contratos.links"
                    :key="link.label"
                    :href="link.url"
                    :class="[
                        'px-3 py-2 rounded-md text-sm',
                        link.active
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted hover:bg-muted/80'
                    ]"
                    v-html="link.label"
                    preserve-state
                    preserve-scroll
                />
            </nav>
        </div>
        </div>
    </UserLayout>
</template>