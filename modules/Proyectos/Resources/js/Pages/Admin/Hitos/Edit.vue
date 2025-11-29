<script setup lang="ts">
/**
 * Página de edición de hitos (Admin).
 * Utiliza el componente HitoForm reutilizable.
 */
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent } from "@modules/Core/Resources/js/components/ui/card";
import { AlertCircle } from 'lucide-vue-next';
import HitoForm from "@modules/Proyectos/Resources/js/components/HitoForm.vue";
import { toast } from 'vue-sonner';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';
import type { BreadcrumbItem } from '@/types';
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Proyectos', href: '/admin/proyectos' },
    { title: props.proyecto.nombre, href: `/admin/proyectos/${props.proyecto.id}` },
    { title: 'Hitos', href: `/admin/proyectos/${props.proyecto.id}/hitos` },
    { title: 'Editar Hito', href: '#' },
];

const { route } = window as any;

// Handler para submit del formulario
const handleSubmit = (form: any) => {
    form.put(`/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}`, {
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
    router.visit(`/admin/proyectos/${props.proyecto.id}/hitos`);
};
</script>

<template>
    <Head :title="`Editar Hito - ${hito.nombre}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Editar Hito</h1>
            </div>

            <!-- Información del hito -->
            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <AlertCircle class="h-4 w-4" />
                        <span>ID: {{ hito.id }}</span>
                        <span class="mx-2">•</span>
                        <span>Progreso: {{ hito.porcentaje_completado || 0 }}%</span>
                        <span class="mx-2">•</span>
                        <span>Creado: {{ format(parseISO(hito.created_at), 'dd/MM/yyyy', { locale: es }) }}</span>
                        <span class="mx-2">•</span>
                        <span>Actualizado: {{ format(parseISO(hito.updated_at), 'dd/MM/yyyy', { locale: es }) }}</span>
                    </div>
                </CardContent>
            </Card>

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
                :search-users-endpoint="route('admin.proyectos.search-users')"
                @submit="handleSubmit"
                @cancel="handleCancel"
            />
        </div>
    </AdminLayout>
</template>
