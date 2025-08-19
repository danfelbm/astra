<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SPhoneInput } from '@/components/ui/phone-input';
import GeographicSelector from '@/components/forms/GeographicSelector.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, ref, watch, onMounted } from 'vue';

// Definir tipos
interface TipoDocumento {
    value: string;
    label: string;
}

// Opciones de tipo de documento
const tiposDocumento: TipoDocumento[] = [
    { value: 'CC', label: 'CC (Cédula de Ciudadanía)' },
    { value: 'TI', label: 'TI (Tarjeta de Identidad)' },
    { value: 'CE', label: 'CE (Cédula de Extranjería)' },
    { value: 'PA', label: 'PA (Pasaporte)' },
];

// Estado para los datos geográficos
const territorios = ref([]);
const departamentos = ref([]);

const form = useForm({
    name: '',
    email: '',
    tipo_documento: 'CC',
    documento_identidad: '',
    telefono: '',
    territorio_id: null as number | null,
    departamento_id: null as number | null,
    municipio_id: null as number | null,
    localidad_id: null as number | null,
});

// Computed para validaciones dinámicas
const documentoPattern = computed(() => {
    if (form.tipo_documento === 'PA') {
        return /^[A-Z0-9]+$/i; // Alfanumérico para pasaporte
    }
    return /^[0-9]+$/; // Solo números para otros tipos
});

// Computed para el inputmode del documento
const documentoInputMode = computed(() => {
    return form.tipo_documento === 'PA' ? 'text' : 'numeric';
});

// Validación del documento en tiempo real
const documentoError = ref('');
watch(() => form.documento_identidad, (value) => {
    if (value && !documentoPattern.value.test(value)) {
        if (form.tipo_documento === 'PA') {
            documentoError.value = 'El pasaporte debe contener solo letras y números';
        } else {
            documentoError.value = 'El documento debe contener solo números';
        }
    } else {
        documentoError.value = '';
    }
});

// Estado para territorio internacional
const isInternational = computed(() => form.territorio_id === 2);

// Manejar cambio de ubicación geográfica
const handleGeographicChange = (value: any) => {
    form.territorio_id = value.territorio_id || null;
    
    // Si es territorio internacional (id 2), asumir departamento_id = 35
    if (form.territorio_id === 2) {
        form.departamento_id = 35;
        form.localidad_id = null; // No mostrar localidad para internacional
    } else {
        form.departamento_id = value.departamento_id || null;
        form.localidad_id = value.localidad_id || null;
    }
    
    form.municipio_id = value.municipio_id || null;
};

// Cargar datos geográficos al montar
onMounted(async () => {
    try {
        // Cargar territorios (usar ruta pública sin auth)
        const territoriosResponse = await fetch('/api/public/geographic/territorios');
        const territoriosData = await territoriosResponse.json();
        territorios.value = territoriosData;
    } catch (error) {
        console.error('Error cargando datos geográficos:', error);
    }
});

const submit = () => {
    // Limpiar errores personalizados antes de enviar
    documentoError.value = '';
    
    form.post(route('register'));
};
</script>

<template>
    <AuthBase title="Crear una cuenta" description="Ingresa tus datos para crear tu cuenta">
        <Head title="Registro" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <!-- Nombre -->
                <div class="grid gap-2">
                    <Label for="name">Nombre completo</Label>
                    <Input 
                        id="name" 
                        type="text" 
                        required 
                        autofocus 
                        tabindex="1" 
                        autocomplete="name" 
                        v-model="form.name" 
                        placeholder="Nombre completo" 
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <!-- Email -->
                <div class="grid gap-2">
                    <Label for="email">Correo electrónico</Label>
                    <Input 
                        id="email" 
                        type="email" 
                        required 
                        tabindex="2" 
                        autocomplete="email" 
                        v-model="form.email" 
                        placeholder="correo@ejemplo.com" 
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <!-- Número de documento -->
                <div class="grid gap-2">
                    <Label for="documento_identidad">Número de documento</Label>
                    <div class="flex items-center">
                        <Select v-model="form.tipo_documento" required>
                            <SelectTrigger id="tipo_documento" tabindex="3" class="rounded-e-none rounded-s-lg w-18">
                                <SelectValue placeholder="CC" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem 
                                    v-for="tipo in tiposDocumento" 
                                    :key="tipo.value" 
                                    :value="tipo.value"
                                >
                                    {{ tipo.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Input 
                            id="documento_identidad" 
                            class="rounded-s-none rounded-e-lg"
                            :type="documentoInputMode === 'numeric' ? 'text' : 'text'"
                            :inputmode="documentoInputMode"
                            required 
                            tabindex="4" 
                            autocomplete="off" 
                            v-model="form.documento_identidad" 
                            placeholder="Número de documento" 
                        />
                    </div>
                    <InputError :message="documentoError || form.errors.documento_identidad || form.errors.tipo_documento" />
                </div>

                <!-- Teléfono -->
                <div class="grid gap-2">
                    <Label for="telefono">Teléfono</Label>
                    <SPhoneInput 
                        v-model="form.telefono"
                    />
                    <InputError :message="form.errors.telefono" />
                </div>

                <!-- Ubicación Geográfica -->
                <div class="grid gap-2">
                    <GeographicSelector
                        :modelValue="{
                            territorio_id: form.territorio_id,
                            departamento_id: form.departamento_id,
                            municipio_id: form.municipio_id,
                            localidad_id: form.localidad_id
                        }"
                        @update:modelValue="handleGeographicChange"
                        mode="single"
                        :showCard="false"
                        description="Selecciona tu ubicación"
                        :municipioLabel="isInternational ? 'País' : 'Municipio'"
                        :showLocalidad="!isInternational"
                        :showDepartamento="!isInternational"
                    />
                    <InputError :message="form.errors.territorio_id || form.errors.departamento_id || form.errors.municipio_id || form.errors.localidad_id" />
                </div>


                <!-- Botón de Submit -->
                <Button type="submit" class="mt-2 w-full" tabindex="6" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Crear cuenta
                </Button>
            </div>

            <!-- Link para login -->
            <div class="text-center text-sm text-muted-foreground">
                ¿Ya tienes una cuenta?
                <TextLink :href="route('login')" class="underline underline-offset-4" tabindex="7">Iniciar sesión</TextLink>
            </div>
        </form>
    </AuthBase>
</template>