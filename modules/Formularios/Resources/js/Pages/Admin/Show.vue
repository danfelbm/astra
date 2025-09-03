<script setup lang="ts">
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@modules/Core/Resources/js/components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@modules/Core/Resources/js/components/ui/tabs";
import { Alert, AlertDescription } from "@modules/Core/Resources/js/components/ui/alert";
import { 
    ArrowLeft, Edit, Download, ExternalLink, Users, FileText, 
    Calendar, Clock, BarChart3, Copy, Eye, Trash2 
} from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { toast } from 'vue-sonner';

// Props
interface Props {
    formulario: {
        id: number;
        titulo: string;
        descripcion?: string;
        slug: string;
        configuracion_campos: any[];
        tipo_acceso: string;
        estado: string;
        activo: boolean;
        categoria?: {
            id: number;
            nombre: string;
        };
        estadisticas: {
            total_respuestas: number;
            respuestas_hoy: number;
            respuestas_semana: number;
            respuestas_mes: number;
            usuarios_unicos: number;
            visitantes_unicos: number;
        };
        url_publica: string;
    };
    respuestas: {
        data: Array<{
            id: number;
            codigo_confirmacion: string;
            nombre: string;
            email?: string;
            documento?: string;
            es_visitante: boolean;
            respuestas: Record<string, any>;
            tiempo_llenado?: string;
            created_at: string;
            enviado_en: string;
        }>;
        links: any;
        meta: any;
    };
}

const props = defineProps<Props>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Formularios', href: '/admin/formularios' },
    { title: props.formulario.titulo, href: '#' },
];

// Estado local
const activeTab = ref('resumen');
const selectedRespuesta = ref<any>(null);
const showRespuestaModal = ref(false);

// Métodos
const handleEdit = () => {
    router.get(route('admin.formularios.edit', props.formulario.id));
};

const handleExport = () => {
    window.location.href = route('admin.formularios.exportar', props.formulario.id);
};

const copyUrlToClipboard = () => {
    navigator.clipboard.writeText(props.formulario.url_publica).then(() => {
        toast.success('URL copiada al portapapeles');
    });
};

const viewRespuesta = (respuesta: any) => {
    selectedRespuesta.value = respuesta;
    showRespuestaModal.value = true;
};

const formatDate = (date: string) => {
    return format(new Date(date), "d 'de' MMMM 'de' yyyy, HH:mm", { locale: es });
};

const getTipoAccesoLabel = (tipo: string) => {
    const labels: Record<string, string> = {
        publico: 'Público',
        autenticado: 'Autenticado',
        con_permiso: 'Con Permiso',
    };
    return labels[tipo] || tipo;
};

const getEstadoColor = (estado: string) => {
    const colors: Record<string, string> = {
        borrador: 'secondary',
        publicado: 'default',
        archivado: 'outline',
    };
    return colors[estado] || 'secondary';
};

// Computed
const porcentajeVisitantes = computed(() => {
    const total = props.formulario.estadisticas.usuarios_unicos + props.formulario.estadisticas.visitantes_unicos;
    if (total === 0) return 0;
    return Math.round((props.formulario.estadisticas.visitantes_unicos / total) * 100);
});
</script>

<template>
    <Head :title="formulario.titulo" />
    
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Encabezado -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <Link :href="route('admin.formularios.index')">
                            <Button variant="ghost" size="sm">
                                <ArrowLeft class="h-4 w-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 class="text-3xl font-bold">{{ formulario.titulo }}</h1>
                            <p v-if="formulario.descripcion" class="text-muted-foreground">
                                {{ formulario.descripcion }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="handleEdit">
                            <Edit class="mr-2 h-4 w-4" />
                            Editar
                        </Button>
                        <Button 
                            v-if="formulario.estadisticas.total_respuestas > 0"
                            @click="handleExport"
                        >
                            <Download class="mr-2 h-4 w-4" />
                            Exportar CSV
                        </Button>
                    </div>
                </div>
                
                <!-- Badges de estado -->
                <div class="flex items-center gap-2">
                    <Badge :variant="getEstadoColor(formulario.estado)">
                        {{ formulario.estado }}
                    </Badge>
                    <Badge variant="outline">
                        {{ getTipoAccesoLabel(formulario.tipo_acceso) }}
                    </Badge>
                    <Badge v-if="formulario.categoria" variant="secondary">
                        {{ formulario.categoria.nombre }}
                    </Badge>
                    <Badge :variant="formulario.activo ? 'default' : 'destructive'">
                        {{ formulario.activo ? 'Activo' : 'Inactivo' }}
                    </Badge>
                </div>
            </div>
            
            <!-- URL Pública -->
            <Alert class="mb-6">
                <AlertDescription class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <ExternalLink class="h-4 w-4" />
                        <span class="font-medium">URL Pública:</span>
                        <code class="bg-gray-100 px-2 py-1 rounded text-sm">
                            {{ formulario.url_publica }}
                        </code>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="ghost" size="sm" @click="copyUrlToClipboard">
                            <Copy class="h-4 w-4" />
                        </Button>
                        <a :href="formulario.url_publica" target="_blank">
                            <Button variant="ghost" size="sm">
                                <ExternalLink class="h-4 w-4" />
                            </Button>
                        </a>
                    </div>
                </AlertDescription>
            </Alert>
            
            <!-- Tabs -->
            <Tabs v-model="activeTab" class="space-y-6">
                <TabsList>
                    <TabsTrigger value="resumen">Resumen</TabsTrigger>
                    <TabsTrigger value="respuestas">
                        Respuestas ({{ formulario.estadisticas.total_respuestas }})
                    </TabsTrigger>
                    <TabsTrigger value="estadisticas">Estadísticas</TabsTrigger>
                </TabsList>
                
                <!-- Tab: Resumen -->
                <TabsContent value="resumen" class="space-y-6">
                    <!-- Estadísticas rápidas -->
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Card>
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">
                                    Total Respuestas
                                </CardTitle>
                                <FileText class="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{ formulario.estadisticas.total_respuestas }}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ formulario.estadisticas.respuestas_hoy }} hoy
                                </p>
                            </CardContent>
                        </Card>
                        
                        <Card>
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">
                                    Usuarios Únicos
                                </CardTitle>
                                <Users class="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{ formulario.estadisticas.usuarios_unicos }}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Usuarios registrados
                                </p>
                            </CardContent>
                        </Card>
                        
                        <Card>
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">
                                    Visitantes
                                </CardTitle>
                                <Users class="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{ formulario.estadisticas.visitantes_unicos }}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ porcentajeVisitantes }}% del total
                                </p>
                            </CardContent>
                        </Card>
                        
                        <Card>
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">
                                    Esta Semana
                                </CardTitle>
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{ formulario.estadisticas.respuestas_semana }}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Respuestas
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                    
                    <!-- Información del formulario -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Configuración del Formulario</CardTitle>
                            <CardDescription>
                                Estructura y campos del formulario
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div>
                                    <h4 class="font-medium mb-2">Campos del formulario:</h4>
                                    <div class="space-y-2">
                                        <div 
                                            v-for="(campo, index) in formulario.configuracion_campos" 
                                            :key="campo.id"
                                            class="flex items-center gap-2 p-2 bg-gray-50 rounded"
                                        >
                                            <span class="text-sm font-medium">{{ index + 1 }}.</span>
                                            <span class="text-sm">{{ campo.title }}</span>
                                            <Badge variant="outline" class="text-xs">
                                                {{ campo.type }}
                                            </Badge>
                                            <Badge v-if="campo.required" variant="destructive" class="text-xs">
                                                Requerido
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
                
                <!-- Tab: Respuestas -->
                <TabsContent value="respuestas" class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Respuestas Recibidas</CardTitle>
                            <CardDescription>
                                Lista de todas las respuestas enviadas
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="respuestas.data.length === 0" class="text-center py-8">
                                <FileText class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                                <p class="text-muted-foreground">
                                    No hay respuestas aún
                                </p>
                            </div>
                            
                            <Table v-else>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Código</TableHead>
                                        <TableHead>Nombre</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Tipo</TableHead>
                                        <TableHead>Tiempo</TableHead>
                                        <TableHead>Fecha</TableHead>
                                        <TableHead>Acciones</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="respuesta in respuestas.data" :key="respuesta.id">
                                        <TableCell class="font-mono text-xs">
                                            {{ respuesta.codigo_confirmacion }}
                                        </TableCell>
                                        <TableCell>{{ respuesta.nombre }}</TableCell>
                                        <TableCell>{{ respuesta.email || '-' }}</TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {{ respuesta.es_visitante ? 'Visitante' : 'Usuario' }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{{ respuesta.tiempo_llenado || '-' }}</TableCell>
                                        <TableCell class="text-sm">
                                            {{ formatDate(respuesta.enviado_en) }}
                                        </TableCell>
                                        <TableCell>
                                            <Button 
                                                variant="ghost" 
                                                size="sm"
                                                @click="viewRespuesta(respuesta)"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            
                            <!-- Paginación -->
                            <div v-if="respuestas.links && respuestas.links.length > 3" class="mt-4">
                                <nav class="flex items-center justify-center gap-1">
                                    <template v-for="link in respuestas.links" :key="link.label">
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
                        </CardContent>
                    </Card>
                </TabsContent>
                
                <!-- Tab: Estadísticas -->
                <TabsContent value="estadisticas" class="space-y-6">
                    <div class="grid gap-6 md:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Respuestas por Período</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <span>Hoy</span>
                                        <span class="font-bold">{{ formulario.estadisticas.respuestas_hoy }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>Esta semana</span>
                                        <span class="font-bold">{{ formulario.estadisticas.respuestas_semana }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>Este mes</span>
                                        <span class="font-bold">{{ formulario.estadisticas.respuestas_mes }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>Total</span>
                                        <span class="font-bold">{{ formulario.estadisticas.total_respuestas }}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                        
                        <Card>
                            <CardHeader>
                                <CardTitle>Tipo de Usuarios</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <span>Usuarios registrados</span>
                                        <span class="font-bold">{{ formulario.estadisticas.usuarios_unicos }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>Visitantes</span>
                                        <span class="font-bold">{{ formulario.estadisticas.visitantes_unicos }}</span>
                                    </div>
                                    <div class="mt-4 pt-4 border-t">
                                        <div class="flex items-center justify-between">
                                            <span>% Visitantes</span>
                                            <span class="font-bold">{{ porcentajeVisitantes }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>
            </Tabs>
        </div>
    </AdminLayout>
</template>