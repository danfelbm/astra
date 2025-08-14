// Tipos de operadores disponibles para los filtros
export type FilterOperator = 
  | 'equals'           // Es igual a
  | 'not_equals'       // No es igual a
  | 'contains'         // Contiene
  | 'not_contains'     // No contiene
  | 'starts_with'      // Empieza con
  | 'ends_with'        // Termina con
  | 'is_empty'         // Está vacío
  | 'is_not_empty'     // No está vacío
  | 'greater_than'     // Mayor que
  | 'less_than'        // Menor que
  | 'greater_or_equal' // Mayor o igual que
  | 'less_or_equal'    // Menor o igual que
  | 'between'          // Entre
  | 'in'               // En lista
  | 'not_in';          // No en lista

// Tipos de campos soportados
export type FieldType = 
  | 'text' 
  | 'number' 
  | 'date' 
  | 'datetime' 
  | 'select' 
  | 'select-cascade'  // Nuevo tipo para selectores en cascada
  | 'multiselect' 
  | 'boolean';

// Operadores lógicos para combinar condiciones
export type LogicalOperator = 'AND' | 'OR';

// Definición de un campo filtrable
export interface FilterField {
  // Nombre del campo en la base de datos
  name: string;
  
  // Etiqueta a mostrar en la UI
  label: string;
  
  // Tipo de campo
  type: FieldType;
  
  // Operadores permitidos para este campo
  operators?: FilterOperator[];
  
  // Opciones para campos select/multiselect
  options?: FilterOption[];
  
  // Función para cargar opciones dinámicamente
  loadOptions?: () => Promise<FilterOption[]>;
  
  // Valor por defecto
  defaultValue?: any;
  
  // Placeholder para inputs
  placeholder?: string;
  
  // Si el campo es requerido
  required?: boolean;
  
  // Validación personalizada
  validate?: (value: any) => boolean | string;
  
  // --- Propiedades para campos en cascada (select-cascade) ---
  
  // Campo del que depende este campo (para cascadas)
  cascadeFrom?: string;
  
  // Campos hijos que dependen de este campo
  cascadeChildren?: string[];
  
  // Endpoint para cargar opciones en cascada
  cascadeEndpoint?: string;
  
  // Nombre del parámetro a enviar al endpoint
  cascadeParam?: string;
  
  // Si las opciones deben cargarse inmediatamente al montar
  loadOnMount?: boolean;
  
  // Cache de opciones para evitar recargas innecesarias
  cacheOptions?: boolean;
}

// Opción para campos select
export interface FilterOption {
  value: string | number;
  label: string;
  disabled?: boolean;
}

// Una condición individual de filtro
export interface FilterCondition {
  id: string;
  field: string;
  operator: FilterOperator;
  value: any;
}

// Un grupo de condiciones con operador lógico
export interface FilterGroup {
  id: string;
  operator: LogicalOperator;
  conditions: FilterCondition[];
  groups?: FilterGroup[]; // Subgrupos anidados
}

// Estado completo del filtro avanzado
export interface AdvancedFilterState {
  // Búsqueda rápida
  quickSearch?: string;
  
  // Grupo raíz de condiciones
  rootGroup: FilterGroup;
  
  // Si los filtros están expandidos
  isExpanded: boolean;
  
  // Si hay cambios sin aplicar
  hasChanges: boolean;
}

// Configuración del componente de filtros avanzados
export interface AdvancedFilterConfig {
  // Campos disponibles para filtrar
  fields: FilterField[];
  
  // Si mostrar búsqueda rápida
  showQuickSearch?: boolean;
  
  // Placeholder para búsqueda rápida
  quickSearchPlaceholder?: string;
  
  // Campos incluidos en búsqueda rápida
  quickSearchFields?: string[];
  
  // Máximo nivel de anidación de grupos
  maxNestingLevel?: number;
  
  // Si permitir guardar filtros
  allowSaveFilters?: boolean;
  
  // Si mostrar contador de resultados
  showResultCount?: boolean;
  
  // Debounce en ms para aplicar filtros automáticamente
  debounceTime?: number;
  
  // Si aplicar filtros automáticamente al cambiar
  autoApply?: boolean;
  
  // Filtros predefinidos
  presetFilters?: PresetFilter[];
}

// Filtro predefinido/guardado
export interface PresetFilter {
  id: string;
  name: string;
  description?: string;
  filter: FilterGroup;
  isDefault?: boolean;
  createdBy?: string;
  createdAt?: Date;
}

// Mapeo de operadores a etiquetas en español
export const operatorLabels: Record<FilterOperator, string> = {
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

// Operadores disponibles por tipo de campo
export const operatorsByFieldType: Record<FieldType, FilterOperator[]> = {
  text: [
    'equals',
    'not_equals',
    'contains',
    'not_contains',
    'starts_with',
    'ends_with',
    'is_empty',
    'is_not_empty',
  ],
  number: [
    'equals',
    'not_equals',
    'greater_than',
    'less_than',
    'greater_or_equal',
    'less_or_equal',
    'between',
    'is_empty',
    'is_not_empty',
  ],
  date: [
    'equals',
    'not_equals',
    'greater_than',
    'less_than',
    'greater_or_equal',
    'less_or_equal',
    'between',
    'is_empty',
    'is_not_empty',
  ],
  datetime: [
    'equals',
    'not_equals',
    'greater_than',
    'less_than',
    'greater_or_equal',
    'less_or_equal',
    'between',
    'is_empty',
    'is_not_empty',
  ],
  select: [
    'equals',
    'not_equals',
    'is_empty',
    'is_not_empty',
  ],
  'select-cascade': [
    'equals',
    'not_equals',
    'is_empty',
    'is_not_empty',
  ],
  multiselect: [
    'in',
    'not_in',
    'is_empty',
    'is_not_empty',
  ],
  boolean: [
    'equals',
  ],
};

// Las funciones helper están disponibles en @/utils/filters
// Este archivo solo contiene las definiciones de tipos