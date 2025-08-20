<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle, Clock, AlertCircle, XCircle, ArrowLeft, FileText, Users, AlertTriangle } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';

interface CsvImport {
    id: number;
    votacion_id?: number;
    filename: string;
    original_filename: string;
    name?: string;
    import_type?: 'votacion' | 'users' | 'general';
    import_mode?: 'insert' | 'update' | 'both';
    status: 'pending' | 'processing' | 'completed' | 'failed';
    total_rows: number;
    processed_rows: number;
    successful_rows: number;
    failed_rows: number;
    errors: string[];
    conflict_resolution?: any[];
    progress_percentage: number;
    duration: string | null;
    started_at: string | null;
    completed_at: string | null;
    motivo: string | null;
    votacion?: {
        id: number;
        titulo: string;
    };
    created_by: {
        name: string;
    };
}

interface Props {
    import: CsvImport;
}

const props = defineProps<Props>();

const importData = ref<CsvImport>(props.import);
let pollingInterval: NodeJS.Timeout | null = null;

const breadcrumbs: BreadcrumbItemType[] = importData.value.import_type === 'users' ? [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Usuarios', href: '/admin/usuarios' },
    { title: 'Importaciones', href: '/admin/imports' },
    { title: 'Progreso', href: '#' },
] : [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Votaciones', href: '/admin/votaciones' },
    { title: importData.value.votacion?.titulo || 'Votación', href: `/admin/votaciones/${importData.value.votacion_id}/edit` },
    { title: 'Progreso de Importación', href: '#' },
];

// Estados computados
const statusConfig = computed(() => {
    switch (importData.value.status) {
        case 'pending':
            return {
                icon: Clock,
                color: 'bg-yellow-500',
                textColor: 'text-yellow-700',
                bgColor: 'bg-yellow-50',
                label: 'Pendiente',
                description: 'La importación está en cola para ser procesada'
            };
        case 'processing':
            return {
                icon: Clock,
                color: 'bg-blue-500',
                textColor: 'text-blue-700',
                bgColor: 'bg-blue-50',
                label: 'Procesando',
                description: 'La importación está siendo procesada en lotes'
            };
        case 'completed':
            return {
                icon: CheckCircle,
                color: 'bg-green-500',
                textColor: 'text-green-700',
                bgColor: 'bg-green-50',
                label: 'Completada',
                description: 'La importación se completó exitosamente'
            };
        case 'failed':
            return {
                icon: XCircle,
                color: 'bg-red-500',
                textColor: 'text-red-700',
                bgColor: 'bg-red-50',
                label: 'Fallida',
                description: 'La importación falló durante el procesamiento'
            };
        default:
            return {
                icon: AlertCircle,
                color: 'bg-gray-500',
                textColor: 'text-gray-700',
                bgColor: 'bg-gray-50',
                label: 'Desconocido',
                description: 'Estado desconocido'
            };
    }
});

const isActive = computed(() => {
    return ['pending', 'processing'].includes(importData.value.status);
});

const showErrors = ref(false);
const resolvingConflict = ref(false);
const showMergeModal = ref(false);
const selectedConflict = ref<any>(null);
const mergeSelections = ref<Record<string, 'csv' | 'existing'>>({});

// Conflictos computados
const conflicts = computed(() => {
    return importData.value.conflict_resolution?.filter(c => !c.resolved) || [];
});

const resolvedConflicts = computed(() => {
    return importData.value.conflict_resolution?.filter(c => c.resolved) || [];
});

const hasConflicts = computed(() => {
    return conflicts.value.length > 0;
});

// Configuración de tipos de conflicto
const getConflictTypeConfig = (type: string) => {
    switch (type) {
        case 'email_document_mismatch':
            return {
                label: 'Email y documento pertenecen a usuarios diferentes',
                icon: AlertTriangle,
                color: 'text-red-600'
            };
        case 'document_exists_different_email':
            return {
                label: 'Documento existe con email diferente',
                icon: AlertTriangle,
                color: 'text-orange-600'
            };
        case 'email_exists_different_document':
            return {
                label: 'Email existe con documento diferente',
                icon: AlertTriangle,
                color: 'text-orange-600'
            };
        default:
            return {
                label: 'Conflicto desconocido',
                icon: AlertCircle,
                color: 'text-gray-600'
            };
    }
};

// Función para actualizar el estado
const updateStatus = async () => {
    try {
        const response = await fetch(`/admin/imports/${importData.value.id}/status`);
        if (response.ok) {
            const data = await response.json();
            
            // Detectar cambio de estado a completado
            const wasProcessing = importData.value.status === 'processing';
            const isNowCompleted = data.status === 'completed';
            
            // Actualizar datos
            importData.value = { ...importData.value, ...data };
            
            // Si acaba de completarse, cargar los conflictos inmediatamente
            if (wasProcessing && isNowCompleted) {
                console.log('Importación completada. Conflictos recibidos:', data.conflict_resolution);
                
                if (data.conflict_resolution && data.conflict_resolution.length > 0) {
                    importData.value.conflict_resolution = data.conflict_resolution;
                    console.log(`✓ ${data.conflict_resolution.length} conflictos detectados y cargados para resolver`);
                } else {
                    console.log('✓ Importación completada sin conflictos');
                }
            }
            
            // Detener polling si ya no está activo Y no hay conflictos pendientes
            const hasUnresolvedConflicts = data.conflict_resolution?.some(c => !c.resolved);
            if (!isActive.value && !hasUnresolvedConflicts && pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
        }
    } catch (error) {
        console.error('Error updating status:', error);
    }
};

// Iniciar polling
const startPolling = () => {
    // Continuar polling si está procesando O si hay conflictos pendientes
    const hasUnresolvedConflicts = importData.value.conflict_resolution?.some(c => !c.resolved);
    if (isActive.value || hasUnresolvedConflicts) {
        pollingInterval = setInterval(updateStatus, 2000); // Cada 2 segundos
    }
};

// Navegar de vuelta
const goBack = () => {
    if (importData.value.import_type === 'users') {
        router.get('/admin/imports');
    } else if (importData.value.votacion_id) {
        router.get(`/admin/votaciones/${importData.value.votacion_id}/edit`);
    } else {
        router.get('/admin/usuarios');
    }
};

// Ver historial completo
const viewAllImports = () => {
    if (importData.value.import_type === 'users') {
        router.get('/admin/imports');
    } else if (importData.value.votacion_id) {
        router.get(`/admin/votaciones/${importData.value.votacion_id}/imports`);
    }
};

// Resolver conflicto
const resolveConflict = async (conflictId: string, resolution: string) => {
    if (resolvingConflict.value) return;
    
    resolvingConflict.value = true;
    
    try {
        const response = await fetch(`/admin/imports/${importData.value.id}/resolve-conflict`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                conflict_id: conflictId,
                resolution: resolution,
            }),
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // Marcar el conflicto como resuelto localmente para reactividad inmediata
            const conflictIndex = importData.value.conflict_resolution?.findIndex(c => c.id === conflictId);
            if (conflictIndex !== undefined && conflictIndex >= 0 && importData.value.conflict_resolution) {
                importData.value.conflict_resolution[conflictIndex].resolved = true;
            }
            
            // Actualizar los datos de la importación desde el servidor
            await updateStatus();
            
            // Mostrar mensaje de éxito
            console.log('Conflicto resuelto:', data.message);
        } else {
            alert(data.error || 'Error al resolver conflicto');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al resolver conflicto');
    } finally {
        resolvingConflict.value = false;
    }
};

// Funciones del modal de fusión
const openMergeModal = async (conflict: any) => {
    try {
        // Obtener datos actualizados del usuario antes de abrir el modal
        const response = await fetch(`/admin/imports/${importData.value.id}/refresh-conflict-data`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                conflict_id: conflict.id,
            }),
        });
        
        if (response.ok) {
            const data = await response.json();
            // Usar el conflicto con datos actualizados
            selectedConflict.value = data.conflict;
        } else {
            // Si falla, usar los datos originales como fallback
            console.warn('No se pudieron actualizar los datos del conflicto, usando datos originales');
            selectedConflict.value = conflict;
        }
    } catch (error) {
        console.error('Error al actualizar datos del conflicto:', error);
        // Si hay error, usar los datos originales como fallback
        selectedConflict.value = conflict;
    }
    
    // Pre-seleccionar valores del CSV por defecto
    const defaultSelections: Record<string, 'csv' | 'existing'> = {};
    Object.keys(selectedConflict.value.data).forEach(field => {
        if (field !== 'password') { // Excluir campos sensibles
            defaultSelections[field] = 'csv';
        }
    });
    mergeSelections.value = defaultSelections;
    showMergeModal.value = true;
};

const closeMergeModal = () => {
    showMergeModal.value = false;
    selectedConflict.value = null;
    mergeSelections.value = {};
};

const confirmMerge = async () => {
    if (!selectedConflict.value) return;
    
    resolvingConflict.value = true;
    
    try {
        const response = await fetch(`/admin/imports/${importData.value.id}/resolve-conflict`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                conflict_id: selectedConflict.value.id,
                resolution: 'merge',
                merge_selections: mergeSelections.value,
            }),
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // Marcar el conflicto como resuelto localmente para reactividad inmediata
            if (selectedConflict.value) {
                const conflictIndex = importData.value.conflict_resolution?.findIndex(c => c.id === selectedConflict.value?.id);
                if (conflictIndex !== undefined && conflictIndex >= 0 && importData.value.conflict_resolution) {
                    importData.value.conflict_resolution[conflictIndex].resolved = true;
                }
            }
            
            // Cerrar modal primero para mejor UX
            closeMergeModal();
            
            // Actualizar los datos desde el servidor
            await updateStatus();
            
            console.log('Conflicto fusionado:', data.message);
        } else {
            alert(data.error || 'Error al fusionar conflicto');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al fusionar conflicto');
    } finally {
        resolvingConflict.value = false;
    }
};

// Funciones auxiliares para el modal
const getCellClass = (field: string, source: 'csv' | 'existing') => {
    if (!selectedConflict.value) return '';
    
    const csvValue = selectedConflict.value.data[field];
    const existingValue = getExistingValue(field);
    
    if (csvValue !== existingValue) {
        return source === mergeSelections.value[field] 
            ? 'bg-green-50 border-green-200' 
            : 'bg-red-50 border-red-200';
    }
    
    return '';
};

const getExistingValue = (field: string) => {
    if (!selectedConflict.value) return '';
    
    let existingUser = null;
    
    // Buscar en existing_user o existing_users
    if (selectedConflict.value.existing_user) {
        existingUser = selectedConflict.value.existing_user;
    } else if (selectedConflict.value.existing_users) {
        // Priorizar by_email sobre by_document
        existingUser = selectedConflict.value.existing_users.by_email || 
                      selectedConflict.value.existing_users.by_document;
    }
    
    if (!existingUser) return '';
    
    // Para campos de ubicación, mostrar nombres si están disponibles, sino IDs
    const locationFields = {
        'territorio_id': 'territorio_nombre',
        'departamento_id': 'departamento_nombre', 
        'municipio_id': 'municipio_nombre',
        'localidad_id': 'localidad_nombre'
    };
    
    if (locationFields[field] && existingUser[locationFields[field]]) {
        return existingUser[locationFields[field]];
    }
    
    return existingUser[field] || '';
};

const getCsvDisplayValue = (field: string) => {
    if (!selectedConflict.value) return '';
    
    const csvValue = selectedConflict.value.data[field];
    const resolvedValue = selectedConflict.value.data_with_ids?.[field];
    
    // Para campos de ubicación, verificar si se pudo resolver
    const locationFields = ['territorio_id', 'departamento_id', 'municipio_id', 'localidad_id'];
    
    if (locationFields.includes(field)) {
        // Si hay data_with_ids pero el valor resuelto es null o vacío, 
        // significa que no se pudo resolver - mostrar vacío
        if (selectedConflict.value.data_with_ids && !resolvedValue) {
            return ''; // No se pudo resolver, mostrar vacío
        }
        
        // Si se resolvió exitosamente, mostrar el valor original del CSV
        if (csvValue) {
            if (isNaN(csvValue)) {
                return csvValue; // Es un nombre, mostrar tal como está
            } else {
                return `${csvValue} (ID)`; // Es un número, indicar que es ID
            }
        }
    }
    
    return csvValue || '';
};

onMounted(() => {
    startPolling();
});

onUnmounted(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});
</script>

<template>
    <Head title="Progreso de Importación" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Progreso de Importación</h1>
                    <p class="text-muted-foreground">
                        {{ importData.name || importData.original_filename }}
                        {{ importData.votacion ? ` - ${importData.votacion.titulo}` : '' }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="goBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        {{ importData.import_type === 'users' ? 'Volver a Importaciones' : 'Volver a Votación' }}
                    </Button>
                    <Button variant="outline" @click="viewAllImports">
                        <FileText class="mr-2 h-4 w-4" />
                        Ver Historial
                    </Button>
                </div>
            </div>

            <!-- Estado Principal -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="flex items-center gap-2">
                            <component 
                                :is="statusConfig.icon"
                                :class="['h-6 w-6', statusConfig.textColor]"
                            />
                            {{ statusConfig.label }}
                        </CardTitle>
                        <Badge :class="statusConfig.bgColor + ' ' + statusConfig.textColor">
                            {{ statusConfig.label }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground mb-4">
                        {{ statusConfig.description }}
                    </p>
                    
                    <!-- Motivo de la Importación -->
                    <div v-if="importData.motivo" class="mb-4 p-3 bg-muted/50 rounded-lg">
                        <h4 class="text-sm font-medium mb-2">Motivo de la importación:</h4>
                        <p class="text-sm text-muted-foreground">{{ importData.motivo }}</p>
                    </div>
                    
                    <!-- Barra de Progreso -->
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span>Progreso</span>
                            <span>{{ importData.processed_rows }} / {{ importData.total_rows || '?' }} filas</span>
                        </div>
                        <Progress 
                            :value="importData.progress_percentage" 
                            :class="statusConfig.color"
                        />
                        <div class="text-center text-sm text-muted-foreground">
                            {{ importData.progress_percentage }}% completado
                        </div>
                    </div>

                    <!-- Información de Tiempo -->
                    <div v-if="importData.started_at || importData.completed_at" class="mt-4 pt-4 border-t">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div v-if="importData.started_at">
                                <span class="font-medium">Iniciado:</span>
                                <p class="text-muted-foreground">{{ importData.started_at }}</p>
                            </div>
                            <div v-if="importData.completed_at">
                                <span class="font-medium">Completado:</span>
                                <p class="text-muted-foreground">{{ importData.completed_at }}</p>
                            </div>
                            <div v-if="importData.duration">
                                <span class="font-medium">Duración:</span>
                                <p class="text-muted-foreground">{{ importData.duration }}</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center space-x-2">
                            <FileText class="h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-2xl font-bold">{{ importData.total_rows || 0 }}</p>
                                <p class="text-xs text-muted-foreground">Total Filas</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center space-x-2">
                            <CheckCircle class="h-5 w-5 text-green-600" />
                            <div>
                                <p class="text-2xl font-bold text-green-600">{{ importData.successful_rows }}</p>
                                <p class="text-xs text-muted-foreground">Exitosos</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center space-x-2">
                            <XCircle class="h-5 w-5 text-red-600" />
                            <div>
                                <p class="text-2xl font-bold text-red-600">{{ importData.failed_rows }}</p>
                                <p class="text-xs text-muted-foreground">Errores</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center space-x-2">
                            <Users class="h-5 w-5 text-blue-600" />
                            <div>
                                <p class="text-2xl font-bold text-blue-600">{{ importData.processed_rows }}</p>
                                <p class="text-xs text-muted-foreground">Procesados</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Conflictos -->
            <Card v-if="hasConflicts">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-orange-700">
                        <AlertTriangle class="h-5 w-5" />
                        Conflictos Detectados ({{ conflicts.length }})
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <Card 
                            v-for="conflict in conflicts" 
                            :key="conflict.id"
                            class="border-orange-200"
                        >
                            <CardContent class="p-4">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <component 
                                                :is="getConflictTypeConfig(conflict.type).icon"
                                                :class="['h-4 w-4', getConflictTypeConfig(conflict.type).color]"
                                            />
                                            <span class="font-medium">Fila {{ conflict.row }}</span>
                                            <Badge variant="outline" class="text-xs">
                                                {{ getConflictTypeConfig(conflict.type).label }}
                                            </Badge>
                                        </div>
                                        <p class="text-sm text-muted-foreground mb-3">
                                            {{ conflict.description }}
                                        </p>
                                        
                                        <!-- Datos del conflicto -->
                                        <div class="bg-gray-50 p-2 rounded text-xs space-y-1">
                                            <div><strong>Email:</strong> {{ conflict.data.email }}</div>
                                            <div v-if="conflict.data.documento_identidad">
                                                <strong>Documento:</strong> {{ conflict.data.documento_identidad }}
                                            </div>
                                            <div><strong>Nombre:</strong> {{ conflict.data.name }}</div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-col gap-2 ml-4">
                                        <Button 
                                            size="sm" 
                                            variant="outline"
                                            :disabled="resolvingConflict"
                                            @click="resolveConflict(conflict.id, 'skip')"
                                        >
                                            Omitir
                                        </Button>
                                        <Button 
                                            size="sm" 
                                            variant="outline"
                                            :disabled="resolvingConflict"
                                            @click="resolveConflict(conflict.id, 'update')"
                                        >
                                            Actualizar
                                        </Button>
                                        <Button 
                                            size="sm"
                                            :disabled="resolvingConflict"
                                            @click="openMergeModal(conflict)"
                                        >
                                            Fusionar
                                        </Button>
                                        <Button 
                                            size="sm"
                                            variant="destructive"
                                            :disabled="resolvingConflict"
                                            @click="resolveConflict(conflict.id, 'force_create')"
                                            title="Crear nuevo usuario con datos modificados para evitar conflictos"
                                        >
                                            Forzar Creación
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </CardContent>
            </Card>

            <!-- Conflictos Resueltos -->
            <Card v-if="resolvedConflicts.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-green-700">
                        <CheckCircle class="h-5 w-5" />
                        Conflictos Resueltos ({{ resolvedConflicts.length }})
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2 max-h-32 overflow-y-auto">
                        <div 
                            v-for="conflict in resolvedConflicts" 
                            :key="conflict.id"
                            class="flex items-center gap-2 text-sm text-green-700 bg-green-50 p-2 rounded"
                        >
                            <CheckCircle class="h-4 w-4" />
                            <span>Fila {{ conflict.row }}: {{ conflict.description }}</span>
                            <Badge variant="outline" class="text-xs">
                                {{ conflict.resolution }}
                            </Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Errores -->
            <Card v-if="importData.errors && importData.errors.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <AlertTriangle class="h-5 w-5 text-orange-600" />
                            Errores Encontrados ({{ importData.errors.length }})
                        </span>
                        <Button 
                            variant="outline" 
                            size="sm"
                            @click="showErrors = !showErrors"
                        >
                            {{ showErrors ? 'Ocultar' : 'Mostrar' }} Errores
                        </Button>
                    </CardTitle>
                </CardHeader>
                <CardContent v-if="showErrors">
                    <Alert class="mb-4">
                        <AlertTriangle class="h-4 w-4" />
                        <AlertDescription>
                            Los siguientes registros no pudieron ser procesados. El resto de usuarios fueron importados correctamente.
                        </AlertDescription>
                    </Alert>
                    <div class="max-h-60 overflow-y-auto space-y-2">
                        <div 
                            v-for="(error, index) in importData.errors" 
                            :key="index"
                            class="p-3 bg-red-50 border border-red-200 rounded text-sm text-red-800"
                        >
                            {{ error }}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Indicador de actualización automática -->
            <div v-if="isActive" class="text-center">
                <div class="inline-flex items-center gap-2 text-sm text-muted-foreground">
                    <div class="animate-pulse h-2 w-2 bg-blue-500 rounded-full"></div>
                    Actualizando automáticamente cada 2 segundos
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Modal de Fusión -->
    <Dialog v-model:open="showMergeModal">
        <DialogContent class="max-w-7xl max-h-[90vh] overflow-auto wide-dialog">
            <DialogHeader>
                <DialogTitle>
                    Fusionar Datos - Fila {{ selectedConflict?.row }}
                </DialogTitle>
            </DialogHeader>
            
            <div v-if="selectedConflict" class="space-y-4">
                <p class="text-sm text-muted-foreground">
                    Selecciona qué valor usar para cada campo. Los campos marcados en rojo son diferentes entre el CSV y la base de datos.
                </p>
                
                <div class="border rounded-lg overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-40">Campo</TableHead>
                                <TableHead class="min-w-64">Valor del CSV</TableHead>
                                <TableHead class="min-w-64">Valor Existente</TableHead>
                                <TableHead class="w-48">Usar</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow 
                                v-for="field in Object.keys(selectedConflict.data)" 
                                :key="field"
                                v-show="field !== 'password'"
                            >
                                <TableCell class="font-medium w-40">{{ field }}</TableCell>
                                <TableCell 
                                    :class="getCellClass(field, 'csv')"
                                    class="min-w-64"
                                >
                                    <div class="truncate" :title="getCsvDisplayValue(field)">
                                        {{ getCsvDisplayValue(field) || '-' }}
                                    </div>
                                </TableCell>
                                <TableCell 
                                    :class="getCellClass(field, 'existing')"
                                    class="min-w-64"
                                >
                                    <div class="truncate" :title="getExistingValue(field)">
                                        {{ getExistingValue(field) || '-' }}
                                    </div>
                                </TableCell>
                                <TableCell class="w-48">
                                    <RadioGroup 
                                        v-model="mergeSelections[field]" 
                                        class="flex space-x-4"
                                    >
                                        <div class="flex items-center space-x-2">
                                            <RadioGroupItem value="csv" :id="`${field}-csv`" />
                                            <Label :for="`${field}-csv`">CSV</Label>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <RadioGroupItem value="existing" :id="`${field}-existing`" />
                                            <Label :for="`${field}-existing`">Existente</Label>
                                        </div>
                                    </RadioGroup>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="closeMergeModal">
                    Cancelar
                </Button>
                <Button @click="confirmMerge" :disabled="resolvingConflict">
                    {{ resolvingConflict ? 'Fusionando...' : 'Confirmar Fusión' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<style>
.wide-dialog {
    --container-lg: 84rem !important;
    max-width: 84rem !important;
}
</style>