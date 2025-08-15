<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { DateTimePicker } from '@/components/ui/datetime-picker';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ArrowLeft, MapPin, Save, Video, Settings, Users, Mic, Camera } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

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
    zoom_meeting_id?: string;
    zoom_meeting_password?: string;
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

interface Props {
    asamblea?: Asamblea | null;
    territorios: Territorio[];
    departamentos: Departamento[];
    municipios: Municipio[];
    localidades: Localidad[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Asambleas', href: '/admin/asambleas' },
    { title: props.asamblea ? 'Editar' : 'Nueva', href: '#' },
];

// Helper para obtener route
const { route } = window as any;

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
    zoom_meeting_type: props.asamblea?.zoom_meeting_type || 'scheduled',
    zoom_settings: props.asamblea?.zoom_settings || {
        host_video: true,
        participant_video: false,
        waiting_room: true,
        mute_upon_entry: true,
        auto_recording: 'none'
    },
});

// Validaciones de fecha
const fechaInicioError = computed(() => {
    if (!form.fecha_inicio) return '';
    const fecha = new Date(form.fecha_inicio);
    const ahora = new Date();
    if (fecha < ahora) {
        return 'La fecha de inicio debe ser posterior a la fecha actual';
    }
    return '';
});

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

    <AppLayout :breadcrumbs="breadcrumbs">
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
                                    :class="{ 'border-red-500': form.errors.fecha_inicio || fechaInicioError }"
                                />
                                <span v-if="fechaInicioError" class="text-sm text-red-500">
                                    {{ fechaInicioError }}
                                </span>
                                <span v-else-if="form.errors.fecha_inicio" class="text-sm text-red-500">
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
                        
                        <span v-if="form.errors.zoom_enabled" class="text-sm text-red-500">
                            {{ form.errors.zoom_enabled }}
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
    </AppLayout>
</template>