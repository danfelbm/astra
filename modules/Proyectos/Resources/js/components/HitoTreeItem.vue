<script setup lang="ts">
import { ref } from 'vue';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { ChevronRight, ChevronDown, Target } from 'lucide-vue-next';
import type { HitoWithChildren } from '../composables/useHitoHierarchy';

interface Props {
  hito: HitoWithChildren;
  nivel?: number;
  selectable?: boolean;
  selected?: boolean;
  disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  nivel: 0,
  selectable: false,
  selected: false,
  disabled: false
});

const emit = defineEmits<{
  select: [hito: HitoWithChildren];
}>();

// Estado de expansión
const isExpanded = ref(true);

// Tiene hijos
const hasChildren = props.hito.children && props.hito.children.length > 0;

// Manejar clic
const handleClick = () => {
  if (props.selectable && !props.disabled) {
    emit('select', props.hito);
  }
};

// Toggle expansión
const toggleExpand = (event: Event) => {
  event.stopPropagation();
  if (hasChildren) {
    isExpanded.value = !isExpanded.value;
  }
};

// Colores por estado
const getEstadoColor = (estado: string): string => {
  const colors: Record<string, string> = {
    pendiente: 'bg-gray-100 text-gray-800',
    en_progreso: 'bg-blue-100 text-blue-800',
    completado: 'bg-green-100 text-green-800',
    cancelado: 'bg-red-100 text-red-800',
  };
  return colors[estado] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
  <div class="select-none">
    <!-- Nodo actual -->
    <div
      :class="[
        'flex items-center gap-2 p-2 rounded-md transition-colors',
        selectable && !disabled ? 'cursor-pointer hover:bg-muted' : '',
        selected ? 'bg-primary/10 border border-primary' : '',
        disabled ? 'opacity-50 cursor-not-allowed' : ''
      ]"
      @click="handleClick"
    >
      <!-- Botón expandir/contraer -->
      <Button
        v-if="hasChildren"
        variant="ghost"
        size="icon"
        class="h-6 w-6 p-0"
        @click="toggleExpand"
      >
        <ChevronDown v-if="isExpanded" class="h-4 w-4" />
        <ChevronRight v-else class="h-4 w-4" />
      </Button>
      <div v-else class="w-6" />

      <!-- Icono -->
      <Target class="h-4 w-4 text-muted-foreground flex-shrink-0" />

      <!-- Nombre y metadata -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
          <span class="font-medium truncate">{{ hito.nombre }}</span>

          <!-- Badge de nivel -->
          <Badge v-if="nivel > 0" variant="outline" class="text-xs">
            Nivel {{ nivel }}
          </Badge>

          <!-- Badge de estado -->
          <Badge :class="getEstadoColor(hito.estado)" class="text-xs">
            {{ hito.estado_label || hito.estado }}
          </Badge>

          <!-- Contador de hijos -->
          <Badge v-if="hasChildren" variant="secondary" class="text-xs">
            {{ hito.children?.length }} {{ hito.children?.length === 1 ? 'hijo' : 'hijos' }}
          </Badge>
        </div>

        <!-- Descripción o info adicional -->
        <p v-if="hito.descripcion" class="text-xs text-muted-foreground truncate mt-0.5">
          {{ hito.descripcion }}
        </p>
      </div>

      <!-- Progreso -->
      <div v-if="hito.porcentaje_completado !== undefined" class="flex items-center gap-1 text-xs text-muted-foreground">
        <span>{{ hito.porcentaje_completado }}%</span>
      </div>
    </div>

    <!-- Hijos (recursivo) -->
    <div
      v-if="hasChildren && isExpanded"
      class="ml-6 mt-1 space-y-1 border-l-2 border-muted pl-2"
    >
      <HitoTreeItem
        v-for="child in hito.children"
        :key="child.id"
        :hito="child"
        :nivel="nivel + 1"
        :selectable="selectable"
        :selected="selected"
        :disabled="disabled"
        @select="emit('select', $event)"
      />
    </div>
  </div>
</template>
