<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Folder, Briefcase } from 'lucide-vue-next';
import { computed } from 'vue';

interface CargoPadre {
    id: number;
    nombre: string;
    es_cargo: boolean;
}

interface Cargo {
    id: number;
    nombre: string;
    descripcion?: string;
    parent_id?: number;
    parent?: {
        id: number;
        nombre: string;
    };
    es_cargo: boolean;
    activo: boolean;
}

interface Props {
    cargo?: Cargo | null;
    cargosPadre: CargoPadre[];
}

const props = defineProps<Props>();

const isEditing = computed(() => !!props.cargo);

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Cargos', href: '/admin/cargos' },
    { title: isEditing.value ? 'Editar' : 'Crear', href: '#' },
];

// Función para inicializar datos del formulario
const getInitialFormData = () => {
    if (!props.cargo) {
        return {
            nombre: '',
            descripcion: '',
            parent_id: 'null',
            es_cargo: true,
            activo: true,
        };
    }

    const cargo = props.cargo;
    
    return {
        nombre: cargo.nombre || '',
        descripcion: cargo.descripcion || '',
        parent_id: cargo.parent_id ? String(cargo.parent_id) : 'null',
        es_cargo: cargo.es_cargo,
        activo: cargo.activo,
    };
};

// Formulario principal
const form = useForm(getInitialFormData());

// Enviar formulario
const submit = () => {
    // Transformar parent_id antes de enviar
    const formData = {
        nombre: form.nombre,
        descripcion: form.descripcion,
        parent_id: form.parent_id && form.parent_id !== 'null' ? parseInt(form.parent_id) : null,
        es_cargo: form.es_cargo,
        activo: form.activo,
    };

    if (isEditing.value) {
        form.transform(() => formData).put(`/admin/cargos/${props.cargo!.id}`, {
            onError: (errors) => {
                // Los errores se muestran automáticamente en los campos
                console.error('Errores de validación:', errors);
            }
        });
    } else {
        form.transform(() => formData).post('/admin/cargos', {
            onError: (errors) => {
                // Los errores se muestran automáticamente en los campos
                console.error('Errores de validación:', errors);
            }
        });
    }
};

// Obtener icono para tipo de cargo padre
const getTipoIcon = (esCargo: boolean) => {
    return esCargo ? Briefcase : Folder;
};

// Validación básica del formulario
const isFormValid = computed(() => {
    return form.nombre.trim().length > 0;
});
</script>

<template>
    <Head :title="isEditing ? 'Editar Cargo' : 'Nuevo Cargo'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">
                        {{ isEditing ? 'Editar Cargo' : 'Nuevo Cargo' }}
                    </h1>
                    <p class="text-muted-foreground">
                        {{ isEditing ? 'Modifica los datos del cargo' : 'Crea un nuevo cargo en la estructura jerárquica' }}
                    </p>
                </div>
                <Button variant="outline" @click="$inertia.visit('/admin/cargos')">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver
                </Button>
            </div>

            <!-- Formulario -->
            <Card>
                <CardHeader>
                    <CardTitle>
                        {{ isEditing ? 'Datos del Cargo' : 'Información del Nuevo Cargo' }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <form @submit.prevent="submit">
                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="space-y-4">
                                <!-- Nombre -->
                                <div>
                                    <Label for="nombre">Nombre del Cargo *</Label>
                                    <Input
                                        id="nombre"
                                        v-model="form.nombre"
                                        placeholder="Ej: Presidente, Senador, Alcalde..."
                                        :class="{ 'border-destructive': form.errors.nombre }"
                                    />
                                    <p v-if="form.errors.nombre" class="text-sm text-destructive mt-1">
                                        {{ form.errors.nombre }}
                                    </p>
                                </div>

                                <!-- Descripción -->
                                <div>
                                    <Label for="descripcion">Descripción (opcional)</Label>
                                    <Textarea
                                        id="descripcion"
                                        v-model="form.descripcion"
                                        placeholder="Descripción detallada del cargo..."
                                        rows="4"
                                        :class="{ 'border-destructive': form.errors.descripcion }"
                                    />
                                    <p v-if="form.errors.descripcion" class="text-sm text-destructive mt-1">
                                        {{ form.errors.descripcion }}
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <!-- Cargo Padre -->
                                <div>
                                    <Label for="parent_id">Cargo Padre (opcional)</Label>
                                    <Select v-model="form.parent_id">
                                        <SelectTrigger :class="{ 'border-destructive': form.errors.parent_id }">
                                            <SelectValue placeholder="Seleccionar cargo padre..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="null">Sin cargo padre (raíz)</SelectItem>
                                            <SelectItem
                                                v-for="cargoPadre in cargosPadre"
                                                :key="cargoPadre.id"
                                                :value="cargoPadre.id.toString()"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <component :is="getTipoIcon(cargoPadre.es_cargo)" class="h-3 w-3" />
                                                    {{ cargoPadre.nombre }}
                                                </div>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.parent_id" class="text-sm text-destructive mt-1">
                                        {{ form.errors.parent_id }}
                                    </p>
                                    <p class="text-xs text-muted-foreground mt-1">
                                        Define la jerarquía organizacional. Un cargo sin padre es de nivel raíz.
                                    </p>
                                </div>

                                <!-- Tipo de Elemento -->
                                <div class="space-y-3">
                                    <Label>Tipo de Elemento</Label>
                                    <div class="flex items-center space-x-2">
                                        <Checkbox
                                            id="es_cargo"
                                            :checked="form.es_cargo"
                                            @update:checked="form.es_cargo = $event"
                                        />
                                        <Label for="es_cargo" class="flex items-center gap-2">
                                            <Briefcase class="h-4 w-4" />
                                            Es un Cargo
                                        </Label>
                                    </div>
                                    <div class="bg-muted/50 p-3 rounded-lg text-sm space-y-2">
                                        <div class="flex items-start gap-2">
                                            <Briefcase class="h-4 w-4 mt-0.5 text-blue-600" />
                                            <div>
                                                <p class="font-medium text-blue-800">Cargo:</p>
                                                <p class="text-muted-foreground">
                                                    Aparece en el listado de cargos disponibles para crear convocatorias y postulaciones.
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <Folder class="h-4 w-4 mt-0.5 text-gray-600" />
                                            <div>
                                                <p class="font-medium text-gray-800">Categoría:</p>
                                                <p class="text-muted-foreground">
                                                    Solo se usa para organizar la estructura jerárquica. No aparece en convocatorias.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Estado Activo -->
                                <div class="flex items-center space-x-2">
                                    <Checkbox
                                        id="activo"
                                        :checked="form.activo"
                                        @update:checked="form.activo = $event"
                                    />
                                    <Label for="activo">
                                        Cargo activo
                                    </Label>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Solo los cargos activos pueden ser seleccionados como padres o utilizados en convocatorias.
                                </p>
                            </div>
                        </div>

                        <!-- Vista previa de la ruta jerárquica -->
                        <div v-if="form.parent_id && form.parent_id !== 'null'" class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <Label class="text-blue-800 font-medium">Vista previa de la jerarquía:</Label>
                            <div class="flex items-center gap-2 mt-2 text-blue-700">
                                <span v-if="cargosPadre.find(p => p.id.toString() === form.parent_id)">
                                    {{ cargosPadre.find(p => p.id.toString() === form.parent_id)?.nombre }}
                                </span>
                                <span class="text-blue-500">→</span>
                                <span class="font-medium">{{ form.nombre || 'Nuevo cargo' }}</span>
                            </div>
                        </div>

                        <!-- Errores generales -->
                        <div v-if="form.errors.delete" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-red-800 text-sm">{{ form.errors.delete }}</p>
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex justify-between mt-8">
                            <Button variant="outline" type="button" @click="$inertia.visit('/admin/cargos')">
                                Cancelar
                            </Button>
                            <Button 
                                type="submit" 
                                :disabled="form.processing || !isFormValid"
                            >
                                {{ form.processing ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Crear') }} Cargo
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>