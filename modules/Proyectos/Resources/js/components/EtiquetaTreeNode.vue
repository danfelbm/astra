<template>
    <div class="tree-node">
        <div
            :class="[
                'flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700',
                'transition-colors cursor-move',
                isDragOver ? 'bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-300' : ''
            ]"
            :style="{ paddingLeft: (nivel * 24) + 'px' }"
            draggable="true"
            @dragstart="handleDragStart"
            @dragover="handleDragOver"
            @drop="handleDrop"
            @dragend="handleDragEnd"
            @dragleave="handleDragLeave"
        >
            <!-- Botón expandir/colapsar -->
            <button
                v-if="etiqueta.children && etiqueta.children.length > 0"
                @click="$emit('toggle', etiqueta.id)"
                class="p-0.5 hover:bg-accent rounded"
                type="button"
            >
                <ChevronRight
                    :class="[
                        'h-4 w-4 transition-transform',
                        expandidos.has(etiqueta.id) ? 'rotate-90' : ''
                    ]"
                />
            </button>
            <span v-else class="w-5"></span>

            <!-- Color de categoría -->
            <div
                v-if="etiqueta.categoria?.color"
                :class="[
                    'w-3 h-3 rounded-full',
                    etiqueta.categoria.color
                ]"
            />

            <!-- Nombre de la etiqueta -->
            <span class="flex-1 font-medium">
                {{ etiqueta.nombre }}
                <span v-if="etiqueta.categoria" class="text-xs text-muted-foreground ml-1">
                    ({{ etiqueta.categoria.nombre }})
                </span>
            </span>

            <!-- Badges de información -->
            <div class="flex gap-1">
                <Badge v-if="etiqueta.usos_count > 0" variant="secondary" class="text-xs">
                    {{ etiqueta.usos_count }} usos
                </Badge>
                <Badge v-if="etiqueta.children && etiqueta.children.length > 0" variant="outline" class="text-xs">
                    {{ etiqueta.children.length }}
                </Badge>
            </div>

            <!-- Acciones -->
            <div class="flex gap-1">
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7"
                    @click.stop="$emit('edit', etiqueta)"
                >
                    <Edit2 class="h-3 w-3" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7 text-red-500 hover:text-red-600"
                    @click.stop="$emit('delete', etiqueta)"
                >
                    <Trash2 class="h-3 w-3" />
                </Button>
            </div>
        </div>

        <!-- Hijos recursivos -->
        <div v-if="expandidos.has(etiqueta.id) && etiqueta.children">
            <EtiquetaTreeNode
                v-for="hijo in etiqueta.children"
                :key="hijo.id"
                :etiqueta="hijo"
                :nivel="nivel + 1"
                :expandidos="expandidos"
                :drag-drop-helpers="dragDropHelpers"
                @toggle="$emit('toggle', $event)"
                @edit="$emit('edit', $event)"
                @delete="$emit('delete', $event)"
                @drop="$emit('drop', $event)"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { ChevronRight, Edit2, Trash2 } from 'lucide-vue-next';
import type { Etiqueta } from '../types/etiquetas';

interface Props {
    etiqueta: Etiqueta;
    nivel: number;
    expandidos: Set<number>;
    dragDropHelpers: any;
}

const props = withDefaults(defineProps<Props>(), {
    nivel: 0
});

const emit = defineEmits<{
    toggle: [id: number];
    edit: [etiqueta: Etiqueta];
    delete: [etiqueta: Etiqueta];
    drop: [data: any];
}>();

const isDragOver = ref(false);

const handleDragStart = (e: DragEvent) => {
    e.dataTransfer!.effectAllowed = 'move';
    props.dragDropHelpers.handleDragStart(props.etiqueta);
};

const handleDragOver = (e: DragEvent) => {
    e.preventDefault();
    e.dataTransfer!.dropEffect = 'move';
    isDragOver.value = true;
    props.dragDropHelpers.handleDragOver(e, props.etiqueta);
};

const handleDragLeave = () => {
    isDragOver.value = false;
};

const handleDrop = (e: DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    isDragOver.value = false;

    const result = props.dragDropHelpers.handleDrop(e, props.etiqueta);
    if (result && result.valido) {
        emit('drop', result);
    }
};

const handleDragEnd = () => {
    isDragOver.value = false;
    props.dragDropHelpers.handleDragEnd();
};
</script>

<style scoped>
.tree-node {
    user-select: none;
}
</style>