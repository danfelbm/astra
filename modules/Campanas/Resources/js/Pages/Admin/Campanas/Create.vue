<script setup lang="ts">
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { Textarea } from "@modules/Core/Resources/js/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@modules/Core/Resources/js/components/ui/select";
import { Switch } from "@modules/Core/Resources/js/components/ui/switch";
import { Alert, AlertDescription } from "@modules/Core/Resources/js/components/ui/alert";
import { RadioGroup, RadioGroupItem } from "@modules/Core/Resources/js/components/ui/radio-group";
import { type BreadcrumbItemType } from '@/types';
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import SegmentSelector from "@modules/Campanas/Resources/js/Components/SegmentSelector.vue";
import WhatsAppGroupSelector from "@modules/Campanas/Resources/js/Components/WhatsAppGroupSelector.vue";
import AdvancedFilters from "@modules/Core/Resources/js/components/filters/AdvancedFilters.vue";
import type { AdvancedFilterConfig } from "@modules/Core/Resources/js/types/filters";
import { Head, useForm, router } from '@inertiajs/vue3';
import { ArrowLeft, Save, Send, Mail, MessageSquare, Calendar, Users, Info, AlertCircle, Filter } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';
import { debounce } from 'lodash-es';

interface Segmento {
    id: number;
    nombre: string;
    descripcion?: string;
    tipo: string;
    count: number;
}

interface Plantilla {
    id: number;
    nombre: string;
    asunto?: string;
    descripcion?: string;
    variables_usadas?: string[];
}

interface WhatsAppGroup {
    id: number;
    group_jid: string;
    nombre: string;
    descripcion?: string;
    tipo: 'grupo' | 'comunidad';
    avatar_url?: string;
    participantes_count: number;
}

interface Campana {
    id?: number;
    nombre: string;
    descripcion?: string;
    tipo: 'email' | 'whatsapp' | 'ambos';
    whatsapp_mode?: 'individual' | 'grupos' | 'mixto';
    estado?: string;
    segment_id?: number;
    audience_mode?: 'segment' | 'manual';
    filters?: any;
    plantilla_email_id?: number;
    plantilla_whatsapp_id?: number;
    fecha_programada?: string;
    configuracion?: any;
    whatsapp_groups?: WhatsAppGroup[];
}

interface Props {
    campana?: Campana | null;
    segmentos?: Segmento[];
    plantillasEmail?: Plantilla[];
    plantillasWhatsApp?: Plantilla[];
    tiposOptions?: string[];
    estadosOptions?: string[];
    whatsappModesOptions?: Record<string, string>;
    whatsappGrupos?: WhatsAppGroup[];
    batchSizeEmailDefault?: number;
    batchSizeWhatsAppDefault?: number;
    whatsAppDelayDefault?: { min: number; max: number };
    filterFieldsConfig?: any[]; // Configuración de campos para AdvancedFilters (modo manual)
}

const props = withDefaults(defineProps<Props>(), {
    segmentos: () => [],
    plantillasEmail: () => [],
    plantillasWhatsApp: () => [],
    tiposOptions: () => ['email', 'whatsapp', 'ambos'],
    whatsappModesOptions: () => ({ individual: 'Contactos Individuales', grupos: 'Grupos de WhatsApp', mixto: 'Contactos y Grupos' }),
    whatsappGrupos: () => [],
});

const isEditing = computed(() => !!props.campana?.id);

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Campañas', href: '/admin/envio-campanas' },
    { title: isEditing.value ? 'Editar Campaña' : 'Nueva Campaña', href: '#' },
];

const form = useForm({
    nombre: props.campana?.nombre || '',
    descripcion: props.campana?.descripcion || '',
    tipo: props.campana?.tipo || 'email',
    whatsapp_mode: props.campana?.whatsapp_mode || 'individual',
    estado: props.campana?.estado || 'borrador',
    segment_id: props.campana?.segment_id || null,
    audience_mode: props.campana?.audience_mode || 'segment', // 'segment' o 'manual'
    filters: props.campana?.filters || {}, // Filtros para modo manual
    plantilla_email_id: props.campana?.plantilla_email_id || null,
    plantilla_whatsapp_id: props.campana?.plantilla_whatsapp_id || null,
    whatsapp_group_ids: props.campana?.whatsapp_groups?.map(g => g.id) || [],
    fecha_programada: props.campana?.fecha_programada || '',
    es_programada: !!props.campana?.fecha_programada,
    configuracion: {
        batch_size_email: props.campana?.configuracion?.batch_size_email || props.batchSizeEmailDefault || 50,
        // WhatsApp: solo intervalos (no batch size - Evolution API es uno a uno)
        whatsapp_delay_min: props.campana?.configuracion?.whatsapp_delay_min || props.whatsAppDelayDefault?.min || 5,
        whatsapp_delay_max: props.campana?.configuracion?.whatsapp_delay_max || props.whatsAppDelayDefault?.max || 30,
        enable_tracking: props.campana?.configuracion?.enable_tracking ?? true,
        enable_pixel_tracking: props.campana?.configuracion?.enable_pixel_tracking ?? true,
        enable_click_tracking: props.campana?.configuracion?.enable_click_tracking ?? true,
    }
});

const selectedSegment = computed(() => {
    return props.segmentos.find(s => s.id === form.segment_id);
});

const requiresEmail = computed(() => form.tipo === 'email' || form.tipo === 'ambos');
const requiresWhatsApp = computed(() => form.tipo === 'whatsapp' || form.tipo === 'ambos');
const requiresWhatsAppGroups = computed(() => requiresWhatsApp.value && (form.whatsapp_mode === 'grupos' || form.whatsapp_mode === 'mixto'));
const requiresWhatsAppIndividual = computed(() => requiresWhatsApp.value && (form.whatsapp_mode === 'individual' || form.whatsapp_mode === 'mixto'));

// Estado para modo manual de audiencia
const manualFilterCount = ref<number | null>(null);
const isCountingUsers = ref(false);

// Configuración para AdvancedFilters
const filterConfig = computed<AdvancedFilterConfig>(() => ({
    fields: props.filterFieldsConfig || [],
    showQuickSearch: true,
    quickSearchPlaceholder: 'Buscar por nombre o email...',
    quickSearchFields: ['name', 'email'],
    maxNestingLevel: 2,
    allowSaveFilters: false,
    autoApply: false,
}));

// Manejar aplicación de filtros desde AdvancedFilters
const handleFiltersApply = async (params: any) => {
    form.filters = params;
    form.segment_id = null; // Limpiar segmento al usar filtros manuales
    await countFilteredUsers();
};

// Contar usuarios filtrados (con debounce)
const countFilteredUsers = debounce(async () => {
    const filters = form.filters;

    // advanced_filters viene como STRING JSON desde AdvancedFilters
    // Verificar si hay filtros definidos
    const hasConditions = !!filters?.advanced_filters || !!filters?.search;

    if (!hasConditions) {
        manualFilterCount.value = null;
        return;
    }

    isCountingUsers.value = true;
    try {
        const response = await axios.post('/admin/envio-campanas/count-users', { filters });
        manualFilterCount.value = response.data.count;
    } catch (error) {
        console.error('Error contando usuarios:', error);
        manualFilterCount.value = null;
        toast.error('Error al contar usuarios');
    } finally {
        isCountingUsers.value = false;
    }
}, 500);

// Limpiar filtros manuales
const handleFiltersClear = () => {
    form.filters = {};
    manualFilterCount.value = null;
};

// NO reseteamos al cambiar de modo - los datos persisten hasta salir de la página
// Solo se usa el modo activo (segment_id o filters) al guardar

watch(() => form.tipo, (newTipo) => {
    // Limpiar plantillas no necesarias según el tipo
    if (newTipo === 'email') {
        form.plantilla_whatsapp_id = null;
    } else if (newTipo === 'whatsapp') {
        form.plantilla_email_id = null;
    }
});

const submit = () => {
    // Validar audiencia según el modo (solo aplica si hay envíos individuales)
    if (requiresWhatsAppIndividual.value || requiresEmail.value) {
        if (form.audience_mode === 'segment') {
            if (!form.segment_id) {
                toast.error('Debes seleccionar un segmento');
                return;
            }
        } else if (form.audience_mode === 'manual') {
            if (!manualFilterCount.value || manualFilterCount.value <= 0) {
                toast.error('Debes definir filtros que generen al menos un destinatario');
                return;
            }
        }
    }

    if (requiresEmail.value && !form.plantilla_email_id) {
        toast.error('Debes seleccionar una plantilla de email');
        return;
    }

    if (requiresWhatsApp.value && !form.plantilla_whatsapp_id) {
        toast.error('Debes seleccionar una plantilla de WhatsApp');
        return;
    }

    // Validar grupos de WhatsApp si el modo lo requiere
    if (requiresWhatsAppGroups.value && form.whatsapp_group_ids.length === 0) {
        toast.error('Debes seleccionar al menos un grupo de WhatsApp');
        return;
    }

    if (isEditing.value) {
        form.put(`/admin/envio-campanas/${props.campana?.id}`, {
            onSuccess: () => {
                toast.success('Campaña actualizada exitosamente');
            },
            onError: () => {
                toast.error('Error al actualizar la campaña');
            }
        });
    } else {
        form.post('/admin/envio-campanas', {
            onSuccess: () => {
                toast.success('Campaña creada exitosamente');
            },
            onError: () => {
                toast.error('Error al crear la campaña');
            }
        });
    }
};

const canSubmit = computed(() => {
    const baseValid = form.nombre &&
        !form.processing &&
        (requiresEmail.value ? form.plantilla_email_id : true) &&
        (requiresWhatsApp.value ? form.plantilla_whatsapp_id : true);

    // Validar grupos de WhatsApp si se requieren
    const gruposValid = !requiresWhatsAppGroups.value || form.whatsapp_group_ids.length > 0;

    // Validar audiencia según el modo (solo si hay envíos individuales)
    if (requiresWhatsAppIndividual.value || requiresEmail.value) {
        if (form.audience_mode === 'segment') {
            return baseValid && gruposValid && form.segment_id;
        } else {
            // Modo manual: requiere filtros con al menos un destinatario
            return baseValid && gruposValid && manualFilterCount.value && manualFilterCount.value > 0;
        }
    }

    // Solo grupos de WhatsApp: no requiere audiencia individual
    return baseValid && gruposValid;
});

// Método para toggle de programación
const toggleProgramacion = (value) => {
    form.es_programada = value;
    
    if (!value) {
        form.fecha_programada = '';
    }
};

// Métodos para tracking switches
const togglePixelTracking = (value) => {
    form.configuracion.enable_pixel_tracking = value;
};

const toggleClickTracking = (value) => {
    form.configuracion.enable_click_tracking = value;
};
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head :title="isEditing ? 'Editar Campaña' : 'Nueva Campaña'" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">
                        {{ isEditing ? 'Editar Campaña' : 'Nueva Campaña' }}
                    </h1>
                    <p class="text-muted-foreground mt-1">
                        {{ isEditing 
                            ? 'Modifica los detalles de la campaña' 
                            : 'Configura y programa tu campaña de comunicación' 
                        }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button 
                        type="button" 
                        variant="outline"
                        @click="router.visit('/admin/envio-campanas')"
                    >
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        :disabled="!canSubmit"
                    >
                        <Save class="w-4 h-4 mr-2" />
                        {{ isEditing ? 'Actualizar' : 'Guardar' }} Campaña
                    </Button>
                </div>
            </div>

            <Alert v-if="Object.keys(form.errors).length > 0" variant="destructive">
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>
                    <ul class="list-disc pl-4">
                        <li v-for="(error, key) in form.errors" :key="key">
                            {{ error }}
                        </li>
                    </ul>
                </AlertDescription>
            </Alert>

            <!-- Información Básica -->
            <Card>
                <CardHeader>
                    <CardTitle>Información Básica</CardTitle>
                    <CardDescription>
                        Define el nombre y tipo de campaña
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="nombre">
                                Nombre de la Campaña <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="nombre"
                                v-model="form.nombre"
                                placeholder="Ej: Recordatorio Asamblea General"
                                :error="form.errors.nombre"
                            />
                        </div>
                        <div>
                            <Label>Tipo de Campaña <span class="text-destructive">*</span></Label>
                            <RadioGroup v-model="form.tipo" class="flex flex-wrap gap-4 mt-2">
                                <div class="flex items-center space-x-2">
                                    <RadioGroupItem value="email" id="tipo-email" />
                                    <Label htmlFor="tipo-email" class="flex items-center gap-1 cursor-pointer">
                                        <Mail class="w-4 h-4" />
                                        Email
                                    </Label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <RadioGroupItem value="whatsapp" id="tipo-whatsapp" />
                                    <Label htmlFor="tipo-whatsapp" class="flex items-center gap-1 cursor-pointer">
                                        <MessageSquare class="w-4 h-4" />
                                        WhatsApp
                                    </Label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <RadioGroupItem value="ambos" id="tipo-ambos" />
                                    <Label htmlFor="tipo-ambos" class="flex items-center gap-1 cursor-pointer">
                                        <Send class="w-4 h-4" />
                                        Ambos
                                    </Label>
                                </div>
                            </RadioGroup>
                        </div>
                    </div>
                    <div>
                        <Label htmlFor="descripcion">Descripción</Label>
                        <Textarea
                            id="descripcion"
                            v-model="form.descripcion"
                            placeholder="Describe el objetivo de esta campaña..."
                            rows="3"
                        />
                    </div>
                </CardContent>
            </Card>

            <!-- Audiencia (solo si requiere envíos individuales) -->
            <Card v-if="requiresEmail || requiresWhatsAppIndividual">
                <CardHeader>
                    <CardTitle>Audiencia</CardTitle>
                    <CardDescription>
                        Define quiénes recibirán esta campaña
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Selector de modo de audiencia -->
                    <RadioGroup v-model="form.audience_mode" class="flex gap-4">
                        <div class="flex items-center space-x-2">
                            <RadioGroupItem value="segment" id="mode-segment" />
                            <Label htmlFor="mode-segment" class="flex items-center gap-1 cursor-pointer">
                                <Users class="w-4 h-4" />
                                Usar segmento existente
                            </Label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <RadioGroupItem value="manual" id="mode-manual" />
                            <Label htmlFor="mode-manual" class="flex items-center gap-1 cursor-pointer">
                                <Filter class="w-4 h-4" />
                                Filtros manuales
                            </Label>
                        </div>
                    </RadioGroup>

                    <!-- Modo Segmento -->
                    <div v-if="form.audience_mode === 'segment'">
                        <SegmentSelector
                            v-model="form.segment_id"
                            :segments="segmentos"
                        />
                        <div v-if="selectedSegment" class="mt-4 p-3 bg-muted rounded-md">
                            <div class="flex items-center gap-2 text-sm">
                                <Users class="w-4 h-4" />
                                <span class="font-medium">{{ selectedSegment.count }} usuarios</span>
                                <span class="text-muted-foreground">recibirán esta campaña</span>
                            </div>
                        </div>
                    </div>

                    <!-- Modo Manual -->
                    <div v-else class="space-y-4">
                        <Alert>
                            <Info class="h-4 w-4" />
                            <AlertDescription>
                                Define filtros para seleccionar usuarios específicos sin crear un segmento permanente.
                            </AlertDescription>
                        </Alert>

                        <AdvancedFilters
                            :config="filterConfig"
                            :hide-topbar="true"
                            @apply="handleFiltersApply"
                            @clear="handleFiltersClear"
                        />

                        <!-- Preview de conteo -->
                        <div v-if="manualFilterCount !== null" class="p-3 bg-muted rounded-md">
                            <div class="flex items-center gap-2 text-sm">
                                <Users class="w-4 h-4" />
                                <span class="font-medium">{{ manualFilterCount }} usuarios</span>
                                <span class="text-muted-foreground">recibirán esta campaña</span>
                            </div>
                        </div>
                        <div v-else-if="isCountingUsers" class="p-3 bg-muted rounded-md">
                            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary"></div>
                                Calculando usuarios...
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Selección de Plantillas -->
            <Card v-if="requiresEmail">
                <CardHeader>
                    <CardTitle>Plantilla de Email</CardTitle>
                    <CardDescription>
                        Selecciona la plantilla de email a utilizar
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Select v-model="form.plantilla_email_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Selecciona una plantilla" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem 
                                v-for="plantilla in plantillasEmail" 
                                :key="plantilla.id"
                                :value="plantilla.id.toString()"
                            >
                                <div>
                                    <div class="font-medium">{{ plantilla.nombre }}</div>
                                    <div v-if="plantilla.asunto" class="text-xs text-muted-foreground">
                                        Asunto: {{ plantilla.asunto }}
                                    </div>
                                </div>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </CardContent>
            </Card>

            <Card v-if="requiresWhatsApp">
                <CardHeader>
                    <CardTitle>Configuración de WhatsApp</CardTitle>
                    <CardDescription>
                        Define el modo y la plantilla de WhatsApp
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Modo de WhatsApp -->
                    <div class="space-y-2">
                        <Label>Tipo de Audiencia WhatsApp</Label>
                        <RadioGroup v-model="form.whatsapp_mode" class="flex flex-wrap gap-4">
                            <div class="flex items-center space-x-2">
                                <RadioGroupItem value="individual" id="whatsapp-mode-individual" />
                                <Label htmlFor="whatsapp-mode-individual" class="flex items-center gap-1 cursor-pointer">
                                    <Users class="w-4 h-4" />
                                    Contactos Individuales
                                </Label>
                            </div>
                            <div class="flex items-center space-x-2">
                                <RadioGroupItem value="grupos" id="whatsapp-mode-grupos" />
                                <Label htmlFor="whatsapp-mode-grupos" class="flex items-center gap-1 cursor-pointer">
                                    <MessageSquare class="w-4 h-4" />
                                    Grupos
                                </Label>
                            </div>
                            <div class="flex items-center space-x-2">
                                <RadioGroupItem value="mixto" id="whatsapp-mode-mixto" />
                                <Label htmlFor="whatsapp-mode-mixto" class="flex items-center gap-1 cursor-pointer">
                                    <Send class="w-4 h-4" />
                                    Contactos y Grupos
                                </Label>
                            </div>
                        </RadioGroup>
                    </div>

                    <!-- Selector de Grupos (si aplica) -->
                    <div v-if="requiresWhatsAppGroups" class="space-y-2">
                        <Label>Grupos de WhatsApp</Label>
                        <WhatsAppGroupSelector
                            v-model="form.whatsapp_group_ids"
                            :grupos="whatsappGrupos"
                        />
                    </div>

                    <!-- Alerta si modo grupos - no hay personalización -->
                    <Alert v-if="requiresWhatsAppGroups">
                        <Info class="h-4 w-4" />
                        <AlertDescription>
                            Los mensajes a grupos no soportan variables de personalización (ej: nombre del usuario).
                            El mensaje se enviará tal cual está definido en la plantilla.
                        </AlertDescription>
                    </Alert>

                    <!-- Plantilla de WhatsApp -->
                    <div class="space-y-2">
                        <Label>Plantilla de WhatsApp</Label>
                        <Select v-model="form.plantilla_whatsapp_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Selecciona una plantilla" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="plantilla in plantillasWhatsApp"
                                    :key="plantilla.id"
                                    :value="plantilla.id.toString()"
                                >
                                    <div>
                                        <div class="font-medium">{{ plantilla.nombre }}</div>
                                        <div v-if="plantilla.descripcion" class="text-xs text-muted-foreground">
                                            {{ plantilla.descripcion }}
                                        </div>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            <!-- Programación -->
            <Card>
                <CardHeader>
                    <CardTitle>Programación</CardTitle>
                    <CardDescription>
                        Define cuándo se enviará la campaña
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex items-center space-x-2">
                        <!-- TOGGLE CUSTOM QUE FUNCIONA -->
                        <button
                            type="button"
                            @click="toggleProgramacion(!form.es_programada)"
                            :class="[
                                'inline-flex h-[1.15rem] w-8 shrink-0 items-center rounded-full border border-transparent shadow-xs transition-all outline-none focus-visible:ring-[3px] cursor-pointer',
                                form.es_programada 
                                    ? 'bg-primary' 
                                    : 'bg-input'
                            ]"
                            :aria-checked="form.es_programada"
                            role="switch"
                        >
                            <span
                                :class="[
                                    'pointer-events-none block size-4 rounded-full bg-background ring-0 transition-transform',
                                    form.es_programada 
                                        ? 'translate-x-[calc(100%-2px)]' 
                                        : 'translate-x-0'
                                ]"
                            ></span>
                        </button>
                        <Label @click="toggleProgramacion(!form.es_programada)" class="cursor-pointer">Programar envío</Label>
                    </div>
                    <div v-if="form.es_programada" class="space-y-2">
                        <Label htmlFor="fecha_programada">
                            Fecha y hora de envío <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="fecha_programada"
                            type="datetime-local"
                            v-model="form.fecha_programada"
                            :min="new Date().toISOString().slice(0, 16)"
                            class="w-full"
                            required
                        />
                        <p class="text-xs text-muted-foreground">
                            La campaña se enviará automáticamente en la fecha y hora seleccionada
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Configuración Avanzada -->
            <Card>
                <CardHeader>
                    <CardTitle>Configuración Avanzada</CardTitle>
                    <CardDescription>
                        Ajusta los parámetros de envío y tracking
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Configuración de Email -->
                    <div v-if="requiresEmail">
                        <Label htmlFor="batch_size_email">
                            Tamaño de lote (Email)
                        </Label>
                        <Input
                            id="batch_size_email"
                            type="number"
                            v-model.number="form.configuracion.batch_size_email"
                            min="1"
                            max="100"
                            class="max-w-xs"
                        />
                        <p class="text-xs text-muted-foreground mt-1">
                            Emails enviados por lote (máximo 100, recomendado: 50)
                        </p>
                    </div>

                    <!-- Configuración de WhatsApp - Intervalos -->
                    <div v-if="requiresWhatsApp" class="space-y-4">
                        <Alert>
                            <Info class="h-4 w-4" />
                            <AlertDescription>
                                Los mensajes de WhatsApp se envían uno a uno con intervalos de tiempo
                                aleatorios para evitar bloqueos de la API.
                            </AlertDescription>
                        </Alert>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="whatsapp_delay_min">
                                    Intervalo mínimo (segundos)
                                </Label>
                                <Input
                                    id="whatsapp_delay_min"
                                    type="number"
                                    v-model.number="form.configuracion.whatsapp_delay_min"
                                    min="5"
                                    max="120"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    Mínimo 5 segundos entre mensajes
                                </p>
                            </div>
                            <div>
                                <Label htmlFor="whatsapp_delay_max">
                                    Intervalo máximo (segundos)
                                </Label>
                                <Input
                                    id="whatsapp_delay_max"
                                    type="number"
                                    v-model.number="form.configuracion.whatsapp_delay_max"
                                    min="5"
                                    max="120"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    Máximo 120 segundos entre mensajes
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="requiresEmail" class="space-y-2">
                        <Label>Opciones de Tracking</Label>
                        <div class="space-y-2">
                            <div class="flex items-center space-x-2">
                                <button
                                    type="button"
                                    @click="togglePixelTracking(!form.configuracion.enable_pixel_tracking)"
                                    :class="[
                                        'inline-flex h-[1.15rem] w-8 shrink-0 items-center rounded-full border border-transparent shadow-xs transition-all outline-none focus-visible:ring-[3px] cursor-pointer',
                                        form.configuracion.enable_pixel_tracking 
                                            ? 'bg-primary' 
                                            : 'bg-input'
                                    ]"
                                    :aria-checked="form.configuracion.enable_pixel_tracking"
                                    role="switch"
                                >
                                    <span
                                        :class="[
                                            'pointer-events-none block size-4 rounded-full bg-background ring-0 transition-transform',
                                            form.configuracion.enable_pixel_tracking 
                                                ? 'translate-x-[calc(100%-2px)]' 
                                                : 'translate-x-0'
                                        ]"
                                    ></span>
                                </button>
                                <Label class="font-normal cursor-pointer" @click="togglePixelTracking(!form.configuracion.enable_pixel_tracking)">
                                    Tracking de apertura (pixel)
                                </Label>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button
                                    type="button"
                                    @click="toggleClickTracking(!form.configuracion.enable_click_tracking)"
                                    :class="[
                                        'inline-flex h-[1.15rem] w-8 shrink-0 items-center rounded-full border border-transparent shadow-xs transition-all outline-none focus-visible:ring-[3px] cursor-pointer',
                                        form.configuracion.enable_click_tracking 
                                            ? 'bg-primary' 
                                            : 'bg-input'
                                    ]"
                                    :aria-checked="form.configuracion.enable_click_tracking"
                                    role="switch"
                                >
                                    <span
                                        :class="[
                                            'pointer-events-none block size-4 rounded-full bg-background ring-0 transition-transform',
                                            form.configuracion.enable_click_tracking 
                                                ? 'translate-x-[calc(100%-2px)]' 
                                                : 'translate-x-0'
                                        ]"
                                    ></span>
                                </button>
                                <Label class="font-normal cursor-pointer" @click="toggleClickTracking(!form.configuracion.enable_click_tracking)">
                                    Tracking de clicks
                                </Label>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Alert>
                <Info class="h-4 w-4" />
                <AlertDescription>
                    La campaña se guardará en estado <strong>borrador</strong>. 
                    Podrás revisarla y enviarla desde el listado de campañas.
                </AlertDescription>
            </Alert>

            </form>
        </div>
    </AdminLayout>
</template>