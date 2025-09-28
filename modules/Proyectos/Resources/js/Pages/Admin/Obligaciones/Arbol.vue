<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import ObligacionTree from '@modules/Proyectos/Resources/js/components/ObligacionTree.vue';
import { usePermissions } from '@modules/Core/Resources/js/composables/usePermissions';
import { toast } from 'vue-sonner';
import { ArrowLeft, Plus } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
  contrato: any;
  obligaciones: any[];
  estadisticas?: any;
  canCreate?: boolean;
  canEdit?: boolean;
  canDelete?: boolean;
  canComplete?: boolean;
}>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Admin', href: '/admin/dashboard' },
  { title: 'Contratos', href: '/admin/contratos' },
  { title: props.contrato.nombre, href: `/admin/contratos/${props.contrato.id}` },
  { title: 'Árbol de Obligaciones', href: '#' }
];

const { hasPermission } = usePermissions();

const eliminarObligacion = (obligacion: any) => {
  if (confirm(`¿Eliminar "${obligacion.titulo}" y todas sus obligaciones hijas?`)) {
    router.delete(route('admin.obligaciones.destroy', obligacion.id), {
      preserveScroll: true,
      onSuccess: () => toast.success('Obligación eliminada exitosamente')
    });
  }
};

const completarObligacion = (obligacion: any) => {
  router.post(route('admin.obligaciones.completar', obligacion.id), {}, {
    preserveScroll: true,
    onSuccess: () => toast.success('Obligación marcada como completada')
  });
};

const moverObligacion = (obligacion: any, parentId: number | null, orden: number) => {
  router.post(route('admin.obligaciones.mover', obligacion.id), {
    nuevo_parent_id: parentId,
    nuevo_orden: orden
  }, {
    preserveScroll: true,
    onSuccess: () => toast.success('Obligación movida exitosamente')
  });
};
</script>

<template>
  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link :href="route('admin.contratos.show', contrato.id)">
            <Button variant="ghost" size="sm">
              <ArrowLeft class="h-4 w-4 mr-2" />
              Volver al Contrato
            </Button>
          </Link>
          <div>
            <h1 class="text-3xl font-bold">Árbol de Obligaciones</h1>
            <p class="text-gray-600 mt-1">{{ contrato.nombre }}</p>
          </div>
        </div>
        <Link
          v-if="canCreate || hasPermission('obligaciones.create')"
          :href="route('admin.obligaciones.create', { contrato_id: contrato.id })"
        >
          <Button>
            <Plus class="h-4 w-4 mr-2" />
            Nueva Obligación
          </Button>
        </Link>
      </div>

      <!-- Estadísticas rápidas -->
      <div v-if="estadisticas" class="grid grid-cols-4 gap-4">
        <Card>
          <CardContent class="pt-6">
            <div class="text-2xl font-bold">{{ estadisticas.total || 0 }}</div>
            <p class="text-sm text-gray-600">Total</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6">
            <div class="text-2xl font-bold text-yellow-600">{{ estadisticas.pendientes || 0 }}</div>
            <p class="text-sm text-gray-600">Pendientes</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6">
            <div class="text-2xl font-bold text-green-600">{{ estadisticas.cumplidas || 0 }}</div>
            <p class="text-sm text-gray-600">Cumplidas</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6">
            <div class="text-2xl font-bold text-red-600">{{ estadisticas.vencidas || 0 }}</div>
            <p class="text-sm text-gray-600">Vencidas</p>
          </CardContent>
        </Card>
      </div>

      <!-- Árbol de obligaciones -->
      <Card class="flex-1">
        <CardHeader>
          <CardTitle>Vista Jerárquica</CardTitle>
          <CardDescription>Organiza y visualiza las obligaciones en forma de árbol</CardDescription>
        </CardHeader>
        <CardContent>
          <ObligacionTree
            v-if="obligaciones && obligaciones.length > 0"
            :obligaciones="obligaciones"
            :contratoId="contrato.id"
            :editable="canEdit || hasPermission('obligaciones.edit')"
            @create="() => router.visit(route('admin.obligaciones.create', { contrato_id: contrato.id }))"
            @edit="(o) => router.visit(route('admin.obligaciones.edit', o.id))"
            @delete="eliminarObligacion"
            @complete="completarObligacion"
            @move="moverObligacion"
          />
          <div v-else class="text-center py-8 text-gray-500">
            <p>No hay obligaciones para mostrar</p>
            <p class="text-sm mt-2">Crea la primera obligación para comenzar</p>
          </div>
        </CardContent>
      </Card>
    </div>
  </AdminLayout>
</template>