<script setup lang="ts">
/**
 * InlineEditText - Componente para edición inline de texto simple
 * Auto-save en blur o Enter
 */
import { ref, watch, nextTick, onMounted } from 'vue';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import InlineEditWrapper from './InlineEditWrapper.vue';

interface Props {
    modelValue: string | null;
    canEdit?: boolean;
    loading?: boolean;
    disabled?: boolean;
    label?: string;
    placeholder?: string;
    maxLength?: number;
    // Validación simple
    required?: boolean;
    minLength?: number;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    canEdit: true,
    loading: false,
    disabled: false,
    placeholder: 'Sin valor',
    maxLength: 255,
    required: false,
    minLength: 0,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    'save': [value: string];
}>();

// Referencia al wrapper
const wrapperRef = ref<InstanceType<typeof InlineEditWrapper> | null>(null);
// Referencia al componente Input (usamos $el para acceder al input nativo)
const inputRef = ref<InstanceType<typeof Input> | null>(null);

// Valor temporal durante edición
const tempValue = ref(props.modelValue || '');
// Error de validación
const validationError = ref<string | null>(null);

// Sincronizar cuando cambia el valor externo
watch(() => props.modelValue, (newVal) => {
    if (!wrapperRef.value?.isEditing) {
        tempValue.value = newVal || '';
    }
});

// Validar valor
const validate = (): boolean => {
    validationError.value = null;

    if (props.required && !tempValue.value.trim()) {
        validationError.value = 'Este campo es requerido';
        return false;
    }

    if (props.minLength && tempValue.value.length < props.minLength) {
        validationError.value = `Mínimo ${props.minLength} caracteres`;
        return false;
    }

    return true;
};

// Guardar valor
const save = () => {
    if (!validate()) return;

    const newValue = tempValue.value.trim();

    // Solo emitir si cambió el valor
    if (newValue !== (props.modelValue || '')) {
        emit('save', newValue);
    } else {
        // Si no cambió, solo cerrar
        wrapperRef.value?.closeEditing();
    }
};

// Manejar inicio de edición
const handleEditStart = () => {
    tempValue.value = props.modelValue || '';
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
    tempValue.value = props.modelValue || '';
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
    // No guardar si el click fue en el botón cancelar
    const relatedTarget = e.relatedTarget as HTMLElement;
    if (relatedTarget?.closest('.inline-edit-wrapper')) {
        return;
    }
    save();
};

// Cerrar edición después de guardar exitoso (llamado por el padre)
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
        :display-value="modelValue || ''"
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
                    :maxlength="maxLength"
                    :disabled="loading"
                    :class="{ 'border-red-500': validationError }"
                    class="h-8"
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
