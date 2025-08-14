<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { 
  Search, 
  Filter, 
  X, 
  ChevronDown, 
  ChevronUp, 
  Save, 
  History,
  Trash2,
  Check,
  AlertCircle,
} from 'lucide-vue-next';
import FilterGroup from './FilterGroup.vue';
import { useAdvancedFilters } from '@/composables/useAdvancedFilters';
import type { AdvancedFilterConfig, PresetFilter } from '@/types/filters';

// Props
interface Props {
  config: AdvancedFilterConfig;
  route?: string;  // Opcional para modo AJAX
  routeParams?: Record<string, any>;
  preserveScroll?: boolean;
  preserveState?: boolean;
  initialFilters?: any;
}

const props = withDefaults(defineProps<Props>(), {
  preserveScroll: true,
  preserveState: true,
  routeParams: () => ({}),
});

// Emits
const emit = defineEmits<{
  apply: [filters: Record<string, any>];
  clear: [];
}>();

// Usar el composable
const {
  state,
  savedFilters,
  isLoading,
  hasFilters,
  activeConditionsCount,
  addCondition,
  removeCondition,
  updateCondition,
  addGroup,
  removeGroup,
  updateGroupOperator,
  applyFilters: applyFiltersBase,
  clearFilters: clearFiltersBase,
  toggleExpanded,
  saveAsPreset,
  loadPreset,
  deletePreset,
  validateFilters,
} = useAdvancedFilters({
  config: props.config,
  route: props.route,
  routeParams: props.routeParams,
  onApply: (filters) => emit('apply', filters),
  preserveScroll: props.preserveScroll,
  preserveState: props.preserveState,
  initialFilters: props.initialFilters,
});

// Estado local del componente
const showSaveDialog = ref(false);
const presetName = ref('');
const presetDescription = ref('');
const validationErrors = ref<string[]>([]);

// Computed
const quickSearchPlaceholder = computed(() => 
  props.config.quickSearchPlaceholder || 'Buscar...'
);

const showQuickSearch = computed(() => 
  props.config.showQuickSearch !== false
);

const expandButtonText = computed(() => 
  state.value.isExpanded ? 'Ocultar filtros avanzados' : 'Filtros avanzados'
);

const expandButtonIcon = computed(() => 
  state.value.isExpanded ? ChevronUp : ChevronDown
);

// Aplicar filtros con validación
const applyFilters = () => {
  validationErrors.value = [];
  
  if (validateFilters()) {
    applyFiltersBase();
  } else {
    validationErrors.value.push('Por favor corrige los errores en los filtros antes de aplicar.');
  }
};

// Limpiar filtros
const clearFilters = () => {
  clearFiltersBase();
  emit('clear');
};

// Guardar preset
const handleSavePreset = async () => {
  if (!presetName.value) {
    return;
  }
  
  await saveAsPreset(presetName.value, presetDescription.value);
  
  // Limpiar formulario
  presetName.value = '';
  presetDescription.value = '';
  showSaveDialog.value = false;
};

// Formatear fecha para mostrar
const formatDate = (date: Date) => {
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};
</script>

<template>
  <div class="space-y-4">
    <!-- Barra superior con búsqueda rápida y botón de filtros avanzados -->
    <Card>
      <CardContent class="p-4">
        <div class="flex items-center gap-2">
          <!-- Búsqueda rápida -->
          <div v-if="showQuickSearch" class="relative flex-1 max-w-md">
            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              v-model="state.quickSearch"
              :placeholder="quickSearchPlaceholder"
              class="pl-10"
              @keyup.enter="applyFilters"
            />
          </div>

          <!-- Botón de filtros avanzados -->
          <Button
            type="button"
            variant="outline"
            @click="toggleExpanded"
            :class="{ 'bg-primary text-primary-foreground': state.isExpanded }"
          >
            <Filter class="h-4 w-4 mr-2" />
            {{ expandButtonText }}
            <component :is="expandButtonIcon" class="h-4 w-4 ml-2" />
            
            <!-- Badge con contador de condiciones activas -->
            <Badge 
              v-if="activeConditionsCount > 0" 
              variant="secondary"
              class="ml-2"
            >
              {{ activeConditionsCount }}
            </Badge>
          </Button>

          <!-- Botones de acción rápida cuando hay filtros -->
          <div v-if="hasFilters" class="flex items-center gap-2">
            <Button
              type="button"
              variant="default"
              size="sm"
              @click="applyFilters"
              :disabled="isLoading || !state.hasChanges"
            >
              <Check class="h-4 w-4 mr-2" />
              Aplicar
            </Button>
            
            <Button
              type="button"
              variant="outline"
              size="sm"
              @click="clearFilters"
              :disabled="isLoading"
            >
              <X class="h-4 w-4 mr-2" />
              Limpiar
            </Button>
          </div>

          <!-- Menú de filtros guardados -->
          <DropdownMenu v-if="config.allowSaveFilters !== false">
            <DropdownMenuTrigger asChild>
              <Button type="button" variant="outline" size="icon">
                <History class="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-[250px]">
              <DropdownMenuLabel>Filtros Guardados</DropdownMenuLabel>
              <DropdownMenuSeparator />
              
              <div v-if="savedFilters.length === 0" class="p-2 text-sm text-muted-foreground text-center">
                No hay filtros guardados
              </div>
              
              <DropdownMenuItem
                v-for="preset in savedFilters"
                :key="preset.id"
                @click="loadPreset(preset.id)"
                class="flex items-center justify-between"
              >
                <div class="flex-1">
                  <div class="font-medium">{{ preset.name }}</div>
                  <div v-if="preset.description" class="text-xs text-muted-foreground">
                    {{ preset.description }}
                  </div>
                  <div class="text-xs text-muted-foreground">
                    {{ formatDate(preset.createdAt) }}
                  </div>
                </div>
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  class="h-6 w-6 p-0"
                  @click.stop="deletePreset(preset.id)"
                >
                  <Trash2 class="h-3 w-3" />
                </Button>
              </DropdownMenuItem>
              
              <DropdownMenuSeparator />
              
              <Dialog v-model:open="showSaveDialog">
                <DialogTrigger asChild>
                  <DropdownMenuItem 
                    @click.prevent="showSaveDialog = true"
                    :disabled="!hasFilters"
                  >
                    <Save class="h-4 w-4 mr-2" />
                    Guardar filtro actual
                  </DropdownMenuItem>
                </DialogTrigger>
                <DialogContent>
                  <DialogHeader>
                    <DialogTitle>Guardar Filtro</DialogTitle>
                    <DialogDescription>
                      Guarda la configuración actual de filtros para reutilizarla más tarde.
                    </DialogDescription>
                  </DialogHeader>
                  <div class="space-y-4 py-4">
                    <div>
                      <label class="text-sm font-medium">Nombre</label>
                      <Input
                        v-model="presetName"
                        placeholder="Ej: Usuarios activos de Lima"
                        class="mt-1"
                      />
                    </div>
                    <div>
                      <label class="text-sm font-medium">Descripción (opcional)</label>
                      <Input
                        v-model="presetDescription"
                        placeholder="Describe este filtro..."
                        class="mt-1"
                      />
                    </div>
                  </div>
                  <DialogFooter>
                    <Button type="button" variant="outline" @click="showSaveDialog = false">
                      Cancelar
                    </Button>
                    <Button type="button" @click="handleSavePreset" :disabled="!presetName">
                      Guardar
                    </Button>
                  </DialogFooter>
                </DialogContent>
              </Dialog>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </CardContent>
    </Card>

    <!-- Panel de filtros avanzados expandible -->
    <Collapsible v-model:open="state.isExpanded">
      <CollapsibleContent>
        <Card>
          <CardHeader>
            <CardTitle>Filtros Avanzados</CardTitle>
            <CardDescription>
              Construye consultas complejas combinando múltiples condiciones con operadores lógicos.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <!-- Errores de validación -->
            <div v-if="validationErrors.length > 0" class="mb-4">
              <div 
                v-for="(error, index) in validationErrors" 
                :key="index"
                class="flex items-center gap-2 p-2 text-sm text-destructive bg-destructive/10 rounded"
              >
                <AlertCircle class="h-4 w-4" />
                {{ error }}
              </div>
            </div>

            <!-- Grupo raíz de filtros -->
            <FilterGroup
              :group="state.rootGroup"
              :fields="config.fields"
              :level="0"
              :max-nesting-level="config.maxNestingLevel || 3"
              :is-root="true"
              :on-update-condition="(conditionId, updates) => updateCondition(conditionId, updates)"
              :on-remove-condition="(conditionId) => removeCondition(conditionId)"
              :on-add-condition="() => addCondition()"
              :on-update-operator="(operator) => updateGroupOperator(state.rootGroup.id, operator)"
              :on-add-group="() => addGroup()"
              @update-subgroup-condition="(conditionId, updates, groupId) => updateCondition(conditionId, updates, groupId)"
              @remove-subgroup-condition="(conditionId, groupId) => removeCondition(conditionId, groupId)"
              @add-subgroup-condition="(groupId) => addCondition(groupId)"
              @update-subgroup-operator="(groupId, operator) => updateGroupOperator(groupId, operator)"
              @add-subgroup="(parentId) => addGroup(parentId)"
              @remove-subgroup="(groupId, parentId) => removeGroup(groupId, parentId)"
            />

            <!-- Botones de acción -->
            <div class="flex items-center justify-between mt-6 pt-4 border-t">
              <div class="text-sm text-muted-foreground">
                <span v-if="activeConditionsCount > 0">
                  {{ activeConditionsCount }} condicion(es) activa(s)
                </span>
                <span v-else>
                  No hay condiciones activas
                </span>
              </div>

              <div class="flex items-center gap-2">
                <Button
                  type="button"
                  variant="outline"
                  @click="clearFilters"
                  :disabled="isLoading || !hasFilters"
                >
                  <X class="h-4 w-4 mr-2" />
                  Limpiar todo
                </Button>
                
                <Button
                  type="button"
                  variant="default"
                  @click="applyFilters"
                  :disabled="isLoading || (!state.hasChanges && !config.autoApply)"
                >
                  <Check class="h-4 w-4 mr-2" />
                  Aplicar filtros
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      </CollapsibleContent>
    </Collapsible>

    <!-- Indicador de carga -->
    <div v-if="isLoading" class="fixed bottom-4 right-4 z-50">
      <Card class="shadow-lg">
        <CardContent class="p-3 flex items-center gap-2">
          <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary"></div>
          <span class="text-sm">Aplicando filtros...</span>
        </CardContent>
      </Card>
    </div>
  </div>
</template>