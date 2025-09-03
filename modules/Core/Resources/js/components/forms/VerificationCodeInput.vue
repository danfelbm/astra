<template>
    <div class="flex gap-2">
        <Input
            v-for="(digit, index) in digits"
            :key="index"
            :ref="el => inputRefs[index] = el"
            v-model="digits[index]"
            type="text"
            inputmode="numeric"
            pattern="[0-9]"
            maxlength="1"
            class="w-12 h-12 text-center text-lg font-semibold"
            :class="{
                'border-destructive': error,
                'border-green-500': isComplete && !error,
                'opacity-50': disabled
            }"
            :disabled="disabled"
            @input="handleInput(index, $event)"
            @keydown="handleKeydown(index, $event)"
            @paste="handlePaste"
            @focus="handleFocus(index)"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Input } from "../ui/input";

interface Props {
    modelValue?: string;
    length?: number;
    disabled?: boolean;
    error?: string;
    autoFocus?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    length: 6,
    disabled: false,
    autoFocus: false
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    'complete': [value: string];
}>();

const digits = ref<string[]>(Array(props.length).fill(''));
const inputRefs = ref<any[]>([]);

const isComplete = computed(() => {
    return digits.value.every(d => d !== '') && digits.value.join('').length === props.length;
});

// Sincronizar con modelValue
watch(() => props.modelValue, (newValue) => {
    if (newValue) {
        digits.value = newValue.split('').slice(0, props.length);
        // Rellenar con espacios vacíos si es necesario
        while (digits.value.length < props.length) {
            digits.value.push('');
        }
    } else {
        digits.value = Array(props.length).fill('');
    }
}, { immediate: true });

// Emitir cambios
watch(digits, (newDigits) => {
    const value = newDigits.join('');
    emit('update:modelValue', value);
    
    if (value.length === props.length && !value.includes('')) {
        emit('complete', value);
    }
}, { deep: true });

const handleInput = (index: number, event: Event) => {
    const target = event.target as HTMLInputElement;
    const value = target.value;
    
    // Solo permitir dígitos
    if (!/^\d*$/.test(value)) {
        target.value = '';
        digits.value[index] = '';
        return;
    }
    
    // Si se ingresa un valor, mover al siguiente campo
    if (value && index < props.length - 1) {
        const nextInput = inputRefs.value[index + 1];
        if (nextInput) {
            nextInput.$el?.querySelector('input')?.focus();
        }
    }
};

const handleKeydown = (index: number, event: KeyboardEvent) => {
    const key = event.key;
    
    // Manejar backspace
    if (key === 'Backspace') {
        event.preventDefault();
        
        if (!digits.value[index] && index > 0) {
            // Si el campo actual está vacío, ir al anterior
            const prevInput = inputRefs.value[index - 1];
            if (prevInput) {
                digits.value[index - 1] = '';
                prevInput.$el?.querySelector('input')?.focus();
            }
        } else {
            // Limpiar el campo actual
            digits.value[index] = '';
        }
    }
    
    // Manejar flechas
    if (key === 'ArrowLeft' && index > 0) {
        event.preventDefault();
        const prevInput = inputRefs.value[index - 1];
        prevInput?.$el?.querySelector('input')?.focus();
    }
    
    if (key === 'ArrowRight' && index < props.length - 1) {
        event.preventDefault();
        const nextInput = inputRefs.value[index + 1];
        nextInput?.$el?.querySelector('input')?.focus();
    }
    
    // Prevenir entrada de caracteres no numéricos
    if (!/^\d$/.test(key) && !['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight'].includes(key)) {
        event.preventDefault();
    }
};

const handlePaste = (event: ClipboardEvent) => {
    event.preventDefault();
    const pastedData = event.clipboardData?.getData('text') || '';
    const cleanedData = pastedData.replace(/\D/g, '').slice(0, props.length);
    
    if (cleanedData) {
        digits.value = cleanedData.split('');
        // Rellenar con espacios vacíos si es necesario
        while (digits.value.length < props.length) {
            digits.value.push('');
        }
        
        // Enfocar el último campo lleno o el siguiente vacío
        const lastFilledIndex = digits.value.findLastIndex(d => d !== '');
        const focusIndex = lastFilledIndex < props.length - 1 ? lastFilledIndex + 1 : lastFilledIndex;
        
        setTimeout(() => {
            inputRefs.value[focusIndex]?.$el?.querySelector('input')?.focus();
        }, 0);
    }
};

const handleFocus = (index: number) => {
    // Seleccionar todo el contenido al enfocar
    setTimeout(() => {
        const input = inputRefs.value[index]?.$el?.querySelector('input');
        if (input) {
            input.select();
        }
    }, 0);
};

// Auto focus en el primer campo si está habilitado
if (props.autoFocus) {
    setTimeout(() => {
        inputRefs.value[0]?.$el?.querySelector('input')?.focus();
    }, 100);
}
</script>

<style scoped>
/* Ocultar las flechas de número en Chrome, Safari, Edge */
input[type="text"]::-webkit-outer-spin-button,
input[type="text"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Ocultar las flechas de número en Firefox */
input[type="text"] {
    -moz-appearance: textfield;
}
</style>