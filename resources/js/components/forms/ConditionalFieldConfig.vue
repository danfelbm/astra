<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { AlertCircle, Plus, Trash2, Eye, EyeOff } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import type { FormField, ConditionalRule, ConditionalOperator } from '@/types/forms';

interface Props {
    modelValue?: {
        enabled: boolean;
        showWhen: 'all' | 'any';
        conditions: ConditionalRule[];
    };
    fields: FormField[];
    currentFieldId: string;
}

interface Emits {
    (e: 'update:modelValue', value: any): void;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => ({
        enabled: false,
        showWhen: 'all',
        conditions: [],
    }),
});

const emit = defineEmits<Emits>();

// Usar computed con get/set para evitar ciclos de actualización
const localConfig = computed({
    get() {
        return {
            enabled: props.modelValue?.enabled || false,
            showWhen: props.modelValue?.showWhen || 'all',
            conditions: props.modelValue?.conditions || [],
        };
    },
    set(value) {
        emit('update:modelValue', value);
    }
});

// Helper para actualizar propiedades específicas
const updateConfig = (updates: Partial<typeof localConfig.value>) => {
    localConfig.value = { ...localConfig.value, ...updates };
};

// Campos disponibles como fuente (solo select, radio, checkbox)
const availableSourceFields = computed(() => {
    return props.fields.filter(field => {
        // Excluir el campo actual
        if (field.id === props.currentFieldId) return false;
        // Solo estos tipos pueden ser fuente
        return ['select', 'radio', 'checkbox'].includes(field.type);
    });
});

// Operadores disponibles
const operators: { value: ConditionalOperator; label: string }[] = [
    { value: 'equals', label: 'Es igual a' },
    { value: 'not_equals', label: 'No es igual a' },
    { value: 'contains', label: 'Contiene' },
    { value: 'not_contains', label: 'No contiene' },
    { value: 'is_empty', label: 'Está vacío' },
    { value: 'is_not_empty', label: 'No está vacío' },
];

// Agregar nueva condición
const addCondition = () => {
    const newCondition: ConditionalRule = {
        fieldId: '',
        operator: 'equals',
        value: '',
    };
    const newConditions = [...localConfig.value.conditions, newCondition];
    updateConfig({ conditions: newConditions });
};

// Eliminar condición
const removeCondition = (index: number) => {
    const newConditions = localConfig.value.conditions.filter((_, i) => i !== index);
    updateConfig({ conditions: newConditions });
};

// Obtener opciones para un campo específico
const getFieldOptions = (fieldId: string): string[] => {
    const field = props.fields.find(f => f.id === fieldId);
    if (!field) return [];
    return field.options || [];
};

// Obtener nombre del campo por ID
const getFieldName = (fieldId: string): string => {
    const field = props.fields.find(f => f.id === fieldId);
    return field?.title || fieldId;
};

// Verificar si el operador requiere valor
const operatorRequiresValue = (operator: ConditionalOperator): boolean => {
    return !['is_empty', 'is_not_empty'].includes(operator);
};

// Actualizar valor de condición
const updateConditionValue = (index: number, value: string | string[]) => {
    const newConditions = [...localConfig.value.conditions];
    newConditions[index] = { ...newConditions[index], value };
    updateConfig({ conditions: newConditions });
};

// Actualizar campo fuente de condición
const updateConditionField = (index: number, fieldId: string) => {
    const newConditions = [...localConfig.value.conditions];
    newConditions[index] = { ...newConditions[index], fieldId, value: '' };
    updateConfig({ conditions: newConditions });
};

// Actualizar operador de condición
const updateConditionOperator = (index: number, operator: ConditionalOperator) => {
    const newConditions = [...localConfig.value.conditions];
    newConditions[index] = { 
        ...newConditions[index], 
        operator,
        value: operatorRequiresValue(operator) ? newConditions[index].value : undefined
    };
    updateConfig({ conditions: newConditions });
};

// Generar descripción de la regla
const ruleDescription = computed(() => {
    if (!localConfig.value.enabled || localConfig.value.conditions.length === 0) {
        return 'Sin condiciones configuradas';
    }

    const connector = localConfig.value.showWhen === 'all' ? ' Y ' : ' O ';
    
    const descriptions = localConfig.value.conditions.map(condition => {
        const fieldName = getFieldName(condition.fieldId);
        const operatorLabel = operators.find(o => o.value === condition.operator)?.label || condition.operator;
        
        if (!operatorRequiresValue(condition.operator)) {
            return `"${fieldName}" ${operatorLabel}`;
        }
        
        const valueText = Array.isArray(condition.value) 
            ? condition.value.join(', ')
            : condition.value || '[valor]';
            
        return `"${fieldName}" ${operatorLabel} "${valueText}"`;
    });

    return descriptions.join(connector);
});

// Estado de vista previa expandida
const showPreview = ref(false);
</script>

<template>
    <Card class="border-orange-200 bg-orange-50">
        <CardHeader>
            <CardTitle class="text-orange-900 flex items-center justify-between">
                <span>Configuración Condicional</span>
                <Badge v-if="localConfig.enabled" variant="secondary" class="bg-orange-100">
                    {{ localConfig.conditions.length }} condicion{{ localConfig.conditions.length !== 1 ? 'es' : '' }}
                </Badge>
            </CardTitle>
            <CardDescription class="text-orange-700">
                Define cuándo debe mostrarse este campo basándose en valores de otros campos
            </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Habilitar condiciones -->
            <div class="flex items-center space-x-2">
                <Checkbox
                    id="enable_conditions"
                    :checked="localConfig.enabled"
                    @update:checked="(checked) => updateConfig({ enabled: checked })"
                />
                <Label for="enable_conditions" class="text-sm font-medium">
                    Hacer este campo condicional
                    <span class="text-muted-foreground block text-xs">
                        El campo solo se mostrará cuando se cumplan las condiciones definidas
                    </span>
                </Label>
            </div>

            <!-- Configuración de condiciones -->
            <div v-if="localConfig.enabled" class="space-y-4">
                <!-- Verificar si hay campos disponibles -->
                <div v-if="availableSourceFields.length === 0" class="p-4 bg-amber-100 border border-amber-300 rounded-lg">
                    <div class="flex items-start gap-2">
                        <AlertCircle class="h-5 w-5 text-amber-600 mt-0.5" />
                        <div class="text-sm text-amber-800">
                            <p class="font-medium">No hay campos disponibles para condiciones</p>
                            <p class="mt-1">Agrega campos de tipo "Lista desplegable", "Opción múltiple" o "Casillas de verificación" antes de configurar condiciones.</p>
                        </div>
                    </div>
                </div>

                <template v-else>
                    <!-- Selector de lógica -->
                    <div>
                        <Label>Mostrar este campo cuando:</Label>
                        <Select 
                            :model-value="localConfig.showWhen"
                            @update:model-value="(value) => updateConfig({ showWhen: value })"
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Se cumplan TODAS las condiciones (AND)
                                </SelectItem>
                                <SelectItem value="any">
                                    Se cumpla ALGUNA condición (OR)
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Lista de condiciones -->
                    <div class="space-y-3">
                        <div 
                            v-for="(condition, index) in localConfig.conditions" 
                            :key="index"
                            class="p-3 bg-white border rounded-lg space-y-3"
                        >
                            <!-- Campo fuente -->
                            <div>
                                <Label class="text-xs">Campo a evaluar</Label>
                                <Select
                                    :model-value="condition.fieldId"
                                    @update:model-value="(value) => updateConditionField(index, value)"
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona un campo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="field in availableSourceFields"
                                            :key="field.id"
                                            :value="field.id"
                                        >
                                            {{ field.title }}
                                            <Badge variant="outline" class="ml-2 text-xs">
                                                {{ field.type }}
                                            </Badge>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Operador -->
                            <div>
                                <Label class="text-xs">Operador</Label>
                                <Select
                                    :model-value="condition.operator"
                                    @update:model-value="(value) => updateConditionOperator(index, value)"
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="op in operators"
                                            :key="op.value"
                                            :value="op.value"
                                        >
                                            {{ op.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Valor (si el operador lo requiere) -->
                            <div v-if="operatorRequiresValue(condition.operator) && condition.fieldId">
                                <Label class="text-xs">Valor</Label>
                                <Select
                                    v-if="getFieldOptions(condition.fieldId).length > 0"
                                    :model-value="condition.value as string"
                                    @update:model-value="(value) => updateConditionValue(index, value)"
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona un valor" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="option in getFieldOptions(condition.fieldId)"
                                            :key="option"
                                            :value="option"
                                        >
                                            {{ option }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-else class="text-xs text-muted-foreground mt-1">
                                    El campo seleccionado no tiene opciones definidas
                                </p>
                            </div>

                            <!-- Botón eliminar -->
                            <div class="flex justify-end">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="removeCondition(index)"
                                    class="text-destructive"
                                >
                                    <Trash2 class="h-4 w-4 mr-1" />
                                    Eliminar condición
                                </Button>
                            </div>
                        </div>

                        <!-- Botón agregar condición -->
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="addCondition"
                            class="w-full"
                        >
                            <Plus class="h-4 w-4 mr-2" />
                            Agregar condición
                        </Button>
                    </div>

                    <!-- Vista previa de la regla -->
                    <div v-if="localConfig.conditions.length > 0" class="mt-4">
                        <div class="flex items-center justify-between mb-2">
                            <Label class="text-xs">Vista previa de la regla:</Label>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="showPreview = !showPreview"
                            >
                                <component :is="showPreview ? EyeOff : Eye" class="h-4 w-4 mr-1" />
                                {{ showPreview ? 'Ocultar' : 'Mostrar' }}
                            </Button>
                        </div>
                        <div v-if="showPreview" class="p-3 bg-gray-100 rounded-lg">
                            <p class="text-sm text-gray-700">
                                Este campo se mostrará cuando:
                            </p>
                            <p class="text-sm font-medium text-gray-900 mt-1">
                                {{ ruleDescription }}
                            </p>
                        </div>
                    </div>
                </template>
            </div>
        </CardContent>
    </Card>
</template>