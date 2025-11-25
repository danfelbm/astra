<script setup lang="ts">
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import ObligacionTree from '@modules/Proyectos/Resources/js/components/ObligacionTree.vue';
import ObligacionTable from '@modules/Proyectos/Resources/js/components/ObligacionTable.vue';
import ObligacionFilters from '@modules/Proyectos/Resources/js/components/ObligacionFilters.vue';
import Pagination from '@modules/Core/Resources/js/components/ui/pagination/Pagination.vue';
import { Plus, Download, TreePine, List } from 'lucide-vue-next';
import { usePermissions } from '@modules/Core/Resources/js/composables/usePermissions';
import { toast } from 'vue-sonner';
import { debounce } from 'lodash';
import type { ObligacionContrato, ObligacionEstadisticas } from '@modules/Proyectos/Resources/js/types/obligaciones';

// Props
interface Contrato {
  id: number;
  nombre: string;
}

interface Props {
  obligaciones: {
    data: ObligacionContrato[];
    links?: any;
    meta?: any;
  };
  estadisticas: ObligacionEstadisticas;
  filters: any;
  contrato?: Contrato;
  contratos?: Contrato[];
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
  contrato_id: props.filters?.contrato_id || props.contrato?.id || null
});

// Métodos
const aplicarFiltros = debounce(() => {
  router.get(route('admin.obligaciones.index'), filters.value, {
    preserveState: true,
    preserveScroll: true
  });
}, 300);

const limpiarFiltros = () => {
  filters.value = { search: '', contrato_id: props.contrato?.id || null };
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
      <ObligacionFilters
        v-model="filters"
        :contratos="contratos || []"
        :show-contrato-filter="!contrato"
        @filter="aplicarFiltros"
        @clear="limpiarFiltros"
      />

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
          <div v-else-if="vistaActual === 'tabla'">
            <ObligacionTable
              :obligaciones="obligaciones.data || []"
              :can-edit="canEdit"
              :can-delete="canDelete"
              @view="verObligacion"
              @edit="editarObligacion"
              @delete="eliminarObligacion"
            />

            <!-- Paginación -->
            <div v-if="obligaciones.links && obligaciones.data?.length" class="mt-4 flex justify-center">
              <Pagination
                :links="obligaciones.links"
                :meta="obligaciones.meta"
              />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AdminLayout>
</template>