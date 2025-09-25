<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Textarea } from '@modules/Core/Resources/js/components/ui/textarea';
import { Switch } from '@modules/Core/Resources/js/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@modules/Core/Resources/js/components/ui/select';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import { Separator } from '@modules/Core/Resources/js/components/ui/separator';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import {
    Save,
    X,
    Plus,
    Trash2,
    Info,
    AlertCircle,
    FileText,
    Hash,
    Calendar,
    AlignLeft,
    List,
    CheckSquare,
    Circle,
    Paperclip
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import type { CampoPersonalizadoContrato } from '@modules/Proyectos/Resources/js/types/contratos';

// Props
const props = defineProps<{
    campo?: CampoPersonalizadoContrato;
    isEdit?: boolean;
    authPermissions: string[];
}>();

// Breadcrumbs para navegación
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Contratos', href: '/admin/contratos' },
    { title: 'Campos Personalizados', href: '/admin/contratos/campos-personalizados' },
    { title: props.campo ? 'Editar Campo' : 'Crear Campo',
      href: props.campo ? `/admin/contratos/campos-personalizados/${props.campo.id}/edit` : '/admin/contratos/campos-personalizados/create' },
];

// Formulario
const form = useForm({
    nombre: props.campo?.nombre || '',
    slug: props.campo?.slug || '',
    tipo: props.campo?.tipo || 'text',
    descripcion: props.campo?.descripcion || '',
    placeholder: props.campo?.placeholder || '',
    es_requerido: props.campo?.es_requerido || false,
    activo: props.campo?.activo !== undefined ? props.campo.activo : true,
    orden: props.campo?.orden || 0,
    validacion: props.campo?.validacion || '',
    opciones: props.campo?.opciones || []
});

// Estado
const nuevaOpcion = ref('');
const mostrarAyudaTipo = ref(false);

// Computed
const tiposDisponibles = computed(() => [
    { value: 'text', label: 'Texto', icon: FileText, descripcion: 'Campo de texto simple' },
    { value: 'number', label: 'Número', icon: Hash, descripcion: 'Campo numérico' },
    { value: 'date', label: 'Fecha', icon: Calendar, descripcion: 'Selector de fecha' },
    { value: 'textarea', label: 'Texto largo', icon: AlignLeft, descripcion: 'Área de texto múltiples líneas' },
    { value: 'select', label: 'Selección', icon: List, descripcion: 'Lista desplegable de opciones' },
    { value: 'checkbox', label: 'Casillas', icon: CheckSquare, descripcion: 'Múltiple selección' },
    { value: 'radio', label: 'Radio', icon: Circle, descripcion: 'Selección única' },
    { value: 'file', label: 'Archivo', icon: Paperclip, descripcion: 'Carga de archivos' }
]);

const necesitaOpciones = computed(() =>
    ['select', 'checkbox', 'radio'].includes(form.tipo)
);

const tipoSeleccionado = computed(() =>
    tiposDisponibles.value.find(t => t.value === form.tipo)
);

const canManageFields = computed(() =>
    props.authPermissions.includes('contratos.manage_fields')
);

// Watchers
watch(() => form.nombre, (nuevoNombre) => {
    if (!props.isEdit && nuevoNombre) {
        // Generar slug automáticamente solo en creación
        form.slug = nuevoNombre
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_|_$/g, '');
    }
});

// Métodos
const agregarOpcion = () => {
    if (nuevaOpcion.value.trim()) {
        if (!form.opciones) {
            form.opciones = [];
        }
        form.opciones.push(nuevaOpcion.value.trim());
        nuevaOpcion.value = '';
    }
};

const eliminarOpcion = (index: number) => {
    form.opciones.splice(index, 1);
};

const moverOpcion = (index: number, direccion: 'arriba' | 'abajo') => {
    const newIndex = direccion === 'arriba' ? index - 1 : index + 1;
    if (newIndex >= 0 && newIndex < form.opciones.length) {
        const temp = form.opciones[index];
        form.opciones[index] = form.opciones[newIndex];
        form.opciones[newIndex] = temp;
    }
};

const submit = () => {
    if (props.isEdit) {
        form.put(route('admin.campos-personalizados-contrato.update', props.campo?.id), {
            onSuccess: () => {
                toast.success('Campo actualizado correctamente');
            },
            onError: (errors) => {
                toast.error('Error al actualizar el campo');
            }
        });
    } else {
        form.post(route('admin.campos-personalizados-contrato.store'), {
            onSuccess: () => {
                toast.success('Campo creado correctamente');
            },
            onError: (errors) => {
                toast.error('Error al crear el campo');
            }
        });
    }
};

// Layout
defineOptions({
    layout: AdminLayout
});
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold tracking-tight">
                    {{ isEdit ? 'Editar' : 'Crear' }} Campo Personalizado
                </h2>
                <p class="text-muted-foreground mt-2">
                    Configura un campo adicional para los contratos
                </p>
            </div>
            <Link :href="route('admin.campos-personalizados-contrato.index')">
                <Button variant="outline">
                    <X class="w-4 h-4 mr-2" />
                    Cancelar
                </Button>
            </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Información básica -->
            <Card>
                <CardHeader>
                    <CardTitle>Información Básica</CardTitle>
                    <CardDescription>
                        Define el nombre y tipo del campo personalizado
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Nombre -->
                    <div class="space-y-2">
                        <Label for="nombre" required>Nombre del Campo</Label>
                        <Input
                            v-model="form.nombre"
                            id="nombre"
                            placeholder="Ej: Número de Referencia"
                            :disabled="!canManageFields"
                        />
                        <p v-if="form.errors.nombre" class="text-sm text-destructive">
                            {{ form.errors.nombre }}
                        </p>
                    </div>

                    <!-- Slug -->
                    <div class="space-y-2">
                        <Label for="slug" required>Identificador (Slug)</Label>
                        <Input
                            v-model="form.slug"
                            id="slug"
                            placeholder="numero_referencia"
                            pattern="[a-z0-9_]+"
                            :disabled="isEdit || !canManageFields"
                        />
                        <p class="text-xs text-muted-foreground">
                            Identificador único del campo. Solo minúsculas, números y guiones bajos.
                        </p>
                        <p v-if="form.errors.slug" class="text-sm text-destructive">
                            {{ form.errors.slug }}
                        </p>
                    </div>

                    <!-- Tipo -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <Label for="tipo" required>Tipo de Campo</Label>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="mostrarAyudaTipo = !mostrarAyudaTipo"
                            >
                                <Info class="w-4 h-4" />
                            </Button>
                        </div>
                        <Select v-model="form.tipo" :disabled="isEdit || !canManageFields">
                            <SelectTrigger>
                                <SelectValue>
                                    <div v-if="tipoSeleccionado" class="flex items-center gap-2">
                                        <component :is="tipoSeleccionado.icon" class="w-4 h-4" />
                                        {{ tipoSeleccionado.label }}
                                    </div>
                                </SelectValue>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="tipo in tiposDisponibles"
                                    :key="tipo.value"
                                    :value="tipo.value"
                                >
                                    <div class="flex items-center gap-2">
                                        <component :is="tipo.icon" class="w-4 h-4" />
                                        <div>
                                            <div>{{ tipo.label }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ tipo.descripcion }}
                                            </div>
                                        </div>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.tipo" class="text-sm text-destructive">
                            {{ form.errors.tipo }}
                        </p>
                    </div>

                    <!-- Ayuda sobre tipos -->
                    <Alert v-if="mostrarAyudaTipo">
                        <Info class="h-4 w-4" />
                        <AlertDescription>
                            <ul class="space-y-1 mt-2">
                                <li v-for="tipo in tiposDisponibles" :key="tipo.value">
                                    <strong>{{ tipo.label }}:</strong> {{ tipo.descripcion }}
                                </li>
                            </ul>
                        </AlertDescription>
                    </Alert>

                    <!-- Descripción -->
                    <div class="space-y-2">
                        <Label for="descripcion">Descripción</Label>
                        <Textarea
                            v-model="form.descripcion"
                            id="descripcion"
                            placeholder="Descripción del campo para ayudar a los usuarios"
                            rows="3"
                            :disabled="!canManageFields"
                        />
                        <p v-if="form.errors.descripcion" class="text-sm text-destructive">
                            {{ form.errors.descripcion }}
                        </p>
                    </div>

                    <!-- Placeholder -->
                    <div class="space-y-2">
                        <Label for="placeholder">Texto de Ayuda (Placeholder)</Label>
                        <Input
                            v-model="form.placeholder"
                            id="placeholder"
                            placeholder="Ej: Ingrese el número de referencia"
                            :disabled="!canManageFields"
                        />
                        <p v-if="form.errors.placeholder" class="text-sm text-destructive">
                            {{ form.errors.placeholder }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Opciones (para select, checkbox, radio) -->
            <Card v-if="necesitaOpciones">
                <CardHeader>
                    <CardTitle>Opciones</CardTitle>
                    <CardDescription>
                        Define las opciones disponibles para este campo
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Agregar opción -->
                    <div class="flex gap-2">
                        <Input
                            v-model="nuevaOpcion"
                            placeholder="Nueva opción"
                            @keyup.enter="agregarOpcion"
                            :disabled="!canManageFields"
                        />
                        <Button
                            type="button"
                            @click="agregarOpcion"
                            :disabled="!canManageFields"
                        >
                            <Plus class="w-4 h-4 mr-2" />
                            Agregar
                        </Button>
                    </div>

                    <!-- Lista de opciones -->
                    <div v-if="form.opciones?.length > 0" class="space-y-2">
                        <div
                            v-for="(opcion, index) in form.opciones"
                            :key="index"
                            class="flex items-center gap-2 p-2 bg-muted rounded-lg"
                        >
                            <span class="flex-1">{{ opcion }}</span>
                            <div class="flex gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="moverOpcion(index, 'arriba')"
                                    :disabled="index === 0 || !canManageFields"
                                    class="h-8 w-8 p-0"
                                >
                                    ↑
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="moverOpcion(index, 'abajo')"
                                    :disabled="index === form.opciones.length - 1 || !canManageFields"
                                    class="h-8 w-8 p-0"
                                >
                                    ↓
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="eliminarOpcion(index)"
                                    :disabled="!canManageFields"
                                    class="text-destructive h-8 w-8 p-0"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-muted-foreground">
                        No hay opciones configuradas
                    </div>
                </CardContent>
            </Card>

            <!-- Configuración avanzada -->
            <Card>
                <CardHeader>
                    <CardTitle>Configuración Avanzada</CardTitle>
                    <CardDescription>
                        Opciones adicionales de validación y comportamiento
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Requerido -->
                    <div class="flex items-center justify-between">
                        <div class="space-y-0.5">
                            <Label>Campo Requerido</Label>
                            <p class="text-sm text-muted-foreground">
                                El campo debe ser completado obligatoriamente
                            </p>
                        </div>
                        <Switch
                            v-model:checked="form.es_requerido"
                            :disabled="!canManageFields"
                        />
                    </div>

                    <Separator />

                    <!-- Activo -->
                    <div class="flex items-center justify-between">
                        <div class="space-y-0.5">
                            <Label>Campo Activo</Label>
                            <p class="text-sm text-muted-foreground">
                                El campo será visible en los formularios
                            </p>
                        </div>
                        <Switch
                            v-model:checked="form.activo"
                            :disabled="!canManageFields"
                        />
                    </div>

                    <Separator />

                    <!-- Orden -->
                    <div class="space-y-2">
                        <Label for="orden">Orden de Aparición</Label>
                        <Input
                            v-model.number="form.orden"
                            id="orden"
                            type="number"
                            min="0"
                            placeholder="0"
                            :disabled="!canManageFields"
                        />
                        <p class="text-xs text-muted-foreground">
                            Los campos se ordenan de menor a mayor. Campos con el mismo orden se muestran alfabéticamente.
                        </p>
                    </div>

                    <!-- Validación personalizada -->
                    <div class="space-y-2">
                        <Label for="validacion">Reglas de Validación (Avanzado)</Label>
                        <Input
                            v-model="form.validacion"
                            id="validacion"
                            placeholder="min:10|max:100"
                            :disabled="!canManageFields"
                        />
                        <p class="text-xs text-muted-foreground">
                            Reglas de validación Laravel. Ej: min:5|max:100|regex:/^[A-Z]/
                        </p>
                        <p v-if="form.errors.validacion" class="text-sm text-destructive">
                            {{ form.errors.validacion }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Acciones -->
            <div class="flex justify-end gap-2">
                <Link :href="route('admin.campos-personalizados-contrato.index')">
                    <Button type="button" variant="outline">
                        Cancelar
                    </Button>
                </Link>
                <Button
                    type="submit"
                    :disabled="form.processing || !canManageFields"
                >
                    <Save class="w-4 h-4 mr-2" />
                    {{ isEdit ? 'Actualizar' : 'Crear' }} Campo
                </Button>
            </div>
        </form>
        </div>
    </AdminLayout>
</template>