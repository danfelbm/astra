<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent } from '@modules/Core/Resources/js/components/ui/card';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@modules/Core/Resources/js/components/ui/select';
import { Search, X, Filter } from 'lucide-vue-next';

// Interfaces
interface Contrato {
  id: number;
  nombre: string;
}

interface Filters {
  search: string;
  contrato_id: number | string | null;
}

interface Props {
  /** Filtros actuales */
  modelValue: Filters;
  /** Lista de contratos para el selector */
  contratos?: Contrato[];
  /** Si está cargando */
  loading?: boolean;
  /** Mostrar filtro de contrato */
  showContratoFilter?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  contratos: () => [],
  loading: false,
  showContratoFilter: true
});

const emit = defineEmits<{
  'update:modelValue': [value: Filters];
  'filter': [];
  'clear': [];
}>();

// Computed para v-model bidireccional
const filters = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
});

// Verificar si hay filtros activos
const hasActiveFilters = computed(() => {
  return filters.value.search ||
         (props.showContratoFilter && filters.value.contrato_id);
});

// Métodos
const updateSearch = (value: string) => {
  filters.value = { ...filters.value, search: value };
  emit('filter');
};

const updateContrato = (value: string | number | null) => {
  // Convertir 'all' a null
  const contratoId = value === 'all' ? null : value;
  filters.value = { ...filters.value, contrato_id: contratoId };
  emit('filter');
};

const clearFilters = () => {
  filters.value = {
    search: '',
    contrato_id: null
  };
  emit('clear');
};
</script>

<template>
  <Card>
    <CardContent class="pt-6">
      <div class="flex flex-col sm:flex-row gap-4">
        <!-- Búsqueda -->
        <div class="flex-1">
          <Label for="search" class="sr-only">Buscar</Label>
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
            <Input
              id="search"
              :model-value="filters.search"
              type="text"
              placeholder="Buscar por título o descripción..."
              class="pl-10"
              :disabled="loading"
              @update:model-value="updateSearch"
            />
          </div>
        </div>

        <!-- Filtro por contrato -->
        <div v-if="showContratoFilter && contratos.length > 0" class="w-full sm:w-64">
          <Label for="contrato_id" class="sr-only">Contrato</Label>
          <Select
            :model-value="filters.contrato_id?.toString() || 'all'"
            :disabled="loading"
            @update:model-value="updateContrato"
          >
            <SelectTrigger id="contrato_id">
              <Filter class="h-4 w-4 mr-2 text-gray-400" />
              <SelectValue placeholder="Todos los contratos" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Todos los contratos</SelectItem>
              <SelectItem
                v-for="contrato in contratos"
                :key="contrato.id"
                :value="contrato.id.toString()"
              >
                {{ contrato.nombre }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <!-- Botón limpiar filtros -->
        <Button
          v-if="hasActiveFilters"
          variant="ghost"
          size="icon"
          :disabled="loading"
          @click="clearFilters"
          title="Limpiar filtros"
        >
          <X class="h-4 w-4" />
        </Button>
      </div>
    </CardContent>
  </Card>
</template>
