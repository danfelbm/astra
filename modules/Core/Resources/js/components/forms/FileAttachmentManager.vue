<!--
  FileAttachmentManager - Componente para gestión de archivos adjuntos

  USO: Para formularios que envían archivos junto con FormData (no upload directo a API)

  Este componente NO sube archivos automáticamente. En su lugar:
  - Emite evento 'files-added' con los archivos seleccionados
  - Emite evento 'file-removed' con la ruta del archivo a eliminar
  - El componente padre debe manejar el envío junto con el resto del formulario

  Para upload directo a API, usar DocumentUpload.vue o FileUploadField.vue
-->
<template>
  <div class="space-y-4">
    <!-- Archivos existentes (solo en modo edición) -->
    <div v-if="existingFiles.length > 0" class="space-y-2">
      <Label class="text-sm font-medium">Archivos actuales</Label>
      <div class="space-y-2">
        <div
          v-for="(file, index) in existingFiles"
          :key="`existing-${index}`"
          class="flex items-center justify-between p-3 bg-muted rounded-lg border border-border hover:bg-accent/50 transition-colors"
        >
          <div class="flex items-center gap-3 flex-1 min-w-0">
            <Paperclip class="h-4 w-4 text-muted-foreground flex-shrink-0" />
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium truncate">{{ file.nombre_original }}</p>
              <p v-if="file.tamaño" class="text-xs text-muted-foreground">
                {{ formatFileSize(file.tamaño) }}
              </p>
            </div>
            <Badge variant="outline" class="flex-shrink-0">
              {{ getFileExtension(file.nombre_original) }}
            </Badge>
          </div>
          <Button
            type="button"
            variant="ghost"
            size="icon"
            class="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10 flex-shrink-0"
            @click="handleRemoveExisting(index)"
            :disabled="disabled"
          >
            <X class="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>

    <!-- Zona de carga con drag & drop -->
    <div>
      <Label v-if="label" class="text-sm font-medium">{{ label }}</Label>

      <div
        @drop="handleDrop"
        @dragover.prevent="isDragging = true"
        @dragleave="isDragging = false"
        @click="triggerFileInput"
        class="relative mt-2"
      >
        <input
          ref="fileInput"
          type="file"
          :multiple="multiple"
          :accept="accept"
          :disabled="disabled"
          class="hidden"
          @change="handleFileSelect"
        />

        <div
          class="border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-all"
          :class="{
            'border-primary bg-primary/5 ring-2 ring-primary/20': isDragging,
            'border-muted-foreground/25 hover:border-muted-foreground/50 hover:bg-muted/30': !isDragging && !disabled,
            'opacity-50 cursor-not-allowed': disabled,
            'border-destructive': error
          }"
        >
          <Upload
            class="h-10 w-10 mx-auto mb-3 transition-colors"
            :class="isDragging ? 'text-primary' : 'text-muted-foreground'"
          />

          <p class="text-sm font-medium mb-1">
            {{ isDragging ? 'Suelta los archivos aquí' : 'Haz clic o arrastra archivos aquí' }}
          </p>

          <p class="text-xs text-muted-foreground">
            {{ description || `Formatos: ${accept}` }}
          </p>

          <p class="text-xs text-muted-foreground mt-1">
            Máximo {{ maxSizeMB }}MB por archivo
            <span v-if="multiple"> • Hasta {{ maxFiles }} archivos total</span>
          </p>
        </div>
      </div>
    </div>

    <!-- Vista previa de archivos nuevos -->
    <div v-if="newFiles.length > 0" class="space-y-2">
      <Label class="text-sm font-medium">Archivos a subir</Label>
      <div class="space-y-2">
        <div
          v-for="(file, index) in newFiles"
          :key="`new-${index}`"
          class="flex items-center justify-between p-3 bg-accent rounded-lg border border-primary/20"
        >
          <div class="flex items-center gap-3 flex-1 min-w-0">
            <Upload class="h-4 w-4 text-primary flex-shrink-0" />
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium truncate">{{ file.name }}</p>
              <p class="text-xs text-muted-foreground">
                {{ formatFileSize(file.size) }}
              </p>
            </div>
            <Badge variant="secondary" class="flex-shrink-0">
              {{ getFileExtension(file.name) }}
            </Badge>
          </div>
          <Button
            type="button"
            variant="ghost"
            size="icon"
            class="h-8 w-8 hover:bg-destructive/10 flex-shrink-0"
            @click="handleRemoveNew(index)"
            :disabled="disabled"
          >
            <X class="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>

    <!-- Mensaje de error -->
    <Alert v-if="error" variant="destructive">
      <AlertCircle class="h-4 w-4" />
      <AlertDescription>{{ error }}</AlertDescription>
    </Alert>

    <!-- Información de límites -->
    <div v-if="!disabled && (existingFiles.length > 0 || newFiles.length > 0)" class="text-xs text-muted-foreground">
      {{ totalFiles }} de {{ maxFiles }} archivos
      <span v-if="totalFiles >= maxFiles" class="text-destructive"> (límite alcanzado)</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Label } from '../ui/label';
import { Button } from '../ui/button';
import { Badge } from '../ui/badge';
import { Alert, AlertDescription } from '../ui/alert';
import { Upload, Paperclip, X, AlertCircle } from 'lucide-vue-next';

interface ExistingFile {
  ruta: string;
  nombre_original: string;
  tamaño?: number;
  tipo?: string;
}

interface Props {
  /** Archivos existentes (para modo edición) */
  existingFiles?: ExistingFile[];
  /** Label del campo */
  label?: string;
  /** Descripción adicional */
  description?: string;
  /** Permitir múltiples archivos */
  multiple?: boolean;
  /** Número máximo de archivos */
  maxFiles?: number;
  /** Tamaño máximo por archivo en MB */
  maxSizeMB?: number;
  /** Tipos de archivo aceptados */
  accept?: string;
  /** Deshabilitar el componente */
  disabled?: boolean;
  /** Mensaje de error */
  error?: string;
}

const props = withDefaults(defineProps<Props>(), {
  existingFiles: () => [],
  multiple: true,
  maxFiles: 10,
  maxSizeMB: 5,
  accept: '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png',
  disabled: false
});

const emit = defineEmits<{
  /** Emite cuando se agregan nuevos archivos */
  'files-added': [files: File[]];
  /** Emite cuando se elimina un archivo existente (envía la ruta) */
  'file-removed': [ruta: string];
}>();

// Estado
const fileInput = ref<HTMLInputElement>();
const isDragging = ref(false);
const newFiles = ref<File[]>([]);
const internalError = ref<string>('');

// Computed
const existingFiles = computed(() => props.existingFiles || []);
const totalFiles = computed(() => existingFiles.value.length + newFiles.value.length);
const canAddMoreFiles = computed(() => totalFiles.value < props.maxFiles);

// Métodos
const triggerFileInput = () => {
  if (!props.disabled && canAddMoreFiles.value) {
    fileInput.value?.click();
  }
};

const handleFileSelect = (event: Event) => {
  const input = event.target as HTMLInputElement;
  if (!input.files) return;

  processFiles(Array.from(input.files));

  // Limpiar input para permitir seleccionar el mismo archivo nuevamente
  input.value = '';
};

const handleDrop = (event: DragEvent) => {
  event.preventDefault();
  isDragging.value = false;

  if (props.disabled) return;

  const files = Array.from(event.dataTransfer?.files || []);
  processFiles(files);
};

const processFiles = (files: File[]) => {
  internalError.value = '';

  // Validar cantidad
  const availableSlots = props.maxFiles - totalFiles.value;
  if (files.length > availableSlots) {
    internalError.value = `Solo puedes agregar ${availableSlots} archivo(s) más. Límite: ${props.maxFiles}`;
    return;
  }

  const validFiles: File[] = [];
  const errors: string[] = [];

  files.forEach(file => {
    // Validar tamaño
    const maxSize = props.maxSizeMB * 1024 * 1024;
    if (file.size > maxSize) {
      errors.push(`${file.name}: excede ${props.maxSizeMB}MB`);
      return;
    }

    // Validar tipo de archivo
    const extension = `.${file.name.split('.').pop()?.toLowerCase()}`;
    const acceptedExtensions = props.accept.split(',').map(ext => ext.trim().toLowerCase());

    if (!acceptedExtensions.includes(extension)) {
      errors.push(`${file.name}: formato no permitido`);
      return;
    }

    validFiles.push(file);
  });

  if (errors.length > 0) {
    internalError.value = errors.join(', ');
  }

  if (validFiles.length > 0) {
    if (props.multiple) {
      newFiles.value.push(...validFiles);
    } else {
      newFiles.value = [validFiles[0]];
    }

    emit('files-added', newFiles.value);
  }
};

const handleRemoveNew = (index: number) => {
  newFiles.value.splice(index, 1);
  emit('files-added', newFiles.value);
};

const handleRemoveExisting = (index: number) => {
  const file = existingFiles.value[index];
  if (file && confirm('¿Estás seguro de eliminar este archivo?')) {
    emit('file-removed', file.ruta);
  }
};

const formatFileSize = (bytes: number | undefined): string => {
  if (!bytes) return '0 KB';
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(1024));
  return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${sizes[i]}`;
};

const getFileExtension = (filename: string): string => {
  return filename.split('.').pop()?.toUpperCase() || 'FILE';
};

// Exponer métodos y propiedades para acceso desde componente padre
defineExpose({
  newFiles,
  clearNewFiles: () => { newFiles.value = []; }
});

// Watch para limpiar error interno cuando se limpia el error externo
watch(() => props.error, (newError) => {
  if (!newError) {
    internalError.value = '';
  }
});
</script>
