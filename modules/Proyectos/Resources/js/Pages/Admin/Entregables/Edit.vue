<script setup lang="ts">
// Página para editar un entregable (Admin)
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import { Card, CardContent } from '@modules/Core/Resources/js/components/ui/card';
import { AlertCircle } from 'lucide-vue-next';
import EntregableForm from '@modules/Proyectos/Resources/js/components/EntregableForm.vue';
import type { Hito, Entregable } from '@modules/Proyectos/Resources/js/types/hitos';
import type { CategoriaEtiqueta } from "@modules/Proyectos/Resources/js/types/etiquetas";

// Interfaces
interface Usuario {
  id: number;
  name: string;
  email: string;
  avatar?: string;
}

interface UsuarioAsignado {
  user_id: number;
  rol: 'colaborador' | 'revisor';
}

interface CampoPersonalizado {
  id: number;
  nombre: string;
  tipo: string;
  es_requerido: boolean;
  opciones?: any[];
}

interface Props {
  proyecto: {
    id: number;
    nombre: string;
    descripcion?: string;
  };
  hito: Hito;
  entregable: Entregable;
  usuarios: Usuario[];
  usuariosAsignados: UsuarioAsignado[];
  camposPersonalizados?: CampoPersonalizado[];
  valoresCamposPersonalizados?: Record<number, any>;
  categorias?: CategoriaEtiqueta[];
  estados: Array<{ value: string; label: string }>;
  prioridades: Array<{ value: string; label: string; color: string }>;
}

const props = defineProps<Props>();

// Helper para obtener route
const { route } = window as any;

// Breadcrumbs
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { title: 'Admin', href: '/admin/dashboard' },
  { title: 'Proyectos', href: '/admin/proyectos' },
  { title: props.proyecto.nombre, href: `/admin/proyectos/${props.proyecto.id}` },
  { title: 'Hitos', href: `/admin/proyectos/${props.proyecto.id}/hitos` },
  { title: props.hito.nombre, href: `/admin/proyectos/${props.proyecto.id}?tab=hitos&hito=${props.hito.id}&modal=hito` },
  { title: 'Entregables', href: `/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}/entregables` },
  { title: 'Editar Entregable' },
]);
</script>

<template>
  <Head :title="`Editar Entregable - ${entregable.nombre}`" />

  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Editar Entregable</h1>
      </div>

      <!-- Información del entregable -->
      <Card>
        <CardContent class="pt-6">
          <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
            <AlertCircle class="h-4 w-4" />
            <span>ID: {{ entregable.id }}</span>
            <span class="mx-2">•</span>
            <span>Creado: {{ new Date(entregable.created_at).toLocaleDateString() }}</span>
            <span class="mx-2">•</span>
            <span>Actualizado: {{ new Date(entregable.updated_at).toLocaleDateString() }}</span>
          </div>
        </CardContent>
      </Card>

      <!-- Formulario reutilizable -->
      <EntregableForm
        mode="edit"
        :proyecto="proyecto"
        :hito="hito"
        :entregable="entregable"
        :usuarios="usuarios"
        :usuarios-asignados="usuariosAsignados"
        :campos-personalizados="camposPersonalizados"
        :valores-campos-personalizados="valoresCamposPersonalizados"
        :categorias="categorias"
        :estados="estados"
        :prioridades="prioridades"
        :submit-url="`/admin/proyectos/${proyecto.id}/hitos/${hito.id}/entregables/${entregable.id}`"
        :cancel-url="`/admin/proyectos/${proyecto.id}/hitos/${hito.id}/entregables`"
        :search-users-endpoint="route('admin.proyectos.search-users')"
      />
    </div>
  </AdminLayout>
</template>
