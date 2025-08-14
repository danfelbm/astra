import { ref, computed, watch, Ref } from 'vue';
import type { FilterField, FilterOption } from '@/types/filters';

export interface GeographicValues {
  territorio_id?: number | null;
  departamento_id?: number | null;
  municipio_id?: number | null;
  localidad_id?: number | null;
}

export interface UseGeographicFiltersOptions {
  // Prefijo para los campos (opcional)
  prefix?: string;
  
  // Valores iniciales
  initialValues?: GeographicValues;
  
  // Callback cuando cambian los valores
  onChange?: (values: GeographicValues) => void;
  
  // Si incluir cada nivel
  includeTerritorio?: boolean;
  includeDepartamento?: boolean;
  includeMunicipio?: boolean;
  includeLocalidad?: boolean;
  
  // URLs base para los endpoints (opcional, usa las por defecto si no se especifican)
  endpoints?: {
    territorios?: string;
    departamentos?: string;
    municipios?: string;
    localidades?: string;
  };
}

export function useGeographicFilters(options: UseGeographicFiltersOptions = {}) {
  const {
    prefix = '',
    initialValues = {},
    onChange,
    includeTerritorio = true,
    includeDepartamento = true,
    includeMunicipio = true,
    includeLocalidad = true,
    endpoints = {},
  } = options;
  
  // Estado de los valores seleccionados
  const values = ref<GeographicValues>({
    territorio_id: initialValues.territorio_id || null,
    departamento_id: initialValues.departamento_id || null,
    municipio_id: initialValues.municipio_id || null,
    localidad_id: initialValues.localidad_id || null,
  });
  
  // Estado de las opciones cargadas
  const territorios = ref<FilterOption[]>([]);
  const departamentos = ref<FilterOption[]>([]);
  const municipios = ref<FilterOption[]>([]);
  const localidades = ref<FilterOption[]>([]);
  
  // Estados de carga
  const loadingTerritorios = ref(false);
  const loadingDepartamentos = ref(false);
  const loadingMunicipios = ref(false);
  const loadingLocalidades = ref(false);
  
  // Errores
  const errors = ref<Record<string, string>>({});
  
  // Helper para obtener route
  const { route } = window as any;
  
  // Endpoints con valores por defecto
  const endpointUrls = {
    territorios: endpoints.territorios || route('admin.geographic.territorios'),
    departamentos: endpoints.departamentos || route('admin.geographic.departamentos'),
    municipios: endpoints.municipios || route('admin.geographic.municipios'),
    localidades: endpoints.localidades || route('admin.geographic.localidades'),
  };
  
  // Función genérica para cargar opciones
  const loadOptions = async (
    endpoint: string,
    params: Record<string, any> = {},
    targetRef: Ref<FilterOption[]>,
    loadingRef: Ref<boolean>,
    errorKey: string
  ) => {
    loadingRef.value = true;
    errors.value[errorKey] = '';
    
    try {
      const url = new URL(endpoint);
      
      // Añadir parámetros a la URL
      Object.entries(params).forEach(([key, value]) => {
        if (value !== null && value !== undefined) {
          url.searchParams.append(key, String(value));
        }
      });
      
      const response = await fetch(url.toString(), {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });
      
      if (!response.ok) {
        throw new Error(`Error ${response.status}: ${response.statusText}`);
      }
      
      const data = await response.json();
      
      // Formatear los datos
      const formattedOptions = (Array.isArray(data) ? data : data.data || []).map((item: any) => ({
        value: item.id,
        label: item.nombre || item.name,
        disabled: item.disabled || false,
      }));
      
      targetRef.value = formattedOptions;
      
      return formattedOptions;
    } catch (error) {
      console.error(`Error cargando ${errorKey}:`, error);
      errors.value[errorKey] = error instanceof Error ? error.message : 'Error desconocido';
      targetRef.value = [];
      return [];
    } finally {
      loadingRef.value = false;
    }
  };
  
  // Cargar territorios
  const loadTerritorios = async () => {
    if (!includeTerritorio) return;
    
    await loadOptions(
      endpointUrls.territorios,
      {},
      territorios,
      loadingTerritorios,
      'territorios'
    );
  };
  
  // Cargar departamentos
  const loadDepartamentos = async (territorioId?: number | null) => {
    if (!includeDepartamento) return;
    
    // Si no hay territorio seleccionado y se requiere, limpiar
    if (includeTerritorio && !territorioId) {
      departamentos.value = [];
      values.value.departamento_id = null;
      return;
    }
    
    const params = includeTerritorio && territorioId 
      ? { territorio_ids: territorioId }
      : {};
    
    await loadOptions(
      endpointUrls.departamentos,
      params,
      departamentos,
      loadingDepartamentos,
      'departamentos'
    );
  };
  
  // Cargar municipios
  const loadMunicipios = async (departamentoId?: number | null) => {
    if (!includeMunicipio) return;
    
    // Si no hay departamento seleccionado y se requiere, limpiar
    if (includeDepartamento && !departamentoId) {
      municipios.value = [];
      values.value.municipio_id = null;
      return;
    }
    
    const params = includeDepartamento && departamentoId
      ? { departamento_ids: departamentoId }
      : {};
    
    await loadOptions(
      endpointUrls.municipios,
      params,
      municipios,
      loadingMunicipios,
      'municipios'
    );
  };
  
  // Cargar localidades
  const loadLocalidades = async (municipioId?: number | null) => {
    if (!includeLocalidad) return;
    
    // Si no hay municipio seleccionado y se requiere, limpiar
    if (includeMunicipio && !municipioId) {
      localidades.value = [];
      values.value.localidad_id = null;
      return;
    }
    
    const params = includeMunicipio && municipioId
      ? { municipio_ids: municipioId }
      : {};
    
    await loadOptions(
      endpointUrls.localidades,
      params,
      localidades,
      loadingLocalidades,
      'localidades'
    );
  };
  
  // Watchers para las cascadas
  watch(
    () => values.value.territorio_id,
    (newValue) => {
      // Limpiar valores dependientes
      if (includeDepartamento) {
        values.value.departamento_id = null;
        values.value.municipio_id = null;
        values.value.localidad_id = null;
      }
      
      // Cargar departamentos del territorio seleccionado
      loadDepartamentos(newValue);
    }
  );
  
  watch(
    () => values.value.departamento_id,
    (newValue) => {
      // Limpiar valores dependientes
      if (includeMunicipio) {
        values.value.municipio_id = null;
        values.value.localidad_id = null;
      }
      
      // Cargar municipios del departamento seleccionado
      loadMunicipios(newValue);
    }
  );
  
  watch(
    () => values.value.municipio_id,
    (newValue) => {
      // Limpiar valores dependientes
      if (includeLocalidad) {
        values.value.localidad_id = null;
      }
      
      // Cargar localidades del municipio seleccionado
      loadLocalidades(newValue);
    }
  );
  
  // Watch general para notificar cambios
  watch(
    values,
    (newValues) => {
      if (onChange) {
        onChange(newValues);
      }
    },
    { deep: true }
  );
  
  // Generar configuración de campos para filtros
  const generateFilterFields = (): FilterField[] => {
    const fields: FilterField[] = [];
    
    if (includeTerritorio) {
      fields.push({
        name: `${prefix}territorio_id`,
        label: 'Territorio',
        type: 'select-cascade',
        placeholder: 'Seleccionar territorio...',
        cascadeEndpoint: endpointUrls.territorios,
        cascadeChildren: includeDepartamento ? [`${prefix}departamento_id`] : [],
        options: territorios.value,
        loadOnMount: true,
      });
    }
    
    if (includeDepartamento) {
      fields.push({
        name: `${prefix}departamento_id`,
        label: 'Departamento',
        type: 'select-cascade',
        placeholder: 'Seleccionar departamento...',
        cascadeFrom: includeTerritorio ? `${prefix}territorio_id` : undefined,
        cascadeEndpoint: endpointUrls.departamentos,
        cascadeParam: 'territorio_ids',
        cascadeChildren: includeMunicipio ? [`${prefix}municipio_id`] : [],
        options: departamentos.value,
        loadOnMount: !includeTerritorio,
      });
    }
    
    if (includeMunicipio) {
      fields.push({
        name: `${prefix}municipio_id`,
        label: 'Municipio',
        type: 'select-cascade',
        placeholder: 'Seleccionar municipio...',
        cascadeFrom: includeDepartamento ? `${prefix}departamento_id` : undefined,
        cascadeEndpoint: endpointUrls.municipios,
        cascadeParam: 'departamento_ids',
        cascadeChildren: includeLocalidad ? [`${prefix}localidad_id`] : [],
        options: municipios.value,
        loadOnMount: !includeDepartamento,
      });
    }
    
    if (includeLocalidad) {
      fields.push({
        name: `${prefix}localidad_id`,
        label: 'Localidad',
        type: 'select-cascade',
        placeholder: 'Seleccionar localidad...',
        cascadeFrom: includeMunicipio ? `${prefix}municipio_id` : undefined,
        cascadeEndpoint: endpointUrls.localidades,
        cascadeParam: 'municipio_ids',
        cascadeChildren: [],
        options: localidades.value,
        loadOnMount: !includeMunicipio,
      });
    }
    
    return fields;
  };
  
  // Limpiar todos los valores
  const clearValues = () => {
    values.value = {
      territorio_id: null,
      departamento_id: null,
      municipio_id: null,
      localidad_id: null,
    };
  };
  
  // Establecer valores programáticamente
  const setValues = (newValues: Partial<GeographicValues>) => {
    Object.assign(values.value, newValues);
  };
  
  // Computed para saber si hay valores seleccionados
  const hasValues = computed(() => {
    return Object.values(values.value).some(v => v !== null && v !== undefined);
  });
  
  // Computed para obtener el nivel más específico seleccionado
  const selectedLevel = computed(() => {
    if (values.value.localidad_id) return 'localidad';
    if (values.value.municipio_id) return 'municipio';
    if (values.value.departamento_id) return 'departamento';
    if (values.value.territorio_id) return 'territorio';
    return null;
  });
  
  // Inicializar cargando los primeros niveles
  const initialize = async () => {
    // Cargar territorios si están incluidos
    if (includeTerritorio) {
      await loadTerritorios();
    }
    
    // Si no hay territorio pero hay departamento, cargar departamentos
    if (!includeTerritorio && includeDepartamento) {
      await loadDepartamentos();
    }
    
    // Si hay valores iniciales, cargar las opciones necesarias
    if (initialValues.territorio_id && includeTerritorio) {
      await loadDepartamentos(initialValues.territorio_id);
    }
    
    if (initialValues.departamento_id && includeDepartamento) {
      await loadMunicipios(initialValues.departamento_id);
    }
    
    if (initialValues.municipio_id && includeMunicipio) {
      await loadLocalidades(initialValues.municipio_id);
    }
  };
  
  return {
    // Estado
    values,
    territorios,
    departamentos,
    municipios,
    localidades,
    
    // Estados de carga
    loadingTerritorios,
    loadingDepartamentos,
    loadingMunicipios,
    loadingLocalidades,
    
    // Errores
    errors,
    
    // Funciones
    loadTerritorios,
    loadDepartamentos,
    loadMunicipios,
    loadLocalidades,
    generateFilterFields,
    clearValues,
    setValues,
    initialize,
    
    // Computed
    hasValues,
    selectedLevel,
  };
}