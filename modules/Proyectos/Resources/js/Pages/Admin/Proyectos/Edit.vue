<script setup lang="ts">
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent } from "@modules/Core/Resources/js/components/ui/card";
import ProyectoFormFields from "@modules/Proyectos/Resources/js/components/ProyectoFormFields.vue";
import { AlertCircle } from 'lucide-vue-next';
import type { CategoriaEtiqueta, Etiqueta } from "@modules/Proyectos/Resources/js/types/etiquetas";

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

interface Proyecto {
    id: number;
    nombre: string;
    descripcion?: string;
    fecha_inicio: string;
    fecha_fin?: string;
    estado: string;
    prioridad: string;
    responsable_id?: number;
    responsable?: User;
    etiquetas?: Etiqueta[];
    nomenclatura_archivos?: string;
    created_at: string;
    updated_at: string;
}

interface TokenNomenclatura {
    token: string;
    descripcion: string;
}

interface Props {
    proyecto: Proyecto;
    camposPersonalizados: CampoPersonalizado[];
    valoresCampos: Record<number, any>;
    categorias?: CategoriaEtiqueta[];
    gestores?: User[];
    tokensNomenclatura?: TokenNomenclatura[];
}

const props = defineProps<Props>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Proyectos', href: '/admin/proyectos' },
    { title: props.proyecto.nombre, href: `/admin/proyectos/${props.proyecto.id}` },
    { title: 'Editar', href: `/admin/proyectos/${props.proyecto.id}/edit` },
];

// Helper para obtener route
const { route } = window as any;

// Función para cancelar
const handleCancel = () => {
    router.visit(`/admin/proyectos/${props.proyecto.id}`);
};

// Función cuando el formulario se envía exitosamente
const handleSuccess = () => {
    // Podrías redirigir aquí si lo deseas
    // router.visit(`/admin/proyectos/${props.proyecto.id}`);
};
</script>

<template>
    <Head :title="`Editar: ${proyecto.nombre}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Editar Proyecto
                </h1>
            </div>

            <!-- Información del proyecto -->
            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <AlertCircle class="h-4 w-4" />
                        <span>ID: {{ proyecto.id }}</span>
                        <span class="mx-2">•</span>
                        <span>Creado: {{ new Date(proyecto.created_at).toLocaleDateString() }}</span>
                        <span class="mx-2">•</span>
                        <span>Actualizado: {{ new Date(proyecto.updated_at).toLocaleDateString() }}</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Formulario reutilizable -->
            <ProyectoFormFields
                :proyecto="proyecto"
                :campos-personalizados="camposPersonalizados"
                :valores-campos="valoresCampos"
                :categorias="categorias"
                :gestores="gestores"
                :tokens-nomenclatura="tokensNomenclatura"
                :submit-url="`/admin/proyectos/${proyecto.id}`"
                :cancel-url="`/admin/proyectos/${proyecto.id}`"
                :search-users-endpoint="route('admin.proyectos.search-users')"
                :show-responsable="true"
                :show-gestores="true"
                @cancel="handleCancel"
                @success="handleSuccess"
            />
        </div>
    </AdminLayout>
</template>
