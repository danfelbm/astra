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
import { ref, computed, onMounted, watch } from 'vue';
import type { Comentario, ComentarioFormMode, EmojiKey, PaginationLink, UploadedFile } from '../types/comentarios';
import { useComentarios, type SortOption } from '../composables/useComentarios';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Skeleton } from '@modules/Core/Resources/js/components/ui/skeleton';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@modules/Core/Resources/js/components/ui/select';
import ComentarioForm from './ComentarioForm.vue';
import ComentarioItem from './ComentarioItem.vue';
import { MessageSquare, ArrowUpDown, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

// Opciones de ordenamiento
const sortOptions: { value: SortOption; label: string }[] = [
    { value: 'recientes', label: 'Más recientes' },
    { value: 'antiguos', label: 'Más antiguos' },
    { value: 'populares', label: 'Más populares' },
];

interface Props {
    commentableType: string;
    commentableId: number;
    canCreate?: boolean;
    canReact?: boolean;
    // Modo embebido (sin Card wrapper, para usar dentro de Sheet/Dialog)
    embedded?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canCreate: true,
    canReact: true,
    embedded: false,
});

// Leer página inicial desde la URL
const getPaginaInicialDeUrl = (): number => {
    const urlParams = new URLSearchParams(window.location.search);
    const pagina = urlParams.get('pagina');
    return pagina ? parseInt(pagina, 10) : 1;
};

// Composable de comentarios con opciones
const {
    comentarios,
    loading,
    error,
    total,
    lastPage,
    from,
    to,
    links,
    tienePaginacion,
    puedePaginaAnterior,
    puedePaginaSiguiente,
    sortBy,
    cargar,
    irAPagina,
    paginaSiguiente,
    paginaAnterior,
    crear,
    editar,
    eliminar,
    toggleReaccion,
    cambiarOrden,
    cargarRespuestasAdicionales,
} = useComentarios(props.commentableType, props.commentableId, {
    paginaInicial: getPaginaInicialDeUrl(),
    urlParam: 'pagina',
    sincronizarUrl: true,
});

// Links de paginación procesados
const paginationLinks = computed(() => {
    if (!links.value || links.value.length <= 3) return [];
    return links.value;
});

// Ir a una página específica desde los links
const handlePageClick = (link: PaginationLink) => {
    if (!link.url || link.active) return;

    // Extraer el número de página de la URL del link
    const url = new URL(link.url, window.location.origin);
    const page = parseInt(url.searchParams.get('page') || '1', 10);
    irAPagina(page);
};

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
const handleFormSubmit = async (contenido: string, parentId: number | null, quotedId: number | null, archivos: UploadedFile[] = []) => {
    formLoading.value = true;

    try {
        if (formMode.value === 'edit' && comentarioParaEditar.value) {
            // Editar comentario existente
            const result = await editar(comentarioParaEditar.value.id, { contenido, archivos });
            if (result.success) {
                toast.success(result.message);
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
                archivos,
            });
            if (result.success) {
                toast.success(result.message);
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
const handleEdit = async (comentario: Comentario, contenido: string, archivos: UploadedFile[] = []) => {
    const result = await editar(comentario.id, { contenido, archivos });
    if (result.success) {
        toast.success(result.message);
    } else {
        toast.error(result.message || 'Error al actualizar');
    }
};

// Manejar eliminación de un comentario
const handleDelete = async (comentario: Comentario) => {
    const result = await eliminar(comentario.id);
    if (result.success) {
        toast.success(result.message);
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

// Manejar submit de respuesta desde formulario inline
const handleSubmitReply = async (contenido: string, parentId: number, archivos: UploadedFile[] = []) => {
    const result = await crear({
        contenido,
        parent_id: parentId,
        quoted_comentario_id: null,
        archivos,
    });
    if (result.success) {
        toast.success(result.message);
    } else {
        toast.error(result.message || 'Error al enviar respuesta');
    }
};

// Manejar carga de más respuestas (para respuestas profundas)
const handleCargarMasRespuestas = async (comentarioId: number) => {
    const result = await cargarRespuestasAdicionales(comentarioId);
    if (!result.success) {
        toast.error(result.message || 'Error al cargar respuestas');
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
    <!-- Wrapper: Card cuando no está embebido, div simple cuando sí -->
    <component :is="embedded ? 'div' : Card" :class="{ 'space-y-4': embedded }">
        <!-- Header -->
        <component :is="embedded ? 'div' : CardHeader">
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

                <!-- Selector de ordenamiento -->
                <Select v-if="total > 1" :model-value="sortBy" @update:model-value="cambiarOrden">
                    <SelectTrigger class="w-[160px] h-8 text-xs">
                        <ArrowUpDown class="h-3 w-3 mr-1" />
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in sortOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </component>

        <component :is="embedded ? 'div' : CardContent" class="space-y-6">
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
                    @submit-reply="handleSubmitReply"
                    @cargar-mas-respuestas="handleCargarMasRespuestas"
                />

                <!-- Paginación -->
                <div v-if="tienePaginacion" class="flex flex-col items-center gap-3 pt-4 border-t">
                    <!-- Info de resultados -->
                    <div class="text-sm text-muted-foreground">
                        Mostrando {{ from }} a {{ to }} de {{ total }} comentarios
                    </div>

                    <!-- Controles de paginación -->
                    <div class="flex items-center gap-1">
                        <!-- Ir al inicio -->
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-8 w-8 p-0"
                            :disabled="!puedePaginaAnterior || loading"
                            @click="irAPagina(1)"
                            title="Primera página"
                        >
                            <ChevronsLeft class="h-4 w-4" />
                        </Button>

                        <!-- Anterior -->
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-8 w-8 p-0"
                            :disabled="!puedePaginaAnterior || loading"
                            @click="paginaAnterior"
                            title="Página anterior"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </Button>

                        <!-- Números de página -->
                        <template v-for="link in paginationLinks" :key="link.label">
                            <!-- Omitir Previous y Next (ya tenemos botones para eso) -->
                            <template v-if="!link.label.includes('Previous') && !link.label.includes('Next') && !link.label.includes('previous') && !link.label.includes('next')">
                                <!-- Elipsis -->
                                <span
                                    v-if="link.label === '...'"
                                    class="px-2 text-muted-foreground"
                                >
                                    ...
                                </span>
                                <!-- Número de página -->
                                <Button
                                    v-else
                                    :variant="link.active ? 'default' : 'outline'"
                                    size="sm"
                                    class="h-8 min-w-[32px] px-2"
                                    :disabled="loading || link.active"
                                    @click="handlePageClick(link)"
                                >
                                    {{ link.label }}
                                </Button>
                            </template>
                        </template>

                        <!-- Siguiente -->
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-8 w-8 p-0"
                            :disabled="!puedePaginaSiguiente || loading"
                            @click="paginaSiguiente"
                            title="Página siguiente"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </Button>

                        <!-- Ir al final -->
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-8 w-8 p-0"
                            :disabled="!puedePaginaSiguiente || loading"
                            @click="irAPagina(lastPage)"
                            title="Última página"
                        >
                            <ChevronsRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </component>
    </component>
</template>
