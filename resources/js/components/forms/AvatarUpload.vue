<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Upload, X, User, Loader2 } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

interface Props {
    modelValue?: string | null; // URL del avatar actual
    label?: string;
    description?: string;
    maxSize?: number; // En MB
    accept?: string;
    userName?: string; // Para mostrar iniciales en fallback
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    label: 'Foto de perfil',
    description: 'JPG, PNG o WEBP. Máximo 5MB.',
    maxSize: 5,
    accept: '.jpg,.jpeg,.png,.webp',
    userName: 'Usuario',
    disabled: false
});

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
    'upload': [file: File];
    'delete': [];
    'error': [error: string];
}>();

// Estados locales
const isDragging = ref(false);
const isUploading = ref(false);
const fileInputRef = ref<HTMLInputElement>();
const previewUrl = ref<string | null>(null);

// Observar cambios en el valor del modelo
watch(() => props.modelValue, (newValue) => {
    previewUrl.value = newValue;
}, { immediate: true });

// Obtener iniciales del nombre
const getInitials = (name: string): string => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};

// Tipos de archivo permitidos
const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

// Validar archivo
const validateFile = (file: File): string | null => {
    // Validar tipo
    if (!allowedTypes.includes(file.type)) {
        return 'Tipo de archivo no válido. Solo se permiten JPG, PNG y WEBP.';
    }

    // Validar tamaño (en bytes)
    const maxSizeBytes = props.maxSize * 1024 * 1024;
    if (file.size > maxSizeBytes) {
        return `El archivo excede el tamaño máximo de ${props.maxSize}MB.`;
    }

    return null;
};

// Procesar archivo seleccionado
const processFile = async (file: File) => {
    // Validar archivo
    const error = validateFile(file);
    if (error) {
        toast.error(error);
        emit('error', error);
        return;
    }

    try {
        isUploading.value = true;

        // Crear preview local
        const reader = new FileReader();
        reader.onload = (e) => {
            previewUrl.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);

        // Emitir evento de upload
        emit('upload', file);

        toast.success('Avatar cargado correctamente');
    } catch (error) {
        const errorMsg = 'Error al cargar el archivo';
        toast.error(errorMsg);
        emit('error', errorMsg);
    } finally {
        isUploading.value = false;
    }
};

// Manejar selección de archivo
const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        processFile(file);
    }
};

// Manejar drag & drop
const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = false;
};

const handleDrop = (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = false;

    const file = event.dataTransfer?.files[0];
    if (file && file.type.startsWith('image/')) {
        processFile(file);
    }
};

// Abrir selector de archivo
const openFileDialog = () => {
    fileInputRef.value?.click();
};

// Eliminar avatar
const deleteAvatar = () => {
    previewUrl.value = null;
    emit('update:modelValue', null);
    emit('delete');
    
    toast.success('Avatar eliminado correctamente');
    
    // Limpiar input de archivo
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};
</script>

<template>
    <div class="space-y-2">
        <!-- Label y descripción -->
        <div v-if="label || description">
            <Label v-if="label" class="text-sm font-medium">{{ label }}</Label>
            <p v-if="description" class="text-sm text-muted-foreground">{{ description }}</p>
        </div>

        <!-- Área de upload -->
        <div class="flex items-start space-x-4">
            <!-- Avatar preview -->
            <Avatar class="h-24 w-24">
                <AvatarImage v-if="previewUrl" :src="previewUrl" :alt="userName" />
                <AvatarFallback class="bg-muted">
                    <User v-if="!userName || userName === 'Usuario'" class="h-12 w-12 text-muted-foreground" />
                    <span v-else class="text-2xl font-semibold">{{ getInitials(userName) }}</span>
                </AvatarFallback>
            </Avatar>

            <!-- Controles -->
            <div class="flex-1 space-y-2">
                <!-- Zona de drag & drop -->
                <div
                    @dragover="handleDragOver"
                    @dragleave="handleDragLeave"
                    @drop="handleDrop"
                    :class="[
                        'border-2 border-dashed rounded-lg p-4 text-center cursor-pointer transition-colors',
                        isDragging ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/50',
                        disabled ? 'opacity-50 cursor-not-allowed' : ''
                    ]"
                    @click="openFileDialog"
                >
                    <input
                        ref="fileInputRef"
                        type="file"
                        :accept="accept"
                        @change="handleFileSelect"
                        :disabled="disabled || isUploading"
                        class="hidden"
                    />

                    <div class="space-y-2">
                        <div class="flex justify-center">
                            <Loader2 v-if="isUploading" class="h-8 w-8 animate-spin text-primary" />
                            <Upload v-else class="h-8 w-8 text-muted-foreground" />
                        </div>
                        
                        <div class="text-sm">
                            <span v-if="isUploading" class="text-muted-foreground">Cargando...</span>
                            <span v-else class="text-muted-foreground">
                                Arrastra una imagen aquí o
                                <span class="text-primary font-medium">selecciona un archivo</span>
                            </span>
                        </div>
                        
                        <p class="text-xs text-muted-foreground">
                            {{ accept.replace(/\./g, '').toUpperCase().replace(/,/g, ', ') }} • Max {{ maxSize }}MB
                        </p>
                    </div>
                </div>

                <!-- Botón eliminar (si hay avatar) -->
                <Button
                    v-if="previewUrl"
                    @click="deleteAvatar"
                    variant="outline"
                    size="sm"
                    :disabled="disabled || isUploading"
                    class="w-full"
                >
                    <X class="h-4 w-4 mr-2" />
                    Eliminar avatar
                </Button>
            </div>
        </div>
    </div>
</template>