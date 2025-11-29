<script setup lang="ts">
// Página para crear un nuevo entregable (User)
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import UserLayout from '@modules/Core/Resources/js/layouts/UserLayout.vue';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { ArrowLeft } from 'lucide-vue-next';
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
</script>

<template>
  <Head :title="`Nuevo Entregable - ${hito.nombre}`" />

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
            Nuevo Entregable
          </h1>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Agregar un nuevo entregable al hito
          </p>
        </div>
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
        :submit-url="`/miembro/mis-proyectos/${proyecto.id}/hitos/${hito.id}/entregables`"
        :cancel-url="`/miembro/mis-proyectos/${proyecto.id}?tab=hitos`"
        :search-users-endpoint="route('admin.proyectos.search-users')"
      />
    </div>
  </UserLayout>
</template>
