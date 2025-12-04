<script setup lang="ts">
/**
 * Componente para mostrar logs de envío de campañas
 * Distingue entre Resend (email) y Evolution API (WhatsApp)
 */
import { ref, computed, onMounted } from 'vue';
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@modules/Core/Resources/js/components/ui/table";
import { Alert, AlertDescription } from "@modules/Core/Resources/js/components/ui/alert";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "@modules/Core/Resources/js/components/ui/dialog";
import { Mail, MessageSquare, RefreshCw, AlertCircle, CheckCircle, Clock, XCircle, Eye, Users } from 'lucide-vue-next';

// Interfaz para los logs
interface Log {
    id: number;
    tipo: 'email' | 'whatsapp' | 'whatsapp_group';
    servicio: 'resend' | 'evolution_individual' | 'evolution_group';
    estado: string;
    destinatario: string;
    fecha: string | null;
    mensaje_id: string | null;
    error: string | null;
    metadata: Record<string, any> | null;
    grupo?: {
        nombre: string | null;
        participantes: number | null;
    };
}

// Props del componente
const props = defineProps<{
    campanaId: number;
    tipo: 'email' | 'whatsapp' | 'ambos';
}>();

// Estado reactivo
const logs = ref<Log[]>([]);
const filtroServicio = ref<'todos' | 'resend' | 'evolution_individual' | 'evolution_group'>('todos');
const isLoading = ref(false);
const error = ref<string | null>(null);
const selectedLog = ref<Log | null>(null);
const showDetailDialog = ref(false);

// Logs filtrados por servicio
const logsFiltrados = computed(() => {
    if (filtroServicio.value === 'todos') return logs.value;
    return logs.value.filter(l => l.servicio === filtroServicio.value);
});

// Contadores por tipo
const contadores = computed(() => ({
    email: logs.value.filter(l => l.servicio === 'resend').length,
    waIndividual: logs.value.filter(l => l.servicio === 'evolution_individual').length,
    waGrupo: logs.value.filter(l => l.servicio === 'evolution_group').length,
}));

// Estadísticas
const stats = computed(() => {
    const total = logs.value.length;
    const exitosos = logs.value.filter(l => ['enviado', 'abierto', 'click'].includes(l.estado)).length;
    const fallidos = logs.value.filter(l => l.estado === 'fallido').length;
    const pendientes = logs.value.filter(l => ['pendiente', 'enviando'].includes(l.estado)).length;

    return { total, exitosos, fallidos, pendientes };
});

// Cargar logs desde el backend
const cargarLogs = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const response = await fetch(`/admin/envio-campanas/${props.campanaId}/logs`);

        if (!response.ok) {
            throw new Error('Error al cargar logs');
        }

        logs.value = await response.json();
    } catch (e: any) {
        error.value = e.message || 'Error desconocido';
    } finally {
        isLoading.value = false;
    }
};

// Obtener variante del badge según estado
const getEstadoVariant = (estado: string) => {
    const variants: Record<string, string> = {
        'pendiente': 'secondary',
        'enviando': 'outline',
        'enviado': 'default',
        'abierto': 'default',
        'click': 'default',
        'fallido': 'destructive',
    };
    return variants[estado] || 'secondary';
};

// Obtener ícono según estado
const getEstadoIcon = (estado: string) => {
    const icons: Record<string, any> = {
        'pendiente': Clock,
        'enviando': Clock,
        'enviado': CheckCircle,
        'abierto': Eye,
        'click': Eye,
        'fallido': XCircle,
    };
    return icons[estado] || Clock;
};

// Formatear fecha
const formatDate = (dateStr: string | null) => {
    if (!dateStr) return '-';
    try {
        const date = new Date(dateStr);
        return date.toLocaleString('es-CO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateStr;
    }
};

// Ver detalles de un log
const verDetalles = (log: Log) => {
    selectedLog.value = log;
    showDetailDialog.value = true;
};

// Cargar logs al montar
onMounted(cargarLogs);
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <CardTitle>Logs de Envío</CardTitle>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ stats.total }} registros | {{ stats.exitosos }} exitosos | {{ stats.fallidos }} fallidos
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <!-- Filtro por Resend (Email) -->
                    <Button
                        v-if="tipo !== 'whatsapp'"
                        :variant="filtroServicio === 'resend' ? 'default' : 'outline'"
                        size="sm"
                        @click="filtroServicio = filtroServicio === 'resend' ? 'todos' : 'resend'"
                    >
                        <Mail class="w-4 h-4 mr-1" />
                        Email
                        <Badge v-if="contadores.email > 0" variant="secondary" class="ml-1 text-xs">
                            {{ contadores.email }}
                        </Badge>
                    </Button>

                    <!-- Filtro por Evolution Individual (WhatsApp contactos) -->
                    <Button
                        v-if="tipo !== 'email'"
                        :variant="filtroServicio === 'evolution_individual' ? 'default' : 'outline'"
                        size="sm"
                        @click="filtroServicio = filtroServicio === 'evolution_individual' ? 'todos' : 'evolution_individual'"
                    >
                        <MessageSquare class="w-4 h-4 mr-1" />
                        WA Individual
                        <Badge v-if="contadores.waIndividual > 0" variant="secondary" class="ml-1 text-xs">
                            {{ contadores.waIndividual }}
                        </Badge>
                    </Button>

                    <!-- Filtro por Evolution Grupos (WhatsApp grupos) -->
                    <Button
                        v-if="tipo !== 'email'"
                        :variant="filtroServicio === 'evolution_group' ? 'default' : 'outline'"
                        size="sm"
                        @click="filtroServicio = filtroServicio === 'evolution_group' ? 'todos' : 'evolution_group'"
                    >
                        <Users class="w-4 h-4 mr-1" />
                        WA Grupos
                        <Badge v-if="contadores.waGrupo > 0" variant="secondary" class="ml-1 text-xs">
                            {{ contadores.waGrupo }}
                        </Badge>
                    </Button>

                    <!-- Botón refrescar -->
                    <Button variant="ghost" size="sm" @click="cargarLogs" :disabled="isLoading">
                        <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isLoading }" />
                    </Button>
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
            <div v-if="isLoading && logs.length === 0" class="text-center py-8 text-muted-foreground">
                Cargando logs...
            </div>

            <!-- Sin datos -->
            <div v-else-if="logsFiltrados.length === 0" class="text-center py-8 text-muted-foreground">
                No hay logs de envío disponibles
            </div>

            <!-- Tabla de logs -->
            <div v-else class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[100px]">Servicio</TableHead>
                            <TableHead>Destinatario</TableHead>
                            <TableHead class="w-[100px]">Estado</TableHead>
                            <TableHead>Fecha</TableHead>
                            <TableHead>ID Mensaje</TableHead>
                            <TableHead class="w-[80px]">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="log in logsFiltrados" :key="log.id">
                            <!-- Servicio -->
                            <TableCell>
                                <Badge :variant="log.servicio === 'resend' ? 'default' : 'secondary'">
                                    <Mail v-if="log.servicio === 'resend'" class="w-3 h-3 mr-1" />
                                    <Users v-else-if="log.servicio === 'evolution_group'" class="w-3 h-3 mr-1" />
                                    <MessageSquare v-else class="w-3 h-3 mr-1" />
                                    {{ log.servicio === 'resend' ? 'Email' :
                                       log.servicio === 'evolution_group' ? 'Grupo' : 'WA' }}
                                </Badge>
                            </TableCell>

                            <!-- Destinatario -->
                            <TableCell class="max-w-[200px]">
                                <!-- Para grupos, mostrar nombre del grupo -->
                                <div v-if="log.grupo?.nombre" class="truncate">
                                    <div class="font-medium text-green-700">{{ log.grupo.nombre }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ log.grupo.participantes }} participantes
                                    </div>
                                </div>
                                <div v-else class="truncate" :title="log.destinatario">
                                    {{ log.destinatario }}
                                </div>
                            </TableCell>

                            <!-- Estado -->
                            <TableCell>
                                <Badge :variant="getEstadoVariant(log.estado)">
                                    <component :is="getEstadoIcon(log.estado)" class="w-3 h-3 mr-1" />
                                    {{ log.estado }}
                                </Badge>
                            </TableCell>

                            <!-- Fecha -->
                            <TableCell class="text-sm">
                                {{ formatDate(log.fecha) }}
                            </TableCell>

                            <!-- ID Mensaje -->
                            <TableCell class="font-mono text-xs max-w-[150px] truncate" :title="log.mensaje_id || ''">
                                {{ log.mensaje_id || '-' }}
                            </TableCell>

                            <!-- Acciones -->
                            <TableCell>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="verDetalles(log)"
                                >
                                    <Eye class="w-4 h-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </CardContent>
    </Card>

    <!-- Dialog de detalles -->
    <Dialog v-model:open="showDetailDialog">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>Detalles del Envío</DialogTitle>
                <DialogDescription>
                    Información completa del registro de envío
                </DialogDescription>
            </DialogHeader>

            <div v-if="selectedLog" class="space-y-4">
                <!-- Info básica -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-muted-foreground">Servicio</p>
                        <Badge :variant="selectedLog.servicio === 'resend' ? 'default' : 'secondary'">
                            <Mail v-if="selectedLog.servicio === 'resend'" class="w-3 h-3 mr-1" />
                            <Users v-else-if="selectedLog.servicio === 'evolution_group'" class="w-3 h-3 mr-1" />
                            <MessageSquare v-else class="w-3 h-3 mr-1" />
                            {{ selectedLog.servicio === 'resend' ? 'Email (Resend)' :
                               selectedLog.servicio === 'evolution_group' ? 'WA Grupo' : 'WA Individual' }}
                        </Badge>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Estado</p>
                        <Badge :variant="getEstadoVariant(selectedLog.estado)">
                            {{ selectedLog.estado }}
                        </Badge>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Destinatario</p>
                        <p class="font-mono text-sm">{{ selectedLog.destinatario }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Fecha de Envío</p>
                        <p class="text-sm">{{ formatDate(selectedLog.fecha) }}</p>
                    </div>
                </div>

                <!-- Info del grupo (si aplica) -->
                <div v-if="selectedLog.grupo?.nombre" class="p-3 bg-green-50 dark:bg-green-950 rounded-lg">
                    <p class="text-sm text-muted-foreground mb-2">Información del Grupo</p>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-muted-foreground">Nombre:</span>
                            <span class="ml-2 font-medium">{{ selectedLog.grupo.nombre }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Participantes:</span>
                            <span class="ml-2 font-medium">{{ selectedLog.grupo.participantes }}</span>
                        </div>
                    </div>
                </div>

                <!-- ID del mensaje -->
                <div v-if="selectedLog.mensaje_id">
                    <p class="text-sm text-muted-foreground">ID del Mensaje</p>
                    <p class="font-mono text-xs bg-muted p-2 rounded">{{ selectedLog.mensaje_id }}</p>
                </div>

                <!-- Error -->
                <div v-if="selectedLog.error">
                    <p class="text-sm text-muted-foreground">Error</p>
                    <Alert variant="destructive">
                        <AlertCircle class="h-4 w-4" />
                        <AlertDescription>{{ selectedLog.error }}</AlertDescription>
                    </Alert>
                </div>

                <!-- Metadata -->
                <div v-if="selectedLog.metadata && Object.keys(selectedLog.metadata).length > 0">
                    <p class="text-sm text-muted-foreground mb-2">Metadata</p>
                    <pre class="bg-muted p-3 rounded text-xs overflow-auto max-h-[200px]">{{ JSON.stringify(selectedLog.metadata, null, 2) }}</pre>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
