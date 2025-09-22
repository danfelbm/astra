<template>
    <div>
        <div
            class="flex items-center gap-1 py-1 px-2 hover:bg-accent rounded cursor-pointer select-none"
            :style="{ paddingLeft: (nivel * 20 + 8) + 'px' }"
            @click="handleToggleSelect"
        >
            <button
                v-if="hasChildren"
                @click.stop="handleToggleExpand"
                class="p-0.5 hover:bg-gray-200 dark:hover:bg-gray-600 rounded"
                type="button"
            >
                <ChevronRight
                    :class="[
                        'h-3 w-3 transition-transform',
                        isExpanded ? 'rotate-90' : ''
                    ]"
                />
            </button>
            <span v-else class="w-4"></span>

            <Check
                :class="[
                    'h-4 w-4 flex-shrink-0',
                    isSelected ? 'opacity-100' : 'opacity-0'
                ]"
            />

            <span class="flex-1 text-sm">
                {{ etiqueta.nombre }}
                <span v-if="etiqueta.categoria" class="text-xs text-muted-foreground ml-1">
                    ({{ etiqueta.categoria.nombre }})
                </span>
            </span>

            <Badge
                v-if="etiqueta.usos_count > 0"
                variant="secondary"
                class="text-xs"
            >
                {{ etiqueta.usos_count }}
            </Badge>
        </div>

        <div v-if="isExpanded && hasChildren">
            <EtiquetaSelectorTreeItem
                v-for="hijo in etiqueta.children"
                :key="hijo.id"
                :etiqueta="hijo"
                :nivel="nivel + 1"
                :selected-ids="selectedIds"
                :expandidos="expandidos"
                :is-selectable="isSelectable"
                @toggle-expand="$emit('toggle-expand', $event)"
                @toggle-select="$emit('toggle-select', $event)"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Check, ChevronRight } from 'lucide-vue-next';
import type { Etiqueta } from '../types/etiquetas';

interface Props {
    etiqueta: Etiqueta;
    nivel?: number;
    selectedIds: number[];
    expandidos: Set<number>;
    isSelectable: (etiqueta: Etiqueta) => boolean;
}

const props = withDefaults(defineProps<Props>(), {
    nivel: 0
});

const emit = defineEmits<{
    'toggle-expand': [id: number];
    'toggle-select': [etiqueta: Etiqueta];
}>();

const isExpanded = computed(() => props.expandidos.has(props.etiqueta.id));
const isSelected = computed(() => props.selectedIds.includes(props.etiqueta.id));
const hasChildren = computed(() => props.etiqueta.children && props.etiqueta.children.length > 0);

const handleToggleExpand = () => {
    emit('toggle-expand', props.etiqueta.id);
};

const handleToggleSelect = () => {
    if (props.isSelectable(props.etiqueta)) {
        emit('toggle-select', props.etiqueta);
    }
};
</script>