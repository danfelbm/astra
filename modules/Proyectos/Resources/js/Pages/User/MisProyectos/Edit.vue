<script setup lang="ts">
import UserLayout from "@modules/Core/Resources/js/layouts/UserLayout.vue";
import { Head, router, Link } from '@inertiajs/vue3';
import { Alert, AlertDescription } from "@modules/Core/Resources/js/components/ui/alert";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import ProyectoFormFields from "@modules/Proyectos/Resources/js/components/ProyectoFormFields.vue";
import { ArrowLeft, AlertCircle } from 'lucide-vue-next';
import type { CategoriaEtiqueta, Etiqueta } from "@modules/Proyectos/Resources/js/types/etiquetas";

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

// Función para cancelar
const handleCancel = () => {
    router.visit(`/miembro/mis-proyectos/${props.proyecto.id}`);
};

// Función cuando el formulario se envía exitosamente
const handleSuccess = () => {
    setTimeout(() => {
        router.visit(`/miembro/mis-proyectos/${props.proyecto.id}`);
    }, 1000);
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

            <!-- Formulario reutilizable -->
            <div class="max-w-4xl">
                <ProyectoFormFields
                    :proyecto="proyecto"
                    :campos-personalizados="camposPersonalizados"
                    :valores-campos="valoresCampos"
                    :categorias="categorias"
                    :submit-url="`/miembro/mis-proyectos/${proyecto.id}`"
                    :cancel-url="`/miembro/mis-proyectos/${proyecto.id}`"
                    :estados="estados"
                    :prioridades="prioridades"
                    :show-responsable="false"
                    :show-gestores="false"
                    @cancel="handleCancel"
                    @success="handleSuccess"
                />
            </div>
        </div>
    </UserLayout>
</template>
