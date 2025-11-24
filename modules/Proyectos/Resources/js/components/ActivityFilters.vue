<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@modules/Core/Resources/js/components/ui/select';
import { Calendar } from '@modules/Core/Resources/js/components/ui/calendar';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@modules/Core/Resources/js/components/ui/popover';
import { Calendar as CalendarIcon, X, Filter } from 'lucide-vue-next';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';

interface Usuario {
  id: number;
  name: string;
  email: string;
}

interface Filtros {
  usuario_id: number | null;
  tipo_entidad: string | null;
  tipo_accion: string | null;
  fecha_inicio: string | null;
  fecha_fin: string | null;
}

interface Props {
  modelValue: Filtros;
  usuarios?: Usuario[];
  contextLevel?: 'proyecto' | 'hito' | 'entregable';
  showCard?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  usuarios: () => [],
  contextLevel: 'proyecto',
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

// Tipos de entidad disponibles según el contexto
const tiposEntidad = computed(() => {
  const tipos = [];

  if (props.contextLevel === 'proyecto') {
    tipos.push(
      { value: 'Modules\\Proyectos\\Models\\Proyecto', label: 'Proyecto' },
      { value: 'Modules\\Proyectos\\Models\\Hito', label: 'Hito' },
      { value: 'Modules\\Proyectos\\Models\\Entregable', label: 'Entregable' }
    );
  } else if (props.contextLevel === 'hito') {
    tipos.push(
      { value: 'Modules\\Proyectos\\Models\\Hito', label: 'Hito' },
      { value: 'Modules\\Proyectos\\Models\\Entregable', label: 'Entregable' }
    );
  } else {
    tipos.push(
      { value: 'Modules\\Proyectos\\Models\\Entregable', label: 'Entregable' }
    );
  }

  return tipos;
});

// Tipos de acción disponibles
const tiposAccion = [
  { value: 'created', label: 'Creado' },
  { value: 'updated', label: 'Actualizado' },
  { value: 'deleted', label: 'Eliminado' },
  { value: 'restored', label: 'Restaurado' },
  { value: 'custom', label: 'Acción personalizada' }
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
    usuario_id: null,
    tipo_entidad: null,
    tipo_accion: null,
    fecha_inicio: null,
    fecha_fin: null
  };
};

// Verificar si hay filtros activos
const hasActiveFilters = computed(() => {
  return Object.values(filtros.value).some(v => v !== null && v !== undefined);
});

// Formatear fecha para mostrar
const formatDateDisplay = (dateString: string | null) => {
  if (!dateString) return '';
  try {
    return format(new Date(dateString), "d 'de' MMM yyyy", { locale: es });
  } catch {
    return dateString;
  }
};
</script>

<template>
  <Card v-if="showCard">
    <CardHeader class="pb-3">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <Filter class="h-4 w-4" />
          <CardTitle class="text-base">Filtros de Actividad</CardTitle>
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
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Filtro por Usuario -->
        <div v-if="usuarios.length > 0" class="space-y-2">
          <Label for="usuario">Usuario</Label>
          <div class="flex gap-2">
            <Select
              :model-value="filtros.usuario_id?.toString()"
              @update:model-value="(val) => updateFilter('usuario_id', val ? parseInt(val) : null)"
            >
              <SelectTrigger id="usuario">
                <SelectValue placeholder="Todos los usuarios" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="null">Todos los usuarios</SelectItem>
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
              v-if="filtros.usuario_id"
              variant="ghost"
              size="icon"
              @click="clearFilter('usuario_id')"
            >
              <X class="h-4 w-4" />
            </Button>
          </div>
        </div>

        <!-- Filtro por Tipo de Entidad -->
        <div v-if="tiposEntidad.length > 1" class="space-y-2">
          <Label for="tipo-entidad">Tipo de Entidad</Label>
          <div class="flex gap-2">
            <Select
              :model-value="filtros.tipo_entidad || 'null'"
              @update:model-value="(val) => updateFilter('tipo_entidad', val === 'null' ? null : val)"
            >
              <SelectTrigger id="tipo-entidad">
                <SelectValue placeholder="Todas las entidades" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="null">Todas las entidades</SelectItem>
                <SelectItem
                  v-for="tipo in tiposEntidad"
                  :key="tipo.value"
                  :value="tipo.value"
                >
                  {{ tipo.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <Button
              v-if="filtros.tipo_entidad"
              variant="ghost"
              size="icon"
              @click="clearFilter('tipo_entidad')"
            >
              <X class="h-4 w-4" />
            </Button>
          </div>
        </div>

        <!-- Filtro por Tipo de Acción -->
        <div class="space-y-2">
          <Label for="tipo-accion">Tipo de Acción</Label>
          <div class="flex gap-2">
            <Select
              :model-value="filtros.tipo_accion || 'null'"
              @update:model-value="(val) => updateFilter('tipo_accion', val === 'null' ? null : val)"
            >
              <SelectTrigger id="tipo-accion">
                <SelectValue placeholder="Todas las acciones" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="null">Todas las acciones</SelectItem>
                <SelectItem
                  v-for="tipo in tiposAccion"
                  :key="tipo.value"
                  :value="tipo.value"
                >
                  {{ tipo.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <Button
              v-if="filtros.tipo_accion"
              variant="ghost"
              size="icon"
              @click="clearFilter('tipo_accion')"
            >
              <X class="h-4 w-4" />
            </Button>
          </div>
        </div>

        <!-- Filtro por Rango de Fechas -->
        <div class="space-y-2">
          <Label>Rango de Fechas</Label>
          <div class="flex gap-2">
            <Popover>
              <PopoverTrigger as-child>
                <Button variant="outline" class="w-full justify-start text-left font-normal">
                  <CalendarIcon class="mr-2 h-4 w-4" />
                  <span v-if="filtros.fecha_inicio || filtros.fecha_fin" class="text-sm">
                    {{ filtros.fecha_inicio ? formatDateDisplay(filtros.fecha_inicio) : '...' }}
                    -
                    {{ filtros.fecha_fin ? formatDateDisplay(filtros.fecha_fin) : '...' }}
                  </span>
                  <span v-else class="text-muted-foreground">Seleccionar rango</span>
                </Button>
              </PopoverTrigger>
              <PopoverContent class="w-auto p-0" align="start">
                <div class="p-3 space-y-2">
                  <div>
                    <Label class="text-xs">Desde</Label>
                    <input
                      type="date"
                      :value="filtros.fecha_inicio || ''"
                      @input="(e) => updateFilter('fecha_inicio', (e.target as HTMLInputElement).value)"
                      class="w-full mt-1 px-3 py-2 border rounded-md text-sm"
                    />
                  </div>
                  <div>
                    <Label class="text-xs">Hasta</Label>
                    <input
                      type="date"
                      :value="filtros.fecha_fin || ''"
                      @input="(e) => updateFilter('fecha_fin', (e.target as HTMLInputElement).value)"
                      class="w-full mt-1 px-3 py-2 border rounded-md text-sm"
                    />
                  </div>
                </div>
              </PopoverContent>
            </Popover>
            <Button
              v-if="filtros.fecha_inicio || filtros.fecha_fin"
              variant="ghost"
              size="icon"
              @click="clearFilter('fecha_inicio'); clearFilter('fecha_fin')"
            >
              <X class="h-4 w-4" />
            </Button>
          </div>
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

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Same content as above but without Card wrapper -->
      <!-- ... (copy the same filter content) -->
    </div>
  </div>
</template>
