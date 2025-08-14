<script setup lang="ts">
import { computed, watch, ref } from 'vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import DatePicker from '@/components/ui/date-picker/DatePicker.vue';
import DateTimePicker from '@/components/ui/datetime-picker/DateTimePicker.vue';
import CascadeSelect from './CascadeSelect.vue';
import { X } from 'lucide-vue-next';
import type { 
  FilterCondition, 
  FilterField, 
  FilterOperator,
  operatorLabels,
  operatorsByFieldType,
} from '@/types/filters';

// Props
interface Props {
  condition: FilterCondition;
  fields: FilterField[];
  onUpdate: (updates: Partial<FilterCondition>) => void;
  onRemove: () => void;
  allConditions?: FilterCondition[]; // Para acceder a valores de campos padre en cascadas
}

const props = defineProps<Props>();

// Campo seleccionado actual
const selectedField = computed(() => 
  props.fields.find(f => f.name === (props.condition.field || props.condition.name))
);

// Tipo de campo actual
const fieldType = computed(() => selectedField.value?.type || 'text');

// Obtener el valor del campo padre para cascadas
const parentFieldValue = computed(() => {
  if (!selectedField.value?.cascadeFrom || !props.allConditions) {
    return null;
  }
  
  // Buscar la condición del campo padre
  const parentCondition = props.allConditions.find(
    c => (c.field === selectedField.value?.cascadeFrom || c.name === selectedField.value?.cascadeFrom) && c.id !== props.condition.id
  );
  
  return parentCondition?.value || null;
});

// Operadores disponibles para el campo actual
const availableOperators = computed(() => {
  if (selectedField.value?.operators) {
    return selectedField.value.operators;
  }
  
  // Importar los operadores por tipo de campo
  const operators = {
    text: ['equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'ends_with', 'is_empty', 'is_not_empty'],
    number: ['equals', 'not_equals', 'greater_than', 'less_than', 'greater_or_equal', 'less_or_equal', 'between', 'is_empty', 'is_not_empty'],
    date: ['equals', 'not_equals', 'greater_than', 'less_than', 'greater_or_equal', 'less_or_equal', 'between', 'is_empty', 'is_not_empty'],
    datetime: ['equals', 'not_equals', 'greater_than', 'less_than', 'greater_or_equal', 'less_or_equal', 'between', 'is_empty', 'is_not_empty'],
    select: ['equals', 'not_equals', 'is_empty', 'is_not_empty'],
    'select-cascade': ['equals', 'not_equals', 'is_empty', 'is_not_empty'],
    multiselect: ['in', 'not_in', 'is_empty', 'is_not_empty'],
    boolean: ['equals'],
  } as const;
  
  return operators[fieldType.value as keyof typeof operators] || ['equals'];
});

// Labels de operadores
const operatorLabelsMap: Record<string, string> = {
  equals: 'Es igual a',
  not_equals: 'No es igual a',
  contains: 'Contiene',
  not_contains: 'No contiene',
  starts_with: 'Empieza con',
  ends_with: 'Termina con',
  is_empty: 'Está vacío',
  is_not_empty: 'No está vacío',
  greater_than: 'Mayor que',
  less_than: 'Menor que',
  greater_or_equal: 'Mayor o igual que',
  less_or_equal: 'Menor o igual que',
  between: 'Entre',
  in: 'En lista',
  not_in: 'No en lista',
};

// Si el operador actual requiere valor
const requiresValue = computed(() => {
  return !['is_empty', 'is_not_empty'].includes(props.condition.operator);
});

// Si el operador requiere dos valores (between)
const requiresTwoValues = computed(() => {
  return props.condition.operator === 'between';
});

// Manejar cambio de campo
const handleFieldChange = (fieldName: string) => {
  const field = props.fields.find(f => f.name === fieldName);
  if (field) {
    // Preservar el objeto completo y actualizar solo lo necesario
    props.onUpdate({
      ...props.condition,
      field: fieldName,
      name: fieldName, // Incluir ambas claves para compatibilidad
      operator: availableOperators.value[0] as FilterOperator,
      value: field.defaultValue || null,
    });
  }
};

// Manejar cambio de operador
const handleOperatorChange = (operator: string) => {
  props.onUpdate({ 
    ...props.condition,
    operator: operator as FilterOperator,
    // Limpiar valor si el operador no lo requiere
    value: ['is_empty', 'is_not_empty'].includes(operator) ? null : props.condition.value,
  });
};

// Manejar cambio de valor
const handleValueChange = (value: any) => {
  props.onUpdate({ 
    ...props.condition,
    value 
  });
};

// Manejar cambio de valor para between (array de 2 valores)
const handleBetweenChange = (index: 0 | 1, value: any) => {
  const currentValue = Array.isArray(props.condition.value) 
    ? [...props.condition.value] 
    : [null, null];
  currentValue[index] = value;
  props.onUpdate({ 
    ...props.condition,
    value: currentValue 
  });
};
</script>

<template>
  <div class="flex items-center gap-2">
    <!-- Selector de campo -->
    <Select 
      :model-value="condition.field || condition.name"
      @update:model-value="handleFieldChange"
    >
      <SelectTrigger class="w-[200px]">
        <SelectValue placeholder="Seleccionar campo..." />
      </SelectTrigger>
      <SelectContent>
        <SelectItem 
          v-for="field in fields" 
          :key="field.name"
          :value="field.name"
        >
          {{ field.label }}
        </SelectItem>
      </SelectContent>
    </Select>

    <!-- Selector de operador -->
    <Select 
      :model-value="condition.operator"
      @update:model-value="handleOperatorChange"
      :disabled="!condition.field && !condition.name"
    >
      <SelectTrigger class="w-[180px]">
        <SelectValue placeholder="Seleccionar operador..." />
      </SelectTrigger>
      <SelectContent>
        <SelectItem 
          v-for="operator in availableOperators" 
          :key="operator"
          :value="operator"
        >
          {{ operatorLabelsMap[operator] }}
        </SelectItem>
      </SelectContent>
    </Select>

    <!-- Campo de valor -->
    <div v-if="requiresValue" class="flex-1">
      <!-- Between: dos campos -->
      <div v-if="requiresTwoValues" class="flex items-center gap-2">
        <!-- Primer valor -->
        <div class="flex-1">
          <Input 
            v-if="fieldType === 'text'"
            :model-value="Array.isArray(condition.value) ? condition.value[0] : ''"
            @update:model-value="(v) => handleBetweenChange(0, v)"
            :placeholder="selectedField?.placeholder || 'Desde...'"
          />
          <Input 
            v-else-if="fieldType === 'number'"
            type="number"
            :model-value="Array.isArray(condition.value) ? condition.value[0] : ''"
            @update:model-value="(v) => handleBetweenChange(0, v)"
            placeholder="Desde..."
          />
          <DatePicker 
            v-else-if="fieldType === 'date'"
            :model-value="Array.isArray(condition.value) ? condition.value[0] : null"
            @update:model-value="(v) => handleBetweenChange(0, v)"
          />
          <DateTimePicker 
            v-else-if="fieldType === 'datetime'"
            :model-value="Array.isArray(condition.value) ? condition.value[0] : null"
            @update:model-value="(v) => handleBetweenChange(0, v)"
          />
        </div>
        
        <span class="text-muted-foreground">y</span>
        
        <!-- Segundo valor -->
        <div class="flex-1">
          <Input 
            v-if="fieldType === 'text'"
            :model-value="Array.isArray(condition.value) ? condition.value[1] : ''"
            @update:model-value="(v) => handleBetweenChange(1, v)"
            :placeholder="selectedField?.placeholder || 'Hasta...'"
          />
          <Input 
            v-else-if="fieldType === 'number'"
            type="number"
            :model-value="Array.isArray(condition.value) ? condition.value[1] : ''"
            @update:model-value="(v) => handleBetweenChange(1, v)"
            placeholder="Hasta..."
          />
          <DatePicker 
            v-else-if="fieldType === 'date'"
            :model-value="Array.isArray(condition.value) ? condition.value[1] : null"
            @update:model-value="(v) => handleBetweenChange(1, v)"
          />
          <DateTimePicker 
            v-else-if="fieldType === 'datetime'"
            :model-value="Array.isArray(condition.value) ? condition.value[1] : null"
            @update:model-value="(v) => handleBetweenChange(1, v)"
          />
        </div>
      </div>
      
      <!-- Valor único -->
      <div v-else>
        <!-- Input de texto -->
        <Input 
          v-if="fieldType === 'text'"
          :model-value="condition.value || ''"
          @update:model-value="handleValueChange"
          :placeholder="selectedField?.placeholder || 'Ingrese un valor...'"
        />
        
        <!-- Input numérico -->
        <Input 
          v-else-if="fieldType === 'number'"
          type="number"
          :model-value="condition.value || ''"
          @update:model-value="handleValueChange"
          :placeholder="selectedField?.placeholder || 'Ingrese un número...'"
        />
        
        <!-- Date picker -->
        <DatePicker 
          v-else-if="fieldType === 'date'"
          :model-value="condition.value"
          @update:model-value="handleValueChange"
        />
        
        <!-- DateTime picker -->
        <DateTimePicker 
          v-else-if="fieldType === 'datetime'"
          :model-value="condition.value"
          @update:model-value="handleValueChange"
        />
        
        <!-- Select único -->
        <Select 
          v-else-if="fieldType === 'select'"
          :model-value="condition.value"
          @update:model-value="handleValueChange"
        >
          <SelectTrigger>
            <SelectValue :placeholder="selectedField?.placeholder || 'Seleccionar...'" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem 
              v-for="option in selectedField?.options || []" 
              :key="option.value"
              :value="option.value"
              :disabled="option.disabled"
            >
              {{ option.label }}
            </SelectItem>
          </SelectContent>
        </Select>
        
        <!-- Select en cascada -->
        <CascadeSelect
          v-else-if="fieldType === 'select-cascade' && selectedField"
          :model-value="condition.value"
          @update:model-value="handleValueChange"
          :field="selectedField"
          :parent-value="parentFieldValue"
        />
        
        <!-- Boolean -->
        <Select 
          v-else-if="fieldType === 'boolean'"
          :model-value="condition.value"
          @update:model-value="handleValueChange"
        >
          <SelectTrigger>
            <SelectValue placeholder="Seleccionar..." />
          </SelectTrigger>
          <SelectContent>
            <SelectItem :value="true">Sí</SelectItem>
            <SelectItem :value="false">No</SelectItem>
          </SelectContent>
        </Select>
        
        <!-- TODO: Multiselect para operadores in/not_in -->
      </div>
    </div>
    
    <!-- Placeholder cuando no requiere valor -->
    <div v-else class="flex-1 text-muted-foreground text-sm px-3">
      (sin valor)
    </div>

    <!-- Botón eliminar -->
    <Button 
      type="button"
      variant="ghost" 
      size="sm"
      @click="onRemove"
      class="text-destructive hover:text-destructive"
    >
      <X class="h-4 w-4" />
    </Button>
  </div>
</template>