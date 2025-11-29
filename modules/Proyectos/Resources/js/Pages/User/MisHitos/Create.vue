<script setup lang="ts">
/**
 * Página de creación de hitos (User).
 * Utiliza el componente HitoForm reutilizable.
 */
import UserLayout from "@modules/Core/Resources/js/layouts/UserLayout.vue";
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import HitoForm from "@modules/Proyectos/Resources/js/components/HitoForm.vue";
import { ArrowLeft } from 'lucide-vue-next';
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

// Helper para obtener route
const { route } = window as any;

// Handler para submit del formulario
const handleSubmit = (form: any) => {
    form.post(`/miembro/mis-proyectos/${props.proyecto.id}/hitos`, {
        preserveScroll: true
    });
};

// Handler para cancelar
const handleCancel = () => {
    router.visit(`/miembro/mis-proyectos/${props.proyecto.id}?tab=hitos`);
};
</script>

<template>
    <Head :title="`Crear Hito - ${proyecto.nombre}`" />

    <UserLayout>
        <div class="flex h-full flex-1 flex-col rounded-xl p-4">
            <!-- Header con navegación -->
            <div class="flex items-center gap-4 mb-6">
                <Link :href="`/miembro/mis-proyectos/${proyecto.id}?tab=hitos`">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Crear Nuevo Hito
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Define los detalles del nuevo hito para tu proyecto
                    </p>
                </div>
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
                :show-parent-selector="true"
                :search-users-endpoint="route('admin.proyectos.search-users')"
                @submit="handleSubmit"
                @cancel="handleCancel"
            />
        </div>
    </UserLayout>
</template>
