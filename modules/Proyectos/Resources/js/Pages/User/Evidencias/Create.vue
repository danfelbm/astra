<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import UserLayout from '@modules/Core/Resources/js/layouts/UserLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Textarea } from '@modules/Core/Resources/js/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@modules/Core/Resources/js/components/ui/select';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import { Checkbox } from '@modules/Core/Resources/js/components/ui/checkbox';
import { Progress } from '@modules/Core/Resources/js/components/ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@modules/Core/Resources/js/components/ui/tabs';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import FileUploadField from '@modules/Core/Resources/js/components/forms/FileUploadField.vue';
import EntregableSelector from '@modules/Proyectos/Resources/js/components/EntregableSelector.vue';
import { useAutoSave } from '@modules/Core/Resources/js/composables/useAutoSave';
import { useFileUpload } from '@modules/Core/Resources/js/composables/useFileUpload';
import { toast } from 'vue-sonner';
import { Save, CheckCircle, Clock, AlertCircle, Trash2, Camera, FileText, Image, Video, Music } from 'lucide-vue-next';
import type { Contrato } from '@modules/Proyectos/Resources/js/types/contratos';
import type { ObligacionContrato } from '@modules/Proyectos/Resources/js/types/obligaciones';
import type { Entregable } from '@modules/Proyectos/Resources/js/types/hitos';
import type { TipoEvidencia, EvidenciaFormData, TipoEvidenciaOption, ArchivosPorTipoMap } from '@modules/Proyectos/Resources/js/types/evidencias';
import type { BreadcrumbItemType } from '@modules/Core/Resources/js/types';

interface Props {
    contrato: Contrato;
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
    { title: 'Subir Evidencia', href: '#' }
];

// Composable para archivos
const { uploadFiles } = useFileUpload();

// Estado del formulario
const form = useForm<EvidenciaFormData>({
    obligacion_id: null,
    tipo_evidencia: null,
    archivo_path: null,
    archivo_nombre: null,
    archivos_paths: [],
    archivos_nombres: [],
    tipos_archivos: null,
    descripcion: null,
    entregable_ids: [], // Cambiado de 'entregables' a 'entregable_ids'
    metadata: null
});

// Array para el FileUploadField (necesita ser un array, no string)
const archivosSubidos = ref<string[]>([]);

// Nuevo: Archivos agrupados por tipo
const archivosPorTipo = ref<ArchivosPorTipoMap>({
    imagen: { paths: [], nombres: [], count: 0 },
    video: { paths: [], nombres: [], count: 0 },
    audio: { paths: [], nombres: [], count: 0 },
    documento: { paths: [], nombres: [], count: 0 }
});

// Tipo activo para la UI (el tipo de evidencia que el usuario está viendo/editando)
const tipoActivo = ref<TipoEvidencia | null>(null);

// Estado de captura multimedia
const isCapturing = ref(false);
const mediaStream = ref<MediaStream | null>(null);
const videoElement = ref<HTMLVideoElement | null>(null);
const audioRecorder = ref<MediaRecorder | null>(null);
const capturedBlob = ref<Blob | null>(null);

// Archivo pendiente de subir
const pendingFile = ref<File | null>(null);

// Estado de autosave - enviar datos directamente sin wrapping
const formDataRef = computed(() => form.data());

const {
    state: autoSaveState,
    isSaving,
    hasSaved,
    hasError,
    saveNow,
    startWatching,
    stopAutoSave,
    restoreDraft,
    clearLocalStorage
} = useAutoSave(formDataRef, {
    url: `/miembro/mis-contratos/${props.contrato.id}/evidencias/autosave`,
    debounceTime: 3000,
    showNotifications: true,
    useLocalStorage: true,
    localStorageKey: `evidencia_draft_contrato_${props.contrato.id}`
});

// Configuración de tipos de evidencia con íconos
const tipoConfig = computed(() => {
    const configs: Record<TipoEvidencia, any> = {
        imagen: {
            icon: Image,
            accept: 'image/*',
            capture: 'environment',
            maxSize: 10 * 1024 * 1024 // 10MB
        },
        video: {
            icon: Video,
            accept: 'video/*',
            capture: 'environment',
            maxSize: 500 * 1024 * 1024 // 500MB
        },
        audio: {
            icon: Music,
            accept: 'audio/*',
            capture: false,
            maxSize: 50 * 1024 * 1024 // 50MB
        },
        documento: {
            icon: FileText,
            accept: '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.rtf',
            capture: false,
            maxSize: 20 * 1024 * 1024 // 20MB
        }
    };
    return form.tipo_evidencia ? configs[form.tipo_evidencia] : null;
});

// Calcular progreso del formulario
const progressInfo = computed(() => {
    const campos = [
        form.obligacion_id,
        form.tipo_evidencia,
        form.archivo_path,
        form.descripcion,
        form.entregable_ids.length > 0
    ];
    const llenos = campos.filter(Boolean).length;
    const porcentaje = Math.round((llenos / 5) * 100);
    return { llenos, total: 5, porcentaje };
});

// Watcher para cambiar el tipo activo sin borrar archivos
watch(() => form.tipo_evidencia, (newTipo) => {
    if (newTipo) {
        tipoActivo.value = newTipo;

        // Detener captura si está activa al cambiar de tipo
        if (isCapturing.value) {
            stopCapture();
        }
    }
});

// Detectar el tipo de un archivo basado en su MIME type
const detectarTipoArchivo = (file: File | Blob): TipoEvidencia => {
    const mimeType = file.type;
    if (mimeType.startsWith('image/')) return 'imagen';
    if (mimeType.startsWith('video/')) return 'video';
    if (mimeType.startsWith('audio/')) return 'audio';
    return 'documento';
};

// Actualizar el formulario con todos los archivos agrupados
const actualizarFormConTodosArchivos = () => {
    const todosPaths: string[] = [];
    const todosNombres: string[] = [];
    const tiposMap: Record<string, TipoEvidencia> = {};

    Object.entries(archivosPorTipo.value).forEach(([tipo, data]) => {
        data.paths.forEach((path, idx) => {
            todosPaths.push(path);
            todosNombres.push(data.nombres[idx]);
            tiposMap[path] = tipo as TipoEvidencia;
        });
    });

    form.archivos_paths = todosPaths;
    form.archivos_nombres = todosNombres;
    form.tipos_archivos = tiposMap;

    // Establecer archivo principal para retrocompatibilidad
    if (todosPaths.length > 0 && !form.archivo_path) {
        form.archivo_path = todosPaths[0];
        form.archivo_nombre = todosNombres[0];
    }

    // Sincronizar con FileUploadField
    archivosSubidos.value = todosPaths;

    // Calcular tipo dominante
    if (Object.keys(tiposMap).length > 0) {
        const conteo = Object.values(tiposMap).reduce((acc, tipo) => {
            acc[tipo] = (acc[tipo] || 0) + 1;
            return acc;
        }, {} as Record<string, number>);

        const tipoDominante = Object.entries(conteo).reduce((a, b) =>
            a[1] > b[1] ? a : b
        )[0] as TipoEvidencia;

        form.tipo_evidencia = tipoDominante;
    }
};

// Contar archivos totales
const totalArchivosSubidos = computed(() => {
    return Object.values(archivosPorTipo.value).reduce(
        (total, tipo) => total + tipo.count,
        0
    );
});

// Limpiar archivos de un tipo específico
const limpiarArchivosTipo = (tipo: TipoEvidencia) => {
    archivosPorTipo.value[tipo] = { paths: [], nombres: [], count: 0 };
    actualizarFormConTodosArchivos();
    toast.info(`Archivos de tipo ${tipo} eliminados`);
};

// Limpiar todos los archivos
const limpiarTodosArchivos = () => {
    archivosPorTipo.value = {
        imagen: { paths: [], nombres: [], count: 0 },
        video: { paths: [], nombres: [], count: 0 },
        audio: { paths: [], nombres: [], count: 0 },
        documento: { paths: [], nombres: [], count: 0 }
    };
    form.archivos_paths = [];
    form.archivos_nombres = [];
    form.tipos_archivos = null;
    form.archivo_path = null;
    form.archivo_nombre = null;
    archivosSubidos.value = [];
    toast.info('Todos los archivos han sido eliminados');
};

// Validación del formulario
const isFormValid = computed(() => {
    return form.obligacion_id &&
           form.tipo_evidencia &&
           (totalArchivosSubidos.value > 0 || pendingFile.value || capturedBlob.value);
});

// Iniciar captura de cámara/micrófono
const startCapture = async () => {
    if (!form.tipo_evidencia || form.tipo_evidencia === 'documento') {
        toast.error('No se puede capturar este tipo de evidencia');
        return;
    }

    try {
        isCapturing.value = true;

        const constraints: MediaStreamConstraints = {
            video: form.tipo_evidencia === 'imagen' || form.tipo_evidencia === 'video',
            audio: form.tipo_evidencia === 'audio' || form.tipo_evidencia === 'video'
        };

        mediaStream.value = await navigator.mediaDevices.getUserMedia(constraints);

        if (form.tipo_evidencia === 'audio' || form.tipo_evidencia === 'video') {
            // Iniciar grabación
            audioRecorder.value = new MediaRecorder(mediaStream.value);
            const chunks: Blob[] = [];

            audioRecorder.value.ondataavailable = (e) => {
                chunks.push(e.data);
            };

            audioRecorder.value.onstop = () => {
                const blob = new Blob(chunks, {
                    type: form.tipo_evidencia === 'audio' ? 'audio/webm' : 'video/webm'
                });
                capturedBlob.value = blob;
                processCapturedBlob(blob);
            };

            audioRecorder.value.start();
            toast.info('Grabación iniciada');
        }
    } catch (error) {
        console.error('Error al iniciar captura:', error);
        toast.error('No se pudo acceder a la cámara/micrófono');
        isCapturing.value = false;
    }
};

// Detener captura
const stopCapture = () => {
    if (mediaStream.value) {
        mediaStream.value.getTracks().forEach(track => track.stop());
        mediaStream.value = null;
    }

    if (audioRecorder.value && audioRecorder.value.state === 'recording') {
        audioRecorder.value.stop();
    }

    isCapturing.value = false;
};

// Capturar foto
const capturePhoto = () => {
    if (!videoElement.value) return;

    const canvas = document.createElement('canvas');
    canvas.width = videoElement.value.videoWidth;
    canvas.height = videoElement.value.videoHeight;
    const ctx = canvas.getContext('2d');

    if (ctx) {
        ctx.drawImage(videoElement.value, 0, 0);
        canvas.toBlob((blob) => {
            if (blob) {
                capturedBlob.value = blob;
                processCapturedBlob(blob);
                stopCapture();
            }
        }, 'image/jpeg', 0.95);
    }
};

// Procesar blob capturado
const processCapturedBlob = async (blob: Blob) => {
    const fileName = `captura_${Date.now()}.${getExtensionFromMimeType(blob.type)}`;
    const file = new File([blob], fileName, { type: blob.type });

    capturedBlob.value = blob;
    form.archivo_nombre = fileName;
    form.metadata = {
        mime_type: blob.type,
        size: blob.size,
        captured_at: new Date().toISOString()
    };

    // Subir archivo capturado inmediatamente
    try {
        toast.info('Subiendo archivo capturado...');

        const uploadedFiles = await uploadFiles([file], {
            module: 'evidencias',
            fieldId: form.tipo_evidencia || 'archivo',
            onProgress: (fileName: string, progress: number) => {
                // El componente FileUploadField manejará el progreso visualmente
            },
        });

        if (uploadedFiles.length > 0) {
            form.archivo_path = uploadedFiles[0].path;
            form.archivo_nombre = uploadedFiles[0].name;
            form.metadata = uploadedFiles[0].metadata;

            // Sincronizar con el array del FileUploadField
            archivosSubidos.value = [uploadedFiles[0].path];

            capturedBlob.value = null; // Limpiar blob ya que está subido
            toast.success('Archivo subido exitosamente');
        }
    } catch (error) {
        console.error('Error al subir archivo capturado:', error);
        toast.error('Error al subir el archivo capturado');
    }
};

// Obtener extensión del MIME type
const getExtensionFromMimeType = (mimeType: string): string => {
    const extensions: Record<string, string> = {
        'image/jpeg': 'jpg',
        'image/png': 'png',
        'image/webp': 'webp',
        'video/webm': 'webm',
        'audio/webm': 'webm'
    };
    return extensions[mimeType] || 'bin';
};

// Manejar selección de archivos (ahora soporta múltiples tipos)
const handleFilesSelected = async (files: File[]) => {
    if (files.length > 0) {
        // Verificar límite máximo de archivos
        const totalActual = totalArchivosSubidos.value;
        const nuevosArchivos = files.length;

        if (totalActual + nuevosArchivos > 10) {
            toast.error(`No puedes subir más de 10 archivos. Actualmente tienes ${totalActual} archivo(s).`);
            return;
        }

        // Agrupar archivos por tipo antes de subir
        const archivosPorTipoTemp: Record<TipoEvidencia, File[]> = {
            imagen: [],
            video: [],
            audio: [],
            documento: []
        };

        files.forEach(file => {
            const tipo = detectarTipoArchivo(file);
            archivosPorTipoTemp[tipo].push(file);
        });

        // Subir archivos por grupos de tipo
        try {
            let totalSubidos = 0;

            for (const [tipo, archivos] of Object.entries(archivosPorTipoTemp)) {
                if (archivos.length === 0) continue;

                toast.info(`Subiendo ${archivos.length} archivo(s) de tipo ${tipo}...`);

                const uploadedFiles = await uploadFiles(archivos, {
                    module: 'evidencias',
                    fieldId: tipo, // Usar el tipo como fieldId
                    onProgress: (fileName: string, progress: number) => {
                        // El componente FileUploadField manejará el progreso visualmente
                    },
                });

                if (uploadedFiles.length > 0) {
                    // Agregar archivos al grupo correspondiente
                    const tipoKey = tipo as TipoEvidencia;

                    uploadedFiles.forEach(uploadedFile => {
                        archivosPorTipo.value[tipoKey].paths.push(uploadedFile.path);
                        archivosPorTipo.value[tipoKey].nombres.push(uploadedFile.name);
                        archivosPorTipo.value[tipoKey].count++;
                    });

                    totalSubidos += uploadedFiles.length;
                }
            }

            if (totalSubidos > 0) {
                // Actualizar el formulario con todos los archivos
                actualizarFormConTodosArchivos();
                toast.success(`${totalSubidos} archivo(s) subido(s) exitosamente. Total: ${totalArchivosSubidos.value}/10`);
            }
        } catch (error) {
            console.error('Error al subir archivos:', error);
            toast.error('Error al subir archivos');
        }
    }
};

// Guardar borrador manualmente
const saveManually = async () => {
    await saveNow();
    toast.success('Borrador guardado');
};

// Enviar formulario
const handleSubmit = async () => {
    if (!isFormValid.value) return;

    // Los archivos ya se suben inmediatamente, solo verificar que hay al menos uno

    // Verificar que hay archivos subidos
    if (totalArchivosSubidos.value === 0 && !form.archivo_path) {
        toast.error('Por favor seleccione o capture al menos un archivo');
        return;
    }

    // Actualizar el formulario con todos los archivos agrupados antes de enviar
    actualizarFormConTodosArchivos();

    // Detener autoguardado
    stopAutoSave();

    // Enviar formulario
    form.post(`/miembro/mis-contratos/${props.contrato.id}/evidencias`, {
        onSuccess: () => {
            clearLocalStorage();
            toast.success(`Evidencia subida exitosamente con ${form.archivos_paths.length} archivo(s)`);
        },
        onError: (errors) => {
            console.error('Errores de validación:', errors);
            // Reanudar autoguardado si hay error
            startWatching();
        }
    });
};

// Descartar borrador y resetear formulario
const discardDraft = () => {
    if (!confirm('¿Estás seguro de descartar el borrador? Se perderán todos los cambios no enviados.')) {
        return;
    }

    // Limpiar localStorage
    clearLocalStorage();

    // Resetear formulario a valores iniciales
    form.reset();

    // Limpiar archivos
    limpiarTodosArchivos();

    // Detener captura si está activa
    if (isCapturing.value) {
        stopCapture();
    }

    toast.success('Borrador descartado');
};

// Lifecycle hooks
onMounted(() => {
    // Intentar recuperar borrador
    const draft = restoreDraft();
    if (draft && draft.data) {
        Object.assign(form, draft.data);
    }

    // Iniciar autoguardado
    startWatching();
});

onUnmounted(() => {
    stopAutoSave();
    stopCapture();
});
</script>

<template>
    <Head :title="`Subir Evidencia - ${contrato.nombre}`" />

    <UserLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-0 sm:p-4 pb-24">
            <!-- Indicador de autoguardado -->
            <div class="flex items-center justify-end gap-2 text-sm text-muted-foreground">
                <div v-if="isSaving" class="flex items-center gap-1.5">
                    <Clock class="h-4 w-4 animate-spin" />
                    <span>Guardando automáticamente...</span>
                </div>
                <div v-else-if="hasSaved && !hasError" class="flex items-center gap-1.5">
                    <CheckCircle class="h-4 w-4 text-green-600" />
                    <span>Guardado a las {{ autoSaveState.lastSaved?.toLocaleTimeString() }}</span>
                </div>
                <div v-else-if="hasError" class="flex items-center gap-1.5 text-amber-600">
                    <AlertCircle class="h-4 w-4" />
                    <span>Guardado localmente</span>
                </div>
            </div>

            <!-- Formulario Principal -->
            <Card>
                <CardHeader>
                    <CardTitle>Información de la Evidencia</CardTitle>
                    <CardDescription>
                        Selecciona la obligación, el tipo de evidencia y adjunta el archivo correspondiente
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6 sm:mb-16">
                    <!-- Selección de Obligación -->
                    <div class="space-y-2">
                        <Label htmlFor="obligacion">Obligación *</Label>
                        <Select v-model="form.obligacion_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Selecciona una obligación" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="obligacion in obligaciones"
                                    :key="obligacion.id"
                                    :value="obligacion.id"
                                >
                                    {{ obligacion.titulo }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.obligacion_id" class="text-sm text-destructive">
                            {{ form.errors.obligacion_id }}
                        </p>
                    </div>

                    <!-- Tipo de Evidencia -->
                    <div class="space-y-2">
                        <Label>Tipo de Evidencia *</Label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <Button
                                v-for="tipo in tiposEvidencia"
                                :key="tipo.value"
                                @click="form.tipo_evidencia = tipo.value"
                                :variant="form.tipo_evidencia === tipo.value ? 'default' : 'outline'"
                                class="h-20 flex flex-col gap-2"
                            >
                                <component
                                    :is="tipo.value === 'imagen' ? Image :
                                         tipo.value === 'video' ? Video :
                                         tipo.value === 'audio' ? Music : FileText"
                                    class="h-6 w-6"
                                />
                                <span>{{ tipo.label }}</span>
                            </Button>
                        </div>
                        <p v-if="form.errors.tipo_evidencia" class="text-sm text-destructive">
                            {{ form.errors.tipo_evidencia }}
                        </p>
                    </div>

                    <!-- Carga de Archivo -->
                    <div v-if="form.tipo_evidencia" class="space-y-2">
                        <Label>Archivo de Evidencia *</Label>

                        <!-- Resumen de archivos por tipo -->
                        <div v-if="totalArchivosSubidos > 0" class="mb-4 p-4 border rounded-lg bg-muted/50">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium">Archivos subidos</span>
                                <Badge variant="outline">{{ totalArchivosSubidos }}/10</Badge>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <div v-for="(tipo, key) in archivosPorTipo" :key="key" class="flex items-center gap-2">
                                    <component
                                        :is="key === 'imagen' ? Image :
                                             key === 'video' ? Video :
                                             key === 'audio' ? Music : FileText"
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <span class="text-xs">{{ tipo.count }} {{ key }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Captura para imagen/video/audio -->
                        <div v-if="form.tipo_evidencia !== 'documento'" class="space-y-4">
                            <div class="flex gap-2">
                                <Button
                                    v-if="!isCapturing"
                                    @click="startCapture"
                                    variant="outline"
                                    class="flex-1"
                                >
                                    <Camera class="mr-2 h-4 w-4" />
                                    {{ form.tipo_evidencia === 'audio' ? 'Grabar Audio' : 'Capturar' }}
                                </Button>
                                <Button
                                    v-else
                                    @click="stopCapture"
                                    variant="destructive"
                                    class="flex-1"
                                >
                                    Detener
                                </Button>
                            </div>

                            <!-- Vista previa de video para captura -->
                            <video
                                v-if="isCapturing && (form.tipo_evidencia === 'imagen' || form.tipo_evidencia === 'video')"
                                ref="videoElement"
                                :srcObject="mediaStream"
                                autoplay
                                playsinline
                                muted
                                class="w-full rounded-lg border"
                            />

                            <!-- Botón para capturar foto -->
                            <Button
                                v-if="isCapturing && form.tipo_evidencia === 'imagen'"
                                @click="capturePhoto"
                                class="w-full"
                            >
                                Tomar Foto
                            </Button>
                        </div>

                        <!-- Campo de carga tradicional -->
                        <FileUploadField
                            v-model="archivosSubidos"
                            @filesSelected="handleFilesSelected"
                            :label="''"
                            :description="`Selecciona archivos desde tu dispositivo (${totalArchivosSubidos}/10 archivos)`"
                            :required="true"
                            :multiple="true"
                            :max-files="10"
                            :max-file-size="tipoConfig?.maxSize"
                            accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx"
                            :error="form.errors.archivo_path || form.errors.archivos_paths"
                            :disabled="form.processing"
                            module="evidencias"
                            :field-id="form.tipo_evidencia || 'archivo'"
                            :auto-upload="false"
                        />
                    </div>

                    <!-- Entregables Relacionados -->
                    <EntregableSelector
                        v-model="form.entregable_ids"
                        :entregables="entregables"
                        label="Entregables Relacionados"
                        description="Selecciona los entregables del proyecto que se relacionan con esta evidencia"
                        :disabled="form.processing"
                    />

                    <!-- Descripción -->
                    <div class="space-y-2">
                        <Label htmlFor="descripcion">Descripción</Label>
                        <Textarea
                            id="descripcion"
                            v-model="form.descripcion"
                            placeholder="Describe brevemente la evidencia adjuntada"
                            rows="3"
                        />
                        <p v-if="form.errors.descripcion" class="text-sm text-destructive">
                            {{ form.errors.descripcion }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Bottom Bar -->
        <Teleport to="body">
            <Transition name="slide-up">
                <div class="fixed bottom-0 left-0 right-0 z-50 px-2 sm:px-4 pb-2 sm:pb-4">
                    <div class="mx-auto max-w-7xl">
                        <div class="backdrop-blur-lg bg-tertiary-60 dark:bg-gray-900/80 border border-gray-200/50 dark:border-gray-700/50 rounded-xl sm:rounded-2xl shadow-2xl p-3 sm:p-4">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                                <!-- Botón Descartar Borrador -->
                                <div class="hidden sm:flex items-center">
                                    <Button
                                        variant="outline"
                                        type="button"
                                        @click="discardDraft"
                                        class="backdrop-blur-sm flex-shrink-0 text-xs sm:text-sm py-2 px-3 text-destructive hover:text-destructive"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                        <span class="ml-2">Descartar borrador</span>
                                    </Button>
                                </div>

                                <!-- Barra de progreso -->
                                <div class="flex-1 sm:max-w-xs lg:max-w-sm mx-0 sm:mx-4">
                                    <div class="flex items-center gap-3">
                                        <Progress :modelValue="progressInfo.porcentaje" class="flex-1" />
                                        <span class="text-xs sm:text-sm text-muted-foreground whitespace-nowrap font-medium">
                                            {{ progressInfo.llenos }} / {{ progressInfo.total }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-muted-foreground mt-1 text-center hidden sm:block">
                                        Campos completados
                                    </p>
                                </div>

                                <!-- Grupo acciones principales -->
                                <div class="flex items-center gap-2 sm:gap-3">
                                    <!-- Botón Guardar Borrador -->
                                    <Button
                                        @click="saveManually"
                                        :disabled="isSaving"
                                        variant="outline"
                                        class="backdrop-blur-sm flex-1 sm:flex-none text-xs sm:text-sm py-2 px-3"
                                    >
                                        <template v-if="isSaving">
                                            <Clock class="mr-2 h-4 w-4 animate-spin" />
                                            Guardando...
                                        </template>
                                        <template v-else>
                                            <Save class="mr-2 h-4 w-4" />
                                            Guardar borrador
                                        </template>
                                    </Button>

                                    <!-- Botón Enviar -->
                                    <Button
                                        @click="handleSubmit"
                                        :disabled="!isFormValid || form.processing"
                                        class="bg-green-600 hover:bg-green-700 text-white border-green-600 hover:border-green-700 disabled:bg-gray-400 disabled:border-gray-400 flex-1 sm:flex-none text-xs sm:text-sm py-2 px-3"
                                    >
                                        <CheckCircle class="h-4 w-4" />
                                        <span class="ml-2 whitespace-nowrap">Subir Evidencia</span>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </UserLayout>
</template>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
    transition: transform 0.3s ease;
}

.slide-up-enter-from {
    transform: translateY(100%);
}

.slide-up-leave-to {
    transform: translateY(100%);
}
</style>