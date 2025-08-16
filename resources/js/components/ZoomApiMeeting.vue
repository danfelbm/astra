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
import { ref, onMounted, computed } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';

interface Props {
    asambleaId: number;
}

const props = defineProps<Props>();

// Estado del componente
const isLoading = ref(false);
const isRegistering = ref(false);
const error = ref<string | null>(null);
const statusData = ref<any>(null);

// Helper para obtener route
const { route } = window as any;

/**
 * Obtener estado del registro para la asamblea
 */
const fetchStatus = async () => {
    try {
        isLoading.value = true;
        error.value = null;

        const response = await fetch(route('api.zoom.registrants.status', props.asambleaId), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();
        
        if (data.success) {
            statusData.value = data;
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
            // Actualizar estado después del registro exitoso
            await fetchStatus();
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

        const response = await fetch(route('api.zoom.registrants.destroy', props.asambleaId), {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();
        
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

// Computed para obtener el estado visual del registro
const registrationStatus = computed(() => {
    if (!statusData.value?.existing_registration) {
        return null;
    }

    const reg = statusData.value.existing_registration;
    return {
        ...reg,
        statusColor: reg.status === 'active' ? 'bg-green-100 text-green-800' :
                     reg.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                     'bg-gray-100 text-gray-800'
    };
});

// Computed para verificar si puede registrarse
const canRegister = computed(() => {
    return statusData.value?.can_register && !statusData.value?.existing_registration;
});

// Computed para verificar si puede acceder a la reunión
const canJoinMeeting = computed(() => {
    const reg = statusData.value?.existing_registration;
    const asambleaEnCurso = statusData.value?.asamblea?.estado === 'en_curso';
    return reg && reg.zoom_join_url && asambleaEnCurso;
});

// Computed para verificar si la asamblea está en curso
const isAsambleaEnCurso = computed(() => {
    return statusData.value?.asamblea?.estado === 'en_curso';
});

// Cargar estado inicial
onMounted(() => {
    fetchStatus();
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
            <!-- Usuario ya registrado -->
            <Card v-if="registrationStatus" class="border-green-200">
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
                        :disabled="isRegistering"
                        class="w-full"
                    >
                        <Loader2 v-if="isRegistering" class="mr-2 h-4 w-4 animate-spin" />
                        <Video v-else class="mr-2 h-4 w-4" />
                        Generar link de ingreso
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
                    Si compartes tu link de ingreso con alguien más corres el riesgo de no poder participar en la asamblea, 
                    pues solo un dispositivo puede estar conectado a la videoconferencia.
                </AlertDescription>
            </Alert>
        </div>
    </div>
</template>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>