<script setup lang="ts">
/**
 * Panel principal de comentarios.
 * Integra el formulario, lista de comentarios y toda la lógica de interacción.
 *
 * Uso:
 * <ComentariosPanel
 *     commentable-type="hitos"
 *     :commentable-id="hito.id"
 *     :can-create="canEdit"
 * />
 */
import { ref, onMounted, watch } from 'vue';
import type { Comentario, ComentarioFormMode, EmojiKey } from '../types/comentarios';
import { useComentarios } from '../composables/useComentarios';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Skeleton } from '@modules/Core/Resources/js/components/ui/skeleton';
import ComentarioForm from './ComentarioForm.vue';
import ComentarioItem from './ComentarioItem.vue';
import { MessageSquare, Loader2 } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

interface Props {
    commentableType: string;
    commentableId: number;
    canCreate?: boolean;
    canReact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canCreate: true,
    canReact: true,
});

// Composable de comentarios
const {
    comentarios,
    loading,
    error,
    total,
    puedeCargarMas,
    cargar,
    cargarMas,
    crear,
    editar,
    eliminar,
    toggleReaccion,
} = useComentarios(props.commentableType, props.commentableId);

// Estado del formulario principal
const formMode = ref<ComentarioFormMode>('create');
const comentarioParaResponder = ref<Comentario | null>(null);
const comentarioParaCitar = ref<Comentario | null>(null);
const comentarioParaEditar = ref<Comentario | null>(null);
const formLoading = ref(false);

// Referencia al formulario para limpiar
const formRef = ref<InstanceType<typeof ComentarioForm> | null>(null);

// Cargar comentarios al montar
onMounted(() => {
    cargar();
});

// Recargar si cambia el commentable
watch(
    () => [props.commentableType, props.commentableId],
    () => { cargar(); }
);

// Manejar envío del formulario principal
const handleFormSubmit = async (contenido: string, parentId: number | null, quotedId: number | null) => {
    formLoading.value = true;

    try {
        if (formMode.value === 'edit' && comentarioParaEditar.value) {
            // Editar comentario existente
            const result = await editar(comentarioParaEditar.value.id, { contenido });
            if (result.success) {
                toast.success('Comentario actualizado');
                resetForm();
            } else {
                toast.error(result.message || 'Error al actualizar');
            }
        } else {
            // Crear nuevo comentario o respuesta
            const result = await crear({
                contenido,
                parent_id: parentId,
                quoted_comentario_id: quotedId,
            });
            if (result.success) {
                toast.success(parentId ? 'Respuesta enviada' : 'Comentario publicado');
                resetForm();
            } else {
                toast.error(result.message || 'Error al publicar');
            }
        }
    } finally {
        formLoading.value = false;
    }
};

// Manejar respuesta a un comentario
const handleReply = (comentario: Comentario) => {
    formMode.value = 'reply';
    comentarioParaResponder.value = comentario;
    comentarioParaCitar.value = null;
    comentarioParaEditar.value = null;
};

// Manejar cita de un comentario
const handleQuote = (comentario: Comentario) => {
    formMode.value = 'quote';
    comentarioParaCitar.value = comentario;
    comentarioParaResponder.value = null;
    comentarioParaEditar.value = null;
};

// Manejar edición de un comentario
const handleEdit = async (comentario: Comentario, contenido: string) => {
    const result = await editar(comentario.id, { contenido });
    if (result.success) {
        toast.success('Comentario actualizado');
    } else {
        toast.error(result.message || 'Error al actualizar');
    }
};

// Manejar eliminación de un comentario
const handleDelete = async (comentario: Comentario) => {
    const result = await eliminar(comentario.id);
    if (result.success) {
        toast.success('Comentario eliminado');
    } else {
        toast.error(result.message || 'Error al eliminar');
    }
};

// Manejar toggle de reacción
const handleToggleReaccion = async (comentarioId: number, emoji: EmojiKey) => {
    const result = await toggleReaccion(comentarioId, emoji);
    if (!result.success) {
        toast.error(result.message || 'Error al agregar reacción');
    }
};

// Resetear formulario
const resetForm = () => {
    formMode.value = 'create';
    comentarioParaResponder.value = null;
    comentarioParaCitar.value = null;
    comentarioParaEditar.value = null;
    formRef.value?.clear();
};

// Cancelar formulario
const handleFormCancel = () => {
    resetForm();
};
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle class="flex items-center gap-2">
                        <MessageSquare class="h-5 w-5" />
                        Comentarios
                    </CardTitle>
                    <CardDescription v-if="total > 0">
                        {{ total }} {{ total === 1 ? 'comentario' : 'comentarios' }}
                    </CardDescription>
                </div>
            </div>
        </CardHeader>

        <CardContent class="space-y-6">
            <!-- Formulario de nuevo comentario -->
            <div v-if="canCreate">
                <ComentarioForm
                    ref="formRef"
                    :mode="formMode"
                    :comentario-editar="comentarioParaEditar"
                    :comentario-citar="comentarioParaCitar"
                    :comentario-responder="comentarioParaResponder"
                    :loading="formLoading"
                    @submit="handleFormSubmit"
                    @cancel="handleFormCancel"
                />
            </div>

            <!-- Estado de carga inicial -->
            <div v-if="loading && comentarios.length === 0" class="space-y-4">
                <div v-for="i in 3" :key="i" class="flex gap-3">
                    <Skeleton class="h-8 w-8 rounded-full" />
                    <div class="flex-1 space-y-2">
                        <Skeleton class="h-4 w-32" />
                        <Skeleton class="h-16 w-full" />
                    </div>
                </div>
            </div>

            <!-- Error -->
            <div v-else-if="error" class="text-center py-8 text-destructive">
                <p>{{ error }}</p>
                <Button variant="outline" size="sm" class="mt-2" @click="cargar()">
                    Reintentar
                </Button>
            </div>

            <!-- Sin comentarios -->
            <div v-else-if="comentarios.length === 0" class="text-center py-8">
                <MessageSquare class="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                <p class="text-muted-foreground">No hay comentarios todavía</p>
                <p v-if="canCreate" class="text-sm text-muted-foreground mt-1">
                    Sé el primero en comentar
                </p>
            </div>

            <!-- Lista de comentarios -->
            <div v-else class="space-y-4">
                <ComentarioItem
                    v-for="comentario in comentarios"
                    :key="comentario.id"
                    :comentario="comentario"
                    :can-create="canCreate"
                    :can-react="canReact"
                    @reply="handleReply"
                    @quote="handleQuote"
                    @edit="handleEdit"
                    @delete="handleDelete"
                    @toggle-reaccion="handleToggleReaccion"
                />

                <!-- Cargar más -->
                <div v-if="puedeCargarMas" class="text-center pt-4">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="loading"
                        @click="cargarMas"
                    >
                        <Loader2 v-if="loading" class="h-4 w-4 mr-2 animate-spin" />
                        Cargar más comentarios
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
