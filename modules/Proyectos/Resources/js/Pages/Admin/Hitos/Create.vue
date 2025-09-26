<script setup lang="ts">
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { Textarea } from "@modules/Core/Resources/js/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@modules/Core/Resources/js/components/ui/select";
// Removido DatePicker - usaremos Input type="date" HTML5
import AddUsersModal from "@modules/Core/Resources/js/components/modals/AddUsersModal.vue";
import { ArrowLeft, Save, UserPlus, X } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import type { BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Proyecto {
    id: number;
    nombre: string;
}

interface Props {
    proyecto: Proyecto;
    responsables: User[];
    estados: { value: string; label: string }[];
    siguienteOrden: number;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Proyectos', href: '/admin/proyectos' },
    { title: props.proyecto.nombre, href: `/admin/proyectos/${props.proyecto.id}` },
    { title: 'Hitos', href: `/admin/proyectos/${props.proyecto.id}/hitos` },
    { title: 'Crear Hito', href: '#' },
];

// Estado para el modal de selección de responsable
const showResponsableModal = ref(false);

// Helper para obtener route
const { route } = window as any;

// Estado para almacenar el responsable seleccionado
const responsableSeleccionado = ref<User | null>(null);
const usuariosCache = ref<Map<number, User>>(new Map());

// Inicializar cache con responsables disponibles
props.responsables.forEach(u => usuariosCache.value.set(u.id, u));

// Removidos watchers de debug

const form = useForm({
    nombre: '',
    descripcion: '',
    fecha_inicio: '',
    fecha_fin: '',
    estado: 'pendiente',
    responsable_id: null as number | null,
    orden: props.siguienteOrden,
    crear_entregables_predefinidos: false,
});

// Manejar selección de responsable desde el modal
const handleResponsableSelect = (data: { userIds: number[]; extraData: Record<string, any> }) => {
    if (data.userIds.length > 0) {
        const userId = data.userIds[0];
        form.responsable_id = userId;

        // Buscar primero en el cache
        if (usuariosCache.value.has(userId)) {
            responsableSeleccionado.value = usuariosCache.value.get(userId) || null;
        } else {
            // Si no está en cache, crear un placeholder temporal
            // El usuario real se mostrará después del guardado
            responsableSeleccionado.value = {
                id: userId,
                name: `Usuario seleccionado (ID: ${userId})`,
                email: 'Se actualizará al guardar'
            } as User;

            // Agregar al cache para no perderlo
            usuariosCache.value.set(userId, responsableSeleccionado.value);
        }
    }
};

const submit = () => {
    // Las fechas ya están en formato yyyy-MM-dd desde el input
    const data = {
        ...form.data(),
    };

    form.transform(() => data).post(`/admin/proyectos/${props.proyecto.id}/hitos`, {
        onSuccess: () => {
            toast.success('Hito creado exitosamente');
        },
        onError: () => {
            toast.error('Error al crear el hito');
        },
    });
};
</script>

<template>
    <Head :title="`Crear Hito - ${proyecto.nombre}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Crear Nuevo Hito</h1>
                <Link :href="`/admin/proyectos/${proyecto.id}/hitos`">
                    <Button variant="outline" size="sm">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Volver
                    </Button>
                </Link>
            </div>

            <!-- Formulario -->
            <Card>
                <CardHeader>
                    <CardTitle>Información del Hito</CardTitle>
                    <CardDescription>
                        Define los detalles del nuevo hito para el proyecto
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Nombre -->
                        <div class="space-y-2">
                            <Label for="nombre" required>Nombre del Hito</Label>
                            <Input
                                id="nombre"
                                v-model="form.nombre"
                                type="text"
                                placeholder="Ej: Pre-producción"
                                :disabled="form.processing"
                            />
                            <span v-if="form.errors.nombre" class="text-sm text-red-500">{{ form.errors.nombre }}</span>
                        </div>

                        <!-- Descripción -->
                        <div class="space-y-2">
                            <Label for="descripcion">Descripción</Label>
                            <Textarea
                                id="descripcion"
                                v-model="form.descripcion"
                                placeholder="Describe el objetivo y alcance del hito..."
                                :disabled="form.processing"
                                rows="3"
                            />
                            <span v-if="form.errors.descripcion" class="text-sm text-red-500">{{ form.errors.descripcion }}</span>
                        </div>

                        <!-- Fechas -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Fecha Inicio -->
                            <div class="space-y-2">
                                <Label for="fecha_inicio">Fecha de Inicio</Label>
                                <Input
                                    id="fecha_inicio"
                                    v-model="form.fecha_inicio"
                                    type="date"
                                    :disabled="form.processing"
                                />
                                <span v-if="form.errors.fecha_inicio" class="text-sm text-red-500">{{ form.errors.fecha_inicio }}</span>
                            </div>

                            <!-- Fecha Fin -->
                            <div class="space-y-2">
                                <Label for="fecha_fin">Fecha de Fin</Label>
                                <Input
                                    id="fecha_fin"
                                    v-model="form.fecha_fin"
                                    type="date"
                                    :disabled="form.processing"
                                />
                                <span v-if="form.errors.fecha_fin" class="text-sm text-red-500">{{ form.errors.fecha_fin }}</span>
                            </div>
                        </div>

                        <!-- Estado y Responsable -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Estado -->
                            <div class="space-y-2">
                                <Label for="estado">Estado Inicial</Label>
                                <Select v-model="form.estado">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="estado in estados"
                                            :key="estado.value"
                                            :value="estado.value"
                                        >
                                            {{ estado.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <span v-if="form.errors.estado" class="text-sm text-red-500">{{ form.errors.estado }}</span>
                            </div>

                            <!-- Responsable -->
                            <div class="space-y-2">
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
                                <span v-if="form.errors.responsable_id" class="text-sm text-red-500">{{ form.errors.responsable_id }}</span>
                            </div>
                        </div>

                        <!-- Crear entregables predefinidos -->
                        <div class="flex items-center space-x-2">
                            <input
                                id="crear_entregables"
                                v-model="form.crear_entregables_predefinidos"
                                type="checkbox"
                                class="rounded border-gray-300"
                            />
                            <Label for="crear_entregables" class="cursor-pointer">
                                Crear entregables predefinidos según el tipo de proyecto
                            </Label>
                        </div>

                        <!-- Acciones -->
                        <div class="flex justify-end gap-2 pt-4">
                            <Link :href="`/admin/proyectos/${proyecto.id}/hitos`">
                                <Button type="button" variant="outline">
                                    Cancelar
                                </Button>
                            </Link>
                            <Button type="submit" :disabled="form.processing">
                                <Save class="h-4 w-4 mr-2" />
                                Crear Hito
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>

        <!-- Modal de selección de responsable -->
        <AddUsersModal
            v-model="showResponsableModal"
            title="Seleccionar Responsable del Hito"
            description="Selecciona el usuario que será responsable de este hito"
            :search-endpoint="route('admin.proyectos.search-users')"
            :excluded-ids="form.responsable_id ? [form.responsable_id] : []"
            :max-selection="1"
            submit-button-text="Seleccionar Responsable"
            search-placeholder="Buscar por nombre, email, documento o teléfono..."
            @submit="handleResponsableSelect"
        />
    </AdminLayout>
</template>