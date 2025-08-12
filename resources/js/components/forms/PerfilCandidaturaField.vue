<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { AlertCircle, FileText, Plus, User } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface CandidaturaAprobada {
    id: number;
    version: number;
    fecha_aprobacion: string;
    resumen: string;
}

interface Props {
    modelValue?: number | null;
    candidaturasAprobadas: CandidaturaAprobada[];
    label?: string;
    description?: string;
    required?: boolean;
    error?: string;
    disabled?: boolean;
}

interface Emits {
    (e: 'update:modelValue', value: number | null): void;
}

const props = withDefaults(defineProps<Props>(), {
    label: 'Perfil de Candidatura',
    description: 'Selecciona tu perfil de candidatura aprobado para vincular a esta postulación',
    required: false,
    disabled: false,
});

const emit = defineEmits<Emits>();

const candidaturaSeleccionada = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

const tieneCandidaturas = computed(() => props.candidaturasAprobadas.length > 0);

const candidaturaSeleccionadaData = computed(() => {
    if (!candidaturaSeleccionada.value) return null;
    return props.candidaturasAprobadas.find(c => c.id === candidaturaSeleccionada.value);
});

const handleSelectChange = (value: string) => {
    const numValue = value === 'null' ? null : parseInt(value);
    candidaturaSeleccionada.value = numValue;
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

        <!-- Sin candidaturas aprobadas -->
        <Card v-if="!tieneCandidaturas" class="border-amber-200 bg-amber-50">
            <CardHeader class="pb-3">
                <div class="flex items-center space-x-2">
                    <AlertCircle class="h-5 w-5 text-amber-600" />
                    <CardTitle class="text-base text-amber-800">
                        No tienes un perfil de candidatura aprobado
                    </CardTitle>
                </div>
                <CardDescription class="text-amber-700">
                    Para vincular tu perfil a esta postulación, primero necesitas crear y obtener la aprobación de tu candidatura.
                </CardDescription>
            </CardHeader>
            <CardContent class="pt-0">
                <Button 
                    variant="outline" 
                    size="sm" 
                    class="border-amber-300 text-amber-800 hover:bg-amber-100"
                    @click="router.get('/candidaturas')"
                >
                    <Plus class="mr-2 h-4 w-4" />
                    Crear Perfil de Candidatura
                </Button>
            </CardContent>
        </Card>

        <!-- Con candidaturas disponibles -->
        <div v-else class="space-y-3">
            <Select 
                :model-value="candidaturaSeleccionada?.toString() || 'null'"
                @update:model-value="handleSelectChange"
                :disabled="disabled"
            >
                <SelectTrigger :class="[
                    'w-full',
                    error ? 'border-red-300' : ''
                ]">
                    <SelectValue placeholder="Selecciona tu perfil de candidatura" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="null">
                        <div class="flex items-center space-x-2">
                            <span class="text-muted-foreground">No vincular perfil</span>
                        </div>
                    </SelectItem>
                    <SelectItem 
                        v-for="candidatura in candidaturasAprobadas" 
                        :key="candidatura.id"
                        :value="candidatura.id.toString()"
                    >
                        <div class="flex items-center space-x-2">
                            <User class="h-4 w-4" />
                            <span>{{ candidatura.resumen }}</span>
                        </div>
                    </SelectItem>
                </SelectContent>
            </Select>

            <!-- Vista previa de candidatura seleccionada -->
            <Card v-if="candidaturaSeleccionadaData" class="border-green-200 bg-green-50">
                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <FileText class="h-5 w-5 text-green-600" />
                            <CardTitle class="text-base text-green-800">
                                Perfil seleccionado
                            </CardTitle>
                        </div>
                        <Badge variant="secondary" class="bg-green-100 text-green-800">
                            Aprobado
                        </Badge>
                    </div>
                    <CardDescription class="text-green-700">
                        Se vinculará tu perfil de candidatura a esta postulación, preservando una copia de todos tus datos actuales.
                    </CardDescription>
                </CardHeader>
                <CardContent class="pt-0">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Versión:</span>
                            <span class="font-medium">v{{ candidaturaSeleccionadaData.version }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Fecha aprobación:</span>
                            <span class="font-medium">{{ candidaturaSeleccionadaData.fecha_aprobacion }}</span>
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