<template>
  <Transition name="fade">
    <div v-if="showStatus" class="mt-4">
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
            <Clock v-if="!loading && status === 'processing'" class="h-4 w-4" />
            <Check v-if="!loading && status === 'sent'" class="h-4 w-4 text-green-600" />
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
          <div v-else-if="status" class="space-y-4">
            <Alert :variant="status === 'sent' ? 'default' : 'default'">
              <AlertCircle v-if="status === 'processing'" class="h-4 w-4" />
              <Check v-if="status === 'sent'" class="h-4 w-4" />
              <AlertTitle>{{ statusTitle }}</AlertTitle>
              <AlertDescription>
                {{ statusMessage }}
              </AlertDescription>
            </Alert>

            <!-- Info adicional cuando se envía por ambos canales -->
            <div v-if="canal === 'both'" class="text-xs text-muted-foreground space-y-1">
              <div class="flex items-center gap-2">
                <Check v-if="emailSent" class="h-3 w-3 text-green-600" />
                <Loader2 v-else class="h-3 w-3 animate-spin" />
                <span>Correo electrónico {{ emailSent ? 'enviado' : 'procesando' }}</span>
              </div>
              <div class="flex items-center gap-2">
                <Check v-if="whatsappSent" class="h-3 w-3 text-green-600" />
                <Loader2 v-else class="h-3 w-3 animate-spin" />
                <span>WhatsApp {{ whatsappSent ? 'enviado' : 'procesando' }}</span>
              </div>
            </div>
          </div>

          <!-- Estado sin datos -->
          <div v-else class="text-center py-4">
            <p class="text-sm text-muted-foreground">
              No se pudo obtener el estado
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
import { Button } from "./ui/button"
import { Skeleton } from "./ui/skeleton"
import {
  Loader2,
  Clock,
  AlertCircle,
  Check,
  RefreshCw
} from 'lucide-vue-next'
import axios from 'axios'

interface Props {
  identifier: string
  autoRefresh?: boolean
  refreshInterval?: number
}

const props = withDefaults(defineProps<Props>(), {
  autoRefresh: true,
  refreshInterval: 3000, // 3 segundos
})

const emit = defineEmits<{
  statusUpdate: [status: string]
  error: [error: Error]
}>()

// Estado
const loading = ref(false)
const showStatus = ref(false)
const status = ref<string>('')
const canal = ref<string>('')
const emailSent = ref(false)
const whatsappSent = ref(false)
const intervalId = ref<number | null>(null)

// Computed
const statusTitle = computed(() => {
  if (status.value === 'sent') return 'Código enviado con éxito'
  if (status.value === 'processing') return 'Procesando tu solicitud'
  return 'Verificando estado...'
})

const statusMessage = computed(() => {
  if (status.value === 'sent') {
    if (canal.value === 'both') {
      return 'Tu código ha sido enviado por correo electrónico y WhatsApp'
    } else if (canal.value === 'whatsapp') {
      return 'Tu código ha sido enviado por WhatsApp'
    } else {
      return 'Tu código ha sido enviado por correo electrónico'
    }
  }

  if (status.value === 'processing') {
    return 'Tu código está siendo enviado en este momento...'
  }

  return ''
})

// Métodos
const checkStatus = async () => {
  loading.value = true

  try {
    const response = await axios.get(
      `/api/queue/otp/position/${encodeURIComponent(props.identifier)}`
    )

    if (response.data.success && response.data.data) {
      const data = response.data.data
      status.value = data.status
      canal.value = data.canal || 'email'
      emailSent.value = data.email_sent || false
      whatsappSent.value = data.whatsapp_sent || false
      showStatus.value = true

      emit('statusUpdate', status.value)

      // Si ya fue enviado, detener el auto-refresh
      if (status.value === 'sent') {
        stopAutoRefresh()
      }
    }
  } catch (error: any) {
    console.error('Error verificando estado:', error)
    emit('error', error)
  } finally {
    loading.value = false
  }
}

const startAutoRefresh = () => {
  if (!props.autoRefresh) return

  intervalId.value = window.setInterval(() => {
    checkStatus()
  }, props.refreshInterval)
}

const stopAutoRefresh = () => {
  if (intervalId.value) {
    clearInterval(intervalId.value)
    intervalId.value = null
  }
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
