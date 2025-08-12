<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { computed } from 'vue';

interface Props {
    modelValue?: Date | string | null;
    placeholder?: string;
    disabled?: boolean;
    class?: string;
    min?: string;
    max?: string;
}

interface Emits {
    (e: 'update:modelValue', value: string | null): void;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Seleccionar fecha y hora',
});

const emit = defineEmits<Emits>();

// Convertir el valor para el input datetime-local
const localValue = computed({
    get: () => {
        if (!props.modelValue) return '';
        
        let date: Date;
        if (props.modelValue instanceof Date) {
            date = props.modelValue;
        } else {
            date = new Date(props.modelValue);
        }
        
        // Verificar si la fecha es válida
        if (isNaN(date.getTime())) {
            return '';
        }
        
        // Formatear para datetime-local (YYYY-MM-DDTHH:mm)
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    },
    set: (value: string) => {
        if (!value) {
            emit('update:modelValue', null);
            return;
        }
        
        // Convertir el valor datetime-local a ISO string
        const date = new Date(value);
        const isoString = date.toISOString();
        emit('update:modelValue', isoString);
    },
});
</script>

<template>
    <Input
        v-model="localValue"
        type="datetime-local"
        :class="cn('w-full', props.class)"
        :disabled="disabled"
        :placeholder="placeholder"
        :min="min"
        :max="max"
    />
</template>