<script setup lang="ts">
// Página para crear un nuevo entregable (User)
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import UserLayout from '@modules/Core/Resources/js/layouts/UserLayout.vue';
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

// Endpoint de búsqueda de usuarios - usamos el mismo del admin ya que funciona
const searchUsersEndpoint = '/admin/proyectos-search-users';
</script>

<template>
  <UserLayout>
    <Head :title="`Nuevo Entregable - ${hito.nombre}`" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Header -->
      <div>
        <div class="text-sm text-muted-foreground mb-1">
          {{ proyecto.nombre }} / {{ hito.nombre }}
        </div>
        <h2 class="text-3xl font-bold tracking-tight">Nuevo Entregable</h2>
        <p class="text-muted-foreground mt-2">
          Agregar un nuevo entregable al hito "{{ hito.nombre }}"
        </p>
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
        :search-users-endpoint="searchUsersEndpoint"
      />
    </div>
  </UserLayout>
</template>
