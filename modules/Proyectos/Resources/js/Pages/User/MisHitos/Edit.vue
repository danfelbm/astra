<script setup lang="ts">
/**
 * Página de edición de hitos (User).
 * Utiliza el componente HitoForm reutilizable.
 */
import UserLayout from "@modules/Core/Resources/js/layouts/UserLayout.vue";
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import HitoForm from "@modules/Proyectos/Resources/js/components/HitoForm.vue";
import { ArrowLeft } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';
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
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Editar Hito</h1>
                    <p class="text-muted-foreground">Proyecto: {{ proyecto.nombre }}</p>
                </div>
                <Link :href="`/miembro/mis-proyectos/${proyecto.id}?tab=hitos`">
                    <Button variant="outline" size="sm">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Volver al proyecto
                    </Button>
                </Link>
            </div>

            <!-- Card de información del hito -->
            <Card class="mb-4">
                <CardHeader>
                    <CardTitle>{{ hito.nombre }}</CardTitle>
                    <CardDescription>
                        Editando hito del proyecto: {{ proyecto.nombre }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-muted-foreground">Progreso</p>
                            <p class="font-semibold">{{ hito.porcentaje_completado || 0 }}%</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Entregables</p>
                            <p class="font-semibold">{{ hito.entregables?.length || 0 }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Creado</p>
                            <p class="font-semibold">{{ format(parseISO(hito.created_at), 'dd/MM/yyyy', { locale: es }) }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Última actualización</p>
                            <p class="font-semibold">{{ format(parseISO(hito.updated_at), 'dd/MM/yyyy', { locale: es }) }}</p>
                        </div>
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
                :show-parent-selector="true"
                :search-users-endpoint="route('admin.proyectos.search-users')"
                @submit="handleSubmit"
                @cancel="handleCancel"
            />
        </div>
    </UserLayout>
</template>
