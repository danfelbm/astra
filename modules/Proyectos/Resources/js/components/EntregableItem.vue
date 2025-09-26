<template>
  <div
    :class="[
      'group flex items-center space-x-3 p-3 rounded-lg border transition-all duration-200',
      isCompleted ? 'bg-muted/50 opacity-75' : 'hover:shadow-sm hover:border-primary/20',
      isSelected ? 'ring-2 ring-primary ring-offset-2' : '',
    ]"
  >
    <!-- Checkbox para completar -->
    <div v-if="canComplete" class="flex-shrink-0">
      <Checkbox
        :checked="isCompleted"
        @update:checked="handleComplete"
        :disabled="isCompleted || loading"
        class="data-[state=checked]:bg-primary data-[state=checked]:border-primary"
      />
    </div>

    <!-- Indicador de prioridad -->
    <div
      :class="[
        'w-1 h-8 rounded-full flex-shrink-0',
        prioridadClasses,
      ]"
      :title="`Prioridad ${prioridadLabel}`"
    ></div>

    <!-- Contenido principal -->
    <div class="flex-1 min-w-0">
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <!-- Nombre del entregable -->
          <h4
            :class="[
              'font-medium text-sm',
              isCompleted ? 'line-through text-muted-foreground' : '',
            ]"
          >
            {{ entregable.nombre }}
          </h4>

          <!-- Descripción -->
          <p v-if="entregable.descripcion && !collapsed" class="text-xs text-muted-foreground mt-1 line-clamp-2">
            {{ entregable.descripcion }}
          </p>

          <!-- Metadatos -->
          <div class="flex items-center space-x-3 mt-2 text-xs text-muted-foreground">
            <!-- Fechas -->
            <div v-if="entregable.fecha_fin" class="flex items-center">
              <CalendarDays class="mr-1 h-3 w-3" />
              <span :class="{'text-red-500': isOverdue}">
                {{ formatDate(entregable.fecha_fin) }}
              </span>
            </div>

            <!-- Responsable -->
            <div v-if="entregable.responsable" class="flex items-center">
              <User class="mr-1 h-3 w-3" />
              <span>{{ entregable.responsable.name }}</span>
            </div>

            <!-- Usuarios asignados -->
            <div v-if="entregable.usuarios && entregable.usuarios.length > 0" class="flex items-center">
              <Users class="mr-1 h-3 w-3" />
              <span>{{ entregable.usuarios.length }} asignados</span>
            </div>

            <!-- Estado -->
            <Badge :variant="estadoVariant" class="h-5">
              {{ estadoLabel }}
            </Badge>
          </div>

          <!-- Información de completado -->
          <div v-if="isCompleted && entregable.completado_por" class="flex items-center space-x-2 mt-2 text-xs text-muted-foreground">
            <CheckCircle2 class="h-3 w-3 text-green-500" />
            <span>
              Completado por {{ entregable.completado_por.name }}
              {{ entregable.completado_at ? ` el ${formatDate(entregable.completado_at)}` : '' }}
            </span>
          </div>

          <!-- Notas de completado -->
          <div v-if="isCompleted && entregable.notas_completado && !collapsed" class="mt-2 p-2 bg-muted rounded text-xs">
            {{ entregable.notas_completado }}
          </div>
        </div>

        <!-- Acciones -->
        <div class="flex items-center space-x-1 ml-3">
          <!-- Botón de colapsar/expandir -->
          <Button
            v-if="entregable.descripcion || (isCompleted && entregable.notas_completado)"
            variant="ghost"
            size="icon"
            class="h-7 w-7"
            @click="collapsed = !collapsed"
          >
            <ChevronDown :class="['h-4 w-4 transition-transform', collapsed ? '' : 'rotate-180']" />
          </Button>

          <!-- Menú de acciones -->
          <DropdownMenu v-if="(canEdit || canDelete) && !isCompleted">
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="icon" class="h-7 w-7 opacity-0 group-hover:opacity-100 transition-opacity">
                <MoreVertical class="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem v-if="canEdit" @click="$emit('edit', entregable)">
                <Edit2 class="mr-2 h-4 w-4" />
                Editar
              </DropdownMenuItem>
              <DropdownMenuItem v-if="canAssign" @click="$emit('assign', entregable)">
                <UserPlus class="mr-2 h-4 w-4" />
                Asignar usuarios
              </DropdownMenuItem>
              <DropdownMenuItem @click="$emit('duplicate', entregable)">
                <Copy class="mr-2 h-4 w-4" />
                Duplicar
              </DropdownMenuItem>
              <DropdownMenuSeparator v-if="canDelete" />
              <DropdownMenuItem v-if="canDelete" @click="$emit('delete', entregable)" class="text-red-600">
                <Trash2 class="mr-2 h-4 w-4" />
                Eliminar
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { Checkbox } from '@modules/Core/Resources/js/components/ui/checkbox';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@modules/Core/Resources/js/components/ui/dropdown-menu';
import {
  CalendarDays,
  CheckCircle2,
  ChevronDown,
  Copy,
  Edit2,
  MoreVertical,
  Trash2,
  User,
  UserPlus,
  Users,
} from 'lucide-vue-next';
import type { Entregable } from '@modules/Proyectos/Resources/js/types/hitos';

// Props
const props = defineProps<{
  entregable: Entregable;
  canEdit?: boolean;
  canDelete?: boolean;
  canComplete?: boolean;
  canAssign?: boolean;
  isSelected?: boolean;
}>();

// Emits
const emit = defineEmits<{
  edit: [entregable: Entregable];
  delete: [entregable: Entregable];
  duplicate: [entregable: Entregable];
  assign: [entregable: Entregable];
  complete: [entregable: Entregable, notes?: string];
  'update:selected': [value: boolean];
}>();

// State
const collapsed = ref(true);
const loading = ref(false);

// Computed
const isCompleted = computed(() => props.entregable.estado === 'completado');

const isOverdue = computed(() => {
  if (!props.entregable.fecha_fin || isCompleted.value) return false;
  return new Date(props.entregable.fecha_fin) < new Date();
});

const estadoLabel = computed(() => {
  const estados = {
    pendiente: 'Pendiente',
    en_progreso: 'En Progreso',
    completado: 'Completado',
    cancelado: 'Cancelado',
  };
  return estados[props.entregable.estado] || props.entregable.estado;
});

const estadoVariant = computed(() => {
  const variantes = {
    pendiente: 'secondary',
    en_progreso: 'default',
    completado: 'success',
    cancelado: 'destructive',
  };
  return variantes[props.entregable.estado] || 'secondary';
});

const prioridadLabel = computed(() => {
  const prioridades = {
    baja: 'Baja',
    media: 'Media',
    alta: 'Alta',
  };
  return prioridades[props.entregable.prioridad] || props.entregable.prioridad;
});

const prioridadClasses = computed(() => {
  const clases = {
    baja: 'bg-blue-400',
    media: 'bg-yellow-400',
    alta: 'bg-red-400',
  };
  return clases[props.entregable.prioridad] || 'bg-gray-400';
});

// Methods
const formatDate = (date: string | Date | null) => {
  if (!date) return '';
  try {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    return format(dateObj, 'dd MMM yyyy', { locale: es });
  } catch {
    return '';
  }
};

const handleComplete = async (checked: boolean) => {
  if (!checked || isCompleted.value) return;

  loading.value = true;
  // Aquí podrías mostrar un diálogo para pedir notas opcionales
  emit('complete', props.entregable);

  // El componente padre manejará la actualización real
  setTimeout(() => {
    loading.value = false;
  }, 500);
};
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>