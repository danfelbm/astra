<script setup lang="ts">
/**
 * Componente para mostrar actividad reciente de envíos de campañas
 * Con paginación server-side y filtros sofisticados
 */
import { ref, computed, onMounted, watch } from 'vue';
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@modules/Core/Resources/js/components/ui/table";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@modules/Core/Resources/js/components/ui/select";
import { Alert, AlertDescription } from "@modules/Core/Resources/js/components/ui/alert";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@modules/Core/Resources/js/components/ui/tabs";
import Pagination from "@modules/Core/Resources/js/components/ui/pagination/Pagination.vue";
import {
    Mail, MessageSquare, RefreshCw, AlertCircle, Eye, MousePointer,
    Users, ChevronRight, ChevronDown, Search, X, Filter, Link as LinkIcon
} from 'lucide-vue-next';

// Interfaz para los envíos
interface Envio {
    id: number;
    tipo: 'email' | 'whatsapp' | 'whatsapp_group';
    user?: { id: number; nombre: string; email?: string };
    grupo?: { nombre: string; participantes: number; jid: string };
    destinatario?: string;
    estado: string;
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
        group_nombre?: string;
        group_participantes?: number;
    };
    error?: string;
    created_at: string;
}

// Interfaz de paginación
interface PaginatedResponse {
    data: Envio[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

// Props del componente
const props = defineProps<{
    campanaId: number;
    tipo: 'email' | 'whatsapp' | 'ambos';
}>();

// Estado reactivo
const envios = ref<PaginatedResponse | null>(null);
const isLoading = ref(false);
const error = ref<string | null>(null);
const expandedRows = ref<number[]>([]);

// Estado de filtros (usamos '__all__' como valor por defecto para los selects)
const filtros = ref({
    tipo: '__all__',
    estado: '__all__',
    search: '',
    fecha_desde: '',
    fecha_hasta: '',
});

// Página actual
const currentPage = ref(1);

// Opciones de filtros
const tiposOptions = computed(() => {
    if (props.tipo === 'email') return [{ value: 'email', label: 'Email' }];
    if (props.tipo === 'whatsapp') return [
        { value: 'whatsapp', label: 'WhatsApp' },
        { value: 'whatsapp_group', label: 'Grupo WhatsApp' },
    ];
    return [
        { value: 'email', label: 'Email' },
        { value: 'whatsapp', label: 'WhatsApp' },
        { value: 'whatsapp_group', label: 'Grupo WhatsApp' },
    ];
});

const estadosOptions = [
    { value: 'pendiente', label: 'Pendiente' },
    { value: 'enviando', label: 'Enviando' },
    { value: 'enviado', label: 'Enviado' },
    { value: 'abierto', label: 'Abierto' },
    { value: 'click', label: 'Con Click' },
    { value: 'fallido', label: 'Fallido' },
];

// Verificar si hay filtros activos (ignorando el valor especial '__all__')
const hasActiveFilters = computed(() => {
    return (filtros.value.tipo !== '__all__' && filtros.value.tipo !== '') ||
           (filtros.value.estado !== '__all__' && filtros.value.estado !== '') ||
           filtros.value.search !== '' ||
           filtros.value.fecha_desde !== '' ||
           filtros.value.fecha_hasta !== '';
});

// Contar filtros activos
const activeFiltersCount = computed(() => {
    let count = 0;
    if (filtros.value.tipo && filtros.value.tipo !== '__all__') count++;
    if (filtros.value.estado && filtros.value.estado !== '__all__') count++;
    if (filtros.value.search) count++;
    if (filtros.value.fecha_desde) count++;
    if (filtros.value.fecha_hasta) count++;
    return count;
});

// Construir URL con parámetros (ignorando el valor especial '__all__')
const buildUrl = () => {
    const params = new URLSearchParams();
    params.append('page', currentPage.value.toString());

    if (filtros.value.tipo && filtros.value.tipo !== '__all__') params.append('tipo', filtros.value.tipo);
    if (filtros.value.estado && filtros.value.estado !== '__all__') params.append('estado', filtros.value.estado);
    if (filtros.value.search) params.append('search', filtros.value.search);
    if (filtros.value.fecha_desde) params.append('fecha_desde', filtros.value.fecha_desde);
    if (filtros.value.fecha_hasta) params.append('fecha_hasta', filtros.value.fecha_hasta);

    return `/admin/envio-campanas/${props.campanaId}/envios?${params.toString()}`;
};

// Cargar envíos desde el backend
const cargarEnvios = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const response = await fetch(buildUrl(), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error('Error al cargar actividad');
        }

        const data = await response.json();

        if (data.success) {
            envios.value = data.envios;
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    } catch (e: any) {
        error.value = e.message || 'Error desconocido';
    } finally {
        isLoading.value = false;
    }
};

// Aplicar filtros (resetea a página 1)
const aplicarFiltros = () => {
    currentPage.value = 1;
    cargarEnvios();
};

// Limpiar filtros (restaurar valores por defecto)
const limpiarFiltros = () => {
    filtros.value = {
        tipo: '__all__',
        estado: '__all__',
        search: '',
        fecha_desde: '',
        fecha_hasta: '',
    };
    currentPage.value = 1;
    cargarEnvios();
};

// Navegar a una página
const navigateToPage = (url: string | null) => {
    if (!url) return;

    // Extraer número de página de la URL
    const urlObj = new URL(url, window.location.origin);
    const page = urlObj.searchParams.get('page');
    if (page) {
        currentPage.value = parseInt(page);
        cargarEnvios();
    }
};

// Expandir/colapsar fila
const toggleRow = (envioId: number) => {
    const index = expandedRows.value.indexOf(envioId);
    if (index > -1) {
        expandedRows.value.splice(index, 1);
    } else {
        expandedRows.value.push(envioId);
    }
};

// Formatear fecha
const formatDate = (dateStr: string | null | undefined) => {
    if (!dateStr) return '-';
    try {
        return new Date(dateStr).toLocaleString('es-ES');
    } catch {
        return dateStr;
    }
};

// Formatear hora
const formatTime = (dateStr: string | null | undefined) => {
    if (!dateStr) return '-';
    try {
        return new Date(dateStr).toLocaleTimeString('es-ES');
    } catch {
        return dateStr;
    }
};

// Obtener variante del badge según estado
const getEstadoVariant = (estado: string) => {
    if (['enviado', 'abierto', 'click'].includes(estado)) return 'default';
    if (estado === 'fallido') return 'destructive';
    return 'secondary';
};

// Mostrar columnas de apertura/clicks
const showEmailColumns = computed(() => {
    return props.tipo === 'email' || props.tipo === 'ambos';
});

// Cargar al montar
onMounted(cargarEnvios);
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex flex-col gap-4">
                <!-- Título y botón refrescar -->
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <CardTitle>Actividad Reciente</CardTitle>
                        <Badge v-if="envios?.total" variant="secondary">
                            {{ envios.total }} envíos
                        </Badge>
                    </div>
                    <Button
                        @click="cargarEnvios"
                        variant="outline"
                        size="sm"
                        :disabled="isLoading"
                    >
                        <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isLoading }" />
                    </Button>
                </div>

                <!-- Panel de Filtros -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-3 p-4 bg-muted/50 rounded-lg">
                    <!-- Búsqueda -->
                    <div class="lg:col-span-2">
                        <Label class="text-xs text-muted-foreground">Buscar destinatario</Label>
                        <div class="relative">
                            <Search class="absolute left-2 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="filtros.search"
                                placeholder="Email, teléfono, nombre..."
                                class="pl-8"
                                @keyup.enter="aplicarFiltros"
                            />
                        </div>
                    </div>

                    <!-- Tipo -->
                    <div>
                        <Label class="text-xs text-muted-foreground">Tipo</Label>
                        <Select v-model="filtros.tipo">
                            <SelectTrigger>
                                <SelectValue placeholder="Todos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Todos</SelectItem>
                                <SelectItem
                                    v-for="opt in tiposOptions"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Estado -->
                    <div>
                        <Label class="text-xs text-muted-foreground">Estado</Label>
                        <Select v-model="filtros.estado">
                            <SelectTrigger>
                                <SelectValue placeholder="Todos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Todos</SelectItem>
                                <SelectItem
                                    v-for="opt in estadosOptions"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Fecha desde -->
                    <div>
                        <Label class="text-xs text-muted-foreground">Desde</Label>
                        <Input
                            v-model="filtros.fecha_desde"
                            type="date"
                        />
                    </div>

                    <!-- Fecha hasta -->
                    <div>
                        <Label class="text-xs text-muted-foreground">Hasta</Label>
                        <Input
                            v-model="filtros.fecha_hasta"
                            type="date"
                        />
                    </div>

                    <!-- Botones de acción -->
                    <div class="lg:col-span-6 flex justify-end gap-2 pt-2 border-t mt-2">
                        <Button
                            v-if="hasActiveFilters"
                            variant="ghost"
                            size="sm"
                            @click="limpiarFiltros"
                        >
                            <X class="w-4 h-4 mr-1" />
                            Limpiar
                        </Button>
                        <Button
                            variant="default"
                            size="sm"
                            @click="aplicarFiltros"
                            :disabled="isLoading"
                        >
                            <Filter class="w-4 h-4 mr-1" />
                            Aplicar filtros
                            <Badge v-if="activeFiltersCount > 0" variant="secondary" class="ml-1">
                                {{ activeFiltersCount }}
                            </Badge>
                        </Button>
                    </div>
                </div>
            </div>
        </CardHeader>

        <CardContent>
            <!-- Error -->
            <Alert v-if="error" variant="destructive" class="mb-4">
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>{{ error }}</AlertDescription>
            </Alert>

            <!-- Loading -->
            <div v-if="isLoading && !envios" class="text-center py-8 text-muted-foreground">
                <RefreshCw class="w-6 h-6 animate-spin mx-auto mb-2" />
                Cargando actividad...
            </div>

            <!-- Sin datos -->
            <div v-else-if="envios && envios.data.length === 0" class="text-center py-8 text-muted-foreground">
                <Mail class="w-8 h-8 mx-auto mb-2 opacity-50" />
                No hay envíos que mostrar
            </div>

            <!-- Tabla -->
            <div v-else-if="envios" class="space-y-4">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[100px]">Tipo</TableHead>
                            <TableHead>Destinatario</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead>Fecha Envío</TableHead>
                            <TableHead v-if="showEmailColumns">Apertura</TableHead>
                            <TableHead v-if="showEmailColumns">Clicks</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-for="envio in envios.data" :key="envio.id">
                            <!-- Fila principal -->
                            <TableRow
                                @click="toggleRow(envio.id)"
                                class="cursor-pointer hover:bg-muted/50 transition-colors"
                            >
                                <!-- Tipo -->
                                <TableCell>
                                    <Badge :variant="envio.tipo === 'email' ? 'default' : 'secondary'" class="text-xs">
                                        <Mail v-if="envio.tipo === 'email'" class="w-3 h-3 mr-1" />
                                        <Users v-else-if="envio.tipo === 'whatsapp_group'" class="w-3 h-3 mr-1" />
                                        <MessageSquare v-else class="w-3 h-3 mr-1" />
                                        {{ envio.tipo === 'email' ? 'Email' : envio.tipo === 'whatsapp_group' ? 'Grupo' : 'WA' }}
                                    </Badge>
                                </TableCell>

                                <!-- Destinatario -->
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <ChevronRight v-if="!expandedRows.includes(envio.id)" class="w-4 h-4 flex-shrink-0" />
                                        <ChevronDown v-else class="w-4 h-4 flex-shrink-0" />

                                        <!-- Usuario individual -->
                                        <div v-if="envio.user">
                                            <div class="font-medium">{{ envio.user.nombre }}</div>
                                            <div class="text-sm text-muted-foreground">
                                                {{ envio.user.email || envio.destinatario }}
                                            </div>
                                        </div>
                                        <!-- Grupo de WhatsApp -->
                                        <div v-else-if="envio.grupo" class="flex items-center gap-2">
                                            <div>
                                                <div class="font-medium text-green-700">{{ envio.grupo.nombre }}</div>
                                                <div class="text-sm text-muted-foreground">
                                                    {{ envio.grupo.participantes }} participantes
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Fallback -->
                                        <div v-else>
                                            <div class="font-medium text-muted-foreground">{{ envio.destinatario }}</div>
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Estado -->
                                <TableCell>
                                    <Badge :variant="getEstadoVariant(envio.estado)">
                                        {{ envio.estado }}
                                    </Badge>
                                </TableCell>

                                <!-- Fecha Envío -->
                                <TableCell>
                                    {{ formatDate(envio.fecha_enviado) }}
                                </TableCell>

                                <!-- Apertura (solo Email) -->
                                <TableCell v-if="showEmailColumns">
                                    <template v-if="envio.tipo === 'email'">
                                        <div v-if="envio.fecha_abierto" class="flex items-center gap-1">
                                            <Eye class="w-3 h-3" />
                                            {{ formatTime(envio.fecha_abierto) }}
                                            <Badge v-if="envio.aperturas_count && envio.aperturas_count > 1" variant="secondary" class="ml-1">
                                                {{ envio.aperturas_count }}
                                            </Badge>
                                        </div>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </template>
                                    <span v-else class="text-muted-foreground text-xs">N/A</span>
                                </TableCell>

                                <!-- Clicks (solo Email) -->
                                <TableCell v-if="showEmailColumns">
                                    <template v-if="envio.tipo === 'email'">
                                        <div v-if="envio.fecha_primer_click" class="flex items-center gap-1">
                                            <MousePointer class="w-3 h-3" />
                                            {{ formatTime(envio.fecha_primer_click) }}
                                            <Badge v-if="envio.clicks_count && envio.clicks_count > 1" variant="secondary" class="ml-1">
                                                {{ envio.clicks_count }}
                                            </Badge>
                                        </div>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </template>
                                    <span v-else class="text-muted-foreground text-xs">N/A</span>
                                </TableCell>
                            </TableRow>

                            <!-- Contenido expandido -->
                            <TableRow v-if="expandedRows.includes(envio.id)">
                                <TableCell :colspan="showEmailColumns ? 6 : 4" class="p-0">
                                    <div class="bg-muted/30 p-4">
                                        <!-- Para Email: tabs de aperturas y clics -->
                                        <template v-if="envio.tipo === 'email'">
                                            <Tabs default-value="aperturas" class="w-full">
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
                                                            <span>{{ formatDate(apertura.timestamp) }}</span>
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
                                                        <div v-if="envio.metadata?.clicks_detail?.length">
                                                            <div v-for="(click, index) in envio.metadata.clicks_detail" :key="`click-${index}`"
                                                                 class="p-2 bg-background rounded space-y-1">
                                                                <div class="flex items-center gap-2 text-sm">
                                                                    <LinkIcon class="w-3 h-3 text-muted-foreground" />
                                                                    <span class="font-medium">{{ formatDate(click.clicked_at) }}</span>
                                                                </div>
                                                                <div class="text-xs text-muted-foreground ml-5">
                                                                    <div class="truncate">URL: {{ click.url }}</div>
                                                                    <div v-if="click.ip">IP: {{ click.ip }}</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div v-else-if="envio.metadata?.clicks?.length">
                                                            <div v-for="(click, index) in envio.metadata.clicks" :key="`click-${index}`"
                                                                 class="p-2 bg-background rounded space-y-1">
                                                                <div class="flex items-center gap-2 text-sm">
                                                                    <LinkIcon class="w-3 h-3 text-muted-foreground" />
                                                                    <span class="font-medium">{{ formatDate(click.timestamp) }}</span>
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
                                        </template>

                                        <!-- Para WhatsApp Grupo: info del grupo -->
                                        <template v-else-if="envio.tipo === 'whatsapp_group'">
                                            <div class="space-y-3">
                                                <div class="text-sm font-medium">Información del Grupo</div>
                                                <div class="grid grid-cols-2 gap-4 text-sm">
                                                    <div>
                                                        <span class="text-muted-foreground">Nombre:</span>
                                                        <span class="ml-2 font-medium">{{ envio.grupo?.nombre || envio.metadata?.group_nombre || 'N/A' }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-muted-foreground">Participantes:</span>
                                                        <span class="ml-2 font-medium">{{ envio.grupo?.participantes || envio.metadata?.group_participantes || 'N/A' }}</span>
                                                    </div>
                                                    <div class="col-span-2">
                                                        <span class="text-muted-foreground">JID:</span>
                                                        <code class="ml-2 text-xs bg-muted px-2 py-1 rounded">{{ envio.destinatario }}</code>
                                                    </div>
                                                </div>
                                                <div class="text-xs text-muted-foreground mt-2">
                                                    WhatsApp no soporta tracking de aperturas ni clics para grupos.
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Para WhatsApp Individual -->
                                        <template v-else>
                                            <div class="space-y-3">
                                                <div class="text-sm font-medium">Información del Envío</div>
                                                <div class="grid grid-cols-2 gap-4 text-sm">
                                                    <div>
                                                        <span class="text-muted-foreground">Destinatario:</span>
                                                        <span class="ml-2 font-medium">{{ envio.destinatario }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-muted-foreground">Estado:</span>
                                                        <span class="ml-2 font-medium">{{ envio.estado }}</span>
                                                    </div>
                                                </div>
                                                <div v-if="envio.error" class="text-sm text-destructive">
                                                    Error: {{ envio.error }}
                                                </div>
                                                <div class="text-xs text-muted-foreground mt-2">
                                                    WhatsApp no soporta tracking de aperturas ni clics.
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </template>
                    </TableBody>
                </Table>

                <!-- Info de resultados y paginación -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-muted-foreground">
                        Mostrando {{ envios.from || 0 }} a {{ envios.to || 0 }} de {{ envios.total }} resultados
                    </div>

                    <!-- Paginación -->
                    <div v-if="envios.links && envios.links.length > 3" class="flex items-center gap-2">
                        <template v-for="link in envios.links" :key="link.label">
                            <Button
                                v-if="link.url"
                                :variant="link.active ? 'default' : 'outline'"
                                size="sm"
                                @click="navigateToPage(link.url)"
                                v-html="link.label.replace('&laquo; Previous', '← Anterior').replace('Next &raquo;', 'Siguiente →')"
                            />
                            <span
                                v-else
                                class="px-3 py-1 text-sm text-muted-foreground cursor-not-allowed"
                                v-html="link.label.replace('&laquo; Previous', '← Anterior').replace('Next &raquo;', 'Siguiente →')"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
