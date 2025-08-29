<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItemType } from '@/types';
// @ts-ignore
const route = window.route;
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { 
    Table, 
    TableBody, 
    TableCell, 
    TableHead, 
    TableHeader, 
    TableRow 
} from '@/components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { 
    DropdownMenu, 
    DropdownMenuContent, 
    DropdownMenuItem, 
    DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    Search, 
    Filter, 
    Download, 
    Eye, 
    Check, 
    X as XIcon, 
    MoreVertical,
    FileText,
    Users,
    Clock,
    CheckCircle,
    ChevronLeft,
    ChevronRight
} from 'lucide-vue-next';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { toast } from 'vue-sonner';
import { debounce } from 'lodash';

interface UpdateRequest {
    id: number;
    user: {
        id: number;
        name: string;
        email: string;
        documento_identidad: string;
    };
    new_email: string | null;
    new_telefono: string | null;
    new_territorio?: { id: number; nombre: string } | null;
    new_departamento?: { id: number; nombre: string } | null;
    new_municipio?: { id: number; nombre: string } | null;
    new_localidad?: { id: number; nombre: string } | null;
    documentos_soporte: any[];
    status: 'pending' | 'approved' | 'rejected';
    admin?: {
        id: number;
        name: string;
    };
    admin_notes: string | null;
    created_at: string;
    approved_at: string | null;
    rejected_at: string | null;
}

interface Stats {
    total: number;
    pending: number;
    approved: number;
    rejected: number;
}

const props = defineProps<{
    requests: {
        data: UpdateRequest[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from?: number;
        to?: number;
    };
    stats: Stats;
    filters: {
        status?: string;
        has_documents?: string;
        date_from?: string;
        date_to?: string;
        search?: string;
    };
}>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Usuarios', href: '/admin/usuarios' },
    { title: 'Solicitudes de Actualización', href: '/admin/solicitudes-actualizacion' },
];

// Estados
const localFilters = ref({
    status: props.filters.status || 'all',
    has_documents: props.filters.has_documents || 'all',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    search: props.filters.search || ''
});

// Aplicar filtros con debounce
const applyFilters = debounce(() => {
    // Filtrar valores 'all' antes de enviar
    const filters = Object.entries(localFilters.value).reduce((acc, [key, value]) => {
        if (value && value !== 'all') {
            acc[key] = value;
        }
        return acc;
    }, {} as any);
    
    router.get('/admin/solicitudes-actualizacion', {
        ...filters,
        page: 1
    }, {
        preserveState: true,
        preserveScroll: true
    });
}, 300);

// Watch para cambios en filtros
watch(() => localFilters.value, () => {
    applyFilters();
}, { deep: true });

// Limpiar filtros
const clearFilters = () => {
    localFilters.value = {
        status: 'all',
        has_documents: 'all',
        date_from: '',
        date_to: '',
        search: ''
    };
};

// Navegar a página
const changePage = (page: number) => {
    router.get('/admin/solicitudes-actualizacion', {
        ...localFilters.value,
        page
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

// Ver detalle
const viewRequest = (id: number) => {
    router.visit(`/admin/solicitudes-actualizacion/${id}`);
};

// Aprobar solicitud rápida
const quickApprove = (id: number) => {
    if (!confirm('¿Aprobar esta solicitud de actualización?')) return;
    
    router.post(route('admin.update-requests.approve', { updateRequest: id }), {
        notes: 'Aprobado desde lista'
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Solicitud aprobada correctamente');
        },
        onError: (errors: any) => {
            console.error('Error:', errors);
            toast.error('Error al aprobar la solicitud');
        }
    });
};

// Rechazar solicitud rápida
const quickReject = (id: number) => {
    const reason = prompt('Motivo del rechazo:');
    if (!reason) return;
    
    router.post(route('admin.update-requests.reject', { updateRequest: id }), {
        notes: reason
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Solicitud rechazada');
        },
        onError: (errors: any) => {
            console.error('Error:', errors);
            toast.error('Error al rechazar la solicitud');
        }
    });
};

// Exportar CSV
const exportCsv = () => {
    window.location.href = '/admin/solicitudes-actualizacion/export/csv?' + 
        new URLSearchParams(localFilters.value as any).toString();
};

// Formatear fecha
const formatDate = (date: string | null) => {
    if (!date) return '-';
    return format(new Date(date), 'dd MMM yyyy HH:mm', { locale: es });
};

// Obtener color de badge según estado
const getStatusColor = (status: string) => {
    switch (status) {
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'rejected': return 'destructive';
        default: return 'secondary';
    }
};

// Obtener texto de estado
const getStatusText = (status: string) => {
    switch (status) {
        case 'pending': return 'Pendiente';
        case 'approved': return 'Aprobada';
        case 'rejected': return 'Rechazada';
        default: return status;
    }
};
</script>

<template>
    <Head title="Solicitudes de Actualización" />
    
    <AdminLayout :breadcrumbs="breadcrumbs">
        
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Solicitudes de Actualización</h1>
                    <p class="text-muted-foreground">
                        Gestiona las solicitudes de actualización de datos de usuarios
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button @click="exportCsv" variant="outline">
                        <Download class="mr-2 h-4 w-4" />
                        Exportar CSV
                    </Button>
                </div>
            </div>
            
            <!-- Estadísticas -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex flex-row items-center justify-between space-y-0">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Total Solicitudes</p>
                                <p class="text-2xl font-bold">{{ stats.total }}</p>
                            </div>
                            <Users class="h-4 w-4 text-muted-foreground" />
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex flex-row items-center justify-between space-y-0">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Pendientes</p>
                                <p class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</p>
                            </div>
                            <Clock class="h-4 w-4 text-yellow-500" />
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex flex-row items-center justify-between space-y-0">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Aprobadas</p>
                                <p class="text-2xl font-bold text-green-600">{{ stats.approved }}</p>
                            </div>
                            <CheckCircle class="h-4 w-4 text-green-500" />
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex flex-row items-center justify-between space-y-0">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Rechazadas</p>
                                <p class="text-2xl font-bold text-red-600">{{ stats.rejected }}</p>
                            </div>
                            <XIcon class="h-4 w-4 text-red-500" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filtros -->
            <Card>
                <CardContent class="pt-6">
                    <div class="grid gap-4 md:grid-cols-5">
                        <div>
                            <Input
                                v-model="localFilters.search"
                                placeholder="Buscar por nombre/documento..."
                                class="w-full"
                            >
                                <template #prefix>
                                    <Search class="h-4 w-4 text-muted-foreground" />
                                </template>
                            </Input>
                        </div>
                        
                        <Select v-model="localFilters.status">
                            <SelectTrigger>
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="pending">Pendiente</SelectItem>
                                <SelectItem value="approved">Aprobada</SelectItem>
                                <SelectItem value="rejected">Rechazada</SelectItem>
                            </SelectContent>
                        </Select>
                        
                        <Select v-model="localFilters.has_documents">
                            <SelectTrigger>
                                <SelectValue placeholder="Documentos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="true">Con documentos</SelectItem>
                                <SelectItem value="false">Sin documentos</SelectItem>
                            </SelectContent>
                        </Select>
                        
                        <Input
                            v-model="localFilters.date_from"
                            type="date"
                            placeholder="Fecha desde"
                        />
                        
                        <Input
                            v-model="localFilters.date_to"
                            type="date"
                            placeholder="Fecha hasta"
                        />
                    </div>
                    
                    <div class="mt-4">
                        <Button @click="clearFilters" variant="outline" size="sm">
                            Limpiar filtros
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Tabla de solicitudes -->
            <Card>
                <CardContent class="pt-6">
                    <div v-if="requests.data.length === 0" class="text-center py-8">
                        <p class="text-muted-foreground">No se encontraron solicitudes</p>
                    </div>
                    
                    <div v-else class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>#</TableHead>
                                    <TableHead>Usuario</TableHead>
                                    <TableHead>Cambios</TableHead>
                                    <TableHead>Documentos</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead>Admin</TableHead>
                                    <TableHead class="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="request in requests.data" :key="request.id">
                                    <TableCell class="font-medium">{{ request.id }}</TableCell>
                                    <TableCell>
                                        <div>
                                            <p class="font-semibold">{{ request.user.name }}</p>
                                            <p class="text-sm text-muted-foreground">
                                                {{ request.user.documento_identidad }}
                                            </p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="space-y-1">
                                            <div v-if="request.new_email" class="text-sm">
                                                📧 {{ request.new_email }}
                                            </div>
                                            <div v-if="request.new_telefono" class="text-sm">
                                                📱 {{ request.new_telefono }}
                                            </div>
                                            <div v-if="request.new_municipio" class="text-sm">
                                                📍 {{ [request.new_departamento?.nombre, request.new_municipio?.nombre].filter(Boolean).join(', ') }}
                                            </div>
                                            <div v-if="!request.new_email && !request.new_telefono && !request.new_municipio" class="text-sm text-muted-foreground">
                                                Sin cambios
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge v-if="request.documentos_soporte?.length > 0">
                                            <FileText class="mr-1 h-3 w-3" />
                                            {{ request.documentos_soporte.length }}
                                        </Badge>
                                        <span v-else class="text-sm text-muted-foreground">-</span>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusColor(request.status)">
                                            {{ getStatusText(request.status) }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <span class="text-sm">{{ formatDate(request.created_at) }}</span>
                                    </TableCell>
                                    <TableCell>
                                        <span v-if="request.admin" class="text-sm">
                                            {{ request.admin.name }}
                                        </span>
                                        <span v-else class="text-sm text-muted-foreground">-</span>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon">
                                                    <MoreVertical class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem @click="viewRequest(request.id)">
                                                    <Eye class="mr-2 h-4 w-4" />
                                                    Ver detalle
                                                </DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    v-if="request.status === 'pending'"
                                                    @click="quickApprove(request.id)"
                                                    class="text-green-600"
                                                >
                                                    <Check class="mr-2 h-4 w-4" />
                                                    Aprobar
                                                </DropdownMenuItem>
                                                <DropdownMenuItem 
                                                    v-if="request.status === 'pending'"
                                                    @click="quickReject(request.id)"
                                                    class="text-red-600"
                                                >
                                                    <XIcon class="mr-2 h-4 w-4" />
                                                    Rechazar
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    
                    <!-- Paginación -->
                    <div v-if="requests.last_page > 1" class="flex items-center justify-between mt-4">
                        <div class="text-sm text-muted-foreground">
                            Mostrando {{ requests.from || 0 }} a {{ requests.to || 0 }} de {{ requests.total }} resultados
                        </div>
                        <div class="flex items-center space-x-2">
                            <Button 
                                @click="changePage(requests.current_page - 1)"
                                :disabled="requests.current_page === 1"
                                variant="outline"
                                size="sm"
                            >
                                <ChevronLeft class="h-4 w-4" />
                                Anterior
                            </Button>
                            <div class="text-sm">
                                Página {{ requests.current_page }} de {{ requests.last_page }}
                            </div>
                            <Button 
                                @click="changePage(requests.current_page + 1)"
                                :disabled="requests.current_page === requests.last_page"
                                variant="outline"
                                size="sm"
                            >
                                Siguiente
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>