<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@modules/Core/Resources/js/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@modules/Core/Resources/js/components/ui/select';
import { Network, X } from 'lucide-vue-next';
import HitoTreeView from './HitoTreeView.vue';
import { useHitoHierarchy, type HitoWithChildren } from '../composables/useHitoHierarchy';
import type { Hito } from '../types/hitos';

interface HitoDisponible {
  id: number;
  nombre: string;
  ruta_completa?: string;
  nivel?: number;
}

interface Props {
  hitos: HitoDisponible[];
  modelValue?: number | null;
  label?: string;
  description?: string;
  disabled?: boolean;
  required?: boolean;
  error?: string;
  placeholder?: string;
  // Para vista avanzada
  showAdvancedView?: boolean;
  currentHitoId?: number | null; // Para excluir el hito actual y sus descendientes
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  label: 'Hito Padre',
  description: 'Selecciona un hito padre para crear una jerarquía',
  disabled: false,
  required: false,
  error: '',
  placeholder: 'Sin padre (raíz)',
  showAdvancedView: true,
  currentHitoId: null
});

const emit = defineEmits<{
  'update:modelValue': [value: number | null];
  change: [value: number | null];
}>();

// Estado local
const localValue = ref<number | null>(props.modelValue);
const showDialog = ref(false);

// Sincronizar con prop
watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue;
});

// Convertir hitos disponibles a formato Hito
const hitosCompletos = computed<Hito[]>(() => {
  return props.hitos.map(h => ({
    id: h.id,
    nombre: h.nombre,
    parent_id: null, // Se inferirá del nivel
    nivel: h.nivel || 0,
    ruta_completa: h.ruta_completa || h.nombre,
    // Otros campos necesarios con valores por defecto
    proyecto_id: 0,
    descripcion: '',
    fecha_inicio: null,
    fecha_fin: null,
    estado: 'pendiente',
    estado_label: 'Pendiente',
    porcentaje_completado: 0,
    orden: 0,
    created_at: '',
    updated_at: '',
  } as Hito));
});

// Usar composable para obtener información de jerarquía
const { getHitosDisponiblesComoPadres, getRutaCompleta, getNivel } = useHitoHierarchy(hitosCompletos);

// Hitos disponibles (excluyendo el actual y sus descendientes si aplica)
const hitosDisponibles = computed(() => {
  if (props.currentHitoId) {
    return getHitosDisponiblesComoPadres(props.currentHitoId);
  }
  return hitosCompletos.value;
});

// IDs deshabilitados (el hito actual y sus descendientes)
const disabledIds = computed(() => {
  if (!props.currentHitoId) return [];

  const allIds = hitosCompletos.value.map(h => h.id);
  const availableIds = hitosDisponibles.value.map(h => h.id);

  return allIds.filter(id => !availableIds.includes(id));
});

// Hito seleccionado
const selectedHito = computed(() => {
  return props.hitos.find(h => h.id === localValue.value) || null;
});

// Manejar cambio
const handleChange = (value: string | null | number) => {
  const numValue = value === 'null' || value === null ? null : Number(value);
  localValue.value = numValue;
  emit('update:modelValue', numValue);
  emit('change', numValue);
};

// Manejar selección del árbol
const handleTreeSelect = (hito: HitoWithChildren) => {
  handleChange(hito.id);
  showDialog.value = false;
};

// Limpiar selección
const clearSelection = () => {
  handleChange(null);
};
</script>

<template>
  <div class="space-y-2">
    <!-- Label -->
    <Label v-if="label" :for="`hito-selector-${Math.random()}`">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </Label>

    <!-- Selector Simple o Avanzado -->
    <div class="flex gap-2">
      <!-- Select Simple -->
      <div class="flex-1">
        <Select
          :model-value="localValue?.toString() || 'null'"
          :disabled="disabled"
          @update:model-value="handleChange"
        >
          <SelectTrigger>
            <SelectValue :placeholder="placeholder" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="null">{{ placeholder }}</SelectItem>
            <SelectItem
              v-for="hito in hitos"
              :key="hito.id"
              :value="hito.id.toString()"
            >
              <span class="flex items-center gap-2">
                <span v-if="hito.nivel && hito.nivel > 0" class="text-muted-foreground">
                  {{ '—'.repeat(hito.nivel) }}
                </span>
                <span>{{ hito.nombre }}</span>
                <Badge v-if="hito.nivel" variant="outline" class="text-xs ml-auto">
                  Nivel {{ hito.nivel }}
                </Badge>
              </span>
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Botón limpiar -->
      <Button
        v-if="localValue && !disabled"
        variant="outline"
        size="icon"
        @click="clearSelection"
        title="Limpiar selección"
      >
        <X class="h-4 w-4" />
      </Button>

      <!-- Botón vista avanzada -->
      <Dialog v-if="showAdvancedView && hitos.length > 0" v-model:open="showDialog">
        <DialogTrigger as-child>
          <Button variant="outline" size="icon" :disabled="disabled" title="Vista jerárquica">
            <Network class="h-4 w-4" />
          </Button>
        </DialogTrigger>
        <DialogContent class="max-w-3xl max-h-[80vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Seleccionar Hito Padre</DialogTitle>
            <DialogDescription>
              Selecciona un hito de la jerarquía para establecerlo como padre
            </DialogDescription>
          </DialogHeader>

          <HitoTreeView
            :hitos="hitosDisponibles"
            title="Hitos Disponibles"
            :description="`Selecciona un hito padre. ${currentHitoId ? 'Los descendientes del hito actual están deshabilitados.' : ''}`"
            :selectable="true"
            :selected-id="localValue"
            :disabled-ids="disabledIds"
            :show-stats="true"
            @select="handleTreeSelect"
          />
        </DialogContent>
      </Dialog>
    </div>

    <!-- Descripción -->
    <p v-if="description && !error" class="text-xs text-muted-foreground">
      {{ description }}
    </p>

    <!-- Información del hito seleccionado -->
    <div v-if="selectedHito" class="p-3 bg-muted rounded-md text-sm">
      <p class="font-medium">{{ selectedHito.nombre }}</p>
      <p v-if="selectedHito.ruta_completa" class="text-xs text-muted-foreground mt-1">
        Ruta: {{ selectedHito.ruta_completa }}
      </p>
      <Badge v-if="selectedHito.nivel !== undefined" variant="outline" class="mt-2">
        Nivel {{ selectedHito.nivel }}
      </Badge>
    </div>

    <!-- Error -->
    <p v-if="error" class="text-xs text-red-600">
      {{ error }}
    </p>
  </div>
</template>
