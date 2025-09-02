<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import GeographicRestrictions from '@/components/forms/GeographicRestrictions.vue';
import { computed } from 'vue';
import type { GeographicRestrictions as GeographicRestrictionsType } from '@/types/forms';

interface PerfilCandidaturaConfig {
    cargo_id?: number | 'all';
    periodo_electoral_id?: number | 'all';
    territorio_id?: number;
    departamento_id?: number;
    municipio_id?: number;
    localidad_id?: number;
    territorios_ids?: number[];
    departamentos_ids?: number[];
    municipios_ids?: number[];
    localidades_ids?: number[];
    multiple?: boolean;
    mostrarVotoBlanco?: boolean;
}

interface Cargo {
    id: number;
    nombre: string;
    ruta_jerarquica?: string;
}

interface PeriodoElectoral {
    id: number;
    nombre: string;
    fecha_inicio: string;
    fecha_fin: string;
}

interface Props {
    modelValue: PerfilCandidaturaConfig;
    cargos?: Cargo[];
    periodosElectorales?: PeriodoElectoral[];
    context?: 'convocatoria' | 'votacion' | 'candidatura';
    disabled?: boolean;
}

interface Emits {
    (e: 'update:modelValue', value: PerfilCandidaturaConfig): void;
}

const props = withDefaults(defineProps<Props>(), {
    cargos: () => [],
    periodosElectorales: () => [],
    context: 'convocatoria',
});

const emit = defineEmits<Emits>();

// Computed para manejar el modelValue
const localConfig = computed({
    get: () => props.modelValue || {
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
        mostrarVotoBlanco: true
    },
    set: (value: PerfilCandidaturaConfig) => emit('update:modelValue', value)
});

const updateConfig = (key: keyof PerfilCandidaturaConfig, value: any) => {
    emit('update:modelValue', {
        ...localConfig.value,
        [key]: value
    });
};

// Computed para restricciones geográficas
const geographicRestrictions = computed({
    get: (): GeographicRestrictionsType => ({
        territorio_id: localConfig.value.territorio_id,
        departamento_id: localConfig.value.departamento_id,
        municipio_id: localConfig.value.municipio_id,
        localidad_id: localConfig.value.localidad_id,
        territorios_ids: localConfig.value.territorios_ids || [],
        departamentos_ids: localConfig.value.departamentos_ids || [],
        municipios_ids: localConfig.value.municipios_ids || [],
        localidades_ids: localConfig.value.localidades_ids || []
    }),
    set: (value: GeographicRestrictionsType) => {
        emit('update:modelValue', {
            ...localConfig.value,
            territorio_id: value.territorio_id,
            departamento_id: value.departamento_id,
            municipio_id: value.municipio_id,
            localidad_id: value.localidad_id,
            territorios_ids: value.territorios_ids,
            departamentos_ids: value.departamentos_ids,
            municipios_ids: value.municipios_ids,
            localidades_ids: value.localidades_ids
        });
    }
});

// Computed para obtener el cargo seleccionado
const cargoSeleccionado = computed(() => {
    if (!localConfig.value.cargo_id || localConfig.value.cargo_id === 'all') return null;
    return props.cargos.find(c => c.id === localConfig.value.cargo_id);
});

// Computed para obtener el período seleccionado
const periodoSeleccionado = computed(() => {
    if (!localConfig.value.periodo_electoral_id || localConfig.value.periodo_electoral_id === 'all') return null;
    return props.periodosElectorales.find(p => p.id === localConfig.value.periodo_electoral_id);
});

// Computed para validación básica
const hasBasicConfig = computed(() => {
    return localConfig.value.cargo_id !== undefined || localConfig.value.periodo_electoral_id !== undefined;
});

// Mostrar configuraciones avanzadas para votaciones y formularios
// En convocatorias NO se muestran (solo permite elegir candidatura propia)
const showAdvancedConfig = computed(() => {
    return props.context === 'votacion' || props.context === 'formulario';
});
</script>

<template>
    <div class="space-y-4">
        <div class="p-4 bg-muted/50 dark:bg-muted/20 rounded-lg space-y-4">
            <h4 v-if="showAdvancedConfig" class="font-medium flex items-center gap-2">
                <Badge variant="secondary" class="text-xs">Configuración de Perfil de Candidatura</Badge>
            </h4>
            
            <div v-if="showAdvancedConfig" class="space-y-4">
                <!-- Selector de cargo -->
                <div>
                    <Label>Cargo</Label>
                    <Select 
                        :model-value="localConfig.cargo_id ? String(localConfig.cargo_id) : undefined"
                        @update:model-value="(value) => updateConfig('cargo_id', value === 'all' ? 'all' : (value ? Number(value) : undefined))"
                        :disabled="disabled"
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Selecciona un cargo" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                <span class="font-medium">Todos los cargos</span>
                            </SelectItem>
                            <SelectItem 
                                v-for="cargo in cargos"
                                :key="cargo.id"
                                :value="String(cargo.id)"
                            >
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ cargo.nombre }}</span>
                                    <span v-if="cargo.ruta_jerarquica" class="text-xs text-muted-foreground">
                                        {{ cargo.ruta_jerarquica }}
                                    </span>
                                </div>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                
                <!-- Selector de período electoral -->
                <div>
                    <Label>Período Electoral</Label>
                    <Select 
                        :model-value="localConfig.periodo_electoral_id ? String(localConfig.periodo_electoral_id) : undefined"
                        @update:model-value="(value) => updateConfig('periodo_electoral_id', value === 'all' ? 'all' : (value ? Number(value) : undefined))"
                        :disabled="disabled"
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Selecciona un período" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                <span class="font-medium">Todos los períodos</span>
                            </SelectItem>
                            <SelectItem 
                                v-for="periodo in periodosElectorales"
                                :key="periodo.id"
                                :value="String(periodo.id)"
                            >
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ periodo.nombre }}</span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ new Date(periodo.fecha_inicio).toLocaleDateString() }} - 
                                        {{ new Date(periodo.fecha_fin).toLocaleDateString() }}
                                    </span>
                                </div>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                
                <!-- Configuraciones avanzadas -->
                <div class="space-y-4">
                    <!-- Opciones de votación -->
                    <div>
                        <Label class="text-sm font-medium mb-3 block">Opciones de votación</Label>
                        <div class="space-y-2">
                            <div class="flex items-center space-x-2">
                                <Checkbox
                                    :checked="localConfig.multiple"
                                    @update:checked="(checked) => updateConfig('multiple', checked)"
                                    :disabled="disabled"
                                />
                                <Label class="text-sm">Permitir selección múltiple de candidatos</Label>
                            </div>
                            
                            <div class="flex items-center space-x-2">
                                <Checkbox
                                    :checked="localConfig.mostrarVotoBlanco"
                                    @update:checked="(checked) => updateConfig('mostrarVotoBlanco', checked)"
                                    :disabled="disabled"
                                />
                                <Label class="text-sm">Mostrar opción de voto en blanco</Label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Restricciones geográficas -->
                    <div>
                        <Label class="text-sm font-medium mb-3 block">Restricciones Geográficas (opcional)</Label>
                        <p class="text-xs text-muted-foreground mb-3">
                            Limita los candidatos mostrados según ubicación geográfica
                        </p>
                        
                        <GeographicRestrictions
                            v-model="geographicRestrictions"
                            :disabled="disabled"
                        />
                    </div>
                </div>
            </div>
            
            <!-- Validación -->
            <div v-if="showAdvancedConfig && !hasBasicConfig" class="p-3 bg-destructive/10 dark:bg-destructive/20 rounded-md">
                <p class="text-sm text-destructive">
                    ⚠️ Debes seleccionar al menos un cargo o período electoral
                </p>
            </div>
            
            <!-- Resumen de configuración -->
            <div v-if="showAdvancedConfig && hasBasicConfig" class="mt-4 p-3 bg-muted/30 dark:bg-muted/10 rounded-md">
                <p class="text-sm text-muted-foreground">
                    <strong>Configuración:</strong>
                    <br>
                    <span v-if="cargoSeleccionado">
                        <strong>Cargo:</strong> {{ cargoSeleccionado.ruta_jerarquica || cargoSeleccionado.nombre }}
                    </span>
                    <span v-else-if="localConfig.cargo_id === 'all'">
                        <strong>Cargo:</strong> Todos los cargos
                    </span>
                    <br v-if="localConfig.cargo_id">
                    <span v-if="periodoSeleccionado">
                        <strong>Período:</strong> {{ periodoSeleccionado.nombre }}
                    </span>
                    <span v-else-if="localConfig.periodo_electoral_id === 'all'">
                        <strong>Período:</strong> Todos los períodos
                    </span>
                    <br v-if="showAdvancedConfig">
                    <span v-if="showAdvancedConfig">
                        {{ localConfig.multiple ? 'Selección múltiple' : 'Selección única' }},
                        {{ localConfig.mostrarVotoBlanco ? 'con' : 'sin' }} voto en blanco
                    </span>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Estilos específicos para el config de perfil candidatura */
</style>