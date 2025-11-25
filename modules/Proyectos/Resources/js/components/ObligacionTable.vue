<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@modules/Core/Resources/js/components/ui/table';
import { Eye, Pencil, Trash2, AlertCircle, CornerDownRight } from 'lucide-vue-next';
import { usePermissions } from '@modules/Core/Resources/js/composables/usePermissions';
import type { ObligacionContrato } from '@modules/Proyectos/Resources/js/types/obligaciones';

// Props
interface Props {
  /** Lista de obligaciones a mostrar */
  obligaciones: ObligacionContrato[];
  /** Si el usuario puede editar */
  canEdit?: boolean;
  /** Si el usuario puede eliminar */
  canDelete?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  canEdit: false,
  canDelete: false
});

const emit = defineEmits<{
  'view': [obligacion: ObligacionContrato];
  'edit': [obligacion: ObligacionContrato];
  'delete': [obligacion: ObligacionContrato];
}>();

const { hasPermission } = usePermissions();
</script>

<template>
  <div>
    <!-- Tabla de obligaciones -->
    <div v-if="obligaciones && obligaciones.length > 0">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Título</TableHead>
            <TableHead>Descripción</TableHead>
            <TableHead>Archivos</TableHead>
            <TableHead class="text-right">Acciones</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow
            v-for="obligacion in obligaciones"
            :key="obligacion.id"
          >
            <TableCell class="font-medium">
              <div class="flex items-center">
                <!-- Indicador visual de jerarquía -->
                <span v-if="obligacion.nivel > 0" class="flex items-center text-muted-foreground mr-2" :style="{ paddingLeft: `${(obligacion.nivel - 1) * 12}px` }">
                  <CornerDownRight class="h-4 w-4" />
                </span>
                <div>
                  <Link
                    :href="`/admin/obligaciones/${obligacion.id}`"
                    class="hover:text-blue-600 hover:underline"
                  >
                    {{ obligacion.titulo }}
                  </Link>
                  <Badge v-if="obligacion.padre" variant="secondary" class="ml-2 text-xs">
                    Hija
                  </Badge>
                  <Badge v-if="obligacion.tiene_hijos" variant="outline" class="ml-1 text-xs">
                    {{ obligacion.total_hijos }} hijos
                  </Badge>
                </div>
              </div>
            </TableCell>
            <TableCell>
              <span class="text-sm text-gray-600">
                {{ obligacion.descripcion ?
                    (obligacion.descripcion.length > 100 ?
                     obligacion.descripcion.substring(0, 100) + '...' :
                     obligacion.descripcion) :
                    'Sin descripción' }}
              </span>
            </TableCell>
            <TableCell>
              <span v-if="obligacion.archivos_adjuntos?.length" class="text-sm">
                {{ obligacion.archivos_adjuntos.length }} archivo(s)
              </span>
              <span v-else class="text-sm text-gray-400">Sin archivos</span>
            </TableCell>
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1">
                <!-- Botón Ver -->
                <Button
                  variant="ghost"
                  size="sm"
                  class="h-8 px-2"
                  @click="emit('view', obligacion)"
                >
                  <Eye class="h-4 w-4 mr-1" />
                  Ver
                </Button>
                <!-- Botón Editar -->
                <Button
                  v-if="canEdit || hasPermission('obligaciones.edit')"
                  variant="ghost"
                  size="sm"
                  class="h-8 px-2"
                  @click="emit('edit', obligacion)"
                >
                  <Pencil class="h-4 w-4 mr-1" />
                  Editar
                </Button>
                <!-- Botón Eliminar -->
                <Button
                  v-if="canDelete || hasPermission('obligaciones.delete')"
                  variant="ghost"
                  size="sm"
                  class="h-8 px-2 text-red-600 hover:text-red-700 hover:bg-red-50"
                  @click="emit('delete', obligacion)"
                >
                  <Trash2 class="h-4 w-4 mr-1" />
                  Eliminar
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- Estado vacío -->
    <div v-else class="text-center py-8 text-gray-500">
      <AlertCircle class="h-12 w-12 mx-auto mb-4 text-gray-300" />
      <p>No se encontraron obligaciones</p>
      <p class="text-sm mt-1">Crea una nueva obligación para comenzar</p>
    </div>
  </div>
</template>
