<script setup lang="ts">
/**
 * InlineEditSelect - Componente para edición inline con select
 * Auto-save cuando se selecciona una opción
 */
import { ref, watch, computed } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@modules/Core/Resources/js/components/ui/select';
import InlineEditWrapper from './InlineEditWrapper.vue';

interface SelectOption {
    value: string;
    label: string;
    color?: string; // Color opcional para badge
    icon?: string;  // Icono opcional
}

interface Props {
    modelValue: string | null;
    options: SelectOption[];
    canEdit?: boolean;
    loading?: boolean;
    disabled?: boolean;
    label?: string;
    placeholder?: string;
    // Mostrar badge con color
    showBadge?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    canEdit: true,
    loading: false,
    disabled: false,
    placeholder: 'Seleccionar...',
    showBadge: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    'save': [value: string];
}>();

// Referencia al wrapper
const wrapperRef = ref<InstanceType<typeof InlineEditWrapper> | null>(null);

// Valor temporal durante edición
const tempValue = ref(props.modelValue || '');

// Obtener la opción actual
const currentOption = computed(() => {
    return props.options.find(opt => opt.value === props.modelValue);
});

// Valor para display
const displayValue = computed(() => {
    return currentOption.value?.label || '';
});

// Sincronizar cuando cambia el valor externo
watch(() => props.modelValue, (newVal) => {
    if (!wrapperRef.value?.isEditing) {
        tempValue.value = newVal || '';
    }
});

// Guardar valor
const save = () => {
    // Solo emitir si cambió el valor
    if (tempValue.value !== props.modelValue) {
        emit('save', tempValue.value);
    } else {
        wrapperRef.value?.closeEditing();
    }
};

// Manejar inicio de edición
const handleEditStart = () => {
    tempValue.value = props.modelValue || '';
};

// Manejar cancelación
const handleEditCancel = () => {
    tempValue.value = props.modelValue || '';
};

// Manejar cambio de selección (auto-save)
const handleChange = (value: string) => {
    tempValue.value = value;
    save();
};

// Cerrar edición después de guardar exitoso
const closeAfterSave = () => {
    wrapperRef.value?.closeEditing();
};

defineExpose({
    closeAfterSave,
});
</script>

<template>
    <InlineEditWrapper
        ref="wrapperRef"
        :display-value="displayValue"
        :can-edit="canEdit"
        :loading="loading"
        :disabled="disabled"
        :label="label"
        :placeholder="placeholder"
        @edit-start="handleEditStart"
        @edit-cancel="handleEditCancel"
        @save="save"
    >
        <!-- Display con badge opcional -->
        <template #display>
            <span
                v-if="currentOption"
                :class="[
                    showBadge && currentOption.color ? 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium' : '',
                    showBadge && currentOption.color ? currentOption.color : ''
                ]"
            >
                {{ currentOption.label }}
            </span>
            <span v-else class="text-muted-foreground italic">{{ placeholder }}</span>
        </template>

        <template #edit>
            <Select
                :model-value="tempValue"
                :disabled="loading"
                @update:model-value="handleChange"
            >
                <SelectTrigger class="h-8 w-auto min-w-[150px]">
                    <SelectValue :placeholder="placeholder" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in options"
                        :key="option.value"
                        :value="option.value"
                    >
                        <span
                            :class="[
                                showBadge && option.color ? 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium' : '',
                                showBadge && option.color ? option.color : ''
                            ]"
                        >
                            {{ option.label }}
                        </span>
                    </SelectItem>
                </SelectContent>
            </Select>
        </template>
    </InlineEditWrapper>
</template>
