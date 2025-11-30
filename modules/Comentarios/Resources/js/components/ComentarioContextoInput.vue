<script setup lang="ts">
/**
 * Componente reutilizable para agregar comentarios con contexto.
 * Diseñado para ser usado desde cualquier módulo (Proyectos, Elecciones, etc.)
 * Incluye editor WYSIWYG y soporte para archivos adjuntos.
 */
import { ref, watch } from 'vue';
import type { UploadedFile } from '../types/comentarios';
import RichTextEditor from '@modules/Core/Resources/js/components/ui/RichTextEditor.vue';
import FileUploadField from '@modules/Core/Resources/js/components/forms/FileUploadField.vue';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Paperclip } from 'lucide-vue-next';

interface Props {
    /** Valor inicial del contenido */
    modelValue?: string;
    /** Placeholder del editor */
    placeholder?: string;
    /** Número de filas del editor */
    rows?: number;
    /** Si se deben mostrar los controles de archivos */
    showFileUpload?: boolean;
    /** Módulo para el upload de archivos */
    uploadModule?: string;
    /** ID del campo para el upload */
    uploadFieldId?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    placeholder: 'Escribe un comentario...',
    rows: 3,
    showFileUpload: true,
    uploadModule: 'comentarios',
    uploadFieldId: 'archivos',
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    'update:archivos': [archivos: UploadedFile[]];
}>();

// Estado interno
const contenido = ref(props.modelValue);
const archivos = ref<UploadedFile[]>([]);
const showUploadPanel = ref(false);

// Sincronizar con prop
watch(() => props.modelValue, (newVal) => {
    contenido.value = newVal;
});

// Emitir cambios de contenido
watch(contenido, (newVal) => {
    emit('update:modelValue', newVal);
});

// Emitir cambios de archivos
watch(archivos, (newVal) => {
    emit('update:archivos', newVal);
}, { deep: true });

// Toggle del panel de archivos
const toggleUpload = () => {
    showUploadPanel.value = !showUploadPanel.value;
};

// Exponer métodos para el padre
defineExpose({
    /** Limpia el contenido y archivos */
    clear: () => {
        contenido.value = '';
        archivos.value = [];
        showUploadPanel.value = false;
    },
    /** Obtiene el contenido actual */
    getContenido: () => contenido.value,
    /** Obtiene los archivos actuales */
    getArchivos: () => archivos.value,
    /** Verifica si hay contenido válido */
    isValid: () => {
        const textoLimpio = contenido.value.replace(/<[^>]*>/g, '').trim();
        return textoLimpio.length > 0;
    },
});
</script>

<template>
    <div class="space-y-3">
        <!-- Editor WYSIWYG -->
        <RichTextEditor
            v-model="contenido"
            :placeholder="placeholder"
            :rows="rows"
        />

        <!-- Zona de archivos adjuntos -->
        <div v-if="showFileUpload && showUploadPanel" class="mt-2">
            <FileUploadField
                v-model="archivos"
                label="Archivos adjuntos"
                description="Máximo 3 archivos de 10MB cada uno"
                :module="uploadModule"
                :field-id="uploadFieldId"
                :multiple="true"
                :max-files="3"
                :max-file-size="10"
                accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx"
                :auto-upload="true"
            />
        </div>

        <!-- Botón de adjuntar archivos (solo si showFileUpload está activo) -->
        <div v-if="showFileUpload" class="flex items-center">
            <Button
                variant="ghost"
                size="sm"
                type="button"
                @click="toggleUpload"
                :class="{ 'text-primary': showUploadPanel || archivos.length > 0 }"
                title="Adjuntar archivos"
            >
                <Paperclip class="h-4 w-4 mr-1" />
                <span class="text-xs">
                    Adjuntar archivos
                    <template v-if="archivos.length > 0">({{ archivos.length }})</template>
                </span>
            </Button>
        </div>
    </div>
</template>
