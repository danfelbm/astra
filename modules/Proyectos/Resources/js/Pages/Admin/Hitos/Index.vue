<script setup lang="ts">
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import HitoCard from '@modules/Proyectos/Resources/js/components/HitoCard.vue';
import { Plus, Search, Filter, ArrowLeft, Target, Network, List } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { ref, computed } from 'vue';
import type { Hito } from '@modules/Proyectos/Resources/js/types/hitos';
import type { BreadcrumbItem } from '@/types';

interface Proyecto {
    id: number;
    nombre: string;
    estado: string;
}

interface Props {
    proyecto: Proyecto;
    hitos: Hito[] | {
        data: Hito[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters?: {
        search?: string;
        estado?: string;
        responsable_id?: number;
    };
    estadisticas?: {
        total: number;
        pendientes: number;
        en_progreso: number;
        completados: number;
        vencidos: number;
        progreso_general: number;
    };
    timeline?: any[];
    canCreate?: boolean;
    canEdit?: boolean;
    canDelete?: boolean;
    canManageDeliverables?: boolean;
}

const props = defineProps<Props>();

// Normalizar hitos a un formato consistente
const hitosData = computed(() => {
  if (Array.isArray(props.hitos)) {
    // Si es un array directo, crear estructura paginada falsa
    return {
      data: props.hitos,
      current_page: 1,
      last_page: 1,
      per_page: props.hitos.length,
      total: props.hitos.length,
    };
  }
  return props.hitos;
});

// Estado local
const searchQuery = ref(props.filters?.search || '');
const selectedEstado = ref(props.filters?.estado || '');
const vistaJerarquica = ref(false);

// Organizar hitos por jerarquía
const hitosJerarquicos = computed(() => {
  if (!vistaJerarquica.value) {
    return hitosData.value.data;
  }

  // Obtener solo los hitos raíz (sin padre)
  const raices = hitosData.value.data.filter(h => !h.parent_id);

  // Función recursiva para construir el árbol
  const buildTree = (parentId: number | null, nivel: number = 0): Hito[] => {
    return hitosData.value.data
      .filter(h => h.parent_id === parentId)
      .flatMap(h => [
        { ...h, _nivel: nivel },
        ...buildTree(h.id, nivel + 1)
      ]);
  };

  // Construir árbol completo
  return raices.flatMap(raiz => [
    { ...raiz, _nivel: 0 },
    ...buildTree(raiz.id, 1)
  ]);
});

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Proyectos', href: '/admin/proyectos' },
    { title: props.proyecto.nombre, href: `/admin/proyectos/${props.proyecto.id}` },
    { title: 'Hitos', href: `/admin/proyectos/${props.proyecto.id}/hitos` },
];

// Métodos de navegación
const navigateToHito = (hito: Hito) => {
    router.visit(`/admin/proyectos/${props.proyecto.id}/hitos/${hito.id}`);
};

const navigateToEditHito = (hito: Hito) => {
    router.visit(`/admin/proyectos/${props.proyecto.id}/hitos/${hito.id}/edit`);
};

const confirmDeleteHito = (hito: Hito) => {
    if (confirm(`¿Estás seguro de eliminar el hito "${hito.nombre}"?`)) {
        router.delete(`/admin/proyectos/${props.proyecto.id}/hitos/${hito.id}`, {
            onSuccess: () => {
                toast.success('Hito eliminado exitosamente');
            },
            onError: () => {
                toast.error('Error al eliminar el hito');
            }
        });
    }
};

const duplicateHito = (hito: Hito) => {
    router.post(`/admin/proyectos/${props.proyecto.id}/hitos/${hito.id}/duplicar`, {}, {
        onSuccess: () => {
            toast.success('Hito duplicado exitosamente');
        },
        onError: () => {
            toast.error('Error al duplicar el hito');
        }
    });
};

const navigateToAddEntregable = (hito: Hito) => {
    router.visit(`/admin/proyectos/${props.proyecto.id}/hitos/${hito.id}/entregables/create`);
};

const navigateToEntregables = (hito: Hito) => {
    router.visit(`/admin/proyectos/${props.proyecto.id}/hitos/${hito.id}/entregables`);
};

// Búsqueda
const handleSearch = () => {
    router.get(`/admin/proyectos/${props.proyecto.id}/hitos`, {
        search: searchQuery.value,
        estado: selectedEstado.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Filtrar por estado
const filterByEstado = (estado: string) => {
    selectedEstado.value = estado;
    handleSearch();
};
</script>

<template>
    <Head :title="`Hitos - ${proyecto.nombre}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Hitos del Proyecto
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ proyecto.nombre }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="`/admin/proyectos/${proyecto.id}`">
                        <Button variant="outline" size="sm">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Volver al Proyecto
                        </Button>
                    </Link>
                    <Link v-if="canCreate" :href="`/admin/proyectos/${proyecto.id}/hitos/create`">
                        <Button size="sm">
                            <Plus class="h-4 w-4 mr-2" />
                            Nuevo Hito
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Estadísticas -->
            <div v-if="estadisticas" class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <Card>
                    <CardContent class="p-4">
                        <p class="text-2xl font-bold">{{ estadisticas.total }}</p>
                        <p class="text-xs text-muted-foreground">Total</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-2xl font-bold text-gray-500">{{ estadisticas.pendientes }}</p>
                        <p class="text-xs text-muted-foreground">Pendientes</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-2xl font-bold text-blue-500">{{ estadisticas.en_progreso }}</p>
                        <p class="text-xs text-muted-foreground">En Progreso</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-2xl font-bold text-green-500">{{ estadisticas.completados }}</p>
                        <p class="text-xs text-muted-foreground">Completados</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-2xl font-bold" :class="{'text-red-500': estadisticas.vencidos > 0}">
                            {{ estadisticas.vencidos }}
                        </p>
                        <p class="text-xs text-muted-foreground">Vencidos</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Filtros -->
            <div class="flex flex-col sm:flex-row gap-2">
                <div class="flex-1">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Buscar hitos..."
                            class="pl-9"
                            @keyup.enter="handleSearch"
                        />
                    </div>
                </div>
                <div class="flex gap-2">
                    <!-- Toggle Vista Jerárquica -->
                    <Button
                        variant="outline"
                        size="sm"
                        :class="{'bg-primary text-primary-foreground': vistaJerarquica}"
                        @click="vistaJerarquica = !vistaJerarquica"
                        title="Cambiar vista"
                    >
                        <Network v-if="vistaJerarquica" class="h-4 w-4 mr-2" />
                        <List v-else class="h-4 w-4 mr-2" />
                        {{ vistaJerarquica ? 'Jerárquica' : 'Plana' }}
                    </Button>

                    <Button
                        variant="outline"
                        size="sm"
                        :class="{'bg-primary text-primary-foreground': selectedEstado === ''}"
                        @click="filterByEstado('')"
                    >
                        Todos
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :class="{'bg-primary text-primary-foreground': selectedEstado === 'pendiente'}"
                        @click="filterByEstado('pendiente')"
                    >
                        Pendientes
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :class="{'bg-primary text-primary-foreground': selectedEstado === 'en_progreso'}"
                        @click="filterByEstado('en_progreso')"
                    >
                        En Progreso
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :class="{'bg-primary text-primary-foreground': selectedEstado === 'completado'}"
                        @click="filterByEstado('completado')"
                    >
                        Completados
                    </Button>
                </div>
            </div>

            <!-- Lista de Hitos -->
            <div v-if="hitosData.data.length > 0">
                <!-- Vista en Grid (Plana) -->
                <div v-if="!vistaJerarquica" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <HitoCard
                        v-for="hito in hitosData.data"
                        :key="hito.id"
                        :hito="hito"
                        :proyecto-id="proyecto.id"
                        :canEdit="canEdit"
                        :canDelete="canDelete"
                        :canManageDeliverables="canManageDeliverables"
                        :showActions="true"
                        @view="navigateToHito"
                        @edit="navigateToEditHito"
                        @delete="confirmDeleteHito"
                        @duplicate="duplicateHito"
                        @add-entregable="navigateToAddEntregable"
                        @view-entregables="navigateToEntregables"
                    />
                </div>

                <!-- Vista Jerárquica (Lista) -->
                <div v-else class="space-y-3">
                    <div
                        v-for="hito in hitosJerarquicos"
                        :key="hito.id"
                        :style="{ marginLeft: `${(hito._nivel || 0) * 2}rem` }"
                        class="transition-all"
                    >
                        <HitoCard
                            :hito="hito"
                            :proyecto-id="proyecto.id"
                            :canEdit="canEdit"
                            :canDelete="canDelete"
                            :canManageDeliverables="canManageDeliverables"
                            :showActions="true"
                            @view="navigateToHito"
                            @edit="navigateToEditHito"
                            @delete="confirmDeleteHito"
                            @duplicate="duplicateHito"
                            @add-entregable="navigateToAddEntregable"
                            @view-entregables="navigateToEntregables"
                        >
                            <!-- Badge de nivel si es jerárquico -->
                            <template v-if="hito._nivel > 0" #prepend>
                                <Badge variant="outline" class="mr-2">
                                    Nivel {{ hito._nivel }}
                                </Badge>
                            </template>
                        </HitoCard>
                    </div>
                </div>
            </div>

            <!-- Estado vacío -->
            <Card v-else>
                <CardContent class="flex flex-col items-center justify-center py-12">
                    <div class="rounded-full bg-muted p-3 mb-4">
                        <Target class="h-8 w-8 text-muted-foreground" />
                    </div>
                    <h3 class="text-lg font-semibold mb-2">No hay hitos</h3>
                    <p class="text-muted-foreground text-center mb-4">
                        {{ searchQuery ? 'No se encontraron hitos con los filtros aplicados' : 'Comienza creando el primer hito del proyecto' }}
                    </p>
                    <Link v-if="canCreate && !searchQuery" :href="`/admin/proyectos/${proyecto.id}/hitos/create`">
                        <Button>
                            <Plus class="h-4 w-4 mr-2" />
                            Crear Primer Hito
                        </Button>
                    </Link>
                </CardContent>
            </Card>

            <!-- Paginación -->
            <div v-if="hitosData.last_page > 1" class="flex justify-center gap-2 mt-4">
                <Button
                    v-for="page in hitosData.last_page"
                    :key="page"
                    :variant="page === hitosData.current_page ? 'default' : 'outline'"
                    size="sm"
                    @click="router.visit(`/admin/proyectos/${proyecto.id}/hitos?page=${page}`)"
                >
                    {{ page }}
                </Button>
            </div>
        </div>
    </AdminLayout>
</template>