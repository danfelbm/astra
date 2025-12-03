<script setup lang="ts">
/**
 * InlineEditDate - Componente para edición inline de fechas
 * Auto-save cuando se selecciona una fecha
 */
import { ref, watch, nextTick, computed } from 'vue';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import InlineEditWrapper from './InlineEditWrapper.vue';
import { format, parseISO, isValid } from 'date-fns';
import { es } from 'date-fns/locale';

interface Props {
    modelValue: string | null; // Formato ISO (YYYY-MM-DD)
    canEdit?: boolean;
    loading?: boolean;
    disabled?: boolean;
    label?: string;
    placeholder?: string;
    // Formato para mostrar
    displayFormat?: string;
    // Restricciones de fecha
    minDate?: string;
    maxDate?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    canEdit: true,
    loading: false,
    disabled: false,
    placeholder: 'Sin fecha',
    displayFormat: 'dd/MM/yyyy',
});

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
    'save': [value: string | null];
}>();

// Referencia al wrapper
const wrapperRef = ref<InstanceType<typeof InlineEditWrapper> | null>(null);
// Referencia al componente Input (usamos $el para acceder al input nativo)
const inputRef = ref<InstanceType<typeof Input> | null>(null);

// Valor temporal durante edición
const tempValue = ref(props.modelValue || '');

// Valor formateado para display
const displayValue = computed(() => {
    if (!props.modelValue) return '';
    try {
        const date = parseISO(props.modelValue);
        if (isValid(date)) {
            return format(date, props.displayFormat, { locale: es });
        }
    } catch {
        // Ignorar error de parseo
    }
    return props.modelValue;
});

// Sincronizar cuando cambia el valor externo
watch(() => props.modelValue, (newVal) => {
    if (!wrapperRef.value?.isEditing) {
        tempValue.value = newVal || '';
    }
});

// Guardar valor
const save = () => {
    const newValue = tempValue.value || null;

    // Solo emitir si cambió el valor
    if (newValue !== props.modelValue) {
        emit('save', newValue);
    } else {
        wrapperRef.value?.closeEditing();
    }
};

// Manejar inicio de edición
const handleEditStart = () => {
    tempValue.value = props.modelValue || '';

    // Enfocar input después de renderizar (accedemos al $el del componente Vue)
    nextTick(() => {
        const inputEl = inputRef.value?.$el as HTMLInputElement | undefined;
        inputEl?.focus();
        // Abrir el selector de fecha nativo
        inputEl?.showPicker?.();
    });
};

// Manejar cancelación
const handleEditCancel = () => {
    tempValue.value = props.modelValue || '';
};

// Manejar cambio de fecha (auto-save)
const handleChange = () => {
    save();
};

// Manejar blur
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
        <template #edit>
            <Input
                ref="inputRef"
                v-model="tempValue"
                type="date"
                :min="minDate"
                :max="maxDate"
                :disabled="loading"
                class="h-8 w-auto"
                @change="handleChange"
                @blur="handleBlur"
            />
        </template>
    </InlineEditWrapper>
</template>
