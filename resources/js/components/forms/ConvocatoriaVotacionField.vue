<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { AlertCircle, Users, User } from 'lucide-vue-next';
import { computed } from 'vue';

interface CandidatoConvocatoria {
    id: number;
    name: string;
    email?: string;
    // Datos de la postulación
    postulacion_id: number;
    fecha_postulacion?: string;
    // Datos de la candidatura si está vinculada
    candidatura_snapshot?: any;
    // Datos del cargo y ubicación
    cargo?: string;
    territorio?: string;
    departamento?: string;
    municipio?: string;
    localidad?: string;
}

interface Props {
    modelValue?: string | string[] | null;
    candidatos: CandidatoConvocatoria[];
    label?: string;
    description?: string;
    required?: boolean;
    multiple?: boolean;
    mostrarVotoBlanco?: boolean;
    error?: string;
    disabled?: boolean;
    convocatoriaNombre?: string;
    vistaPreferida?: 'lista' | 'cards';
}

interface Emits {
    (e: 'update:modelValue', value: string | string[] | null): void;
}

const props = withDefaults(defineProps<Props>(), {
    label: 'Seleccionar Candidato(s)',
    description: 'Selecciona entre los candidatos aprobados de la convocatoria',
    required: false,
    multiple: false,
    mostrarVotoBlanco: true,
    disabled: false,
    candidatos: () => [],
    vistaPreferida: 'lista',
});

const emit = defineEmits<Emits>();

const tieneCandidatos = computed(() => props.candidatos.length > 0);

// Manejo de selección simple (radio)
const handleRadioChange = (value: string) => {
    // Si es 'null' (voto en blanco), emitir null, sino emitir el nombre directamente
    const nameValue = value === 'null' ? null : value;
    emit('update:modelValue', nameValue);
};

// Manejo de selección múltiple (checkbox)
const isSelected = (candidatoName: string) => {
    if (props.multiple) {
        return Array.isArray(props.modelValue) && props.modelValue.includes(candidatoName);
    }
    return props.modelValue === candidatoName;
};

const handleCheckboxChange = (candidatoName: string, checked: boolean) => {
    if (!props.multiple) {
        emit('update:modelValue', checked ? candidatoName : null);
        return;
    }

    const currentValue = Array.isArray(props.modelValue) ? props.modelValue : [];
    let newValue: string[];

    if (checked) {
        newValue = [...currentValue, candidatoName];
    } else {
        newValue = currentValue.filter(name => name !== candidatoName);
    }

    emit('update:modelValue', newValue);
};

// Formatear ubicación del candidato
const formatUbicacion = (candidato: CandidatoConvocatoria): string => {
    const partes = [];
    if (candidato.localidad) partes.push(candidato.localidad);
    if (candidato.municipio) partes.push(candidato.municipio);
    if (candidato.departamento) partes.push(candidato.departamento);
    if (candidato.territorio) partes.push(candidato.territorio);
    
    return partes.length > 0 ? partes.join(', ') : '';
};

// Obtener información adicional del candidato desde su candidatura snapshot
const getInfoAdicional = (candidato: CandidatoConvocatoria): string => {
    if (!candidato.candidatura_snapshot?.formulario_data) return '';
    
    // Intentar extraer información relevante del formulario de candidatura
    const data = candidato.candidatura_snapshot.formulario_data;
    const info = [];
    
    // Buscar campos comunes como profesión, experiencia, etc.
    if (data.profesion) info.push(data.profesion);
    if (data.experiencia) info.push(`${data.experiencia} años de experiencia`);
    
    return info.join(' - ');
};

// Obtener iniciales del candidato para el Avatar
const getInitials = (name: string): string => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
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
            <p v-if="convocatoriaNombre" class="text-xs text-blue-600 font-medium">
                Convocatoria: {{ convocatoriaNombre }}
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
                    No se encontraron candidatos con postulaciones aprobadas para esta convocatoria.
                </CardDescription>
            </CardHeader>
        </Card>

        <!-- Lista de candidatos disponibles -->
        <div v-else class="space-y-3">
            <!-- Vista Lista - Selección simple (radio buttons) -->
            <RadioGroup 
                v-if="!multiple && vistaPreferida === 'lista'"
                :model-value="modelValue"
                @update:model-value="handleRadioChange"
                :disabled="disabled"
                class="space-y-2"
            >
                <Card class="border">
                    <CardContent class="pt-6">
                        <div class="space-y-3">
                            <!-- Lista de candidatos -->
                            <div 
                                v-for="candidato in candidatos" 
                                :key="candidato.id"
                                class="flex items-start space-x-3 py-2"
                            >
                                <RadioGroupItem 
                                    :value="candidato.name" 
                                    :id="`candidato_${candidato.id}`" 
                                />
                                <Label 
                                    :for="`candidato_${candidato.id}`" 
                                    class="text-sm font-normal cursor-pointer flex-1"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <User class="h-4 w-4 text-muted-foreground" />
                                                <p class="font-medium">{{ candidato.name }}</p>
                                            </div>
                                            <p v-if="candidato.cargo" class="text-xs text-muted-foreground ml-6">
                                                Cargo: {{ candidato.cargo }}
                                            </p>
                                            <p v-if="formatUbicacion(candidato)" class="text-xs text-muted-foreground ml-6">
                                                Ubicación: {{ formatUbicacion(candidato) }}
                                            </p>
                                            <p v-if="getInfoAdicional(candidato)" class="text-xs text-blue-600 ml-6">
                                                {{ getInfoAdicional(candidato) }}
                                            </p>
                                        </div>
                                    </div>
                                </Label>
                            </div>
                            
                            <!-- Opción para voto en blanco (al final) -->
                            <div v-if="mostrarVotoBlanco" class="flex items-start space-x-3 pt-3 border-t">
                                <RadioGroupItem value="null" id="candidato_none" />
                                <Label for="candidato_none" class="text-sm font-normal cursor-pointer">
                                    <span class="text-muted-foreground">Voto en blanco</span>
                                </Label>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </RadioGroup>

            <!-- Vista Lista - Selección múltiple (checkboxes) -->
            <Card v-else-if="multiple && vistaPreferida === 'lista'" class="border">
                <CardHeader class="pb-3">
                    <div class="flex items-center space-x-2">
                        <Users class="h-4 w-4 text-muted-foreground" />
                        <CardTitle class="text-sm">
                            Candidatos disponibles
                        </CardTitle>
                    </div>
                    <CardDescription class="text-xs">
                        Puedes seleccionar múltiples candidatos de la convocatoria
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div 
                            v-for="candidato in candidatos" 
                            :key="candidato.id"
                            class="flex items-start space-x-3"
                        >
                            <Checkbox
                                :id="`candidato_check_${candidato.id}`"
                                :checked="isSelected(candidato.name)"
                                @update:checked="(checked) => handleCheckboxChange(candidato.name, checked)"
                                :disabled="disabled"
                            />
                            <Label 
                                :for="`candidato_check_${candidato.id}`" 
                                class="text-sm font-normal cursor-pointer flex-1"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <User class="h-4 w-4 text-muted-foreground" />
                                            <p class="font-medium">{{ candidato.name }}</p>
                                        </div>
                                        <p v-if="candidato.cargo" class="text-xs text-muted-foreground ml-6">
                                            Cargo: {{ candidato.cargo }}
                                        </p>
                                        <p v-if="formatUbicacion(candidato)" class="text-xs text-muted-foreground ml-6">
                                            Ubicación: {{ formatUbicacion(candidato) }}
                                        </p>
                                        <p v-if="getInfoAdicional(candidato)" class="text-xs text-blue-600 ml-6">
                                            {{ getInfoAdicional(candidato) }}
                                        </p>
                                    </div>
                                </div>
                            </Label>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Vista Cards - Selección simple (radio buttons) -->
            <RadioGroup 
                v-if="!multiple && vistaPreferida === 'cards'"
                :model-value="modelValue"
                @update:model-value="handleRadioChange"
                :disabled="disabled"
                class="space-y-4"
            >
                <!-- Grid de candidatos y voto en blanco -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Card 
                        v-for="candidato in candidatos" 
                        :key="candidato.id"
                        class="cursor-pointer transition-all hover:shadow-md"
                        :class="modelValue === candidato.name ? 'ring-2 ring-primary' : ''"
                        @click="handleRadioChange(candidato.name)"
                    >
                        <CardContent class="p-4">
                            <!-- Avatar y nombre -->
                            <div class="flex items-center gap-3 mb-3">
                                <Avatar class="h-12 w-12">
                                    <AvatarFallback class="bg-primary/10 text-primary font-semibold">
                                        {{ getInitials(candidato.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium truncate">{{ candidato.name }}</p>
                                    <p v-if="candidato.email" class="text-xs text-muted-foreground truncate">
                                        {{ candidato.email }}
                                    </p>
                                </div>
                            </div>

                            <!-- Información adicional -->
                            <div class="space-y-1 text-xs text-muted-foreground">
                                <p v-if="candidato.cargo" class="truncate">
                                    <span class="font-medium">Cargo:</span> {{ candidato.cargo }}
                                </p>
                                <p v-if="formatUbicacion(candidato)" class="truncate">
                                    <span class="font-medium">Ubicación:</span> {{ formatUbicacion(candidato) }}
                                </p>
                                <p v-if="getInfoAdicional(candidato)" class="text-blue-600 truncate">
                                    {{ getInfoAdicional(candidato) }}
                                </p>
                            </div>

                            <!-- Radio button -->
                            <div class="flex justify-end mt-3 pt-3 border-t">
                                <RadioGroupItem 
                                    :value="candidato.name" 
                                    :checked="modelValue === candidato.name"
                                    @click.stop
                                />
                            </div>
                        </CardContent>
                    </Card>
                    
                    <!-- Voto en blanco (al final del grid) -->
                    <Card 
                        v-if="mostrarVotoBlanco" 
                        class="cursor-pointer transition-all hover:shadow-md"
                        :class="modelValue === null ? 'ring-2 ring-primary' : ''"
                        @click="handleRadioChange('null')"
                    >
                        <CardContent class="flex items-center justify-center h-full min-h-[180px] p-4">
                            <div class="text-center">
                                <span class="text-muted-foreground text-lg">Voto en blanco</span>
                                <div class="mt-4">
                                    <RadioGroupItem 
                                        value="null" 
                                        :checked="modelValue === null"
                                        @click.stop
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </RadioGroup>

            <!-- Vista Cards - Selección múltiple (checkboxes) -->
            <div v-else-if="multiple && vistaPreferida === 'cards'" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Card 
                        v-for="candidato in candidatos" 
                        :key="candidato.id"
                        class="cursor-pointer transition-all hover:shadow-md"
                        :class="isSelected(candidato.name) ? 'ring-2 ring-primary' : ''"
                        @click="handleCheckboxChange(candidato.name, !isSelected(candidato.name))"
                    >
                        <CardContent class="p-4">
                            <!-- Avatar y nombre -->
                            <div class="flex items-center gap-3 mb-3">
                                <Avatar class="h-12 w-12">
                                    <AvatarFallback class="bg-primary/10 text-primary font-semibold">
                                        {{ getInitials(candidato.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium truncate">{{ candidato.name }}</p>
                                    <p v-if="candidato.email" class="text-xs text-muted-foreground truncate">
                                        {{ candidato.email }}
                                    </p>
                                </div>
                            </div>

                            <!-- Información adicional -->
                            <div class="space-y-1 text-xs text-muted-foreground">
                                <p v-if="candidato.cargo" class="truncate">
                                    <span class="font-medium">Cargo:</span> {{ candidato.cargo }}
                                </p>
                                <p v-if="formatUbicacion(candidato)" class="truncate">
                                    <span class="font-medium">Ubicación:</span> {{ formatUbicacion(candidato) }}
                                </p>
                                <p v-if="getInfoAdicional(candidato)" class="text-blue-600 truncate">
                                    {{ getInfoAdicional(candidato) }}
                                </p>
                            </div>

                            <!-- Checkbox -->
                            <div class="flex justify-end mt-3 pt-3 border-t">
                                <Checkbox
                                    :checked="isSelected(candidato.name)"
                                    @update:checked="(checked) => handleCheckboxChange(candidato.name, checked)"
                                    @click.stop
                                    :disabled="disabled"
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Error message -->
        <p v-if="error" class="text-sm text-red-600">
            {{ error }}
        </p>
    </div>
</template>