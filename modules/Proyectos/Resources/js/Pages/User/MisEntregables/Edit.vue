<script setup lang="ts">
// Página para editar un entregable (User)
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import UserLayout from '@modules/Core/Resources/js/layouts/UserLayout.vue';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { ArrowLeft, AlertCircle } from 'lucide-vue-next';
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
  <Head :title="`Editar Entregable - ${entregable.nombre}`" />

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
            Editar Entregable
          </h1>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Actualiza la información del entregable
          </p>
        </div>
      </div>

      <!-- Información del entregable -->
      <Alert class="mb-6">
        <AlertCircle class="h-4 w-4" />
        <AlertDescription>
          <div class="flex flex-col gap-1">
            <span><strong>ID del Entregable:</strong> #{{ entregable.id }}</span>
            <span><strong>Creado:</strong> {{ new Date(entregable.created_at).toLocaleDateString('es-ES') }}</span>
            <span><strong>Última actualización:</strong> {{ new Date(entregable.updated_at).toLocaleDateString('es-ES') }}</span>
          </div>
        </AlertDescription>
      </Alert>

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
