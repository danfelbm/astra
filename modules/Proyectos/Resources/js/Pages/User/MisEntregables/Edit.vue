<script setup lang="ts">
// Página para editar un entregable (User)
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import UserLayout from '@modules/Core/Resources/js/layouts/UserLayout.vue';
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
  user?: {
    id: number;
    name: string;
    email: string;
  };
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
</script>

<template>
  <UserLayout>
    <Head :title="`Editar Entregable - ${entregable.nombre}`" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Header -->
      <div>
        <div class="text-sm text-muted-foreground mb-1">
          {{ proyecto.nombre }} / {{ hito.nombre }}
        </div>
        <h2 class="text-3xl font-bold tracking-tight">Editar Entregable</h2>
        <p class="text-muted-foreground mt-2">
          Modificar el entregable "{{ entregable.nombre }}"
        </p>
      </div>

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
        :submit-url="`/miembro/mis-proyectos/${proyecto.id}/hitos/${hito.id}/entregables/${entregable.id}`"
        :cancel-url="`/miembro/mis-proyectos/${proyecto.id}?tab=hitos`"
        :search-users-endpoint="route('admin.proyectos.search-users')"
      />
    </div>
  </UserLayout>
</template>
