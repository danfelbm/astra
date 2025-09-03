<script setup lang="ts">
import { Badge } from "../../../ui/badge";
import { Input } from "../../../ui/input";
import { Label } from "../../../ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../../../ui/select";
import { computed } from 'vue';

interface NumberConfig {
    min?: number;
    max?: number;
    step?: number;
    decimals?: number;
}

interface Props {
    modelValue: NumberConfig;
    disabled?: boolean;
}

interface Emits {
    (e: 'update:modelValue', value: NumberConfig): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Opciones de decimales
const decimalOptions = [
    { value: 0, label: '0 (números enteros)' },
    { value: 1, label: '1 decimal' },
    { value: 2, label: '2 decimales' },
    { value: 3, label: '3 decimales' },
    { value: 4, label: '4 decimales' },
];

// Computed para manejar el modelValue
const localConfig = computed({
    get: () => props.modelValue || {
        min: undefined,
        max: undefined,
        step: 1,
        decimals: 0
    },
    set: (value: NumberConfig) => emit('update:modelValue', value)
});

const updateConfig = (key: keyof NumberConfig, value: any) => {
    // Convertir strings vacíos a undefined para min/max
    if ((key === 'min' || key === 'max') && value === '') {
        value = undefined;
    }
    // Asegurar que step y decimals sean números válidos
    if (key === 'step' && (value === '' || value < 0)) {
        value = 1;
    }
    if (key === 'decimals' && (value === '' || value < 0)) {
        value = 0;
    }
    
    emit('update:modelValue', {
        ...localConfig.value,
        [key]: value === '' || value === null ? undefined : Number(value)
    });
};

// Computed para validaciones
const hasValidRange = computed(() => {
    const { min, max } = localConfig.value;
    if (min !== undefined && max !== undefined) {
        return min <= max;
    }
    return true;
});
</script>

<template>
    <div class="space-y-4">
        <div class="p-4 bg-muted/50 dark:bg-muted/20 rounded-lg space-y-4">
            <h4 class="font-medium flex items-center gap-2">
                <Badge variant="secondary" class="text-xs">Configuración Numérica</Badge>
            </h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Valor mínimo -->
                <div>
                    <Label>Valor mínimo (opcional)</Label>
                    <Input
                        type="number"
                        :model-value="localConfig.min"
                        @update:model-value="(value) => updateConfig('min', value)"
                        placeholder="Sin límite"
                        :disabled="disabled"
                    />
                    <p class="text-xs text-muted-foreground mt-1">
                        Dejar vacío para sin límite mínimo
                    </p>
                </div>
                
                <!-- Valor máximo -->
                <div>
                    <Label>Valor máximo (opcional)</Label>
                    <Input
                        type="number"
                        :model-value="localConfig.max"
                        @update:model-value="(value) => updateConfig('max', value)"
                        placeholder="Sin límite"
                        :disabled="disabled"
                    />
                    <p class="text-xs text-muted-foreground mt-1">
                        Dejar vacío para sin límite máximo
                    </p>
                </div>
                
                <!-- Incremento/Step -->
                <div>
                    <Label>Incremento (step)</Label>
                    <Input
                        type="number"
                        :model-value="localConfig.step"
                        @update:model-value="(value) => updateConfig('step', value)"
                        min="0.01"
                        step="0.01"
                        placeholder="1"
                        :disabled="disabled"
                    />
                    <p class="text-xs text-muted-foreground mt-1">
                        Valor de incremento para los botones +/-
                    </p>
                </div>
                
                <!-- Decimales permitidos -->
                <div>
                    <Label>Decimales permitidos</Label>
                    <Select 
                        :model-value="String(localConfig.decimals)" 
                        @update:model-value="(value) => updateConfig('decimals', Number(value))"
                        :disabled="disabled"
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Selecciona decimales" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem 
                                v-for="option in decimalOptions"
                                :key="option.value"
                                :value="String(option.value)"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
            
            <!-- Validación de rango -->
            <div v-if="!hasValidRange" class="p-3 bg-destructive/10 dark:bg-destructive/20 rounded-md">
                <p class="text-sm text-destructive">
                    ⚠️ El valor mínimo no puede ser mayor al valor máximo
                </p>
            </div>
            
            <!-- Vista previa de configuración -->
            <div class="mt-4 p-3 bg-muted/30 dark:bg-muted/10 rounded-md">
                <p class="text-sm text-muted-foreground">
                    <strong>Resumen:</strong>
                    {{ localConfig.decimals === 0 ? 'Números enteros' : `Hasta ${localConfig.decimals} decimal${localConfig.decimals === 1 ? '' : 'es'}` }}.
                    Incremento de {{ localConfig.step || 1 }}.
                    <span v-if="localConfig.min !== undefined || localConfig.max !== undefined">
                        <br>
                        Rango: 
                        {{ localConfig.min !== undefined ? localConfig.min : '...' }} 
                        - 
                        {{ localConfig.max !== undefined ? localConfig.max : '...' }}
                    </span>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Estilos específicos para el config de number */
</style>