<template>
  <div class="obligacion-tree">
    <!-- Barra de herramientas -->
    <div class="flex items-center justify-between mb-4 p-2 bg-gray-50 rounded-lg">
      <div class="flex items-center gap-2">
        <Button
          size="sm"
          variant="outline"
          @click="expandAll"
          title="Expandir todo"
        >
          <ChevronDown class="h-4 w-4" />
        </Button>
        <Button
          size="sm"
          variant="outline"
          @click="collapseAll"
          title="Colapsar todo"
        >
          <ChevronRight class="h-4 w-4" />
        </Button>
        <Separator orientation="vertical" class="h-6" />
        <Button
          v-if="editable && hasPermission('obligaciones.create')"
          size="sm"
          variant="outline"
          @click="$emit('create')"
        >
          <Plus class="h-4 w-4 mr-1" />
          Nueva Obligación
        </Button>
      </div>

      <div class="flex items-center gap-2">
        <span class="text-sm text-gray-600">
          {{ obligacionesFlat.length }} obligaciones
        </span>
      </div>
    </div>

    <!-- Árbol de obligaciones -->
    <div
      class="obligacion-tree-container border rounded-lg p-2 min-h-[200px]"
      @dragover="handleDragOver"
      @drop="handleDropRoot"
    >
      <div v-if="loading" class="flex items-center justify-center py-8">
        <Loader2 class="h-8 w-8 animate-spin text-gray-400" />
      </div>

      <div v-else-if="!obligacionesArbol.length" class="text-center py-8 text-gray-500">
        No hay obligaciones registradas
      </div>

      <TransitionGroup
        v-else
        name="list"
        tag="div"
        class="space-y-1"
      >
        <ObligacionItem
          v-for="obligacion in obligacionesArbol"
          :key="obligacion.id"
          :obligacion="obligacion"
          :nivel="0"
          :expanded="expandedNodes.includes(obligacion.id)"
          :selected="selectedNode === obligacion.id"
          :editable="editable"
          :draggable="draggable"
          @toggle="toggleNode(obligacion.id)"
          @select="selectNode(obligacion.id)"
          @edit="$emit('edit', obligacion)"
          @delete="$emit('delete', obligacion)"
          @add-child="$emit('add-child', obligacion)"
          @drag-start="handleDragStart"
          @drag-over="handleDragOver"
          @drop="handleDrop"
        >
          <template v-if="obligacion.hijos?.length" #children>
            <ObligacionItem
              v-for="hijo in obligacion.hijos"
              :key="hijo.id"
              :obligacion="hijo"
              :nivel="1"
              :expanded="expandedNodes.includes(hijo.id)"
              :selected="selectedNode === hijo.id"
              :editable="editable"
              :draggable="draggable"
              @toggle="toggleNode(hijo.id)"
              @select="selectNode(hijo.id)"
              @edit="$emit('edit', hijo)"
              @delete="$emit('delete', hijo)"
              @add-child="$emit('add-child', hijo)"
              @drag-start="handleDragStart"
              @drag-over="handleDragOver"
              @drop="handleDrop"
            >
              <!-- Nivel 3: nietos -->
              <template v-if="hijo.hijos?.length" #children>
                <ObligacionItem
                  v-for="nieto in hijo.hijos"
                  :key="nieto.id"
                  :obligacion="nieto"
                  :nivel="2"
                  :expanded="expandedNodes.includes(nieto.id)"
                  :selected="selectedNode === nieto.id"
                  :editable="editable"
                  :draggable="draggable"
                  @toggle="toggleNode(nieto.id)"
                  @select="selectNode(nieto.id)"
                  @edit="$emit('edit', nieto)"
                  @delete="$emit('delete', nieto)"
                  @add-child="$emit('add-child', nieto)"
                  @drag-start="handleDragStart"
                  @drag-over="handleDragOver"
                  @drop="handleDrop"
                >
                  <!-- Nivel 4: bisnietos -->
                  <template v-if="nieto.hijos?.length" #children>
                    <ObligacionItem
                      v-for="bisnieto in nieto.hijos"
                      :key="bisnieto.id"
                      :obligacion="bisnieto"
                      :nivel="3"
                      :expanded="expandedNodes.includes(bisnieto.id)"
                      :selected="selectedNode === bisnieto.id"
                      :editable="editable"
                      :draggable="draggable"
                      @toggle="toggleNode(bisnieto.id)"
                      @select="selectNode(bisnieto.id)"
                      @edit="$emit('edit', bisnieto)"
                      @delete="$emit('delete', bisnieto)"
                      @add-child="$emit('add-child', bisnieto)"
                      @drag-start="handleDragStart"
                      @drag-over="handleDragOver"
                      @drop="handleDrop"
                    />
                  </template>
                </ObligacionItem>
              </template>
            </ObligacionItem>
          </template>
        </ObligacionItem>
      </TransitionGroup>
    </div>

    <!-- Estadísticas simplificadas -->
    <div v-if="obligacionesFlat.length" class="mt-4 bg-gray-50 p-2 rounded text-center">
      <div class="text-2xl font-bold">{{ obligacionesFlat.length }}</div>
      <div class="text-xs text-gray-600">Total de Obligaciones</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Separator } from '@modules/Core/Resources/js/components/ui/separator';
import ObligacionItem from './ObligacionItem.vue';
import { ChevronDown, ChevronRight, Plus, Loader2 } from 'lucide-vue-next';
import type {
  ObligacionContrato,
  ObligacionTreeProps,
  ObligacionEstadisticas,
  DragData
} from '@modules/Proyectos/Resources/js/types/obligaciones';
import { usePermissions } from '@modules/Core/Resources/js/composables/usePermissions';

const props = withDefaults(defineProps<ObligacionTreeProps>(), {
  editable: true,
  selectable: true,
  expandedNodes: () => [],
  selectedNode: null
});

const emit = defineEmits<{
  select: [obligacion: ObligacionContrato];
  edit: [obligacion: ObligacionContrato];
  delete: [obligacion: ObligacionContrato];
  'add-child': [parent: ObligacionContrato];
  create: [];
  move: [obligacion: ObligacionContrato, newParentId: number | null, newOrder: number];
  reorder: [items: number[], parentId: number | null];
  'update:expandedNodes': [nodes: number[]];
  'update:selectedNode': [node: number | null];
}>();

const { hasPermission } = usePermissions();

// Estado local
const loading = ref(false);
const expandedNodes = ref<number[]>(props.expandedNodes);
const selectedNode = ref<number | null>(props.selectedNode);
const draggedItem = ref<DragData | null>(null);
const draggable = computed(() => props.editable && hasPermission('obligaciones.edit'));

// Computed
const obligacionesFlat = computed(() => {
  const flat: ObligacionContrato[] = [];
  const addToFlat = (items: ObligacionContrato[]) => {
    items.forEach(item => {
      flat.push(item);
      if (item.hijos?.length) {
        addToFlat(item.hijos);
      }
    });
  };
  addToFlat(props.obligaciones);
  return flat;
});

const obligacionesArbol = computed(() => {
  return construirArbol(props.obligaciones);
});

// Estadísticas simplificadas - ya no se usan campos de estado

// Métodos
const toggleNode = (nodeId: number) => {
  const index = expandedNodes.value.indexOf(nodeId);
  if (index > -1) {
    expandedNodes.value.splice(index, 1);
  } else {
    expandedNodes.value.push(nodeId);
  }
  emit('update:expandedNodes', expandedNodes.value);
};

const selectNode = (nodeId: number | null) => {
  selectedNode.value = nodeId;
  emit('update:selectedNode', nodeId);
  if (nodeId) {
    const obligacion = obligacionesFlat.value.find(o => o.id === nodeId);
    if (obligacion) {
      emit('select', obligacion);
    }
  }
};

const expandAll = () => {
  expandedNodes.value = obligacionesFlat.value
    .filter(o => o.tiene_hijos)
    .map(o => o.id);
  emit('update:expandedNodes', expandedNodes.value);
};

const collapseAll = () => {
  expandedNodes.value = [];
  emit('update:expandedNodes', expandedNodes.value);
};

// Drag & Drop
const handleDragStart = (item: ObligacionContrato, event: DragEvent) => {
  if (!draggable.value) return;

  draggedItem.value = {
    obligacionId: item.id,
    parentId: item.parent_id,
    orden: item.orden,
    nivel: item.nivel
  };

  event.dataTransfer!.effectAllowed = 'move';
  event.dataTransfer!.setData('application/json', JSON.stringify(draggedItem.value));
};

const handleDragOver = (event: DragEvent) => {
  if (!draggable.value) return;
  event.preventDefault();
  event.dataTransfer!.dropEffect = 'move';
};

const handleDrop = (targetItem: ObligacionContrato, event: DragEvent) => {
  if (!draggable.value) return;

  event.preventDefault();
  event.stopPropagation();

  const data = JSON.parse(event.dataTransfer!.getData('application/json')) as DragData;

  if (!data || data.obligacionId === targetItem.id) {
    return;
  }

  // Validar que no se mueva a un descendiente
  if (esDescendiente(data.obligacionId, targetItem.id)) {
    console.error('No se puede mover una obligación dentro de sus propios hijos');
    return;
  }

  emit('move', obligacionesFlat.value.find(o => o.id === data.obligacionId)!, targetItem.id, targetItem.orden + 1);
  draggedItem.value = null;
};

const handleDropRoot = (event: DragEvent) => {
  if (!draggable.value) return;

  event.preventDefault();
  event.stopPropagation();

  const data = JSON.parse(event.dataTransfer!.getData('application/json')) as DragData;

  if (!data) return;

  emit('move', obligacionesFlat.value.find(o => o.id === data.obligacionId)!, null, 1);
  draggedItem.value = null;
};

// Utilidades
const construirArbol = (items: ObligacionContrato[]): ObligacionContrato[] => {
  if (!items || items.length === 0) return [];

  const mapa = new Map<number, ObligacionContrato>();
  const raices: ObligacionContrato[] = [];

  // Obtener todos los IDs presentes en el array
  const idsPresentes = new Set(items.map(item => item.id));

  items.forEach(item => {
    mapa.set(item.id, { ...item, hijos: item.hijos ? [...item.hijos] : [] });
  });

  items.forEach(item => {
    const nodo = mapa.get(item.id)!;
    // Es raíz si: parent_id es null O su padre no está en el conjunto de items
    // Esto permite mostrar sub-árboles (ej: solo los hijos de una obligación)
    if (item.parent_id === null || !idsPresentes.has(item.parent_id)) {
      raices.push(nodo);
    } else {
      const padre = mapa.get(item.parent_id);
      if (padre) {
        padre.hijos = padre.hijos || [];
        // Solo añadir si no está ya (evitar duplicados cuando hijos vienen pre-cargados)
        if (!padre.hijos.some(h => h.id === nodo.id)) {
          padre.hijos.push(nodo);
        }
      }
    }
  });

  return raices.sort((a, b) => a.orden - b.orden);
};

const esDescendiente = (parentId: number, childId: number): boolean => {
  const obligacion = obligacionesFlat.value.find(o => o.id === childId);
  if (!obligacion) return false;
  if (obligacion.parent_id === parentId) return true;
  if (obligacion.parent_id === null) return false;
  return esDescendiente(parentId, obligacion.parent_id);
};
</script>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}

.obligacion-tree-container {
  max-height: 600px;
  overflow-y: auto;
}
</style>