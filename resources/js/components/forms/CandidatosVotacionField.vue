<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { AlertCircle, Users } from 'lucide-vue-next';
import { computed } from 'vue';

interface CandidatoElegible {
    id: number;
    name: string;
    email?: string;
    cargo?: string;
    territorio?: string;
    departamento?: string;
    municipio?: string;
    localidad?: string;
}

interface Props {
    modelValue?: number | number[] | null;
    candidatosElegibles: CandidatoElegible[];
    label?: string;
    description?: string;
    required?: boolean;
    multiple?: boolean;
    mostrarVotoBlanco?: boolean;
    error?: string;
    disabled?: boolean;
}

interface Emits {
    (e: 'update:modelValue', value: number | number[] | null): void;
}

const props = withDefaults(defineProps<Props>(), {
    label: 'Seleccionar Candidato(s)',
    description: 'Selecciona entre los candidatos disponibles',
    required: false,
    multiple: false,
    mostrarVotoBlanco: true,
    disabled: false,
    candidatosElegibles: () => [],
});

const emit = defineEmits<Emits>();

const tieneCandidatos = computed(() => props.candidatosElegibles.length > 0);

// Manejo de selección simple (radio)
const handleRadioChange = (value: string) => {
    const numValue = value === 'null' ? null : parseInt(value);
    emit('update:modelValue', numValue);
};

// Manejo de selección múltiple (checkbox)
const isSelected = (candidatoId: number) => {
    if (props.multiple) {
        return Array.isArray(props.modelValue) && props.modelValue.includes(candidatoId);
    }
    return props.modelValue === candidatoId;
};

const handleCheckboxChange = (candidatoId: number, checked: boolean) => {
    if (!props.multiple) {
        emit('update:modelValue', checked ? candidatoId : null);
        return;
    }

    const currentValue = Array.isArray(props.modelValue) ? props.modelValue : [];
    let newValue: number[];

    if (checked) {
        newValue = [...currentValue, candidatoId];
    } else {
        newValue = currentValue.filter(id => id !== candidatoId);
    }

    emit('update:modelValue', newValue);
};

// Formatear ubicación del candidato
const formatUbicacion = (candidato: CandidatoElegible): string => {
    const partes = [];
    if (candidato.localidad) partes.push(candidato.localidad);
    if (candidato.municipio) partes.push(candidato.municipio);
    if (candidato.departamento) partes.push(candidato.departamento);
    if (candidato.territorio) partes.push(candidato.territorio);
    
    return partes.length > 0 ? partes.join(', ') : '';
};
</script>

<template>
    <div class="space-y-3">
        <div class="space-y-1.5">
            <Label class="text-sm font-medium">
                {{ label }}
                <span v-if="required" class="text-red-500 ml-1">*</span>
            </Label>
            <p v-if="description" class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>

        <!-- Sin candidatos disponibles -->
        <Card v-if="!tieneCandidatos" class="border-amber-200 bg-amber-50">
            <CardHeader class="pb-3">
                <div class="flex items-center space-x-2">
                    <AlertCircle class="h-5 w-5 text-amber-600" />
                    <CardTitle class="text-base text-amber-800">
                        No hay candidatos disponibles
                    </CardTitle>
                </div>
                <CardDescription class="text-amber-700">
                    No se encontraron candidatos que cumplan con los criterios configurados para esta votación.
                </CardDescription>
            </CardHeader>
        </Card>

        <!-- Lista de candidatos disponibles -->
        <div v-else class="space-y-3">
            <!-- Selección simple (radio buttons) -->
            <RadioGroup 
                v-if="!multiple"
                :model-value="modelValue?.toString() || 'null'"
                @update:model-value="handleRadioChange"
                :disabled="disabled"
                class="space-y-2"
            >
                <Card class="border">
                    <CardContent class="pt-6">
                        <div class="space-y-3">
                            <!-- Opción para voto en blanco (condicional) -->
                            <div v-if="mostrarVotoBlanco" class="flex items-start space-x-3 pb-3 border-b">
                                <RadioGroupItem value="null" id="candidato_none" />
                                <Label for="candidato_none" class="text-sm font-normal cursor-pointer">
                                    <span class="text-muted-foreground">Voto en blanco</span>
                                </Label>
                            </div>
                            
                            <!-- Lista de candidatos -->
                            <div 
                                v-for="candidato in candidatosElegibles" 
                                :key="candidato.id"
                                class="flex items-start space-x-3 py-2"
                            >
                                <RadioGroupItem 
                                    :value="candidato.id.toString()" 
                                    :id="`candidato_${candidato.id}`" 
                                />
                                <Label 
                                    :for="`candidato_${candidato.id}`" 
                                    class="text-sm font-normal cursor-pointer flex-1"
                                >
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-medium">{{ candidato.name }}</p>
                                            <p v-if="candidato.cargo" class="text-xs text-muted-foreground mt-0.5">
                                                {{ candidato.cargo }}
                                            </p>
                                            <p v-if="formatUbicacion(candidato)" class="text-xs text-muted-foreground">
                                                {{ formatUbicacion(candidato) }}
                                            </p>
                                        </div>
                                    </div>
                                </Label>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </RadioGroup>

            <!-- Selección múltiple (checkboxes) -->
            <Card v-else class="border">
                <CardHeader class="pb-3">
                    <div class="flex items-center space-x-2">
                        <Users class="h-4 w-4 text-muted-foreground" />
                        <CardTitle class="text-sm">
                            Candidatos disponibles
                        </CardTitle>
                    </div>
                    <CardDescription class="text-xs">
                        Puedes seleccionar múltiples candidatos
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div 
                            v-for="candidato in candidatosElegibles" 
                            :key="candidato.id"
                            class="flex items-start space-x-3"
                        >
                            <Checkbox
                                :id="`candidato_check_${candidato.id}`"
                                :checked="isSelected(candidato.id)"
                                @update:checked="(checked) => handleCheckboxChange(candidato.id, checked)"
                                :disabled="disabled"
                            />
                            <Label 
                                :for="`candidato_check_${candidato.id}`" 
                                class="text-sm font-normal cursor-pointer flex-1"
                            >
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-medium">{{ candidato.name }}</p>
                                        <p v-if="candidato.cargo" class="text-xs text-muted-foreground mt-0.5">
                                            {{ candidato.cargo }}
                                        </p>
                                        <p v-if="formatUbicacion(candidato)" class="text-xs text-muted-foreground">
                                            {{ formatUbicacion(candidato) }}
                                        </p>
                                    </div>
                                </div>
                            </Label>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Error message -->
        <p v-if="error" class="text-sm text-red-600">
            {{ error }}
        </p>
    </div>
</template>