<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@modules/Core/Resources/js/components/ui/table';
import { Checkbox } from '@modules/Core/Resources/js/components/ui/checkbox';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@modules/Core/Resources/js/components/ui/avatar';
import {
  Calendar,
  AlertCircle,
  Users,
  Eye,
  Edit,
  CheckCircle2,
  Trash2,
  ChevronUp,
  ChevronDown,
  CircleDot,
  Clock,
} from 'lucide-vue-next';
import type { Entregable, EstadoEntregable, PrioridadEntregable } from '@modules/Proyectos/Resources/js/types/hitos';
import StatusChangeModal from './StatusChangeModal.vue';

// Props
interface Props {
  entregables: Entregable[];
  proyectoId: number;
  hitoId: number;
  canEdit?: boolean;
  canDelete?: boolean;
  canComplete?: boolean;
  showCheckbox?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  canEdit: false,
  canDelete: false,
  canComplete: false,
  showCheckbox: true,
});

// Emits (actualizados para incluir observaciones)
const emit = defineEmits<{
  'view': [entregable: Entregable];
  'edit': [entregable: Entregable];
  'delete': [entregable: Entregable];
  'complete': [entregable: Entregable, observaciones: string];
  'mark-in-progress': [entregable: Entregable, observaciones: string];
  'selection-change': [selectedIds: number[]];
}>();

// Estado local
const selectedEntregables = ref<number[]>([]);

// Estado para el modal de cambio de estado
const statusChangeModalOpen = ref(false);
const entregableToChange = ref<Entregable | null>(null);
const nuevoEstadoPendiente = ref<'pendiente' | 'en_progreso' | 'completado' | 'cancelado'>('pendiente');
const statusChangeLoading = ref(false);

// Computed
const entregablesConInfo = computed(() => {
  return props.entregables.map(entregable => ({
    ...entregable,
    diasRestantes: getDiasRestantes(entregable.fecha_fin),
    estaVencido: entregable.fecha_fin && getDiasRestantes(entregable.fecha_fin) < 0,
    estaProximoVencer: entregable.fecha_fin && getDiasRestantes(entregable.fecha_fin) >= 0 && getDiasRestantes(entregable.fecha_fin) <= 7,
  }));
});

// Métodos
const toggleSelectAll = () => {
  if (selectedEntregables.value.length === props.entregables.length) {
    selectedEntregables.value = [];
  } else {
    selectedEntregables.value = props.entregables.map(e => e.id);
  }
  emit('selection-change', selectedEntregables.value);
};

const isSelected = (id: number) => {
  return selectedEntregables.value.includes(id);
};

const toggleSelection = (id: number) => {
  const index = selectedEntregables.value.indexOf(id);
  if (index > -1) {
    selectedEntregables.value.splice(index, 1);
  } else {
    selectedEntregables.value.push(id);
  }
  emit('selection-change', selectedEntregables.value);
};

const getEstadoBadgeVariant = (estado: EstadoEntregable) => {
  const variants = {
    pendiente: 'secondary',
    en_progreso: 'default',
    completado: 'success',
    cancelado: 'destructive',
  };
  return variants[estado] || 'secondary';
};

const getPrioridadBadgeVariant = (prioridad: PrioridadEntregable) => {
  const variants = {
    baja: 'outline',
    media: 'default',
    alta: 'destructive',
  };
  return variants[prioridad] || 'outline';
};

const getPrioridadIcon = (prioridad: PrioridadEntregable) => {
  if (prioridad === 'alta') return ChevronUp;
  if (prioridad === 'baja') return ChevronDown;
  return CircleDot;
};

const formatDate = (date: string | null) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
};

const getDiasRestantes = (fecha: string | null) => {
  if (!fecha) return null;
  const hoy = new Date();
  const fechaFin = new Date(fecha);
  const diff = fechaFin.getTime() - hoy.getTime();
  return Math.ceil(diff / (1000 * 60 * 60 * 24));
};

// Handlers de acciones
const handleView = (entregable: Entregable) => {
  emit('view', entregable);
};

const handleEdit = (entregable: Entregable) => {
  emit('edit', entregable);
};

// Abre el modal para completar
const handleComplete = (entregable: Entregable) => {
  entregableToChange.value = entregable;
  nuevoEstadoPendiente.value = 'completado';
  statusChangeModalOpen.value = true;
};

const handleDelete = (entregable: Entregable) => {
  emit('delete', entregable);
};

// Abre el modal para marcar en progreso
const handleMarkInProgress = (entregable: Entregable) => {
  entregableToChange.value = entregable;
  nuevoEstadoPendiente.value = 'en_progreso';
  statusChangeModalOpen.value = true;
};

// Confirma el cambio de estado con observaciones
const confirmStatusChange = (observaciones: string) => {
  if (!entregableToChange.value) return;

  if (nuevoEstadoPendiente.value === 'completado') {
    emit('complete', entregableToChange.value, observaciones);
  } else if (nuevoEstadoPendiente.value === 'en_progreso') {
    emit('mark-in-progress', entregableToChange.value, observaciones);
  }

  // Cerrar modal y limpiar estado
  statusChangeModalOpen.value = false;
  entregableToChange.value = null;
};
</script>

<template>
  <Table>
    <TableHeader>
      <TableRow>
        <TableHead v-if="showCheckbox" class="w-[40px]">
          <Checkbox
            :checked="selectedEntregables.length === entregables.length && entregables.length > 0"
            @update:checked="toggleSelectAll"
          />
        </TableHead>
        <TableHead>Entregable</TableHead>
        <TableHead>Responsable</TableHead>
        <TableHead>Estado</TableHead>
        <TableHead>Prioridad</TableHead>
        <TableHead>Fecha Fin</TableHead>
        <TableHead>Colaboradores</TableHead>
        <TableHead>Etiquetas</TableHead>
      </TableRow>
    </TableHeader>
    <TableBody>
      <TableRow
        v-for="entregable in entregablesConInfo"
        :key="entregable.id"
        class="hover:bg-muted/50"
      >
        <TableCell v-if="showCheckbox" @click.stop>
          <Checkbox
            :checked="isSelected(entregable.id)"
            @update:checked="() => toggleSelection(entregable.id)"
          />
        </TableCell>
        <TableCell>
          <div>
            <p class="font-medium">{{ entregable.nombre }}</p>
            <p v-if="entregable.descripcion" class="text-sm text-muted-foreground">
              {{ entregable.descripcion }}
            </p>

            <!-- Alertas de vencimiento -->
            <div v-if="entregable.estaVencido" class="flex items-center gap-1 mt-1">
              <AlertCircle class="h-3 w-3 text-red-600" />
              <span class="text-xs text-red-600">Vencido hace {{ Math.abs(entregable.diasRestantes) }} días</span>
            </div>
            <div v-else-if="entregable.estaProximoVencer" class="flex items-center gap-1 mt-1">
              <AlertCircle class="h-3 w-3 text-orange-600" />
              <span class="text-xs text-orange-600">Vence en {{ entregable.diasRestantes }} días</span>
            </div>

            <!-- Botones de acciones -->
            <div class="flex gap-1 mt-2">
              <Button
                variant="ghost"
                size="sm"
                class="h-7 px-2 text-xs"
                @click="handleView(entregable)"
              >
                <Eye class="mr-1 h-3 w-3" />
                Ver
              </Button>
              <Button
                v-if="canEdit"
                variant="ghost"
                size="sm"
                class="h-7 px-2 text-xs"
                @click="handleEdit(entregable)"
              >
                <Edit class="mr-1 h-3 w-3" />
                Editar
              </Button>
              <Button
                v-if="canEdit && entregable.estado === 'pendiente'"
                variant="ghost"
                size="sm"
                class="h-7 px-2 text-xs text-blue-600 hover:text-blue-700"
                @click="handleMarkInProgress(entregable)"
              >
                <Clock class="mr-1 h-3 w-3" />
                En Progreso
              </Button>
              <Button
                v-if="canComplete && entregable.estado !== 'completado'"
                variant="ghost"
                size="sm"
                class="h-7 px-2 text-xs text-green-600 hover:text-green-700"
                @click="handleComplete(entregable)"
              >
                <CheckCircle2 class="mr-1 h-3 w-3" />
                Completar
              </Button>
              <Button
                v-if="canDelete"
                variant="ghost"
                size="sm"
                class="h-7 px-2 text-xs text-red-600 hover:text-red-700"
                @click="handleDelete(entregable)"
              >
                <Trash2 class="mr-1 h-3 w-3" />
                Eliminar
              </Button>
            </div>
          </div>
        </TableCell>
        <TableCell>
          <div v-if="entregable.responsable" class="flex items-center gap-2">
            <Avatar class="h-8 w-8">
              <AvatarImage v-if="entregable.responsable.avatar" :src="entregable.responsable.avatar" />
              <AvatarFallback>{{ entregable.responsable.name.substring(0, 2).toUpperCase() }}</AvatarFallback>
            </Avatar>
            <span class="text-sm">{{ entregable.responsable.name }}</span>
          </div>
          <span v-else class="text-sm text-muted-foreground">Sin asignar</span>
        </TableCell>
        <TableCell>
          <Badge :variant="getEstadoBadgeVariant(entregable.estado)">
            {{ entregable.estado_label }}
          </Badge>
        </TableCell>
        <TableCell>
          <Badge :variant="getPrioridadBadgeVariant(entregable.prioridad)">
            <component :is="getPrioridadIcon(entregable.prioridad)" class="mr-1 h-3 w-3" />
            {{ entregable.prioridad_label }}
          </Badge>
        </TableCell>
        <TableCell>
          <div class="flex items-center gap-1">
            <Calendar class="h-4 w-4 text-muted-foreground" />
            <span class="text-sm">{{ formatDate(entregable.fecha_fin) }}</span>
          </div>
        </TableCell>
        <TableCell>
          <div v-if="entregable.usuarios && entregable.usuarios.length > 0" class="flex items-center gap-1">
            <Users class="h-4 w-4 text-muted-foreground" />
            <span class="text-sm">{{ entregable.usuarios.length }}</span>
          </div>
          <span v-else class="text-sm text-muted-foreground">-</span>
        </TableCell>
        <TableCell>
          <div v-if="entregable.etiquetas && entregable.etiquetas.length > 0" class="flex flex-wrap gap-1">
            <Badge
              v-for="etiqueta in entregable.etiquetas.slice(0, 2)"
              :key="etiqueta.id"
              variant="outline"
              class="text-xs"
              :style="{
                borderColor: etiqueta.color || '#94a3b8',
                color: etiqueta.color || '#64748b'
              }"
            >
              {{ etiqueta.nombre }}
            </Badge>
            <Badge
              v-if="entregable.etiquetas.length > 2"
              variant="outline"
              class="text-xs"
            >
              +{{ entregable.etiquetas.length - 2 }}
            </Badge>
          </div>
          <span v-else class="text-sm text-muted-foreground">-</span>
        </TableCell>
      </TableRow>

      <TableRow v-if="entregables.length === 0">
        <TableCell :colspan="showCheckbox ? 8 : 7" class="text-center py-8">
          <p class="text-muted-foreground">No se encontraron entregables</p>
        </TableCell>
      </TableRow>
    </TableBody>
  </Table>

  <!-- Modal de Cambio de Estado -->
  <StatusChangeModal
    v-model:open="statusChangeModalOpen"
    :entregable-nombre="entregableToChange?.nombre"
    :estado-actual="entregableToChange?.estado as 'pendiente' | 'en_progreso' | 'completado' | 'cancelado'"
    :nuevo-estado="nuevoEstadoPendiente"
    :loading="statusChangeLoading"
    @confirm="confirmStatusChange"
  />
</template>
