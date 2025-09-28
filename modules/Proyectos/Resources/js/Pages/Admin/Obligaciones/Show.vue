<template>
  <AdminLayout :title="obligacion.titulo">
    <template #header>
      <div class="flex justify-between">
        <h2 class="text-xl font-semibold">{{ obligacion.titulo }}</h2>
        <div class="flex gap-2">
          <Link v-if="hasPermission('obligaciones.edit')" :href="route('admin.obligaciones.edit', obligacion.id)">
            <Button variant="outline"><Pencil class="h-4 w-4 mr-1" /> Editar</Button>
          </Link>
        </div>
      </div>
    </template>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Información General</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div>
                <Label>Descripción</Label>
                <p class="mt-1">{{ obligacion.descripcion || 'Sin descripción' }}</p>
              </div>
            </CardContent>
          </Card>

          <Card v-if="obligacion.hijos?.length">
            <CardHeader>
              <CardTitle>Obligaciones Hijas</CardTitle>
            </CardHeader>
            <CardContent>
              <ObligacionTree
                :obligaciones="obligacion.hijos"
                :contratoId="obligacion.contrato_id"
                :editable="hasPermission('obligaciones.edit')"
                @edit="(o) => router.visit(route('admin.obligaciones.edit', o.id))"
              />
            </CardContent>
          </Card>
        </div>

        <div class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Detalles del Contrato</CardTitle>
            </CardHeader>
            <CardContent>
              <Link :href="route('admin.contratos.show', contrato.id)" class="hover:underline">
                <h3 class="font-medium">{{ contrato.nombre }}</h3>
              </Link>
              <p class="text-sm text-gray-600 mt-1">{{ contrato.proyecto?.nombre }}</p>
            </CardContent>
          </Card>

          <Card v-if="obligacion.archivos_adjuntos?.length">
            <CardHeader>
              <CardTitle>Archivos Adjuntos</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-2">
                <div v-for="archivo in obligacion.archivos_adjuntos" :key="archivo.ruta" class="flex items-center gap-2">
                  <Paperclip class="h-4 w-4" />
                  <a :href="archivo.ruta" target="_blank" class="hover:underline text-sm">
                    {{ archivo.nombre_original }}
                  </a>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Auditoría</CardTitle>
            </CardHeader>
            <CardContent class="text-sm space-y-2">
              <div>
                <span class="text-gray-600">Creado por:</span>
                <p>{{ obligacion.creador?.name || 'Sistema' }}</p>
                <p class="text-xs text-gray-500">{{ formatDate(obligacion.created_at) }}</p>
              </div>
              <div v-if="obligacion.updated_at">
                <span class="text-gray-600">Última actualización:</span>
                <p>{{ obligacion.actualizador?.name || 'Sistema' }}</p>
                <p class="text-xs text-gray-500">{{ formatDate(obligacion.updated_at) }}</p>
              </div>
              <div v-if="obligacion.cumplido_at">
                <span class="text-gray-600">Cumplido por:</span>
                <p>{{ obligacion.cumplido_por_usuario?.name || 'Sistema' }}</p>
                <p class="text-xs text-gray-500">{{ formatDate(obligacion.cumplido_at) }}</p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Progress } from '@modules/Core/Resources/js/components/ui/progress';
import ObligacionTree from '@modules/Proyectos/Resources/js/components/ObligacionTree.vue';
import { Pencil, User, Paperclip } from 'lucide-vue-next';
import { usePermissions } from '@modules/Core/Resources/js/composables/usePermissions';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';

const props = defineProps<{
  obligacion: any;
  contrato: any;
}>();

const { hasPermission } = usePermissions();

const formatDate = (dateString: string | undefined) => {
  if (!dateString) return 'Sin fecha';
  try {
    return format(parseISO(dateString), 'dd MMM yyyy HH:mm', { locale: es });
  } catch {
    return dateString;
  }
};

// Funciones de variantes eliminadas - ya no se usan estados ni prioridades
</script>