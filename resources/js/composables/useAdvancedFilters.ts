import { ref, computed, watch, Ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { debounce } from '@/lib/utils';
import type {
  AdvancedFilterConfig,
  AdvancedFilterState,
  FilterGroup,
  FilterCondition,
  FilterField,
  PresetFilter,
  LogicalOperator,
} from '@/types/filters';
import {
  createEmptyFilterGroup,
  createEmptyCondition,
  filterStateToQueryParams,
  hasActiveFilters,
  countActiveConditions,
} from '@/utils/filters';

export interface UseAdvancedFiltersOptions {
  // Configuración del filtro
  config: AdvancedFilterConfig;
  
  // URL para aplicar los filtros (ruta de Inertia) - opcional para modo AJAX
  route?: string;
  
  // Parámetros adicionales para la ruta
  routeParams?: Record<string, any>;
  
  // Callback cuando se aplican los filtros
  onApply?: (filters: Record<string, any>) => void;
  
  // Si preservar el scroll al aplicar filtros
  preserveScroll?: boolean;
  
  // Si preservar el estado al aplicar filtros
  preserveState?: boolean;
  
  // Filtros iniciales
  initialFilters?: Partial<AdvancedFilterState>;
}

export function useAdvancedFilters(options: UseAdvancedFiltersOptions) {
  const {
    config,
    route,
    routeParams = {},
    onApply,
    preserveScroll = true,
    preserveState = true,
    initialFilters = {},
  } = options;

  // Estado del filtro
  
  // Procesar el rootGroup inicial para asegurar que las condiciones tengan IDs
  const processInitialGroup = (group: FilterGroup): FilterGroup => {
    return {
      ...group,
      id: group.id || crypto.randomUUID(),
      conditions: group.conditions?.map(c => ({
        ...c,
        id: c.id || crypto.randomUUID(),
        // Normalizar field/name
        field: c.field || c.name,
        name: c.name || c.field,
      })) || [],
      groups: group.groups?.map(processInitialGroup) || []
    };
  };
  
  const state = ref<AdvancedFilterState>({
    quickSearch: initialFilters.quickSearch || '',
    rootGroup: initialFilters.rootGroup ? processInitialGroup(initialFilters.rootGroup) : createEmptyFilterGroup(),
    isExpanded: initialFilters.isExpanded || false,
    hasChanges: false,
  });

  // Filtros guardados
  const savedFilters = ref<PresetFilter[]>([]);
  
  // Cargando estado
  const isLoading = ref(false);
  
  // Contador de resultados
  const resultCount = ref<number | null>(null);

  // Computed: Tiene filtros activos
  const hasFilters = computed(() => hasActiveFilters(state.value));
  
  // Computed: Número de condiciones activas
  const activeConditionsCount = computed(() => 
    countActiveConditions(state.value.rootGroup)
  );

  // Computed: Query params actuales
  const currentQueryParams = computed(() => 
    filterStateToQueryParams(state.value)
  );

  // Agregar una nueva condición
  const addCondition = (groupId?: string) => {
    const targetGroup = groupId 
      ? findGroupById(state.value.rootGroup, groupId)
      : state.value.rootGroup;
    
    if (targetGroup) {
      const firstField = config.fields[0];
      const newCondition = createEmptyCondition(firstField?.name);
      if (!targetGroup.conditions) {
        targetGroup.conditions = [];
      }
      targetGroup.conditions.push(newCondition);
      state.value.hasChanges = true;
    }
  };

  // Eliminar una condición
  const removeCondition = (conditionId: string, groupId?: string) => {
    // Buscar en qué grupo está la condición
    const findAndRemoveCondition = (group: FilterGroup): boolean => {
      // Buscar en las condiciones del grupo actual
      const index = group.conditions.findIndex(c => c.id === conditionId);
      if (index !== -1) {
        group.conditions.splice(index, 1);
        return true;
      }
      
      // Buscar en subgrupos
      if (group.groups) {
        for (const subGroup of group.groups) {
          if (findAndRemoveCondition(subGroup)) {
            return true;
          }
        }
      }
      
      return false;
    };
    
    // Si se especifica un groupId, buscar directamente en ese grupo
    if (groupId) {
      const targetGroup = findGroupById(state.value.rootGroup, groupId);
      if (targetGroup) {
        targetGroup.conditions = targetGroup.conditions.filter(
          c => c.id !== conditionId
        );
        state.value.hasChanges = true;
      }
    } else {
      // Si no, buscar en toda la estructura
      findAndRemoveCondition(state.value.rootGroup);
      state.value.hasChanges = true;
    }
  };

  // Actualizar una condición
  const updateCondition = (
    conditionId: string, 
    updates: Partial<FilterCondition>,
    groupId?: string
  ) => {
    // Buscar la condición en el grupo especificado o en toda la estructura
    const findAndUpdateCondition = (group: FilterGroup): boolean => {
      // Buscar en las condiciones del grupo actual
      const conditionIndex = group.conditions.findIndex(c => c.id === conditionId);
      if (conditionIndex !== -1) {
        // Crear una nueva condición con las actualizaciones para mantener la inmutabilidad
        const updatedCondition = {
          ...group.conditions[conditionIndex],
          ...updates
        };
        // Si tiene 'field' y 'name', mantener ambos sincronizados
        if (updates.field || updates.name) {
          updatedCondition.field = updates.field || updates.name;
          updatedCondition.name = updates.name || updates.field;
        }
        // Reemplazar la condición en el array
        group.conditions[conditionIndex] = updatedCondition;
        return true;
      }
      
      // Buscar en subgrupos
      if (group.groups) {
        for (const subGroup of group.groups) {
          if (findAndUpdateCondition(subGroup)) {
            return true;
          }
        }
      }
      
      return false;
    };
    
    if (groupId) {
      const targetGroup = findGroupById(state.value.rootGroup, groupId);
      if (targetGroup) {
        const condition = targetGroup.conditions.find(c => c.id === conditionId);
        if (condition) {
          Object.assign(condition, updates);
          state.value.hasChanges = true;
        }
      }
    } else {
      findAndUpdateCondition(state.value.rootGroup);
      state.value.hasChanges = true;
    }
  };

  // Agregar un nuevo grupo
  const addGroup = (parentGroupId?: string, operator: LogicalOperator = 'AND') => {
    const targetGroup = parentGroupId 
      ? findGroupById(state.value.rootGroup, parentGroupId)
      : state.value.rootGroup;
    
    if (targetGroup) {
      if (!targetGroup.groups) {
        targetGroup.groups = [];
      }
      
      const newGroup = createEmptyFilterGroup(operator);
      targetGroup.groups.push(newGroup);
      state.value.hasChanges = true;
    }
  };

  // Eliminar un grupo
  const removeGroup = (groupId: string, parentGroupId?: string) => {
    if (!parentGroupId && groupId === state.value.rootGroup.id) {
      // No se puede eliminar el grupo raíz, solo limpiarlo
      state.value.rootGroup.conditions = [];
      state.value.rootGroup.groups = [];
      state.value.hasChanges = true;
      return;
    }
    
    // Función recursiva para buscar y eliminar el grupo
    const findAndRemoveGroup = (parent: FilterGroup): boolean => {
      if (parent.groups) {
        const index = parent.groups.findIndex(g => g.id === groupId);
        if (index !== -1) {
          parent.groups.splice(index, 1);
          return true;
        }
        
        // Buscar en subgrupos
        for (const subGroup of parent.groups) {
          if (findAndRemoveGroup(subGroup)) {
            return true;
          }
        }
      }
      return false;
    };
    
    // Si se especifica un parentGroupId, buscar directamente en ese grupo
    if (parentGroupId) {
      const parentGroup = findGroupById(state.value.rootGroup, parentGroupId);
      if (parentGroup && parentGroup.groups) {
        const index = parentGroup.groups.findIndex(g => g.id === groupId);
        if (index !== -1) {
          parentGroup.groups.splice(index, 1);
          state.value.hasChanges = true;
        }
      }
    } else {
      // Si no se especifica padre, buscar en toda la estructura
      findAndRemoveGroup(state.value.rootGroup);
      state.value.hasChanges = true;
    }
  };

  // Cambiar operador de un grupo
  const updateGroupOperator = (groupId: string, operator: LogicalOperator) => {
    const group = findGroupById(state.value.rootGroup, groupId);
    if (group) {
      group.operator = operator;
      state.value.hasChanges = true;
    }
  };

  // Aplicar filtros
  const applyFilters = () => {
    isLoading.value = true;
    const params = currentQueryParams.value;
    
    // Si no hay ruta definida, solo ejecutar el callback (modo AJAX)
    if (!route) {
      if (onApply) {
        onApply(params);
      }
      isLoading.value = false;
      state.value.hasChanges = false;
      return;
    }
    
    // Aplicar usando Inertia si hay ruta
    router.get(route, {
      ...routeParams,
      ...params,
    }, {
      preserveScroll,
      preserveState,
      onFinish: () => {
        isLoading.value = false;
        state.value.hasChanges = false;
      },
    });
  };

  // Aplicar filtros con debounce (para auto-apply)
  const applyFiltersDebounced = config.debounceTime 
    ? debounce(applyFilters, config.debounceTime)
    : applyFilters;

  // Limpiar todos los filtros
  const clearFilters = () => {
    state.value.quickSearch = '';
    state.value.rootGroup = createEmptyFilterGroup();
    state.value.hasChanges = false;
    applyFilters();
  };

  // Toggle expandir/colapsar
  const toggleExpanded = () => {
    state.value.isExpanded = !state.value.isExpanded;
  };

  // Guardar filtro actual como preset
  const saveAsPreset = async (name: string, description?: string) => {
    const preset: PresetFilter = {
      id: crypto.randomUUID(),
      name,
      description,
      filter: JSON.parse(JSON.stringify(state.value.rootGroup)), // Deep clone
      createdAt: new Date(),
    };
    
    // Guardar en localStorage por ahora
    const saved = localStorage.getItem(`filters_${route}`);
    const existing = saved ? JSON.parse(saved) : [];
    existing.push(preset);
    localStorage.setItem(`filters_${route}`, JSON.stringify(existing));
    
    savedFilters.value = existing;
    
    return preset;
  };

  // Cargar un preset
  const loadPreset = (presetId: string) => {
    const preset = savedFilters.value.find(p => p.id === presetId);
    if (preset) {
      state.value.rootGroup = JSON.parse(JSON.stringify(preset.filter));
      state.value.hasChanges = true;
      
      if (config.autoApply) {
        applyFiltersDebounced();
      }
    }
  };

  // Eliminar un preset
  const deletePreset = (presetId: string) => {
    savedFilters.value = savedFilters.value.filter(p => p.id !== presetId);
    localStorage.setItem(`filters_${route}`, JSON.stringify(savedFilters.value));
  };

  // Cargar presets guardados
  const loadSavedFilters = () => {
    const saved = localStorage.getItem(`filters_${route}`);
    if (saved) {
      savedFilters.value = JSON.parse(saved);
    }
  };

  // Helper: Buscar grupo por ID
  const findGroupById = (group: FilterGroup, id: string): FilterGroup | null => {
    if (group.id === id) return group;
    
    if (group.groups) {
      for (const subGroup of group.groups) {
        const found = findGroupById(subGroup, id);
        if (found) return found;
      }
    }
    
    return null;
  };

  // Obtener campo por nombre
  const getField = (fieldName: string): FilterField | undefined => {
    return config.fields.find(f => f.name === fieldName);
  };

  // Validar una condición
  const validateCondition = (condition: FilterCondition): boolean | string => {
    const field = getField(condition.field);
    if (!field) return 'Campo no válido';
    
    // Validación personalizada del campo
    if (field.validate) {
      const result = field.validate(condition.value);
      if (result !== true) return result;
    }
    
    // Validaciones básicas
    if (field.required && !condition.value) {
      return 'Este campo es requerido';
    }
    
    return true;
  };

  // Validar todo el filtro
  const validateFilters = (): boolean => {
    const validateGroup = (group: FilterGroup): boolean => {
      // Validar condiciones
      for (const condition of group.conditions) {
        if (validateCondition(condition) !== true) {
          return false;
        }
      }
      
      // Validar subgrupos
      if (group.groups) {
        for (const subGroup of group.groups) {
          if (!validateGroup(subGroup)) {
            return false;
          }
        }
      }
      
      return true;
    };
    
    return validateGroup(state.value.rootGroup);
  };

  // Watch para auto-apply
  if (config.autoApply) {
    watch(
      () => state.value.quickSearch,
      () => {
        state.value.hasChanges = true;
        applyFiltersDebounced();
      }
    );
    
    watch(
      () => state.value.rootGroup,
      () => {
        if (state.value.hasChanges) {
          applyFiltersDebounced();
        }
      },
      { deep: true }
    );
  }

  // Inicializar
  loadSavedFilters();

  return {
    // Estado
    state,
    savedFilters,
    isLoading,
    resultCount,
    
    // Computed
    hasFilters,
    activeConditionsCount,
    currentQueryParams,
    
    // Métodos de condiciones
    addCondition,
    removeCondition,
    updateCondition,
    
    // Métodos de grupos
    addGroup,
    removeGroup,
    updateGroupOperator,
    
    // Métodos principales
    applyFilters,
    clearFilters,
    toggleExpanded,
    
    // Métodos de presets
    saveAsPreset,
    loadPreset,
    deletePreset,
    
    // Helpers
    getField,
    validateCondition,
    validateFilters,
  };
}