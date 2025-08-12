<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Upload, File, X, Download, Eye } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useFileUpload } from '@/composables/useFileUpload';

interface UploadedFile {
    id: string;
    name: string;
    size: number;
    url?: string;
    path?: string;
}

interface Props {
    modelValue: UploadedFile[] | string[];
    label: string;
    description?: string;
    required?: boolean;
    multiple?: boolean;
    maxFiles?: number;
    maxFileSize?: number; // en MB
    accept?: string; // Tipos de archivo aceptados (ej: ".pdf,.docx,.jpg")
    error?: string;
    disabled?: boolean;
    showPreview?: boolean;
    module?: 'votaciones' | 'convocatorias' | 'postulaciones' | 'candidaturas';
    fieldId?: string;
    autoUpload?: boolean; // Si debe subir automáticamente al seleccionar
}

interface Emits {
    (e: 'update:modelValue', value: UploadedFile[] | string[]): void;
    (e: 'filesSelected', files: File[]): void;
}

const props = withDefaults(defineProps<Props>(), {
    required: false,
    multiple: false,
    maxFiles: 5,
    maxFileSize: 10, // 10MB por defecto
    accept: '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif',
    disabled: false,
    showPreview: true,
    module: 'postulaciones',
    fieldId: 'file',
    autoUpload: true,
});

const emit = defineEmits<Emits>();

// Composable
const { 
    isUploading, 
    uploadFiles: uploadFilesToServer,
    deleteFile: deleteFileFromServer,
    downloadFile: downloadFileFromServer,
    formatFileSize 
} = useFileUpload();

// Referencias
const fileInputRef = ref<HTMLInputElement>();
const selectedFiles = ref<File[]>([]);
const previewUrls = ref<Map<string, string>>(new Map());
const uploadProgress = ref<Map<string, number>>(new Map());
const uploadErrors = ref<Map<string, string>>(new Map());

// Computed
const existingFiles = computed(() => {
    if (!props.modelValue) return [];
    
    // Si modelValue es un array de strings (rutas de archivo)
    if (props.modelValue.length > 0 && typeof props.modelValue[0] === 'string') {
        return (props.modelValue as string[]).map((path, index) => ({
            id: `existing_${index}`,
            name: path.split('/').pop() || 'archivo',
            size: 0,
            path: path,
            url: `/storage/${path}`
        }));
    }
    
    // Si modelValue es un array de objetos UploadedFile
    return props.modelValue as UploadedFile[];
});

const canAddMoreFiles = computed(() => {
    const totalFiles = existingFiles.value.length + selectedFiles.value.length;
    return props.multiple ? totalFiles < props.maxFiles : totalFiles === 0;
});

const acceptedExtensions = computed(() => {
    return props.accept.split(',').map(ext => ext.trim().toLowerCase());
});

// Métodos
const triggerFileInput = () => {
    if (!props.disabled && canAddMoreFiles.value) {
        fileInputRef.value?.click();
    }
};


const isValidFileType = (file: File): boolean => {
    const fileExtension = '.' + file.name.split('.').pop()?.toLowerCase();
    return acceptedExtensions.value.some(ext => ext === fileExtension);
};

const isValidFileSize = (file: File): boolean => {
    const maxSizeInBytes = props.maxFileSize * 1024 * 1024;
    return file.size <= maxSizeInBytes;
};

const handleFileSelect = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const files = Array.from(target.files || []);
    
    // Resetear errores
    uploadErrors.value.clear();
    
    // Validar y filtrar archivos
    const validFiles: File[] = [];
    
    files.forEach(file => {
        // Validar tipo de archivo
        if (!isValidFileType(file)) {
            uploadErrors.value.set(file.name, `Tipo de archivo no permitido. Solo se permiten: ${props.accept}`);
            return;
        }
        
        // Validar tamaño
        if (!isValidFileSize(file)) {
            uploadErrors.value.set(file.name, `El archivo excede el tamaño máximo de ${props.maxFileSize}MB`);
            return;
        }
        
        // Validar cantidad de archivos
        const totalFiles = existingFiles.value.length + validFiles.length;
        if (props.multiple && totalFiles >= props.maxFiles) {
            uploadErrors.value.set(file.name, `Se ha alcanzado el límite de ${props.maxFiles} archivos`);
            return;
        }
        
        validFiles.push(file);
        
        // Crear preview para imágenes
        if (file.type.startsWith('image/') && props.showPreview) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewUrls.value.set(file.name, e.target?.result as string);
            };
            reader.readAsDataURL(file);
        }
    });
    
    if (props.multiple) {
        selectedFiles.value = [...selectedFiles.value, ...validFiles];
    } else {
        selectedFiles.value = validFiles.slice(0, 1);
    }
    
    // Emitir evento con los archivos seleccionados
    if (validFiles.length > 0) {
        emit('filesSelected', validFiles);
        
        // Si está habilitada la carga automática, subir los archivos
        if (props.autoUpload && props.module && props.fieldId) {
            try {
                const uploadedFiles = await uploadFilesToServer(validFiles, {
                    module: props.module,
                    fieldId: props.fieldId,
                    onProgress: (fileName, progress) => {
                        uploadProgress.value.set(fileName, progress);
                    },
                    onSuccess: (files) => {
                        // Actualizar el modelValue con los archivos subidos
                        const newFiles = [...existingFiles.value, ...files];
                        emit('update:modelValue', newFiles);
                        
                        // Limpiar archivos seleccionados
                        selectedFiles.value = [];
                        previewUrls.value.clear();
                    },
                    onError: (error) => {
                        console.error('Error al subir archivos:', error);
                        validFiles.forEach(file => {
                            uploadErrors.value.set(file.name, 'Error al subir el archivo');
                        });
                    }
                });
            } catch (error) {
                console.error('Error en carga automática:', error);
            }
        }
    }
    
    // Limpiar el input para permitir seleccionar el mismo archivo nuevamente
    target.value = '';
};

const removeSelectedFile = (index: number) => {
    const file = selectedFiles.value[index];
    previewUrls.value.delete(file.name);
    uploadProgress.value.delete(file.name);
    uploadErrors.value.delete(file.name);
    selectedFiles.value.splice(index, 1);
};

const removeExistingFile = async (index: number) => {
    const fileToRemove = existingFiles.value[index];
    
    // Si tiene path, intentar eliminar del servidor
    if (fileToRemove.path) {
        try {
            await deleteFileFromServer(fileToRemove.path);
        } catch (error) {
            console.error('Error al eliminar archivo del servidor:', error);
        }
    }
    
    const newFiles = [...existingFiles.value];
    newFiles.splice(index, 1);
    
    // Actualizar modelValue
    if (typeof props.modelValue[0] === 'string') {
        const paths = newFiles.map(f => f.path).filter(Boolean) as string[];
        emit('update:modelValue', paths);
    } else {
        emit('update:modelValue', newFiles);
    }
};

const getFileIcon = (fileName: string): string => {
    const extension = fileName.split('.').pop()?.toLowerCase();
    if (['jpg', 'jpeg', 'png', 'gif', 'svg'].includes(extension || '')) {
        return 'image';
    }
    if (['pdf'].includes(extension || '')) {
        return 'pdf';
    }
    if (['doc', 'docx'].includes(extension || '')) {
        return 'doc';
    }
    if (['xls', 'xlsx'].includes(extension || '')) {
        return 'excel';
    }
    return 'file';
};

// Exponer archivos seleccionados para que el componente padre pueda acceder
defineExpose({
    selectedFiles,
    uploadProgress,
    uploadErrors,
});
</script>

<template>
    <div class="space-y-2">
        <!-- Label y descripción -->
        <div>
            <Label class="text-sm font-medium">
                {{ label }}
                <span v-if="required" class="text-red-500 ml-1">*</span>
            </Label>
            <p v-if="description" class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>

        <!-- Zona de carga -->
        <Card 
            class="border-2 border-dashed hover:border-primary/50 transition-colors cursor-pointer"
            :class="{ 
                'opacity-50 cursor-not-allowed': disabled || !canAddMoreFiles || isUploading,
                'border-red-300': error 
            }"
            @click="triggerFileInput"
        >
            <CardContent class="p-6">
                <div class="flex flex-col items-center justify-center text-center">
                    <Upload class="h-10 w-10 text-muted-foreground mb-3" />
                    <p class="text-sm font-medium mb-1">
                        {{ isUploading ? 'Subiendo archivos...' : canAddMoreFiles ? 'Haz clic para seleccionar archivos' : 'Límite de archivos alcanzado' }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ multiple ? `Hasta ${maxFiles} archivos` : 'Un archivo' }} • 
                        Máx. {{ maxFileSize }}MB • 
                        Formatos: {{ accept }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <!-- Input oculto -->
        <input
            ref="fileInputRef"
            type="file"
            :multiple="multiple"
            :accept="accept"
            :disabled="disabled || !canAddMoreFiles || isUploading"
            @change="handleFileSelect"
            class="hidden"
        />

        <!-- Archivos existentes -->
        <div v-if="existingFiles.length > 0" class="space-y-2">
            <Label class="text-xs text-muted-foreground">Archivos cargados:</Label>
            <div class="space-y-2">
                <Card
                    v-for="(file, index) in existingFiles"
                    :key="file.id"
                    class="p-3"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3 flex-1">
                            <File class="h-5 w-5 text-muted-foreground flex-shrink-0" />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium truncate">{{ file.name }}</p>
                                <p v-if="file.size > 0" class="text-xs text-muted-foreground">
                                    {{ formatFileSize(file.size) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <Button
                                v-if="file.url"
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click.stop="window.open(file.url, '_blank')"
                            >
                                <Eye class="h-4 w-4" />
                            </Button>
                            <Button
                                v-if="file.url"
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click.stop="window.open(file.url + '?download=true', '_blank')"
                            >
                                <Download class="h-4 w-4" />
                            </Button>
                            <Button
                                v-if="!disabled"
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click.stop="removeExistingFile(index)"
                            >
                                <X class="h-4 w-4 text-destructive" />
                            </Button>
                        </div>
                    </div>
                </Card>
            </div>
        </div>

        <!-- Archivos seleccionados pendientes de carga -->
        <div v-if="selectedFiles.length > 0" class="space-y-2">
            <Label class="text-xs text-muted-foreground">Archivos pendientes de carga:</Label>
            <div class="space-y-2">
                <Card
                    v-for="(file, index) in selectedFiles"
                    :key="file.name"
                    class="p-3"
                    :class="{ 'border-red-300': uploadErrors.has(file.name) }"
                >
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3 flex-1">
                                <!-- Preview de imagen si está disponible -->
                                <img 
                                    v-if="previewUrls.has(file.name) && showPreview"
                                    :src="previewUrls.get(file.name)"
                                    :alt="file.name"
                                    class="h-10 w-10 object-cover rounded flex-shrink-0"
                                />
                                <File v-else class="h-5 w-5 text-muted-foreground flex-shrink-0" />
                                
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium truncate">{{ file.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ formatFileSize(file.size) }}
                                    </p>
                                </div>
                            </div>
                            <Button
                                v-if="!disabled"
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="removeSelectedFile(index)"
                            >
                                <X class="h-4 w-4 text-destructive" />
                            </Button>
                        </div>
                        
                        <!-- Barra de progreso si hay carga en proceso -->
                        <div v-if="uploadProgress.has(file.name)" class="w-full">
                            <div class="w-full bg-gray-200 rounded-full h-1.5">
                                <div 
                                    class="bg-primary h-1.5 rounded-full transition-all duration-300"
                                    :style="{ width: `${uploadProgress.get(file.name)}%` }"
                                ></div>
                            </div>
                        </div>
                        
                        <!-- Error si existe -->
                        <p v-if="uploadErrors.has(file.name)" class="text-xs text-red-600">
                            {{ uploadErrors.get(file.name) }}
                        </p>
                    </div>
                </Card>
            </div>
        </div>

        <!-- Error general -->
        <p v-if="error" class="text-sm text-red-600">
            {{ error }}
        </p>

        <!-- Información adicional -->
        <div v-if="!disabled && canAddMoreFiles" class="text-xs text-muted-foreground">
            <p v-if="multiple">
                {{ existingFiles.length + selectedFiles.length }} de {{ maxFiles }} archivos
            </p>
        </div>
    </div>
</template>