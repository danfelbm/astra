<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import { Network } from 'lucide-vue-next';
import HitoTreeItem from './HitoTreeItem.vue';
import { useHitoHierarchy, type HitoWithChildren } from '../composables/useHitoHierarchy';
import type { Hito } from '../types/hitos';

interface Props {
  hitos: Hito[];
  title?: string;
  description?: string;
  selectable?: boolean;
  selectedId?: number | null;
  disabledIds?: number[];
  showStats?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Jerarquía de Hitos',
  description: 'Vista en árbol de la estructura de hitos',
  selectable: false,
  selectedId: null,
  disabledIds: () => [],
  showStats: true
});

const emit = defineEmits<{
  select: [hito: HitoWithChildren];
}>();

// Usar el composable de jerarquía
const { arbolHitos, hitosRaiz, contarPorNivel } = useHitoHierarchy(computed(() => props.hitos));

// Verificar si un hito está seleccionado
const isSelected = (hitoId: number): boolean => {
  return props.selectedId === hitoId;
};

// Verificar si un hito está deshabilitado
const isDisabled = (hitoId: number): boolean => {
  return props.disabledIds.includes(hitoId);
};

// Manejar selección
const handleSelect = (hito: HitoWithChildren) => {
  if (!isDisabled(hito.id)) {
    emit('select', hito);
  }
};

// Estadísticas
const stats = computed(() => ({
  total: props.hitos.length,
  raices: hitosRaiz.value.length,
  niveles: Object.keys(contarPorNivel.value).length,
  porNivel: contarPorNivel.value
}));
</script>

<template>
  <Card>
    <CardHeader>
      <div class="flex items-center gap-2">
        <Network class="h-5 w-5" />
        <CardTitle>{{ title }}</CardTitle>
      </div>
      <CardDescription>
        {{ description }}
      </CardDescription>
    </CardHeader>
    <CardContent class="space-y-4">
      <!-- Estadísticas -->
      <div v-if="showStats && hitos.length > 0" class="flex gap-4 text-sm text-muted-foreground border-b pb-3">
        <div>
          <span class="font-medium">Total:</span> {{ stats.total }}
        </div>
        <div>
          <span class="font-medium">Raíces:</span> {{ stats.raices }}
        </div>
        <div>
          <span class="font-medium">Niveles:</span> {{ stats.niveles }}
        </div>
      </div>

      <!-- Árbol -->
      <div v-if="arbolHitos.length > 0" class="space-y-2">
        <HitoTreeItem
          v-for="hito in arbolHitos"
          :key="hito.id"
          :hito="hito"
          :nivel="0"
          :selectable="selectable"
          :selected="isSelected(hito.id)"
          :disabled="isDisabled(hito.id)"
          @select="handleSelect"
        />
      </div>

      <!-- Estado vacío -->
      <Alert v-else>
        <Network class="h-4 w-4" />
        <AlertDescription>
          No hay hitos disponibles para mostrar en la jerarquía
        </AlertDescription>
      </Alert>
    </CardContent>
  </Card>
</template>
