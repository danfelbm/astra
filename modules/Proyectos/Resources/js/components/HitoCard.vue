<template>
  <Card class="hover:shadow-md transition-shadow duration-200">
    <!-- Header compacto -->
    <CardHeader :class="compact ? 'pb-2 pt-3 px-4' : 'pb-3'">
      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2 min-w-0 flex-1">
          <div :class="estadoClasses" class="w-2 h-2 rounded-full flex-shrink-0"></div>
          <CardTitle :class="compact ? 'text-base' : 'text-lg'" class="font-medium truncate">
            {{ hito.nombre }}
          </CardTitle>
        </div>
        <Badge :variant="prioridadVariant" :class="compact ? 'text-xs px-2 py-0.5' : 'text-xs'">
          {{ hito.estado_label || estadoLabel }}
        </Badge>
      </div>
    </CardHeader>

    <CardContent :class="compact ? 'pb-2 px-4 space-y-2' : 'pb-3 space-y-3'">
      <!-- Descripción (solo si no es compact) -->
      <p v-if="hito.descripcion && !compact" class="text-sm text-muted-foreground line-clamp-2">
        {{ hito.descripcion }}
      </p>

      <!-- Fechas y Responsable en línea -->
      <div class="flex items-center justify-between gap-4 text-xs text-muted-foreground">
        <div class="flex items-center gap-3">
          <div v-if="hito.fecha_inicio" class="flex items-center gap-1">
            <CalendarDays class="h-3 w-3" />
            <span>{{ formatDate(hito.fecha_inicio) }}</span>
          </div>
          <span v-if="hito.fecha_inicio && hito.fecha_fin" class="text-gray-300">→</span>
          <div v-if="hito.fecha_fin" class="flex items-center gap-1">
            <CalendarCheck class="h-3 w-3" />
            <span>{{ formatDate(hito.fecha_fin) }}</span>
          </div>
        </div>
        <div v-if="hito.responsable" class="flex items-center gap-1.5">
          <Avatar class="h-5 w-5">
            <AvatarFallback class="text-[10px]">
              {{ hito.responsable.name?.charAt(0)?.toUpperCase() }}
            </AvatarFallback>
          </Avatar>
          <span class="truncate max-w-[100px]">{{ hito.responsable.name }}</span>
        </div>
      </div>

      <!-- Barra de progreso compacta -->
      <div :class="compact ? 'space-y-1' : 'space-y-2'">
        <div class="flex justify-between items-center text-xs">
          <span class="text-muted-foreground">Progreso</span>
          <span class="font-medium">{{ hito.porcentaje_completado }}%</span>
        </div>
        <Progress :model-value="hito.porcentaje_completado" :class="compact ? 'h-1.5' : 'h-2'" />
      </div>

      <!-- Stats de entregables y días restantes -->
      <div class="flex items-center justify-between text-xs text-muted-foreground">
        <span>{{ hito.entregables_completados || 0 }}/{{ hito.total_entregables || hito.entregables?.length || 0 }} entregables</span>
        <div v-if="hito.dias_restantes !== null && hito.dias_restantes !== undefined" class="flex items-center gap-1">
          <Clock class="h-3 w-3" />
          <span :class="{'text-red-500 font-medium': hito.dias_restantes < 0, 'text-orange-500': hito.dias_restantes >= 0 && hito.dias_restantes <= 7}">
            {{ hito.dias_restantes < 0 ? `Vencido hace ${Math.abs(hito.dias_restantes)} días` : `${hito.dias_restantes} días` }}
          </span>
        </div>
      </div>

      <!-- Etiquetas (solo si no es compact y hay etiquetas) -->
      <div v-if="!compact && hito.etiquetas && hito.etiquetas.length > 0" class="flex flex-wrap gap-1">
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
        <Badge v-if="hito.etiquetas.length > 3" variant="outline" class="text-xs">
          +{{ hito.etiquetas.length - 3 }}
        </Badge>
      </div>
    </CardContent>

    <!-- Footer con TODAS las acciones -->
    <CardFooter v-if="showActions" :class="compact ? 'pt-2 pb-3 px-4 border-t' : 'pt-3 border-t'">
      <div class="w-full flex items-center justify-between gap-2">
        <!-- Acciones primarias -->
        <div class="flex items-center gap-1">
          <Button variant="ghost" size="sm" @click="$emit('view', hito)" class="h-8 px-2">
            <Eye class="h-4 w-4" />
            <span class="ml-1.5">Ver detalles</span>
          </Button>
          <Button variant="ghost" size="sm" @click="$emit('view-entregables', hito)" class="h-8 px-2">
            <ListTodo class="h-4 w-4" />
            <span class="ml-1.5">Entregables</span>
          </Button>
        </div>
        <!-- Acciones secundarias -->
        <div class="flex items-center gap-1">
          <Button v-if="canEdit" variant="ghost" size="sm" @click="$emit('edit', hito)" class="h-8 px-2">
            <Edit2 class="h-4 w-4" />
            <span class="ml-1.5">Editar</span>
          </Button>
          <Button v-if="canManageDeliverables" variant="ghost" size="sm" @click="$emit('add-entregable', hito)" class="h-8 px-2">
            <Plus class="h-4 w-4" />
            <span class="ml-1.5">Añadir</span>
          </Button>
          <Button variant="ghost" size="sm" @click="$emit('duplicate', hito)" class="h-8 px-2">
            <Copy class="h-4 w-4" />
            <span class="ml-1.5">Duplicar</span>
          </Button>
          <Button v-if="canDelete" variant="ghost" size="sm" @click="$emit('delete', hito)" class="h-8 px-2 text-red-600 hover:text-red-700 hover:bg-red-50">
            <Trash2 class="h-4 w-4" />
            <span class="ml-1.5">Eliminar</span>
          </Button>
        </div>
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
  CalendarDays,
  CalendarCheck,
  Clock,
  Copy,
  Edit2,
  Eye,
  ListTodo,
  Plus,
  Trash2,
} from 'lucide-vue-next';
import type { Hito } from '@modules/Proyectos/Resources/js/types/hitos';

// Props
const props = withDefaults(defineProps<{
  hito: Hito;
  canEdit?: boolean;
  canDelete?: boolean;
  canManageDeliverables?: boolean;
  showActions?: boolean;
  compact?: boolean;
}>(), {
  canEdit: false,
  canDelete: false,
  canManageDeliverables: false,
  showActions: true,
  compact: false,
});

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