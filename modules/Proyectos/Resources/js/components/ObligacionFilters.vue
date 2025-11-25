<script setup lang="ts">
import { ref, computed } from 'vue';
import { Card, CardContent } from '@modules/Core/Resources/js/components/ui/card';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Search, X, FileText } from 'lucide-vue-next';
import AddContratosModal from '@modules/Proyectos/Resources/js/components/modals/AddContratosModal.vue';
import type { Proyecto } from '@modules/Proyectos/Resources/js/types/contratos';

// Interfaces
interface ContratoSeleccionado {
  id: number;
  nombre: string;
  proyecto?: {
    id: number;
    nombre: string;
  };
}

interface Filters {
  search: string;
  contrato_id: number | string | null;
}

interface Props {
  /** Filtros actuales */
  modelValue: Filters;
  /** Lista de proyectos para el modal de contratos */
  proyectos?: Proyecto[];
  /** Si está cargando */
  loading?: boolean;
  /** Mostrar filtro de contrato */
  showContratoFilter?: boolean;
  /** Contrato seleccionado actualmente (para mostrar nombre) */
  contratoSeleccionado?: ContratoSeleccionado | null;
}

const props = withDefaults(defineProps<Props>(), {
  proyectos: () => [],
  loading: false,
  showContratoFilter: true,
  contratoSeleccionado: null
});

const emit = defineEmits<{
  'update:modelValue': [value: Filters];
  'update:contratoSeleccionado': [value: ContratoSeleccionado | null];
  'filter': [];
  'clear': [];
}>();

// Estado del modal de contratos
const showContratoModal = ref(false);
const contratoLocal = ref<ContratoSeleccionado | null>(props.contratoSeleccionado);

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

// Handler para cuando se selecciona un contrato desde el modal
const handleContratoSelect = (data: { contratoIds: number[]; contratos?: any[] }) => {
  if (data.contratoIds.length > 0) {
    filters.value = { ...filters.value, contrato_id: data.contratoIds[0] };
    if (data.contratos && data.contratos.length > 0) {
      contratoLocal.value = data.contratos[0];
      emit('update:contratoSeleccionado', data.contratos[0]);
    }
    emit('filter');
  }
};

// Limpiar contrato seleccionado
const limpiarContrato = () => {
  filters.value = { ...filters.value, contrato_id: null };
  contratoLocal.value = null;
  emit('update:contratoSeleccionado', null);
  emit('filter');
};

const clearFilters = () => {
  filters.value = {
    search: '',
    contrato_id: null
  };
  contratoLocal.value = null;
  emit('update:contratoSeleccionado', null);
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

        <!-- Filtro por contrato (usando modal) -->
        <div v-if="showContratoFilter" class="w-full sm:w-auto">
          <Label for="contrato_filter" class="sr-only">Contrato</Label>

          <!-- Mostrar contrato seleccionado (con nombre conocido) -->
          <div v-if="contratoLocal" class="flex items-center gap-2 px-3 py-2 bg-muted rounded-lg">
            <FileText class="h-4 w-4 text-muted-foreground flex-shrink-0" />
            <div class="flex-1 min-w-0">
              <p class="font-medium text-sm truncate">{{ contratoLocal.nombre }}</p>
              <p v-if="contratoLocal.proyecto" class="text-xs text-muted-foreground truncate">
                {{ contratoLocal.proyecto.nombre }}
              </p>
            </div>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              class="h-6 w-6 p-0 flex-shrink-0"
              @click="limpiarContrato"
            >
              <X class="h-4 w-4" />
            </Button>
          </div>

          <!-- Filtro activo desde URL (sin nombre conocido) -->
          <div v-else-if="filters.contrato_id" class="flex items-center gap-2 px-3 py-2 bg-muted rounded-lg">
            <FileText class="h-4 w-4 text-muted-foreground flex-shrink-0" />
            <div class="flex-1 min-w-0">
              <p class="font-medium text-sm">Contrato #{{ filters.contrato_id }}</p>
              <p class="text-xs text-muted-foreground">Filtro activo</p>
            </div>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              class="h-6 w-6 p-0 flex-shrink-0"
              @click="limpiarContrato"
            >
              <X class="h-4 w-4" />
            </Button>
          </div>

          <!-- Botón para abrir modal -->
          <Button
            v-else
            type="button"
            variant="outline"
            @click="showContratoModal = true"
            :disabled="loading"
            class="w-full sm:w-auto"
          >
            <FileText class="h-4 w-4 mr-2" />
            Filtrar por Contrato
          </Button>

          <!-- Modal de selección de contratos -->
          <AddContratosModal
            v-model="showContratoModal"
            title="Filtrar por Contrato"
            description="Selecciona el contrato para filtrar las obligaciones"
            search-endpoint="/admin/contratos/search"
            :proyectos="proyectos"
            :excluded-ids="[]"
            :max-selection="1"
            submit-button-text="Aplicar Filtro"
            @submit="handleContratoSelect"
          />
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
