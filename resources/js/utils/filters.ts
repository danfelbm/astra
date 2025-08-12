import type {
  AdvancedFilterState,
  FilterGroup,
  FilterCondition,
  LogicalOperator,
} from '@/types/filters';

// Helper para crear un grupo vacío
export function createEmptyFilterGroup(operator: LogicalOperator = 'AND'): FilterGroup {
  return {
    id: crypto.randomUUID(),
    operator,
    conditions: [],
    groups: [],
  };
}

// Helper para crear una condición vacía
export function createEmptyCondition(field?: string): FilterCondition {
  return {
    id: crypto.randomUUID(),
    field: field || null, // Usar null en lugar de cadena vacía
    operator: 'equals',
    value: null,
  };
}

// Helper para convertir el estado del filtro a query params
export function filterStateToQueryParams(state: AdvancedFilterState): Record<string, any> {
  const params: Record<string, any> = {};
  
  if (state.quickSearch) {
    params.search = state.quickSearch;
  }
  
  // Convertir el grupo raíz a un formato serializable
  if (state.rootGroup.conditions.length > 0 || (state.rootGroup.groups && state.rootGroup.groups.length > 0)) {
    params.advanced_filters = JSON.stringify(serializeFilterGroup(state.rootGroup));
  }
  
  return params;
}

// Serializar grupo de filtros para enviar al backend
function serializeFilterGroup(group: FilterGroup): any {
  return {
    operator: group.operator,
    conditions: group.conditions.map(c => ({
      field: c.field,
      operator: c.operator,
      value: c.value,
    })),
    groups: group.groups?.map(g => serializeFilterGroup(g)) || [],
  };
}

// Helper para verificar si hay filtros activos
export function hasActiveFilters(state: AdvancedFilterState): boolean {
  return Boolean(
    state.quickSearch ||
    state.rootGroup.conditions.length > 0 ||
    (state.rootGroup.groups && state.rootGroup.groups.length > 0)
  );
}

// Helper para contar condiciones activas
export function countActiveConditions(group: FilterGroup): number {
  let count = group.conditions.length;
  
  if (group.groups) {
    for (const subGroup of group.groups) {
      count += countActiveConditions(subGroup);
    }
  }
  
  return count;
}