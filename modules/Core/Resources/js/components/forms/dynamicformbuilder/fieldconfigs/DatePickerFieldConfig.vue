<script setup lang="ts">
import { Badge } from "../../../ui/badge";
import { Checkbox } from "../../../ui/checkbox";
import { Input } from "../../../ui/input";
import { Label } from "../../../ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../../../ui/select";
import { computed } from 'vue';

interface DatePickerConfig {
    minDate?: string;
    maxDate?: string;
    format?: string;
    allowPastDates?: boolean;
    allowFutureDates?: boolean;
}

interface Props {
    modelValue: DatePickerConfig;
    disabled?: boolean;
}

interface Emits {
    (e: 'update:modelValue', value: DatePickerConfig): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Formatos de fecha disponibles
const dateFormats = [
    { value: 'DD/MM/YYYY', label: 'DD/MM/YYYY (31/12/2024)' },
    { value: 'MM/DD/YYYY', label: 'MM/DD/YYYY (12/31/2024)' },
    { value: 'YYYY-MM-DD', label: 'YYYY-MM-DD (2024-12-31)' },
    { value: 'DD-MM-YYYY', label: 'DD-MM-YYYY (31-12-2024)' },
];

// Computed para manejar el modelValue
const localConfig = computed({
    get: () => props.modelValue || {
        minDate: undefined,
        maxDate: undefined,
        format: 'DD/MM/YYYY',
        allowPastDates: true,
        allowFutureDates: true
    },
    set: (value: DatePickerConfig) => emit('update:modelValue', value)
});

const updateConfig = (key: keyof DatePickerConfig, value: any) => {
    emit('update:modelValue', {
        ...localConfig.value,
        [key]: value
    });
};

// Función para formatear fecha para input[type="date"]
const formatDateForInput = (dateString?: string) => {
    if (!dateString) return '';
    // Si ya está en formato YYYY-MM-DD, devolverlo tal como está
    if (dateString.match(/^\d{4}-\d{2}-\d{2}$/)) return dateString;
    
    // Intentar convertir otros formatos a YYYY-MM-DD
    try {
        const date = new Date(dateString);
        return date.toISOString().split('T')[0];
    } catch {
        return '';
    }
};

// Función para convertir de input[type="date"] a string
const handleDateChange = (key: keyof DatePickerConfig, value: string) => {
    updateConfig(key, value || undefined);
};
</script>

<template>
    <div class="space-y-4">
        <div class="p-4 bg-muted/50 dark:bg-muted/20 rounded-lg space-y-4">
            <h4 class="font-medium flex items-center gap-2">
                <Badge variant="secondary" class="text-xs">Configuración de Fecha</Badge>
            </h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Formato de fecha -->
                <div class="md:col-span-2">
                    <Label>Formato de fecha</Label>
                    <Select 
                        :model-value="localConfig.format" 
                        @update:model-value="(value) => updateConfig('format', value)"
                        :disabled="disabled"
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Selecciona formato" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem 
                                v-for="format in dateFormats"
                                :key="format.value"
                                :value="format.value"
                            >
                                {{ format.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                
                <!-- Restricciones temporales -->
                <div>
                    <Label class="text-sm font-medium mb-3 block">Restricciones temporales</Label>
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                :checked="localConfig.allowPastDates"
                                @update:checked="(checked) => updateConfig('allowPastDates', checked)"
                                :disabled="disabled"
                            />
                            <Label class="text-sm">Permitir fechas pasadas</Label>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                :checked="localConfig.allowFutureDates"
                                @update:checked="(checked) => updateConfig('allowFutureDates', checked)"
                                :disabled="disabled"
                            />
                            <Label class="text-sm">Permitir fechas futuras</Label>
                        </div>
                    </div>
                </div>
                
                <!-- Rango de fechas -->
                <div class="space-y-3">
                    <Label class="text-sm font-medium">Rango de fechas (opcional)</Label>
                    
                    <div>
                        <Label class="text-xs text-muted-foreground">Fecha mínima</Label>
                        <Input
                            type="date"
                            :model-value="formatDateForInput(localConfig.minDate)"
                            @update:model-value="(value) => handleDateChange('minDate', value)"
                            :disabled="disabled"
                        />
                    </div>
                    
                    <div>
                        <Label class="text-xs text-muted-foreground">Fecha máxima</Label>
                        <Input
                            type="date"
                            :model-value="formatDateForInput(localConfig.maxDate)"
                            @update:model-value="(value) => handleDateChange('maxDate', value)"
                            :disabled="disabled"
                        />
                    </div>
                </div>
            </div>
            
            <!-- Vista previa de configuración -->
            <div class="mt-4 p-3 bg-muted/30 dark:bg-muted/10 rounded-md">
                <p class="text-sm text-muted-foreground">
                    <strong>Resumen:</strong>
                    Formato: {{ localConfig.format }}.
                    {{ localConfig.allowPastDates && localConfig.allowFutureDates ? 'Sin restricciones temporales.' : '' }}
                    {{ localConfig.allowPastDates && !localConfig.allowFutureDates ? 'Solo fechas pasadas y actuales.' : '' }}
                    {{ !localConfig.allowPastDates && localConfig.allowFutureDates ? 'Solo fechas futuras y actuales.' : '' }}
                    {{ !localConfig.allowPastDates && !localConfig.allowFutureDates ? 'Solo fecha actual.' : '' }}
                    <span v-if="localConfig.minDate || localConfig.maxDate">
                        <br>
                        Rango: 
                        {{ localConfig.minDate ? formatDateForInput(localConfig.minDate) : '...' }} 
                        - 
                        {{ localConfig.maxDate ? formatDateForInput(localConfig.maxDate) : '...' }}
                    </span>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Estilos específicos para el config de datepicker */
</style>