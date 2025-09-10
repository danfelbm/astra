<script setup lang="ts">
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@modules/Core/Resources/js/components/ui/tabs";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@modules/Core/Resources/js/components/ui/table";
import { Alert, AlertDescription } from "@modules/Core/Resources/js/components/ui/alert";
import { type BreadcrumbItemType } from '@/types';
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import CampanaMetrics from "@modules/Campanas/Resources/js/Components/CampanaMetrics.vue";
import CampanaProgress from "@modules/Campanas/Resources/js/Components/CampanaProgress.vue";
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    ArrowLeft, Edit, Play, Pause, XCircle, RefreshCw, BarChart3, 
    Mail, MessageSquare, Users, Clock, Send, Eye, MousePointer,
    TrendingUp, TrendingDown, Activity, Calendar, User, Download,
    ChevronRight, ChevronDown, Link as LinkIcon
} from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted, reactive } from 'vue';
import { toast } from 'vue-sonner';

interface Campana {
    id: number;
    nombre: string;
    descripcion?: string;
    tipo: 'email' | 'whatsapp' | 'ambos';
    estado: 'borrador' | 'programada' | 'enviando' | 'completada' | 'pausada' | 'cancelada';
    segment?: { id: number; name: string; users_count: number };
    plantillaEmail?: { id: number; nombre: string; asunto: string };
    plantillaWhatsApp?: { id: number; nombre: string };
    fecha_programada?: string;
    fecha_inicio?: string;
    fecha_fin?: string;
    configuracion?: any;
    createdBy?: { id: number; nombre: string };
    created_at: string;
    updated_at: string;
}

interface Metricas {
    // Métricas generales
    total_destinatarios: number;
    total_enviados: number;
    total_pendientes: number;
    total_fallidos: number;
    progreso?: number;
    
    // Métricas de email
    emails_enviados: number;
    emails_abiertos: number;
    emails_con_click: number;
    emails_rebotados: number;
    total_clicks: number;
    tasa_apertura: number;
    tasa_click: number;
    tasa_rebote: number;
    tiempo_promedio_apertura: number;
    tiempo_promedio_click: number;
    
    // Métricas de WhatsApp
    whatsapp_enviados: number;
    whatsapp_entregados: number;
    whatsapp_fallidos: number;
    whatsapp_tasa_entrega?: number;
    
    // Metadata
    ultima_actualizacion?: string;
}

interface Comparacion {
    vs_anterior?: {
        apertura: number;
        clicks: number;
        enviados: number;
    };
    vs_promedio?: {
        apertura: number;
        clicks: number;
        enviados: number;
    };
}

interface Tendencia {
    fecha: string;
    enviados: number;
    abiertos?: number;
    clicks?: number;
}

interface Envio {
    id: number;
    user?: { id: number; nombre: string; email?: string };
    estado: string;
    intentos: number;
    fecha_enviado?: string;
    fecha_abierto?: string;
    fecha_primer_click?: string;
    clicks_count?: number;
    aperturas_count?: number;
    metadata?: {
        clicks?: Array<{url: string, timestamp: string}>;
        clicks_detail?: Array<{url: string, clicked_at: string, user_agent: string, ip: string}>;
        aperturas?: Array<{timestamp: string, user_agent: string, ip: string}>;
        device?: {user_agent: string, ip: string, opened_at: string};
    };
    error?: string;
    created_at: string;
}

interface Props {
    campana: Campana;
    metricas: Metricas;
    comparacion?: Comparacion;
    tendencias?: Tendencia[];
    enviosRecientes?: Envio[];
    canEdit?: boolean;
    canDelete?: boolean;
    canPause?: boolean;
    canResume?: boolean;
    canCancel?: boolean;
}

const props = defineProps<Props>();

// Convertir props a datos reactivos para actualizaciones en tiempo real
const campanaData = ref<Campana>(props.campana);
const metricasData = ref<Metricas>(props.metricas);
const comparacionData = ref(props.comparacion);
const tendenciasData = ref(props.tendencias);
const enviosRecientesData = ref(props.enviosRecientes);

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Campañas', href: '/admin/campanas' },
    { title: campanaData.value.nombre, href: '#' },
];

const activeTab = ref('resumen');
const isRefreshing = ref(false);
let refreshInterval: NodeJS.Timeout | null = null;

// Variables para controlar el auto-refresh
const autoRefreshEnabled = ref(true);
const refreshRate = ref(5000); // 5 segundos por defecto
const lastRefreshTime = ref<Date>(new Date());
const expandedRows = ref<number[]>([]);

const toggleRow = (envioId: number) => {
    const index = expandedRows.value.indexOf(envioId);
    if (index > -1) {
        expandedRows.value.splice(index, 1);
    } else {
        expandedRows.value.push(envioId);
    }
};

const progreso = computed(() => {
    const total = metricasData.value.total_destinatarios || 1;
    const enviados = metricasData.value.total_enviados || 0;
    const porcentaje = Math.round((enviados / total) * 100);
    // Asegurar que nunca exceda 100%
    return Math.min(porcentaje, 100);
});

const getEstadoBadgeVariant = (estado: string) => {
    const variants: any = {
        'borrador': 'secondary',
        'programada': 'outline',
        'enviando': 'default',
        'completada': 'default',
        'pausada': 'warning',
        'cancelada': 'destructive'
    };
    return variants[estado] || 'secondary';
};

const getTipoIcon = (tipo: string) => {
    return tipo === 'email' ? Mail : tipo === 'whatsapp' ? MessageSquare : Send;
};

const iniciarEnvio = () => {
    router.post(`/admin/campanas/${campanaData.value.id}/send`, {}, {
        onSuccess: () => {
            toast.success('Campaña iniciada exitosamente');
            campanaData.value.estado = 'enviando';
            startAutoRefresh(); // Iniciar auto-refresh al comenzar envío
            refreshMetrics(); // Actualizar métricas inmediatamente
        },
        onError: () => {
            toast.error('Error al iniciar la campaña');
        }
    });
};

const pausarCampana = () => {
    router.post(`/admin/campanas/${campanaData.value.id}/pause`, {}, {
        onSuccess: () => {
            toast.success('Campaña pausada');
            campanaData.value.estado = 'pausada';
            refreshMetrics();
        },
        onError: () => {
            toast.error('Error al pausar la campaña');
        }
    });
};

const reanudarCampana = () => {
    router.post(`/admin/campanas/${campanaData.value.id}/resume`, {}, {
        onSuccess: () => {
            toast.success('Campaña reanudada');
            campanaData.value.estado = 'enviando';
            startAutoRefresh(); // Reactivar auto-refresh
            refreshMetrics();
        },
        onError: () => {
            toast.error('Error al reanudar la campaña');
        }
    });
};

const cancelarCampana = () => {
    if (confirm('¿Estás seguro de cancelar esta campaña? Esta acción no se puede deshacer.')) {
        router.post(`/admin/campanas/${campanaData.value.id}/cancel`, {}, {
            onSuccess: () => {
                toast.success('Campaña cancelada');
                campanaData.value.estado = 'cancelada';
                stopAutoRefresh(); // Detener auto-refresh
                refreshMetrics();
            },
            onError: () => {
                toast.error('Error al cancelar la campaña');
            }
        });
    }
};

const refreshMetrics = async () => {
    if (isRefreshing.value) return; // Evitar múltiples llamadas simultáneas
    
    isRefreshing.value = true;
    try {
        const response = await fetch(`/admin/campanas/${campanaData.value.id}/metrics`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Actualizar datos reactivos directamente
            if (data.metricas) {
                metricasData.value = { ...metricasData.value, ...data.metricas };
            }
            
            if (data.estado) {
                campanaData.value.estado = data.estado;
            }
            
            if (data.progreso) {
                // El progreso se calcula automáticamente con el computed
            }
            
            lastRefreshTime.value = new Date();
            
            // Solo detener auto-refresh si realmente completó o fue cancelada
            // Verificar tanto el estado como el progreso real
            const isReallyCompleted = data.estado === 'completada' && 
                                     metricasData.value.total_enviados >= metricasData.value.total_destinatarios;
            const isCancelled = data.estado === 'cancelada';
            
            if (isReallyCompleted || isCancelled) {
                stopAutoRefresh();
                if (isReallyCompleted) {
                    toast.success('¡Campaña completada exitosamente! 🎉');
                } else if (isCancelled) {
                    toast.info('Campaña cancelada. Auto-actualización detenida.');
                }
            } else if (data.estado === 'completada' && progreso.value < 100) {
                // Si el estado dice completada pero el progreso no es 100%, continuar actualizando
                console.warn('[CampanaMetrics] Estado completada pero progreso incompleto, continuando actualización...');
            }
        }
    } catch (error) {
        console.error('Error refreshing metrics:', error);
        // No mostrar toast en cada error para no molestar al usuario
        // Solo mostrar si es manual
        if (!autoRefreshEnabled.value) {
            toast.error('Error al actualizar métricas');
        }
    } finally {
        isRefreshing.value = false;
    }
};

const exportarReporte = () => {
    router.post(`/admin/campanas/${campanaData.value.id}/export`, {
        formato: 'excel',
        incluir_detalles: true
    }, {
        onSuccess: () => {
            toast.success('Reporte exportado exitosamente');
        },
        onError: () => {
            toast.error('Error al exportar el reporte');
        }
    });
};

// Variables para control de reintentos
let consecutiveErrors = 0;
const maxConsecutiveErrors = 3;

// Funciones para controlar el auto-refresh
const startAutoRefresh = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
    
    // Configurar intervalo basado en el estado de la campaña
    // Más agresivo durante el envío
    let rate = refreshRate.value;
    if (campanaData.value.estado === 'enviando') {
        // Actualización más frecuente durante envío activo
        rate = 2000; // Cada 2 segundos
    } else if (campanaData.value.estado === 'programada') {
        rate = 5000; // Cada 5 segundos
    }
    
    refreshInterval = setInterval(() => {
        if (autoRefreshEnabled.value) {
            refreshMetrics().catch(error => {
                consecutiveErrors++;
                console.error('[AutoRefresh] Error consecutivo', consecutiveErrors, 'de', maxConsecutiveErrors);
                
                // Solo detener si hay muchos errores consecutivos
                if (consecutiveErrors >= maxConsecutiveErrors) {
                    console.error('[AutoRefresh] Demasiados errores consecutivos, deteniendo auto-refresh');
                    stopAutoRefresh();
                    toast.error('Auto-actualización detenida por errores. Haz clic en "Auto OFF" para reactivar.');
                }
            }).then(() => {
                // Resetear contador de errores si la petición fue exitosa
                if (consecutiveErrors > 0) {
                    consecutiveErrors = 0;
                }
            });
        }
    }, rate);
    
    autoRefreshEnabled.value = true;
    consecutiveErrors = 0; // Resetear contador al iniciar
};

const stopAutoRefresh = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
    autoRefreshEnabled.value = false;
    consecutiveErrors = 0; // Resetear contador al detener
};

const toggleAutoRefresh = () => {
    if (autoRefreshEnabled.value) {
        stopAutoRefresh();
        toast.info('Auto-actualización detenida');
    } else {
        startAutoRefresh();
        toast.success('Auto-actualización activada');
        refreshMetrics(); // Actualizar inmediatamente
    }
};

// Auto-refresh para campañas activas
onMounted(() => {
    // Iniciar auto-refresh para estados activos
    if (['enviando', 'programada', 'pausada'].includes(campanaData.value.estado)) {
        startAutoRefresh();
    }
    
    // Hacer una actualización inicial después de montar
    setTimeout(() => {
        refreshMetrics();
    }, 1000);
});

onUnmounted(() => {
    stopAutoRefresh();
});
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Campaña: ${campanaData.nombre}`" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex justify-between items-start">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold">{{ campanaData.nombre }}</h1>
                        <Badge :variant="getEstadoBadgeVariant(campanaData.estado)" class="text-sm">
                            {{ campanaData.estado }}
                        </Badge>
                        <!-- Indicador de actualización en tiempo real -->
                        <div v-if="autoRefreshEnabled" class="flex items-center gap-1 text-xs">
                            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                            <span class="text-muted-foreground">Actualizando...</span>
                        </div>
                    </div>
                    <p v-if="campanaData.descripcion" class="text-muted-foreground mt-1">
                        {{ campanaData.descripcion }}
                    </p>
                    <div class="flex items-center gap-4 mt-2 text-sm text-muted-foreground">
                        <div class="flex items-center gap-1">
                            <component :is="getTipoIcon(campanaData.tipo)" class="w-4 h-4" />
                            <span class="capitalize">{{ campanaData.tipo }}</span>
                        </div>
                        <div v-if="campanaData.segment" class="flex items-center gap-1">
                            <Users class="w-4 h-4" />
                            <span>{{ campanaData.segment.name }}</span>
                        </div>
                        <div v-if="campanaData.fecha_programada" class="flex items-center gap-1">
                            <Calendar class="w-4 h-4" />
                            <span>{{ campanaData.fecha_programada ? new Date(campanaData.fecha_programada).toLocaleString('es-ES') : '-' }}</span>
                        </div>
                        <!-- Última actualización -->
                        <div class="flex items-center gap-1 text-xs">
                            <Clock class="w-3 h-3" />
                            <span>Actualizado: {{ lastRefreshTime.toLocaleTimeString('es-ES') }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link href="/admin/campanas">
                        <Button variant="outline">
                            <ArrowLeft class="w-4 h-4 mr-2" />
                            Volver
                        </Button>
                    </Link>
                    
                    <!-- Control de auto-refresh -->
                    <Button 
                        @click="toggleAutoRefresh" 
                        variant="outline"
                        :class="{ 'border-green-500': autoRefreshEnabled }"
                    >
                        <RefreshCw class="w-4 h-4 mr-2" :class="{ 'animate-spin': isRefreshing }" />
                        {{ autoRefreshEnabled ? 'Auto ON' : 'Auto OFF' }}
                    </Button>
                    
                    <!-- Acciones según estado -->
                    <Link v-if="canEdit && campanaData.estado === 'borrador'" :href="`/admin/campanas/${campanaData.id}/edit`">
                        <Button variant="outline">
                            <Edit class="w-4 h-4 mr-2" />
                            Editar
                        </Button>
                    </Link>
                    
                    <Button v-if="campanaData.estado === 'borrador'" @click="iniciarEnvio" variant="default">
                        <Play class="w-4 h-4 mr-2" />
                        Iniciar Envío
                    </Button>
                    
                    <Button v-if="canPause && campanaData.estado === 'enviando'" @click="pausarCampana" variant="warning">
                        <Pause class="w-4 h-4 mr-2" />
                        Pausar
                    </Button>
                    
                    <Button v-if="canResume && campanaData.estado === 'pausada'" @click="reanudarCampana" variant="default">
                        <Play class="w-4 h-4 mr-2" />
                        Reanudar
                    </Button>
                    
                    <Button v-if="canCancel && ['borrador', 'programada', 'pausada'].includes(campanaData.estado)" 
                            @click="cancelarCampana" 
                            variant="destructive">
                        <XCircle class="w-4 h-4 mr-2" />
                        Cancelar
                    </Button>
                    
                    <Button @click="exportarReporte" variant="outline">
                        <Download class="w-4 h-4 mr-2" />
                        Exportar
                    </Button>
                </div>
            </div>

            <!-- Progreso de Envío -->
            <CampanaProgress 
                v-if="['enviando', 'completada', 'pausada'].includes(campanaData.estado)"
                :progreso="progreso"
                :total="metricasData.total_destinatarios"
                :enviados="metricasData.total_enviados"
                :pendientes="metricasData.total_pendientes"
                :fallidos="metricasData.total_fallidos"
                :estado="campanaData.estado"
                :fecha-inicio="campanaData.fecha_inicio"
            />

            <!-- Métricas Principales -->
            <CampanaMetrics 
                :metrics="metricasData"
                :comparison="comparacionData"
                :tipo="campanaData.tipo"
            />

            <!-- Tabs de Información -->
            <Tabs v-model="activeTab">
                <TabsList>
                    <TabsTrigger value="resumen">Resumen</TabsTrigger>
                    <TabsTrigger value="actividad">Actividad Reciente</TabsTrigger>
                    <TabsTrigger value="tendencias" v-if="tendenciasData && tendenciasData.length > 0">
                        Tendencias
                    </TabsTrigger>
                    <TabsTrigger value="configuracion">Configuración</TabsTrigger>
                </TabsList>

                <!-- Tab Resumen -->
                <TabsContent value="resumen">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Información de la Campaña</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Creada por:</span>
                                    <span>{{ campanaData.createdBy?.nombre || 'Sistema' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Fecha creación:</span>
                                    <span>{{ new Date(campanaData.created_at).toLocaleDateString('es-ES') }}</span>
                                </div>
                                <div v-if="campanaData.fecha_inicio" class="flex justify-between">
                                    <span class="text-muted-foreground">Inicio envío:</span>
                                    <span>{{ campanaData.fecha_inicio ? new Date(campanaData.fecha_inicio).toLocaleString('es-ES') : '-' }}</span>
                                </div>
                                <div v-if="campanaData.fecha_fin" class="flex justify-between">
                                    <span class="text-muted-foreground">Fin envío:</span>
                                    <span>{{ campanaData.fecha_fin ? new Date(campanaData.fecha_fin).toLocaleString('es-ES') : '-' }}</span>
                                </div>
                                <div v-if="metricasData.tiempo_promedio_envio" class="flex justify-between">
                                    <span class="text-muted-foreground">Tiempo promedio:</span>
                                    <span>{{ metricasData.tiempo_promedio_envio }}s por envío</span>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Plantillas Utilizadas</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <div v-if="campanaData.plantillaEmail">
                                    <div class="flex items-center gap-2 mb-1">
                                        <Mail class="w-4 h-4 text-muted-foreground" />
                                        <span class="font-medium">Email:</span>
                                    </div>
                                    <div class="pl-6 text-sm">
                                        <div>{{ campanaData.plantillaEmail.nombre }}</div>
                                        <div class="text-muted-foreground">
                                            Asunto: {{ campanaData.plantillaEmail.asunto }}
                                        </div>
                                    </div>
                                </div>
                                <div v-if="campanaData.plantillaWhatsApp">
                                    <div class="flex items-center gap-2 mb-1">
                                        <MessageSquare class="w-4 h-4 text-muted-foreground" />
                                        <span class="font-medium">WhatsApp:</span>
                                    </div>
                                    <div class="pl-6 text-sm">
                                        {{ campanaData.plantillaWhatsApp.nombre }}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Tab Actividad -->
                <TabsContent value="actividad">
                    <Card>
                        <CardHeader>
                            <div class="flex justify-between items-center">
                                <CardTitle>Actividad Reciente</CardTitle>
                                <Button 
                                    @click="refreshMetrics" 
                                    variant="outline" 
                                    size="sm"
                                    :disabled="isRefreshing"
                                >
                                    <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isRefreshing }" />
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Usuario</TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead>Fecha Envío</TableHead>
                                        <TableHead>Apertura</TableHead>
                                        <TableHead>Clicks</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <template v-for="envio in enviosRecientesData" :key="envio.id">
                                        <TableRow 
                                            @click="toggleRow(envio.id)"
                                            class="cursor-pointer hover:bg-muted/50 transition-colors"
                                        >
                                                <TableCell>
                                                <div class="flex items-center gap-2">
                                                    <ChevronRight v-if="!expandedRows.includes(envio.id)" class="w-4 h-4" />
                                                    <ChevronDown v-else class="w-4 h-4" />
                                                    <div>
                                                        <div class="font-medium">{{ envio.user?.nombre }}</div>
                                                        <div class="text-sm text-muted-foreground">
                                                            {{ envio.user?.email }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </TableCell>
                                        <TableCell>
                                            <Badge :variant="envio.estado === 'enviado' ? 'default' : 'destructive'">
                                                {{ envio.estado }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {{ envio.fecha_enviado ? new Date(envio.fecha_enviado).toLocaleString('es-ES') : '-' }}
                                        </TableCell>
                                        <TableCell>
                                            <div v-if="envio.fecha_abierto" class="flex items-center gap-1">
                                                <Eye class="w-3 h-3" />
                                                {{ new Date(envio.fecha_abierto).toLocaleTimeString('es-ES') }}
                                                <Badge v-if="envio.aperturas_count && envio.aperturas_count > 1" variant="secondary" class="ml-1">
                                                    {{ envio.aperturas_count }}
                                                </Badge>
                                            </div>
                                            <span v-else class="text-muted-foreground">-</span>
                                        </TableCell>
                                        <TableCell>
                                            <div v-if="envio.fecha_primer_click" class="flex items-center gap-1">
                                                <MousePointer class="w-3 h-3" />
                                                {{ new Date(envio.fecha_primer_click).toLocaleTimeString('es-ES') }}
                                                <Badge v-if="envio.clicks_count && envio.clicks_count > 1" variant="secondary" class="ml-1">
                                                    {{ envio.clicks_count }}
                                                </Badge>
                                            </div>
                                            <span v-else class="text-muted-foreground">-</span>
                                            </TableCell>
                                        </TableRow>
                                        
                                        <!-- Contenido expandido -->
                                        <TableRow v-if="expandedRows.includes(envio.id)">
                                            <TableCell colspan="6" class="p-0">
                                                <div class="bg-muted/30 p-4">
                                                    <Tabs defaultValue="aperturas" class="w-full">
                                                        <TabsList class="grid w-full max-w-md grid-cols-2">
                                                            <TabsTrigger value="aperturas">
                                                                <Eye class="w-4 h-4 mr-2" />
                                                                Aperturas {{ envio.aperturas_count ? `(${envio.aperturas_count})` : '' }}
                                                            </TabsTrigger>
                                                            <TabsTrigger value="clics">
                                                                <MousePointer class="w-4 h-4 mr-2" />
                                                                Clics {{ envio.clicks_count ? `(${envio.clicks_count})` : '' }}
                                                            </TabsTrigger>
                                                        </TabsList>
                                                        
                                                        <TabsContent value="aperturas" class="mt-4">
                                                            <div v-if="envio.metadata?.aperturas?.length" class="space-y-2">
                                                                <div class="text-sm font-medium mb-2">Historial de aperturas:</div>
                                                                <div v-for="(apertura, index) in envio.metadata.aperturas" :key="index" 
                                                                     class="flex items-center gap-2 text-sm p-2 bg-background rounded">
                                                                    <Eye class="w-3 h-3 text-muted-foreground" />
                                                                    <span>{{ new Date(apertura.timestamp).toLocaleString('es-ES') }}</span>
                                                                    <span class="text-muted-foreground text-xs">{{ apertura.ip }}</span>
                                                                </div>
                                                            </div>
                                                            <div v-else class="text-sm text-muted-foreground">
                                                                No hay registros de apertura
                                                            </div>
                                                        </TabsContent>
                                                        
                                                        <TabsContent value="clics" class="mt-4">
                                                            <div v-if="(envio.metadata?.clicks?.length || envio.metadata?.clicks_detail?.length)" class="space-y-2">
                                                                <div class="text-sm font-medium mb-2">Historial de clics:</div>
                                                                <!-- Priorizar clicks_detail si existe (tiene más información) -->
                                                                <div v-if="envio.metadata?.clicks_detail?.length">
                                                                    <div v-for="(click, index) in envio.metadata.clicks_detail" :key="`click-${index}`" 
                                                                         class="p-2 bg-background rounded space-y-1">
                                                                        <div class="flex items-center gap-2 text-sm">
                                                                            <LinkIcon class="w-3 h-3 text-muted-foreground" />
                                                                            <span class="font-medium">{{ new Date(click.clicked_at).toLocaleString('es-ES') }}</span>
                                                                        </div>
                                                                        <div class="text-xs text-muted-foreground ml-5">
                                                                            <div class="truncate">URL: {{ click.url }}</div>
                                                                            <div v-if="click.ip">IP: {{ click.ip }}</div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <!-- Solo mostrar clicks si NO existe clicks_detail -->
                                                                <div v-else-if="envio.metadata?.clicks?.length">
                                                                    <div v-for="(click, index) in envio.metadata.clicks" :key="`click-${index}`" 
                                                                         class="p-2 bg-background rounded space-y-1">
                                                                        <div class="flex items-center gap-2 text-sm">
                                                                            <LinkIcon class="w-3 h-3 text-muted-foreground" />
                                                                            <span class="font-medium">{{ new Date(click.timestamp).toLocaleString('es-ES') }}</span>
                                                                        </div>
                                                                        <div class="text-xs text-muted-foreground ml-5">
                                                                            <div class="truncate">URL: {{ click.url }}</div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div v-else class="text-sm text-muted-foreground">
                                                                No hay registros de clics
                                                            </div>
                                                        </TabsContent>
                                                    </Tabs>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    </template>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Tab Configuración -->
                <TabsContent value="configuracion">
                    <Card>
                        <CardHeader>
                            <CardTitle>Configuración de Envío</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-if="campanaData.configuracion?.batch_size_email">
                                    <div class="text-sm text-muted-foreground">Tamaño de lote (Email)</div>
                                    <div class="font-medium">{{ campanaData.configuracion.batch_size_email }} emails/lote</div>
                                </div>
                                <div v-if="campanaData.configuracion?.batch_size_whatsapp">
                                    <div class="text-sm text-muted-foreground">Tamaño de lote (WhatsApp)</div>
                                    <div class="font-medium">{{ campanaData.configuracion.batch_size_whatsapp }} mensajes/lote</div>
                                </div>
                                <div v-if="campanaData.configuracion?.enable_pixel_tracking !== undefined">
                                    <div class="text-sm text-muted-foreground">Tracking de apertura</div>
                                    <div class="font-medium">
                                        {{ campanaData.configuracion.enable_pixel_tracking ? 'Habilitado' : 'Deshabilitado' }}
                                    </div>
                                </div>
                                <div v-if="campanaData.configuracion?.enable_click_tracking !== undefined">
                                    <div class="text-sm text-muted-foreground">Tracking de clicks</div>
                                    <div class="font-medium">
                                        {{ campanaData.configuracion.enable_click_tracking ? 'Habilitado' : 'Deshabilitado' }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AdminLayout>
</template>