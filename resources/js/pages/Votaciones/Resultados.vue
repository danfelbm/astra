<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import BarChart from '@/components/BarChart.vue';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, BarChart3, Globe, Shield, Calendar, Users, ExternalLink } from 'lucide-vue-next';
import { ref, onMounted } from 'vue';

interface Categoria {
    id: number;
    nombre: string;
    descripcion?: string;
    activa: boolean;
}

interface Votacion {
    id: number;
    titulo: string;
    descripcion?: string;
    categoria: Categoria;
    formulario_config: any[];
    fecha_inicio: string;
    fecha_fin: string;
    fecha_publicacion_resultados?: string;
    total_votos: number;
}

interface User {
    es_admin: boolean;
}

interface Props {
    votacion: Votacion;
    user: User;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = props.user.es_admin 
    ? [
        { title: 'Admin', href: '/admin/dashboard' },
        { title: 'Votaciones', href: '/admin/votaciones' },
        { title: 'Resultados', href: '#' },
    ]
    : [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Mis Votaciones', href: '/votaciones' },
        { title: 'Resultados', href: '#' },
    ];

// Estados de carga
const loadingConsolidado = ref(false);
const loadingTerritorio = ref(false);
const loadingTokens = ref(false);

// Datos de las pestañas
const datosConsolidado = ref<any>(null);
const datosTerritorio = ref<any>(null);
const datosTokens = ref<any>(null);

// Filtros
const agrupacionTerritorio = ref('territorio');
const busquedaToken = ref('');
const paginaTokens = ref(1);

// Función para volver a votaciones
const volverAVotaciones = () => {
    router.get(props.user.es_admin ? '/admin/votaciones' : '/votaciones');
};

// Función para ir a verificar token
const irAVerificarToken = (token: string) => {
    const url = `/verificar-token/${token}`;
    window.open(url, '_blank');
};

// Cargar datos consolidados
const cargarDatosConsolidado = async () => {
    loadingConsolidado.value = true;
    try {
        const response = await fetch(`/api/votaciones/${props.votacion.id}/resultados/consolidado`);
        const data = await response.json();
        datosConsolidado.value = data;
    } catch (error) {
        console.error('Error cargando datos consolidados:', error);
    } finally {
        loadingConsolidado.value = false;
    }
};

// Cargar datos por territorio
const cargarDatosTerritorio = async () => {
    loadingTerritorio.value = true;
    try {
        const response = await fetch(`/api/votaciones/${props.votacion.id}/resultados/territorio?agrupacion=${agrupacionTerritorio.value}`);
        const data = await response.json();
        datosTerritorio.value = data;
    } catch (error) {
        console.error('Error cargando datos de territorio:', error);
    } finally {
        loadingTerritorio.value = false;
    }
};

// Cargar tokens
const cargarTokens = async () => {
    loadingTokens.value = true;
    try {
        let url = `/api/votaciones/${props.votacion.id}/resultados/tokens?page=${paginaTokens.value}`;
        if (busquedaToken.value) {
            url += `&busqueda=${encodeURIComponent(busquedaToken.value)}`;
        }
        const response = await fetch(url);
        const data = await response.json();
        datosTokens.value = data;
    } catch (error) {
        console.error('Error cargando tokens:', error);
    } finally {
        loadingTokens.value = false;
    }
};

// Formatear fechas
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Cuando cambia la agrupación de territorio
const onAgrupacionChange = () => {
    cargarDatosTerritorio();
};

// Buscar tokens
const buscarTokens = () => {
    paginaTokens.value = 1;
    cargarTokens();
};

// Función para generar datos de gráfico desde respuestas
const getChartDataFromResponses = (respuestas: any[]) => {
    return {
        labels: respuestas.map(r => r.opcion),
        datasets: [{
            label: 'Votos',
            data: respuestas.map(r => r.cantidad)
        }]
    };
};

// Cargar datos iniciales
onMounted(() => {
    cargarDatosConsolidado();
    cargarDatosTerritorio();
    cargarTokens();
});
</script>

<template>
    <Head :title="`Resultados: ${votacion.titulo}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Resultados de Votación</h1>
                    <p class="text-muted-foreground">
                        {{ votacion.titulo }}
                    </p>
                </div>
                <Button @click="volverAVotaciones" variant="outline">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver
                </Button>
            </div>

            <!-- Info de la votación -->
            <Card>
                <CardContent class="pt-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="flex items-center gap-2">
                            <Badge variant="outline">
                                {{ votacion.categoria.nombre }}
                            </Badge>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" />
                            Finalizó: {{ formatDate(votacion.fecha_fin) }}
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <Users class="h-4 w-4 text-blue-600" />
                            <span class="font-medium">{{ votacion.total_votos }} votos</span>
                        </div>
                        <div v-if="votacion.fecha_publicacion_resultados" class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Globe class="h-4 w-4" />
                            Publicado: {{ formatDate(votacion.fecha_publicacion_resultados) }}
                        </div>
                    </div>
                    <p v-if="votacion.descripcion" class="text-sm text-muted-foreground mt-3">
                        {{ votacion.descripcion }}
                    </p>
                </CardContent>
            </Card>

            <!-- Pestañas de resultados -->
            <Tabs default-value="consolidado" class="flex-1">
                <TabsList class="grid w-full grid-cols-3">
                    <TabsTrigger value="consolidado" @click="cargarDatosConsolidado">
                        <BarChart3 class="mr-2 h-4 w-4" />
                        Consolidado
                    </TabsTrigger>
                    <TabsTrigger value="territorio" @click="cargarDatosTerritorio">
                        <Globe class="mr-2 h-4 w-4" />
                        Por Territorio
                    </TabsTrigger>
                    <TabsTrigger value="tokens" @click="cargarTokens">
                        <Shield class="mr-2 h-4 w-4" />
                        Tokens Públicos
                    </TabsTrigger>
                </TabsList>

                <!-- Tab 1: Consolidado Total -->
                <TabsContent value="consolidado" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Resultados Consolidados por Pregunta</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="loadingConsolidado" class="text-center py-8">
                                Cargando resultados...
                            </div>
                            <div v-else-if="datosConsolidado" class="space-y-6">
                                <div v-for="pregunta in datosConsolidado.resultados" :key="pregunta.id" class="space-y-4">
                                    <div>
                                        <h3 class="text-lg font-semibold">{{ pregunta.titulo }}</h3>
                                        <p class="text-sm text-muted-foreground">
                                            {{ pregunta.total_respuestas }} respuestas de {{ datosConsolidado.total_votos }} votos totales
                                        </p>
                                    </div>
                                    
                                    <!-- Para preguntas de opciones (radio, select, checkbox) -->
                                    <div v-if="pregunta.tipo !== 'text' && pregunta.tipo !== 'textarea'" class="space-y-4">
                                        <!-- Gráfico de barras -->
                                        <div class="bg-card border rounded-lg p-4">
                                            <BarChart 
                                                :data="getChartDataFromResponses(pregunta.respuestas)"
                                                :title="`Distribución de respuestas: ${pregunta.titulo}`"
                                                :height="300"
                                            />
                                        </div>
                                        
                                        <!-- Tabla de resultados -->
                                        <div class="space-y-2">
                                            <div 
                                                v-for="respuesta in pregunta.respuestas" 
                                                :key="respuesta.opcion"
                                                class="flex items-center justify-between p-3 bg-muted/30 rounded-lg"
                                            >
                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <span class="font-medium">{{ respuesta.opcion }}</span>
                                                        <span class="text-sm text-muted-foreground">
                                                            {{ respuesta.cantidad }} ({{ respuesta.porcentaje }}%)
                                                        </span>
                                                    </div>
                                                    <div class="w-full bg-muted rounded-full h-2">
                                                        <div 
                                                            class="bg-primary h-2 rounded-full transition-all duration-300"
                                                            :style="{ width: `${respuesta.porcentaje}%` }"
                                                        ></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Para preguntas abiertas -->
                                    <div v-else class="space-y-2">
                                        <div class="max-h-64 overflow-y-auto space-y-2">
                                            <div 
                                                v-for="(respuesta, index) in pregunta.respuestas.slice(0, 10)" 
                                                :key="index"
                                                class="p-2 bg-muted/30 rounded text-sm"
                                            >
                                                {{ respuesta }}
                                            </div>
                                        </div>
                                        <p v-if="pregunta.respuestas.length > 10" class="text-xs text-muted-foreground">
                                            Mostrando 10 de {{ pregunta.respuestas.length }} respuestas
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Tab 2: Por Territorio -->
                <TabsContent value="territorio" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle>Resultados por Territorio</CardTitle>
                                <Select v-model="agrupacionTerritorio" @update:model-value="onAgrupacionChange">
                                    <SelectTrigger class="w-48">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="territorio">Por Territorio</SelectItem>
                                        <SelectItem value="departamento">Por Departamento</SelectItem>
                                        <SelectItem value="municipio">Por Municipio</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div v-if="loadingTerritorio" class="text-center py-8">
                                Cargando datos territoriales...
                            </div>
                            <div v-else-if="datosTerritorio" class="space-y-4">
                                <div class="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>{{ agrupacionTerritorio === 'territorio' ? 'Territorio' : agrupacionTerritorio === 'departamento' ? 'Departamento' : 'Municipio' }} ID</TableHead>
                                                <TableHead v-if="agrupacionTerritorio === 'municipio'">Departamento ID</TableHead>
                                                <TableHead class="text-right">Votos</TableHead>
                                                <TableHead class="text-right">Porcentaje</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="resultado in datosTerritorio.resultados" :key="resultado.grupo_id">
                                                <TableCell class="font-medium">{{ resultado.grupo_id || 'Sin especificar' }}</TableCell>
                                                <TableCell v-if="agrupacionTerritorio === 'municipio'">{{ resultado.departamento_id || 'N/A' }}</TableCell>
                                                <TableCell class="text-right">{{ resultado.total_votos }}</TableCell>
                                                <TableCell class="text-right">
                                                    <Badge variant="secondary">{{ resultado.porcentaje }}%</Badge>
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    Total de votos analizados: {{ datosTerritorio.total_votos }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Tab 3: Tokens Públicos -->
                <TabsContent value="tokens" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle>Tokens de Verificación Pública</CardTitle>
                                <div class="flex gap-2">
                                    <Input
                                        v-model="busquedaToken"
                                        placeholder="Buscar token..."
                                        class="w-64"
                                        @keyup.enter="buscarTokens"
                                    />
                                    <Button @click="buscarTokens" variant="outline">
                                        Buscar
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div v-if="loadingTokens" class="text-center py-8">
                                Cargando tokens...
                            </div>
                            <div v-else-if="datosTokens" class="space-y-4">
                                <div class="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Token</TableHead>
                                                <TableHead>Fecha de Voto</TableHead>
                                                <TableHead class="text-right">Acción</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="token in datosTokens.tokens" :key="token.id">
                                                <TableCell class="font-mono text-xs">
                                                    {{ token.token_unico.substring(0, 40) }}...
                                                </TableCell>
                                                <TableCell>
                                                    {{ formatDate(token.created_at) }}
                                                </TableCell>
                                                <TableCell class="text-right">
                                                    <Button 
                                                        @click="irAVerificarToken(token.token_unico)" 
                                                        size="sm" 
                                                        variant="outline"
                                                    >
                                                        <ExternalLink class="mr-2 h-3 w-3" />
                                                        Verificar
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>

                                <!-- Paginación simple -->
                                <div v-if="datosTokens.pagination && datosTokens.pagination.last_page > 1" class="flex items-center justify-between">
                                    <p class="text-sm text-muted-foreground">
                                        Página {{ datosTokens.pagination.current_page }} de {{ datosTokens.pagination.last_page }}
                                        ({{ datosTokens.pagination.total }} tokens total)
                                    </p>
                                    <div class="flex gap-2">
                                        <Button 
                                            @click="paginaTokens--; cargarTokens()" 
                                            :disabled="datosTokens.pagination.current_page <= 1"
                                            size="sm"
                                            variant="outline"
                                        >
                                            Anterior
                                        </Button>
                                        <Button 
                                            @click="paginaTokens++; cargarTokens()" 
                                            :disabled="datosTokens.pagination.current_page >= datosTokens.pagination.last_page"
                                            size="sm"
                                            variant="outline"
                                        >
                                            Siguiente
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>