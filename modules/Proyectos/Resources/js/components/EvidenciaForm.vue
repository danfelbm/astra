<script setup lang="ts">
import { computed, watch } from 'vue';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Textarea } from '@modules/Core/Resources/js/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@modules/Core/Resources/js/components/ui/select';
import { Checkbox } from '@modules/Core/Resources/js/components/ui/checkbox';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import { Card, CardContent } from '@modules/Core/Resources/js/components/ui/card';
import InputError from '@modules/Core/Resources/js/components/InputError.vue';
import { Camera, Video, AudioLines, FileText, Info } from 'lucide-vue-next';
import type { TipoEvidencia } from '@modules/Proyectos/Resources/js/types/evidencias';

// Props y emits
const props = defineProps<{
    form: any; // UseForm de Inertia
    obligaciones: Array<{
        id: number;
        nombre: string;
        descripcion?: string;
        estado: string;
    }>;
    entregables: Array<{
        id: number;
        nombre: string;
        hito: string;
        estado: string;
    }>;
    showCapture?: boolean;
}>();

const emit = defineEmits<{
    'request-capture': [];
}>();

// Configuración de tipos de archivo
const tiposArchivo = {
    imagen: {
        icon: Camera,
        accept: 'image/*',
        capture: 'environment',
        extensions: '.jpg,.jpeg,.png,.gif,.webp',
        maxSize: 10 * 1024 * 1024, // 10MB
    },
    video: {
        icon: Video,
        accept: 'video/*',
        capture: 'environment',
        extensions: '.mp4,.mov,.avi,.webm',
        maxSize: 100 * 1024 * 1024, // 100MB
    },
    audio: {
        icon: AudioLines,
        accept: 'audio/*',
        capture: false,
        extensions: '.mp3,.wav,.ogg,.m4a',
        maxSize: 50 * 1024 * 1024, // 50MB
    },
    documento: {
        icon: FileText,
        accept: '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx',
        capture: false,
        extensions: '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx',
        maxSize: 20 * 1024 * 1024, // 20MB
    },
};

// Computed
const tipoSeleccionado = computed(() => {
    return props.form.tipo_evidencia ? tiposArchivo[props.form.tipo_evidencia as TipoEvidencia] : null;
});

const obligacionSeleccionada = computed(() => {
    return props.obligaciones.find(o => o.id === props.form.obligacion_id);
});

const entregablesSeleccionados = computed(() => {
    return props.entregables.filter(e => props.form.entregable_ids?.includes(e.id));
});

const canCapture = computed(() => {
    return props.showCapture &&
           props.form.tipo_evidencia &&
           ['imagen', 'video', 'audio'].includes(props.form.tipo_evidencia);
});

// Métodos
const handleFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (file) {
        // Validar tipo
        const tipoConfig = tiposArchivo[props.form.tipo_evidencia as TipoEvidencia];
        if (tipoConfig && file.size > tipoConfig.maxSize) {
            props.form.errors.archivo = `El archivo excede el tamaño máximo permitido (${tipoConfig.maxSize / 1024 / 1024}MB)`;
            return;
        }

        props.form.archivo = file;
        props.form.clearErrors('archivo');
    }
};

const toggleEntregable = (entregableId: number) => {
    if (!props.form.entregable_ids) {
        props.form.entregable_ids = [];
    }

    const index = props.form.entregable_ids.indexOf(entregableId);
    if (index > -1) {
        props.form.entregable_ids.splice(index, 1);
    } else {
        props.form.entregable_ids.push(entregableId);
    }
};

// Watchers
watch(() => props.form.tipo_evidencia, () => {
    // Limpiar archivo cuando cambia el tipo
    props.form.archivo = null;
    props.form.clearErrors('archivo');
});

watch(() => props.form.obligacion_id, () => {
    props.form.clearErrors('obligacion_id');
});
</script>

<template>
    <div class="space-y-6">
        <!-- Selección de Obligación -->
        <div>
            <Label htmlFor="obligacion_id">
                Obligación Contractual <span class="text-destructive">*</span>
            </Label>
            <Select v-model="form.obligacion_id">
                <SelectTrigger id="obligacion_id">
                    <SelectValue placeholder="Seleccione la obligación relacionada" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="obligacion in obligaciones"
                        :key="obligacion.id"
                        :value="obligacion.id"
                    >
                        <div class="flex items-center justify-between w-full">
                            <span>{{ obligacion.nombre }}</span>
                            <span class="text-xs text-muted-foreground ml-2">({{ obligacion.estado }})</span>
                        </div>
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.obligacion_id" class="mt-1" />

            <!-- Descripción de la obligación seleccionada -->
            <Alert v-if="obligacionSeleccionada?.descripcion" class="mt-2">
                <Info class="w-4 h-4" />
                <AlertDescription>
                    {{ obligacionSeleccionada.descripcion }}
                </AlertDescription>
            </Alert>
        </div>

        <!-- Tipo de Evidencia -->
        <div>
            <Label htmlFor="tipo_evidencia">
                Tipo de Evidencia <span class="text-destructive">*</span>
            </Label>
            <Select v-model="form.tipo_evidencia">
                <SelectTrigger id="tipo_evidencia">
                    <SelectValue placeholder="Seleccione el tipo de evidencia" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="imagen">
                        <div class="flex items-center gap-2">
                            <Camera class="w-4 h-4" />
                            <span>Imagen</span>
                        </div>
                    </SelectItem>
                    <SelectItem value="video">
                        <div class="flex items-center gap-2">
                            <Video class="w-4 h-4" />
                            <span>Video</span>
                        </div>
                    </SelectItem>
                    <SelectItem value="audio">
                        <div class="flex items-center gap-2">
                            <AudioLines class="w-4 h-4" />
                            <span>Audio</span>
                        </div>
                    </SelectItem>
                    <SelectItem value="documento">
                        <div class="flex items-center gap-2">
                            <FileText class="w-4 h-4" />
                            <span>Documento</span>
                        </div>
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.tipo_evidencia" class="mt-1" />
        </div>

        <!-- Carga de Archivo -->
        <div v-if="form.tipo_evidencia">
            <Label htmlFor="archivo">
                Archivo de Evidencia <span class="text-destructive">*</span>
            </Label>

            <div class="mt-2 space-y-2">
                <!-- Input de archivo -->
                <div class="flex items-center gap-2">
                    <input
                        id="archivo"
                        type="file"
                        :accept="tipoSeleccionado?.accept"
                        :capture="canCapture ? tipoSeleccionado?.capture : undefined"
                        @change="handleFileChange"
                        class="flex-1 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-primary-foreground hover:file:bg-primary/90"
                    />
                </div>

                <!-- Botón de captura (si aplica) -->
                <button
                    v-if="canCapture"
                    type="button"
                    @click="emit('request-capture')"
                    class="w-full py-2 px-4 border border-dashed border-muted-foreground rounded-md hover:bg-muted transition-colors"
                >
                    <div class="flex items-center justify-center gap-2">
                        <component :is="tipoSeleccionado?.icon" class="w-5 h-5" />
                        <span>Capturar {{ form.tipo_evidencia }}</span>
                    </div>
                </button>

                <!-- Información del archivo seleccionado -->
                <Alert v-if="form.archivo">
                    <Info class="w-4 h-4" />
                    <AlertDescription>
                        <div v-if="form.archivo instanceof File">
                            <strong>Archivo seleccionado:</strong> {{ form.archivo.name }}<br>
                            <strong>Tamaño:</strong> {{ (form.archivo.size / 1024 / 1024).toFixed(2) }} MB
                        </div>
                        <div v-else>
                            Archivo capturado exitosamente
                        </div>
                    </AlertDescription>
                </Alert>

                <p class="text-sm text-muted-foreground">
                    Formatos aceptados: {{ tipoSeleccionado?.extensions }}
                    (Máximo {{ (tipoSeleccionado?.maxSize || 0) / 1024 / 1024 }} MB)
                </p>
            </div>

            <InputError :message="form.errors.archivo" class="mt-1" />
        </div>

        <!-- Descripción -->
        <div>
            <Label htmlFor="descripcion">
                Descripción de la Evidencia
            </Label>
            <Textarea
                id="descripcion"
                v-model="form.descripcion"
                rows="3"
                placeholder="Describa brevemente el contenido y propósito de esta evidencia..."
                class="mt-2"
            />
            <InputError :message="form.errors.descripcion" class="mt-1" />
        </div>

        <!-- Selección de Entregables -->
        <div v-if="entregables.length > 0">
            <Label>
                Entregables Relacionados
            </Label>
            <p class="text-sm text-muted-foreground mb-3">
                Seleccione los entregables del proyecto que esta evidencia ayuda a cumplir
            </p>

            <Card>
                <CardContent class="pt-6 max-h-60 overflow-y-auto">
                    <div class="space-y-3">
                        <div
                            v-for="entregable in entregables"
                            :key="entregable.id"
                            class="flex items-start space-x-3"
                        >
                            <Checkbox
                                :id="`entregable-${entregable.id}`"
                                :checked="form.entregable_ids?.includes(entregable.id)"
                                @update:checked="() => toggleEntregable(entregable.id)"
                            />
                            <div class="grid gap-1.5 leading-none">
                                <label
                                    :for="`entregable-${entregable.id}`"
                                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer"
                                >
                                    {{ entregable.nombre }}
                                </label>
                                <p class="text-xs text-muted-foreground">
                                    Hito: {{ entregable.hito }}
                                    <span class="ml-2">({{ entregable.estado }})</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <InputError :message="form.errors.entregable_ids" class="mt-1" />
        </div>

        <!-- Resumen de selección -->
        <Alert v-if="entregablesSeleccionados.length > 0">
            <Info class="w-4 h-4" />
            <AlertDescription>
                <strong>Entregables seleccionados:</strong>
                <ul class="mt-1 ml-4 list-disc">
                    <li v-for="entregable in entregablesSeleccionados" :key="entregable.id">
                        {{ entregable.nombre }}
                    </li>
                </ul>
            </AlertDescription>
        </Alert>
    </div>
</template>