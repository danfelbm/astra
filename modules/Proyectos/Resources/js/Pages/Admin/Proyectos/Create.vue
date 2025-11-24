<script setup lang="ts">
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, router } from '@inertiajs/vue3';
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
import GestoresManager from "@modules/Proyectos/Resources/js/components/GestoresManager.vue";
import AddUsersModal from "@modules/Core/Resources/js/components/modals/AddUsersModal.vue";
import { Save, X, Tag, UserPlus } from 'lucide-vue-next';
import type { CategoriaEtiqueta } from "@modules/Proyectos/Resources/js/types/etiquetas";
import { ref, computed } from 'vue';
import { toast } from 'vue-sonner';

// Interfaces
interface User {
    id: number;
    name: string;
    email?: string;
}

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

interface Props {
    camposPersonalizados: CampoPersonalizado[];
    categorias?: CategoriaEtiqueta[];
}

const props = defineProps<Props>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Proyectos', href: '/admin/proyectos' },
    { title: 'Crear Proyecto', href: '/admin/proyectos/create' },
];

// Formulario
const form = useForm({
    nombre: '',
    descripcion: '',
    fecha_inicio: '',
    fecha_fin: '',
    estado: 'planificacion',
    prioridad: 'media',
    responsable_id: null as number | null,
    etiquetas: [] as number[],
    gestores: [] as number[],
    campos_personalizados: {} as Record<number, any>
});

// Estado de procesamiento
const processing = ref(false);

// Estado para el modal de selección de responsable
const showResponsableModal = ref(false);

// Ref para el responsable seleccionado
const responsableSeleccionado = ref<User | null>(null);

// Refs para gestores (lista completa de usuarios disponibles)
const gestoresDisponibles = ref<User[]>([]);

// Helper para obtener route
const { route } = window as any;

// Función para guardar
const submit = () => {
    processing.value = true;

    form.post('/admin/proyectos', {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        }
    });
};

// Función para cancelar
const cancelar = () => {
    router.visit('/admin/proyectos');
};

// Actualizar campos personalizados
const updateCamposPersonalizados = (valores: Record<number, any>) => {
    form.campos_personalizados = valores;
};

// Manejar selección de responsable desde el modal
const handleResponsableSelect = (data: { userIds: number[]; extraData: Record<string, any>; users?: User[] }) => {
    if (data.userIds.length > 0) {
        form.responsable_id = data.userIds[0]; // Solo tomamos el primer usuario seleccionado
        // Actualizar la referencia del responsable con los datos completos
        if (data.users && data.users.length > 0) {
            responsableSeleccionado.value = data.users[0];
        }
    }
};
</script>

<template>
    <Head title="Crear Proyecto" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Crear Proyecto
                </h1>
            </div>

            <!-- Formulario -->
            <form @submit.prevent="submit" class="space-y-6">
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

                        <!-- Fecha de Inicio -->
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

                        <!-- Fecha de Fin -->
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

                        <!-- Estado -->
                        <div>
                            <Label for="estado">Estado *</Label>
                            <Select v-model="form.estado">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccione el estado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="planificacion">Planificación</SelectItem>
                                    <SelectItem value="en_progreso">En Progreso</SelectItem>
                                    <SelectItem value="pausado">Pausado</SelectItem>
                                    <SelectItem value="completado">Completado</SelectItem>
                                    <SelectItem value="cancelado">Cancelado</SelectItem>
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
                                    <SelectItem value="baja">Baja</SelectItem>
                                    <SelectItem value="media">Media</SelectItem>
                                    <SelectItem value="alta">Alta</SelectItem>
                                    <SelectItem value="critica">Crítica</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.prioridad" class="mt-1 text-sm text-red-600">
                                {{ form.errors.prioridad }}
                            </p>
                        </div>

                        <!-- Responsable -->
                        <div>
                            <Label for="responsable_id">Responsable</Label>
                            <div class="space-y-2">
                                <!-- Mostrar responsable seleccionado -->
                                <div v-if="responsableSeleccionado" class="p-3 bg-muted rounded-lg flex items-center justify-between">
                                    <div>
                                        <p class="font-medium">{{ responsableSeleccionado.name }}</p>
                                        <p class="text-sm text-muted-foreground">{{ responsableSeleccionado.email }}</p>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="form.responsable_id = null; responsableSeleccionado = null"
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                                <!-- Botón para seleccionar responsable -->
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="showResponsableModal = true"
                                    class="w-full"
                                >
                                    <UserPlus class="h-4 w-4 mr-2" />
                                    {{ responsableSeleccionado ? 'Cambiar Responsable' : 'Seleccionar Responsable' }}
                                </Button>
                            </div>
                            <p v-if="form.errors.responsable_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.responsable_id }}
                            </p>
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

                <!-- Gestores del Proyecto -->
                <GestoresManager
                    v-model="form.gestores"
                    :gestores="gestoresDisponibles"
                />

                <!-- Campos Personalizados -->
                <CamposPersonalizadosForm
                    v-if="camposPersonalizados.length > 0"
                    :campos="camposPersonalizados"
                    :valores="{}"
                    :errors="form.errors"
                    @update="updateCamposPersonalizados"
                />

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
                            {{ processing ? 'Guardando...' : 'Guardar Proyecto' }}
                        </Button>
                    </CardContent>
                </Card>
            </form>
        </div>

        <!-- Modal de selección de responsable -->
        <AddUsersModal
            v-model="showResponsableModal"
            title="Seleccionar Responsable"
            description="Selecciona el usuario que será responsable de este proyecto"
            :search-endpoint="route('admin.proyectos.search-users')"
            :excluded-ids="form.responsable_id ? [form.responsable_id] : []"
            :max-selection="1"
            submit-button-text="Seleccionar Responsable"
            search-placeholder="Buscar por nombre, email, documento o teléfono..."
            @submit="handleResponsableSelect"
        />
    </AdminLayout>
</template>