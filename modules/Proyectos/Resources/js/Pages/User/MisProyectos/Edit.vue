<script setup lang="ts">
import UserLayout from "@modules/Core/Resources/js/layouts/UserLayout.vue";
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Textarea } from "@modules/Core/Resources/js/components/ui/textarea";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { Alert, AlertDescription } from "@modules/Core/Resources/js/components/ui/alert";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from "@modules/Core/Resources/js/components/ui/select";
import CamposPersonalizadosForm from "@modules/Proyectos/Resources/js/components/CamposPersonalizadosForm.vue";
import EtiquetaSelector from "@modules/Proyectos/Resources/js/components/EtiquetaSelector.vue";
import { Save, X, ArrowLeft, AlertCircle, Tag } from 'lucide-vue-next';
import type { CategoriaEtiqueta, Etiqueta } from "@modules/Proyectos/Resources/js/types/etiquetas";
import { ref } from 'vue';
import { toast } from 'vue-sonner';

// Interfaces
interface CampoPersonalizado {
    id: number;
    nombre: string;
    slug: string;
    tipo: string;
    opciones?: any[];
    es_requerido: boolean;
    placeholder?: string;
    descripcion?: string;
}

interface Proyecto {
    id: number;
    nombre: string;
    descripcion?: string;
    fecha_inicio: string;
    fecha_fin?: string;
    estado: string;
    prioridad: string;
    responsable_id?: number;
    etiquetas?: Etiqueta[];
    created_at: string;
    updated_at: string;
}

interface Props {
    proyecto: Proyecto;
    camposPersonalizados: CampoPersonalizado[];
    valoresCampos: Record<string, any>;
    estados: Record<string, string>;
    prioridades: Record<string, string>;
    categorias?: CategoriaEtiqueta[];
}

const props = defineProps<Props>();

// Preparar valores iniciales de campos personalizados
const valoresInicialesCampos: Record<number, any> = {};
props.camposPersonalizados.forEach(campo => {
    valoresInicialesCampos[campo.id] = props.valoresCampos[campo.slug] || '';
});

// Formatear fechas para inputs HTML de tipo date
const formatDateForInput = (dateString: string | undefined): string => {
    if (!dateString) return '';
    // Si la fecha viene con hora, tomar solo la parte de fecha
    return dateString.split(' ')[0];
};

// Formulario
const form = useForm({
    nombre: props.proyecto.nombre,
    descripcion: props.proyecto.descripcion || '',
    fecha_inicio: formatDateForInput(props.proyecto.fecha_inicio),
    fecha_fin: formatDateForInput(props.proyecto.fecha_fin),
    estado: props.proyecto.estado,
    prioridad: props.proyecto.prioridad,
    etiquetas: props.proyecto.etiquetas?.map(e => e.id) || [],
    campos_personalizados: valoresInicialesCampos
});

// Estado de procesamiento
const processing = ref(false);

// Función para actualizar
const submit = () => {
    processing.value = true;

    form.put(`/miembro/mis-proyectos/${props.proyecto.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Proyecto actualizado exitosamente');
            setTimeout(() => {
                router.visit(`/miembro/mis-proyectos/${props.proyecto.id}`);
            }, 1000);
        },
        onError: (errors) => {
            toast.error('Error al actualizar el proyecto');
            console.error(errors);
        },
        onFinish: () => {
            processing.value = false;
        }
    });
};

// Función para cancelar
const cancelar = () => {
    router.visit(`/miembro/mis-proyectos/${props.proyecto.id}`);
};

// Actualizar campos personalizados
const updateCamposPersonalizados = (valores: Record<number, any>) => {
    form.campos_personalizados = valores;
};

// Función para formatear fecha
const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head :title="`Editar: ${proyecto.nombre}`" />

    <UserLayout>
        <div class="flex h-full flex-1 flex-col rounded-xl p-4">
            <!-- Header con navegación -->
            <div class="flex items-center gap-4 mb-6">
                <Link :href="`/miembro/mis-proyectos/${proyecto.id}`">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Editar Proyecto
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Actualiza la información de tu proyecto
                    </p>
                </div>
            </div>

            <!-- Información del proyecto -->
            <Alert class="mb-6">
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>
                    <div class="flex flex-col gap-1">
                        <span><strong>ID del Proyecto:</strong> #{{ proyecto.id }}</span>
                        <span><strong>Creado:</strong> {{ formatDate(proyecto.created_at) }}</span>
                        <span><strong>Última actualización:</strong> {{ formatDate(proyecto.updated_at) }}</span>
                    </div>
                </AlertDescription>
            </Alert>

            <!-- Formulario -->
            <form @submit.prevent="submit" class="space-y-6 max-w-4xl">
                <!-- Información básica -->
                <Card>
                    <CardHeader>
                        <CardTitle>Información Básica</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Nombre -->
                        <div>
                            <Label for="nombre">Nombre del Proyecto *</Label>
                            <Input
                                id="nombre"
                                v-model="form.nombre"
                                type="text"
                                placeholder="Ingrese el nombre del proyecto"
                                :class="{ 'border-red-500': form.errors.nombre }"
                                required
                            />
                            <p v-if="form.errors.nombre" class="mt-1 text-sm text-red-600">
                                {{ form.errors.nombre }}
                            </p>
                        </div>

                        <!-- Descripción -->
                        <div>
                            <Label for="descripcion">Descripción</Label>
                            <Textarea
                                id="descripcion"
                                v-model="form.descripcion"
                                placeholder="Ingrese una descripción del proyecto"
                                rows="4"
                                :class="{ 'border-red-500': form.errors.descripcion }"
                            />
                            <p v-if="form.errors.descripcion" class="mt-1 text-sm text-red-600">
                                {{ form.errors.descripcion }}
                            </p>
                        </div>

                        <!-- Fechas -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label for="fecha_inicio">Fecha de Inicio *</Label>
                                <Input
                                    id="fecha_inicio"
                                    v-model="form.fecha_inicio"
                                    type="date"
                                    :class="{ 'border-red-500': form.errors.fecha_inicio }"
                                    required
                                />
                                <p v-if="form.errors.fecha_inicio" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.fecha_inicio }}
                                </p>
                            </div>

                            <div>
                                <Label for="fecha_fin">Fecha de Fin</Label>
                                <Input
                                    id="fecha_fin"
                                    v-model="form.fecha_fin"
                                    type="date"
                                    :min="form.fecha_inicio"
                                    :class="{ 'border-red-500': form.errors.fecha_fin }"
                                />
                                <p v-if="form.errors.fecha_fin" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.fecha_fin }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Estado y Prioridad -->
                <Card>
                    <CardHeader>
                        <CardTitle>Configuración del Proyecto</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Estado -->
                            <div>
                                <Label for="estado">Estado *</Label>
                                <Select v-model="form.estado">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccione el estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="(label, value) in estados" :key="value" :value="value">
                                            {{ label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.estado" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.estado }}
                                </p>
                            </div>

                            <!-- Prioridad -->
                            <div>
                                <Label for="prioridad">Prioridad *</Label>
                                <Select v-model="form.prioridad">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccione la prioridad" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="(label, value) in prioridades" :key="value" :value="value">
                                            {{ label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.prioridad" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.prioridad }}
                                </p>
                            </div>
                        </div>

                        <!-- Etiquetas -->
                        <div v-if="categorias && categorias.length > 0">
                            <Label class="flex items-center gap-2 mb-2">
                                <Tag class="h-4 w-4" />
                                Etiquetas
                            </Label>
                            <EtiquetaSelector
                                v-model="form.etiquetas"
                                :categorias="categorias"
                                :max-etiquetas="10"
                                placeholder="Seleccionar etiquetas para el proyecto..."
                                description="Puedes asignar hasta 10 etiquetas para categorizar este proyecto"
                            />
                            <p v-if="form.errors.etiquetas" class="mt-1 text-sm text-red-600">
                                {{ form.errors.etiquetas }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Campos Personalizados -->
                <CamposPersonalizadosForm
                    v-if="camposPersonalizados.length > 0"
                    :campos="camposPersonalizados"
                    :valores="valoresInicialesCampos"
                    :errors="form.errors"
                    @update="updateCamposPersonalizados"
                />

                <!-- Nota informativa -->
                <Alert>
                    <AlertCircle class="h-4 w-4" />
                    <AlertDescription>
                        Los cambios se guardarán inmediatamente. Si necesitas cambiar el responsable del proyecto,
                        contacta con un administrador.
                    </AlertDescription>
                </Alert>

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
                            {{ processing ? 'Actualizando...' : 'Actualizar Proyecto' }}
                        </Button>
                    </CardContent>
                </Card>
            </form>
        </div>
    </UserLayout>
</template>