<script setup lang="ts">
/**
 * HitosViewModeToggle - Toggle para seleccionar modo de visualización
 * Incluye switch para confirmar al arrastrar (solo visible en modo kanban)
 */
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Switch } from '@modules/Core/Resources/js/components/ui/switch';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { List, LayoutGrid, Columns3 } from 'lucide-vue-next';
import type { EntregablesViewMode } from '@modules/Proyectos/Resources/js/types/hitos';

// Props
interface Props {
    modelValue: EntregablesViewMode;
    confirmOnDrag: boolean;
}

const props = defineProps<Props>();

// Emits
const emit = defineEmits<{
    'update:modelValue': [value: EntregablesViewMode];
    'update:confirmOnDrag': [value: boolean];
}>();

// Opciones de modo
const modos: { value: EntregablesViewMode; icon: typeof List; label: string }[] = [
    { value: 'list', icon: List, label: 'Lista' },
    { value: 'tabs', icon: LayoutGrid, label: 'Tabs' },
    { value: 'kanban', icon: Columns3, label: 'Kanban' },
];
</script>

<template>
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <!-- Toggle de modo de vista -->
        <div class="flex gap-1 bg-muted p-1 rounded-lg">
            <Button
                v-for="modo in modos"
                :key="modo.value"
                :variant="modelValue === modo.value ? 'default' : 'ghost'"
                size="sm"
                class="h-8 px-3"
                :title="modo.label"
                @click="emit('update:modelValue', modo.value)"
            >
                <component :is="modo.icon" class="h-4 w-4" />
                <span class="ml-1.5 hidden sm:inline text-xs">{{ modo.label }}</span>
            </Button>
        </div>

        <!-- Switch para confirmar al arrastrar (solo visible en kanban) -->
        <div
            v-if="modelValue === 'kanban'"
            class="flex items-center gap-2"
        >
            <Switch
                id="confirm-drag"
                :model-value="confirmOnDrag"
                @update:model-value="emit('update:confirmOnDrag', $event)"
            />
            <Label for="confirm-drag" class="text-sm text-muted-foreground cursor-pointer">
                Confirmar al arrastrar
            </Label>
        </div>
    </div>
</template>
