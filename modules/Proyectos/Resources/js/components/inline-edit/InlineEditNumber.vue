<script setup lang="ts">
/**
 * InlineEditNumber - Componente para edición inline de números
 * Auto-save en blur o Enter
 */
import { ref, watch, nextTick } from 'vue';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import InlineEditWrapper from './InlineEditWrapper.vue';

interface Props {
    modelValue: number | null;
    canEdit?: boolean;
    loading?: boolean;
    disabled?: boolean;
    label?: string;
    placeholder?: string;
    min?: number;
    max?: number;
    step?: number;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    canEdit: true,
    loading: false,
    disabled: false,
    placeholder: '0',
    step: 1,
});

const emit = defineEmits<{
    'update:modelValue': [value: number | null];
    'save': [value: number | null];
}>();

// Referencia al wrapper
const wrapperRef = ref<InstanceType<typeof InlineEditWrapper> | null>(null);
// Referencia al componente Input (usamos $el para acceder al input nativo)
const inputRef = ref<InstanceType<typeof Input> | null>(null);

// Valor temporal durante edición (como string para el input)
const tempValue = ref(props.modelValue?.toString() || '');
// Error de validación
const validationError = ref<string | null>(null);

// Sincronizar cuando cambia el valor externo
watch(() => props.modelValue, (newVal) => {
    if (!wrapperRef.value?.isEditing) {
        tempValue.value = newVal?.toString() || '';
    }
});

// Validar valor
const validate = (): boolean => {
    validationError.value = null;
    const num = parseFloat(tempValue.value);

    if (tempValue.value && isNaN(num)) {
        validationError.value = 'Debe ser un número válido';
        return false;
    }

    if (props.min !== undefined && num < props.min) {
        validationError.value = `Mínimo: ${props.min}`;
        return false;
    }

    if (props.max !== undefined && num > props.max) {
        validationError.value = `Máximo: ${props.max}`;
        return false;
    }

    return true;
};

// Guardar valor
const save = () => {
    if (!validate()) return;

    const newValue = tempValue.value ? parseFloat(tempValue.value) : null;

    // Solo emitir si cambió el valor
    if (newValue !== props.modelValue) {
        emit('save', newValue);
    } else {
        wrapperRef.value?.closeEditing();
    }
};

// Manejar inicio de edición
const handleEditStart = () => {
    tempValue.value = props.modelValue?.toString() || '';
    validationError.value = null;

    // Enfocar input después de renderizar (accedemos al $el del componente Vue)
    nextTick(() => {
        const inputEl = inputRef.value?.$el as HTMLInputElement | undefined;
        inputEl?.focus();
        inputEl?.select();
    });
};

// Manejar cancelación
const handleEditCancel = () => {
    tempValue.value = props.modelValue?.toString() || '';
    validationError.value = null;
};

// Manejar Enter
const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        save();
    }
};

// Manejar blur (auto-save)
const handleBlur = (e: FocusEvent) => {
    const relatedTarget = e.relatedTarget as HTMLElement;
    if (relatedTarget?.closest('.inline-edit-wrapper')) {
        return;
    }
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
        :display-value="modelValue?.toString() || ''"
        :can-edit="canEdit"
        :loading="loading"
        :disabled="disabled"
        :label="label"
        :placeholder="placeholder"
        @edit-start="handleEditStart"
        @edit-cancel="handleEditCancel"
        @save="save"
    >
        <template #edit>
            <div class="space-y-1">
                <Input
                    ref="inputRef"
                    v-model="tempValue"
                    type="number"
                    :min="min"
                    :max="max"
                    :step="step"
                    :disabled="loading"
                    :class="{ 'border-red-500': validationError }"
                    class="h-8 w-24"
                    @keydown="handleKeydown"
                    @blur="handleBlur"
                />
                <p v-if="validationError" class="text-xs text-red-600">
                    {{ validationError }}
                </p>
            </div>
        </template>
    </InlineEditWrapper>
</template>
