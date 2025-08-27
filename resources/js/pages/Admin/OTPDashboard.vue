<script setup lang="ts">
import AdminLayout from "@/layouts/AdminLayout.vue";
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { 
  Mail, 
  MessageSquare, 
  Clock, 
  AlertTriangle, 
  CheckCircle, 
  XCircle,
  TrendingUp,
  RefreshCw,
  Users,
  Timer
} from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import type { BreadcrumbItem } from '@/types';

// Props del backend
interface QueueMetrics {
  queue_name: string;
  pending_jobs: number;
  processing_jobs: number;
  failed_jobs: number;
  rate_limit: number;
  estimated_wait_seconds: number;
}

interface OTPStats {
  total_activos: number;
  total_expirados: number;
  total_usados: number;
  por_canal: {
    email: number;
    whatsapp: number;
    both: number;
  };
}

interface Props {
  initialQueueMetrics?: QueueMetrics[];
  initialOtpStats?: OTPStats;
}

const props = withDefaults(defineProps<Props>(), {
  initialQueueMetrics: () => [],
  initialOtpStats: () => ({
    total_activos: 0,
    total_expirados: 0,
    total_usados: 0,
    por_canal: { email: 0, whatsapp: 0, both: 0 }
  })
});

// Estado reactivo
const queueMetrics = ref<QueueMetrics[]>(props.initialQueueMetrics);
const otpStats = ref<OTPStats>(props.initialOtpStats);
const isLoading = ref(false);
const lastUpdated = ref<Date>(new Date());
const autoRefreshInterval = ref<number | null>(null);

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { label: 'Admin', href: route('admin.dashboard') },
  { label: 'Dashboard OTP', href: route('admin.otp-dashboard') }
];

// Computed
const emailQueue = computed(() => 
  queueMetrics.value.find(q => q.queue_name === 'otp-emails')
);

const whatsappQueue = computed(() => 
  queueMetrics.value.find(q => q.queue_name === 'otp-whatsapp')
);

const totalPendingJobs = computed(() => 
  queueMetrics.value.reduce((sum, q) => sum + q.pending_jobs, 0)
);

const totalProcessingJobs = computed(() => 
  queueMetrics.value.reduce((sum, q) => sum + q.processing_jobs, 0)
);

const totalFailedJobs = computed(() => 
  queueMetrics.value.reduce((sum, q) => sum + q.failed_jobs, 0)
);

const systemStatus = computed(() => {
  if (totalFailedJobs.value > 10) return { status: 'error', label: 'Crítico', color: 'destructive' };
  if (totalPendingJobs.value > 50) return { status: 'warning', label: 'Sobrecargado', color: 'warning' };
  if (totalProcessingJobs.value > 0) return { status: 'processing', label: 'Procesando', color: 'default' };
  return { status: 'healthy', label: 'Saludable', color: 'success' };
});

// Métodos
const refreshData = async () => {
  isLoading.value = true;
  
  try {
    // Obtener métricas de colas
    const queueResponse = await axios.get('/api/queue/status');
    if (queueResponse.data.success) {
      queueMetrics.value = queueResponse.data.data;
    }

    // Obtener estadísticas de OTP
    const otpResponse = await axios.get('/api/queue/otp/stats');
    if (otpResponse.data.success) {
      otpStats.value = otpResponse.data.data;
    }

    lastUpdated.value = new Date();
  } catch (error) {
    console.error('Error actualizando datos:', error);
  } finally {
    isLoading.value = false;
  }
};

const startAutoRefresh = () => {
  // Actualizar cada 10 segundos
  autoRefreshInterval.value = window.setInterval(refreshData, 10000);
};

const stopAutoRefresh = () => {
  if (autoRefreshInterval.value) {
    clearInterval(autoRefreshInterval.value);
    autoRefreshInterval.value = null;
  }
};

const formatWaitTime = (seconds: number): string => {
  if (seconds < 60) return `${seconds}s`;
  if (seconds < 3600) return `${Math.round(seconds / 60)}m`;
  return `${Math.round(seconds / 3600)}h`;
};

const getQueueHealthColor = (queue: QueueMetrics) => {
  if (queue.failed_jobs > 5) return 'destructive';
  if (queue.pending_jobs > 25) return 'warning';
  return 'default';
};

// Lifecycle
onMounted(() => {
  startAutoRefresh();
});

onUnmounted(() => {
  stopAutoRefresh();
});
</script>

<template>
  <AdminLayout :breadcrumbs="breadcrumbs">
    <Head title="Dashboard OTP" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Dashboard OTP</h1>
          <p class="text-muted-foreground">
            Monitoreo en tiempo real del sistema de colas OTP
          </p>
        </div>
        
        <div class="flex items-center gap-4">
          <div class="text-sm text-muted-foreground">
            Última actualización: {{ lastUpdated.toLocaleTimeString() }}
          </div>
          <Button 
            @click="refreshData" 
            variant="outline" 
            size="sm"
            :disabled="isLoading"
          >
            <RefreshCw :class="{ 'animate-spin': isLoading }" class="h-4 w-4 mr-2" />
            Actualizar
          </Button>
        </div>
      </div>

      <!-- Estado del Sistema -->
      <Alert :variant="systemStatus.status === 'error' ? 'destructive' : 'default'">
        <CheckCircle v-if="systemStatus.status === 'healthy'" class="h-4 w-4" />
        <Clock v-else-if="systemStatus.status === 'processing'" class="h-4 w-4" />
        <AlertTriangle v-else class="h-4 w-4" />
        <AlertTitle>Estado del Sistema: {{ systemStatus.label }}</AlertTitle>
        <AlertDescription>
          <span v-if="systemStatus.status === 'healthy'">
            Todas las colas funcionan normalmente
          </span>
          <span v-else-if="systemStatus.status === 'processing'">
            {{ totalProcessingJobs }} trabajos en proceso, {{ totalPendingJobs }} en cola
          </span>
          <span v-else-if="systemStatus.status === 'warning'">
            Cola sobrecargada: {{ totalPendingJobs }} trabajos pendientes
          </span>
          <span v-else>
            Sistema crítico: {{ totalFailedJobs }} trabajos fallidos requieren atención
          </span>
        </AlertDescription>
      </Alert>

      <!-- Métricas Generales -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">OTPs Activos</CardTitle>
            <Users class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ otpStats.total_activos }}</div>
            <p class="text-xs text-muted-foreground">
              Códigos válidos pendientes
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">En Cola</CardTitle>
            <Clock class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ totalPendingJobs }}</div>
            <p class="text-xs text-muted-foreground">
              Envíos pendientes
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Procesando</CardTitle>
            <TrendingUp class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ totalProcessingJobs }}</div>
            <p class="text-xs text-muted-foreground">
              Envíos en curso
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Fallidos</CardTitle>
            <XCircle class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-destructive">{{ totalFailedJobs }}</div>
            <p class="text-xs text-muted-foreground">
              Requieren atención
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Estado de Colas Dedicadas -->
      <div class="grid gap-6 md:grid-cols-2">
        <!-- Cola Email -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Mail class="h-5 w-5" />
              Cola Email OTP
              <Badge :variant="emailQueue ? getQueueHealthColor(emailQueue) : 'secondary'">
                {{ emailQueue ? `${emailQueue.rate_limit}/seg` : 'N/A' }}
              </Badge>
            </CardTitle>
          </CardHeader>
          <CardContent v-if="emailQueue" class="space-y-4">
            <div class="grid gap-4 grid-cols-3">
              <div class="text-center">
                <div class="text-2xl font-bold">{{ emailQueue.pending_jobs }}</div>
                <div class="text-xs text-muted-foreground">En cola</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold">{{ emailQueue.processing_jobs }}</div>
                <div class="text-xs text-muted-foreground">Procesando</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-destructive">{{ emailQueue.failed_jobs }}</div>
                <div class="text-xs text-muted-foreground">Fallidos</div>
              </div>
            </div>
            
            <div v-if="emailQueue.pending_jobs > 0" class="space-y-2">
              <div class="flex justify-between text-sm">
                <span>Tiempo estimado de espera</span>
                <span class="font-medium">{{ formatWaitTime(emailQueue.estimated_wait_seconds) }}</span>
              </div>
              <Progress 
                :value="Math.min(100, (emailQueue.processing_jobs / (emailQueue.pending_jobs + emailQueue.processing_jobs)) * 100)" 
                class="h-2" 
              />
            </div>
          </CardContent>
          <CardContent v-else>
            <p class="text-muted-foreground text-center py-4">
              No hay datos disponibles para la cola de emails
            </p>
          </CardContent>
        </Card>

        <!-- Cola WhatsApp -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <MessageSquare class="h-5 w-5" />
              Cola WhatsApp OTP
              <Badge :variant="whatsappQueue ? getQueueHealthColor(whatsappQueue) : 'secondary'">
                {{ whatsappQueue ? `${whatsappQueue.rate_limit}/seg` : 'N/A' }}
              </Badge>
            </CardTitle>
          </CardHeader>
          <CardContent v-if="whatsappQueue" class="space-y-4">
            <div class="grid gap-4 grid-cols-3">
              <div class="text-center">
                <div class="text-2xl font-bold">{{ whatsappQueue.pending_jobs }}</div>
                <div class="text-xs text-muted-foreground">En cola</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold">{{ whatsappQueue.processing_jobs }}</div>
                <div class="text-xs text-muted-foreground">Procesando</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-destructive">{{ whatsappQueue.failed_jobs }}</div>
                <div class="text-xs text-muted-foreground">Fallidos</div>
              </div>
            </div>
            
            <div v-if="whatsappQueue.pending_jobs > 0" class="space-y-2">
              <div class="flex justify-between text-sm">
                <span>Tiempo estimado de espera</span>
                <span class="font-medium">{{ formatWaitTime(whatsappQueue.estimated_wait_seconds) }}</span>
              </div>
              <Progress 
                :value="Math.min(100, (whatsappQueue.processing_jobs / (whatsappQueue.pending_jobs + whatsappQueue.processing_jobs)) * 100)" 
                class="h-2" 
              />
            </div>
          </CardContent>
          <CardContent v-else>
            <p class="text-muted-foreground text-center py-4">
              No hay datos disponibles para la cola de WhatsApp
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Distribución por Canal -->
      <Card>
        <CardHeader>
          <CardTitle>Distribución de OTPs por Canal</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-3">
            <div class="flex items-center gap-4">
              <Mail class="h-8 w-8 text-blue-500" />
              <div>
                <div class="text-2xl font-bold">{{ otpStats.por_canal.email }}</div>
                <div class="text-sm text-muted-foreground">Solo Email</div>
              </div>
            </div>
            
            <div class="flex items-center gap-4">
              <MessageSquare class="h-8 w-8 text-green-500" />
              <div>
                <div class="text-2xl font-bold">{{ otpStats.por_canal.whatsapp }}</div>
                <div class="text-sm text-muted-foreground">Solo WhatsApp</div>
              </div>
            </div>
            
            <div class="flex items-center gap-4">
              <div class="flex">
                <Mail class="h-8 w-8 text-blue-500" />
                <MessageSquare class="h-8 w-8 text-green-500 -ml-2" />
              </div>
              <div>
                <div class="text-2xl font-bold">{{ otpStats.por_canal.both }}</div>
                <div class="text-sm text-muted-foreground">Ambos Canales</div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Acciones Administrativas -->
      <Card>
        <CardHeader>
          <CardTitle>Acciones Administrativas</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-4">
            <Button variant="outline" size="sm">
              <Timer class="h-4 w-4 mr-2" />
              Monitoreo en Tiempo Real
            </Button>
            <Button variant="outline" size="sm">
              <RefreshCw class="h-4 w-4 mr-2" />
              Reiniciar Cola Fallida
            </Button>
            <Button variant="outline" size="sm">
              <Users class="h-4 w-4 mr-2" />
              Ver Logs Detallados
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </AdminLayout>
</template>