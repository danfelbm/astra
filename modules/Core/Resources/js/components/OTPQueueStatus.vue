<template>
  <Transition name="fade">
    <div v-if="showStatus" class="mt-4">
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
            <Clock v-else class="h-4 w-4" />
            Estado de envío de tu código
          </CardTitle>
        </CardHeader>
        <CardContent>
          <!-- Estado de loading -->
          <div v-if="loading" class="space-y-3">
            <Skeleton class="h-4 w-full" />
            <Skeleton class="h-4 w-3/4" />
          </div>

          <!-- Estado con datos -->
          <div v-else-if="queueData" class="space-y-4">
            <!-- Posición en cola -->
            <div v-if="queueData.position > 1" class="space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-sm text-muted-foreground">Posición en cola</span>
                <Badge variant="secondary">{{ queueData.position }}</Badge>
              </div>
              
              <!-- Barra de progreso -->
              <Progress :value="progressPercentage" class="h-2" />
              
              <p class="text-sm text-muted-foreground">
                {{ queueData.total_ahead }} personas delante de ti
              </p>
            </div>

            <!-- Procesando ahora -->
            <div v-else-if="queueData.position === 1" class="space-y-2">
              <Alert>
                <AlertCircle class="h-4 w-4" />
                <AlertTitle>Procesando tu solicitud</AlertTitle>
                <AlertDescription>
                  Tu código está siendo enviado en este momento...
                </AlertDescription>
              </Alert>
            </div>

            <!-- Tiempo estimado -->
            <div v-if="queueData.estimated_seconds > 0" class="space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-sm text-muted-foreground">Tiempo estimado</span>
                <span class="text-sm font-medium">{{ queueData.estimated_time }}</span>
              </div>
              
              <!-- Countdown visual -->
              <div class="flex items-center gap-2 text-xs text-muted-foreground">
                <Timer class="h-3 w-3" />
                <span>Actualizando en {{ countdown }}s</span>
              </div>
            </div>

            <!-- Información del rate limit -->
            <div v-if="showRateLimitInfo" class="mt-4 pt-4 border-t">
              <Alert variant="info">
                <Info class="h-4 w-4" />
                <AlertTitle>¿Por qué la espera?</AlertTitle>
                <AlertDescription>
                  Para garantizar la entrega confiable de mensajes, procesamos
                  {{ rateLimit }} {{ type === 'email' ? 'correos' : 'mensajes' }} por segundo.
                  Esto nos ayuda a mantener un servicio estable para todos.
                </AlertDescription>
              </Alert>
            </div>
          </div>

          <!-- Estado sin datos -->
          <div v-else class="text-center py-4">
            <p class="text-sm text-muted-foreground">
              No se pudo obtener el estado de la cola
            </p>
            <Button 
              @click="checkStatus" 
              variant="ghost" 
              size="sm"
              class="mt-2"
            >
              <RefreshCw class="h-3 w-3 mr-2" />
              Reintentar
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from "./ui/card"
import { Alert, AlertDescription, AlertTitle } from "./ui/alert"
import { Badge } from "./ui/badge"
import { Button } from "./ui/button"
import { Progress } from "./ui/progress"
import { Skeleton } from "./ui/skeleton"
import { 
  Loader2, 
  Clock, 
  AlertCircle, 
  Timer, 
  Info, 
  RefreshCw 
} from 'lucide-vue-next'
import axios from 'axios'

interface QueueData {
  position: number
  total_ahead: number
  estimated_seconds: number
  estimated_time: string
  type?: string
}

interface Props {
  type: 'email' | 'whatsapp'
  identifier: string
  autoRefresh?: boolean
  refreshInterval?: number
  showRateLimitInfo?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  autoRefresh: true,
  refreshInterval: 5000, // 5 segundos
  showRateLimitInfo: true
})

const emit = defineEmits<{
  statusUpdate: [data: QueueData | null]
  error: [error: Error]
}>()

// Estado
const loading = ref(false)
const showStatus = ref(false)
const queueData = ref<QueueData | null>(null)
const countdown = ref(5)
const intervalId = ref<number | null>(null)
const countdownId = ref<number | null>(null)

// Computed
const progressPercentage = computed(() => {
  if (!queueData.value || queueData.value.total_ahead === 0) return 100
  
  // Estimamos el total original basándonos en la posición actual
  const estimatedTotal = queueData.value.position + 10 // Asumimos 10 ya procesados
  const processed = estimatedTotal - queueData.value.position
  
  return Math.min(100, Math.round((processed / estimatedTotal) * 100))
})

const rateLimit = computed(() => {
  return props.type === 'email' ? 2 : 5
})

// Métodos
const checkStatus = async () => {
  loading.value = true
  
  try {
    // Primero verificar posición específica
    const positionResponse = await axios.get(
      `/api/queue/otp/position/${encodeURIComponent(props.identifier)}`,
      { params: { type: props.type } }
    )
    
    if (positionResponse.data.success) {
      queueData.value = positionResponse.data.data
      showStatus.value = true
      emit('statusUpdate', queueData.value)
      
      // Si ya fue procesado, detener el refresh
      if (queueData.value.position === 0) {
        stopAutoRefresh()
      }
    }
  } catch (error: any) {
    if (error.response?.status === 404) {
      // No está en cola, probablemente ya fue procesado
      try {
        // Obtener estimación general
        const estimateResponse = await axios.get('/api/queue/otp/estimate', {
          params: { type: props.type }
        })
        
        if (estimateResponse.data.success) {
          queueData.value = estimateResponse.data.data
          showStatus.value = true
          emit('statusUpdate', queueData.value)
        }
      } catch (estimateError: any) {
        console.error('Error obteniendo estimación:', estimateError)
        emit('error', estimateError)
      }
    } else {
      console.error('Error verificando estado:', error)
      emit('error', error)
    }
  } finally {
    loading.value = false
  }
}

const startAutoRefresh = () => {
  if (!props.autoRefresh) return
  
  // Intervalo principal para verificar estado
  intervalId.value = window.setInterval(() => {
    checkStatus()
    resetCountdown()
  }, props.refreshInterval)
  
  // Countdown visual
  countdownId.value = window.setInterval(() => {
    countdown.value = Math.max(0, countdown.value - 1)
  }, 1000)
}

const stopAutoRefresh = () => {
  if (intervalId.value) {
    clearInterval(intervalId.value)
    intervalId.value = null
  }
  
  if (countdownId.value) {
    clearInterval(countdownId.value)
    countdownId.value = null
  }
}

const resetCountdown = () => {
  countdown.value = Math.floor(props.refreshInterval / 1000)
}

// Lifecycle
onMounted(() => {
  checkStatus()
  startAutoRefresh()
})

onUnmounted(() => {
  stopAutoRefresh()
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>