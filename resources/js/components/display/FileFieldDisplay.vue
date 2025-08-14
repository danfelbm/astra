<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { FileText, Eye, Download, ExternalLink } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    value: string[] | any;
    label?: string;
    showLabel?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showLabel: false
});

// Convertir el valor a array de archivos procesados
const files = computed(() => {
    if (!props.value) return [];
    
    // Si es un array de strings (rutas de archivo)
    if (Array.isArray(props.value)) {
        return props.value.map((path, index) => {
            const fileName = extractFileName(path);
            return {
                id: `file_${index}`,
                name: fileName,
                path: path,
                url: path.startsWith('http') ? path : `/storage/${path}`,
                extension: getFileExtension(fileName)
            };
        });
    }
    
    // Si es un string único
    if (typeof props.value === 'string') {
        const fileName = extractFileName(props.value);
        return [{
            id: 'file_0',
            name: fileName,
            path: props.value,
            url: props.value.startsWith('http') ? props.value : `/storage/${props.value}`,
            extension: getFileExtension(fileName)
        }];
    }
    
    return [];
});

// Extraer nombre del archivo de la ruta
const extractFileName = (path: string): string => {
    if (!path) return 'archivo';
    // Obtener la última parte después del último /
    const parts = path.split('/');
    return parts[parts.length - 1] || 'archivo';
};

// Obtener extensión del archivo
const getFileExtension = (fileName: string): string => {
    const parts = fileName.split('.');
    return parts.length > 1 ? parts[parts.length - 1].toLowerCase() : '';
};

// Determinar el color del badge según la extensión
const getBadgeColor = (extension: string): string => {
    switch (extension) {
        case 'pdf':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        case 'doc':
        case 'docx':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        case 'xls':
        case 'xlsx':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
};

// Abrir archivo en nueva pestaña
const openFile = (url: string) => {
    window.open(url, '_blank');
};

// Descargar archivo
const downloadFile = (url: string, fileName: string) => {
    const link = document.createElement('a');
    link.href = url + '?download=true';
    link.download = fileName;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <div class="space-y-2">
        <!-- Label opcional -->
        <div v-if="showLabel && label">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ label }}
            </label>
        </div>
        
        <!-- Mensaje si no hay archivos -->
        <div v-if="files.length === 0" class="text-sm text-muted-foreground italic">
            No hay archivos adjuntos
        </div>
        
        <!-- Lista de archivos -->
        <div v-else class="space-y-2">
            <Card 
                v-for="file in files" 
                :key="file.id"
                class="hover:shadow-md transition-shadow duration-200"
            >
                <CardContent class="p-3">
                    <div class="flex items-center justify-between">
                        <!-- Información del archivo -->
                        <div class="flex items-center space-x-3 flex-1 min-w-0">
                            <div class="flex-shrink-0">
                                <FileText class="h-8 w-8 text-red-500" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                    {{ file.name }}
                                </p>
                                <Badge :class="getBadgeColor(file.extension)" class="mt-1">
                                    {{ file.extension.toUpperCase() }}
                                </Badge>
                            </div>
                        </div>
                        
                        <!-- Acciones -->
                        <div class="flex items-center space-x-1 flex-shrink-0">
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="openFile(file.url)"
                                class="hover:bg-blue-50 dark:hover:bg-blue-900"
                                title="Ver archivo"
                            >
                                <Eye class="h-4 w-4" />
                                <span class="ml-1 hidden sm:inline">Ver</span>
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="downloadFile(file.url, file.name)"
                                class="hover:bg-green-50 dark:hover:bg-green-900"
                                title="Descargar archivo"
                            >
                                <Download class="h-4 w-4" />
                                <span class="ml-1 hidden sm:inline">Descargar</span>
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="openFile(file.url)"
                                class="hover:bg-purple-50 dark:hover:bg-purple-900"
                                title="Abrir en nueva pestaña"
                            >
                                <ExternalLink class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>