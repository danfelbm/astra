<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ref, onMounted, nextTick, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { Territorio, Departamento, Municipio, Localidad } from '@/types/forms';

// Extended interfaces for both single and multiple selection
interface GeographicSelectionsMultiple {
    territorios_ids: number[];
    departamentos_ids: number[];
    municipios_ids: number[];
    localidades_ids: number[];
}

interface GeographicSelectionsSingle {
    territorio_id?: number;
    departamento_id?: number;
    municipio_id?: number;
    localidad_id?: number;
}

type GeographicSelections = GeographicSelectionsMultiple | GeographicSelectionsSingle;

interface Props {
    modelValue: GeographicSelections;
    disabled?: boolean;
    title?: string;
    description?: string;
    mode?: 'single' | 'multiple'; // Nuevo prop para controlar el modo
    showCard?: boolean; // Si mostrar con Card o sin ella
}

interface Emits {
    (e: 'update:modelValue', value: GeographicSelections): void;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    title: 'Ubicación Geográfica',
    description: 'Selecciona la ubicación',
    mode: 'multiple', // Por defecto mantiene compatibilidad con el comportamiento actual
    showCard: true,
    modelValue: () => ({
        territorios_ids: [],
        departamentos_ids: [],
        municipios_ids: [],
        localidades_ids: [],
    }),
});

const emit = defineEmits<Emits>();

// Geographic data
const territorios = ref<Territorio[]>([]);
const departamentos = ref<Departamento[]>([]);
const municipios = ref<Municipio[]>([]);
const localidades = ref<Localidad[]>([]);
const loading = ref(false);

// Computed helpers para manejar ambos modos
const isMultipleMode = computed(() => props.mode === 'multiple');

const selectedTerritorios = computed(() => {
    if (isMultipleMode.value) {
        return (props.modelValue as GeographicSelectionsMultiple)?.territorios_ids || [];
    } else {
        const id = (props.modelValue as GeographicSelectionsSingle)?.territorio_id;
        return id ? [id] : [];
    }
});

const selectedDepartamentos = computed(() => {
    if (isMultipleMode.value) {
        return (props.modelValue as GeographicSelectionsMultiple)?.departamentos_ids || [];
    } else {
        const id = (props.modelValue as GeographicSelectionsSingle)?.departamento_id;
        return id ? [id] : [];
    }
});

const selectedMunicipios = computed(() => {
    if (isMultipleMode.value) {
        return (props.modelValue as GeographicSelectionsMultiple)?.municipios_ids || [];
    } else {
        const id = (props.modelValue as GeographicSelectionsSingle)?.municipio_id;
        return id ? [id] : [];
    }
});

const selectedLocalidades = computed(() => {
    if (isMultipleMode.value) {
        return (props.modelValue as GeographicSelectionsMultiple)?.localidades_ids || [];
    } else {
        const id = (props.modelValue as GeographicSelectionsSingle)?.localidad_id;
        return id ? [id] : [];
    }
});

// Obtener información del usuario para determinar las rutas a usar
const page = usePage();
const isAdmin = computed(() => page.props.auth?.isAdmin || false);

// Determinar el prefijo de la ruta basado en si es admin o no
const apiPrefix = computed(() => {
    // Si estamos en una ruta admin (/admin/*), usar las rutas admin
    // De lo contrario, usar las rutas públicas de la API
    const currentPath = window.location.pathname;
    return currentPath.startsWith('/admin') ? '/admin/geographic' : '/api/geographic';
});

// Load functions
const loadTerritorios = async () => {
    try {
        loading.value = true;
        const response = await fetch(`${apiPrefix.value}/territorios`);
        territorios.value = await response.json();
    } catch (error) {
        console.error('Error loading territorios:', error);
    } finally {
        loading.value = false;
    }
};

const loadDepartamentos = async (territorioIds: number[]) => {
    if (!territorioIds.length) {
        departamentos.value = [];
        return;
    }
    try {
        loading.value = true;
        const response = await fetch(`${apiPrefix.value}/departamentos?territorio_ids=${territorioIds.join(',')}`);
        departamentos.value = await response.json();
    } catch (error) {
        console.error('Error loading departamentos:', error);
    } finally {
        loading.value = false;
    }
};

const loadMunicipios = async (departamentoIds: number[]) => {
    if (!departamentoIds.length) {
        municipios.value = [];
        return;
    }
    try {
        loading.value = true;
        const response = await fetch(`${apiPrefix.value}/municipios?departamento_ids=${departamentoIds.join(',')}`);
        municipios.value = await response.json();
    } catch (error) {
        console.error('Error loading municipios:', error);
    } finally {
        loading.value = false;
    }
};

const loadLocalidades = async (municipioIds: number[]) => {
    if (!municipioIds.length) {
        localidades.value = [];
        return;
    }
    try {
        loading.value = true;
        const response = await fetch(`${apiPrefix.value}/localidades?municipio_ids=${municipioIds.join(',')}`);
        localidades.value = await response.json();
    } catch (error) {
        console.error('Error loading localidades:', error);
    } finally {
        loading.value = false;
    }
};

// Helper function para manejar actualizaciones según el modo
const handleUpdate = (value: any, field: 'territorio' | 'departamento' | 'municipio' | 'localidad') => {
    let newSelections: GeographicSelections;
    
    if (isMultipleMode.value) {
        // Modo múltiple: mantener arrays
        const arrayValue = Array.isArray(value) 
            ? value.map(Number).filter(Boolean) 
            : (value ? [Number(value)].filter(Boolean) : []);
        
        newSelections = { 
            territorios_ids: (props.modelValue as GeographicSelectionsMultiple)?.territorios_ids || [],
            departamentos_ids: (props.modelValue as GeographicSelectionsMultiple)?.departamentos_ids || [],
            municipios_ids: (props.modelValue as GeographicSelectionsMultiple)?.municipios_ids || [],
            localidades_ids: (props.modelValue as GeographicSelectionsMultiple)?.localidades_ids || [],
        } as GeographicSelectionsMultiple;
        
        // Actualizar el campo correspondiente
        if (field === 'territorio') {
            (newSelections as GeographicSelectionsMultiple).territorios_ids = arrayValue;
            (newSelections as GeographicSelectionsMultiple).departamentos_ids = [];
            (newSelections as GeographicSelectionsMultiple).municipios_ids = [];
            (newSelections as GeographicSelectionsMultiple).localidades_ids = [];
            nextTick(() => loadDepartamentos(arrayValue));
        } else if (field === 'departamento') {
            (newSelections as GeographicSelectionsMultiple).departamentos_ids = arrayValue;
            (newSelections as GeographicSelectionsMultiple).municipios_ids = [];
            (newSelections as GeographicSelectionsMultiple).localidades_ids = [];
            nextTick(() => loadMunicipios(arrayValue));
        } else if (field === 'municipio') {
            (newSelections as GeographicSelectionsMultiple).municipios_ids = arrayValue;
            (newSelections as GeographicSelectionsMultiple).localidades_ids = [];
            nextTick(() => loadLocalidades(arrayValue));
        } else if (field === 'localidad') {
            (newSelections as GeographicSelectionsMultiple).localidades_ids = arrayValue;
        }
    } else {
        // Modo single: valores únicos
        const singleValue = value ? Number(value) : undefined;
        
        newSelections = {
            territorio_id: (props.modelValue as GeographicSelectionsSingle)?.territorio_id,
            departamento_id: (props.modelValue as GeographicSelectionsSingle)?.departamento_id,
            municipio_id: (props.modelValue as GeographicSelectionsSingle)?.municipio_id,
            localidad_id: (props.modelValue as GeographicSelectionsSingle)?.localidad_id,
        } as GeographicSelectionsSingle;
        
        // Actualizar el campo correspondiente y limpiar cascada
        if (field === 'territorio') {
            (newSelections as GeographicSelectionsSingle).territorio_id = singleValue;
            (newSelections as GeographicSelectionsSingle).departamento_id = undefined;
            (newSelections as GeographicSelectionsSingle).municipio_id = undefined;
            (newSelections as GeographicSelectionsSingle).localidad_id = undefined;
            nextTick(() => loadDepartamentos(singleValue ? [singleValue] : []));
        } else if (field === 'departamento') {
            (newSelections as GeographicSelectionsSingle).departamento_id = singleValue;
            (newSelections as GeographicSelectionsSingle).municipio_id = undefined;
            (newSelections as GeographicSelectionsSingle).localidad_id = undefined;
            nextTick(() => loadMunicipios(singleValue ? [singleValue] : []));
        } else if (field === 'municipio') {
            (newSelections as GeographicSelectionsSingle).municipio_id = singleValue;
            (newSelections as GeographicSelectionsSingle).localidad_id = undefined;
            nextTick(() => loadLocalidades(singleValue ? [singleValue] : []));
        } else if (field === 'localidad') {
            (newSelections as GeographicSelectionsSingle).localidad_id = singleValue;
        }
    }
    
    emit('update:modelValue', newSelections);
};

// Initialize data on mount
onMounted(async () => {
    await loadTerritorios();
    
    // Load existing data for editing mode
    if (selectedTerritorios.value.length) {
        await loadDepartamentos(selectedTerritorios.value);
    }
    if (selectedDepartamentos.value.length) {
        await loadMunicipios(selectedDepartamentos.value);
    }
    if (selectedMunicipios.value.length) {
        await loadLocalidades(selectedMunicipios.value);
    }
});

// Helper para formatear el texto de selección
const getSelectionText = (count: number, singular: string, plural: string) => {
    if (isMultipleMode.value) {
        return `${count} ${count === 1 ? singular : plural} seleccionado${count === 1 ? '' : 's'}`;
    }
    return '';
};
</script>

<template>
    <component :is="showCard ? Card : 'div'" :class="showCard ? 'border-dashed' : ''">
        <CardHeader v-if="showCard">
            <CardTitle class="text-base">{{ title }}</CardTitle>
            <p class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </CardHeader>
        <component :is="showCard ? CardContent : 'div'" class="space-y-4">
            <!-- Territorios -->
            <div>
                <Label>Territorio{{ isMultipleMode ? 's' : '' }}</Label>
                <Select 
                    :model-value="isMultipleMode ? selectedTerritorios.map(id => id.toString()) : (selectedTerritorios[0]?.toString() || '')"
                    @update:model-value="(value) => handleUpdate(value, 'territorio')"
                    :multiple="isMultipleMode"
                    :disabled="disabled || loading"
                >
                    <SelectTrigger>
                        <SelectValue :placeholder="`Seleccionar territorio${isMultipleMode ? 's' : ''}...`" />
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
                <p v-if="isMultipleMode" class="text-xs text-muted-foreground mt-1">
                    {{ getSelectionText(selectedTerritorios.length, 'territorio', 'territorios') }}
                </p>
            </div>

            <!-- Departamentos -->
            <div v-if="departamentos.length > 0">
                <Label>Departamento{{ isMultipleMode ? 's' : '' }}</Label>
                <Select 
                    :model-value="isMultipleMode ? selectedDepartamentos.map(id => id.toString()) : (selectedDepartamentos[0]?.toString() || '')"
                    @update:model-value="(value) => handleUpdate(value, 'departamento')"
                    :multiple="isMultipleMode"
                    :disabled="disabled || loading"
                >
                    <SelectTrigger>
                        <SelectValue :placeholder="`Seleccionar departamento${isMultipleMode ? 's' : ''}...`" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem 
                            v-for="departamento in departamentos" 
                            :key="departamento.id" 
                            :value="departamento.id.toString()"
                        >
                            {{ departamento.nombre }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="isMultipleMode" class="text-xs text-muted-foreground mt-1">
                    {{ getSelectionText(selectedDepartamentos.length, 'departamento', 'departamentos') }}
                </p>
            </div>

            <!-- Municipios -->
            <div v-if="municipios.length > 0">
                <Label>Municipio{{ isMultipleMode ? 's' : '' }}</Label>
                <Select 
                    :model-value="isMultipleMode ? selectedMunicipios.map(id => id.toString()) : (selectedMunicipios[0]?.toString() || '')"
                    @update:model-value="(value) => handleUpdate(value, 'municipio')"
                    :multiple="isMultipleMode"
                    :disabled="disabled || loading"
                >
                    <SelectTrigger>
                        <SelectValue :placeholder="`Seleccionar municipio${isMultipleMode ? 's' : ''}...`" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem 
                            v-for="municipio in municipios" 
                            :key="municipio.id" 
                            :value="municipio.id.toString()"
                        >
                            {{ municipio.nombre }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="isMultipleMode" class="text-xs text-muted-foreground mt-1">
                    {{ getSelectionText(selectedMunicipios.length, 'municipio', 'municipios') }}
                </p>
            </div>

            <!-- Localidades -->
            <div v-if="localidades.length > 0">
                <Label>Localidad{{ isMultipleMode ? 'es' : '' }}</Label>
                <Select 
                    :model-value="isMultipleMode ? selectedLocalidades.map(id => id.toString()) : (selectedLocalidades[0]?.toString() || '')"
                    @update:model-value="(value) => handleUpdate(value, 'localidad')"
                    :multiple="isMultipleMode"
                    :disabled="disabled || loading"
                >
                    <SelectTrigger>
                        <SelectValue :placeholder="`Seleccionar localidad${isMultipleMode ? 'es' : ''}...`" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem 
                            v-for="localidad in localidades" 
                            :key="localidad.id" 
                            :value="localidad.id.toString()"
                        >
                            {{ localidad.nombre }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="isMultipleMode" class="text-xs text-muted-foreground mt-1">
                    {{ getSelectionText(selectedLocalidades.length, 'localidad', 'localidades') }}
                </p>
            </div>
        </component>
    </component>
</template>