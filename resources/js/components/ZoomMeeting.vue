<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { router } from '@inertiajs/vue3';
import { 
    AlertCircle, 
    Clock, 
    Video, 
    VideoOff, 
    Volume2, 
    VolumeX,
    Loader2,
    Users,
    Settings
} from 'lucide-vue-next';
import { ref, onMounted, onUnmounted, watch } from 'vue';
// @ts-ignore - Zoom SDK no tiene tipos TypeScript nativos
import ZoomMtgEmbedded from '@zoom/meetingsdk/embedded';

interface Props {
    asambleaId: number;
    meetingId?: string;
    autoJoin?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    autoJoin: false
});

// Estado del componente
const isLoading = ref(false);
const isInitialized = ref(false);
const isJoined = ref(false);
const error = ref<string | null>(null);
const meetingInfo = ref<any>(null);
const accessInfo = ref<any>(null);

// Cliente de Zoom
let zoomClient: any = null;

// Referencias DOM
const meetingContainer = ref<HTMLElement>();

// Estado de la reunión
const meetingState = ref({
    audio: true,
    video: false,
    participants: 0,
    isHost: false
});

// Helper para obtener route
const { route } = window as any;

/**
 * Verificar el acceso a la reunión
 */
const checkAccess = async () => {
    try {
        const response = await fetch(route('api.zoom.check-access', props.asambleaId), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            accessInfo.value = data;
            return data.can_join;
        } else {
            error.value = data.error || 'Error verificando acceso';
            return false;
        }
    } catch (err) {
        console.error('Error checking access:', err);
        error.value = 'Error de conexión al verificar acceso';
        return false;
    }
};

/**
 * Obtener información de la reunión
 */
const getMeetingInfo = async () => {
    try {
        const response = await fetch(route('api.zoom.meeting-info', props.asambleaId), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            meetingInfo.value = data;
            meetingState.value.isHost = data.user.role === 1;
            return data;
        } else {
            error.value = data.error || 'Error obteniendo información de la reunión';
            return null;
        }
    } catch (err) {
        console.error('Error getting meeting info:', err);
        error.value = 'Error de conexión al obtener información';
        return null;
    }
};

/**
 * Generar signature para unirse a la reunión
 */
const generateSignature = async (meetingNumber: string, role: number) => {
    try {
        const response = await fetch(route('api.zoom.signature'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                meetingNumber,
                role,
                videoWebRtcMode: 1
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            return data;
        } else {
            error.value = data.error || 'Error generando signature';
            return null;
        }
    } catch (err) {
        console.error('Error generating signature:', err);
        error.value = 'Error de conexión al generar signature';
        return null;
    }
};

/**
 * Inicializar cliente de Zoom
 */
const initializeZoom = async () => {
    if (isInitialized.value || !meetingContainer.value) return;
    
    try {
        zoomClient = ZoomMtgEmbedded.createClient();
        
        // Configurar eventos
        zoomClient.on('meeting-status', (data: any) => {
            console.log('Meeting status:', data);
            if (data.meetingStatus === 2) { // MEETING_STATUS_ENDED
                isJoined.value = false;
            }
        });
        
        zoomClient.on('active-speaker', (data: any) => {
            console.log('Active speaker:', data);
        });
        
        zoomClient.on('user-added', (data: any) => {
            console.log('User added:', data);
            meetingState.value.participants++;
        });
        
        zoomClient.on('user-removed', (data: any) => {
            console.log('User removed:', data);
            meetingState.value.participants = Math.max(0, meetingState.value.participants - 1);
        });
        
        // Inicializar cliente
        await zoomClient.init({
            zoomAppRoot: meetingContainer.value,
            language: 'es-ES',
            patchJsMedia: true,
            leaveOnPageUnload: true
        });
        
        isInitialized.value = true;
        console.log('Zoom client initialized successfully');
        
    } catch (err) {
        console.error('Error initializing Zoom:', err);
        error.value = 'Error inicializando el cliente de videoconferencia';
    }
};

/**
 * Unirse a la reunión
 */
const joinMeeting = async () => {
    if (!isInitialized.value || isJoined.value || !meetingInfo.value) return;
    
    isLoading.value = true;
    error.value = null;
    
    try {
        // Generar signature
        const signatureData = await generateSignature(
            meetingInfo.value.meeting.id,
            meetingInfo.value.user.role
        );
        
        if (!signatureData) {
            return;
        }
        
        // Unirse a la reunión
        await zoomClient.join({
            signature: signatureData.signature,
            meetingNumber: signatureData.meetingNumber,
            password: signatureData.password,
            userName: signatureData.userName,
            userEmail: signatureData.userEmail
        });
        
        isJoined.value = true;
        console.log('Successfully joined meeting');
        
    } catch (err) {
        console.error('Error joining meeting:', err);
        error.value = 'Error al unirse a la videoconferencia';
    } finally {
        isLoading.value = false;
    }
};

/**
 * Salir de la reunión
 */
const leaveMeeting = async () => {
    if (!zoomClient || !isJoined.value) return;
    
    try {
        await zoomClient.leave();
        isJoined.value = false;
        console.log('Left meeting successfully');
    } catch (err) {
        console.error('Error leaving meeting:', err);
    }
};

/**
 * Controlar audio
 */
const toggleAudio = async () => {
    if (!zoomClient || !isJoined.value) return;
    
    try {
        if (meetingState.value.audio) {
            await zoomClient.mute();
        } else {
            await zoomClient.unmute();
        }
        meetingState.value.audio = !meetingState.value.audio;
    } catch (err) {
        console.error('Error toggling audio:', err);
    }
};

/**
 * Controlar video
 */
const toggleVideo = async () => {
    if (!zoomClient || !isJoined.value) return;
    
    try {
        if (meetingState.value.video) {
            await zoomClient.stopVideo();
        } else {
            await zoomClient.startVideo();
        }
        meetingState.value.video = !meetingState.value.video;
    } catch (err) {
        console.error('Error toggling video:', err);
    }
};

/**
 * Inicializar componente
 */
const initialize = async () => {
    isLoading.value = true;
    error.value = null;
    
    try {
        // Verificar acceso
        const canAccess = await checkAccess();
        if (!canAccess) {
            return;
        }
        
        // Obtener información de la reunión
        const info = await getMeetingInfo();
        if (!info) {
            return;
        }
        
        // Inicializar Zoom
        await initializeZoom();
        
        // Auto-unirse si está habilitado
        if (props.autoJoin && isInitialized.value) {
            await joinMeeting();
        }
        
    } catch (err) {
        console.error('Error during initialization:', err);
        error.value = 'Error inicializando la videoconferencia';
    } finally {
        isLoading.value = false;
    }
};

// Watchers
watch(() => props.asambleaId, () => {
    if (props.asambleaId) {
        initialize();
    }
});

// Lifecycle
onMounted(() => {
    if (props.asambleaId) {
        initialize();
    }
});

onUnmounted(() => {
    if (zoomClient && isJoined.value) {
        leaveMeeting();
    }
});

// Exponer métodos para uso externo
defineExpose({
    joinMeeting,
    leaveMeeting,
    toggleAudio,
    toggleVideo,
    isJoined,
    isLoading,
    error
});
</script>

<template>
    <div class="zoom-meeting-container">
        <!-- Estado de carga inicial -->
        <Card v-if="isLoading && !isInitialized" class="w-full">
            <CardHeader>
                <div class="flex items-center gap-2">
                    <Loader2 class="h-5 w-5 animate-spin" />
                    <CardTitle>Iniciando Videoconferencia</CardTitle>
                </div>
                <CardDescription>
                    Preparando la conexión con la reunión...
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="space-y-2">
                    <Skeleton class="h-4 w-3/4" />
                    <Skeleton class="h-4 w-1/2" />
                </div>
            </CardContent>
        </Card>

        <!-- Error de acceso o conexión -->
        <Alert v-else-if="error" variant="destructive">
            <AlertCircle class="h-4 w-4" />
            <AlertTitle>Error en Videoconferencia</AlertTitle>
            <AlertDescription>
                {{ error }}
                <Button 
                    v-if="accessInfo?.available_at"
                    variant="outline" 
                    size="sm" 
                    class="mt-2"
                    @click="initialize"
                >
                    Reintentar
                </Button>
            </AlertDescription>
        </Alert>

        <!-- No disponible aún -->
        <Alert v-else-if="accessInfo && !accessInfo.can_join" class="border-yellow-200">
            <Clock class="h-4 w-4 text-yellow-600" />
            <AlertTitle>Videoconferencia No Disponible</AlertTitle>
            <AlertDescription>
                {{ accessInfo.reason }}
                <div v-if="accessInfo.available_at" class="mt-2 text-sm">
                    Disponible desde: {{ new Date(accessInfo.available_at).toLocaleString('es-ES') }}
                </div>
            </AlertDescription>
        </Alert>

        <!-- Controles antes de unirse -->
        <Card v-else-if="isInitialized && !isJoined" class="w-full">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Video class="h-5 w-5" />
                    Videoconferencia Disponible
                </CardTitle>
                <CardDescription>
                    {{ meetingInfo?.meeting?.topic || 'Asamblea' }}
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div v-if="meetingInfo" class="text-sm text-muted-foreground space-y-1">
                    <div class="flex items-center gap-2">
                        <Users class="h-4 w-4" />
                        <span>Rol: {{ meetingInfo.user.role === 1 ? 'Moderador' : 'Participante' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Clock class="h-4 w-4" />
                        <span>Duración: {{ meetingInfo.meeting.duration }} minutos</span>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <Button 
                        @click="joinMeeting" 
                        :disabled="isLoading"
                        class="flex-1"
                    >
                        <Video v-if="!isLoading" class="mr-2 h-4 w-4" />
                        <Loader2 v-else class="mr-2 h-4 w-4 animate-spin" />
                        {{ isLoading ? 'Conectando...' : 'Unirse a la Videoconferencia' }}
                    </Button>
                </div>
                
                <div class="text-xs text-muted-foreground">
                    Al unirte aceptas las condiciones de uso de la plataforma de videoconferencia.
                </div>
            </CardContent>
        </Card>

        <!-- Reunión activa -->
        <div v-else-if="isJoined" class="space-y-4">
            <!-- Controles de la reunión -->
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-sm">
                            <div class="h-2 w-2 bg-green-500 rounded-full animate-pulse"></div>
                            <span>En videoconferencia</span>
                            <span v-if="meetingState.participants > 0" class="text-muted-foreground">
                                ({{ meetingState.participants }} participantes)
                            </span>
                        </div>
                        
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="toggleAudio"
                                :class="{
                                    'bg-red-50 text-red-600 border-red-200': !meetingState.audio
                                }"
                            >
                                <Volume2 v-if="meetingState.audio" class="h-4 w-4" />
                                <VolumeX v-else class="h-4 w-4" />
                            </Button>
                            
                            <Button
                                variant="outline"
                                size="sm"
                                @click="toggleVideo"
                                :class="{
                                    'bg-red-50 text-red-600 border-red-200': !meetingState.video
                                }"
                            >
                                <Video v-if="meetingState.video" class="h-4 w-4" />
                                <VideoOff v-else class="h-4 w-4" />
                            </Button>
                            
                            <Button
                                variant="destructive"
                                size="sm"
                                @click="leaveMeeting"
                            >
                                Salir
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
            
            <!-- Contenedor de la reunión -->
            <div 
                ref="meetingContainer" 
                id="meetingSDKElement"
                class="w-full h-[600px] bg-gray-100 rounded-lg overflow-hidden"
            >
                <!-- El SDK de Zoom renderizará aquí -->
            </div>
        </div>

        <!-- Contenedor de inicialización (oculto) -->
        <div 
            v-show="false"
            ref="meetingContainer" 
            id="meetingSDKElement"
        >
            <!-- Zoom Meeting SDK Component View se renderiza aquí -->
        </div>
    </div>
</template>

<style scoped>
.zoom-meeting-container {
    width: 100%;
    max-width: 100%;
}

#meetingSDKElement {
    width: 100%;
    height: 100%;
}
</style>