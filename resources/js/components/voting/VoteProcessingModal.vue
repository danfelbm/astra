<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <component :is="statusIcon" :class="iconClass" class="h-5 w-5" />
          {{ statusTitle }}
        </DialogTitle>
      </DialogHeader>
      
      <div class="space-y-4">
        <!-- Estado de procesamiento -->
        <div class="flex flex-col items-center justify-center py-6">
          <!-- Loading spinner -->
          <div v-if="isProcessing" class="relative">
            <div class="h-16 w-16 animate-spin rounded-full border-4 border-muted border-t-primary"></div>
            <CheckCircle 
              v-if="status === 'processing'" 
              class="absolute left-1/2 top-1/2 h-8 w-8 -translate-x-1/2 -translate-y-1/2 text-primary opacity-50"
            />
          </div>
          
          <!-- Ícono de estado completado/error -->
          <div v-else-if="status === 'completed'" class="text-green-600">
            <CheckCircle class="h-16 w-16" />
          </div>
          <div v-else-if="isError" class="text-red-600">
            <AlertCircle class="h-16 w-16" />
          </div>
          
          <!-- Mensaje de estado -->
          <p class="mt-4 text-center text-sm text-muted-foreground">
            {{ statusMessage }}
          </p>
          
          <!-- Barra de progreso visual -->
          <div v-if="isProcessing" class="mt-4 w-full">
            <Progress :value="progressValue" class="h-2" />
            <p class="mt-2 text-center text-xs text-muted-foreground">
              {{ attemptCount > 0 ? `Intento ${attemptCount} de verificación...` : 'Conectando...' }}
            </p>
          </div>
          
          <!-- Información adicional cuando está completado -->
          <div v-if="status === 'completed' && voteInfo" class="mt-4 w-full rounded-lg bg-green-50 dark:bg-green-950 p-4">
            <div class="space-y-2 text-sm">
              <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Token:</span>
                <code class="rounded bg-black/10 px-2 py-1 text-xs font-mono">
                  {{ voteInfo.token_preview }}
                </code>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Registrado:</span>
                <span class="font-medium">{{ formatDate(voteInfo.created_at) }}</span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Acciones -->
        <DialogFooter>
          <Button 
            v-if="status === 'completed'"
            @click="viewVote"
            class="w-full"
          >
            <Eye class="mr-2 h-4 w-4" />
            Ver mi voto
          </Button>
          
          <Button 
            v-else-if="isError"
            @click="retry"
            variant="destructive"
            class="w-full"
          >
            <RefreshCw class="mr-2 h-4 w-4" />
            Intentar nuevamente
          </Button>
          
          <Button 
            v-else
            @click="close"
            variant="outline"
            class="w-full"
            :disabled="isProcessing"
          >
            {{ isProcessing ? 'Procesando...' : 'Cerrar' }}
          </Button>
        </DialogFooter>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { 
  Loader2, 
  CheckCircle, 
  AlertCircle, 
  Eye, 
  RefreshCw,
  Clock
} from 'lucide-vue-next';

interface Props {
  votacionId: number;
  checkStatusUrl: string;
  modelValue?: boolean;
}

interface VoteInfo {
  voto_id: number;
  token_preview: string;
  created_at: string;
}

const props = defineProps<Props>();
const emit = defineEmits(['update:modelValue', 'completed', 'error']);

const isOpen = ref(props.modelValue ?? true);
const status = ref<'pending' | 'processing' | 'completed' | 'duplicate' | 'error' | 'failed'>('pending');
const statusMessage = ref('Preparando firma digital...');
const voteInfo = ref<VoteInfo | null>(null);
const attemptCount = ref(0);
const progressValue = ref(10);
const pollingInterval = ref<number | null>(null);

// Computed
const isProcessing = computed(() => ['pending', 'processing'].includes(status.value));
const isError = computed(() => ['error', 'failed', 'duplicate'].includes(status.value));

const statusIcon = computed(() => {
  switch (status.value) {
    case 'completed':
      return CheckCircle;
    case 'error':
    case 'failed':
    case 'duplicate':
      return AlertCircle;
    default:
      return Clock;
  }
});

const iconClass = computed(() => {
  if (status.value === 'completed') return 'text-green-600';
  if (isError.value) return 'text-red-600';
  return 'text-primary animate-pulse';
});

const statusTitle = computed(() => {
  switch (status.value) {
    case 'pending':
      return 'Preparando tu voto';
    case 'processing':
      return 'Procesando tu voto';
    case 'completed':
      return '¡Voto registrado!';
    case 'duplicate':
      return 'Voto duplicado';
    case 'error':
    case 'failed':
      return 'Error al procesar';
    default:
      return 'Verificando estado';
  }
});

// Funciones
const checkVoteStatus = async () => {
  try {
    attemptCount.value++;
    
    const response = await fetch(props.checkStatusUrl, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    });
    
    if (!response.ok) {
      throw new Error('Error al verificar estado');
    }
    
    const data = await response.json();
    
    status.value = data.status;
    statusMessage.value = data.message;
    
    if (data.status === 'processing') {
      // Actualizar progreso visual
      progressValue.value = Math.min(90, progressValue.value + 10);
    }
    
    if (data.completed) {
      // Voto completado exitosamente
      progressValue.value = 100;
      voteInfo.value = {
        voto_id: data.voto_id,
        token_preview: data.token_preview,
        created_at: data.created_at,
      };
      stopPolling();
      emit('completed', data);
      
      // Auto-cerrar después de 5 segundos si está completado
      setTimeout(() => {
        if (status.value === 'completed') {
          viewVote();
        }
      }, 5000);
    } else if (data.error) {
      // Error en el procesamiento
      stopPolling();
      emit('error', data);
    }
    
  } catch (error) {
    console.error('Error checking vote status:', error);
    
    // Después de 10 intentos, mostrar error
    if (attemptCount.value >= 10) {
      status.value = 'error';
      statusMessage.value = 'No se pudo verificar el estado. Por favor, recarga la página.';
      stopPolling();
    }
  }
};

const startPolling = () => {
  // Hacer la primera verificación inmediatamente
  checkVoteStatus();
  
  // Luego verificar cada 1.5 segundos
  pollingInterval.value = window.setInterval(() => {
    checkVoteStatus();
  }, 1500);
};

const stopPolling = () => {
  if (pollingInterval.value) {
    clearInterval(pollingInterval.value);
    pollingInterval.value = null;
  }
};

const viewVote = () => {
  router.visit(`/miembro/votaciones/${props.votacionId}/mi-voto`);
};

const retry = () => {
  router.visit(`/miembro/votaciones/${props.votacionId}/votar`);
};

const close = () => {
  isOpen.value = false;
  emit('update:modelValue', false);
  
  // Si está procesando, redirigir al index
  if (isProcessing.value) {
    router.visit('/miembro/votaciones');
  }
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

// Lifecycle
onMounted(() => {
  startPolling();
});

onUnmounted(() => {
  stopPolling();
});

// Watchers
watch(isOpen, (newValue) => {
  emit('update:modelValue', newValue);
  if (!newValue) {
    stopPolling();
  }
});
</script>