<script setup lang="ts">
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Textarea } from "@modules/Core/Resources/js/components/ui/textarea";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { Switch } from "@modules/Core/Resources/js/components/ui/switch";
import { Alert, AlertDescription } from "@modules/Core/Resources/js/components/ui/alert";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from "@modules/Core/Resources/js/components/ui/select";
import { Save, X, Plus, Trash2, AlertCircle } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import { toast } from 'vue-sonner';

// Interfaces
interface CampoPersonalizado {
    id: number;
    nombre: string;
    slug: string;
    tipo: string;
    opciones?: Array<{value: string; label: string}>;
    es_requerido: boolean;
    orden: number;
    activo: boolean;
    descripcion?: string;
    placeholder?: string;
    validacion?: string;
}

interface Props {
    campo?: CampoPersonalizado;
    tipos: Record<string, string>;
}

const props = defineProps<Props>();

// Determinar si es edición o creación
const isEdit = computed(() => !!props.campo);

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Proyectos', href: '/admin/proyectos' },
    { title: 'Campos Personalizados', href: '/admin/campos-personalizados' },
    { title: isEdit.value ? `Editar: ${props.campo?.nombre}` : 'Nuevo Campo', href: '#' },
];

// Formulario
const form = useForm({
    nombre: props.campo?.nombre || '',
    slug: props.campo?.slug || '',
    tipo: props.campo?.tipo || 'text',
    opciones: props.campo?.opciones || [],
    es_requerido: props.campo?.es_requerido !== undefined ? props.campo.es_requerido : false,
    activo: props.campo?.activo !== undefined ? props.campo.activo : true,
    descripcion: props.campo?.descripcion || '',
    placeholder: props.campo?.placeholder || '',
    validacion: props.campo?.validacion || '',
});

// Estado de procesamiento
const processing = ref(false);

// Generar slug automáticamente del nombre
watch(() => form.nombre, (newNombre) => {
    if (!isEdit.value && newNombre) {
        form.slug = newNombre
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }
});

// Determinar si el tipo requiere opciones
const requiresOptions = computed(() => {
    return ['select', 'radio'].includes(form.tipo);
});

// Agregar nueva opción
const addOption = () => {
    form.opciones.push({ value: '', label: '' });
};

// Eliminar opción
const removeOption = (index: number) => {
    form.opciones.splice(index, 1);
};

// Limpiar opciones cuando cambie el tipo
watch(() => form.tipo, (newTipo, oldTipo) => {
    if (!requiresOptions.value && form.opciones.length > 0) {
        form.opciones = [];
    } else if (requiresOptions.value && form.opciones.length === 0) {
        // Agregar al menos una opción vacía
        addOption();
    }
});

// Función para guardar
const submit = () => {
    processing.value = true;

    const url = isEdit.value
        ? `/admin/campos-personalizados/${props.campo?.id}`
        : '/admin/campos-personalizados';

    const method = isEdit.value ? 'put' : 'post';

    form[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(isEdit.value ? 'Campo actualizado exitosamente' : 'Campo creado exitosamente');
        },
        onError: (errors) => {
            toast.error('Error al guardar el campo');
            console.error(errors);
        },
        onFinish: () => {
            processing.value = false;
        }
    });
};

// Función para cancelar
const cancelar = () => {
    router.visit('/admin/campos-personalizados');
};

// Ejemplos de validación según tipo
const getValidacionEjemplo = (tipo: string) => {
    const ejemplos: Record<string, string> = {
        'text': 'min:3|max:255|alpha_dash',
        'number': 'numeric|min:0|max:1000',
        'date': 'date|after:today|before:2030-12-31',
        'textarea': 'min:10|max:5000',
        'file': 'mimes:pdf,doc,docx|max:10240',
    };
    return ejemplos[tipo] || '';
};
</script>

<template>
    <Head :title="isEdit ? 'Editar Campo Personalizado' : 'Nuevo Campo Personalizado'" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ isEdit ? 'Editar Campo Personalizado' : 'Nuevo Campo Personalizado' }}
                </h1>
            </div>

            <!-- Alerta informativa -->
            <Alert>
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>
                    Los campos personalizados permiten agregar información adicional a los proyectos.
                    Una vez creado, el campo estará disponible en todos los formularios de proyectos.
                </AlertDescription>
            </Alert>

            <!-- Formulario -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Información básica -->
                <Card>
                    <CardHeader>
                        <CardTitle>Información del Campo</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Nombre -->
                        <div>
                            <Label for="nombre">Nombre del Campo *</Label>
                            <Input
                                id="nombre"
                                v-model="form.nombre"
                                type="text"
                                placeholder="Ej: Presupuesto estimado"
                                :class="{ 'border-red-500': form.errors.nombre }"
                                required
                            />
                            <p v-if="form.errors.nombre" class="mt-1 text-sm text-red-600">
                                {{ form.errors.nombre }}
                            </p>
                        </div>

                        <!-- Slug -->
                        <div>
                            <Label for="slug">Identificador (slug) *</Label>
                            <Input
                                id="slug"
                                v-model="form.slug"
                                type="text"
                                placeholder="presupuesto_estimado"
                                :class="{ 'border-red-500': form.errors.slug }"
                                :disabled="isEdit"
                                required
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Identificador único del campo. Solo letras minúsculas, números y guiones bajos.
                            </p>
                            <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600">
                                {{ form.errors.slug }}
                            </p>
                        </div>

                        <!-- Descripción -->
                        <div>
                            <Label for="descripcion">Descripción</Label>
                            <Textarea
                                id="descripcion"
                                v-model="form.descripcion"
                                placeholder="Texto de ayuda para el usuario"
                                rows="2"
                                :class="{ 'border-red-500': form.errors.descripcion }"
                            />
                            <p v-if="form.errors.descripcion" class="mt-1 text-sm text-red-600">
                                {{ form.errors.descripcion }}
                            </p>
                        </div>

                        <!-- Placeholder -->
                        <div>
                            <Label for="placeholder">Texto de Placeholder</Label>
                            <Input
                                id="placeholder"
                                v-model="form.placeholder"
                                type="text"
                                placeholder="Texto que aparece cuando el campo está vacío"
                                :class="{ 'border-red-500': form.errors.placeholder }"
                            />
                            <p v-if="form.errors.placeholder" class="mt-1 text-sm text-red-600">
                                {{ form.errors.placeholder }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Configuración del campo -->
                <Card>
                    <CardHeader>
                        <CardTitle>Configuración</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Tipo -->
                        <div>
                            <Label for="tipo">Tipo de Campo *</Label>
                            <Select v-model="form.tipo" :disabled="isEdit">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccione el tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="(label, key) in tipos" :key="key" :value="key">
                                        {{ label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="isEdit" class="mt-1 text-xs text-gray-500">
                                El tipo no puede ser modificado después de crear el campo.
                            </p>
                            <p v-if="form.errors.tipo" class="mt-1 text-sm text-red-600">
                                {{ form.errors.tipo }}
                            </p>
                        </div>

                        <!-- Opciones (para select y radio) -->
                        <div v-if="requiresOptions">
                            <Label>Opciones *</Label>
                            <div class="space-y-2">
                                <div
                                    v-for="(opcion, index) in form.opciones"
                                    :key="index"
                                    class="flex gap-2"
                                >
                                    <Input
                                        v-model="opcion.value"
                                        placeholder="Valor"
                                        class="w-1/3"
                                        required
                                    />
                                    <Input
                                        v-model="opcion.label"
                                        placeholder="Etiqueta"
                                        class="flex-1"
                                        required
                                    />
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        size="sm"
                                        @click="removeOption(index)"
                                        :disabled="form.opciones.length === 1"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="addOption"
                                >
                                    <Plus class="mr-2 h-4 w-4" />
                                    Agregar Opción
                                </Button>
                            </div>
                            <p v-if="form.errors.opciones" class="mt-1 text-sm text-red-600">
                                {{ form.errors.opciones }}
                            </p>
                        </div>

                        <!-- Validación personalizada -->
                        <div>
                            <Label for="validacion">Reglas de Validación (Laravel)</Label>
                            <Input
                                id="validacion"
                                v-model="form.validacion"
                                type="text"
                                :placeholder="getValidacionEjemplo(form.tipo)"
                                :class="{ 'border-red-500': form.errors.validacion }"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Opcional. Usa sintaxis de Laravel. Ej: min:3|max:255
                            </p>
                            <p v-if="form.errors.validacion" class="mt-1 text-sm text-red-600">
                                {{ form.errors.validacion }}
                            </p>
                        </div>

                        <!-- Switches de configuración -->
                        <div class="space-y-4">
                            <!-- Requerido -->
                            <div class="flex items-center justify-between">
                                <div class="space-y-0.5">
                                    <Label for="es_requerido">Campo Requerido</Label>
                                    <p class="text-xs text-gray-500">
                                        Los usuarios deberán completar este campo obligatoriamente
                                    </p>
                                </div>
                                <Switch
                                    id="es_requerido"
                                    v-model="form.es_requerido"
                                />
                            </div>

                            <!-- Activo -->
                            <div class="flex items-center justify-between">
                                <div class="space-y-0.5">
                                    <Label for="activo">Campo Activo</Label>
                                    <p class="text-xs text-gray-500">
                                        Solo los campos activos se muestran en los formularios
                                    </p>
                                </div>
                                <Switch
                                    id="activo"
                                    v-model="form.activo"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Botones de acción -->
                <Card>
                    <CardContent class="flex justify-end gap-4 pt-6">
                        <Button
                            type="button"
                            variant="outline"
                            @click="cancelar"
                            :disabled="processing"
                        >
                            <X class="mr-2 h-4 w-4" />
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            :disabled="processing || form.processing"
                        >
                            <Save class="mr-2 h-4 w-4" />
                            {{ processing ? 'Guardando...' : (isEdit ? 'Actualizar Campo' : 'Crear Campo') }}
                        </Button>
                    </CardContent>
                </Card>
            </form>
        </div>
    </AdminLayout>
</template>