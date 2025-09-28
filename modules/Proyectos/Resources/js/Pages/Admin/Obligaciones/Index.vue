<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@modules/Core/Resources/js/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@modules/Core/Resources/js/components/ui/table';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from '@modules/Core/Resources/js/components/ui/dropdown-menu';
import ObligacionTree from '@modules/Proyectos/Resources/js/components/ObligacionTree.vue';
import Pagination from '@modules/Core/Resources/js/components/ui/pagination/Pagination.vue';
import {
  Plus,
  Download,
  Search,
  AlertCircle,
  Clock,
  TreePine,
  List,
  MoreVertical,
  Eye,
  Pencil,
  Check,
  Trash2
} from 'lucide-vue-next';
import { usePermissions } from '@modules/Core/Resources/js/composables/usePermissions';
import { useObligaciones } from '@modules/Proyectos/Resources/js/composables/useObligaciones';
import { toast } from 'vue-sonner';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';
import { debounce } from 'lodash';
import type { ObligacionContrato, ObligacionEstadisticas } from '@modules/Proyectos/Resources/js/types/obligaciones';

// Props
interface Props {
  obligaciones: {
    data: ObligacionContrato[];
    links?: any;
    meta?: any;
  };
  estadisticas: ObligacionEstadisticas;
  filters: any;
  contrato?: any;
  estadisticasResponsables?: any;
  canCreate?: boolean;
  canEdit?: boolean;
  canDelete?: boolean;
  canComplete?: boolean;
  canExport?: boolean;
}

const props = defineProps<Props>();

// Breadcrumbs para navegación
const breadcrumbs: BreadcrumbItem[] = props.contrato
  ? [
      { title: 'Admin', href: '/admin/dashboard' },
      { title: 'Contratos', href: '/admin/contratos' },
      { title: props.contrato.nombre, href: `/admin/contratos/${props.contrato.id}` },
      { title: 'Obligaciones', href: '#' },
    ]
  : [
      { title: 'Admin', href: '/admin/dashboard' },
      { title: 'Obligaciones', href: '/admin/obligaciones' },
    ];

const { hasPermission } = usePermissions();

// Estado
const vistaActual = ref<'arbol' | 'tabla'>('tabla');
const filters = ref({
  search: props.filters?.search || '',
  contrato_id: props.filters?.contrato_id || props.contrato?.id || ''
});

// Métodos
const aplicarFiltros = () => {
  router.get(route('admin.obligaciones.index'), filters.value, {
    preserveState: true,
    preserveScroll: true
  });
};

const debouncedSearch = debounce(() => {
  aplicarFiltros();
}, 500);

const toggleFiltroVencidas = () => {
  filters.value.vencidas = !filters.value.vencidas;
  filters.value.proximas_vencer = false;
  aplicarFiltros();
};

const toggleFiltroProximas = () => {
  filters.value.proximas_vencer = !filters.value.proximas_vencer;
  filters.value.vencidas = false;
  aplicarFiltros();
};

const crearObligacion = () => {
  router.visit(route('admin.obligaciones.create', { contrato_id: props.contrato?.id }));
};

const verObligacion = (obligacion: ObligacionContrato) => {
  router.visit(route('admin.obligaciones.show', obligacion.id));
};

const editarObligacion = (obligacion: ObligacionContrato) => {
  router.visit(route('admin.obligaciones.edit', obligacion.id));
};

const eliminarObligacion = (obligacion: ObligacionContrato) => {
  const mensaje = obligacion.tiene_hijos
    ? `¿Estás seguro de eliminar "${obligacion.titulo}" y todas sus obligaciones hijas?`
    : `¿Estás seguro de eliminar "${obligacion.titulo}"?`;

  if (confirm(mensaje)) {
    router.delete(route('admin.obligaciones.destroy', obligacion.id), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Obligación eliminada exitosamente');
      }
    });
  }
};

const completarObligacion = (obligacion: ObligacionContrato) => {
  router.post(route('admin.obligaciones.completar', obligacion.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Obligación marcada como cumplida');
    }
  });
};

const agregarHijo = (padre: ObligacionContrato) => {
  router.visit(route('admin.obligaciones.create', {
    contrato_id: padre.contrato_id,
    parent_id: padre.id
  }));
};

const moverObligacion = (obligacion: ObligacionContrato, newParentId: number | null, newOrder: number) => {
  router.post(route('admin.obligaciones.mover', obligacion.id), {
    nuevo_parent_id: newParentId,
    nuevo_orden: newOrder
  }, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Obligación movida exitosamente');
    }
  });
};

const exportarObligaciones = () => {
  window.open(route('admin.obligaciones.exportar', filters.value), '_blank');
};

const formatDate = (dateString: string | undefined) => {
  if (!dateString) return 'Sin fecha';
  try {
    return format(parseISO(dateString), 'dd MMM yyyy', { locale: es });
  } catch {
    return dateString;
  }
};

// Funciones de variantes eliminadas - ya no se usan estados ni prioridades
</script>

<template>
  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">
            Obligaciones
            <span v-if="contrato" class="text-sm text-gray-600">
              - {{ contrato.nombre }}
            </span>
          </h1>
          <p class="text-gray-600 mt-1">Gestiona las obligaciones de los contratos</p>
        </div>
        <div class="flex items-center gap-2">
          <Button
            v-if="canExport || hasPermission('obligaciones.export')"
            variant="outline"
            size="sm"
            @click="exportarObligaciones"
          >
            <Download class="h-4 w-4 mr-2" />
            Exportar
          </Button>
          <Link
            v-if="canCreate || hasPermission('obligaciones.create')"
            :href="route('admin.obligaciones.create', { contrato_id: contrato?.id })"
          >
            <Button>
              <Plus class="h-4 w-4 mr-2" />
              Nueva Obligación
            </Button>
          </Link>
        </div>
      </div>

      <!-- Filtros -->
      <Card>
        <CardContent class="pt-6">
          <!-- Búsqueda -->
          <div>
            <Label for="search">Buscar</Label>
            <div class="relative">
              <Search class="absolute left-2 top-2.5 h-4 w-4 text-gray-400" />
              <Input
                id="search"
                v-model="filters.search"
                type="text"
                placeholder="Buscar obligaciones por título o descripción..."
                class="pl-8"
                @input="debouncedSearch"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Estadística simplificada -->
      <Card>
        <CardContent class="pt-6">
          <div class="text-2xl font-bold">{{ obligaciones?.data?.length || 0 }}</div>
          <p class="text-sm text-gray-600">Total Obligaciones</p>
        </CardContent>
      </Card>

      <!-- Vista de árbol o tabla -->
      <Card class="flex-1">
        <CardHeader>
          <div class="flex items-center justify-between">
            <CardTitle>Lista de Obligaciones</CardTitle>
            <div class="flex items-center gap-2">
              <Button
                variant="ghost"
                size="sm"
                :class="{ 'bg-gray-100': vistaActual === 'arbol' }"
                @click="vistaActual = 'arbol'"
              >
                <TreePine class="h-4 w-4 mr-1" />
                Árbol
              </Button>
              <Button
                variant="ghost"
                size="sm"
                :class="{ 'bg-gray-100': vistaActual === 'tabla' }"
                @click="vistaActual = 'tabla'"
              >
                <List class="h-4 w-4 mr-1" />
                Tabla
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <!-- Vista de árbol -->
          <div v-if="vistaActual === 'arbol' && obligaciones.data && obligaciones.data.length > 0">
            <ObligacionTree
              :obligaciones="obligaciones.data"
              :contratoId="contrato?.id"
              :editable="true"
              @create="crearObligacion"
              @edit="editarObligacion"
              @delete="eliminarObligacion"
              @complete="completarObligacion"
              @add-child="agregarHijo"
              @move="moverObligacion"
            />
          </div>

          <!-- Vista de tabla -->
          <div v-else-if="vistaActual === 'tabla' && obligaciones.data && obligaciones.data.length > 0">
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
                  v-for="obligacion in obligaciones.data"
                  :key="obligacion.id"
                >
                  <TableCell class="font-medium">
                    <div>
                      <span>{{ obligacion.titulo }}</span>
                      <Badge v-if="obligacion.tiene_hijos" variant="outline" class="ml-2 text-xs">
                        {{ obligacion.total_hijos }} hijos
                      </Badge>
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
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon" class="h-8 w-8">
                          <MoreVertical class="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem @click="verObligacion(obligacion)">
                          <Eye class="h-4 w-4 mr-2" />
                          Ver
                        </DropdownMenuItem>
                        <DropdownMenuItem
                          v-if="canEdit || hasPermission('obligaciones.edit')"
                          @click="editarObligacion(obligacion)"
                        >
                          <Pencil class="h-4 w-4 mr-2" />
                          Editar
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                          v-if="canDelete || hasPermission('obligaciones.delete')"
                          class="text-red-600"
                          @click="eliminarObligacion(obligacion)"
                        >
                          <Trash2 class="h-4 w-4 mr-2" />
                          Eliminar
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>

            <!-- Paginación -->
            <div v-if="obligaciones.links" class="mt-4 flex justify-center">
              <Pagination
                :links="obligaciones.links"
                :meta="obligaciones.meta"
              />
            </div>
          </div>

          <!-- Mensaje cuando no hay obligaciones -->
          <div v-else class="text-center py-8 text-gray-500">
            <AlertCircle class="h-12 w-12 mx-auto mb-4 text-gray-300" />
            <p>No se encontraron obligaciones</p>
            <p class="text-sm mt-1">Crea una nueva obligación para comenzar</p>
          </div>
        </CardContent>
      </Card>
    </div>
  </AdminLayout>
</template>