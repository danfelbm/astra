<script setup lang="ts">
/**
 * Página de edición de hitos (User).
 * Utiliza el componente HitoForm reutilizable.
 */
import UserLayout from "@modules/Core/Resources/js/layouts/UserLayout.vue";
import { Head, Link, router } from '@inertiajs/vue3';
import { Alert, AlertDescription } from "@modules/Core/Resources/js/components/ui/alert";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import HitoForm from "@modules/Proyectos/Resources/js/components/HitoForm.vue";
import { ArrowLeft, AlertCircle } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import type { Hito } from '@modules/Proyectos/Resources/js/types/hitos';
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

interface HitoWithResponsable extends Hito {
    responsable?: User;
}

interface Props {
    hito: HitoWithResponsable;
    proyecto: Proyecto;
    responsables: User[];
    hitosDisponibles?: HitoDisponible[];
    camposPersonalizados?: CampoPersonalizado[];
    valoresCamposPersonalizados?: Record<number, any>;
    categorias?: CategoriaEtiqueta[];
    estados: { value: string; label: string }[];
}

const props = defineProps<Props>();

// Helper para obtener route
const { route } = window as any;

// Handler para submit del formulario
const handleSubmit = (form: any) => {
    form.put(`/miembro/mis-proyectos/${props.proyecto.id}/hitos/${props.hito.id}`, {
        onSuccess: () => {
            toast.success('Hito actualizado exitosamente');
        },
        onError: () => {
            toast.error('Error al actualizar el hito');
        },
    });
};

// Handler para cancelar
const handleCancel = () => {
    router.visit(`/miembro/mis-proyectos/${props.proyecto.id}?tab=hitos`);
};
</script>

<template>
    <Head :title="`Editar Hito - ${hito.nombre}`" />

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
                        Editar Hito
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Actualiza la información del hito
                    </p>
                </div>
            </div>

            <!-- Información del hito -->
            <Alert class="mb-6">
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>
                    <div class="flex flex-col gap-1">
                        <span><strong>ID del Hito:</strong> #{{ hito.id }}</span>
                        <span><strong>Progreso:</strong> {{ hito.porcentaje_completado || 0 }}%</span>
                        <span><strong>Creado:</strong> {{ new Date(hito.created_at).toLocaleDateString('es-ES') }}</span>
                        <span><strong>Última actualización:</strong> {{ new Date(hito.updated_at).toLocaleDateString('es-ES') }}</span>
                    </div>
                </AlertDescription>
            </Alert>

            <!-- Formulario reutilizable -->
            <HitoForm
                mode="edit"
                :hito="hito"
                :proyecto-id="proyecto.id"
                :proyecto-nombre="proyecto.nombre"
                :responsables="responsables"
                :hitos-disponibles="hitosDisponibles"
                :campos-personalizados="camposPersonalizados"
                :valores-campos-personalizados="valoresCamposPersonalizados"
                :categorias="categorias"
                :estados="estados"
                :show-parent-selector="true"
                :search-users-endpoint="route('admin.proyectos.search-users')"
                @submit="handleSubmit"
                @cancel="handleCancel"
            />
        </div>
    </UserLayout>
</template>
