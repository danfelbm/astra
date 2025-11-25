<template>
  <div
    :class="[
      'obligacion-item group',
      selected && 'bg-blue-50 border-blue-300',
      isDragOver && 'bg-gray-100 border-dashed',
      'border rounded-lg p-2 mb-1 transition-all hover:shadow-sm'
    ]"
    :style="{ marginLeft: `${nivel * 24}px` }"
    :draggable="draggable && editable"
    @dragstart="handleDragStart"
    @dragover="handleDragOver"
    @drop="handleDrop"
    @dragenter="handleDragEnter"
    @dragleave="handleDragLeave"
  >
    <div class="flex items-start gap-2">
      <!-- Botón expandir/colapsar -->
      <Button
        v-if="obligacion.tiene_hijos"
        variant="ghost"
        size="icon"
        class="h-6 w-6 shrink-0"
        @click.stop="$emit('toggle')"
      >
        <ChevronRight
          :class="[
            'h-4 w-4 transition-transform',
            expanded && 'rotate-90'
          ]"
        />
      </Button>
      <div v-else class="w-6" />

      <!-- Icono simplificado -->
      <div class="h-6 w-6 rounded-full flex items-center justify-center shrink-0 bg-gray-100">
        <Circle class="h-3 w-3 text-gray-600" />
      </div>

      <!-- Contenido principal -->
      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <h4 class="font-medium text-sm truncate">
              {{ obligacion.titulo }}
            </h4>

            <p
              v-if="obligacion.descripcion"
              class="text-xs text-gray-600 mt-1 line-clamp-2"
            >
              {{ obligacion.descripcion }}
            </p>

            <!-- Metadatos -->
            <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
              <!-- Contador de hijos -->
              <div v-if="obligacion.tiene_hijos" class="flex items-center gap-1">
                <Layers class="h-3 w-3" />
                <span>{{ obligacion.total_hijos }} hijos</span>
              </div>

              <!-- Archivos adjuntos -->
              <div v-if="obligacion.archivos_adjuntos?.length" class="flex items-center gap-1">
                <Paperclip class="h-3 w-3" />
                <span>{{ obligacion.archivos_adjuntos.length }}</span>
              </div>
            </div>
          </div>

          <!-- Acciones (siempre visibles) -->
          <div
            v-if="editable"
            class="flex items-center gap-1 shrink-0"
          >
            <!-- Botón Añadir hijo -->
            <Button
              v-if="hasPermission('obligaciones.create')"
              variant="ghost"
              size="sm"
              class="h-7 px-2 text-xs"
              @click.stop="$emit('add-child')"
            >
              <Plus class="h-3 w-3 mr-1" />
              Añadir
            </Button>
            <!-- Botón Editar -->
            <Button
              v-if="hasPermission('obligaciones.edit')"
              variant="ghost"
              size="sm"
              class="h-7 px-2 text-xs"
              @click.stop="$emit('edit')"
            >
              <Pencil class="h-3 w-3 mr-1" />
              Editar
            </Button>
            <!-- Botón Eliminar -->
            <Button
              v-if="hasPermission('obligaciones.delete')"
              variant="ghost"
              size="sm"
              class="h-7 px-2 text-xs text-red-600 hover:text-red-700 hover:bg-red-50"
              @click.stop="handleDelete"
            >
              <Trash2 class="h-3 w-3 mr-1" />
              Eliminar
            </Button>
          </div>
        </div>
      </div>
    </div>

    <!-- Hijos (slot) -->
    <div v-if="expanded && obligacion.hijos?.length" class="mt-2">
      <slot name="children" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import {
  ChevronRight,
  Circle,
  Layers,
  Paperclip,
  Plus,
  Pencil,
  Trash2
} from 'lucide-vue-next';
import type { ObligacionContrato, ObligacionItemProps } from '@modules/Proyectos/Resources/js/types/obligaciones';
import { usePermissions } from '@modules/Core/Resources/js/composables/usePermissions';

const props = withDefaults(defineProps<ObligacionItemProps>(), {
  nivel: 0,
  expanded: false,
  selected: false,
  editable: true
});

const emit = defineEmits<{
  toggle: [];
  select: [];
  edit: [];
  delete: [];
  'add-child': [];
  'drag-start': [item: ObligacionContrato, event: DragEvent];
  'drag-over': [event: DragEvent];
  drop: [item: ObligacionContrato, event: DragEvent];
}>();

const { hasPermission } = usePermissions();

// Estado local
const isDragOver = ref(false);
const draggable = computed(() => props.editable && hasPermission('obligaciones.edit'));

// Métodos
const handleSelect = () => {
  if (props.selected) return;
  emit('select');
};

const handleDelete = () => {
  const mensaje = props.obligacion.tiene_hijos
    ? `¿Estás seguro de eliminar "${props.obligacion.titulo}" y todas sus obligaciones hijas?`
    : `¿Estás seguro de eliminar "${props.obligacion.titulo}"?`;

  if (confirm(mensaje)) {
    emit('delete');
  }
};

const handleDragStart = (event: DragEvent) => {
  if (!draggable.value) return;
  emit('drag-start', props.obligacion, event);
};

const handleDragOver = (event: DragEvent) => {
  if (!draggable.value) return;
  event.preventDefault();
  emit('drag-over', event);
};

const handleDrop = (event: DragEvent) => {
  if (!draggable.value) return;
  isDragOver.value = false;
  emit('drop', props.obligacion, event);
};

const handleDragEnter = () => {
  if (!draggable.value) return;
  isDragOver.value = true;
};

const handleDragLeave = () => {
  if (!draggable.value) return;
  isDragOver.value = false;
};
</script>

<style scoped>
.obligacion-item {
  transition: all 0.2s ease;
}

.obligacion-item:hover {
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
