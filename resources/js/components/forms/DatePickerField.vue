<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { X, Calendar } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    modelValue: string | Date | null;
    label: string;
    description?: string;
    required?: boolean;
    disabled?: boolean;
    error?: string;
    minDate?: string;
    maxDate?: string;
    format?: string;
    allowPastDates?: boolean;
    allowFutureDates?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    required: false,
    disabled: false,
    format: 'DD/MM/YYYY',
    allowPastDates: true,
    allowFutureDates: true,
});

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
}>();

// Usar computed para manejar el valor del input (como en DateTimePicker)
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
        
        // Formatear para input date (YYYY-MM-DD)
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        
        return `${year}-${month}-${day}`;
    },
    set: (value: string) => {
        if (!value) {
            emit('update:modelValue', null);
            return;
        }
        
        // Validar restricciones antes de emitir
        const dateValue = new Date(value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        dateValue.setHours(0, 0, 0, 0);
        
        // Validar fechas pasadas
        if (!props.allowPastDates && dateValue < today) {
            return;
        }
        
        // Validar fechas futuras
        if (!props.allowFutureDates && dateValue > today) {
            return;
        }
        
        // Emitir el valor como string ISO (mantener consistencia)
        emit('update:modelValue', value);
    },
});

// Calcular fecha mínima permitida
const computedMinDate = computed(() => {
    if (props.minDate) {
        return props.minDate;
    }
    if (!props.allowPastDates) {
        // Si no se permiten fechas pasadas, la fecha mínima es hoy
        return new Date().toISOString().split('T')[0];
    }
    return '';
});

// Calcular fecha máxima permitida
const computedMaxDate = computed(() => {
    if (props.maxDate) {
        return props.maxDate;
    }
    if (!props.allowFutureDates) {
        // Si no se permiten fechas futuras, la fecha máxima es hoy
        return new Date().toISOString().split('T')[0];
    }
    return '';
});

// Formatear fecha para mostrar
const formatDate = (dateString: string): string => {
    if (!dateString) return '';
    
    try {
        // Usar el formato ISO para evitar problemas de zona horaria
        const [year, month, day] = dateString.split('-');
        
        // Por ahora solo soportamos formato DD/MM/YYYY
        if (props.format === 'DD/MM/YYYY') {
            return `${day}/${month}/${year}`;
        }
        
        return dateString;
    } catch (error) {
        console.error('Error al formatear fecha:', error);
        return dateString;
    }
};

// Mostrar fecha formateada al usuario (solo visual, no afecta el valor)
const displayDate = computed(() => {
    if (!localValue.value) return '';
    return formatDate(localValue.value);
});

// Función para limpiar el campo
const clearDate = () => {
    emit('update:modelValue', null);
};

// Generar ID único para el campo
const fieldId = `datepicker-${Math.random().toString(36).substr(2, 9)}`;
</script>

<template>
    <div class="space-y-2">
        <Label v-if="label" :for="fieldId" class="text-sm font-medium">
            {{ label }}
            <span v-if="required" class="text-red-500 ml-1">*</span>
        </Label>
        
        <p v-if="description" class="text-sm text-muted-foreground">
            {{ description }}
        </p>
        
        <div class="relative">
            <div class="flex gap-2">
                <div class="flex-1 relative">
                    <Input
                        :id="fieldId"
                        type="date"
                        v-model="localValue"
                        :disabled="disabled"
                        :min="computedMinDate"
                        :max="computedMaxDate"
                        :class="[
                            'w-full',
                            error ? 'border-red-300 focus:border-red-500' : ''
                        ]"
                        :aria-invalid="!!error"
                        :aria-describedby="error ? `${fieldId}-error` : undefined"
                    />
                    <Calendar class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                </div>
                
                <!-- Botón para limpiar la fecha (solo si no es requerido y hay un valor) -->
                <Button
                    v-if="!required && localValue"
                    type="button"
                    variant="outline"
                    size="icon"
                    @click="clearDate"
                    :disabled="disabled"
                    title="Limpiar fecha"
                >
                    <X class="h-4 w-4" />
                </Button>
            </div>
            
            <!-- Mostrar fecha formateada como ayuda visual -->
            <p v-if="displayDate && format !== 'YYYY-MM-DD'" class="text-xs text-muted-foreground mt-1">
                Fecha seleccionada: {{ displayDate }}
            </p>
        </div>
        
        <!-- Mensaje de error -->
        <p v-if="error" :id="`${fieldId}-error`" class="text-sm text-red-600">
            {{ error }}
        </p>
        
        <!-- Información adicional sobre restricciones -->
        <div v-if="!allowPastDates || !allowFutureDates || minDate || maxDate" class="text-xs text-muted-foreground space-y-1">
            <p v-if="!allowPastDates && !minDate">
                * No se permiten fechas pasadas
            </p>
            <p v-if="!allowFutureDates && !maxDate">
                * No se permiten fechas futuras
            </p>
            <p v-if="minDate">
                * Fecha mínima: {{ formatDate(minDate) }}
            </p>
            <p v-if="maxDate">
                * Fecha máxima: {{ formatDate(maxDate) }}
            </p>
        </div>
    </div>
</template>