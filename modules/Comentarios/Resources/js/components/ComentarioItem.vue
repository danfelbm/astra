<script setup lang="ts">
/**
 * Componente recursivo para mostrar un comentario individual.
 * Soporta respuestas anidadas ilimitadas con colapsado tipo Reddit.
 */
import { ref, computed } from 'vue';
import type { Comentario, EmojiKey } from '../types/comentarios';
import { Avatar, AvatarFallback } from '@modules/Core/Resources/js/components/ui/avatar';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@modules/Core/Resources/js/components/ui/dropdown-menu';
import ComentarioReacciones from './ComentarioReacciones.vue';
import ComentarioQuote from './ComentarioQuote.vue';
import ComentarioForm from './ComentarioForm.vue';
import {
    MessageSquare, MoreHorizontal, Edit, Trash2, Quote,
    ChevronDown, ChevronRight, Clock
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';

interface Props {
    comentario: Comentario;
    nivel?: number;
    canCreate?: boolean;
    canReact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    nivel: 0,
    canCreate: true,
    canReact: true,
});

const emit = defineEmits<{
    reply: [comentario: Comentario];
    quote: [comentario: Comentario];
    edit: [comentario: Comentario, contenido: string];
    delete: [comentario: Comentario];
    toggleReaccion: [comentarioId: number, emoji: EmojiKey];
}>();

// Estado local
const collapsed = ref(false);
const showReplyForm = ref(false);
const editMode = ref(false);
const deleteConfirm = ref(false);

// Total de respuestas (incluyendo anidadas)
const totalRespuestas = computed(() => {
    const contar = (c: Comentario): number => {
        let count = c.respuestas?.length || 0;
        c.respuestas?.forEach(r => { count += contar(r); });
        return count;
    };
    return contar(props.comentario);
});

// Tiene respuestas directas
const tieneRespuestas = computed(() => {
    return (props.comentario.respuestas?.length || 0) > 0;
});

// Iniciales del autor para el avatar
const inicialesAutor = computed(() => {
    const name = props.comentario.autor?.name || 'U';
    return name.substring(0, 2).toUpperCase();
});

// Verifica si el usuario actual es el autor
const esAutor = computed(() => {
    // Se asume que es_editable y es_eliminable vienen del backend
    return props.comentario.es_editable || false;
});

// Tiempo restante para editar
const tiempoRestanteTexto = computed(() => {
    const minutos = props.comentario.tiempo_restante_edicion;
    if (!minutos || minutos <= 0) return null;

    const horas = Math.floor(minutos / 60);
    const mins = minutos % 60;

    if (horas > 0) {
        return `${horas}h ${mins}m para editar`;
    }
    return `${mins}m para editar`;
});

// Toggle colapsar respuestas
const toggleCollapse = () => {
    collapsed.value = !collapsed.value;
};

// Abrir formulario de respuesta
const handleReply = () => {
    showReplyForm.value = true;
};

// Enviar respuesta
const handleReplySubmit = (contenido: string, parentId: number | null, quotedId: number | null) => {
    emit('reply', props.comentario);
    // El submit real lo maneja el padre (ComentariosPanel)
    showReplyForm.value = false;
};

// Cancelar respuesta
const handleReplyCancel = () => {
    showReplyForm.value = false;
};

// Citar comentario
const handleQuote = () => {
    emit('quote', props.comentario);
};

// Iniciar edición
const handleEdit = () => {
    editMode.value = true;
};

// Guardar edición
const handleEditSubmit = (contenido: string) => {
    emit('edit', props.comentario, contenido);
    editMode.value = false;
};

// Cancelar edición
const handleEditCancel = () => {
    editMode.value = false;
};

// Confirmar eliminación
const handleDelete = () => {
    if (deleteConfirm.value) {
        emit('delete', props.comentario);
        deleteConfirm.value = false;
    } else {
        deleteConfirm.value = true;
        setTimeout(() => { deleteConfirm.value = false; }, 3000);
    }
};

// Toggle reacción
const handleToggleReaccion = (emoji: EmojiKey) => {
    emit('toggleReaccion', props.comentario.id, emoji);
};
</script>

<template>
    <div class="group" :class="{ 'ml-6 border-l-2 border-muted pl-4': nivel > 0 }">
        <!-- Contenido del comentario -->
        <div class="flex gap-3">
            <!-- Avatar -->
            <Avatar class="h-8 w-8 flex-shrink-0">
                <AvatarFallback class="text-xs">{{ inicialesAutor }}</AvatarFallback>
            </Avatar>

            <div class="flex-1 min-w-0">
                <!-- Header: Autor y fecha -->
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-medium text-sm">{{ comentario.autor?.name || 'Usuario' }}</span>
                    <span class="text-xs text-muted-foreground">{{ comentario.fecha_relativa }}</span>
                    <Badge v-if="comentario.es_editado" variant="outline" class="text-xs h-5">
                        editado
                    </Badge>
                    <span v-if="tiempoRestanteTexto" class="text-xs text-muted-foreground flex items-center gap-1">
                        <Clock class="h-3 w-3" />
                        {{ tiempoRestanteTexto }}
                    </span>
                </div>

                <!-- Modo edición -->
                <div v-if="editMode" class="mt-2">
                    <ComentarioForm
                        mode="edit"
                        :comentario-editar="comentario"
                        @submit="(contenido) => handleEditSubmit(contenido)"
                        @cancel="handleEditCancel"
                    />
                </div>

                <!-- Contenido normal -->
                <template v-else>
                    <!-- Cita si existe -->
                    <ComentarioQuote
                        v-if="comentario.comentario_citado"
                        :comentario="comentario.comentario_citado"
                        class="mt-2"
                    />

                    <!-- Contenido HTML -->
                    <div
                        class="mt-1 text-sm prose prose-sm dark:prose-invert max-w-none"
                        v-html="comentario.contenido"
                    />

                    <!-- Reacciones -->
                    <div class="mt-2">
                        <ComentarioReacciones
                            :reacciones="comentario.reacciones_resumen || []"
                            :can-react="canReact"
                            @toggle="handleToggleReaccion"
                        />
                    </div>

                    <!-- Acciones -->
                    <div class="flex items-center gap-1 mt-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <!-- Responder -->
                        <Button
                            v-if="canCreate"
                            variant="ghost"
                            size="sm"
                            class="h-7 text-xs"
                            @click="handleReply"
                        >
                            <MessageSquare class="h-3 w-3 mr-1" />
                            Responder
                        </Button>

                        <!-- Citar -->
                        <Button
                            v-if="canCreate"
                            variant="ghost"
                            size="sm"
                            class="h-7 text-xs"
                            @click="handleQuote"
                        >
                            <Quote class="h-3 w-3 mr-1" />
                            Citar
                        </Button>

                        <!-- Menú de opciones -->
                        <DropdownMenu v-if="esAutor || comentario.es_eliminable">
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="sm" class="h-7 w-7 p-0">
                                    <MoreHorizontal class="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem
                                    v-if="comentario.es_editable"
                                    @click="handleEdit"
                                >
                                    <Edit class="h-4 w-4 mr-2" />
                                    Editar
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    v-if="comentario.es_eliminable"
                                    class="text-destructive"
                                    @click="handleDelete"
                                >
                                    <Trash2 class="h-4 w-4 mr-2" />
                                    {{ deleteConfirm ? 'Confirmar eliminación' : 'Eliminar' }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <!-- Colapsar respuestas -->
                        <Button
                            v-if="tieneRespuestas"
                            variant="ghost"
                            size="sm"
                            class="h-7 text-xs ml-auto"
                            @click="toggleCollapse"
                        >
                            <ChevronDown v-if="!collapsed" class="h-3 w-3 mr-1" />
                            <ChevronRight v-else class="h-3 w-3 mr-1" />
                            {{ collapsed ? `Mostrar ${totalRespuestas} respuestas` : 'Ocultar respuestas' }}
                        </Button>
                    </div>
                </template>

                <!-- Formulario de respuesta inline -->
                <div v-if="showReplyForm" class="mt-3">
                    <ComentarioForm
                        mode="reply"
                        :comentario-responder="comentario"
                        @submit="handleReplySubmit"
                        @cancel="handleReplyCancel"
                    />
                </div>
            </div>
        </div>

        <!-- Respuestas anidadas (recursivas) -->
        <div v-if="tieneRespuestas && !collapsed" class="mt-3 space-y-3">
            <ComentarioItem
                v-for="respuesta in comentario.respuestas"
                :key="respuesta.id"
                :comentario="respuesta"
                :nivel="nivel + 1"
                :can-create="canCreate"
                :can-react="canReact"
                @reply="emit('reply', $event)"
                @quote="emit('quote', $event)"
                @edit="emit('edit', $event, $event)"
                @delete="emit('delete', $event)"
                @toggle-reaccion="emit('toggleReaccion', $event, $event)"
            />
        </div>
    </div>
</template>
