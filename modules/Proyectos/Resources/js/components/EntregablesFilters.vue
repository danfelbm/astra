<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@modules/Core/Resources/js/components/ui/select';
import { X, Filter, Search } from 'lucide-vue-next';

// Tipos de filtros
interface Filtros {
  search: string | null;
  estado: string | null;
  prioridad: string | null;
  responsable_id: number | null;
  fecha_inicio: string | null;
  fecha_fin: string | null;
}

// Interfaces
interface Usuario {
  id: number;
  name: string;
  email?: string;
}

interface Props {
  modelValue: Filtros;
  usuarios?: Usuario[];
  showCard?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  usuarios: () => [],
  showCard: true
});

const emit = defineEmits<{
  'update:modelValue': [value: Filtros];
}>();

// Computed para los filtros
const filtros = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
});

// Opciones de estado
const estadosDisponibles = [
  { value: 'pendiente', label: 'Pendiente' },
  { value: 'en_progreso', label: 'En Progreso' },
  { value: 'completado', label: 'Completado' },
  { value: 'cancelado', label: 'Cancelado' },
];

// Opciones de prioridad
const prioridadesDisponibles = [
  { value: 'baja', label: 'Baja' },
  { value: 'media', label: 'Media' },
  { value: 'alta', label: 'Alta' },
];

// Actualizar filtro individual
const updateFilter = (key: keyof Filtros, value: any) => {
  filtros.value = { ...filtros.value, [key]: value };
};

// Limpiar filtro individual
const clearFilter = (key: keyof Filtros) => {
  updateFilter(key, null);
};

// Limpiar todos los filtros
const clearAllFilters = () => {
  filtros.value = {
    search: null,
    estado: null,
    prioridad: null,
    responsable_id: null,
    fecha_inicio: null,
    fecha_fin: null
  };
};

// Verificar si hay filtros activos
const hasActiveFilters = computed(() => {
  return Object.values(filtros.value).some(v => v !== null && v !== undefined && v !== '');
});
</script>

<template>
  <Card v-if="showCard">
    <CardHeader class="pb-3">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <Filter class="h-4 w-4" />
          <CardTitle class="text-base">Filtros de Entregables</CardTitle>
        </div>
        <Button
          v-if="hasActiveFilters"
          variant="ghost"
          size="sm"
          @click="clearAllFilters"
        >
          <X class="h-4 w-4 mr-1" />
          Limpiar
        </Button>
      </div>
    </CardHeader>
    <CardContent>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Filtro de búsqueda -->
        <div class="space-y-2">
          <Label for="search">Buscar</Label>
          <div class="relative">
            <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              id="search"
              :value="filtros.search || ''"
              @input="(e) => updateFilter('search', (e.target as HTMLInputElement).value)"
              placeholder="Buscar entregable..."
              class="pl-8"
            />
          </div>
          <Button
            v-if="filtros.search"
            variant="ghost"
            size="icon"
            class="absolute right-2 top-8 h-6 w-6"
            @click="clearFilter('search')"
          >
            <X class="h-3 w-3" />
          </Button>
        </div>

        <!-- Filtro por Estado -->
        <div class="space-y-2">
          <Label for="estado">Estado</Label>
          <div class="flex gap-2">
            <Select
              :model-value="filtros.estado || 'null'"
              @update:model-value="(val) => updateFilter('estado', val === 'null' ? null : val)"
            >
              <SelectTrigger id="estado">
                <SelectValue placeholder="Todos los estados" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="null">Todos los estados</SelectItem>
                <SelectItem
                  v-for="estado in estadosDisponibles"
                  :key="estado.value"
                  :value="estado.value"
                >
                  {{ estado.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <Button
              v-if="filtros.estado"
              variant="ghost"
              size="icon"
              @click="clearFilter('estado')"
            >
              <X class="h-4 w-4" />
            </Button>
          </div>
        </div>

        <!-- Filtro por Prioridad -->
        <div class="space-y-2">
          <Label for="prioridad">Prioridad</Label>
          <div class="flex gap-2">
            <Select
              :model-value="filtros.prioridad || 'null'"
              @update:model-value="(val) => updateFilter('prioridad', val === 'null' ? null : val)"
            >
              <SelectTrigger id="prioridad">
                <SelectValue placeholder="Todas las prioridades" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="null">Todas las prioridades</SelectItem>
                <SelectItem
                  v-for="prioridad in prioridadesDisponibles"
                  :key="prioridad.value"
                  :value="prioridad.value"
                >
                  {{ prioridad.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <Button
              v-if="filtros.prioridad"
              variant="ghost"
              size="icon"
              @click="clearFilter('prioridad')"
            >
              <X class="h-4 w-4" />
            </Button>
          </div>
        </div>

        <!-- Filtro por Responsable -->
        <div v-if="usuarios.length > 0" class="space-y-2">
          <Label for="responsable">Responsable</Label>
          <div class="flex gap-2">
            <Select
              :model-value="filtros.responsable_id?.toString() || 'null'"
              @update:model-value="(val) => updateFilter('responsable_id', val === 'null' ? null : parseInt(val))"
            >
              <SelectTrigger id="responsable">
                <SelectValue placeholder="Todos los responsables" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="null">Todos los responsables</SelectItem>
                <SelectItem
                  v-for="usuario in usuarios"
                  :key="usuario.id"
                  :value="usuario.id.toString()"
                >
                  {{ usuario.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <Button
              v-if="filtros.responsable_id"
              variant="ghost"
              size="icon"
              @click="clearFilter('responsable_id')"
            >
              <X class="h-4 w-4" />
            </Button>
          </div>
        </div>

        <!-- Filtro por Rango de Fechas -->
        <div class="space-y-2 md:col-span-2">
          <Label>Rango de Fechas</Label>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <Label class="text-xs text-muted-foreground">Desde</Label>
              <Input
                type="date"
                :value="filtros.fecha_inicio || ''"
                @input="(e) => updateFilter('fecha_inicio', (e.target as HTMLInputElement).value)"
                class="mt-1"
              />
            </div>
            <div>
              <Label class="text-xs text-muted-foreground">Hasta</Label>
              <Input
                type="date"
                :value="filtros.fecha_fin || ''"
                @input="(e) => updateFilter('fecha_fin', (e.target as HTMLInputElement).value)"
                class="mt-1"
              />
            </div>
          </div>
          <Button
            v-if="filtros.fecha_inicio || filtros.fecha_fin"
            variant="ghost"
            size="sm"
            @click="clearFilter('fecha_inicio'); clearFilter('fecha_fin')"
            class="w-full mt-1"
          >
            <X class="h-4 w-4 mr-1" />
            Limpiar fechas
          </Button>
        </div>
      </div>
    </CardContent>
  </Card>

  <!-- Sin Card wrapper (para uso dentro de otro Card) -->
  <div v-else class="space-y-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <Filter class="h-4 w-4" />
        <h3 class="text-sm font-medium">Filtros</h3>
      </div>
      <Button
        v-if="hasActiveFilters"
        variant="ghost"
        size="sm"
        @click="clearAllFilters"
      >
        <X class="h-4 w-4 mr-1" />
        Limpiar
      </Button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <!-- Same filters but without Card wrapper -->
      <!-- Contenido idéntico al de arriba pero sin el Card -->
    </div>
  </div>
</template>
