<script setup lang="ts">
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import ProyectoFormFields from "@modules/Proyectos/Resources/js/components/ProyectoFormFields.vue";
import type { CategoriaEtiqueta } from "@modules/Proyectos/Resources/js/types/etiquetas";

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

interface TokenNomenclatura {
    token: string;
    descripcion: string;
}

interface Props {
    camposPersonalizados: CampoPersonalizado[];
    categorias?: CategoriaEtiqueta[];
    estados?: Record<string, string>;
    prioridades?: Record<string, string>;
    tokensNomenclatura?: TokenNomenclatura[];
}

const props = defineProps<Props>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Proyectos', href: '/admin/proyectos' },
    { title: 'Crear Proyecto', href: '/admin/proyectos/create' },
];

// Helper para obtener route
const { route } = window as any;

// Función para cancelar
const handleCancel = () => {
    router.visit('/admin/proyectos');
};

// Función cuando el formulario se envía exitosamente
const handleSuccess = () => {
    router.visit('/admin/proyectos');
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

            <!-- Formulario reutilizable -->
            <ProyectoFormFields
                mode="create"
                :campos-personalizados="camposPersonalizados"
                :categorias="categorias"
                :estados="estados"
                :prioridades="prioridades"
                :tokens-nomenclatura="tokensNomenclatura"
                submit-url="/admin/proyectos"
                cancel-url="/admin/proyectos"
                :search-users-endpoint="route('admin.proyectos.search-users')"
                :show-responsable="true"
                :show-gestores="true"
                :show-info-alert="false"
                @cancel="handleCancel"
                @success="handleSuccess"
            />
        </div>
    </AdminLayout>
</template>
