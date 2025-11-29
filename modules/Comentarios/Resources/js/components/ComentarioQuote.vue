<script setup lang="ts">
/**
 * Componente para mostrar un comentario citado (quote).
 * Muestra una vista previa del comentario original que se está citando.
 */
import type { Comentario } from '../types/comentarios';
import { Quote } from 'lucide-vue-next';

interface Props {
    comentario: Comentario;
}

defineProps<Props>();
</script>

<template>
    <div class="flex items-start gap-2 p-3 bg-muted/50 rounded-md border-l-4 border-primary/30 text-sm">
        <Quote class="h-4 w-4 text-muted-foreground flex-shrink-0 mt-0.5" />
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
                <span class="font-medium text-xs">{{ comentario.autor?.name || 'Usuario' }}</span>
                <span class="text-xs text-muted-foreground">{{ comentario.fecha_relativa }}</span>
            </div>
            <p class="text-muted-foreground line-clamp-2" v-html="comentario.contenido_truncado || comentario.contenido_plain"></p>
        </div>
    </div>
</template>
