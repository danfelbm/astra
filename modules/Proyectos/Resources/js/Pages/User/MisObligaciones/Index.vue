<template>
  <UserLayout :title="'Mis Obligaciones'">
    <template #header>
      <h2 class="text-xl font-semibold">Mis Obligaciones</h2>
    </template>

    <div class="py-6">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <!-- Estadísticas -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <Card>
            <CardContent class="pt-6">
              <div class="text-2xl font-bold">{{ estadisticas.total }}</div>
              <p class="text-sm text-gray-600">Total Asignadas</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent class="pt-6">
              <div class="text-2xl font-bold text-yellow-600">{{ estadisticas.pendientes }}</div>
              <p class="text-sm text-gray-600">Pendientes</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent class="pt-6">
              <div class="text-2xl font-bold text-green-600">{{ estadisticas.cumplidas }}</div>
              <p class="text-sm text-gray-600">Cumplidas</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent class="pt-6">
              <div class="text-2xl font-bold text-red-600">{{ estadisticas.vencidas }}</div>
              <p class="text-sm text-gray-600">Vencidas</p>
            </CardContent>
          </Card>
        </div>

        <!-- Obligaciones críticas -->
        <Card v-if="obligacionesCriticas.length > 0" class="mb-6 border-red-200">
          <CardHeader class="bg-red-50">
            <CardTitle class="text-red-800">⚠️ Obligaciones Críticas</CardTitle>
          </CardHeader>
          <CardContent class="pt-4">
            <div class="space-y-2">
              <div v-for="ob in obligacionesCriticas" :key="ob.id" class="flex justify-between items-center p-2 bg-red-50 rounded">
                <div>
                  <Link :href="route('miembro.mis-obligaciones.show', ob.id)" class="font-medium hover:underline">
                    {{ ob.titulo }}
                  </Link>
                  <p class="text-sm text-gray-600">{{ ob.contrato?.nombre }}</p>
                </div>
                <Badge variant="destructive">
                  {{ ob.dias_restantes !== null && ob.dias_restantes >= 0 ? `${ob.dias_restantes} días` : 'Vencida' }}
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Listado principal -->
        <Card>
          <CardHeader>
            <div class="flex justify-between">
              <CardTitle>Mis Obligaciones</CardTitle>
              <Link :href="route('miembro.mis-obligaciones.calendario')">
                <Button variant="outline" size="sm">
                  <Calendar class="h-4 w-4 mr-1" />
                  Vista Calendario
                </Button>
              </Link>
            </div>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Obligación</TableHead>
                  <TableHead>Contrato/Proyecto</TableHead>
                  <TableHead>Estado</TableHead>
                  <TableHead>Vencimiento</TableHead>
                  <TableHead>Progreso</TableHead>
                  <TableHead>Acciones</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="obligacion in obligaciones.data" :key="obligacion.id">
                  <TableCell>
                    <Link :href="route('miembro.mis-obligaciones.show', obligacion.id)" class="font-medium hover:underline">
                      {{ obligacion.titulo }}
                    </Link>
                  </TableCell>
                  <TableCell>
                    <div class="text-sm">
                      <p>{{ obligacion.contrato?.nombre }}</p>
                      <p class="text-gray-500">{{ obligacion.contrato?.proyecto?.nombre }}</p>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge :variant="getEstadoVariant(obligacion.estado)">
                      {{ obligacion.estado_label }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <span :class="{ 'text-red-600': obligacion.esta_vencida }">
                      {{ formatDate(obligacion.fecha_vencimiento) }}
                    </span>
                  </TableCell>
                  <TableCell>
                    <div class="flex items-center gap-2">
                      <Progress :value="obligacion.porcentaje_cumplimiento" class="w-16" />
                      <span class="text-sm">{{ obligacion.porcentaje_cumplimiento }}%</span>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div class="flex gap-1">
                      <Button
                        v-if="obligacion.estado !== 'cumplida' && canComplete"
                        size="sm"
                        variant="ghost"
                        @click="actualizarProgreso(obligacion)"
                      >
                        <Edit class="h-4 w-4" />
                      </Button>
                      <Button
                        v-if="obligacion.estado !== 'cumplida' && canComplete"
                        size="sm"
                        variant="ghost"
                        @click="completarObligacion(obligacion)"
                      >
                        <Check class="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>
    </div>
  </UserLayout>
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import UserLayout from '@modules/Core/Resources/js/layouts/UserLayout.vue';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Progress } from '@modules/Core/Resources/js/components/ui/progress';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@modules/Core/Resources/js/components/ui/table';
import { Calendar, Edit, Check } from 'lucide-vue-next';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';
import { toast } from 'vue-sonner';

const props = defineProps<{
  obligaciones: any;
  estadisticas: any;
  obligacionesCriticas: any[];
  canComplete: boolean;
}>();

const formatDate = (date: string | undefined) => {
  if (!date) return 'Sin fecha';
  try {
    return format(parseISO(date), 'dd MMM yyyy', { locale: es });
  } catch {
    return date;
  }
};

const getEstadoVariant = (estado: string) => {
  const variants: Record<string, any> = {
    'pendiente': 'secondary',
    'en_progreso': 'default',
    'cumplida': 'success',
    'vencida': 'destructive',
    'cancelada': 'warning'
  };
  return variants[estado] || 'secondary';
};

const actualizarProgreso = (obligacion: any) => {
  const porcentaje = prompt('Ingrese el porcentaje de avance (0-100):', String(obligacion.porcentaje_cumplimiento));
  if (porcentaje !== null) {
    router.put(route('miembro.mis-obligaciones.actualizar-progreso', obligacion.id), {
      porcentaje_cumplimiento: parseInt(porcentaje)
    }, {
      onSuccess: () => toast.success('Progreso actualizado')
    });
  }
};

const completarObligacion = (obligacion: any) => {
  if (confirm('¿Marcar esta obligación como cumplida?')) {
    router.post(route('miembro.mis-obligaciones.completar', obligacion.id), {}, {
      onSuccess: () => toast.success('Obligación marcada como cumplida')
    });
  }
};
</script>