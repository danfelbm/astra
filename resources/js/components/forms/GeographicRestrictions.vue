<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ref, onMounted, nextTick } from 'vue';
import type { GeographicRestrictions, Territorio, Departamento, Municipio, Localidad } from '@/types/forms';

interface Props {
    modelValue: GeographicRestrictions;
    disabled?: boolean;
    title?: string;
    description?: string;
}

interface Emits {
    (e: 'update:modelValue', value: GeographicRestrictions): void;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    title: 'Restricciones Geográficas (Opcional)',
    description: 'Selecciona las regiones donde estará disponible. Si no seleccionas nada, estará disponible para todos.',
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

// Load functions
const loadTerritorios = async () => {
    try {
        loading.value = true;
        const response = await fetch('/admin/geographic/territorios');
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
        const response = await fetch(`/admin/geographic/departamentos?territorio_ids=${territorioIds.join(',')}`);
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
        const response = await fetch(`/admin/geographic/municipios?departamento_ids=${departamentoIds.join(',')}`);
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
        const response = await fetch(`/admin/geographic/localidades?municipio_ids=${municipioIds.join(',')}`);
        localidades.value = await response.json();
    } catch (error) {
        console.error('Error loading localidades:', error);
    } finally {
        loading.value = false;
    }
};

// Helper function to handle multi-select values
const handleArrayUpdate = (value: any, field: keyof GeographicRestrictions) => {
    const arrayValue = Array.isArray(value) 
        ? value.map(Number).filter(Boolean) 
        : (value ? [Number(value)].filter(Boolean) : []);
    
    const newRestrictions = { 
        territorios_ids: props.modelValue?.territorios_ids || [],
        departamentos_ids: props.modelValue?.departamentos_ids || [],
        municipios_ids: props.modelValue?.municipios_ids || [],
        localidades_ids: props.modelValue?.localidades_ids || [],
        ...props.modelValue 
    };
    (newRestrictions[field] as number[]) = arrayValue;
    
    // Handle cascade clearing
    if (field === 'territorios_ids') {
        newRestrictions.departamentos_ids = [];
        newRestrictions.municipios_ids = [];
        newRestrictions.localidades_ids = [];
        // Load departamentos after clearing
        nextTick(() => loadDepartamentos(arrayValue));
    } else if (field === 'departamentos_ids') {
        newRestrictions.municipios_ids = [];
        newRestrictions.localidades_ids = [];
        // Load municipios after clearing
        nextTick(() => loadMunicipios(arrayValue));
    } else if (field === 'municipios_ids') {
        newRestrictions.localidades_ids = [];
        // Load localidades after clearing
        nextTick(() => loadLocalidades(arrayValue));
    }
    
    emit('update:modelValue', newRestrictions);
};

// Initialize data on mount
onMounted(async () => {
    await loadTerritorios();
    
    // Load existing data for editing mode
    if (props.modelValue?.territorios_ids?.length) {
        await loadDepartamentos(props.modelValue.territorios_ids);
    }
    if (props.modelValue?.departamentos_ids?.length) {
        await loadMunicipios(props.modelValue.departamentos_ids);
    }
    if (props.modelValue?.municipios_ids?.length) {
        await loadLocalidades(props.modelValue.municipios_ids);
    }
});
</script>

<template>
    <Card class="border-dashed">
        <CardHeader>
            <CardTitle class="text-base">{{ title }}</CardTitle>
            <p class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Territorios -->
            <div>
                <Label>Territorios</Label>
                <Select 
                    :model-value="(props.modelValue?.territorios_ids || []).map(id => id.toString())" 
                    @update:model-value="(value) => handleArrayUpdate(value, 'territorios_ids')"
                    multiple
                    :disabled="disabled || loading"
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Seleccionar territorios..." />
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
                <p class="text-xs text-muted-foreground mt-1">
                    {{ props.modelValue.territorios_ids.length }} territorio(s) seleccionado(s)
                </p>
            </div>

            <!-- Departamentos -->
            <div v-if="departamentos.length > 0">
                <Label>Departamentos</Label>
                <Select 
                    :model-value="(props.modelValue?.departamentos_ids || []).map(id => id.toString())" 
                    @update:model-value="(value) => handleArrayUpdate(value, 'departamentos_ids')"
                    multiple
                    :disabled="disabled || loading"
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Seleccionar departamentos..." />
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
                <p class="text-xs text-muted-foreground mt-1">
                    {{ (props.modelValue?.departamentos_ids || []).length }} departamento(s) seleccionado(s)
                </p>
            </div>

            <!-- Municipios -->
            <div v-if="municipios.length > 0">
                <Label>Municipios</Label>
                <Select 
                    :model-value="(props.modelValue?.municipios_ids || []).map(id => id.toString())" 
                    @update:model-value="(value) => handleArrayUpdate(value, 'municipios_ids')"
                    multiple
                    :disabled="disabled || loading"
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Seleccionar municipios..." />
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
                <p class="text-xs text-muted-foreground mt-1">
                    {{ (props.modelValue?.municipios_ids || []).length }} municipio(s) seleccionado(s)
                </p>
            </div>

            <!-- Localidades -->
            <div v-if="localidades.length > 0">
                <Label>Localidades</Label>
                <Select 
                    :model-value="(props.modelValue?.localidades_ids || []).map(id => id.toString())" 
                    @update:model-value="(value) => handleArrayUpdate(value, 'localidades_ids')"
                    multiple
                    :disabled="disabled || loading"
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Seleccionar localidades..." />
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
                <p class="text-xs text-muted-foreground mt-1">
                    {{ (props.modelValue?.localidades_ids || []).length }} localidad(es) seleccionada(s)
                </p>
            </div>
        </CardContent>
    </Card>
</template>