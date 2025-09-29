<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import {
    Camera,
    Video,
    AudioLines,
    X,
    Circle,
    Square,
    Play,
    Pause,
    RotateCw,
    Check,
    AlertCircle
} from 'lucide-vue-next';
import type { TipoEvidencia } from '@modules/Proyectos/Resources/js/types/evidencias';

// Props y emits
const props = defineProps<{
    tipo: TipoEvidencia;
    isOpen: boolean;
}>();

const emit = defineEmits<{
    'capture': [blob: Blob];
    'close': [];
}>();

// Refs para elementos del DOM
const videoRef = ref<HTMLVideoElement>();
const canvasRef = ref<HTMLCanvasElement>();
const audioRef = ref<HTMLAudioElement>();

// Estado
const mediaStream = ref<MediaStream | null>(null);
const mediaRecorder = ref<MediaRecorder | null>(null);
const recordedChunks = ref<Blob[]>([]);
const isRecording = ref(false);
const isPaused = ref(false);
const hasMedia = ref(false);
const capturedBlob = ref<Blob | null>(null);
const recordingTime = ref(0);
const timerInterval = ref<number | null>(null);
const errorMessage = ref<string>('');
const isLoading = ref(false);

// Configuración por tipo
const captureConfig = computed(() => {
    const configs = {
        imagen: {
            icon: Camera,
            title: 'Capturar Foto',
            constraints: { video: { facingMode: 'environment' }, audio: false },
            mimeType: 'image/jpeg',
            fileExtension: 'jpg'
        },
        video: {
            icon: Video,
            title: 'Grabar Video',
            constraints: { video: { facingMode: 'environment' }, audio: true },
            mimeType: 'video/webm',
            fileExtension: 'webm'
        },
        audio: {
            icon: AudioLines,
            title: 'Grabar Audio',
            constraints: { video: false, audio: true },
            mimeType: 'audio/webm',
            fileExtension: 'webm'
        }
    };
    return configs[props.tipo];
});

// Métodos
const startMedia = async () => {
    try {
        isLoading.value = true;
        errorMessage.value = '';

        // Solicitar permisos y obtener stream
        mediaStream.value = await navigator.mediaDevices.getUserMedia(
            captureConfig.value.constraints
        );

        hasMedia.value = true;

        // Configurar video si aplica
        if (videoRef.value && (props.tipo === 'imagen' || props.tipo === 'video')) {
            videoRef.value.srcObject = mediaStream.value;
            await videoRef.value.play();
        }

        // Configurar grabadora para video/audio
        if (props.tipo !== 'imagen') {
            const mimeType = MediaRecorder.isTypeSupported('video/webm;codecs=vp9')
                ? 'video/webm;codecs=vp9'
                : 'video/webm';

            mediaRecorder.value = new MediaRecorder(mediaStream.value, {
                mimeType: mimeType
            });

            mediaRecorder.value.ondataavailable = (event) => {
                if (event.data && event.data.size > 0) {
                    recordedChunks.value.push(event.data);
                }
            };

            mediaRecorder.value.onstop = () => {
                const blob = new Blob(recordedChunks.value, {
                    type: captureConfig.value.mimeType
                });
                capturedBlob.value = blob;
                stopTimer();
            };
        }
    } catch (error) {
        console.error('Error al acceder a la cámara/micrófono:', error);
        errorMessage.value = 'No se pudo acceder a la cámara/micrófono. Por favor, verifica los permisos.';
        hasMedia.value = false;
    } finally {
        isLoading.value = false;
    }
};

const capturePhoto = () => {
    if (!videoRef.value || !canvasRef.value) return;

    const canvas = canvasRef.value;
    const video = videoRef.value;

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    const ctx = canvas.getContext('2d');
    if (ctx) {
        ctx.drawImage(video, 0, 0);

        canvas.toBlob((blob) => {
            if (blob) {
                capturedBlob.value = blob;
            }
        }, 'image/jpeg', 0.95);
    }
};

const startRecording = () => {
    if (!mediaRecorder.value) return;

    recordedChunks.value = [];
    mediaRecorder.value.start();
    isRecording.value = true;
    isPaused.value = false;
    startTimer();
};

const pauseRecording = () => {
    if (!mediaRecorder.value || !isRecording.value) return;

    if (isPaused.value) {
        mediaRecorder.value.resume();
        isPaused.value = false;
        startTimer();
    } else {
        mediaRecorder.value.pause();
        isPaused.value = true;
        stopTimer();
    }
};

const stopRecording = () => {
    if (!mediaRecorder.value || !isRecording.value) return;

    mediaRecorder.value.stop();
    isRecording.value = false;
    isPaused.value = false;
};

const startTimer = () => {
    stopTimer();
    timerInterval.value = window.setInterval(() => {
        recordingTime.value++;
    }, 1000);
};

const stopTimer = () => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value);
        timerInterval.value = null;
    }
};

const formatTime = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

const reset = () => {
    capturedBlob.value = null;
    recordedChunks.value = [];
    recordingTime.value = 0;
    isRecording.value = false;
    isPaused.value = false;
    stopTimer();

    // Reiniciar media stream si es necesario
    if (hasMedia.value && !mediaStream.value?.active) {
        startMedia();
    }
};

const confirmCapture = () => {
    if (capturedBlob.value) {
        emit('capture', capturedBlob.value);
        close();
    }
};

const close = () => {
    // Detener grabación si está activa
    if (isRecording.value) {
        stopRecording();
    }

    // Limpiar media stream
    if (mediaStream.value) {
        mediaStream.value.getTracks().forEach(track => track.stop());
        mediaStream.value = null;
    }

    // Limpiar estado
    reset();
    hasMedia.value = false;
    errorMessage.value = '';

    emit('close');
};

// Lifecycle
onMounted(() => {
    if (props.isOpen) {
        startMedia();
    }
});

onUnmounted(() => {
    if (mediaStream.value) {
        mediaStream.value.getTracks().forEach(track => track.stop());
    }
    stopTimer();
});
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80">
        <Card class="w-full max-w-2xl mx-4">
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle class="flex items-center gap-2">
                    <component :is="captureConfig.icon" class="w-5 h-5" />
                    {{ captureConfig.title }}
                </CardTitle>
                <Button
                    @click="close"
                    variant="ghost"
                    size="sm"
                    class="h-8 w-8 p-0"
                >
                    <X class="w-4 h-4" />
                </Button>
            </CardHeader>

            <CardContent class="space-y-4">
                <!-- Mensaje de error -->
                <Alert v-if="errorMessage" variant="destructive">
                    <AlertCircle class="h-4 w-4" />
                    <AlertDescription>{{ errorMessage }}</AlertDescription>
                </Alert>

                <!-- Loading -->
                <div v-if="isLoading" class="flex items-center justify-center py-12">
                    <RotateCw class="w-8 h-8 animate-spin text-muted-foreground" />
                </div>

                <!-- Vista previa -->
                <div v-else-if="hasMedia && !capturedBlob" class="relative">
                    <!-- Video para imagen/video -->
                    <video
                        v-if="tipo === 'imagen' || tipo === 'video'"
                        ref="videoRef"
                        class="w-full rounded-lg bg-black"
                        :class="{ 'opacity-50': isPaused }"
                        autoplay
                        playsinline
                        muted
                    />

                    <!-- Visualización de audio -->
                    <div v-else-if="tipo === 'audio'" class="bg-muted rounded-lg p-12 text-center">
                        <AudioLines class="w-16 h-16 mx-auto mb-4 text-muted-foreground"
                                   :class="{ 'animate-pulse': isRecording && !isPaused }" />
                        <p class="text-lg font-medium">
                            {{ isRecording ? (isPaused ? 'Pausado' : 'Grabando...') : 'Listo para grabar' }}
                        </p>
                    </div>

                    <!-- Temporizador para grabación -->
                    <div v-if="isRecording" class="absolute top-4 right-4 bg-red-600 text-white px-3 py-1 rounded-full flex items-center gap-2">
                        <Circle class="w-2 h-2 fill-current animate-pulse" />
                        <span class="text-sm font-medium">{{ formatTime(recordingTime) }}</span>
                    </div>

                    <!-- Canvas oculto para captura de foto -->
                    <canvas
                        v-if="tipo === 'imagen'"
                        ref="canvasRef"
                        class="hidden"
                    />
                </div>

                <!-- Vista previa de captura -->
                <div v-else-if="capturedBlob" class="relative">
                    <!-- Imagen capturada -->
                    <img
                        v-if="tipo === 'imagen'"
                        :src="URL.createObjectURL(capturedBlob)"
                        class="w-full rounded-lg"
                        alt="Foto capturada"
                    />

                    <!-- Video capturado -->
                    <video
                        v-else-if="tipo === 'video'"
                        :src="URL.createObjectURL(capturedBlob)"
                        class="w-full rounded-lg"
                        controls
                    />

                    <!-- Audio capturado -->
                    <div v-else-if="tipo === 'audio'" class="bg-muted rounded-lg p-6">
                        <audio
                            :src="URL.createObjectURL(capturedBlob)"
                            controls
                            class="w-full"
                        />
                        <p class="text-center mt-2 text-sm text-muted-foreground">
                            Duración: {{ formatTime(recordingTime) }}
                        </p>
                    </div>
                </div>

                <!-- Controles -->
                <div class="flex justify-center gap-2">
                    <!-- Sin media -->
                    <template v-if="!hasMedia && !isLoading">
                        <Button @click="startMedia">
                            <RotateCw class="w-4 h-4 mr-2" />
                            Reintentar
                        </Button>
                    </template>

                    <!-- Controles de captura/grabación -->
                    <template v-else-if="!capturedBlob">
                        <!-- Foto -->
                        <Button
                            v-if="tipo === 'imagen'"
                            @click="capturePhoto"
                            size="lg"
                            class="px-8"
                        >
                            <Camera class="w-5 h-5 mr-2" />
                            Tomar Foto
                        </Button>

                        <!-- Video/Audio -->
                        <template v-else>
                            <Button
                                v-if="!isRecording"
                                @click="startRecording"
                                variant="destructive"
                                size="lg"
                                class="px-8"
                            >
                                <Circle class="w-5 h-5 mr-2" />
                                Iniciar Grabación
                            </Button>

                            <template v-else>
                                <Button
                                    @click="pauseRecording"
                                    variant="outline"
                                    size="lg"
                                >
                                    <component :is="isPaused ? Play : Pause" class="w-5 h-5" />
                                </Button>

                                <Button
                                    @click="stopRecording"
                                    variant="destructive"
                                    size="lg"
                                    class="px-8"
                                >
                                    <Square class="w-5 h-5 mr-2" />
                                    Detener
                                </Button>
                            </template>
                        </template>
                    </template>

                    <!-- Controles post-captura -->
                    <template v-else>
                        <Button
                            @click="reset"
                            variant="outline"
                            size="lg"
                        >
                            <RotateCw class="w-4 h-4 mr-2" />
                            Repetir
                        </Button>

                        <Button
                            @click="confirmCapture"
                            size="lg"
                            class="px-8"
                        >
                            <Check class="w-5 h-5 mr-2" />
                            Usar Esta {{ tipo === 'imagen' ? 'Foto' : tipo === 'video' ? 'Video' : 'Audio' }}
                        </Button>
                    </template>
                </div>
            </CardContent>
        </Card>
    </div>
</template>