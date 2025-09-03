<script setup lang="ts">
import { Checkbox } from "../../../ui/checkbox";
import { Label } from "../../../ui/label";
import { RadioGroup, RadioGroupItem } from "../../../ui/radio-group";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../../../ui/select";
import { computed } from 'vue';

interface ConvocatoriaConfig {
    convocatoria_id?: number;
    multiple?: boolean;
    mostrarVotoBlanco?: boolean;
    ordenCandidatos?: 'aleatorio' | 'alfabetico' | 'fecha_postulacion';
    vistaPreferida?: 'lista' | 'cards';
}

interface Convocatoria {
    id: number;
    nombre: string;
    cargo?: { nombre: string; ruta_jerarquica?: string };
    periodo_electoral?: { nombre: string };
    estado_temporal?: string;
}

interface Props {
    modelValue: ConvocatoriaConfig;
    convocatorias?: Convocatoria[];
    disabled?: boolean;
}

interface Emits {
    (e: 'update:modelValue', value: ConvocatoriaConfig): void;
}

const props = withDefaults(defineProps<Props>(), {
    convocatorias: () => [],
});

const emit = defineEmits<Emits>();

// Computed para manejar el modelValue
const localConfig = computed({
    get: () => props.modelValue || {
        convocatoria_id: undefined,
        multiple: false,
        mostrarVotoBlanco: true,
        ordenCandidatos: 'aleatorio',
        vistaPreferida: 'lista'
    },
    set: (value: ConvocatoriaConfig) => emit('update:modelValue', value)
});

const updateConfig = (key: keyof ConvocatoriaConfig, value: any) => {
    emit('update:modelValue', {
        ...localConfig.value,
        [key]: value
    });
};
</script>

<template>
    <!-- Configuración especial para convocatoria en votaciones - CÓDIGO ORIGINAL RESTAURADO -->
    <div class="p-4 bg-green-50 dark:bg-green-950/20 rounded-lg border border-green-200 dark:border-green-800 space-y-4">
        <h4 class="font-medium text-green-900 dark:text-green-100 mb-3">Selección de Convocatoria para Votación</h4>
        <p class="text-sm text-green-700 dark:text-green-300 mb-4">
            Selecciona UNA convocatoria específica. Los usuarios con postulaciones APROBADAS a esta convocatoria aparecerán como opciones de voto.
        </p>
        
        <!-- Selector de Convocatoria -->
        <div v-if="convocatorias && convocatorias.length > 0" class="mb-4">
            <Label>Convocatoria *</Label>
            <Select 
                :model-value="localConfig.convocatoria_id ? localConfig.convocatoria_id.toString() : undefined"
                @update:model-value="(value) => updateConfig('convocatoria_id', value ? Number(value) : undefined)"
                :disabled="disabled"
            >
                <SelectTrigger>
                    <SelectValue placeholder="Selecciona una convocatoria" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="conv in convocatorias"
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
        <div v-else class="mb-4 p-3 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-md">
            <p class="text-sm text-amber-800 dark:text-amber-200">
                No hay convocatorias con postulaciones aprobadas disponibles. Asegúrate de que existan convocatorias con al menos una postulación aprobada.
            </p>
        </div>

        <!-- Opción de selección múltiple -->
        <div class="flex items-center space-x-2 mb-3">
            <Checkbox
                id="conv_multiple"
                :checked="localConfig.multiple"
                @update:checked="(checked) => updateConfig('multiple', checked)"
                :disabled="disabled"
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
                :checked="localConfig.mostrarVotoBlanco"
                @update:checked="(checked) => updateConfig('mostrarVotoBlanco', checked)"
                :disabled="disabled"
            />
            <Label class="text-sm">
                ¿Deseas mostrar Voto en Blanco?
                <span class="text-muted-foreground block text-xs">
                    Agrega la opción "Voto en blanco" para los votantes
                </span>
            </Label>
        </div>

        <!-- Orden de candidatos -->
        <div class="mt-4 space-y-2">
            <Label class="text-sm font-medium">Orden de candidatos</Label>
            <RadioGroup 
                :model-value="localConfig.ordenCandidatos" 
                @update:model-value="(value) => updateConfig('ordenCandidatos', value)"
                :disabled="disabled"
            >
                <div class="space-y-2">
                    <div class="flex items-start space-x-2">
                        <RadioGroupItem value="aleatorio" id="orden_aleatorio" />
                        <Label for="orden_aleatorio" class="text-sm font-normal cursor-pointer">
                            <div>
                                Aleatorio por sesión
                                <span class="text-xs text-muted-foreground block">
                                    Recomendado para equidad electoral - cada votante ve un orden diferente
                                </span>
                            </div>
                        </Label>
                    </div>
                    <div class="flex items-start space-x-2">
                        <RadioGroupItem value="alfabetico" id="orden_alfabetico" />
                        <Label for="orden_alfabetico" class="text-sm font-normal cursor-pointer">
                            <div>
                                Alfabético por nombre
                                <span class="text-xs text-muted-foreground block">
                                    Orden predecible y consistente para todos los votantes
                                </span>
                            </div>
                        </Label>
                    </div>
                    <div class="flex items-start space-x-2">
                        <RadioGroupItem value="fecha_postulacion" id="orden_fecha" />
                        <Label for="orden_fecha" class="text-sm font-normal cursor-pointer">
                            <div>
                                Por fecha de postulación
                                <span class="text-xs text-muted-foreground block">
                                    Más recientes primero - refleja actividad reciente
                                </span>
                            </div>
                        </Label>
                    </div>
                </div>
            </RadioGroup>
        </div>

        <!-- Vista de presentación -->
        <div class="mt-4 space-y-2">
            <Label class="text-sm font-medium">Vista de presentación</Label>
            <RadioGroup 
                :model-value="localConfig.vistaPreferida" 
                @update:model-value="(value) => updateConfig('vistaPreferida', value)"
                :disabled="disabled"
            >
                <div class="space-y-2">
                    <div class="flex items-start space-x-2">
                        <RadioGroupItem value="lista" id="vista_lista" />
                        <Label for="vista_lista" class="text-sm font-normal cursor-pointer">
                            <div>
                                Vista en lista
                                <span class="text-xs text-muted-foreground block">
                                    Lista vertical tradicional - ocupa menos espacio
                                </span>
                            </div>
                        </Label>
                    </div>
                    <div class="flex items-start space-x-2">
                        <RadioGroupItem value="cards" id="vista_cards" />
                        <Label for="vista_cards" class="text-sm font-normal cursor-pointer">
                            <div>
                                Vista en tarjetas
                                <span class="text-xs text-muted-foreground block">
                                    Tarjetas visuales en grid - más atractivo visualmente
                                </span>
                            </div>
                        </Label>
                    </div>
                </div>
            </RadioGroup>
        </div>
    </div>
</template>

<style scoped>
/* Estilos específicos para el config de convocatoria */
</style>