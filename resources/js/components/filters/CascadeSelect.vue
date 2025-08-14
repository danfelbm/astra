<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Loader2 } from 'lucide-vue-next';
import type { FilterField, FilterOption } from '@/types/filters';

// Props
interface Props {
  modelValue: any;
  field: FilterField;
  parentValue?: any; // Valor del campo padre (si existe)
  disabled?: boolean;
}

const props = defineProps<Props>();

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: any];
  'options-loaded': [options: FilterOption[]];
}>();

// Estado
const options = ref<FilterOption[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);

// Cache de opciones para evitar recargas innecesarias
const optionsCache = new Map<string, FilterOption[]>();

// Computed
const isDisabled = computed(() => {
  // Deshabilitado si está explícitamente deshabilitado
  if (props.disabled) return true;
  
  // Si depende de un campo padre, deshabilitado si no hay valor en el padre
  if (props.field.cascadeFrom && !props.parentValue) return true;
  
  return false;
});

const placeholder = computed(() => {
  if (loading.value) return 'Cargando...';
  if (error.value) return 'Error al cargar opciones';
  if (isDisabled.value && props.field.cascadeFrom) {
    return `Seleccione ${props.field.cascadeFrom.split('_')[0]} primero`;
  }
  return props.field.placeholder || 'Seleccionar...';
});

// Función para cargar opciones
const loadOptions = async () => {
  // Si no hay endpoint, usar opciones estáticas si existen
  if (!props.field.cascadeEndpoint) {
    if (props.field.options) {
      options.value = props.field.options;
    }
    return;
  }
  
  // Si depende de un padre y no hay valor, limpiar opciones
  if (props.field.cascadeFrom && !props.parentValue) {
    options.value = [];
    emit('update:modelValue', null);
    return;
  }
  
  // Generar clave de cache
  const cacheKey = props.field.cascadeFrom 
    ? `${props.field.cascadeEndpoint}:${props.parentValue}`
    : props.field.cascadeEndpoint;
  
  // Verificar cache si está habilitado
  if (props.field.cacheOptions !== false && optionsCache.has(cacheKey)) {
    options.value = optionsCache.get(cacheKey)!;
    return;
  }
  
  loading.value = true;
  error.value = null;
  
  try {
    // Construir URL con parámetros si es necesario
    const url = new URL(props.field.cascadeEndpoint);
    
    // Añadir parámetro del padre si existe
    if (props.field.cascadeFrom && props.parentValue) {
      const paramName = props.field.cascadeParam || `${props.field.cascadeFrom}`;
      url.searchParams.append(paramName, props.parentValue);
    }
    
    // Hacer la petición
    const response = await fetch(url.toString(), {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin', // Incluir cookies para autenticación
    });
    
    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`);
    }
    
    const data = await response.json();
    
    // Formatear los datos según el formato esperado
    let formattedOptions: FilterOption[] = [];
    
    if (Array.isArray(data)) {
      // Si es un array directo, formatear cada elemento
      formattedOptions = data.map(item => ({
        value: item.id || item.value,
        label: item.nombre || item.label || item.name,
        disabled: item.disabled || false,
      }));
    } else if (data.data && Array.isArray(data.data)) {
      // Si los datos están en una propiedad 'data'
      formattedOptions = data.data.map((item: any) => ({
        value: item.id || item.value,
        label: item.nombre || item.label || item.name,
        disabled: item.disabled || false,
      }));
    } else {
      console.warn('Formato de respuesta no reconocido:', data);
      formattedOptions = [];
    }
    
    options.value = formattedOptions;
    
    // Guardar en cache si está habilitado
    if (props.field.cacheOptions !== false) {
      optionsCache.set(cacheKey, formattedOptions);
    }
    
    // Emitir evento con las opciones cargadas
    emit('options-loaded', formattedOptions);
    
    // Si el valor actual no está en las nuevas opciones, limpiarlo
    if (props.modelValue && !formattedOptions.find(opt => opt.value === props.modelValue)) {
      emit('update:modelValue', null);
    }
  } catch (err) {
    console.error('Error cargando opciones:', err);
    error.value = err instanceof Error ? err.message : 'Error desconocido';
    options.value = [];
  } finally {
    loading.value = false;
  }
};

// Manejar cambio de valor
const handleChange = (value: any) => {
  emit('update:modelValue', value);
};

// Cargar opciones cuando cambia el valor del padre
watch(
  () => props.parentValue,
  (newValue, oldValue) => {
    // Solo recargar si realmente cambió el valor
    if (newValue !== oldValue) {
      loadOptions();
    }
  }
);

// Cargar opciones iniciales si es necesario
onMounted(() => {
  // Cargar si no depende de un padre o si el padre ya tiene valor
  if (!props.field.cascadeFrom || props.parentValue) {
    loadOptions();
  } else if (props.field.loadOnMount) {
    // O si está configurado para cargar al montar
    loadOptions();
  }
});

// Recargar opciones si cambia el endpoint
watch(
  () => props.field.cascadeEndpoint,
  () => {
    if (props.field.cascadeEndpoint) {
      loadOptions();
    }
  }
);
</script>

<template>
  <div class="relative">
    <Select 
      :model-value="modelValue"
      @update:model-value="handleChange"
      :disabled="isDisabled || loading"
    >
      <SelectTrigger>
        <div class="flex items-center gap-2">
          <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
          <SelectValue :placeholder="placeholder" />
        </div>
      </SelectTrigger>
      <SelectContent>
        <SelectItem 
          v-for="option in options" 
          :key="option.value"
          :value="option.value"
          :disabled="option.disabled"
        >
          {{ option.label }}
        </SelectItem>
        <div v-if="options.length === 0 && !loading" class="px-2 py-1.5 text-sm text-muted-foreground">
          {{ error ? 'Error al cargar opciones' : 'Sin opciones disponibles' }}
        </div>
      </SelectContent>
    </Select>
    
    <!-- Mensaje de error si existe -->
    <p v-if="error && !loading" class="mt-1 text-sm text-destructive">
      {{ error }}
    </p>
  </div>
</template>