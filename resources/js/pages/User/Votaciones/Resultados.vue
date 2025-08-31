<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import BarChart from '@/components/BarChart.vue';
import { type BreadcrumbItemType } from '@/types';
import UserLayout from "@/layouts/UserLayout.vue";
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, BarChart3, Globe, Shield, Calendar, Users, ExternalLink, ChevronDown, ChevronRight, Loader2 } from 'lucide-vue-next';
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

// Siempre usar breadcrumb de usuario ya que este componente está en User/Votaciones
// y es accedido desde rutas de usuario (/miembro/votaciones/*/resultados)
const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Dashboard', href: '/miembro/dashboard' },
    { title: 'Mis Votaciones', href: '/miembro/votaciones' },
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

// Estados para manejo de expansiones
const expandedTerritories = ref<Set<number>>(new Set());
const expandedOptions = ref<Map<string, Set<string>>>(new Map());
const loadingTerritoriesDetails = ref<Record<string, boolean>>({});
const territoriesDetailsData = ref<Record<string, any>>({});
const loadingOptionsDetails = ref<Record<string, boolean>>({});
const optionsDetailsData = ref<Record<string, any>>({});

// Función para volver a votaciones
const volverAVotaciones = () => {
    router.get(props.user.es_admin ? '/admin/votaciones' : '/miembro/votaciones');
};

// Función para ir a verificar token
const irAVerificarToken = (token: string) => {
    const url = `/verificar-token/${token}`;
    window.open(url, '_blank');
};

// Cargar datos consolidados
const cargarDatosConsolidado = async () => {
    loadingConsolidado.value = true;
    // Limpiar opciones expandidas al recargar
    expandedOptions.value.clear();
    optionsDetailsData.value = {};
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
const onAgrupacionChange = async () => {
    // Limpiar territorios expandidos cuando cambia la agrupación (Tab 2)
    expandedTerritories.value.clear();
    territoriesDetailsData.value = {};
    
    // Para Tab 1 (Consolidado): Recargar distribución geográfica de opciones expandidas
    const opcionesExpandidasBackup = new Map(expandedOptions.value);
    optionsDetailsData.value = {};
    
    // Recargar datos de opciones que estaban expandidas con la nueva agrupación
    for (const [preguntaId, opciones] of opcionesExpandidasBackup) {
        for (const opcion of opciones) {
            await loadOptionDetails(preguntaId, opcion);
        }
    }
    
    // Cargar datos de la tab de territorio
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

// Función para togglear expansión de territorio
const toggleTerritoryExpansion = async (grupoId: number, nombreGrupo: string) => {
    const key = `${agrupacionTerritorio.value}_${grupoId}`;
    
    if (expandedTerritories.value.has(grupoId)) {
        expandedTerritories.value.delete(grupoId);
    } else {
        expandedTerritories.value.add(grupoId);
        
        // Si no hay datos cargados, cargarlos
        if (!territoriesDetailsData.value[key]) {
            await loadTerritoryDetails(grupoId, nombreGrupo);
        }
    }
};

// Cargar detalles de ranking por territorio
const loadTerritoryDetails = async (grupoId: number, nombreGrupo: string) => {
    const key = `${agrupacionTerritorio.value}_${grupoId}`;
    loadingTerritoriesDetails.value[key] = true;
    
    try {
        const response = await fetch(`/api/votaciones/${props.votacion.id}/resultados/ranking-territorio?agrupacion=${agrupacionTerritorio.value}&grupo_id=${grupoId}`);
        const data = await response.json();
        territoriesDetailsData.value[key] = data;
    } catch (error) {
        console.error('Error cargando detalles del territorio:', error);
    } finally {
        loadingTerritoriesDetails.value[key] = false;
    }
};

// Función para togglear expansión de opción en consolidado
const toggleOptionExpansion = async (preguntaId: string, opcion: string) => {
    if (!expandedOptions.value.has(preguntaId)) {
        expandedOptions.value.set(preguntaId, new Set());
    }
    
    const preguntaOptions = expandedOptions.value.get(preguntaId)!;
    const key = `${preguntaId}_${opcion}`;
    
    if (preguntaOptions.has(opcion)) {
        preguntaOptions.delete(opcion);
    } else {
        preguntaOptions.add(opcion);
        
        // Si no hay datos cargados, cargarlos
        if (!optionsDetailsData.value[key]) {
            await loadOptionDetails(preguntaId, opcion);
        }
    }
};

// Cargar distribución geográfica por opción
const loadOptionDetails = async (preguntaId: string, opcion: string) => {
    const key = `${preguntaId}_${opcion}`;
    loadingOptionsDetails.value[key] = true;
    
    try {
        const response = await fetch(`/api/votaciones/${props.votacion.id}/resultados/distribucion-opcion?pregunta_id=${preguntaId}&opcion=${encodeURIComponent(opcion)}&agrupacion=${agrupacionTerritorio.value}`);
        const data = await response.json();
        optionsDetailsData.value[key] = data;
    } catch (error) {
        console.error('Error cargando distribución geográfica:', error);
    } finally {
        loadingOptionsDetails.value[key] = false;
    }
};

// Verificar si una opción está expandida
const isOptionExpanded = (preguntaId: string, opcion: string): boolean => {
    return expandedOptions.value.get(preguntaId)?.has(opcion) || false;
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

    <UserLayout :breadcrumbs="breadcrumbs">
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

            <!-- Filtro de agrupación territorial -->
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium">Filtrar resultados:</span>
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
                                        
                                        <!-- Tabla de resultados con expansión -->
                                        <div class="space-y-2">
                                            <Collapsible 
                                                v-for="respuesta in pregunta.respuestas" 
                                                :key="respuesta.opcion"
                                                :open="isOptionExpanded(pregunta.id, respuesta.opcion)"
                                            >
                                                <CollapsibleTrigger 
                                                    @click="toggleOptionExpansion(pregunta.id, respuesta.opcion)"
                                                    class="w-full"
                                                >
                                                    <div class="flex items-center justify-between p-3 bg-muted/30 rounded-lg hover:bg-muted/40 transition-colors cursor-pointer">
                                                        <div class="flex items-center gap-2">
                                                            <ChevronRight 
                                                                v-if="!isOptionExpanded(pregunta.id, respuesta.opcion)"
                                                                class="h-4 w-4 text-muted-foreground"
                                                            />
                                                            <ChevronDown 
                                                                v-else
                                                                class="h-4 w-4 text-muted-foreground"
                                                            />
                                                            <span class="font-medium">{{ respuesta.opcion }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-4">
                                                            <span class="text-sm text-muted-foreground">
                                                                {{ respuesta.cantidad }} votos
                                                            </span>
                                                            <Badge variant="secondary">{{ respuesta.porcentaje }}%</Badge>
                                                        </div>
                                                    </div>
                                                </CollapsibleTrigger>
                                                
                                                <CollapsibleContent>
                                                    <div class="mt-2 p-4 bg-muted/20 rounded-lg border">
                                                        <div v-if="loadingOptionsDetails[`${pregunta.id}_${respuesta.opcion}`]" class="flex items-center justify-center py-4">
                                                            <Loader2 class="h-5 w-5 animate-spin text-muted-foreground mr-2" />
                                                            <span class="text-sm text-muted-foreground">Cargando distribución geográfica...</span>
                                                        </div>
                                                        <div v-else-if="optionsDetailsData[`${pregunta.id}_${respuesta.opcion}`]" class="space-y-4">
                                                            <h5 class="font-medium text-sm mb-3">
                                                                Distribución geográfica de votos para "{{ respuesta.opcion }}"
                                                            </h5>
                                                            
                                                            <!-- Top territorios/departamentos/municipios -->
                                                            <div class="space-y-2">
                                                                <div 
                                                                    v-for="(territorio, index) in optionsDetailsData[`${pregunta.id}_${respuesta.opcion}`].distribucion.slice(0, 10)" 
                                                                    :key="territorio.grupo_id"
                                                                    class="flex items-center justify-between p-2 bg-background rounded"
                                                                >
                                                                    <div class="flex items-center gap-2">
                                                                        <Badge variant="outline" class="w-8 h-6 flex items-center justify-center">
                                                                            {{ index + 1 }}
                                                                        </Badge>
                                                                        <span class="text-sm">
                                                                            {{ territorio.nombre_grupo }}
                                                                            <span v-if="territorio.departamento_nombre" class="text-muted-foreground">
                                                                                ({{ territorio.departamento_nombre }})
                                                                            </span>
                                                                        </span>
                                                                    </div>
                                                                    <div class="flex items-center gap-3">
                                                                        <span class="text-sm font-medium">{{ territorio.total_votos }} votos</span>
                                                                        <Badge variant="outline">{{ territorio.porcentaje }}%</Badge>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div v-if="optionsDetailsData[`${pregunta.id}_${respuesta.opcion}`].distribucion.length > 10" class="text-xs text-muted-foreground text-center">
                                                                Mostrando top 10 de {{ optionsDetailsData[`${pregunta.id}_${respuesta.opcion}`].distribucion.length }} ubicaciones
                                                            </div>
                                                            
                                                            <div v-if="optionsDetailsData[`${pregunta.id}_${respuesta.opcion}`].distribucion.length === 0" class="text-center py-2 text-sm text-muted-foreground">
                                                                No hay datos de distribución geográfica disponibles.
                                                            </div>
                                                        </div>
                                                    </div>
                                                </CollapsibleContent>
                                            </Collapsible>
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
                            <CardTitle>Resultados por {{ agrupacionTerritorio === 'territorio' ? 'Territorio' : agrupacionTerritorio === 'departamento' ? 'Departamento' : 'Municipio' }}</CardTitle>
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
                                                <TableHead class="w-12"></TableHead>
                                                <TableHead>{{ agrupacionTerritorio === 'territorio' ? 'Territorio' : agrupacionTerritorio === 'departamento' ? 'Departamento' : 'Municipio' }}</TableHead>
                                                <TableHead v-if="agrupacionTerritorio === 'municipio'">Departamento</TableHead>
                                                <TableHead class="text-right">Votos</TableHead>
                                                <TableHead class="text-right">Porcentaje</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <template v-for="resultado in datosTerritorio.resultados" :key="resultado.grupo_id">
                                                <TableRow 
                                                    class="cursor-pointer hover:bg-muted/50 transition-colors"
                                                    @click="toggleTerritoryExpansion(
                                                        resultado.grupo_id, 
                                                        agrupacionTerritorio === 'territorio' 
                                                            ? (resultado.territorio_nombre || 'Sin especificar')
                                                            : agrupacionTerritorio === 'departamento'
                                                                ? (resultado.departamento_nombre || 'Sin especificar')
                                                                : (resultado.municipio_nombre || 'Sin especificar')
                                                    )"
                                                >
                                                    <TableCell>
                                                        <ChevronRight 
                                                            v-if="!expandedTerritories.has(resultado.grupo_id)"
                                                            class="h-4 w-4 text-muted-foreground"
                                                        />
                                                        <ChevronDown 
                                                            v-else
                                                            class="h-4 w-4 text-muted-foreground"
                                                        />
                                                    </TableCell>
                                                    <TableCell class="font-medium">
                                                        {{ 
                                                            agrupacionTerritorio === 'territorio' 
                                                                ? (resultado.territorio_nombre || 'Sin especificar')
                                                                : agrupacionTerritorio === 'departamento'
                                                                    ? (resultado.departamento_nombre || 'Sin especificar')
                                                                    : (resultado.municipio_nombre || 'Sin especificar')
                                                        }}
                                                    </TableCell>
                                                    <TableCell v-if="agrupacionTerritorio === 'municipio'">{{ resultado.departamento_nombre || 'N/A' }}</TableCell>
                                                    <TableCell class="text-right">{{ resultado.total_votos }}</TableCell>
                                                    <TableCell class="text-right">
                                                        <Badge variant="secondary">{{ resultado.porcentaje }}%</Badge>
                                                    </TableCell>
                                                </TableRow>
                                                
                                                <!-- Fila expandible con detalles -->
                                                <TableRow v-if="expandedTerritories.has(resultado.grupo_id)">
                                                    <TableCell :colspan="agrupacionTerritorio === 'municipio' ? 5 : 4" class="p-0">
                                                        <div class="bg-muted/20 p-6 border-t">
                                                            <div v-if="loadingTerritoriesDetails[`${agrupacionTerritorio}_${resultado.grupo_id}`]" class="flex items-center justify-center py-8">
                                                                <Loader2 class="h-6 w-6 animate-spin text-muted-foreground mr-2" />
                                                                <span class="text-muted-foreground">Cargando ranking de votación...</span>
                                                            </div>
                                                            <div v-else-if="territoriesDetailsData[`${agrupacionTerritorio}_${resultado.grupo_id}`]" class="space-y-6">
                                                                <h4 class="font-semibold text-lg mb-4">
                                                                    Ranking de votación desde {{ 
                                                                        agrupacionTerritorio === 'territorio' 
                                                                            ? (resultado.territorio_nombre || 'Sin especificar')
                                                                            : agrupacionTerritorio === 'departamento'
                                                                                ? (resultado.departamento_nombre || 'Sin especificar')
                                                                                : (resultado.municipio_nombre || 'Sin especificar')
                                                                    }}
                                                                </h4>
                                                                
                                                                <div v-for="pregunta in territoriesDetailsData[`${agrupacionTerritorio}_${resultado.grupo_id}`].preguntas" :key="pregunta.id" class="space-y-3">
                                                                    <div>
                                                                        <h5 class="font-medium">{{ pregunta.titulo }}</h5>
                                                                        <p class="text-sm text-muted-foreground">
                                                                            Total de respuestas: {{ pregunta.respuestas.reduce((sum, r) => sum + r.cantidad, 0) }}
                                                                        </p>
                                                                    </div>
                                                                    
                                                                    <!-- Gráfico de barras para esta pregunta -->
                                                                    <div v-if="pregunta.respuestas.length > 0" class="bg-card border rounded-lg p-4">
                                                                        <BarChart 
                                                                            :data="getChartDataFromResponses(pregunta.respuestas)"
                                                                            :title="`Distribución: ${pregunta.titulo}`"
                                                                            :height="200"
                                                                        />
                                                                    </div>
                                                                    
                                                                    <!-- Tabla de detalles -->
                                                                    <div class="space-y-2">
                                                                        <div 
                                                                            v-for="respuesta in pregunta.respuestas" 
                                                                            :key="respuesta.opcion"
                                                                            class="flex items-center justify-between p-2 bg-background rounded"
                                                                        >
                                                                            <span class="text-sm">{{ respuesta.opcion }}</span>
                                                                            <div class="flex items-center gap-4">
                                                                                <span class="text-sm font-medium">{{ respuesta.cantidad }} votos</span>
                                                                                <Badge variant="outline">{{ respuesta.porcentaje }}%</Badge>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div v-if="territoriesDetailsData[`${agrupacionTerritorio}_${resultado.grupo_id}`].preguntas.length === 0" class="text-center py-4 text-muted-foreground">
                                                                    No hay datos de votación disponibles para este {{ agrupacionTerritorio }}.
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            </template>
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
    </UserLayout>
</template>