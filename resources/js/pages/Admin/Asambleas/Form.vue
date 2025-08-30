<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { DateTimePicker } from '@/components/ui/datetime-picker';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { type BreadcrumbItemType } from '@/types';
import AdminLayout from "@/layouts/AdminLayout.vue";
import { Head, useForm, router } from '@inertiajs/vue3';
import { ArrowLeft, MapPin, Save, Video, Settings, Users, Mic, Camera, Info, Vote, Plus, X } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import CreateVotacionModal from '@/components/admin/CreateVotacionModal.vue';

interface Asamblea {
    id: number;
    nombre: string;
    descripcion?: string;
    tipo: 'ordinaria' | 'extraordinaria';
    estado: 'programada' | 'en_curso' | 'finalizada' | 'cancelada';
    fecha_inicio: string;
    fecha_fin: string;
    territorio_id?: number;
    departamento_id?: number;
    municipio_id?: number;
    localidad_id?: number;
    lugar?: string;
    quorum_minimo?: number;
    activo: boolean;
    acta_url?: string;
    // Campos de videoconferencia
    zoom_enabled?: boolean;
    zoom_integration_type?: 'sdk' | 'api' | 'message';
    zoom_meeting_id?: string;
    zoom_meeting_password?: string;
    zoom_occurrence_ids?: string;
    zoom_prefix?: string;
    zoom_static_message?: string;
    zoom_api_message_enabled?: boolean;
    zoom_api_message?: string;
    zoom_registration_open_date?: string;
    zoom_meeting_type?: 'instant' | 'scheduled' | 'recurring';
    zoom_settings?: {
        host_video?: boolean;
        participant_video?: boolean;
        waiting_room?: boolean;
        mute_upon_entry?: boolean;
        auto_recording?: 'none' | 'local' | 'cloud';
    };
    zoom_created_at?: string;
    zoom_join_url?: string;
    zoom_start_url?: string;
    // Campos de consulta pública de participantes
    public_participants_enabled?: boolean;
    public_participants_mode?: 'list' | 'search';
}

interface Territorio {
    id: number;
    nombre: string;
}

interface Departamento {
    id: number;
    nombre: string;
    territorio_id: number;
}

interface Municipio {
    id: number;
    nombre: string;
    departamento_id: number;
}

interface Localidad {
    id: number;
    nombre: string;
    municipio_id: number;
}

interface Votacion {
    id: number;
    titulo: string;
    descripcion?: string;
    estado: string;
    fecha_inicio: string;
    fecha_fin: string;
}

interface Props {
    asamblea?: Asamblea | null;
    territorios: Territorio[];
    departamentos: Departamento[];
    municipios: Municipio[];
    localidades: Localidad[];
    votaciones?: Votacion[];
    asambleaVotaciones?: number[];
    canManageParticipants?: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Asambleas', href: '/admin/asambleas' },
    { title: props.asamblea ? 'Editar' : 'Nueva', href: '#' },
];

// Helper para obtener route
const { route } = window as any;

// Computed para obtener la URL base de forma segura
const baseUrl = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.origin;
    }
    return '';
});

// Formulario reactivo
const form = useForm({
    nombre: props.asamblea?.nombre || '',
    descripcion: props.asamblea?.descripcion || '',
    tipo: props.asamblea?.tipo || 'ordinaria',
    estado: props.asamblea?.estado || 'programada',
    fecha_inicio: props.asamblea?.fecha_inicio || '',
    fecha_fin: props.asamblea?.fecha_fin || '',
    territorio_id: props.asamblea?.territorio_id?.toString() || null,
    departamento_id: props.asamblea?.departamento_id?.toString() || null,
    municipio_id: props.asamblea?.municipio_id?.toString() || null,
    localidad_id: props.asamblea?.localidad_id?.toString() || null,
    lugar: props.asamblea?.lugar || '',
    quorum_minimo: props.asamblea?.quorum_minimo || null,
    activo: props.asamblea?.activo ?? true,
    acta_url: props.asamblea?.acta_url || '',
    // Campos de videoconferencia
    zoom_enabled: props.asamblea?.zoom_enabled ?? false,
    zoom_integration_type: props.asamblea?.zoom_integration_type || 'sdk',
    zoom_meeting_id: props.asamblea?.zoom_meeting_id || '',
    zoom_meeting_password: props.asamblea?.zoom_meeting_password || '',
    zoom_occurrence_ids: props.asamblea?.zoom_occurrence_ids || '',
    zoom_prefix: props.asamblea?.zoom_prefix || '',
    zoom_registration_open_date: props.asamblea?.zoom_registration_open_date || '',
    zoom_static_message: props.asamblea?.zoom_static_message || '',
    zoom_api_message_enabled: props.asamblea?.zoom_api_message_enabled ?? false,
    zoom_api_message: props.asamblea?.zoom_api_message || '',
    zoom_join_url: props.asamblea?.zoom_join_url || '',
    zoom_start_url: props.asamblea?.zoom_start_url || '',
    zoom_meeting_type: props.asamblea?.zoom_meeting_type || 'scheduled',
    zoom_settings: props.asamblea?.zoom_settings || {
        host_video: true,
        participant_video: false,
        waiting_room: true,
        mute_upon_entry: true,
        auto_recording: 'none'
    },
    // Campos de consulta pública de participantes
    public_participants_enabled: props.asamblea?.public_participants_enabled ?? false,
    public_participants_mode: props.asamblea?.public_participants_mode || 'list',
    // Votaciones asociadas
    votacion_ids: props.asambleaVotaciones || [],
});

// Estados del componente
const showCreateVotacionModal = ref(false);
const selectedVotaciones = ref<number[]>(props.asambleaVotaciones || []);

// Validaciones de fecha
const fechaFinError = computed(() => {
    if (!form.fecha_fin || !form.fecha_inicio) return '';
    const fechaFin = new Date(form.fecha_fin);
    const fechaInicio = new Date(form.fecha_inicio);
    if (fechaFin <= fechaInicio) {
        return 'La fecha de fin debe ser posterior a la fecha de inicio';
    }
    return '';
});

// Computed para filtrar ubicaciones en cascada
const departamentosFiltrados = computed(() => {
    if (!form.territorio_id) return [];
    return props.departamentos.filter(d => d.territorio_id === Number(form.territorio_id));
});

const municipiosFiltrados = computed(() => {
    if (!form.departamento_id) return [];
    return props.municipios.filter(m => m.departamento_id === Number(form.departamento_id));
});

const localidadesFiltradas = computed(() => {
    if (!form.municipio_id) return [];
    return props.localidades.filter(l => l.municipio_id === Number(form.municipio_id));
});

// Watchers para limpiar selecciones en cascada
watch(() => form.territorio_id, (newVal, oldVal) => {
    // Solo limpiar si cambió el valor
    if (newVal !== oldVal) {
        form.departamento_id = null;
        form.municipio_id = null;
        form.localidad_id = null;
    }
});

watch(() => form.departamento_id, (newVal, oldVal) => {
    // Solo limpiar si cambió el valor
    if (newVal !== oldVal) {
        form.municipio_id = null;
        form.localidad_id = null;
    }
});

watch(() => form.municipio_id, (newVal, oldVal) => {
    // Solo limpiar si cambió el valor
    if (newVal !== oldVal) {
        form.localidad_id = null;
    }
});


// Funciones para manejo de votaciones
const handleVotacionCreated = (votacionId: number) => {
    // Agregar la nueva votación a las seleccionadas
    if (!form.votacion_ids.includes(votacionId)) {
        form.votacion_ids.push(votacionId);
    }
    showCreateVotacionModal.value = false;
};

const removeVotacion = (votacionId: number) => {
    const index = form.votacion_ids.indexOf(votacionId);
    if (index > -1) {
        form.votacion_ids.splice(index, 1);
    }
};

// Computed para obtener las votaciones seleccionadas
const votacionesSeleccionadas = computed(() => {
    if (!props.votaciones || !form.votacion_ids.length) return [];
    return props.votaciones.filter(v => form.votacion_ids.includes(v.id));
});

// Computed para obtener las votaciones disponibles (no seleccionadas)
const votacionesDisponibles = computed(() => {
    if (!props.votaciones) return [];
    return props.votaciones.filter(v => !form.votacion_ids.includes(v.id));
});

// Enviar formulario
const submit = () => {
    if (props.asamblea) {
        form.put(route('admin.asambleas.update', props.asamblea.id), {
            preserveScroll: true,
            onError: () => {
                // Los errores se manejan automáticamente por Inertia
            },
        });
    } else {
        form.post(route('admin.asambleas.store'), {
            preserveScroll: true,
            onError: () => {
                // Los errores se manejan automáticamente por Inertia
            },
        });
    }
};

// Cancelar y volver
const cancelar = () => {
    router.visit(route('admin.asambleas.index'));
};
</script>

<template>
    <Head :title="asamblea ? 'Editar Asamblea' : 'Nueva Asamblea'" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">
                        {{ asamblea ? 'Editar Asamblea' : 'Nueva Asamblea' }}
                    </h1>
                    <p class="text-muted-foreground">
                        {{ asamblea ? 'Modifica los datos de la asamblea' : 'Completa los datos para crear una nueva asamblea' }}
                    </p>
                </div>
                <Button variant="outline" @click="cancelar">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver
                </Button>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Información General -->
                <Card>
                    <CardHeader>
                        <CardTitle>Información General</CardTitle>
                        <CardDescription>
                            Datos básicos de la asamblea
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="nombre">Nombre *</Label>
                                <Input
                                    id="nombre"
                                    v-model="form.nombre"
                                    placeholder="Ej: Asamblea General Ordinaria 2024"
                                    :class="{ 'border-red-500': form.errors.nombre }"
                                />
                                <span v-if="form.errors.nombre" class="text-sm text-red-500">
                                    {{ form.errors.nombre }}
                                </span>
                            </div>

                            <div class="space-y-2">
                                <Label for="tipo">Tipo *</Label>
                                <Select v-model="form.tipo">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.tipo }">
                                        <SelectValue placeholder="Selecciona el tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="ordinaria">Ordinaria</SelectItem>
                                        <SelectItem value="extraordinaria">Extraordinaria</SelectItem>
                                    </SelectContent>
                                </Select>
                                <span v-if="form.errors.tipo" class="text-sm text-red-500">
                                    {{ form.errors.tipo }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="descripcion">Descripción</Label>
                            <Textarea
                                id="descripcion"
                                v-model="form.descripcion"
                                placeholder="Describe el propósito y agenda de la asamblea..."
                                rows="3"
                                :class="{ 'border-red-500': form.errors.descripcion }"
                            />
                            <span v-if="form.errors.descripcion" class="text-sm text-red-500">
                                {{ form.errors.descripcion }}
                            </span>
                        </div>

                        <div v-if="asamblea" class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="estado">Estado</Label>
                                <Select v-model="form.estado">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.estado }">
                                        <SelectValue placeholder="Selecciona el estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="programada">Programada</SelectItem>
                                        <SelectItem value="en_curso">En Curso</SelectItem>
                                        <SelectItem value="finalizada">Finalizada</SelectItem>
                                        <SelectItem value="cancelada">Cancelada</SelectItem>
                                    </SelectContent>
                                </Select>
                                <span v-if="form.errors.estado" class="text-sm text-red-500">
                                    {{ form.errors.estado }}
                                </span>
                            </div>

                            <div class="space-y-2">
                                <Label for="acta_url">URL del Acta</Label>
                                <Input
                                    id="acta_url"
                                    v-model="form.acta_url"
                                    placeholder="https://ejemplo.com/acta.pdf"
                                    :class="{ 'border-red-500': form.errors.acta_url }"
                                />
                                <span v-if="form.errors.acta_url" class="text-sm text-red-500">
                                    {{ form.errors.acta_url }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Fechas y Horarios -->
                <Card>
                    <CardHeader>
                        <CardTitle>Fechas y Horarios</CardTitle>
                        <CardDescription>
                            Define cuándo se realizará la asamblea
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="fecha_inicio">Fecha y Hora de Inicio *</Label>
                                <DateTimePicker
                                    v-model="form.fecha_inicio"
                                    placeholder="Seleccionar fecha y hora de inicio"
                                    :class="{ 'border-red-500': form.errors.fecha_inicio }"
                                />
                                <span v-if="form.errors.fecha_inicio" class="text-sm text-red-500">
                                    {{ form.errors.fecha_inicio }}
                                </span>
                            </div>

                            <div class="space-y-2">
                                <Label for="fecha_fin">Fecha y Hora de Fin *</Label>
                                <DateTimePicker
                                    v-model="form.fecha_fin"
                                    placeholder="Seleccionar fecha y hora de fin"
                                    :class="{ 'border-red-500': form.errors.fecha_fin || fechaFinError }"
                                />
                                <span v-if="fechaFinError" class="text-sm text-red-500">
                                    {{ fechaFinError }}
                                </span>
                                <span v-else-if="form.errors.fecha_fin" class="text-sm text-red-500">
                                    {{ form.errors.fecha_fin }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Ubicación -->
                <Card>
                    <CardHeader>
                        <CardTitle>Ubicación</CardTitle>
                        <CardDescription>
                            Define dónde se realizará la asamblea
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <div class="space-y-2">
                                <Label for="territorio">Territorio</Label>
                                <Select v-model="form.territorio_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona territorio (opcional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem 
                                            v-for="territorio in territorios" 
                                            :key="territorio.id"
                                            :value="territorio.id.toString()"
                                        >
                                            {{ territorio.nombre }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <Label for="departamento">Departamento</Label>
                                <Select v-model="form.departamento_id" :disabled="!form.territorio_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona departamento (opcional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem 
                                            v-for="departamento in departamentosFiltrados" 
                                            :key="departamento.id"
                                            :value="departamento.id.toString()"
                                        >
                                            {{ departamento.nombre }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <Label for="municipio">Municipio</Label>
                                <Select v-model="form.municipio_id" :disabled="!form.departamento_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona municipio (opcional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem 
                                            v-for="municipio in municipiosFiltrados" 
                                            :key="municipio.id"
                                            :value="municipio.id.toString()"
                                        >
                                            {{ municipio.nombre }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <Label for="localidad">Localidad</Label>
                                <Select v-model="form.localidad_id" :disabled="!form.municipio_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona localidad (opcional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem 
                                            v-for="localidad in localidadesFiltradas" 
                                            :key="localidad.id"
                                            :value="localidad.id.toString()"
                                        >
                                            {{ localidad.nombre }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="lugar">Dirección o Lugar</Label>
                            <Input
                                id="lugar"
                                v-model="form.lugar"
                                placeholder="Ej: Salón Principal, Calle 123 #45-67"
                                :class="{ 'border-red-500': form.errors.lugar }"
                            />
                            <span v-if="form.errors.lugar" class="text-sm text-red-500">
                                {{ form.errors.lugar }}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Configuración -->
                <Card>
                    <CardHeader>
                        <CardTitle>Configuración</CardTitle>
                        <CardDescription>
                            Opciones adicionales de la asamblea
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="quorum_minimo">Quórum Mínimo</Label>
                                <Input
                                    id="quorum_minimo"
                                    v-model.number="form.quorum_minimo"
                                    type="number"
                                    min="1"
                                    placeholder="Ej: 50"
                                    :class="{ 'border-red-500': form.errors.quorum_minimo }"
                                />
                                <span v-if="form.errors.quorum_minimo" class="text-sm text-red-500">
                                    {{ form.errors.quorum_minimo }}
                                </span>
                                <p class="text-sm text-muted-foreground">
                                    Número mínimo de asistentes requeridos
                                </p>
                            </div>

                            <div class="flex items-center space-x-2 pt-8">
                                <Switch
                                    id="activo"
                                    v-model="form.activo"
                                />
                                <Label for="activo" class="cursor-pointer">
                                    Asamblea Activa
                                </Label>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Videoconferencia -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Video class="h-5 w-5" />
                            Videoconferencia
                        </CardTitle>
                        <CardDescription>
                            Configuración de videoconferencia con Zoom para la asamblea
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center space-x-2">
                            <Switch
                                id="zoom_enabled"
                                v-model="form.zoom_enabled"
                            />
                            <Label for="zoom_enabled" class="cursor-pointer">
                                Habilitar videoconferencia
                            </Label>
                        </div>
                        
                        <div v-if="form.zoom_enabled" class="space-y-4 pl-6 border-l-2 border-blue-200">
                            <!-- Selector de tipo de integración -->
                            <div class="space-y-3">
                                <Label class="text-sm font-medium">Tipo de Integración</Label>
                                <RadioGroup v-model="form.zoom_integration_type" class="flex gap-6">
                                    <div class="flex items-center space-x-2">
                                        <RadioGroupItem value="sdk" id="sdk" />
                                        <Label for="sdk" class="cursor-pointer">
                                            SDK (Automático)
                                        </Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <RadioGroupItem value="api" id="api" />
                                        <Label for="api" class="cursor-pointer">
                                            API (Manual)
                                        </Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <RadioGroupItem value="message" id="message" />
                                        <Label for="message" class="cursor-pointer">
                                            Mensaje estático
                                        </Label>
                                    </div>
                                </RadioGroup>
                                <p class="text-xs text-muted-foreground">
                                    SDK: Reuniones generadas automáticamente. API: Configuración manual de reunión existente. Mensaje: Mostrar un mensaje personalizado.
                                </p>
                            </div>
                            
                            <!-- Configuración para modo SDK -->
                            <div v-if="form.zoom_integration_type === 'sdk'" class="space-y-4">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="zoom_meeting_type">Tipo de Reunión</Label>
                                        <Select v-model="form.zoom_meeting_type">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecciona el tipo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="scheduled">Programada</SelectItem>
                                            <SelectItem value="instant">Instantánea</SelectItem>
                                            <SelectItem value="recurring">Recurrente</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p class="text-sm text-muted-foreground">
                                        Se recomienda "Programada" para asambleas
                                    </p>
                                </div>

                                <div v-if="asamblea?.zoom_meeting_id" class="space-y-2">
                                    <Label>Estado de la Reunión</Label>
                                    <div class="flex items-center gap-2 p-2 bg-green-50 rounded-md">
                                        <Video class="h-4 w-4 text-green-600" />
                                        <span class="text-sm text-green-800">Reunión creada</span>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        ID: {{ asamblea.zoom_meeting_id }}
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <Label class="text-sm font-medium flex items-center gap-2">
                                    <Settings class="h-4 w-4" />
                                    Configuraciones de la Reunión
                                </Label>
                                
                                <div class="grid gap-3 md:grid-cols-2">
                                    <div class="flex items-center space-x-2">
                                        <Switch
                                            id="host_video"
                                            v-model="form.zoom_settings.host_video"
                                        />
                                        <Label for="host_video" class="text-sm cursor-pointer flex items-center gap-1">
                                            <Camera class="h-3 w-3" />
                                            Video del moderador
                                        </Label>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <Switch
                                            id="participant_video"
                                            v-model="form.zoom_settings.participant_video"
                                        />
                                        <Label for="participant_video" class="text-sm cursor-pointer flex items-center gap-1">
                                            <Users class="h-3 w-3" />
                                            Video de participantes
                                        </Label>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <Switch
                                            id="waiting_room"
                                            v-model="form.zoom_settings.waiting_room"
                                        />
                                        <Label for="waiting_room" class="text-sm cursor-pointer">
                                            Sala de espera
                                        </Label>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <Switch
                                            id="mute_upon_entry"
                                            v-model="form.zoom_settings.mute_upon_entry"
                                        />
                                        <Label for="mute_upon_entry" class="text-sm cursor-pointer flex items-center gap-1">
                                            <Mic class="h-3 w-3" />
                                            Silenciar al entrar
                                        </Label>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="auto_recording" class="text-sm">Grabación automática</Label>
                                    <Select v-model="form.zoom_settings.auto_recording">
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">Sin grabación</SelectItem>
                                            <SelectItem value="local">Grabación local</SelectItem>
                                            <SelectItem value="cloud">Grabación en la nube</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                                <div class="bg-blue-50 p-3 rounded-md">
                                    <p class="text-sm text-blue-800">
                                        <strong>Nota:</strong> La reunión de Zoom se creará automáticamente cuando guardes la asamblea. 
                                        Los participantes podrán acceder a la videoconferencia desde la vista de la asamblea.
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Configuración para modo API -->
                            <div v-if="form.zoom_integration_type === 'api'" class="space-y-4">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="zoom_meeting_id_api">Meeting ID</Label>
                                        <Input
                                            id="zoom_meeting_id_api"
                                            v-model="form.zoom_meeting_id"
                                            placeholder="Ej: 123456789"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            ID de la reunión existente en Zoom
                                        </p>
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <Label for="zoom_occurrence_ids">Occurrence IDs</Label>
                                        <Input
                                            id="zoom_occurrence_ids"
                                            v-model="form.zoom_occurrence_ids"
                                            placeholder="Ej: 1648194360000,1648280760000"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            IDs de ocurrencias separados por comas (opcional)
                                        </p>
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <Label for="zoom_prefix">Prefijo</Label>
                                        <Input
                                            id="zoom_prefix"
                                            v-model="form.zoom_prefix"
                                            placeholder="Ej: CH"
                                            maxlength="10"
                                            class="w-24"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            Prefijo que se añadirá al nombre en Zoom
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <Label for="zoom_registration_open_date">Apertura de inscripciones</Label>
                                    <DateTimePicker
                                        id="zoom_registration_open_date"
                                        v-model="form.zoom_registration_open_date"
                                        placeholder="Seleccionar fecha y hora"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Fecha y hora a partir de la cual los usuarios pueden generar su link personal (opcional)
                                    </p>
                                </div>
                                
                                <!-- Mensaje personalizado para modo API -->
                                <div class="space-y-4 p-4 border rounded-lg bg-gray-50">
                                    <div class="flex items-center space-x-2">
                                        <Switch
                                            id="zoom_api_message_enabled"
                                            v-model="form.zoom_api_message_enabled"
                                        />
                                        <Label for="zoom_api_message_enabled" class="cursor-pointer">
                                            Mostrar mensaje personalizado encima del enlace
                                        </Label>
                                    </div>
                                    
                                    <div v-if="form.zoom_api_message_enabled" class="space-y-2">
                                        <Label for="zoom_api_message">Mensaje a mostrar</Label>
                                        <Textarea
                                            id="zoom_api_message"
                                            v-model="form.zoom_api_message"
                                            placeholder="Escriba aquí el mensaje que se mostrará encima del enlace de Zoom..."
                                            rows="4"
                                            class="min-h-[100px]"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            Este mensaje aparecerá destacado encima del botón de generar/acceder al enlace de Zoom.
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="zoom_meeting_password_api">Contraseña de la Reunión</Label>
                                        <Input
                                            id="zoom_meeting_password_api"
                                            v-model="form.zoom_meeting_password"
                                            placeholder="Contraseña (opcional)"
                                        />
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <Label for="zoom_join_url">Join URL</Label>
                                        <Input
                                            id="zoom_join_url"
                                            v-model="form.zoom_join_url"
                                            placeholder="https://zoom.us/j/123456789"
                                        />
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <Label for="zoom_start_url">Start URL</Label>
                                    <Input
                                        id="zoom_start_url"
                                        v-model="form.zoom_start_url"
                                        placeholder="https://zoom.us/s/123456789"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        URL para iniciar la reunión (para moderadores)
                                    </p>
                                </div>
                                
                                <div class="bg-orange-50 p-3 rounded-md">
                                    <p class="text-sm text-orange-800">
                                        <strong>Modo API:</strong> Los campos se completan manualmente. Los usuarios verán un botón 
                                        para generar su link personal de acceso a la reunión.
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Configuración para mensaje estático -->
                            <div v-if="form.zoom_integration_type === 'message'" class="space-y-4">
                                <div class="space-y-2">
                                    <Label for="zoom_static_message">Mensaje a mostrar</Label>
                                    <Textarea
                                        id="zoom_static_message"
                                        v-model="form.zoom_static_message"
                                        placeholder="Escriba aquí el mensaje que se mostrará en lugar de la videoconferencia..."
                                        rows="5"
                                        class="min-h-[120px]"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Este mensaje se mostrará a los usuarios en el espacio donde normalmente aparecería la videoconferencia.
                                    </p>
                                </div>
                                
                                <div class="bg-blue-50 p-3 rounded-md">
                                    <p class="text-sm text-blue-800">
                                        <strong>Modo Mensaje:</strong> En lugar de una videoconferencia, los usuarios verán el mensaje 
                                        personalizado que configure aquí.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <span v-if="form.errors.zoom_enabled" class="text-sm text-red-500">
                            {{ form.errors.zoom_enabled }}
                        </span>
                    </CardContent>
                </Card>

                <!-- Configuración de Consulta Pública de Participantes -->
                <Card>
                    <CardHeader>
                        <CardTitle>
                            <Users class="inline-block mr-2 h-5 w-5" />
                            Consulta Pública de Participantes
                        </CardTitle>
                        <CardDescription>
                            Configure el acceso público sin autenticación para consultar participantes de la asamblea
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Toggle principal -->
                        <div class="flex items-center space-x-2">
                            <Switch 
                                id="public_participants"
                                v-model="form.public_participants_enabled"
                            />
                            <Label for="public_participants">
                                Habilitar consulta pública de participantes (sin autenticación)
                            </Label>
                        </div>
                        
                        <!-- Opciones de modo (solo si está habilitado) -->
                        <div v-if="form.public_participants_enabled" class="space-y-4">
                            <div class="space-y-2">
                                <Label>Modo de visualización pública</Label>
                                <RadioGroup v-model="form.public_participants_mode">
                                    <div class="flex items-start space-x-2 mb-3">
                                        <RadioGroupItem value="list" id="mode_list" />
                                        <div class="space-y-1">
                                            <Label for="mode_list" class="font-medium cursor-pointer">
                                                Modo Listado
                                            </Label>
                                            <p class="text-sm text-muted-foreground">
                                                Muestra tabla completa con filtros avanzados. Los visitantes pueden ver:
                                                nombre, departamento, municipio y localidad de todos los participantes.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-start space-x-2">
                                        <RadioGroupItem value="search" id="mode_search" />
                                        <div class="space-y-1">
                                            <Label for="mode_search" class="font-medium cursor-pointer">
                                                Modo Búsqueda
                                            </Label>
                                            <p class="text-sm text-muted-foreground">
                                                Solo muestra un campo de búsqueda. Los visitantes pueden buscar por nombre,
                                                cédula o correo, y ver únicamente si la persona es o no participante.
                                            </p>
                                        </div>
                                    </div>
                                </RadioGroup>
                            </div>
                            
                            <!-- Preview de URL -->
                            <Alert>
                                <Info class="h-4 w-4" />
                                <AlertTitle>URL Pública</AlertTitle>
                                <AlertDescription>
                                    <p class="mb-2">Los participantes estarán disponibles públicamente en:</p>
                                    <code class="block bg-gray-100 p-2 rounded text-sm">
                                        {{ baseUrl }}/asambleas/{{ asamblea?.id || '{id}' }}/participantes-publico
                                    </code>
                                    <p class="mt-2 text-xs">
                                        Esta URL será accesible sin necesidad de autenticación cuando la asamblea esté activa.
                                    </p>
                                </AlertDescription>
                            </Alert>
                            
                            <!-- Información de seguridad -->
                            <Alert variant="outline">
                                <Info class="h-4 w-4" />
                                <AlertTitle>Información de Privacidad</AlertTitle>
                                <AlertDescription>
                                    <ul class="list-disc list-inside text-sm space-y-1">
                                        <li v-if="form.public_participants_mode === 'list'">
                                            En modo listado, NO se mostrarán: correo electrónico, teléfono, documento de identidad,
                                            ni información de asistencia.
                                        </li>
                                        <li v-else>
                                            En modo búsqueda, solo se confirmará si la persona es participante o no.
                                            No se revelará ningún dato personal adicional.
                                        </li>
                                        <li>Los datos expuestos cumplen con las políticas de privacidad.</li>
                                    </ul>
                                </AlertDescription>
                            </Alert>
                        </div>
                        
                        <span v-if="form.errors.public_participants_enabled" class="text-sm text-red-500">
                            {{ form.errors.public_participants_enabled }}
                        </span>
                        <span v-if="form.errors.public_participants_mode" class="text-sm text-red-500">
                            {{ form.errors.public_participants_mode }}
                        </span>
                    </CardContent>
                </Card>

                <!-- Votaciones Asociadas -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Vote class="h-5 w-5" />
                            Votaciones Asociadas
                        </CardTitle>
                        <CardDescription>
                            Vincule votaciones existentes o cree nuevas votaciones para esta asamblea
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Selector de votaciones existentes -->
                        <div class="space-y-2">
                            <Label>Vincular votaciones existentes</Label>
                            <div class="flex gap-2">
                                <Select 
                                    :model-value="''"
                                    @update:model-value="(val) => val && !form.votacion_ids.includes(Number(val)) && form.votacion_ids.push(Number(val))"
                                >
                                    <SelectTrigger class="flex-1">
                                        <SelectValue placeholder="Seleccionar votación para agregar..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem 
                                            v-for="votacion in votacionesDisponibles" 
                                            :key="votacion.id"
                                            :value="votacion.id.toString()"
                                        >
                                            {{ votacion.titulo }}
                                            <span class="text-xs text-muted-foreground ml-2">
                                                ({{ votacion.estado }})
                                            </span>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                
                                <Button
                                    type="button"
                                    @click="showCreateVotacionModal = true"
                                    variant="outline"
                                >
                                    <Plus class="h-4 w-4 mr-2" />
                                    Crear nueva
                                </Button>
                            </div>
                        </div>

                        <!-- Lista de votaciones seleccionadas -->
                        <div v-if="votacionesSeleccionadas.length > 0" class="space-y-2">
                            <Label>Votaciones vinculadas</Label>
                            <div class="space-y-2">
                                <div 
                                    v-for="votacion in votacionesSeleccionadas" 
                                    :key="votacion.id"
                                    class="flex items-center justify-between p-3 border rounded-lg bg-gray-50 dark:bg-gray-900"
                                >
                                    <div class="flex-1">
                                        <div class="font-medium">{{ votacion.titulo }}</div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ votacion.descripcion }}
                                        </div>
                                        <div class="text-xs text-muted-foreground mt-1">
                                            Estado: {{ votacion.estado }} | 
                                            Inicio: {{ new Date(votacion.fecha_inicio).toLocaleDateString() }} | 
                                            Fin: {{ new Date(votacion.fecha_fin).toLocaleDateString() }}
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        @click="removeVotacion(votacion.id)"
                                        variant="ghost"
                                        size="sm"
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                        
                        <Alert v-else>
                            <Info class="h-4 w-4" />
                            <AlertTitle>Sin votaciones vinculadas</AlertTitle>
                            <AlertDescription>
                                Esta asamblea no tiene votaciones asociadas. Puede vincular votaciones existentes o crear nuevas.
                            </AlertDescription>
                        </Alert>

                        <span v-if="form.errors.votacion_ids" class="text-sm text-red-500">
                            {{ form.errors.votacion_ids }}
                        </span>
                    </CardContent>
                </Card>

                <!-- Botones de acción -->
                <div class="flex justify-end gap-4">
                    <Button type="button" variant="outline" @click="cancelar">
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        {{ asamblea ? 'Actualizar' : 'Crear' }} Asamblea
                    </Button>
                </div>
            </form>
        </div>

        <!-- Modal de creación rápida de votación -->
        <CreateVotacionModal 
            v-if="showCreateVotacionModal"
            :open="showCreateVotacionModal"
            @close="showCreateVotacionModal = false"
            @created="handleVotacionCreated"
        />
    </AdminLayout>
</template>