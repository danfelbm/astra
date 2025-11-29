<script setup lang="ts">
/**
 * Componente de formulario para crear/editar comentarios.
 * Integra el RichTextEditor existente para WYSIWYG.
 */
import { ref, computed, watch } from 'vue';
import type { ComentarioFormMode, Comentario } from '../types/comentarios';
import RichTextEditor from '@modules/Core/Resources/js/components/ui/RichTextEditor.vue';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Card, CardContent } from '@modules/Core/Resources/js/components/ui/card';
import ComentarioQuote from './ComentarioQuote.vue';
import { Send, X, Loader2 } from 'lucide-vue-next';

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
    submit: [contenido: string, parentId: number | null, quotedId: number | null];
    cancel: [];
}>();

// Estado del formulario
const contenido = ref('');

// Inicializar contenido si estamos editando
watch(() => props.comentarioEditar, (comentario) => {
    if (comentario) {
        contenido.value = comentario.contenido;
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

    emit('submit', contenido.value, parentId, quotedId);

    // Limpiar si no es edición
    if (props.mode !== 'edit') {
        contenido.value = '';
    }
};

// Cancelar
const handleCancel = () => {
    contenido.value = '';
    emit('cancel');
};

// Exponer método para limpiar desde el padre
defineExpose({
    clear: () => { contenido.value = ''; }
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

            <!-- Botones de acción -->
            <div class="flex items-center justify-end gap-2">
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
        </CardContent>
    </Card>
</template>
