<template>
  <UserLayout :title="obligacion.titulo">
    <template #header>
      <h2 class="text-xl font-semibold">{{ obligacion.titulo }}</h2>
    </template>

    <div class="py-6 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Información de la Obligación</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div>
                  <Label>Descripción</Label>
                  <p>{{ obligacion.descripcion || 'Sin descripción' }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <Label>Estado</Label>
                    <Badge :variant="getEstadoVariant(obligacion.estado)">{{ obligacion.estado_label }}</Badge>
                  </div>
                  <div>
                    <Label>Prioridad</Label>
                    <Badge>{{ obligacion.prioridad_label }}</Badge>
                  </div>
                  <div>
                    <Label>Vencimiento</Label>
                    <p>{{ formatDate(obligacion.fecha_vencimiento) }}</p>
                  </div>
                  <div>
                    <Label>Progreso</Label>
                    <Progress :value="obligacion.porcentaje_cumplimiento" />
                  </div>
                </div>
              </div>
              <div v-if="canComplete && obligacion.estado !== 'cumplida'" class="mt-6 flex gap-2">
                <Button @click="actualizarProgreso">Actualizar Progreso</Button>
                <Button variant="success" @click="completarObligacion">Marcar como Cumplida</Button>
              </div>
            </CardContent>
          </Card>
        </div>
        <div>
          <Card>
            <CardHeader>
              <CardTitle>Contrato</CardTitle>
            </CardHeader>
            <CardContent>
              <p class="font-medium">{{ contrato.nombre }}</p>
              <p class="text-sm text-gray-600">{{ contrato.proyecto?.nombre }}</p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </UserLayout>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import UserLayout from '@modules/Core/Resources/js/layouts/UserLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Progress } from '@modules/Core/Resources/js/components/ui/progress';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';
import { toast } from 'vue-sonner';

const props = defineProps<{
  obligacion: any;
  contrato: any;
  canComplete: boolean;
}>();

const formatDate = (date: string) => {
  if (!date) return 'Sin fecha';
  return format(parseISO(date), 'dd MMM yyyy', { locale: es });
};

const getEstadoVariant = (estado: string) => {
  const variants = {
    'cumplida': 'success',
    'vencida': 'destructive',
    'en_progreso': 'default'
  };
  return variants[estado] || 'secondary';
};

const actualizarProgreso = () => {
  const porcentaje = prompt('Porcentaje de avance (0-100):', String(props.obligacion.porcentaje_cumplimiento));
  if (porcentaje) {
    router.put(route('miembro.mis-obligaciones.actualizar-progreso', props.obligacion.id), {
      porcentaje_cumplimiento: parseInt(porcentaje)
    }, {
      preserveScroll: true
    });
  }
};

const completarObligacion = () => {
  if (confirm('¿Marcar como cumplida?')) {
    router.post(route('miembro.mis-obligaciones.completar', props.obligacion.id), {}, {
      preserveScroll: true
    });
  }
};
</script>