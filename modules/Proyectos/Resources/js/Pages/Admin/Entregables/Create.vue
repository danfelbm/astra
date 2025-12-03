<script setup lang="ts">
// Página para crear un nuevo entregable (Admin)
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import EntregableForm from '@modules/Proyectos/Resources/js/components/EntregableForm.vue';
import type { Hito } from '@modules/Proyectos/Resources/js/types/hitos';
import type { CategoriaEtiqueta } from "@modules/Proyectos/Resources/js/types/etiquetas";

// Interfaces
interface Usuario {
  id: number;
  name: string;
  email: string;
  avatar?: string;
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
  usuarios: Usuario[];
  camposPersonalizados?: CampoPersonalizado[];
  categorias?: CategoriaEtiqueta[];
  estados: Array<{ value: string; label: string }>;
  prioridades: Array<{ value: string; label: string; color: string }>;
  siguienteOrden?: number;
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
  { title: 'Nuevo Entregable' },
]);
</script>

<template>
  <Head :title="`Crear Entregable - ${hito.nombre}`" />

  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Nuevo Entregable</h1>
      </div>

      <!-- Formulario reutilizable -->
      <EntregableForm
        mode="create"
        :proyecto="proyecto"
        :hito="hito"
        :usuarios="usuarios"
        :campos-personalizados="camposPersonalizados"
        :categorias="categorias"
        :estados="estados"
        :prioridades="prioridades"
        :siguiente-orden="siguienteOrden"
        :submit-url="`/admin/proyectos/${proyecto.id}/hitos/${hito.id}/entregables`"
        :cancel-url="`/admin/proyectos/${proyecto.id}/hitos/${hito.id}/entregables`"
        :search-users-endpoint="route('admin.proyectos.search-users')"
      />
    </div>
  </AdminLayout>
</template>
