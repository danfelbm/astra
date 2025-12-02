<script setup lang="ts">
/**
 * ComentariosSheet - Sheet lateral para mostrar comentarios de hitos/entregables
 *
 * Componente reutilizable que encapsula Sheet + ComentariosPanel.
 * Usado en HitosDashboard para ver comentarios sin navegar a otra página.
 */
import { Sheet, SheetContent } from '@modules/Core/Resources/js/components/ui/sheet';
import ComentariosPanel from '@modules/Comentarios/Resources/js/components/ComentariosPanel.vue';

interface Props {
    open: boolean;
    commentableType: 'hitos' | 'entregables';
    commentableId: number;
    canCreate?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canCreate: true,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();
</script>

<template>
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent side="right" class="sm:max-w-lg w-full overflow-y-auto">
            <div class="mt-6">
                <ComentariosPanel
                    :commentable-type="commentableType"
                    :commentable-id="commentableId"
                    :can-create="canCreate"
                    embedded
                />
            </div>
        </SheetContent>
    </Sheet>
</template>
