<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
// @ts-ignore
const route = window.route;
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Textarea } from "@modules/Core/Resources/js/components/ui/textarea";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { Alert, AlertDescription } from "@modules/Core/Resources/js/components/ui/alert";
import { Separator } from "@modules/Core/Resources/js/components/ui/separator";
import { 
    ArrowLeft,
    Check,
    X as XIcon,
    Download,
    User,
    Mail,
    Phone,
    FileText,
    Calendar,
    Clock,
    AlertCircle,
    CheckCircle,
    XCircle,
    MapPin
} from 'lucide-vue-next';
import { format, formatDistanceToNow } from 'date-fns';
import { es } from 'date-fns/locale';
import { toast } from 'vue-sonner';

interface UpdateRequest {
    id: number;
    user: {
        id: number;
        name: string;
        email: string;
        telefono: string | null;
        documento_identidad: string;
        territorio?: { id: number; nombre: string } | null;
        departamento?: { id: number; nombre: string } | null;
        municipio?: { id: number; nombre: string } | null;
        localidad?: { id: number; nombre: string } | null;
    };
    new_email: string | null;
    new_telefono: string | null;
    new_territorio?: { id: number; nombre: string } | null;
    new_departamento?: { id: number; nombre: string } | null;
    new_municipio?: { id: number; nombre: string } | null;
    new_localidad?: { id: number; nombre: string } | null;
    current_email: string;
    current_telefono: string | null;
    current_territorio_id?: number | null;
    current_departamento_id?: number | null;
    current_municipio_id?: number | null;
    current_localidad_id?: number | null;
    documentos_soporte: Array<{
        path: string;
        url: string;
        name: string;
        size: number;
        mime_type: string;
        exists: boolean;
    }>;
    status: 'pending' | 'approved' | 'rejected';
    admin: {
        id: number;
        name: string;
    } | null;
    admin_notes: string | null;
    approved_at: string | null;
    rejected_at: string | null;
    created_at: string;
    changes_summary: {
        email?: {
            current: string;
            new: string;
        };
        telefono?: {
            current: string | null;
            new: string;
        };
        ubicacion?: {
            current: {
                territorio_id?: number | null;
                departamento_id?: number | null;
                municipio_id?: number | null;
                localidad_id?: number | null;
            };
            new: {
                territorio_id?: number | null;
                territorio_nombre?: string | null;
                departamento_id?: number | null;
                departamento_nombre?: string | null;
                municipio_id?: number | null;
                municipio_nombre?: string | null;
                localidad_id?: number | null;
                localidad_nombre?: string | null;
            };
        };
    };
    has_changes: boolean;
}

const props = defineProps<{
    updateRequest: UpdateRequest;
}>();

// Estados
const isProcessing = ref(false);
const adminNotes = ref('');
const showApproveDialog = ref(false);
const showRejectDialog = ref(false);

// Aprobar solicitud
const approveRequest = () => {
    console.log('[DEBUG] Iniciando aprobación', {
        id: props.updateRequest.id,
        notes: adminNotes.value,
        status: props.updateRequest.status
    });
    
    if (isProcessing.value) return;
    
    isProcessing.value = true;
    
    const url = route('admin.update-requests.approve', { updateRequest: props.updateRequest.id });
    console.log('[DEBUG] URL de aprobación:', url);
    
    router.post(url, {
        notes: adminNotes.value || ''
    }, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: (page: any) => {
            console.log('[DEBUG] Aprobación exitosa', page);
            toast.success('Solicitud aprobada correctamente');
            showApproveDialog.value = false;
        },
        onError: (errors: any) => {
            console.error('[DEBUG] Error en aprobación:', errors);
            toast.error('Error al aprobar la solicitud');
        },
        onFinish: () => {
            console.log('[DEBUG] Finalizó proceso de aprobación');
            isProcessing.value = false;
        }
    });
};

// Rechazar solicitud
const rejectRequest = () => {
    console.log('[DEBUG] Iniciando rechazo', {
        id: props.updateRequest.id,
        notes: adminNotes.value,
        status: props.updateRequest.status
    });
    
    if (!adminNotes.value.trim()) {
        toast.error('Debes proporcionar un motivo para rechazar');
        return;
    }
    
    if (isProcessing.value) return;
    
    isProcessing.value = true;
    
    const url = route('admin.update-requests.reject', { updateRequest: props.updateRequest.id });
    console.log('[DEBUG] URL de rechazo:', url);
    
    router.post(url, {
        notes: adminNotes.value
    }, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: (page: any) => {
            console.log('[DEBUG] Rechazo exitoso', page);
            toast.success('Solicitud rechazada');
            showRejectDialog.value = false;
        },
        onError: (errors: any) => {
            console.error('[DEBUG] Error en rechazo:', errors);
            toast.error('Error al rechazar la solicitud');
        },
        onFinish: () => {
            console.log('[DEBUG] Finalizó proceso de rechazo');
            isProcessing.value = false;
        }
    });
};

// Descargar documento
const downloadDocument = (path: string) => {
    const url = route('admin.update-requests.download', { 
        updateRequest: props.updateRequest.id,
        path: path
    });
    window.open(url, '_blank');
};

// Volver a la lista
const goBack = () => {
    router.visit('/admin/solicitudes-actualizacion');
};

// Formatear fecha
const formatDate = (date: string | null) => {
    if (!date) return '-';
    return format(new Date(date), 'dd MMM yyyy HH:mm', { locale: es });
};

// Formatear tiempo relativo
const formatRelative = (date: string) => {
    return formatDistanceToNow(new Date(date), { 
        locale: es, 
        addSuffix: true 
    });
};

// Formatear tamaño de archivo
const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

// Obtener color del estado
const getStatusColor = (status: string) => {
    switch (status) {
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'rejected': return 'destructive';
        default: return 'secondary';
    }
};

// Obtener texto del estado
const getStatusText = (status: string) => {
    switch (status) {
        case 'pending': return 'Pendiente';
        case 'approved': return 'Aprobada';
        case 'rejected': return 'Rechazada';
        default: return status;
    }
};

// Obtener icono del estado
const getStatusIcon = (status: string) => {
    switch (status) {
        case 'pending': return Clock;
        case 'approved': return CheckCircle;
        case 'rejected': return XCircle;
        default: return AlertCircle;
    }
};
</script>

<template>
    <AdminLayout>
        <Head :title="`Solicitud #${updateRequest.id}`" />
        
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Solicitud #{{ updateRequest.id }}</h1>
                    <p class="text-muted-foreground">
                        Creada {{ formatRelative(updateRequest.created_at) }}
                    </p>
                </div>
                
                <Badge :variant="getStatusColor(updateRequest.status)" class="text-lg px-4 py-2">
                    <component :is="getStatusIcon(updateRequest.status)" class="mr-2 h-4 w-4" />
                    {{ getStatusText(updateRequest.status) }}
                </Badge>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Columna principal -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Información del usuario -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <User class="h-5 w-5" />
                                Información del Usuario
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label class="text-muted-foreground">Nombre</Label>
                                    <p class="font-semibold">{{ updateRequest.user.name }}</p>
                                </div>
                                <div>
                                    <Label class="text-muted-foreground">Documento</Label>
                                    <p class="font-semibold">{{ updateRequest.user.documento_identidad }}</p>
                                </div>
                                <div>
                                    <Label class="text-muted-foreground">Email Actual</Label>
                                    <p class="font-semibold">{{ updateRequest.current_email || '-' }}</p>
                                </div>
                                <div>
                                    <Label class="text-muted-foreground">Teléfono Actual</Label>
                                    <p class="font-semibold">{{ updateRequest.current_telefono || '-' }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Cambios solicitados -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Cambios Solicitados</CardTitle>
                            <CardDescription>
                                {{ updateRequest.has_changes ? 'Cambios detectados en los siguientes campos' : 'No hay cambios de datos' }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="updateRequest.has_changes" class="space-y-4">
                                <!-- Cambio de email -->
                                <div v-if="updateRequest.changes_summary.email" class="flex items-start gap-3">
                                    <Mail class="h-5 w-5 text-muted-foreground mt-0.5" />
                                    <div class="flex-1 space-y-2">
                                        <Label>Email</Label>
                                        <div class="grid gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm text-muted-foreground">Actual:</span>
                                                <span class="font-mono text-sm">{{ updateRequest.changes_summary.email.current || 'No registrado' }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm text-muted-foreground">Nuevo:</span>
                                                <span class="font-mono text-sm font-semibold text-green-600">{{ updateRequest.changes_summary.email.new }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cambio de teléfono -->
                                <div v-if="updateRequest.changes_summary.telefono" class="flex items-start gap-3">
                                    <Phone class="h-5 w-5 text-muted-foreground mt-0.5" />
                                    <div class="flex-1 space-y-2">
                                        <Label>Teléfono</Label>
                                        <div class="grid gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm text-muted-foreground">Actual:</span>
                                                <span class="font-mono text-sm">{{ updateRequest.changes_summary.telefono.current || 'No registrado' }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm text-muted-foreground">Nuevo:</span>
                                                <span class="font-mono text-sm font-semibold text-green-600">{{ updateRequest.changes_summary.telefono.new }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cambio de ubicación -->
                                <div v-if="updateRequest.changes_summary.ubicacion" class="flex items-start gap-3">
                                    <MapPin class="h-5 w-5 text-muted-foreground mt-0.5" />
                                    <div class="flex-1 space-y-2">
                                        <Label>Ubicación de Residencia</Label>
                                        <div class="grid gap-2">
                                            <div class="flex items-start gap-2">
                                                <span class="text-sm text-muted-foreground min-w-[60px]">Actual:</span>
                                                <span class="font-mono text-sm">
                                                    {{ 
                                                        [
                                                            updateRequest.user.departamento?.nombre,
                                                            updateRequest.user.municipio?.nombre,
                                                            updateRequest.user.localidad?.nombre
                                                        ].filter(Boolean).join(', ') || 'No registrada'
                                                    }}
                                                </span>
                                            </div>
                                            <div class="flex items-start gap-2">
                                                <span class="text-sm text-muted-foreground min-w-[60px]">Nueva:</span>
                                                <span class="font-mono text-sm font-semibold text-green-600">
                                                    {{ 
                                                        [
                                                            updateRequest.changes_summary.ubicacion.new.departamento_nombre,
                                                            updateRequest.changes_summary.ubicacion.new.municipio_nombre,
                                                            updateRequest.changes_summary.ubicacion.new.localidad_nombre
                                                        ].filter(Boolean).join(', ')
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else>
                                <Alert>
                                    <AlertCircle class="h-4 w-4" />
                                    <AlertDescription>
                                        Esta solicitud no incluye cambios en los datos de contacto, solo documentación de soporte.
                                    </AlertDescription>
                                </Alert>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Documentos adjuntos -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="h-5 w-5" />
                                Documentos de Soporte
                            </CardTitle>
                            <CardDescription>
                                {{ updateRequest.documentos_soporte?.length || 0 }} documento(s) adjunto(s)
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="updateRequest.documentos_soporte?.length > 0" class="space-y-2">
                                <div 
                                    v-for="(doc, index) in updateRequest.documentos_soporte" 
                                    :key="index"
                                    class="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50 transition-colors"
                                >
                                    <div class="flex items-center gap-3">
                                        <FileText class="h-5 w-5 text-muted-foreground" />
                                        <div>
                                            <p class="font-medium">{{ doc.name }}</p>
                                            <p class="text-sm text-muted-foreground">
                                                {{ formatFileSize(doc.size) }} • {{ doc.mime_type }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Badge v-if="!doc.exists" variant="destructive" class="text-xs">
                                            No disponible
                                        </Badge>
                                        <Button 
                                            v-if="doc.exists"
                                            @click="downloadDocument(doc.path)"
                                            size="sm"
                                            variant="outline"
                                        >
                                            <Download class="h-4 w-4 mr-1" />
                                            Descargar
                                        </Button>
                                    </div>
                                </div>
                            </div>
                            <div v-else>
                                <p class="text-sm text-muted-foreground text-center py-4">
                                    No se adjuntaron documentos
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Columna lateral -->
                <div class="space-y-6">
                    <!-- Timeline -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Calendar class="h-5 w-5" />
                                Historial
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div class="flex items-start gap-3">
                                    <div class="h-2 w-2 rounded-full bg-blue-500 mt-2"></div>
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold">Solicitud creada</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDate(updateRequest.created_at) }}
                                        </p>
                                    </div>
                                </div>
                                
                                <div v-if="updateRequest.approved_at" class="flex items-start gap-3">
                                    <div class="h-2 w-2 rounded-full bg-green-500 mt-2"></div>
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold">Aprobada</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDate(updateRequest.approved_at) }}
                                        </p>
                                        <p v-if="updateRequest.admin" class="text-xs text-muted-foreground">
                                            Por: {{ updateRequest.admin.name }}
                                        </p>
                                    </div>
                                </div>
                                
                                <div v-if="updateRequest.rejected_at" class="flex items-start gap-3">
                                    <div class="h-2 w-2 rounded-full bg-red-500 mt-2"></div>
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold">Rechazada</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDate(updateRequest.rejected_at) }}
                                        </p>
                                        <p v-if="updateRequest.admin" class="text-xs text-muted-foreground">
                                            Por: {{ updateRequest.admin.name }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Notas del admin -->
                    <Card v-if="updateRequest.admin_notes">
                        <CardHeader>
                            <CardTitle>Notas del Administrador</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm whitespace-pre-wrap">{{ updateRequest.admin_notes }}</p>
                        </CardContent>
                    </Card>

                    <!-- Acciones -->
                    <Card v-if="updateRequest.status === 'pending'">
                        <CardHeader>
                            <CardTitle>Acciones</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label for="notes">Notas (opcional)</Label>
                                <Textarea
                                    id="notes"
                                    v-model="adminNotes"
                                    placeholder="Agregar notas sobre esta decisión..."
                                    rows="3"
                                    class="mt-1"
                                />
                            </div>
                            
                            <Separator />
                            
                            <div class="space-y-2">
                                <Button 
                                    @click="approveRequest"
                                    :disabled="isProcessing"
                                    class="w-full"
                                    variant="default"
                                >
                                    <Check class="mr-2 h-4 w-4" />
                                    Aprobar Solicitud
                                </Button>
                                
                                <Button 
                                    @click="rejectRequest"
                                    :disabled="isProcessing"
                                    class="w-full"
                                    variant="destructive"
                                >
                                    <XIcon class="mr-2 h-4 w-4" />
                                    Rechazar Solicitud
                                </Button>
                            </div>
                            
                            <Alert>
                                <AlertCircle class="h-4 w-4" />
                                <AlertDescription>
                                    {{ updateRequest.has_changes 
                                        ? 'Al aprobar, los datos del usuario serán actualizados automáticamente.' 
                                        : 'Esta solicitud solo actualiza el estado de verificación del usuario.'
                                    }}
                                </AlertDescription>
                            </Alert>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>