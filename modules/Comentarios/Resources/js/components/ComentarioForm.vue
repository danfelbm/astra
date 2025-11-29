<script setup lang="ts">
/**
 * Componente de formulario para crear/editar comentarios.
 * Integra el RichTextEditor existente para WYSIWYG.
 * Soporta hasta 3 archivos adjuntos.
 */
import { ref, computed, watch } from 'vue';
import type { ComentarioFormMode, Comentario, UploadedFile } from '../types/comentarios';
import RichTextEditor from '@modules/Core/Resources/js/components/ui/RichTextEditor.vue';
import FileUploadField from '@modules/Core/Resources/js/components/forms/FileUploadField.vue';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Card, CardContent } from '@modules/Core/Resources/js/components/ui/card';
import ComentarioQuote from './ComentarioQuote.vue';
import { Send, X, Loader2, Paperclip } from 'lucide-vue-next';

interface Props {
    mode?: ComentarioFormMode;
    comentarioEditar?: Comentario | null;
    comentarioCitar?: Comentario | null;
    comentarioResponder?: Comentario | null;
    loading?: boolean;
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    mode: 'create',
    comentarioEditar: null,
    comentarioCitar: null,
    comentarioResponder: null,
    loading: false,
    placeholder: 'Escribe un comentario...',
});

const emit = defineEmits<{
    submit: [contenido: string, parentId: number | null, quotedId: number | null, archivos: UploadedFile[]];
    cancel: [];
}>();

// Estado del formulario
const contenido = ref('');
const archivos = ref<UploadedFile[]>([]);
const showUpload = ref(false);

// Inicializar contenido y archivos si estamos editando
watch(() => props.comentarioEditar, (comentario) => {
    if (comentario) {
        contenido.value = comentario.contenido;
        // Convertir archivos_info a formato UploadedFile
        if (comentario.archivos_info && comentario.archivos_info.length > 0) {
            archivos.value = comentario.archivos_info.map((a, i) => ({
                id: `existing_${i}`,
                name: a.nombre,
                size: 0,
                path: a.path,
                url: a.url,
                mime_type: a.tipo,
                uploaded_at: '',
            }));
            showUpload.value = true;
        }
    }
}, { immediate: true });

// Texto del botón según el modo
const submitButtonText = computed(() => {
    switch (props.mode) {
        case 'edit': return 'Guardar cambios';
        case 'reply': return 'Responder';
        case 'quote': return 'Comentar';
        default: return 'Comentar';
    }
});

// Placeholder según el modo
const placeholderText = computed(() => {
    switch (props.mode) {
        case 'reply': return `Responder a ${props.comentarioResponder?.autor?.name || 'usuario'}...`;
        case 'quote': return 'Escribe tu comentario sobre la cita...';
        default: return props.placeholder;
    }
});

// Verifica si el contenido es válido (no vacío)
const isValid = computed(() => {
    const textoLimpio = contenido.value.replace(/<[^>]*>/g, '').trim();
    return textoLimpio.length > 0;
});

// Enviar formulario
const handleSubmit = () => {
    if (!isValid.value || props.loading) return;

    const parentId = props.mode === 'reply' ? props.comentarioResponder?.id || null : null;
    const quotedId = props.mode === 'quote' ? props.comentarioCitar?.id || null : null;

    emit('submit', contenido.value, parentId, quotedId, archivos.value);

    // Limpiar si no es edición
    if (props.mode !== 'edit') {
        contenido.value = '';
        archivos.value = [];
        showUpload.value = false;
    }
};

// Cancelar
const handleCancel = () => {
    contenido.value = '';
    archivos.value = [];
    showUpload.value = false;
    emit('cancel');
};

// Toggle del panel de archivos
const toggleUpload = () => {
    showUpload.value = !showUpload.value;
};

// Exponer método para limpiar desde el padre
defineExpose({
    clear: () => {
        contenido.value = '';
        archivos.value = [];
        showUpload.value = false;
    }
});
</script>

<template>
    <Card class="border-0 shadow-none">
        <CardContent class="p-0 space-y-3">
            <!-- Cita si existe -->
            <ComentarioQuote
                v-if="mode === 'quote' && comentarioCitar"
                :comentario="comentarioCitar"
            />

            <!-- Indicador de respuesta -->
            <div
                v-if="mode === 'reply' && comentarioResponder"
                class="flex items-center gap-2 text-sm text-muted-foreground"
            >
                <span>Respondiendo a</span>
                <span class="font-medium">{{ comentarioResponder.autor?.name }}</span>
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-5 w-5 p-0 ml-auto"
                    @click="handleCancel"
                >
                    <X class="h-3 w-3" />
                </Button>
            </div>

            <!-- Editor WYSIWYG -->
            <RichTextEditor
                v-model="contenido"
                :placeholder="placeholderText"
                :rows="3"
            />

            <!-- Zona de archivos adjuntos -->
            <div v-if="showUpload" class="mt-2">
                <FileUploadField
                    v-model="archivos"
                    label="Archivos adjuntos"
                    description="Máximo 3 archivos de 10MB cada uno"
                    module="comentarios"
                    field-id="archivos"
                    :multiple="true"
                    :max-files="3"
                    :max-file-size="10"
                    accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx"
                    :auto-upload="true"
                />
            </div>

            <!-- Botones de acción -->
            <div class="flex items-center justify-between gap-2">
                <!-- Botón de adjuntar archivos -->
                <Button
                    variant="ghost"
                    size="sm"
                    type="button"
                    @click="toggleUpload"
                    :class="{ 'text-primary': showUpload || archivos.length > 0 }"
                    title="Adjuntar archivos"
                >
                    <Paperclip class="h-4 w-4" />
                    <span v-if="archivos.length > 0" class="ml-1 text-xs">
                        ({{ archivos.length }})
                    </span>
                </Button>

                <div class="flex items-center gap-2">
                    <Button
                        v-if="mode !== 'create'"
                        variant="ghost"
                        size="sm"
                        @click="handleCancel"
                        :disabled="loading"
                    >
                        Cancelar
                    </Button>
                    <Button
                        size="sm"
                        @click="handleSubmit"
                        :disabled="!isValid || loading"
                    >
                        <Loader2 v-if="loading" class="h-4 w-4 mr-2 animate-spin" />
                        <Send v-else class="h-4 w-4 mr-2" />
                        {{ submitButtonText }}
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
