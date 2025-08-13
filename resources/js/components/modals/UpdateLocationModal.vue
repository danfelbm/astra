<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import GeographicSelector from '@/components/forms/GeographicSelector.vue';
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { MapPin, Phone, AlertCircle } from 'lucide-vue-next';

interface Props {
    open: boolean;
}

const props = defineProps<Props>();

// Obtener datos del usuario actual
const page = usePage();
const user = computed(() => page.props.auth.user);

// Estado del formulario
const loading = ref(false);
const formData = ref({
    territorio_id: user.value?.territorio_id || undefined,
    departamento_id: user.value?.departamento_id || undefined,
    municipio_id: user.value?.municipio_id || undefined,
    localidad_id: user.value?.localidad_id || undefined,
    telefono: user.value?.telefono || '',
});

// Datos geográficos para el componente selector
const geographicData = ref({
    territorio_id: formData.value.territorio_id,
    departamento_id: formData.value.departamento_id,
    municipio_id: formData.value.municipio_id,
    localidad_id: formData.value.localidad_id,
});

// Manejar cambios en la selección geográfica
const handleGeographicChange = (value: any) => {
    geographicData.value = value;
    formData.value.territorio_id = value.territorio_id || undefined;
    formData.value.departamento_id = value.departamento_id || undefined;
    formData.value.municipio_id = value.municipio_id || undefined;
    formData.value.localidad_id = value.localidad_id || undefined;
};

// Validar si el formulario está completo (localidad es opcional)
const isFormValid = computed(() => {
    return !!(
        formData.value.territorio_id &&
        formData.value.departamento_id &&
        formData.value.municipio_id &&
        formData.value.telefono && 
        formData.value.telefono.trim().length >= 7
    );
});

// Guardar la información de ubicación
const saveLocation = async () => {
    if (!isFormValid.value) {
        toast.error('Por favor completa todos los campos requeridos');
        return;
    }

    loading.value = true;

    try {
        // Usar router.patch para actualizar los datos del usuario
        router.patch(route('profile.location.update'), formData.value, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success('Tu información de ubicación ha sido actualizada correctamente');
                // El modal se cerrará automáticamente cuando los datos se actualicen
            },
            onError: (errors) => {
                console.error('Errores:', errors);
                toast.error('Ocurrió un error al actualizar tu información');
            },
            onFinish: () => {
                loading.value = false;
            },
        });
    } catch (error) {
        console.error('Error al guardar ubicación:', error);
        toast.error('Ocurrió un error inesperado');
        loading.value = false;
    }
};

// Formatear teléfono mientras se escribe
const formatPhone = (event: Event) => {
    const input = event.target as HTMLInputElement;
    // Eliminar todo lo que no sea dígito
    let value = input.value.replace(/\D/g, '');
    
    // Limitar a 10 dígitos para Colombia
    if (value.length > 10) {
        value = value.slice(0, 10);
    }
    
    formData.value.telefono = value;
};
</script>

<template>
    <Dialog :open="open" :modal="true">
        <DialogContent 
            class="sm:max-w-[600px]" 
            :closeable="false"
            @escape-key-down.prevent
            @pointer-down-outside.prevent
            @interact-outside.prevent
        >
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-xl">
                    <AlertCircle class="h-5 w-5 text-orange-500" />
                    Completa tu información de ubicación
                </DialogTitle>
                <DialogDescription class="mt-2">
                    Para continuar usando el sistema, necesitamos que completes tu información de ubicación y contacto. 
                    Esta información es importante para garantizar el correcto funcionamiento del sistema de votaciones.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <!-- Información importante -->
                <Card class="border-orange-200 bg-orange-50">
                    <CardContent class="pt-4">
                        <p class="text-sm text-orange-800">
                            <strong>Campos requeridos:</strong> Territorio, Departamento, Municipio y Teléfono.
                            <br />
                            <span class="text-xs">La localidad es opcional.</span>
                        </p>
                    </CardContent>
                </Card>

                <!-- Selector de ubicación geográfica -->
                <div class="space-y-2">
                    <div class="flex items-center gap-2 mb-2">
                        <MapPin class="h-4 w-4 text-muted-foreground" />
                        <Label class="text-base font-medium">Ubicación Geográfica</Label>
                    </div>
                    <GeographicSelector
                        :model-value="geographicData"
                        @update:model-value="handleGeographicChange"
                        mode="single"
                        :show-card="false"
                        title=""
                        description=""
                    />
                </div>

                <!-- Campo de teléfono -->
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <Phone class="h-4 w-4 text-muted-foreground" />
                        <Label for="telefono">Teléfono de contacto *</Label>
                    </div>
                    <Input
                        id="telefono"
                        v-model="formData.telefono"
                        type="tel"
                        placeholder="3001234567"
                        maxlength="10"
                        @input="formatPhone"
                        :disabled="loading"
                        class="font-mono"
                    />
                    <p class="text-xs text-muted-foreground">
                        Ingresa tu número de teléfono sin espacios ni guiones
                    </p>
                </div>
            </div>

            <DialogFooter>
                <div class="flex items-center justify-between w-full">
                    <p class="text-xs text-muted-foreground">
                        * Campos obligatorios
                    </p>
                    <Button 
                        @click="saveLocation" 
                        :disabled="!isFormValid || loading"
                        class="min-w-[120px]"
                    >
                        <template v-if="loading">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Guardando...
                            </span>
                        </template>
                        <template v-else>
                            Guardar y continuar
                        </template>
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>