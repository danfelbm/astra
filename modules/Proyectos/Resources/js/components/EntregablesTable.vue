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

// Props
interface Props {
  entregables: Entregable[];
  proyectoId: number;
  hitoId: number;
  canEdit?: boolean;
  canDelete?: boolean;
  canComplete?: boolean;
  showCheckbox?: boolean;
  grouped?: boolean; // Nueva prop para soportar agrupación
}

const props = withDefaults(defineProps<Props>(), {
  canEdit: false,
  canDelete: false,
  canComplete: false,
  showCheckbox: true,
  grouped: false,
});

// Emits
const emit = defineEmits<{
  'view': [entregable: Entregable];
  'edit': [entregable: Entregable];
  'delete': [entregable: Entregable];
  'complete': [entregable: Entregable];
  'mark-in-progress': [entregable: Entregable];
  'selection-change': [selectedIds: number[]];
}>();

// Estado local
const selectedEntregables = ref<number[]>([]);

// Computed
const entregablesConInfo = computed(() => {
  return props.entregables.map(entregable => ({
    ...entregable,
    diasRestantes: getDiasRestantes(entregable.fecha_fin),
    estaVencido: entregable.fecha_fin && getDiasRestantes(entregable.fecha_fin) < 0,
    estaProximoVencer: entregable.fecha_fin && getDiasRestantes(entregable.fecha_fin) >= 0 && getDiasRestantes(entregable.fecha_fin) <= 7,
  }));
});

// Agrupar entregables por estado cuando grouped=true
const entregablesAgrupados = computed(() => {
  if (!props.grouped) return null;

  return {
    pendientes: entregablesConInfo.value.filter(e => e.estado === 'pendiente'),
    en_progreso: entregablesConInfo.value.filter(e => e.estado === 'en_progreso'),
    completados: entregablesConInfo.value.filter(e => e.estado === 'completado'),
    cancelados: entregablesConInfo.value.filter(e => e.estado === 'cancelado'),
  };
});

const getEstadoIcon = (estado: string) => {
  switch (estado) {
    case 'completado':
      return CheckCircle2;
    case 'en_progreso':
      return Clock;
    case 'pendiente':
      return AlertCircle;
    default:
      return CircleDot;
  }
};

const getEstadoLabel = (estado: string) => {
  const labels: Record<string, string> = {
    pendiente: 'Pendientes',
    en_progreso: 'En Progreso',
    completado: 'Completados',
    cancelado: 'Cancelados',
  };
  return labels[estado] || estado;
};

const getEstadoColor = (estado: string) => {
  const colors: Record<string, string> = {
    pendiente: 'text-yellow-600',
    en_progreso: 'text-blue-600',
    completado: 'text-green-600',
    cancelado: 'text-red-600',
  };
  return colors[estado] || 'text-gray-600';
};

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

const handleComplete = (entregable: Entregable) => {
  emit('complete', entregable);
};

const handleDelete = (entregable: Entregable) => {
  emit('delete', entregable);
};

const handleMarkInProgress = (entregable: Entregable) => {
  emit('mark-in-progress', entregable);
};
</script>

<template>
  <!-- Vista agrupada -->
  <div v-if="grouped" class="space-y-6">
    <!-- Pendientes -->
    <div v-if="entregablesAgrupados.pendientes.length > 0">
      <h3 class="font-semibold mb-3 flex items-center gap-2">
        <component :is="getEstadoIcon('pendiente')" class="h-5 w-5" :class="getEstadoColor('pendiente')" />
        {{ getEstadoLabel('pendiente') }} ({{ entregablesAgrupados.pendientes.length }})
      </h3>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead v-if="showCheckbox" class="w-[40px]"></TableHead>
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
            v-for="entregable in entregablesAgrupados.pendientes"
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
                <p v-if="entregable.descripcion" class="text-sm text-muted-foreground line-clamp-2">
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
        </TableBody>
      </Table>
    </div>

    <!-- En Progreso -->
    <div v-if="entregablesAgrupados.en_progreso.length > 0">
      <h3 class="font-semibold mb-3 flex items-center gap-2">
        <component :is="getEstadoIcon('en_progreso')" class="h-5 w-5" :class="getEstadoColor('en_progreso')" />
        {{ getEstadoLabel('en_progreso') }} ({{ entregablesAgrupados.en_progreso.length }})
      </h3>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead v-if="showCheckbox" class="w-[40px]"></TableHead>
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
            v-for="entregable in entregablesAgrupados.en_progreso"
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
                <p v-if="entregable.descripcion" class="text-sm text-muted-foreground line-clamp-2">
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
        </TableBody>
      </Table>
    </div>

    <!-- Completados -->
    <div v-if="entregablesAgrupados.completados.length > 0">
      <h3 class="font-semibold mb-3 flex items-center gap-2">
        <component :is="getEstadoIcon('completado')" class="h-5 w-5" :class="getEstadoColor('completado')" />
        {{ getEstadoLabel('completado') }} ({{ entregablesAgrupados.completados.length }})
      </h3>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead v-if="showCheckbox" class="w-[40px]"></TableHead>
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
            v-for="entregable in entregablesAgrupados.completados"
            :key="entregable.id"
            class="hover:bg-muted/50 bg-green-50/30"
          >
            <TableCell v-if="showCheckbox" @click.stop>
              <Checkbox
                :checked="isSelected(entregable.id)"
                @update:checked="() => toggleSelection(entregable.id)"
              />
            </TableCell>
            <TableCell>
              <div>
                <p class="font-medium line-through text-muted-foreground">{{ entregable.nombre }}</p>
                <p v-if="entregable.descripcion" class="text-sm text-muted-foreground line-clamp-2">
                  {{ entregable.descripcion }}
                </p>

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
        </TableBody>
      </Table>
    </div>

    <!-- Cancelados -->
    <div v-if="entregablesAgrupados.cancelados.length > 0">
      <h3 class="font-semibold mb-3 flex items-center gap-2">
        <component :is="getEstadoIcon('cancelado')" class="h-5 w-5" :class="getEstadoColor('cancelado')" />
        {{ getEstadoLabel('cancelado') }} ({{ entregablesAgrupados.cancelados.length }})
      </h3>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead v-if="showCheckbox" class="w-[40px]"></TableHead>
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
            v-for="entregable in entregablesAgrupados.cancelados"
            :key="entregable.id"
            class="hover:bg-muted/50 opacity-60"
          >
            <TableCell v-if="showCheckbox" @click.stop>
              <Checkbox
                :checked="isSelected(entregable.id)"
                @update:checked="() => toggleSelection(entregable.id)"
              />
            </TableCell>
            <TableCell>
              <div>
                <p class="font-medium line-through">{{ entregable.nombre }}</p>
                <p v-if="entregable.descripcion" class="text-sm text-muted-foreground line-clamp-2">
                  {{ entregable.descripcion }}
                </p>

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
        </TableBody>
      </Table>
    </div>

    <!-- Mensaje si no hay entregables -->
    <div v-if="entregables.length === 0" class="text-center py-8">
      <p class="text-muted-foreground">No hay entregables asignados</p>
    </div>
  </div>

  <!-- Vista sin agrupar (tabla normal) -->
  <Table v-else>
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
</template>
