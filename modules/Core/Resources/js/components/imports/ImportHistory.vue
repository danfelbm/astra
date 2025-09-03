<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from "../ui/card";
import { Button } from "../ui/button";
import { Badge } from "../ui/badge";
import { History, Clock, CheckCircle, XCircle, FileText } from 'lucide-vue-next';
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

interface CsvImport {
    id: number;
    original_filename: string;
    name?: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    progress_percentage: number;
    successful_rows: number;
    failed_rows: number;
    total_rows?: number;
    created_at: string;
    created_by: {
        name: string;
    };
}

interface Props {
    type: 'votacion' | 'asamblea';
    entityId: number;
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Historial de Importaciones'
});

const recentImports = ref<CsvImport[]>([]);
const isLoading = ref(false);

// Cargar importaciones recientes
const loadRecentImports = async () => {
    if (!props.entityId) return;
    
    isLoading.value = true;
    try {
        const endpoint = props.type === 'votacion' 
            ? `/admin/votaciones/${props.entityId}/imports/recent`
            : `/admin/asambleas/${props.entityId}/imports/recent`;
            
        const response = await fetch(endpoint);
        if (response.ok) {
            const data = await response.json();
            recentImports.value = data;
        }
    } catch (error) {
        console.error('Error loading recent imports:', error);
    } finally {
        isLoading.value = false;
    }
};

// Navegación a páginas de importación
const viewImportProgress = (importId: number) => {
    router.get(`/admin/imports/${importId}`);
};

const viewAllImports = () => {
    const route = props.type === 'votacion'
        ? `/admin/votaciones/${props.entityId}/imports`
        : `/admin/asambleas/${props.entityId}/imports`;
    router.get(route);
};

// Obtener configuración de estado para las importaciones
const getImportStatusConfig = (status: CsvImport['status']) => {
    switch (status) {
        case 'pending':
            return { icon: Clock, label: 'Pendiente', class: 'text-yellow-600' };
        case 'processing':
            return { icon: Clock, label: 'Procesando', class: 'text-blue-600' };
        case 'completed':
            return { icon: CheckCircle, label: 'Completada', class: 'text-green-600' };
        case 'failed':
            return { icon: XCircle, label: 'Fallida', class: 'text-red-600' };
    }
};

// Formatear fecha
const formatImportDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('es-ES', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Cargar datos al montar
onMounted(() => {
    loadRecentImports();
});

// Exponer funciones para uso externo
defineExpose({
    loadRecentImports
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <History class="h-5 w-5" />
                    {{ title }}
                </span>
                <Button 
                    v-if="recentImports.length > 0"
                    variant="outline" 
                    size="sm"
                    @click="viewAllImports"
                >
                    Ver Todas
                </Button>
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div v-if="isLoading" class="flex justify-center py-6">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            </div>
            
            <div v-else-if="recentImports.length === 0" class="text-center py-6 text-muted-foreground">
                <History class="mx-auto h-8 w-8 mb-2" />
                <p>No hay importaciones registradas</p>
                <p class="text-sm">Las importaciones aparecerán aquí una vez que subas archivos CSV</p>
            </div>
            
            <div v-else class="space-y-3">
                <div 
                    v-for="import_ in recentImports" 
                    :key="import_.id"
                    class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
                    @click="viewImportProgress(import_.id)"
                >
                    <div class="flex items-center gap-3">
                        <component 
                            :is="getImportStatusConfig(import_.status).icon"
                            :class="['h-4 w-4', getImportStatusConfig(import_.status).class]"
                        />
                        <div>
                            <p class="font-medium text-sm">
                                {{ import_.name || import_.original_filename }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ formatImportDate(import_.created_at) }} • Por {{ import_.created_by.name }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <div class="text-right text-sm">
                            <Badge 
                                variant="outline"
                                :class="getImportStatusConfig(import_.status).class"
                            >
                                {{ getImportStatusConfig(import_.status).label }}
                            </Badge>
                            <p class="text-xs text-muted-foreground mt-1">
                                <span v-if="import_.status === 'processing'">
                                    {{ import_.progress_percentage }}%
                                </span>
                                <span v-else-if="import_.status === 'completed'">
                                    {{ import_.successful_rows }}/{{ import_.total_rows || import_.successful_rows + import_.failed_rows }} registros
                                </span>
                                <span v-else-if="import_.status === 'failed' && import_.failed_rows > 0">
                                    {{ import_.failed_rows }} errores
                                </span>
                            </p>
                        </div>
                        <FileText class="h-4 w-4 text-gray-400" />
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>