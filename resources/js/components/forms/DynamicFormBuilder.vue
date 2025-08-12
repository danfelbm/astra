<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Plus, Trash2, Eye, X, GitBranch } from 'lucide-vue-next';
import { watch, onMounted, ref, computed } from 'vue';
import { useFormBuilder } from '@/composables/useFormBuilder';
import GeographicRestrictions from '@/components/forms/GeographicRestrictions.vue';
import RepeaterBuilder from './RepeaterBuilder.vue';
import ConditionalFieldConfig from './ConditionalFieldConfig.vue';
import type { FormField, GeographicRestrictions as GeographicRestrictionsType } from '@/types/forms';
import { FIELD_TYPES } from '@/types/forms';

interface Props {
    modelValue: FormField[];
    disabled?: boolean;
    title?: string;
    description?: string;
    showEditableOption?: boolean; // Mostrar opción "Campo editable" (solo para candidaturas)
    // Props para perfil_candidatura en votaciones (deprecated)
    showPerfilCandidaturaConfig?: boolean; // Mostrar configuración avanzada para perfil_candidatura
    cargos?: Array<{ id: number; nombre: string; ruta_jerarquica?: string }>;
    periodosElectorales?: Array<{ id: number; nombre: string; fecha_inicio: string; fecha_fin: string }>;
    // Props para campo convocatoria en votaciones
    showConvocatoriaConfig?: boolean; // Mostrar configuración para campo convocatoria
    convocatorias?: Array<{ 
        id: number; 
        nombre: string; 
        cargo?: { nombre: string; ruta_jerarquica?: string };
        periodo_electoral?: { nombre: string };
        estado_temporal?: string;
    }>;
    context?: 'convocatoria' | 'votacion' | 'candidatura'; // Contexto del formulario
}

interface Emits {
    (e: 'update:modelValue', value: FormField[]): void;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    title: 'Constructor de Formulario',
    description: 'Agrega los campos que aparecerán en el formulario',
    showEditableOption: false,
    showPerfilCandidaturaConfig: false,
    cargos: () => [],
    periodosElectorales: () => [],
    showConvocatoriaConfig: false,
    convocatorias: () => [],
    context: 'convocatoria',
});

const emit = defineEmits<Emits>();

const {
    fields,
    newField,
    showFieldForm,
    editingFieldIndex,
    addField,
    editField,
    removeField,
    resetFieldForm,
    addOption,
    removeOption,
    setFields,
    canSave,
} = useFormBuilder(props.modelValue);

// Watch para inicializar configuraciones cuando se cambia el tipo de campo
watch(() => newField.type, (newType) => {
    // Siempre asegurar que conditionalConfig existe
    if (!newField.conditionalConfig) {
        newField.conditionalConfig = {
            enabled: false,
            showWhen: 'all',
            conditions: [],
        };
    }
    
    if (newType === 'perfil_candidatura' && !newField.perfilCandidaturaConfig) {
        newField.perfilCandidaturaConfig = {
            cargo_id: undefined,
            periodo_electoral_id: undefined,
            territorio_id: undefined,
            departamento_id: undefined,
            municipio_id: undefined,
            localidad_id: undefined,
            territorios_ids: [],
            departamentos_ids: [],
            municipios_ids: [],
            localidades_ids: [],
            multiple: false,
            mostrarVotoBlanco: true,
        };
    }
    if (newType === 'convocatoria' && !newField.convocatoriaConfig) {
        newField.convocatoriaConfig = {
            convocatoria_id: undefined,
            multiple: false,
            mostrarVotoBlanco: props.context === 'votacion', // Solo en votaciones
            filtrarPorUbicacion: props.context === 'candidatura', // Solo en candidaturas
        };
    }
    if (newType === 'file' && !newField.fileConfig) {
        newField.fileConfig = {
            multiple: false,
            maxFiles: 5,
            maxFileSize: 10,
            accept: '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif',
        };
    }
    if (newType === 'datepicker' && !newField.datepickerConfig) {
        newField.datepickerConfig = {
            minDate: undefined,
            maxDate: undefined,
            format: 'DD/MM/YYYY',
            allowPastDates: true,
            allowFutureDates: true,
        };
    }
    if (newType === 'disclaimer' && !newField.disclaimerConfig) {
        newField.disclaimerConfig = {
            disclaimerText: '',
            modalTitle: 'Términos y Condiciones',
            acceptButtonText: 'Acepto',
            declineButtonText: 'No acepto',
        };
    }
    if (newType === 'repeater' && !newField.repeaterConfig) {
        newField.repeaterConfig = {
            minItems: 0,
            maxItems: 10,
            itemName: 'Elemento',
            addButtonText: 'Agregar elemento',
            removeButtonText: 'Eliminar',
            fields: [],
        };
    }
});

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
    if (JSON.stringify(newValue) !== JSON.stringify(fields.value)) {
        setFields(newValue);
    }
}, { deep: true });

// Emit changes to parent
watch(fields, (newFields) => {
    emit('update:modelValue', newFields);
}, { deep: true });

// Handle field addition with validation
const handleAddField = () => {
    if (!newField.title.trim()) return;
    addField();
};

// Filtrar tipos de campo disponibles según el contexto
const availableFieldTypes = computed(() => {
    if (props.context === 'votacion') {
        // En votaciones, no mostrar perfil_candidatura, mostrar convocatoria
        return FIELD_TYPES.filter(type => type.value !== 'perfil_candidatura');
    } else if (props.context === 'candidatura') {
        // En candidaturas, mostrar convocatoria pero no perfil_candidatura
        return FIELD_TYPES.filter(type => type.value !== 'perfil_candidatura');
    } else {
        // En convocatorias, mostrar perfil_candidatura pero no convocatoria
        return FIELD_TYPES.filter(type => type.value !== 'convocatoria');
    }
});

onMounted(() => {
    setFields(props.modelValue);
    
    // Asegurar que conditionalConfig existe al montar
    if (!newField.conditionalConfig) {
        newField.conditionalConfig = {
            enabled: false,
            showWhen: 'all',
            conditions: [],
        };
    }
});
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">{{ title }}</h3>
                <p class="text-sm text-muted-foreground">
                    {{ description }}
                </p>
            </div>
            <Button 
                type="button"
                @click="showFieldForm = true" 
                :disabled="disabled"
            >
                <Plus class="mr-2 h-4 w-4" />
                Agregar Campo
            </Button>
        </div>

        <!-- Lista de campos existentes -->
        <div v-if="fields.length > 0" class="space-y-4">
            <div
                v-for="(field, index) in fields"
                :key="field.id"
                class="border rounded-lg p-4 flex items-center justify-between"
            >
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <Badge variant="outline">{{ availableFieldTypes.find(t => t.value === field.type)?.label || field.type }}</Badge>
                        <span v-if="field.required" class="text-red-500">*</span>
                        <Badge v-if="showEditableOption && field.editable" variant="secondary" class="text-xs bg-green-100 text-green-800">
                            Editable
                        </Badge>
                        <Badge v-if="field.conditionalConfig?.enabled" variant="secondary" class="text-xs bg-orange-100 text-orange-800">
                            <GitBranch class="h-3 w-3 mr-1" />
                            Condicional
                        </Badge>
                        <Badge v-if="field.type === 'convocatoria' && field.convocatoriaConfig?.convocatoria_id" variant="secondary" class="text-xs">
                            Conv. ID: {{ field.convocatoriaConfig.convocatoria_id }}
                        </Badge>
                    </div>
                    <h4 class="font-medium">{{ field.title }}</h4>
                    <p v-if="field.description" class="text-sm text-muted-foreground">
                        {{ field.description }}
                    </p>
                    <div v-if="field.options && field.options.length > 0" class="mt-2">
                        <p class="text-xs text-muted-foreground">Opciones:</p>
                        <div class="flex flex-wrap gap-1 mt-1">
                            <Badge v-for="option in field.options" :key="option" variant="secondary" class="text-xs">
                                {{ option }}
                            </Badge>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button 
                        type="button"
                        variant="ghost" 
                        size="sm" 
                        @click="editField(index)"
                        :disabled="disabled"
                    >
                        <Eye class="h-4 w-4" />
                    </Button>
                    <Button 
                        type="button"
                        variant="ghost" 
                        size="sm" 
                        @click="removeField(index)"
                        :disabled="disabled"
                    >
                        <Trash2 class="h-4 w-4 text-destructive" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Mensaje cuando no hay campos -->
        <div v-else class="text-center py-8 text-muted-foreground border-2 border-dashed rounded-lg">
            <p>No hay campos configurados</p>
            <p class="text-sm">Agrega al menos un campo para continuar</p>
        </div>

        <!-- Formulario para agregar/editar campo -->
        <Card v-if="showFieldForm">
            <CardHeader>
                <CardTitle class="flex items-center justify-between">
                    {{ editingFieldIndex !== null ? 'Editar Campo' : 'Nuevo Campo' }}
                    <Button type="button" variant="ghost" size="sm" @click="resetFieldForm">
                        <X class="h-4 w-4" />
                    </Button>
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <Label>Tipo de Campo</Label>
                        <Select v-model="newField.type">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="type in availableFieldTypes"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-3 mt-6">
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="field_required"
                                :checked="newField.required"
                                @update:checked="(checked) => newField.required = checked"
                            />
                            <Label>Campo obligatorio</Label>
                        </div>
                        
                        <!-- Opción solo para candidaturas -->
                        <div v-if="showEditableOption" class="flex items-center space-x-2">
                            <Checkbox
                                id="field_editable"
                                :checked="newField.editable"
                                @update:checked="(checked) => newField.editable = checked"
                            />
                            <Label class="text-sm">
                                Campo editable en candidaturas aprobadas
                                <span class="text-muted-foreground block text-xs">
                                    Los usuarios podrán editar este campo incluso con candidatura aprobada
                                </span>
                            </Label>
                        </div>
                    </div>
                </div>

                <div>
                    <Label>Título del Campo *</Label>
                    <Input
                        id="field_title"
                        v-model="newField.title"
                        placeholder="Ej: ¿Cuál es tu candidato preferido?"
                    />
                </div>

                <div>
                    <Label>Descripción (opcional)</Label>
                    <Textarea
                        id="field_description"
                        v-model="newField.description"
                        placeholder="Descripción adicional del campo"
                        rows="2"
                    />
                </div>

                <!-- Opciones para select, radio, checkbox -->
                <div v-if="['select', 'radio', 'checkbox'].includes(newField.type)">
                    <div class="flex items-center justify-between">
                        <Label>Opciones</Label>
                        <Button type="button" variant="outline" size="sm" @click="addOption">
                            <Plus class="mr-2 h-3 w-3" />
                            Agregar Opción
                        </Button>
                    </div>
                    <div class="space-y-2 mt-2">
                        <div
                            v-for="(option, index) in newField.options"
                            :key="index"
                            class="flex gap-2"
                        >
                            <Input
                                v-model="newField.options![index]"
                                placeholder="Texto de la opción"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="removeOption(index)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Configuración especial para perfil_candidatura (SOLO para convocatorias, NO para votaciones) -->
                <div v-if="newField.type === 'perfil_candidatura' && context !== 'votacion' && showPerfilCandidaturaConfig && newField.perfilCandidaturaConfig" class="space-y-4">
                    <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <h4 class="font-medium text-blue-900 mb-3">Configuración de Filtros para Candidatos</h4>
                        <p class="text-sm text-blue-700 mb-4">
                            Define los criterios para filtrar qué usuarios con postulaciones aprobadas aparecerán como opciones de voto.
                        </p>
                        
                        <!-- Selector de Cargo -->
                        <div v-if="props.cargos && props.cargos.length > 0" class="mb-4">
                            <Label>Cargo (opcional)</Label>
                            <Select v-model="newField.perfilCandidaturaConfig.cargo_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los cargos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los cargos</SelectItem>
                                    <SelectItem
                                        v-for="cargo in props.cargos"
                                        :key="cargo.id"
                                        :value="cargo.id.toString()"
                                    >
                                        {{ cargo.ruta_jerarquica || cargo.nombre }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-muted-foreground mt-1">
                                Filtrar candidatos postulados a este cargo específico
                            </p>
                        </div>

                        <!-- Selector de Período Electoral -->
                        <div v-if="props.periodosElectorales && props.periodosElectorales.length > 0" class="mb-4">
                            <Label>Período Electoral (opcional)</Label>
                            <Select v-model="newField.perfilCandidaturaConfig.periodo_electoral_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los períodos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los períodos</SelectItem>
                                    <SelectItem
                                        v-for="periodo in props.periodosElectorales"
                                        :key="periodo.id"
                                        :value="periodo.id.toString()"
                                    >
                                        {{ periodo.nombre }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-muted-foreground mt-1">
                                Filtrar candidatos del período electoral seleccionado
                            </p>
                        </div>

                        <!-- Restricciones Geográficas -->
                        <div class="mb-4">
                            <Label class="mb-2">Restricciones Geográficas (opcional)</Label>
                            <GeographicRestrictions
                                v-model="newField.perfilCandidaturaConfig"
                            />
                            <p class="text-xs text-muted-foreground mt-1">
                                Filtrar candidatos por ubicación geográfica
                            </p>
                        </div>

                        <!-- Opción de selección múltiple -->
                        <div class="flex items-center space-x-2 mb-3">
                            <Checkbox
                                id="perfil_multiple"
                                :checked="newField.perfilCandidaturaConfig.multiple"
                                @update:checked="(checked) => newField.perfilCandidaturaConfig.multiple = checked"
                            />
                            <Label class="text-sm">
                                Permitir selección múltiple
                                <span class="text-muted-foreground block text-xs">
                                    Los votantes podrán seleccionar varios candidatos
                                </span>
                            </Label>
                        </div>

                        <!-- Opción de voto en blanco -->
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="perfil_voto_blanco"
                                :checked="newField.perfilCandidaturaConfig.mostrarVotoBlanco"
                                @update:checked="(checked) => newField.perfilCandidaturaConfig.mostrarVotoBlanco = checked"
                            />
                            <Label class="text-sm">
                                ¿Deseas mostrar Voto en Blanco?
                                <span class="text-muted-foreground block text-xs">
                                    Agrega la opción "Voto en blanco" para los votantes
                                </span>
                            </Label>
                        </div>
                    </div>
                </div>

                <!-- Configuración especial para campo archivo -->
                <div v-if="newField.type === 'file' && newField.fileConfig" class="space-y-4">
                    <div class="p-4 bg-purple-50 rounded-lg border border-purple-200">
                        <h4 class="font-medium text-purple-900 mb-3">Configuración de Carga de Archivos</h4>
                        <p class="text-sm text-purple-700 mb-4">
                            Define las opciones para la carga de archivos.
                        </p>
                        
                        <!-- Permitir múltiples archivos -->
                        <div class="space-y-4">
                            <div class="flex items-center space-x-2">
                                <Checkbox
                                    id="file_multiple"
                                    :checked="newField.fileConfig.multiple"
                                    @update:checked="(checked) => newField.fileConfig.multiple = checked"
                                />
                                <Label class="text-sm">
                                    Permitir múltiples archivos
                                    <span class="text-muted-foreground block text-xs">
                                        Los usuarios podrán subir más de un archivo
                                    </span>
                                </Label>
                            </div>

                            <!-- Número máximo de archivos (solo si permite múltiples) -->
                            <div v-if="newField.fileConfig.multiple">
                                <Label>Número máximo de archivos</Label>
                                <Input
                                    v-model.number="newField.fileConfig.maxFiles"
                                    type="number"
                                    min="1"
                                    max="20"
                                    placeholder="5"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    Cantidad máxima de archivos que se pueden subir (1-20)
                                </p>
                            </div>

                            <!-- Tamaño máximo por archivo -->
                            <div>
                                <Label>Tamaño máximo por archivo (MB)</Label>
                                <Input
                                    v-model.number="newField.fileConfig.maxFileSize"
                                    type="number"
                                    min="1"
                                    max="100"
                                    placeholder="10"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    Tamaño máximo en megabytes (1-100 MB)
                                </p>
                            </div>

                            <!-- Tipos de archivo aceptados -->
                            <div>
                                <Label>Tipos de archivo permitidos</Label>
                                <Input
                                    v-model="newField.fileConfig.accept"
                                    placeholder=".pdf,.doc,.docx,.jpg,.png"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    Extensiones separadas por comas (ej: .pdf,.doc,.jpg)
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuración especial para campo datepicker -->
                <div v-if="newField.type === 'datepicker' && newField.datepickerConfig" class="space-y-4">
                    <div class="p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                        <h4 class="font-medium text-indigo-900 mb-3">Configuración de Selector de Fecha</h4>
                        <p class="text-sm text-indigo-700 mb-4">
                            Define las opciones y restricciones para el selector de fecha.
                        </p>
                        
                        <div class="space-y-4">
                            <!-- Fecha mínima -->
                            <div>
                                <Label>Fecha mínima (opcional)</Label>
                                <Input
                                    type="date"
                                    v-model="newField.datepickerConfig.minDate"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    Fecha más antigua que se puede seleccionar
                                </p>
                            </div>
                            
                            <!-- Fecha máxima -->
                            <div>
                                <Label>Fecha máxima (opcional)</Label>
                                <Input
                                    type="date"
                                    v-model="newField.datepickerConfig.maxDate"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    Fecha más reciente que se puede seleccionar
                                </p>
                            </div>
                            
                            <!-- Permitir fechas pasadas -->
                            <div class="flex items-center space-x-2">
                                <Checkbox
                                    id="datepicker_past"
                                    :checked="newField.datepickerConfig.allowPastDates"
                                    @update:checked="(checked) => newField.datepickerConfig.allowPastDates = checked"
                                />
                                <Label class="text-sm">
                                    Permitir fechas pasadas
                                    <span class="text-muted-foreground block text-xs">
                                        Los usuarios podrán seleccionar fechas anteriores a hoy
                                    </span>
                                </Label>
                            </div>
                            
                            <!-- Permitir fechas futuras -->
                            <div class="flex items-center space-x-2">
                                <Checkbox
                                    id="datepicker_future"
                                    :checked="newField.datepickerConfig.allowFutureDates"
                                    @update:checked="(checked) => newField.datepickerConfig.allowFutureDates = checked"
                                />
                                <Label class="text-sm">
                                    Permitir fechas futuras
                                    <span class="text-muted-foreground block text-xs">
                                        Los usuarios podrán seleccionar fechas posteriores a hoy
                                    </span>
                                </Label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuración especial para campo disclaimer -->
                <div v-if="newField.type === 'disclaimer' && newField.disclaimerConfig" class="space-y-4">
                    <div class="p-4 bg-amber-50 rounded-lg border border-amber-200">
                        <h4 class="font-medium text-amber-900 mb-3">Configuración del Disclaimer</h4>
                        <p class="text-sm text-amber-700 mb-4">
                            Define el texto legal que el usuario debe aceptar.
                        </p>
                        
                        <div class="space-y-4">
                            <!-- Texto del disclaimer -->
                            <div>
                                <Label>Texto del disclaimer *</Label>
                                <Textarea
                                    v-model="newField.disclaimerConfig.disclaimerText"
                                    placeholder="Ingrese aquí el texto de términos y condiciones que el usuario debe aceptar..."
                                    rows="6"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    Este texto se mostrará en un modal cuando el usuario intente aceptar
                                </p>
                            </div>
                            
                            <!-- Título del modal -->
                            <div>
                                <Label>Título del modal</Label>
                                <Input
                                    v-model="newField.disclaimerConfig.modalTitle"
                                    placeholder="Términos y Condiciones"
                                />
                            </div>
                            
                            <!-- Texto del botón aceptar -->
                            <div>
                                <Label>Texto del botón aceptar</Label>
                                <Input
                                    v-model="newField.disclaimerConfig.acceptButtonText"
                                    placeholder="Acepto"
                                />
                            </div>
                            
                            <!-- Texto del botón rechazar -->
                            <div>
                                <Label>Texto del botón rechazar</Label>
                                <Input
                                    v-model="newField.disclaimerConfig.declineButtonText"
                                    placeholder="No acepto"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuración especial para campo repetidor -->
                <div v-if="newField.type === 'repeater' && newField.repeaterConfig" class="space-y-4">
                    <div class="p-4 bg-cyan-50 rounded-lg border border-cyan-200">
                        <h4 class="font-medium text-cyan-900 mb-3">Configuración del Repetidor</h4>
                        <p class="text-sm text-cyan-700 mb-4">
                            Define los subcampos y límites del repetidor. Los usuarios podrán agregar múltiples instancias de estos campos.
                        </p>
                        
                        <div class="space-y-4">
                            <!-- Número mínimo de elementos -->
                            <div>
                                <Label>Número mínimo de elementos</Label>
                                <Input
                                    type="number"
                                    v-model.number="newField.repeaterConfig.minItems"
                                    min="0"
                                    max="50"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    Cantidad mínima de elementos que debe tener el usuario
                                </p>
                            </div>
                            
                            <!-- Número máximo de elementos -->
                            <div>
                                <Label>Número máximo de elementos</Label>
                                <Input
                                    type="number"
                                    v-model.number="newField.repeaterConfig.maxItems"
                                    min="1"
                                    max="50"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    Cantidad máxima de elementos que puede agregar el usuario
                                </p>
                            </div>
                            
                            <!-- Nombre del elemento -->
                            <div>
                                <Label>Nombre del elemento</Label>
                                <Input
                                    v-model="newField.repeaterConfig.itemName"
                                    placeholder="Elemento"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    Cómo se llamará cada instancia (ej: "Referencia", "Experiencia", "Documento")
                                </p>
                            </div>
                            
                            <!-- Texto del botón agregar -->
                            <div>
                                <Label>Texto del botón agregar</Label>
                                <Input
                                    v-model="newField.repeaterConfig.addButtonText"
                                    placeholder="Agregar elemento"
                                />
                            </div>
                            
                            <!-- Subcampos del repetidor -->
                            <div>
                                <Label class="text-sm font-medium mb-2">Configuración de subcampos</Label>
                                <p class="text-xs text-muted-foreground mb-3">
                                    Define los campos que se repetirán dentro del repetidor.
                                </p>
                                <RepeaterBuilder
                                    v-model="newField.repeaterConfig.fields"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuración especial para convocatoria en votaciones -->
                <div v-if="newField.type === 'convocatoria' && showConvocatoriaConfig && newField.convocatoriaConfig" class="space-y-4">
                    <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                        <h4 class="font-medium text-green-900 mb-3">Selección de Convocatoria para Votación</h4>
                        <p class="text-sm text-green-700 mb-4">
                            Selecciona UNA convocatoria específica. Los usuarios con postulaciones APROBADAS a esta convocatoria aparecerán como opciones de voto.
                        </p>
                        
                        <!-- Selector de Convocatoria -->
                        <div v-if="props.convocatorias && props.convocatorias.length > 0" class="mb-4">
                            <Label>Convocatoria *</Label>
                            <Select v-model="newField.convocatoriaConfig.convocatoria_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecciona una convocatoria" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="conv in props.convocatorias"
                                        :key="conv.id"
                                        :value="conv.id.toString()"
                                    >
                                        <div class="flex flex-col">
                                            <span>{{ conv.nombre }}</span>
                                            <span v-if="conv.cargo" class="text-xs text-muted-foreground">
                                                {{ conv.cargo.ruta_jerarquica || conv.cargo.nombre }}
                                                <span v-if="conv.periodo_electoral"> - {{ conv.periodo_electoral.nombre }}</span>
                                            </span>
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-muted-foreground mt-1">
                                Se mostrarán los candidatos con postulaciones aprobadas a esta convocatoria
                            </p>
                        </div>
                        
                        <!-- Mensaje cuando no hay convocatorias -->
                        <div v-else class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-md">
                            <p class="text-sm text-amber-800">
                                No hay convocatorias con postulaciones aprobadas disponibles. Asegúrate de que existan convocatorias con al menos una postulación aprobada.
                            </p>
                        </div>

                        <!-- Opción de selección múltiple -->
                        <div class="flex items-center space-x-2 mb-3">
                            <Checkbox
                                id="conv_multiple"
                                :checked="newField.convocatoriaConfig.multiple"
                                @update:checked="(checked) => newField.convocatoriaConfig.multiple = checked"
                            />
                            <Label class="text-sm">
                                Permitir selección múltiple
                                <span class="text-muted-foreground block text-xs">
                                    Los votantes podrán seleccionar varios candidatos
                                </span>
                            </Label>
                        </div>

                        <!-- Opción de voto en blanco -->
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="conv_voto_blanco"
                                :checked="newField.convocatoriaConfig.mostrarVotoBlanco"
                                @update:checked="(checked) => newField.convocatoriaConfig.mostrarVotoBlanco = checked"
                            />
                            <Label class="text-sm">
                                ¿Deseas mostrar Voto en Blanco?
                                <span class="text-muted-foreground block text-xs">
                                    Agrega la opción "Voto en blanco" para los votantes
                                </span>
                            </Label>
                        </div>
                    </div>
                </div>

                <!-- Configuración condicional (para todos los campos excepto el repetidor y campos especiales) -->
                <div v-if="!['repeater', 'perfil_candidatura', 'convocatoria'].includes(newField.type)">
                    <ConditionalFieldConfig
                        v-model="newField.conditionalConfig"
                        :fields="fields"
                        :current-field-id="newField.id || 'new_field'"
                    />
                </div>

                <div class="flex justify-end gap-2">
                    <Button type="button" variant="outline" @click="resetFieldForm">
                        Cancelar
                    </Button>
                    <Button type="button" @click="handleAddField" :disabled="!newField.title.trim()">
                        {{ editingFieldIndex !== null ? 'Actualizar' : 'Agregar' }} Campo
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>