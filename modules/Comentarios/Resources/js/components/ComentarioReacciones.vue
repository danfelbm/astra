<script setup lang="ts">
/**
 * Componente para mostrar y gestionar reacciones (emojis) de un comentario.
 * Permite toggle de emojis y muestra contadores.
 */
import { ref, computed } from 'vue';
import type { ReaccionResumen, EmojiKey } from '../types/comentarios';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger
} from '@modules/Core/Resources/js/components/ui/popover';
import { SmilePlus } from 'lucide-vue-next';

interface Props {
    reacciones: ReaccionResumen[];
    canReact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    reacciones: () => [],
    canReact: true,
});

const emit = defineEmits<{
    toggle: [emoji: EmojiKey];
}>();

// Emojis disponibles
const emojisDisponibles: Record<EmojiKey, string> = {
    thumbs_up: '👍',
    thumbs_down: '👎',
    heart: '❤️',
    laugh: '😄',
    clap: '👏',
    fire: '🔥',
    check: '✅',
    eyes: '👀',
};

const popoverOpen = ref(false);

// Reacciones ordenadas por cantidad
const reaccionesOrdenadas = computed(() => {
    return [...props.reacciones].sort((a, b) => b.count - a.count);
});

// Verifica si el usuario actual ya reaccionó con un emoji específico
const usuarioReacciono = (emoji: EmojiKey): boolean => {
    const reaccion = props.reacciones.find(r => r.emoji === emoji);
    return reaccion?.usuario_actual_reacciono || false;
};

// Maneja el click en un emoji
const handleToggle = (emoji: EmojiKey) => {
    emit('toggle', emoji);
    popoverOpen.value = false;
};
</script>

<template>
    <div class="flex items-center gap-1.5 flex-wrap">
        <!-- Reacciones existentes -->
        <Button
            v-for="reaccion in reaccionesOrdenadas"
            :key="reaccion.emoji"
            variant="ghost"
            size="sm"
            class="h-7 px-2 text-xs gap-1"
            :class="{
                'bg-primary/10 hover:bg-primary/20': reaccion.usuario_actual_reacciono
            }"
            @click="handleToggle(reaccion.emoji as EmojiKey)"
            :disabled="!canReact"
        >
            <span>{{ reaccion.simbolo }}</span>
            <span class="text-muted-foreground">{{ reaccion.count }}</span>
        </Button>

        <!-- Botón para agregar reacción -->
        <Popover v-if="canReact" v-model:open="popoverOpen">
            <PopoverTrigger asChild>
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-7 w-7 p-0"
                >
                    <SmilePlus class="h-4 w-4 text-muted-foreground" />
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-auto p-2" align="start">
                <div class="flex gap-1">
                    <Button
                        v-for="(simbolo, key) in emojisDisponibles"
                        :key="key"
                        variant="ghost"
                        size="sm"
                        class="h-8 w-8 p-0 text-lg hover:scale-125 transition-transform"
                        :class="{
                            'bg-primary/10': usuarioReacciono(key as EmojiKey)
                        }"
                        @click="handleToggle(key as EmojiKey)"
                    >
                        {{ simbolo }}
                    </Button>
                </div>
            </PopoverContent>
        </Popover>
    </div>
</template>
