<template>
  <UserLayout :title="'Calendario de Obligaciones'">
    <template #header>
      <h2 class="text-xl font-semibold">Calendario de Mis Obligaciones</h2>
    </template>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <Card>
        <CardHeader>
          <div class="flex justify-between items-center">
            <CardTitle>{{ nombreMes }} {{ año }}</CardTitle>
            <div class="flex gap-2">
              <Button size="sm" variant="outline" @click="mesAnterior">Anterior</Button>
              <Button size="sm" variant="outline" @click="mesSiguiente">Siguiente</Button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-7 gap-1">
            <div v-for="dia in ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']" :key="dia"
                 class="text-center text-sm font-medium p-2">
              {{ dia }}
            </div>
            <div v-for="dia in diasCalendario" :key="dia.fecha"
                 class="min-h-[80px] border rounded p-1"
                 :class="{ 'bg-gray-50': !dia.esMesActual }">
              <div class="text-xs text-gray-600">{{ dia.numero }}</div>
              <div v-if="obligaciones[dia.fecha]" class="mt-1 space-y-1">
                <Link v-for="ob in obligaciones[dia.fecha]" :key="ob.id"
                      :href="route('miembro.mis-obligaciones.show', ob.id)"
                      class="block text-xs p-1 rounded truncate"
                      :class="getObligacionClass(ob.estado)">
                  {{ ob.titulo }}
                </Link>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </UserLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import UserLayout from '@modules/Core/Resources/js/layouts/UserLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';

const props = defineProps<{
  obligaciones: Record<string, any[]>;
  mes: number;
  año: number;
}>();

const mes = ref(props.mes);
const año = ref(props.año);

const nombreMes = computed(() => {
  const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
  return meses[mes.value - 1];
});

const diasCalendario = computed(() => {
  const dias = [];
  const primerDia = new Date(año.value, mes.value - 1, 1);
  const ultimoDia = new Date(año.value, mes.value, 0);
  const diasMes = ultimoDia.getDate();
  const inicioSemana = primerDia.getDay();

  // Días del mes anterior
  for (let i = inicioSemana - 1; i >= 0; i--) {
    const fecha = new Date(año.value, mes.value - 1, -i);
    dias.push({
      numero: fecha.getDate(),
      fecha: formatearFecha(fecha),
      esMesActual: false
    });
  }

  // Días del mes actual
  for (let i = 1; i <= diasMes; i++) {
    const fecha = new Date(año.value, mes.value - 1, i);
    dias.push({
      numero: i,
      fecha: formatearFecha(fecha),
      esMesActual: true
    });
  }

  // Días del siguiente mes para completar la semana
  const diasRestantes = 42 - dias.length;
  for (let i = 1; i <= diasRestantes; i++) {
    const fecha = new Date(año.value, mes.value, i);
    dias.push({
      numero: i,
      fecha: formatearFecha(fecha),
      esMesActual: false
    });
  }

  return dias;
});

const formatearFecha = (fecha: Date): string => {
  const y = fecha.getFullYear();
  const m = String(fecha.getMonth() + 1).padStart(2, '0');
  const d = String(fecha.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
};

const mesAnterior = () => {
  if (mes.value === 1) {
    mes.value = 12;
    año.value--;
  } else {
    mes.value--;
  }
  recargar();
};

const mesSiguiente = () => {
  if (mes.value === 12) {
    mes.value = 1;
    año.value++;
  } else {
    mes.value++;
  }
  recargar();
};

const recargar = () => {
  router.get(route('miembro.mis-obligaciones.calendario'), {
    mes: mes.value,
    año: año.value
  }, { preserveState: true });
};

const getObligacionClass = (estado: string) => {
  const clases = {
    'pendiente': 'bg-gray-100 text-gray-700 hover:bg-gray-200',
    'en_progreso': 'bg-blue-100 text-blue-700 hover:bg-blue-200',
    'cumplida': 'bg-green-100 text-green-700 hover:bg-green-200',
    'vencida': 'bg-red-100 text-red-700 hover:bg-red-200',
    'cancelada': 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200'
  };
  return clases[estado] || 'bg-gray-100';
};
</script>