<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { ArrowLeft, Calendar, Clock, CheckCircle, AlertCircle, Circle, XCircle } from 'lucide-vue-next';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';

interface TimelineItem {
  id: number;
  titulo: string;
  descripcion?: string;
  fecha_vencimiento: string;
  estado: string;
  prioridad: string;
  responsable?: any;
  nivel: number;
  porcentaje_cumplimiento: number;
  cumplido_at?: string;
  cumplido_por?: any;
}

interface Props {
  contrato: any;
  timeline: TimelineItem[];
}

const props = defineProps<Props>();

// Breadcrumbs para navegación
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Admin', href: '/admin/dashboard' },
  { title: 'Contratos', href: '/admin/contratos' },
  { title: props.contrato.nombre, href: `/admin/contratos/${props.contrato.id}` },
  { title: 'Timeline', href: '#' },
];

// Agrupar obligaciones por nivel jerárquico
const timelineGrouped = computed(() => {
  // Simplemente devolver todas las obligaciones como una lista plana
  return [['Todas las Obligaciones', props.timeline]];
});

// Utilidades
const formatDate = (dateString: string | undefined) => {
  if (!dateString) return 'Sin fecha';
  try {
    return format(parseISO(dateString), 'dd MMMM yyyy', { locale: es });
  } catch {
    return dateString;
  }
};

// Funciones de estados y prioridades eliminadas - ya no se usan
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
            <h1 class="text-3xl font-bold">Timeline de Obligaciones</h1>
            <p class="text-gray-600 mt-1">{{ contrato.nombre }}</p>
          </div>
        </div>
      </div>

      <!-- Timeline -->
      <Card class="flex-1">
        <CardHeader>
          <CardTitle>Cronología de Vencimientos</CardTitle>
          <CardDescription>Vista temporal de todas las obligaciones del contrato</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="timelineGrouped.length > 0" class="relative">
            <!-- Línea vertical del timeline -->
            <div class="absolute left-9 top-0 bottom-0 w-0.5 bg-gray-200"></div>

            <!-- Items del timeline -->
            <div v-for="([fecha, items], index) in timelineGrouped" :key="index" class="mb-8">
              <!-- Fecha -->
              <div class="flex items-center mb-4">
                <div class="bg-white z-10 pr-4">
                  <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                      <Calendar class="h-5 w-5 text-gray-600" />
                    </div>
                    <h3 class="text-lg font-semibold">{{ fecha }}</h3>
                  </div>
                </div>
              </div>

              <!-- Obligaciones de esa fecha -->
              <div class="ml-16 space-y-4">
                <div
                  v-for="item in items"
                  :key="item.id"
                  class="border rounded-lg p-4 hover:shadow-md transition-shadow"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex items-start gap-3 flex-1">
                      <Circle class="h-5 w-5 mt-0.5 text-gray-400" />
                      <div class="flex-1">
                        <Link
                          :href="route('admin.obligaciones.show', item.id)"
                          class="font-medium hover:underline"
                        >
                          {{ item.titulo }}
                        </Link>
                        <p v-if="item.descripcion" class="text-sm text-gray-600 mt-1">
                          {{ item.descripcion }}
                        </p>
                        <span v-if="item.nivel > 0" class="text-xs text-gray-500 mt-2 block">
                          Nivel {{ item.nivel }}
                        </span>
                      </div>
                    </div>
                    <Link :href="route('admin.obligaciones.show', item.id)">
                      <Button variant="ghost" size="sm">Ver</Button>
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Mensaje cuando no hay obligaciones -->
          <div v-else class="text-center py-12 text-gray-500">
            <AlertCircle class="h-12 w-12 mx-auto mb-4 text-gray-300" />
            <p>No hay obligaciones para mostrar en el timeline</p>
            <Link :href="route('admin.obligaciones.create', { contrato_id: contrato.id })" class="mt-4 inline-block">
              <Button>Crear Primera Obligación</Button>
            </Link>
          </div>
        </CardContent>
      </Card>
    </div>
  </AdminLayout>
</template>