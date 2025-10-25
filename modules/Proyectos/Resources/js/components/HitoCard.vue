<template>
  <Card class="hover:shadow-lg transition-shadow duration-200">
    <CardHeader class="pb-3">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div :class="estadoClasses" class="w-2 h-2 rounded-full"></div>
          <CardTitle class="text-lg font-medium">{{ hito.nombre }}</CardTitle>
        </div>
        <div class="flex items-center space-x-2">
          <Badge :variant="prioridadVariant" class="text-xs">
            {{ hito.estado_label || estadoLabel }}
          </Badge>
          <DropdownMenu v-if="canEdit || canDelete">
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="icon" class="h-8 w-8">
                <MoreHorizontal class="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem v-if="canEdit" @click="$emit('edit', hito)">
                <Edit2 class="mr-2 h-4 w-4" />
                Editar
              </DropdownMenuItem>
              <DropdownMenuItem v-if="canManageDeliverables" @click="$emit('add-entregable', hito)">
                <Plus class="mr-2 h-4 w-4" />
                Añadir Entregable
              </DropdownMenuItem>
              <DropdownMenuItem @click="$emit('duplicate', hito)">
                <Copy class="mr-2 h-4 w-4" />
                Duplicar
              </DropdownMenuItem>
              <DropdownMenuSeparator v-if="canDelete" />
              <DropdownMenuItem v-if="canDelete" @click="$emit('delete', hito)" class="text-red-600">
                <Trash2 class="mr-2 h-4 w-4" />
                Eliminar
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>
    </CardHeader>

    <CardContent class="pb-3">
      <!-- Descripción -->
      <p v-if="hito.descripcion" class="text-sm text-muted-foreground mb-3 line-clamp-2">
        {{ hito.descripcion }}
      </p>

      <!-- Fechas -->
      <div v-if="hito.fecha_inicio || hito.fecha_fin" class="flex items-center space-x-4 text-sm text-muted-foreground mb-3">
        <div v-if="hito.fecha_inicio" class="flex items-center">
          <CalendarDays class="mr-1 h-3 w-3" />
          <span>{{ formatDate(hito.fecha_inicio) }}</span>
        </div>
        <div v-if="hito.fecha_fin" class="flex items-center">
          <CalendarCheck class="mr-1 h-3 w-3" />
          <span>{{ formatDate(hito.fecha_fin) }}</span>
        </div>
      </div>

      <!-- Responsable -->
      <div v-if="hito.responsable" class="flex items-center space-x-2 mb-3">
        <Avatar class="h-6 w-6">
          <AvatarFallback class="text-xs">
            {{ hito.responsable.name?.charAt(0)?.toUpperCase() }}
          </AvatarFallback>
        </Avatar>
        <span class="text-sm text-muted-foreground">{{ hito.responsable.name }}</span>
      </div>

      <!-- Etiquetas -->
      <div v-if="hito.etiquetas && hito.etiquetas.length > 0" class="flex flex-wrap gap-1 mb-3">
        <Badge
          v-for="etiqueta in hito.etiquetas.slice(0, 3)"
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
          v-if="hito.etiquetas.length > 3"
          variant="outline"
          class="text-xs"
        >
          +{{ hito.etiquetas.length - 3 }}
        </Badge>
      </div>

      <!-- Barra de progreso -->
      <div class="space-y-2">
        <div class="flex justify-between items-center text-xs">
          <span class="text-muted-foreground">Progreso</span>
          <span class="font-medium">{{ hito.porcentaje_completado }}%</span>
        </div>
        <Progress :value="hito.porcentaje_completado" class="h-2" />
      </div>

      <!-- Estadísticas de entregables -->
      <div v-if="hito.entregables && hito.entregables.length > 0" class="mt-3 flex items-center justify-between text-xs text-muted-foreground">
        <div class="flex items-center space-x-3">
          <span>{{ hito.entregables_completados || 0 }}/{{ hito.total_entregables || hito.entregables.length }} entregables</span>
        </div>
        <div v-if="hito.dias_restantes !== null" class="flex items-center">
          <Clock class="mr-1 h-3 w-3" />
          <span :class="{'text-red-500': hito.dias_restantes < 0, 'text-orange-500': hito.dias_restantes <= 7}">
            {{ hito.dias_restantes < 0 ? `Vencido hace ${Math.abs(hito.dias_restantes)} días` : `${hito.dias_restantes} días restantes` }}
          </span>
        </div>
      </div>
    </CardContent>

    <CardFooter v-if="showActions" class="pt-3 border-t">
      <div class="w-full flex justify-between">
        <Button variant="ghost" size="sm" @click="$emit('view', hito)">
          <Eye class="mr-2 h-4 w-4" />
          Ver detalles
        </Button>
        <Button variant="outline" size="sm" @click="$emit('view-entregables', hito)">
          <ListTodo class="mr-2 h-4 w-4" />
          Entregables
        </Button>
      </div>
    </CardFooter>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Progress } from '@modules/Core/Resources/js/components/ui/progress';
import { Avatar, AvatarFallback } from '@modules/Core/Resources/js/components/ui/avatar';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@modules/Core/Resources/js/components/ui/dropdown-menu';
import {
  CalendarDays,
  CalendarCheck,
  Clock,
  Copy,
  Edit2,
  Eye,
  ListTodo,
  MoreHorizontal,
  Plus,
  Trash2,
} from 'lucide-vue-next';
import type { Hito } from '@modules/Proyectos/Resources/js/types/hitos';

// Props
const props = defineProps<{
  hito: Hito;
  canEdit?: boolean;
  canDelete?: boolean;
  canManageDeliverables?: boolean;
  showActions?: boolean;
}>();

// Emits
defineEmits<{
  view: [hito: Hito];
  edit: [hito: Hito];
  delete: [hito: Hito];
  duplicate: [hito: Hito];
  'add-entregable': [hito: Hito];
  'view-entregables': [hito: Hito];
}>();

// Computed
const estadoLabel = computed(() => {
  const estados = {
    pendiente: 'Pendiente',
    en_progreso: 'En Progreso',
    completado: 'Completado',
    cancelado: 'Cancelado',
  };
  return estados[props.hito.estado] || props.hito.estado;
});

const estadoClasses = computed(() => {
  const clases = {
    pendiente: 'bg-gray-400',
    en_progreso: 'bg-blue-500 animate-pulse',
    completado: 'bg-green-500',
    cancelado: 'bg-red-500',
  };
  return clases[props.hito.estado] || 'bg-gray-400';
});

const prioridadVariant = computed(() => {
  const variantes = {
    pendiente: 'secondary',
    en_progreso: 'default',
    completado: 'success',
    cancelado: 'destructive',
  };
  return variantes[props.hito.estado] || 'secondary';
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
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>