<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
    AlertCircle, 
    CheckCircle2,
    Clock, 
    Video, 
    ExternalLink,
    Loader2,
    RefreshCw
} from 'lucide-vue-next';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';

interface Props {
    asambleaId: number;
}

const props = defineProps<Props>();

// Estado del componente
const isLoading = ref(false);
const isRegistering = ref(false);
const isProcessing = ref(false);
const pollingInterval = ref<number | null>(null);
const pollingStartTime = ref<number | null>(null);
const error = ref<string | null>(null);
const statusData = ref<any>(null);

// Timeout máximo para polling (5 minutos)
const POLLING_TIMEOUT_MS = 5 * 60 * 1000;

// Helper para obtener route
const { route } = window as any;

/**
 * Obtener estado del registro para la asamblea
 */
const fetchStatus = async () => {
    try {
        isLoading.value = true;
        error.value = null;

        const response = await axios.get(route('api.zoom.registrants.status', props.asambleaId));
        const data = response.data;
        
        if (data.success) {
            statusData.value = data;
            
            // Si encontramos un registro completado, detener polling
            if (data.existing_registration && data.existing_registration.status === 'completed') {
                stopPolling();
            }
            // Si encontramos un registro fallido, detener polling 
            else if (data.existing_registration && data.existing_registration.status === 'failed') {
                stopPolling();
            }
            // Si está explícitamente procesando, asegurar que polling esté activo
            else if (data.processing) {
                if (!pollingInterval.value) {
                    startPolling();
                }
            }
            // Si estamos en modo procesamiento pero no hay procesamiento explícito,
            // continuar polling (no detener por race conditions)
            else if (isProcessing.value) {
                // Mantener polling activo hasta resultado definitivo
                if (!pollingInterval.value) {
                    startPolling();
                }
            }
        } else {
            error.value = data.error || 'Error obteniendo estado';
        }

    } catch (err) {
        console.error('Error fetching status:', err);
        error.value = 'Error de conexión';
    } finally {
        isLoading.value = false;
    }
};

/**
 * Reintentar registro después de un fallo
 */
const retryRegistration = async () => {
    // Limpiar error y estado
    error.value = null;
    statusData.value.existing_registration = null;
    
    // Llamar a registerUser
    await registerUser();
};

/**
 * Registrar usuario en la reunión
 */
const registerUser = async () => {
    try {
        isRegistering.value = true;
        error.value = null;

        const response = await axios.post(route('api.zoom.registrants.register'), {
            asamblea_id: props.asambleaId
        });

        if (response.data.success) {
            // Si la respuesta indica que está procesando
            if (response.data.processing) {
                isProcessing.value = true;
                statusData.value = response.data;
                startPolling();
                toast.success('🔄 Procesando registro', {
                    description: 'Se está generando tu enlace de videoconferencia...',
                    duration: 4000,
                });
            } else {
                // Registro inmediato (caso legacy)
                await fetchStatus();
            }
        } else {
            error.value = response.data.error || 'Error registrando usuario';
        }

    } catch (err: any) {
        console.error('Error registering user:', err);
        if (err.response?.data?.error) {
            error.value = err.response.data.error;
        } else {
            error.value = 'Error de conexión';
        }
    } finally {
        isRegistering.value = false;
    }
};

/**
 * Cancelar registro
 */
const cancelRegistration = async () => {
    try {
        isLoading.value = true;
        error.value = null;

        const response = await axios.delete(route('api.zoom.registrants.destroy', props.asambleaId));
        const data = response.data;
        
        if (data.success) {
            // Actualizar estado después de la cancelación
            await fetchStatus();
        } else {
            error.value = data.error || 'Error cancelando registro';
        }

    } catch (err) {
        console.error('Error canceling registration:', err);
        error.value = 'Error de conexión';
    } finally {
        isLoading.value = false;
    }
};

/**
 * Marcar asistencia del usuario
 */
const marcarAsistencia = async () => {
    try {
        const response = await axios.post(route('asambleas.marcar-asistencia', props.asambleaId));
        
        if (response.data.success) {
            toast.success('✅ Asistencia registrada', {
                description: 'Tu asistencia ha sido marcada exitosamente',
                duration: 3000,
            });
        }
    } catch (error: any) {
        // No bloquear la apertura de Zoom si falla
        console.error('Error marcando asistencia:', error);
        
        if (error.response?.status === 400) {
            toast.warning('La asamblea no está en curso', {
                duration: 3000,
            });
        } else if (error.response?.status === 403) {
            toast.error('No eres participante de esta asamblea', {
                duration: 3000,
            });
        } else {
            toast.error('Error al registrar asistencia', {
                description: 'Tu asistencia no pudo ser registrada, pero puedes unirte a la reunión',
                duration: 4000,
            });
        }
    }
};

/**
 * Abrir URL de Zoom en nueva pestaña y marcar asistencia
 */
const openZoomMeeting = async () => {
    // Primero marcar asistencia (no bloqueante)
    await marcarAsistencia();
    
    // Luego abrir Zoom independientemente del resultado
    if (registrationStatus.value?.zoom_join_url) {
        window.open(registrationStatus.value.zoom_join_url, '_blank', 'noopener,noreferrer');
    }
};

/**
 * Iniciar polling para verificar el estado del registro
 */
const startPolling = () => {
    // Limpiar polling anterior si existe
    stopPolling();
    
    // Marcar tiempo de inicio
    pollingStartTime.value = Date.now();
    
    // Iniciar polling cada 2 segundos
    pollingInterval.value = setInterval(async () => {
        try {
            // Verificar timeout de seguridad
            if (pollingStartTime.value && (Date.now() - pollingStartTime.value) > POLLING_TIMEOUT_MS) {
                console.warn('Polling timeout alcanzado (5 minutos)');
                error.value = 'El proceso está tomando más tiempo del esperado. Por favor, recarga la página.';
                stopPolling();
                return;
            }
            
            await fetchStatus();
        } catch (error) {
            console.error('Error en polling:', error);
        }
    }, 2000);
};

/**
 * Detener polling
 */
const stopPolling = () => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
        pollingInterval.value = null;
    }
    pollingStartTime.value = null;
    isProcessing.value = false;
};

// Computed para obtener el estado visual del registro
const registrationStatus = computed(() => {
    if (!statusData.value?.existing_registration) {
        return null;
    }

    const reg = statusData.value.existing_registration;
    return {
        ...reg,
        statusColor: reg.status === 'completed' ? 'bg-green-100 text-green-800' :
                     reg.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                     reg.status === 'failed' ? 'bg-red-100 text-red-800' :
                     'bg-gray-100 text-gray-800'
    };
});

// Computed para verificar si el registro falló
const registrationFailed = computed(() => {
    return statusData.value?.existing_registration?.status === 'failed';
});

// Computed para obtener mensaje de error amigable
const errorMessage = computed(() => {
    if (!registrationFailed.value) return null;
    
    const msg = statusData.value?.existing_registration?.error_message || 'Error desconocido al registrar';
    
    // Si el mensaje incluye "rate limit", sugerir reintentar más tarde
    if (msg.toLowerCase().includes('rate limit')) {
        return 'Se ha alcanzado el límite diario de registros. Por favor, intenta nuevamente mañana.';
    }
    
    return msg;
});

// Computed para determinar si se puede reintentar
const canRetry = computed(() => {
    if (!registrationFailed.value) return false;
    
    const msg = statusData.value?.existing_registration?.error_message || '';
    
    // No permitir reintentar si es un error definitivo
    const permanentErrors = [
        'no existe',
        'capacidad máxima',
        'no tiene permisos'
    ];
    
    return !permanentErrors.some(error => msg.toLowerCase().includes(error));
});

// Computed para verificar si puede registrarse
const canRegister = computed(() => {
    // Si hay un registro fallido, no mostrar el botón de registro aquí
    // (se mostrará en la card de error con opción de reintentar)
    if (registrationFailed.value) return false;
    
    return statusData.value?.can_register && 
           !statusData.value?.existing_registration && 
           !isProcessing.value &&
           !statusData.value?.processing;
});

// Computed para verificar si puede acceder a la reunión
const canJoinMeeting = computed(() => {
    const reg = statusData.value?.existing_registration;
    const asambleaEnCurso = statusData.value?.asamblea?.estado === 'en_curso';
    // Solo si está completado exitosamente
    return reg && reg.status === 'completed' && reg.zoom_join_url && asambleaEnCurso;
});

// Computed para verificar si la asamblea está en curso
const isAsambleaEnCurso = computed(() => {
    return statusData.value?.asamblea?.estado === 'en_curso';
});

// Cargar estado inicial
onMounted(() => {
    fetchStatus();
});

// Limpiar polling al desmontar
onUnmounted(() => {
    stopPolling();
});
</script>

<template>
    <div class="space-y-4">
        <!-- Título -->
        <div class="flex items-center gap-2 mb-4">
            <Video class="h-5 w-5 text-blue-600" />
            <h3 class="text-lg font-semibold">Videoconferencia</h3>
            <Button 
                variant="ghost" 
                size="sm" 
                @click="fetchStatus"
                :disabled="isLoading"
                class="ml-auto"
            >
                <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': isLoading }" />
            </Button>
        </div>

        <!-- Loading inicial -->
        <div v-if="isLoading && !statusData" class="text-center py-8">
            <Loader2 class="h-8 w-8 animate-spin mx-auto mb-2" />
            <p class="text-muted-foreground">Cargando información...</p>
        </div>

        <!-- Error -->
        <Alert v-if="error" variant="destructive">
            <AlertCircle class="h-4 w-4" />
            <AlertTitle>Error</AlertTitle>
            <AlertDescription>{{ error }}</AlertDescription>
        </Alert>

        <!-- Contenido principal -->
        <div v-if="statusData && !isLoading">
            <!-- Procesando registro -->
            <Card v-if="isProcessing || statusData.processing" class="border-blue-200 bg-blue-50">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-blue-700">
                        <Loader2 class="h-5 w-5 animate-spin" />
                        Procesando registro
                    </CardTitle>
                    <CardDescription>
                        Se está generando tu enlace personal de videoconferencia...
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div class="text-sm text-blue-600">
                            ✅ Validaciones completadas<br>
                            🔄 Registrando en Zoom...<br>
                            📧 Preparando notificación por email
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Este proceso toma entre 1-2 minutos. La página se actualizará automáticamente.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Registro fallido -->
            <Card v-else-if="registrationFailed" class="border-red-200 bg-red-50">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-red-700">
                        <AlertCircle class="h-5 w-5" />
                        Error en el registro
                    </CardTitle>
                    <CardDescription class="text-red-600">
                        No se pudo completar tu registro en la videoconferencia
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <Alert variant="destructive">
                        <AlertCircle class="h-4 w-4" />
                        <AlertTitle>Detalles del error</AlertTitle>
                        <AlertDescription>{{ errorMessage }}</AlertDescription>
                    </Alert>
                    
                    <div class="flex gap-2">
                        <Button 
                            v-if="canRetry"
                            @click="retryRegistration"
                            :disabled="isRegistering || isProcessing"
                            class="flex-1"
                        >
                            <RefreshCw v-if="isRegistering || isProcessing" class="mr-2 h-4 w-4 animate-spin" />
                            <RefreshCw v-else class="mr-2 h-4 w-4" />
                            Reintentar registro
                        </Button>
                        
                        <Button 
                            v-else
                            variant="outline"
                            disabled
                            class="flex-1"
                        >
                            No se puede reintentar
                        </Button>
                    </div>
                    
                    <p class="text-xs text-muted-foreground">
                        Si el problema persiste, contacta al administrador.
                    </p>
                </CardContent>
            </Card>
            
            <!-- Usuario ya registrado exitosamente -->
            <Card v-else-if="registrationStatus && registrationStatus.status === 'completed'" class="border-green-200">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-green-700">
                        <CheckCircle2 class="h-5 w-5" />
                        Ya estás registrado
                    </CardTitle>
                    <CardDescription>
                        Tienes acceso a esta videoconferencia
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div v-if="registrationStatus.zoom_start_time">
                        <p class="text-sm font-medium text-muted-foreground">Hora de inicio</p>
                        <p class="text-sm">{{ new Date(registrationStatus.zoom_start_time).toLocaleString('es-ES') }}</p>
                    </div>

                    <div v-if="registrationStatus.zoom_registrant_id">
                        <p class="text-sm font-medium text-muted-foreground">Token único de ingreso</p>
                        <p class="text-sm font-mono bg-gray-50 px-2 py-1 rounded">{{ registrationStatus.zoom_registrant_id }}</p>
                    </div>

                    <!-- Botones de acción -->
                    <div class="pt-2">
                        <!-- Aviso cuando no está en curso -->
                        <div v-if="!isAsambleaEnCurso" class="mb-3">
                            <Alert>
                                <Clock class="h-4 w-4" />
                                <AlertTitle>Reunión no disponible</AlertTitle>
                                <AlertDescription>
                                    La videoconferencia solo está disponible cuando la asamblea está "En Curso". 
                                    Estado actual: {{ statusData.asamblea.estado_label }}
                                </AlertDescription>
                            </Alert>
                        </div>

                        <!-- Botones en la misma fila -->
                        <div class="flex gap-2">
                            <Button 
                                v-if="canJoinMeeting"
                                @click="openZoomMeeting"
                                class="flex-1 bg-green-600 hover:bg-green-700 text-white"
                            >
                                <ExternalLink class="mr-2 h-4 w-4" />
                                Unirse a la Videoconferencia
                            </Button>

                            <Button 
                                variant="outline" 
                                @click="cancelRegistration"
                                :disabled="isLoading"
                                size="sm"
                                class="hidden"
                            >
                                <Loader2 v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
                                Cancelar
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Usuario puede registrarse -->
            <Card v-else-if="canRegister">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Video class="h-5 w-5" />
                        Generar Acceso Personal
                    </CardTitle>
                    <CardDescription>
                        Genera tu link personal para acceder a la videoconferencia
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Button 
                        @click="registerUser"
                        :disabled="isRegistering || isProcessing"
                        class="w-full"
                    >
                        <Loader2 v-if="isRegistering || isProcessing" class="mr-2 h-4 w-4 animate-spin" />
                        <Video v-else class="mr-2 h-4 w-4" />
                        {{ isProcessing ? 'Procesando...' : 'Generar link de ingreso' }}
                    </Button>
                </CardContent>
            </Card>

            <!-- Usuario no puede registrarse -->
            <Alert v-else>
                <Clock class="h-4 w-4" />
                <AlertTitle>No disponible</AlertTitle>
                <AlertDescription>
                    {{ statusData.reason }}
                </AlertDescription>
            </Alert>

            <!-- Advertencia sobre compartir link -->
            <Alert variant="destructive" class="mt-4">
                <AlertCircle class="h-4 w-4" />
                <AlertTitle>Advertencia</AlertTitle>
                <AlertDescription>
                    Tu link es único, cuídalo, no lo compartas, si lo compartes podrías quedarte por fuera de la reunión, ya que solo se admite un usuario por link.
                </AlertDescription>
            </Alert>
        </div>
    </div>
</template>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>