<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Trash2 } from 'lucide-vue-next';
import FilterCondition from './FilterCondition.vue';
import type { 
  FilterGroup as FilterGroupType,
  FilterCondition as FilterConditionType,
  FilterField,
  LogicalOperator,
} from '@/types/filters';

// Props
interface Props {
  group: FilterGroupType;
  fields: FilterField[];
  level?: number;
  maxNestingLevel?: number;
  onUpdateCondition: (conditionId: string, updates: Partial<FilterConditionType>) => void;
  onRemoveCondition: (conditionId: string) => void;
  onAddCondition: () => void;
  onUpdateOperator: (operator: LogicalOperator) => void;
  onAddGroup?: () => void;
  onRemoveGroup?: () => void;
  isRoot?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  level: 0,
  maxNestingLevel: 3,
  isRoot: false,
});

// Computed
const canAddSubgroup = computed(() => {
  return props.level < props.maxNestingLevel && props.onAddGroup;
});

const hasConditions = computed(() => {
  return props.group.conditions.length > 0;
});

const hasSubgroups = computed(() => {
  return props.group.groups && props.group.groups.length > 0;
});

const isEmpty = computed(() => {
  return !hasConditions.value && !hasSubgroups.value;
});

// Color de fondo según nivel de anidación
const bgColorClass = computed(() => {
  const colors = [
    '',
    'bg-muted/20',
    'bg-muted/40',
    'bg-muted/60',
  ];
  return colors[props.level] || 'bg-muted/80';
});

// Estilo del borde según nivel
const borderColorClass = computed(() => {
  const colors = [
    'border-border',
    'border-blue-200 dark:border-blue-800',
    'border-purple-200 dark:border-purple-800',
    'border-green-200 dark:border-green-800',
  ];
  return colors[props.level] || 'border-gray-200 dark:border-gray-800';
});
</script>

<template>
  <Card 
    :class="[
      'relative',
      bgColorClass,
      borderColorClass,
      { 'border-2': level > 0 }
    ]"
  >
    <CardContent class="p-4">
      <!-- Header del grupo -->
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
          <!-- Selector de operador lógico -->
          <Select 
            :model-value="group.operator"
            @update:model-value="onUpdateOperator"
            :disabled="group.conditions.length < 2 && (!group.groups || group.groups.length === 0)"
          >
            <SelectTrigger class="w-[100px] font-semibold">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="AND">Y</SelectItem>
              <SelectItem value="OR">O</SelectItem>
            </SelectContent>
          </Select>
          
          <!-- Indicador de nivel -->
          <span v-if="level > 0" class="text-xs text-muted-foreground">
            Nivel {{ level }}
          </span>
        </div>
        
        <!-- Botón eliminar grupo (no para el grupo raíz) -->
        <Button 
          v-if="!isRoot && onRemoveGroup"
          type="button"
          variant="ghost" 
          size="sm"
          @click="onRemoveGroup"
          class="text-destructive hover:text-destructive"
        >
          <Trash2 class="h-4 w-4" />
        </Button>
      </div>

      <!-- Lista de condiciones -->
      <div v-if="hasConditions" class="space-y-2 mb-3">
        <FilterCondition
          v-for="condition in group.conditions"
          :key="condition.id"
          :condition="condition"
          :fields="fields"
          :all-conditions="group.conditions"
          :on-update="(updates) => onUpdateCondition(condition.id, updates)"
          :on-remove="() => onRemoveCondition(condition.id)"
        />
      </div>

      <!-- Subgrupos -->
      <div v-if="hasSubgroups" class="space-y-3 mb-3">
        <div
          v-for="subgroup in group.groups"
          :key="subgroup.id"
          class="ml-4"
        >
          <div class="flex items-start gap-2">
            <!-- Línea conectora visual -->
            <div class="w-8 mt-6 border-t-2 border-dashed" :class="borderColorClass"></div>
            
            <!-- Subgrupo recursivo -->
            <div class="flex-1">
              <FilterGroup
                :group="subgroup"
                :fields="fields"
                :level="level + 1"
                :max-nesting-level="maxNestingLevel"
                :on-update-condition="(conditionId, updates) => $emit('update-subgroup-condition', conditionId, updates, subgroup.id)"
                :on-remove-condition="(conditionId) => $emit('remove-subgroup-condition', conditionId, subgroup.id)"
                :on-add-condition="() => $emit('add-subgroup-condition', subgroup.id)"
                :on-update-operator="(op) => $emit('update-subgroup-operator', subgroup.id, op)"
                :on-add-group="canAddSubgroup ? () => $emit('add-subgroup', subgroup.id) : undefined"
                :on-remove-group="() => $emit('remove-subgroup', subgroup.id, group.id)"
                @update-subgroup-condition="(condId, updates, groupId) => $emit('update-subgroup-condition', condId, updates, groupId)"
                @remove-subgroup-condition="(condId, groupId) => $emit('remove-subgroup-condition', condId, groupId)"
                @add-subgroup-condition="(groupId) => $emit('add-subgroup-condition', groupId)"
                @update-subgroup-operator="(groupId, op) => $emit('update-subgroup-operator', groupId, op)"
                @add-subgroup="(parentId) => $emit('add-subgroup', parentId)"
                @remove-subgroup="(groupId, parentId) => $emit('remove-subgroup', groupId, parentId)"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Mensaje cuando está vacío -->
      <div v-if="isEmpty" class="text-center py-8 text-muted-foreground">
        <p class="text-sm mb-2">No hay condiciones definidas</p>
        <p class="text-xs">Agrega una condición para comenzar a filtrar</p>
      </div>

      <!-- Botones de acción -->
      <div class="flex gap-2">
        <Button 
          type="button"
          variant="outline" 
          size="sm"
          @click="onAddCondition"
        >
          <Plus class="h-4 w-4 mr-2" />
          {{ group.operator === 'AND' ? 'Y...' : 'O...' }} (Añadir condición)
        </Button>
        
        <Button 
          v-if="canAddSubgroup"
          type="button"
          variant="outline" 
          size="sm"
          @click="onAddGroup"
        >
          <Plus class="h-4 w-4 mr-2" />
          {{ group.operator === 'AND' ? 'O...' : 'Y...' }} (Añadir nuevo grupo de filtros)
        </Button>
      </div>
    </CardContent>
  </Card>
</template>