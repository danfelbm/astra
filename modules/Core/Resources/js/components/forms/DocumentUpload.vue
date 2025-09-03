<template>
    <div class="space-y-4">
        <div>
            <Label v-if="label">{{ label }}</Label>
            <div class="mt-2">
                <!-- Zona de drop -->
                <div
                    @drop="handleDrop"
                    @dragover.prevent
                    @dragenter.prevent
                    @dragleave="isDragging = false"
                    class="relative"
                >
                    <input
                        ref="fileInput"
                        type="file"
                        :multiple="multiple"
                        :accept="acceptedFormats.join(',')"
                        class="hidden"
                        @change="handleFileSelect"
                    />
                    
                    <div
                        @click="$refs.fileInput.click()"
                        class="border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-colors"
                        :class="{
                            'border-primary bg-primary/5': isDragging,
                            'border-muted-foreground/25 hover:border-muted-foreground/50': !isDragging,
                            'opacity-50': disabled
                        }"
                    >
                        <Upload class="h-10 w-10 mx-auto mb-3 text-muted-foreground" />
                        
                        <p class="text-sm font-medium mb-1">
                            {{ isDragging ? 'Suelta los archivos aquí' : 'Haz clic o arrastra archivos aquí' }}
                        </p>
                        
                        <p class="text-xs text-muted-foreground">
                            {{ formatDescription }}
                        </p>
                        
                        <p class="text-xs text-muted-foreground mt-1">
                            Máximo {{ maxSizeMB }}MB por archivo
                            <span v-if="multiple"> • Hasta {{ maxFiles }} archivos</span>
                        </p>
                    </div>
                </div>

                <!-- Progress bars -->
                <div v-if="uploadProgress.length > 0" class="space-y-2 mt-4">
                    <div
                        v-for="(progress, index) in uploadProgress"
                        :key="index"
                        class="space-y-1"
                    >
                        <div class="flex justify-between text-xs">
                            <span class="truncate flex-1">{{ progress.fileName }}</span>
                            <span class="ml-2">{{ progress.progress }}%</span>
                        </div>
                        <Progress :value="progress.progress" class="h-1" />
                    </div>
                </div>

                <!-- Lista de archivos cargados -->
                <div v-if="files.length > 0" class="space-y-2 mt-4">
                    <Label>Archivos cargados</Label>
                    <div class="space-y-1">
                        <div
                            v-for="file in files"
                            :key="file.id"
                            class="flex items-center justify-between p-2 bg-muted rounded-lg"
                        >
                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                <FileText class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                                <span class="text-sm truncate">{{ file.original_name }}</span>
                                <Badge variant="secondary" class="text-xs">
                                    {{ formatFileSize(file.size || 0) }}
                                </Badge>
                            </div>
                            
                            <div class="flex items-center gap-1">
                                <Button
                                    v-if="file.url"
                                    variant="ghost"
                                    size="sm"
                                    @click="downloadFile(file)"
                                    :disabled="disabled"
                                >
                                    <Download class="h-3 w-3" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="removeFile(file.id)"
                                    :disabled="disabled"
                                >
                                    <X class="h-3 w-3" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Errores -->
                <Alert v-if="errors.length > 0" variant="destructive" class="mt-4">
                    <AlertCircle class="h-4 w-4" />
                    <AlertTitle>Errores al cargar archivos</AlertTitle>
                    <AlertDescription>
                        <ul class="list-disc list-inside space-y-1">
                            <li v-for="(error, index) in errors" :key="index" class="text-sm">
                                {{ error }}
                            </li>
                        </ul>
                    </AlertDescription>
                </Alert>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Label } from "../ui/label";
import { Button } from "../ui/button";
import { Badge } from "../ui/badge";
import { Progress } from "../ui/progress";
import { Alert, AlertDescription, AlertTitle } from "../ui/alert";
import { 
    Upload, 
    FileText, 
    Download, 
    X, 
    AlertCircle 
} from 'lucide-vue-next';
import { useFileUpload } from "@modules/Core/Resources/js/composables/useFileUpload";
import { toast } from 'vue-sonner';

interface UploadedFile {
    id: number;
    file_path: string;
    original_name: string;
    mime_type: string;
    size?: number;
    url?: string;
}

interface Props {
    modelValue?: UploadedFile[];
    module: 'votaciones' | 'convocatorias' | 'postulaciones' | 'candidaturas' | 'user-updates';
    fieldId: string;
    label?: string;
    multiple?: boolean;
    maxFiles?: number;
    maxSizeMB?: number;
    acceptedFormats?: string[];
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => [],
    multiple: true,
    maxFiles: 5,
    maxSizeMB: 10,
    acceptedFormats: () => [
        '.pdf',
        '.doc',
        '.docx',
        '.png',
        '.jpg',
        '.jpeg'
    ],
    disabled: false
});

const emit = defineEmits<{
    'update:modelValue': [files: UploadedFile[]];
    'error': [message: string];
}>();

const files = ref<UploadedFile[]>(props.modelValue);
const isDragging = ref(false);
const uploadProgress = ref<{ fileName: string; progress: number }[]>([]);
const errors = ref<string[]>([]);

const { uploadFiles, deleteFile } = useFileUpload();

const formatDescription = computed(() => {
    const formats = props.acceptedFormats
        .map(f => f.replace('.', '').toUpperCase())
        .join(', ');
    return `Formatos permitidos: ${formats}`;
});

const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const handleDrop = (e: DragEvent) => {
    e.preventDefault();
    isDragging.value = false;
    
    if (props.disabled) return;
    
    const droppedFiles = Array.from(e.dataTransfer?.files || []);
    processFiles(droppedFiles);
};

const handleFileSelect = (e: Event) => {
    if (props.disabled) return;
    
    const target = e.target as HTMLInputElement;
    const selectedFiles = Array.from(target.files || []);
    processFiles(selectedFiles);
    
    // Limpiar el input para permitir seleccionar el mismo archivo nuevamente
    target.value = '';
};

const processFiles = async (fileList: File[]) => {
    errors.value = [];
    
    // Validar cantidad de archivos
    if (files.value.length + fileList.length > props.maxFiles) {
        errors.value.push(`Máximo ${props.maxFiles} archivos permitidos`);
        return;
    }
    
    // Validar cada archivo
    const validFiles: File[] = [];
    for (const file of fileList) {
        // Validar tamaño
        if (file.size > props.maxSizeMB * 1024 * 1024) {
            errors.value.push(`${file.name} excede el tamaño máximo de ${props.maxSizeMB}MB`);
            continue;
        }
        
        // Validar formato
        const extension = `.${file.name.split('.').pop()?.toLowerCase()}`;
        if (!props.acceptedFormats.some(format => format.toLowerCase() === extension)) {
            errors.value.push(`${file.name} tiene un formato no permitido`);
            continue;
        }
        
        validFiles.push(file);
    }
    
    if (validFiles.length === 0) return;
    
    // Subir archivos válidos
    try {
        const uploaded = await uploadFiles({
            module: props.module,
            fieldId: props.fieldId,
            files: validFiles,
            onProgress: (fileName, progress) => {
                const index = uploadProgress.value.findIndex(p => p.fileName === fileName);
                if (index >= 0) {
                    uploadProgress.value[index].progress = progress;
                } else {
                    uploadProgress.value.push({ fileName, progress });
                }
            },
            onSuccess: (uploadedFiles) => {
                files.value = [...files.value, ...uploadedFiles];
                emit('update:modelValue', files.value);
                uploadProgress.value = [];
                
                toast.success('Archivos cargados', {
                    description: `${uploadedFiles.length} archivo(s) cargado(s) exitosamente`
                });
            },
            onError: (error) => {
                errors.value.push(error.message);
                uploadProgress.value = [];
            }
        });
    } catch (error: any) {
        errors.value.push(error.message || 'Error al cargar archivos');
        uploadProgress.value = [];
    }
};

const removeFile = async (fileId: number) => {
    if (props.disabled) return;
    
    try {
        const fileIndex = files.value.findIndex(f => f.id === fileId);
        if (fileIndex === -1) return;
        
        const file = files.value[fileIndex];
        
        // Eliminar del servidor si tiene file_path
        if (file.file_path) {
            await deleteFile(file.file_path);
        }
        
        // Eliminar de la lista local
        files.value.splice(fileIndex, 1);
        emit('update:modelValue', files.value);
        
        toast.success('Archivo eliminado', {
            description: 'El archivo se eliminó correctamente'
        });
    } catch (error: any) {
        toast.error('Error al eliminar archivo', {
            description: error.message || 'Intenta nuevamente'
        });
    }
};

const downloadFile = (file: UploadedFile) => {
    if (file.url) {
        window.open(file.url, '_blank');
    }
};

// Actualizar files cuando cambia modelValue
watch(() => props.modelValue, (newValue) => {
    files.value = newValue;
}, { deep: true });
</script>