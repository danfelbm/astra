<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from "@modules/Core/Resources/js/components/ui/alert";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { 
    AlertCircle, 
    CheckCircle2,
    Clock, 
    Info,
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
const fetchStatus = async (skipLoadingIndicator = false) => {
    try {
        // No mostrar loading durante el polling para evitar parpadeos
        if (!skipLoadingIndicator) {
            isLoading.value = true;
        }
        error.value = null;

        const response = await axios.get(route('user.api.zoom.registrants.status', props.asambleaId));
        const data = response.data;
        
        if (data.success) {
            // Preservar el estado de procesamiento si estamos en modo procesamiento
            // para evitar parpadeos cuando el backend aún no detecta el job
            if (isProcessing.value && !data.existing_registration && !data.processing) {
                // Mantener el estado de procesamiento activo
                data.processing = true;
            }
            
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

        const response = await axios.post(route('user.api.zoom.registrants.register'), {
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

        const response = await axios.delete(route('user.api.zoom.registrants.destroy', props.asambleaId));
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
        const response = await axios.post(route('user.asambleas.marcar-asistencia', props.asambleaId));
        
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
 * Abrir URL de Zoom con máxima compatibilidad móvil
 * Implementa múltiples métodos de apertura con fallback
 */
const openZoomMeeting = () => {
    const zoomUrl = registrationStatus.value?.zoom_join_url;
    if (!zoomUrl) return;

    // Intentar abrir el enlace inmediatamente (mantiene contexto de usuario)
    let popupBlocked = false;
    
    // Método 1: window.open directo
    const popup = window.open(zoomUrl, '_blank', 'noopener,noreferrer');
    
    if (!popup || popup.closed || typeof popup.closed === 'undefined') {
        // El popup fue bloqueado, intentar métodos alternativos
        popupBlocked = true;
        
        // Método 2: setTimeout con delay 0 (funciona en algunos navegadores móviles)
        setTimeout(() => {
            const retryPopup = window.open(zoomUrl, '_blank');
            if (!retryPopup || retryPopup.closed) {
                // Método 3: Crear elemento anchor temporal
                const link = document.createElement('a');
                link.href = zoomUrl;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // Si aún falla, mostrar mensaje al usuario
                showManualLinkMessage();
            }
        }, 0);
    }
    
    // Marcar asistencia de forma no bloqueante (no usar await)
    marcarAsistencia().catch(error => {
        // Los errores de asistencia no deben bloquear la apertura del zoom
        console.error('Error marcando asistencia:', error);
    });
    
    // Si detectamos bloqueo, informar al usuario
    if (popupBlocked) {
        toast.warning('⚠️ Tu navegador bloqueó la ventana emergente', {
            description: 'Usa el enlace manual debajo del botón para unirte a la reunión',
            duration: 5000,
        });
    }
};

/**
 * Mostrar mensaje con enlace manual si los métodos automáticos fallan
 */
const showManualLinkMessage = () => {
    toast.error('No se pudo abrir automáticamente', {
        description: 'Por favor usa el enlace manual debajo del botón',
        duration: 6000,
    });
};

/**
 * Detectar si es un dispositivo móvil
 */
const isMobileDevice = () => {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
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
            
            // Skip loading indicator durante polling para evitar parpadeos
            await fetchStatus(true);
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

        <!-- Mensaje personalizado de API si está habilitado -->
        <Card v-if="statusData?.asamblea?.zoom_api_message_enabled && statusData?.asamblea?.zoom_api_message" class="mb-4 border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/50">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-blue-700 dark:text-blue-300">
                    <Info class="h-5 w-5" />
                    Información Importante
                </CardTitle>
            </CardHeader>
            <CardContent>
                <p class="whitespace-pre-wrap text-gray-700 dark:text-gray-300">{{ statusData.asamblea.zoom_api_message }}</p>
            </CardContent>
        </Card>

        <!-- Contenido principal -->
        <div v-if="statusData && !isLoading">
            <!-- Procesando registro -->
            <Card v-if="isProcessing || statusData.processing" class="border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/50">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-blue-700 dark:text-blue-300">
                        <Loader2 class="h-5 w-5 animate-spin" />
                        Procesando registro
                    </CardTitle>
                    <CardDescription class="dark:text-gray-400">
                        Se está generando tu enlace personal de videoconferencia...
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div class="text-sm text-blue-600 dark:text-blue-400">
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
            <Card v-else-if="registrationFailed" class="border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/50">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-red-700 dark:text-red-400">
                        <AlertCircle class="h-5 w-5" />
                        Error en el registro
                    </CardTitle>
                    <CardDescription class="text-red-600 dark:text-red-400">
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
            <Card v-else-if="registrationStatus && registrationStatus.status === 'completed'" class="border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/30">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-green-700 dark:text-green-400">
                        <CheckCircle2 class="h-5 w-5" />
                        Ya estás registrado
                    </CardTitle>
                    <CardDescription class="dark:text-gray-400">
                        Tienes acceso a esta videoconferencia
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div v-if="registrationStatus.zoom_start_time">
                        <p class="text-sm font-medium text-muted-foreground">Hora de inicio</p>
                        <p class="text-sm dark:text-gray-300">{{ new Date(registrationStatus.zoom_start_time).toLocaleString('es-ES') }}</p>
                    </div>

                    <div v-if="registrationStatus.zoom_registrant_id">
                        <p class="text-sm font-medium text-muted-foreground">Token único de ingreso</p>
                        <p class="text-sm font-mono bg-gray-50 dark:bg-gray-800 px-2 py-1 rounded dark:text-gray-300">{{ registrationStatus.zoom_registrant_id }}</p>
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

                        <!-- Botones y enlace de respaldo -->
                        <div class="space-y-3">
                            <!-- Botón principal -->
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
                            
                            <!-- Enlace manual de respaldo (visible especialmente en móviles) -->
                            <div v-if="canJoinMeeting && registrationStatus.zoom_join_url" class="text-center">
                                <p class="text-xs text-muted-foreground mb-2">
                                    {{ isMobileDevice() ? '¿Problemas para abrir? Toca el enlace abajo:' : '¿No se abre automáticamente?' }}
                                </p>
                                <a 
                                    :href="registrationStatus.zoom_join_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800 underline"
                                >
                                    <ExternalLink class="h-3 w-3" />
                                    Abrir Zoom manualmente
                                </a>
                            </div>
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
            
            <!-- Instrucciones especiales para móviles -->
            <Alert v-if="isMobileDevice() && statusData?.existing_registration?.status === 'completed'" class="mt-4 border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/50">
                <Info class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                <AlertTitle class="text-blue-700 dark:text-blue-300">Consejo para dispositivos móviles</AlertTitle>
                <AlertDescription class="text-gray-700 dark:text-gray-300">
                    <ul class="list-disc list-inside space-y-1 mt-2 text-sm">
                        <li>Si tienes la app de Zoom instalada, el enlace debería abrirla automáticamente</li>
                        <li>Si no se abre, usa el enlace manual "Abrir Zoom manualmente"</li>
                        <li>Es posible que tu navegador te pida permiso para abrir Zoom</li>
                        <li>En iOS, podrías necesitar mantener presionado el enlace y seleccionar "Abrir"</li>
                    </ul>
                </AlertDescription>
            </Alert>

            <!-- Advertencia sobre compartir link -->
            <Alert variant="destructive" class="mt-4 dark:border-red-800 dark:bg-red-950/50">
                <AlertCircle class="h-4 w-4 dark:text-red-400" />
                <AlertTitle class="dark:text-red-400">Advertencia</AlertTitle>
                <AlertDescription class="dark:text-red-300">
                    Tu link es único, cuídalo, no lo compartas, si lo compartes podrías quedarte por fuera de la reunión, ya que solo se admite un usuario por link.
                </AlertDescription>
            </Alert>
        </div>
    </div>
</template>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>