<script setup lang="ts">
/**
 * InlineEditTextarea - Componente para edición inline de textos largos
 * Usa botón confirmar en lugar de auto-save (por ser multilinea)
 */
import { ref, watch, nextTick, computed } from 'vue';
import { Textarea } from '@modules/Core/Resources/js/components/ui/textarea';
import InlineEditWrapper from './InlineEditWrapper.vue';

interface Props {
    modelValue: string | null;
    canEdit?: boolean;
    loading?: boolean;
    disabled?: boolean;
    label?: string;
    placeholder?: string;
    maxLength?: number;
    rows?: number;
    // Mostrar preview truncado
    truncateDisplay?: number;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    canEdit: true,
    loading: false,
    disabled: false,
    placeholder: 'Sin descripción',
    maxLength: 5000,
    rows: 3,
    truncateDisplay: 150,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    'save': [value: string];
}>();

// Referencia al wrapper
const wrapperRef = ref<InstanceType<typeof InlineEditWrapper> | null>(null);
// Referencia al componente Textarea (usamos $el para acceder al textarea nativo)
const textareaRef = ref<InstanceType<typeof Textarea> | null>(null);

// Valor temporal durante edición
const tempValue = ref(props.modelValue || '');

// Valor truncado para display
const displayValue = computed(() => {
    const val = props.modelValue || '';
    if (props.truncateDisplay && val.length > props.truncateDisplay) {
        return val.substring(0, props.truncateDisplay) + '...';
    }
    return val;
});

// Sincronizar cuando cambia el valor externo
watch(() => props.modelValue, (newVal) => {
    if (!wrapperRef.value?.isEditing) {
        tempValue.value = newVal || '';
    }
});

// Guardar valor
const save = () => {
    const newValue = tempValue.value.trim();

    // Solo emitir si cambió el valor
    if (newValue !== (props.modelValue || '')) {
        emit('save', newValue);
    } else {
        wrapperRef.value?.closeEditing();
    }
};

// Manejar inicio de edición
const handleEditStart = () => {
    tempValue.value = props.modelValue || '';

    // Enfocar textarea después de renderizar (accedemos al $el del componente Vue)
    nextTick(() => {
        const textareaEl = textareaRef.value?.$el as HTMLTextAreaElement | undefined;
        textareaEl?.focus();
    });
};

// Manejar cancelación
const handleEditCancel = () => {
    tempValue.value = props.modelValue || '';
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
        :show-confirm-button="true"
        @edit-start="handleEditStart"
        @edit-cancel="handleEditCancel"
        @save="save"
    >
        <!-- Display personalizado para preservar saltos de línea -->
        <template #display>
            <span class="whitespace-pre-wrap">{{ displayValue || placeholder }}</span>
        </template>

        <template #edit>
            <Textarea
                ref="textareaRef"
                v-model="tempValue"
                :maxlength="maxLength"
                :rows="rows"
                :disabled="loading"
                class="min-h-[80px] resize-y"
            />
        </template>
    </InlineEditWrapper>
</template>
