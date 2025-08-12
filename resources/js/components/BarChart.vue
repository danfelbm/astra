<script setup lang="ts">
import { ref, onMounted, watch, nextTick } from 'vue';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
  type ChartData,
  type ChartOptions
} from 'chart.js';
import { Bar } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

interface BarChartData {
  labels: string[];
  datasets: {
    label: string;
    data: number[];
    backgroundColor?: string | string[];
    borderColor?: string | string[];
    borderWidth?: number;
  }[];
}

interface Props {
  data: BarChartData;
  title?: string;
  height?: number;
  showPercentage?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  height: 400,
  showPercentage: false,
});

const chartRef = ref();

const chartData = ref<ChartData<'bar'>>({
  labels: [],
  datasets: []
});

const chartOptions = ref<ChartOptions<'bar'>>({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    title: {
      display: !!props.title,
      text: props.title,
      font: {
        size: 16,
        weight: 'bold'
      }
    },
    legend: {
      display: false // Ocultamos la leyenda por defecto para gráficos simples
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          const value = context.parsed.y;
          if (props.showPercentage) {
            return `${context.label}: ${value}%`;
          }
          return `${context.label}: ${value} votos`;
        }
      }
    }
  },
  scales: {
    x: {
      title: {
        display: true,
        text: 'Opciones'
      },
      ticks: {
        maxRotation: 45,
        minRotation: 0
      }
    },
    y: {
      beginAtZero: true,
      title: {
        display: true,
        text: props.showPercentage ? 'Porcentaje (%)' : 'Número de votos'
      },
      ticks: {
        callback: function(value) {
          if (props.showPercentage) {
            return value + '%';
          }
          return value;
        }
      }
    }
  },
  animation: {
    duration: 1000,
    easing: 'easeInOutQuart'
  }
});

// Función para actualizar los datos del gráfico
const updateChartData = () => {
  if (!props.data || !props.data.labels || !props.data.datasets) {
    return;
  }

  chartData.value = {
    labels: props.data.labels,
    datasets: props.data.datasets.map(dataset => ({
      ...dataset,
      backgroundColor: dataset.backgroundColor || [
        'rgba(54, 162, 235, 0.8)',
        'rgba(255, 99, 132, 0.8)',
        'rgba(255, 205, 86, 0.8)',
        'rgba(75, 192, 192, 0.8)',
        'rgba(153, 102, 255, 0.8)',
        'rgba(255, 159, 64, 0.8)',
        'rgba(199, 199, 199, 0.8)',
        'rgba(83, 102, 255, 0.8)',
        'rgba(255, 99, 255, 0.8)',
        'rgba(255, 159, 10, 0.8)'
      ].slice(0, props.data.labels.length),
      borderColor: dataset.borderColor || [
        'rgba(54, 162, 235, 1)',
        'rgba(255, 99, 132, 1)',
        'rgba(255, 205, 86, 1)',
        'rgba(75, 192, 192, 1)',
        'rgba(153, 102, 255, 1)',
        'rgba(255, 159, 64, 1)',
        'rgba(199, 199, 199, 1)',
        'rgba(83, 102, 255, 1)',
        'rgba(255, 99, 255, 1)',
        'rgba(255, 159, 10, 1)'
      ].slice(0, props.data.labels.length),
      borderWidth: dataset.borderWidth || 1
    }))
  };
};

// Función para obtener datos simples desde un array de respuestas
const getChartDataFromResponses = (respuestas: any[]) => {
  const labels = respuestas.map(r => r.opcion);
  const data = respuestas.map(r => props.showPercentage ? r.porcentaje : r.cantidad);
  
  return {
    labels,
    datasets: [{
      label: props.showPercentage ? 'Porcentaje' : 'Votos',
      data
    }]
  };
};

// Watchers para actualizar el gráfico cuando cambien las props
watch(() => props.data, () => {
  updateChartData();
}, { deep: true });

watch(() => props.showPercentage, () => {
  updateChartData();
});

// Inicializar el gráfico cuando se monte el componente
onMounted(() => {
  nextTick(() => {
    updateChartData();
  });
});

// Exponer función helper para uso externo
defineExpose({
  getChartDataFromResponses
});
</script>

<template>
  <div class="w-full" :style="{ height: `${height}px` }">
    <Bar
      ref="chartRef"
      :data="chartData"
      :options="chartOptions"
    />
  </div>
</template>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>