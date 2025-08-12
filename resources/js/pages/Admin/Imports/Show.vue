<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle, Clock, AlertCircle, XCircle, ArrowLeft, FileText, Users, AlertTriangle } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';

interface CsvImport {
    id: number;
    votacion_id: number;
    filename: string;
    original_filename: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    total_rows: number;
    processed_rows: number;
    successful_rows: number;
    failed_rows: number;
    errors: string[];
    progress_percentage: number;
    duration: string | null;
    started_at: string | null;
    completed_at: string | null;
    motivo: string | null;
    votacion: {
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

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Votaciones', href: '/admin/votaciones' },
    { title: importData.value.votacion.titulo, href: `/admin/votaciones/${importData.value.votacion_id}/edit` },
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

// Función para actualizar el estado
const updateStatus = async () => {
    try {
        const response = await fetch(`/admin/imports/${importData.value.id}/status`);
        if (response.ok) {
            const data = await response.json();
            importData.value = { ...importData.value, ...data };
            
            // Detener polling si ya no está activo
            if (!isActive.value && pollingInterval) {
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
    if (isActive.value) {
        pollingInterval = setInterval(updateStatus, 2000); // Cada 2 segundos
    }
};

// Navegar de vuelta a la votación
const goBackToVotacion = () => {
    router.get(`/admin/votaciones/${importData.value.votacion_id}/edit`);
};

// Ver historial completo
const viewAllImports = () => {
    router.get(`/admin/votaciones/${importData.value.votacion_id}/imports`);
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
                        {{ importData.original_filename }} - {{ importData.votacion.titulo }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="goBackToVotacion">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver a Votación
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
</template>