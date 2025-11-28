<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, Teleport } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import UserLayout from '@modules/Core/Resources/js/layouts/UserLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Textarea } from '@modules/Core/Resources/js/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@modules/Core/Resources/js/components/ui/select';
import { Checkbox } from '@modules/Core/Resources/js/components/ui/checkbox';
import { Progress } from '@modules/Core/Resources/js/components/ui/progress';
import FileUploadField from '@modules/Core/Resources/js/components/forms/FileUploadField.vue';
import EntregableSelector from '@modules/Proyectos/Resources/js/components/EntregableSelector.vue';
import { useAutoSave } from '@modules/Core/Resources/js/composables/useAutoSave';
import { useFileUpload } from '@modules/Core/Resources/js/composables/useFileUpload';
import { toast } from 'vue-sonner';
import { ArrowLeft, Save, CheckCircle, Clock, AlertCircle, PanelLeft, Camera, Mic, Upload, FileText, Image, Video, Music, X, Trash2 } from 'lucide-vue-next';
import type { Contrato } from '@modules/Proyectos/Resources/js/types/contratos';
import type { ObligacionContrato } from '@modules/Proyectos/Resources/js/types/obligaciones';
import type { Entregable } from '@modules/Proyectos/Resources/js/types/hitos';
import type { TipoEvidencia, EvidenciaFormData, TipoEvidenciaOption, Evidencia } from '@modules/Proyectos/Resources/js/types/evidencias';
import type { BreadcrumbItemType } from '@modules/Core/Resources/js/types';

interface Props {
    contrato: Contrato;
    evidencia: Evidencia & {
        entregables: Array<{
            id: number;
            nombre: string;
            hito: string;
        }>;
    };
    obligaciones: ObligacionContrato[];
    entregables: Array<{
        id: number;
        nombre: string;
        hito: string;
        estado: string;
        fecha_fin: string | null;
    }>;
    tiposEvidencia: TipoEvidenciaOption[];
}

const props = defineProps<Props>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Dashboard', href: '/miembro/dashboard' },
    { title: 'Mis Contratos', href: '/miembro/mis-contratos' },
    { title: props.contrato.nombre, href: `/miembro/mis-contratos/${props.contrato.id}` },
    { title: 'Evidencias', href: `/miembro/mis-contratos/${props.contrato.id}/evidencias` },
    { title: 'Editar Evidencia', href: '#' }
];

// Composable para archivos
const { uploadFiles } = useFileUpload();

// Estado del formulario - inicializar con datos existentes
const form = useForm<EvidenciaFormData>({
    obligacion_id: props.evidencia.obligacion_id,
    tipo_evidencia: props.evidencia.tipo_evidencia,
    archivo_path: props.evidencia.archivo_path,
    archivo_nombre: props.evidencia.archivo_nombre,
    archivos_paths: props.evidencia.archivos_paths || (props.evidencia.archivo_path ? [props.evidencia.archivo_path] : []),
    archivos_nombres: props.evidencia.archivos_nombres || (props.evidencia.archivo_nombre ? [props.evidencia.archivo_nombre] : []),
    descripcion: props.evidencia.descripcion || '',
    entregable_ids: props.evidencia.entregables?.map(e => e.id) || [],
    metadata: props.evidencia.metadata || null
});

// Array para el FileUploadField (necesita ser un array, no string)
const archivosSubidos = ref<string[]>(
    props.evidencia.archivos_paths || (props.evidencia.archivo_path ? [props.evidencia.archivo_path] : [])
);

// Estados locales
const isBottomBarVisible = ref(false);
const autoSaveStatus = ref<'idle' | 'saving' | 'saved' | 'error'>('idle');
const hasUnsavedChanges = ref(false);
const pendingFile = ref<File | null>(null);
const capturedBlob = ref<Blob | null>(null);
const isCaptureModalOpen = ref(false);
const captureMode = ref<'photo' | 'video' | 'audio'>('photo');

// MediaRecorder para captura
let mediaRecorder: MediaRecorder | null = null;
let stream: MediaStream | null = null;
const recordingChunks = ref<Blob[]>([]);
const isRecording = ref(false);
const recordingTime = ref(0);
let recordingInterval: NodeJS.Timeout | null = null;

// Computed
const selectedObligacion = computed(() => {
    return props.obligaciones.find(o => o.id === form.obligacion_id);
});

const entregablesDisponibles = computed(() => {
    return props.entregables.filter(e => e.estado !== 'completado');
});

const entregablesSeleccionados = computed(() => {
    return props.entregables.filter(e => form.entregable_ids.includes(e.id));
});

const canSubmit = computed(() => {
    return form.obligacion_id && form.tipo_evidencia && !form.processing;
});

const tipoEvidenciaIcon = computed(() => {
    const icons = {
        'imagen': Image,
        'video': Video,
        'audio': Music,
        'documento': FileText
    };
    return icons[form.tipo_evidencia || 'documento'] || FileText;
});

const currentFileUrl = computed(() => {
    if (capturedBlob.value) {
        return URL.createObjectURL(capturedBlob.value);
    }
    return props.evidencia.archivo_url;
});

// Configuración del autosave (datos vienen directamente del frontend)
const formDataRef = computed(() => form.data());
const { startAutoSave, stopAutoSave } = useAutoSave(
    formDataRef,
    (data) => {
        return router.post(
            route('user.mis-contratos.evidencias.autosave', props.contrato.id),
            data,
            {
                preserveScroll: true,
                preserveState: true,
                only: []
            }
        );
    },
    {
        interval: 30000,
        onStatusChange: (status) => {
            autoSaveStatus.value = status;
        }
    }
);

// Watchers
watch(() => form.data(), () => {
    hasUnsavedChanges.value = form.isDirty;
}, { deep: true });

// Watcher para limpiar archivos al cambiar tipo de evidencia
watch(() => form.tipo_evidencia, (newTipo, oldTipo) => {
    if (oldTipo && newTipo && newTipo !== oldTipo) {
        // Confirmar limpieza si hay archivos
        const tieneArchivos = form.archivos_paths.length > 0 ||
                            form.archivo_path ||
                            pendingFile.value ||
                            capturedBlob.value ||
                            archivosSubidos.value.length > 0;

        if (tieneArchivos) {
            const confirmar = confirm(
                `¿Estás seguro de cambiar el tipo de evidencia? Se perderán los archivos seleccionados para "${oldTipo}".`
            );

            if (!confirmar) {
                // Revertir el cambio
                form.tipo_evidencia = oldTipo;
                return;
            }
        }

        // Limpiar todos los archivos
        form.archivo_path = null;
        form.archivo_nombre = null;
        form.archivos_paths = [];
        form.archivos_nombres = [];
        form.metadata = null;
        pendingFile.value = null;
        capturedBlob.value = null;
        archivosSubidos.value = [];

        toast.info(`Tipo de evidencia cambiado a "${newTipo}". Archivos limpiados.`);
    }
});

// Funciones
const updateBottomBarVisibility = () => {
    isBottomBarVisible.value = hasUnsavedChanges.value || form.processing;
};

const handleFilesSelected = async (files: File[]) => {
    if (files.length > 0) {
        // Verificar límite máximo de archivos
        const totalActual = form.archivos_paths.length;
        const nuevosArchivos = files.length;

        if (totalActual + nuevosArchivos > 10) {
            toast.error(`No puedes subir más de 10 archivos. Actualmente tienes ${totalActual} archivo(s).`);
            return;
        }

        if (!form.tipo_evidencia) {
            // Autodetectar tipo basado en el primer archivo
            const file = files[0];
            if (file.type.startsWith('image/')) {
                form.tipo_evidencia = 'imagen';
            } else if (file.type.startsWith('video/')) {
                form.tipo_evidencia = 'video';
            } else if (file.type.startsWith('audio/')) {
                form.tipo_evidencia = 'audio';
            } else {
                form.tipo_evidencia = 'documento';
            }
        }

        // Subir archivos inmediatamente
        try {
            toast.info(`Subiendo ${files.length} archivo(s)...`);

            const uploadedFiles = await uploadFiles(files, {
                module: 'evidencias',
                fieldId: form.tipo_evidencia || 'archivo',
                onProgress: (fileName: string, progress: number) => {
                    // El componente FileUploadField manejará el progreso visualmente
                },
            });

            if (uploadedFiles.length > 0) {
                // Agregar los nuevos archivos a los arrays existentes
                const nuevasPaths = [...form.archivos_paths, ...uploadedFiles.map(f => f.path)];
                const nuevosNombres = [...form.archivos_nombres, ...uploadedFiles.map(f => f.name)];

                form.archivos_paths = nuevasPaths;
                form.archivos_nombres = nuevosNombres;

                // Para retrocompatibilidad, establecer el primer archivo como archivo_path
                if (!form.archivo_path && nuevasPaths.length > 0) {
                    form.archivo_path = nuevasPaths[0];
                    form.archivo_nombre = nuevosNombres[0];
                    form.metadata = uploadedFiles[0].metadata;
                }

                // Sincronizar con el array del FileUploadField
                archivosSubidos.value = nuevasPaths;

                toast.success(`${uploadedFiles.length} archivo(s) subido(s) exitosamente. Total: ${nuevasPaths.length}/10`);
            }
        } catch (error) {
            console.error('Error al subir archivos:', error);
            toast.error('Error al subir archivos');
        }
    } else {
        // Si no hay archivos, limpiar todo
        pendingFile.value = null;
        form.archivo_path = null;
        form.archivo_nombre = null;
        form.archivos_paths = [];
        form.archivos_nombres = [];
        form.metadata = null;
        archivosSubidos.value = [];
    }
};

const removeCurrentFile = () => {
    pendingFile.value = null;
    capturedBlob.value = null;
    form.archivo_path = null;
    form.archivo_nombre = null;
    form.archivos_paths = [];
    form.archivos_nombres = [];
    form.metadata = null;
    archivosSubidos.value = [];
};

const openCaptureModal = (mode: 'photo' | 'video' | 'audio') => {
    captureMode.value = mode;
    isCaptureModalOpen.value = true;
    setupCapture();
};

const setupCapture = async () => {
    try {
        const constraints: MediaStreamConstraints = {
            video: captureMode.value === 'photo' || captureMode.value === 'video',
            audio: captureMode.value === 'video' || captureMode.value === 'audio'
        };

        stream = await navigator.mediaDevices.getUserMedia(constraints);

        if (captureMode.value === 'photo' || captureMode.value === 'video') {
            const video = document.getElementById('captureVideo') as HTMLVideoElement;
            if (video) {
                video.srcObject = stream;
            }
        }
    } catch (error) {
        console.error('Error accessing media devices:', error);
        toast.error('No se pudo acceder a la cámara/micrófono');
        closeCaptureModal();
    }
};

const capturePhoto = () => {
    const video = document.getElementById('captureVideo') as HTMLVideoElement;
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    const ctx = canvas.getContext('2d');
    if (ctx) {
        ctx.drawImage(video, 0, 0);
        canvas.toBlob((blob) => {
            if (blob) {
                capturedBlob.value = blob;
                form.archivo_nombre = `foto_${Date.now()}.jpg`;
                form.tipo_evidencia = 'imagen';
                closeCaptureModal();
            }
        }, 'image/jpeg', 0.8);
    }
};

const startRecording = () => {
    if (!stream) return;

    recordingChunks.value = [];
    mediaRecorder = new MediaRecorder(stream);

    mediaRecorder.ondataavailable = (event) => {
        if (event.data.size > 0) {
            recordingChunks.value.push(event.data);
        }
    };

    mediaRecorder.onstop = () => {
        const blob = new Blob(recordingChunks.value, {
            type: captureMode.value === 'video' ? 'video/webm' : 'audio/webm'
        });
        capturedBlob.value = blob;

        const extension = captureMode.value === 'video' ? 'webm' : 'webm';
        form.archivo_nombre = `${captureMode.value}_${Date.now()}.${extension}`;
        form.tipo_evidencia = captureMode.value as TipoEvidencia;

        stopRecordingTimer();
        closeCaptureModal();
    };

    mediaRecorder.start();
    isRecording.value = true;
    startRecordingTimer();
};

const stopRecording = () => {
    if (mediaRecorder && isRecording.value) {
        mediaRecorder.stop();
        isRecording.value = false;
    }
};

const startRecordingTimer = () => {
    recordingTime.value = 0;
    recordingInterval = setInterval(() => {
        recordingTime.value += 1;
    }, 1000);
};

const stopRecordingTimer = () => {
    if (recordingInterval) {
        clearInterval(recordingInterval);
        recordingInterval = null;
    }
    recordingTime.value = 0;
};

const closeCaptureModal = () => {
    isCaptureModalOpen.value = false;
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    if (mediaRecorder) {
        mediaRecorder = null;
    }
    stopRecordingTimer();
    isRecording.value = false;
};

const formatTime = (seconds: number): string => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

const submitForm = async () => {
    if (!canSubmit.value) return;

    // Verificar que hay archivos subidos (nuevo sistema o legacy)
    if (form.archivos_paths.length === 0 && !form.archivo_path) {
        toast.error('Por favor seleccione o capture al menos un archivo');
        return;
    }

    // Los archivos ya se suben inmediatamente, solo enviar el formulario
    form.put(route('user.mis-contratos.evidencias.update', [props.contrato.id, props.evidencia.id]), {
        onSuccess: () => {
            toast.success(`Evidencia actualizada exitosamente con ${form.archivos_paths.length} archivo(s)`);
            hasUnsavedChanges.value = false;
            stopAutoSave();
        },
        onError: (errors) => {
            console.error('Errores:', errors);
            toast.error('Error al actualizar la evidencia');
        }
    });
};

const saveDraft = () => {
    router.post(
        route('user.mis-contratos.evidencias.autosave', props.contrato.id),
        form.data(),
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success('Borrador guardado');
                autoSaveStatus.value = 'saved';
                setTimeout(() => {
                    autoSaveStatus.value = 'idle';
                }, 2000);
            },
            onError: () => {
                toast.error('Error al guardar borrador');
                autoSaveStatus.value = 'error';
            }
        }
    );
};

// Lifecycle
onMounted(() => {
    watch(() => hasUnsavedChanges.value, updateBottomBarVisibility, { immediate: true });
    startAutoSave();
});

onUnmounted(() => {
    stopAutoSave();
    closeCaptureModal();
});
</script>

<template>
    <UserLayout :breadcrumbs="breadcrumbs">
        <Head title="Editar Evidencia" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-start">
                <div>
                    <Link
                        :href="route('user.mis-contratos.evidencias.show', [contrato.id, evidencia.id])"
                        class="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-2"
                    >
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Volver a Ver Evidencia
                    </Link>
                    <h2 class="text-3xl font-bold tracking-tight">Editar Evidencia</h2>
                    <p class="text-muted-foreground">
                        Modificar evidencia para: <strong>{{ contrato.nombre }}</strong>
                    </p>
                </div>

                <!-- Estado del autosave -->
                <div class="flex items-center gap-2">
                    <div v-if="autoSaveStatus === 'saving'" class="flex items-center text-sm text-muted-foreground">
                        <Clock class="w-4 h-4 mr-1 animate-spin" />
                        Guardando...
                    </div>
                    <div v-else-if="autoSaveStatus === 'saved'" class="flex items-center text-sm text-green-600">
                        <CheckCircle class="w-4 h-4 mr-1" />
                        Guardado
                    </div>
                    <div v-else-if="autoSaveStatus === 'error'" class="flex items-center text-sm text-red-600">
                        <AlertCircle class="w-4 h-4 mr-1" />
                        Error al guardar
                    </div>
                </div>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Columna principal -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Obligación contractual -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Obligación Contractual</CardTitle>
                                <CardDescription>
                                    Selecciona la obligación a la que corresponde esta evidencia
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div>
                                        <Label for="obligacion_id">Obligación *</Label>
                                        <Select v-model="form.obligacion_id" required>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar obligación..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="obligacion in obligaciones"
                                                    :key="obligacion.id"
                                                    :value="obligacion.id"
                                                >
                                                    {{ obligacion.nombre }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div v-if="selectedObligacion?.descripcion" class="p-3 bg-muted/30 rounded-lg">
                                        <p class="text-sm text-muted-foreground">{{ selectedObligacion.descripcion }}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Archivo de evidencia -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <component :is="tipoEvidenciaIcon" class="w-5 h-5" />
                                    Archivo de Evidencia
                                </CardTitle>
                                <CardDescription>
                                    Sube un archivo o captura directamente desde tu dispositivo
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Tipo de evidencia -->
                                <div>
                                    <Label for="tipo_evidencia">Tipo de Evidencia *</Label>
                                    <Select v-model="form.tipo_evidencia" required>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar tipo..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="tipo in tiposEvidencia"
                                                :key="tipo.value"
                                                :value="tipo.value"
                                            >
                                                {{ tipo.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Vista del archivo actual o subida -->
                                <div v-if="currentFileUrl" class="space-y-4">
                                    <div class="relative bg-muted/30 rounded-lg p-4">
                                        <!-- Imagen -->
                                        <div v-if="form.tipo_evidencia === 'imagen'" class="text-center">
                                            <img
                                                :src="currentFileUrl"
                                                alt="Vista previa"
                                                class="max-w-full max-h-64 mx-auto rounded-lg shadow-sm"
                                            />
                                        </div>

                                        <!-- Video -->
                                        <div v-else-if="form.tipo_evidencia === 'video'" class="text-center">
                                            <video
                                                :src="currentFileUrl"
                                                controls
                                                class="max-w-full max-h-64 mx-auto rounded-lg shadow-sm"
                                            >
                                                Tu navegador no soporta la reproducción de video.
                                            </video>
                                        </div>

                                        <!-- Audio -->
                                        <div v-else-if="form.tipo_evidencia === 'audio'" class="flex flex-col items-center gap-4">
                                            <Music class="w-12 h-12 text-muted-foreground" />
                                            <audio
                                                :src="currentFileUrl"
                                                controls
                                                class="w-full max-w-md"
                                            >
                                                Tu navegador no soporta la reproducción de audio.
                                            </audio>
                                        </div>

                                        <!-- Documento -->
                                        <div v-else class="flex items-center justify-center gap-4 py-8">
                                            <FileText class="w-12 h-12 text-muted-foreground" />
                                            <div class="text-center">
                                                <p class="font-medium">{{ form.archivo_nombre }}</p>
                                                <p class="text-sm text-muted-foreground">Documento</p>
                                            </div>
                                        </div>

                                        <!-- Botón para eliminar archivo -->
                                        <Button
                                            type="button"
                                            @click="removeCurrentFile"
                                            variant="destructive"
                                            size="sm"
                                            class="absolute top-2 right-2"
                                        >
                                            <X class="w-4 h-4" />
                                        </Button>
                                    </div>

                                    <p class="text-sm text-muted-foreground text-center">
                                        {{ form.archivo_nombre }}
                                    </p>
                                </div>

                                <!-- Opciones de carga/captura -->
                                <div v-else class="space-y-4">
                                    <FileUploadField
                                        v-model="archivosSubidos"
                                        @filesSelected="handleFilesSelected"
                                        :label="''"
                                        :description="`Selecciona archivos desde tu dispositivo (${form.archivos_paths.length}/10 archivos)`"
                                        :accept="form.tipo_evidencia ? `${form.tipo_evidencia}/*` : '*/*'"
                                        :max-file-size="50"
                                        :multiple="true"
                                        :max-files="10"
                                        :disabled="form.processing"
                                        module="evidencias"
                                        :field-id="form.tipo_evidencia || 'archivo'"
                                        :auto-upload="false"
                                    />

                                    <div class="text-center">
                                        <div class="relative">
                                            <div class="absolute inset-0 flex items-center">
                                                <span class="w-full border-t" />
                                            </div>
                                            <div class="relative flex justify-center text-xs uppercase">
                                                <span class="bg-background px-2 text-muted-foreground">O capturar directamente</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-3 gap-3">
                                        <Button
                                            type="button"
                                            @click="openCaptureModal('photo')"
                                            variant="outline"
                                            class="flex flex-col items-center gap-2 h-auto py-4"
                                        >
                                            <Camera class="w-6 h-6" />
                                            <span class="text-xs">Foto</span>
                                        </Button>
                                        <Button
                                            type="button"
                                            @click="openCaptureModal('video')"
                                            variant="outline"
                                            class="flex flex-col items-center gap-2 h-auto py-4"
                                        >
                                            <Video class="w-6 h-6" />
                                            <span class="text-xs">Video</span>
                                        </Button>
                                        <Button
                                            type="button"
                                            @click="openCaptureModal('audio')"
                                            variant="outline"
                                            class="flex flex-col items-center gap-2 h-auto py-4"
                                        >
                                            <Mic class="w-6 h-6" />
                                            <span class="text-xs">Audio</span>
                                        </Button>
                                    </div>
                                </div>

                                <!-- Descripción -->
                                <div>
                                    <Label for="descripcion">Descripción</Label>
                                    <Textarea
                                        v-model="form.descripcion"
                                        placeholder="Describe brevemente esta evidencia..."
                                        rows="3"
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Columna lateral -->
                    <div class="space-y-6">
                        <!-- Entregables -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Entregables Asociados</CardTitle>
                                <CardDescription>
                                    Opcional: Asocia esta evidencia con entregables específicos
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <EntregableSelector
                                    v-model="form.entregable_ids"
                                    :entregables="entregables"
                                    label=""
                                    description=""
                                    height="12rem"
                                    :disabled="form.processing"
                                />
                            </CardContent>
                        </Card>

                        <!-- Resumen -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Resumen</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Obligación</p>
                                    <p class="text-sm">{{ selectedObligacion?.nombre || 'No seleccionada' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Tipo</p>
                                    <p class="text-sm">{{ form.tipo_evidencia || 'No seleccionado' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Archivo</p>
                                    <p class="text-sm">{{ form.archivo_nombre || 'Ninguno' }}</p>
                                </div>
                                <div v-if="entregablesSeleccionados.length > 0">
                                    <p class="text-sm font-medium text-muted-foreground">Entregables ({{ entregablesSeleccionados.length }})</p>
                                    <div class="space-y-1">
                                        <p
                                            v-for="entregable in entregablesSeleccionados"
                                            :key="entregable.id"
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ entregable.nombre }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </form>
        </div>

        <!-- Barra inferior fija -->
        <Teleport to="body">
            <div v-if="isBottomBarVisible" class="fixed bottom-0 left-0 right-0 bg-background border-t border-border shadow-lg z-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between py-4">
                        <div class="flex items-center gap-4">
                            <Button
                                type="button"
                                @click="saveDraft"
                                variant="outline"
                                :disabled="form.processing"
                            >
                                <Save class="w-4 h-4 mr-2" />
                                Guardar Borrador
                            </Button>

                            <div v-if="autoSaveStatus === 'saving'" class="flex items-center text-sm text-muted-foreground">
                                <Clock class="w-4 h-4 mr-1 animate-spin" />
                                Autoguardando...
                            </div>
                            <div v-else-if="autoSaveStatus === 'saved'" class="flex items-center text-sm text-green-600">
                                <CheckCircle class="w-4 h-4 mr-1" />
                                Autoguardado
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <Button
                                type="button"
                                @click="router.visit(route('user.mis-contratos.evidencias.show', [contrato.id, evidencia.id]))"
                                variant="outline"
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="button"
                                @click="submitForm"
                                :disabled="!canSubmit"
                                :loading="form.processing"
                            >
                                <Upload class="w-4 h-4 mr-2" />
                                Actualizar Evidencia
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal de captura -->
        <Teleport to="body">
            <div v-if="isCaptureModalOpen" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50">
                <div class="bg-background rounded-lg p-6 max-w-2xl w-full mx-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">
                            Capturar {{ captureMode === 'photo' ? 'Foto' : captureMode === 'video' ? 'Video' : 'Audio' }}
                        </h3>
                        <Button @click="closeCaptureModal" variant="outline" size="sm">
                            <X class="w-4 h-4" />
                        </Button>
                    </div>

                    <div class="space-y-4">
                        <div v-if="captureMode === 'photo' || captureMode === 'video'" class="relative">
                            <video
                                id="captureVideo"
                                autoplay
                                muted
                                class="w-full rounded-lg bg-black"
                            ></video>
                        </div>

                        <div v-if="isRecording" class="flex items-center justify-center gap-2 text-red-600">
                            <div class="w-3 h-3 bg-red-600 rounded-full animate-pulse"></div>
                            <span class="font-mono">{{ formatTime(recordingTime) }}</span>
                        </div>

                        <div class="flex justify-center gap-3">
                            <Button
                                v-if="captureMode === 'photo'"
                                @click="capturePhoto"
                                class="flex items-center gap-2"
                            >
                                <Camera class="w-4 h-4" />
                                Tomar Foto
                            </Button>

                            <Button
                                v-else-if="captureMode === 'video' && !isRecording"
                                @click="startRecording"
                                class="flex items-center gap-2"
                            >
                                <Video class="w-4 h-4" />
                                Iniciar Grabación
                            </Button>

                            <Button
                                v-else-if="captureMode === 'video' && isRecording"
                                @click="stopRecording"
                                variant="destructive"
                                class="flex items-center gap-2"
                            >
                                <Video class="w-4 h-4" />
                                Detener Grabación
                            </Button>

                            <Button
                                v-else-if="captureMode === 'audio' && !isRecording"
                                @click="startRecording"
                                class="flex items-center gap-2"
                            >
                                <Mic class="w-4 h-4" />
                                Iniciar Grabación
                            </Button>

                            <Button
                                v-else-if="captureMode === 'audio' && isRecording"
                                @click="stopRecording"
                                variant="destructive"
                                class="flex items-center gap-2"
                            >
                                <Mic class="w-4 h-4" />
                                Detener Grabación
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </UserLayout>
</template>