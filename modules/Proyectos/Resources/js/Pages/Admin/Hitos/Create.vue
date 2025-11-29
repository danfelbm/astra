<script setup lang="ts">
/**
 * Página de creación de hitos (Admin).
 * Utiliza el componente HitoForm reutilizable.
 */
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { Head, router } from '@inertiajs/vue3';
import HitoForm from "@modules/Proyectos/Resources/js/components/HitoForm.vue";
import type { BreadcrumbItem } from '@/types';
import type { CategoriaEtiqueta } from "@modules/Proyectos/Resources/js/types/etiquetas";

interface User {
    id: number;
    name: string;
    email: string;
}

interface Proyecto {
    id: number;
    nombre: string;
}

interface CampoPersonalizado {
    id: number;
    nombre: string;
    tipo: string;
    es_requerido: boolean;
    opciones?: any[];
}

interface HitoDisponible {
    id: number;
    nombre: string;
    ruta_completa: string;
    nivel: number;
}

interface Props {
    proyecto: Proyecto;
    responsables: User[];
    hitosDisponibles?: HitoDisponible[];
    camposPersonalizados?: CampoPersonalizado[];
    categorias?: CategoriaEtiqueta[];
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

const { route } = window as any;

// Handler para submit del formulario
const handleSubmit = (form: any) => {
    form.post(`/admin/proyectos/${props.proyecto.id}/hitos`, {
        preserveScroll: true
    });
};

// Handler para cancelar
const handleCancel = () => {
    router.visit(`/admin/proyectos/${props.proyecto.id}/hitos`);
};
</script>

<template>
    <Head :title="`Crear Hito - ${proyecto.nombre}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Crear Nuevo Hito</h1>
            </div>

            <!-- Formulario reutilizable -->
            <HitoForm
                mode="create"
                :proyecto-id="proyecto.id"
                :proyecto-nombre="proyecto.nombre"
                :responsables="responsables"
                :hitos-disponibles="hitosDisponibles"
                :campos-personalizados="camposPersonalizados"
                :categorias="categorias"
                :estados="estados"
                :siguiente-orden="siguienteOrden"
                :search-users-endpoint="route('admin.proyectos.search-users')"
                @submit="handleSubmit"
                @cancel="handleCancel"
            />
        </div>
    </AdminLayout>
</template>
