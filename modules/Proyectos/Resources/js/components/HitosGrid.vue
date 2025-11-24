<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Progress } from '@modules/Core/Resources/js/components/ui/progress';
import { Plus, ListTodo, Target } from 'lucide-vue-next';
import HitoCard from './HitoCard.vue';
import type { Hito } from '@modules/Proyectos/Resources/js/types/hitos';

// Props
interface Props {
    hitos: Hito[];
    proyectoId: number;
    proyectoNombre?: string;
    estadisticas?: {
        total: number;
        pendientes: number;
        en_progreso: number;
        completados: number;
        vencidos: number;
        progreso_general: number;
    };
    showStats?: boolean;
    showHeader?: boolean;
    showActions?: boolean;
    showViewAll?: boolean;
    maxHitos?: number; // Límite de hitos a mostrar
    canCreate?: boolean;
    canEdit?: boolean;
    canDelete?: boolean;
    canManageDeliverables?: boolean;
    emptyMessage?: string;
}

const props = withDefaults(defineProps<Props>(), {
    showStats: true,
    showHeader: true,
    showActions: true,
    showViewAll: true,
    maxHitos: undefined,
    canCreate: false,
    canEdit: false,
    canDelete: false,
    canManageDeliverables: false,
    emptyMessage: 'No hay hitos definidos para este proyecto'
});

// Emits
defineEmits<{
    view: [hito: Hito];
    edit: [hito: Hito];
    delete: [hito: Hito];
    duplicate: [hito: Hito];
    'add-entregable': [hito: Hito];
    'view-entregables': [hito: Hito];
}>();

// Hitos a mostrar (con límite opcional)
const hitosToShow = computed(() => {
    if (props.maxHitos && props.maxHitos > 0) {
        return props.hitos.slice(0, props.maxHitos);
    }
    return props.hitos;
});

// Verificar si hay más hitos que el límite
const hasMoreHitos = computed(() => {
    return props.maxHitos && props.hitos.length > props.maxHitos;
});
</script>

<template>
    <Card>
        <CardHeader v-if="showHeader" class="flex flex-row items-center justify-between space-y-0 pb-2">
            <div>
                <CardTitle class="flex items-center gap-2">
                    <Target class="h-5 w-5" />
                    Hitos y Entregables
                </CardTitle>
                <CardDescription v-if="proyectoNombre">
                    {{ proyectoNombre }}
                </CardDescription>
            </div>
            <div v-if="showActions" class="flex items-center gap-2">
                <Link
                    v-if="showViewAll && hitos.length > 0"
                    :href="`/admin/proyectos/${proyectoId}/hitos`"
                >
                    <Button variant="outline" size="sm">
                        <ListTodo class="h-4 w-4 mr-2" />
                        Ver Todos
                    </Button>
                </Link>
                <Link
                    v-if="canCreate"
                    :href="`/admin/proyectos/${proyectoId}/hitos/create`"
                >
                    <Button size="sm">
                        <Plus class="h-4 w-4 mr-2" />
                        Nuevo Hito
                    </Button>
                </Link>
            </div>
        </CardHeader>

        <CardContent>
            <!-- Estadísticas de Hitos -->
            <div v-if="showStats && estadisticas && estadisticas.total > 0" class="mb-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div class="text-center">
                        <p class="text-2xl font-bold">{{ estadisticas.total }}</p>
                        <p class="text-xs text-muted-foreground">Total Hitos</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-blue-500">{{ estadisticas.en_progreso }}</p>
                        <p class="text-xs text-muted-foreground">En Progreso</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-green-500">{{ estadisticas.completados }}</p>
                        <p class="text-xs text-muted-foreground">Completados</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold" :class="{'text-red-500': estadisticas.vencidos > 0}">
                            {{ estadisticas.vencidos }}
                        </p>
                        <p class="text-xs text-muted-foreground">Vencidos</p>
                    </div>
                </div>

                <!-- Barra de progreso general -->
                <div class="space-y-2">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-muted-foreground">Progreso General</span>
                        <span class="font-medium">{{ estadisticas.progreso_general }}%</span>
                    </div>
                    <Progress :value="estadisticas.progreso_general" class="h-2" />
                </div>
            </div>

            <!-- Lista de Hitos (1 columna) -->
            <div v-if="hitos.length > 0" class="space-y-3">
                <HitoCard
                    v-for="hito in hitosToShow"
                    :key="hito.id"
                    :hito="hito"
                    :canEdit="canEdit"
                    :canDelete="canDelete"
                    :canManageDeliverables="canManageDeliverables"
                    :showActions="true"
                    :compact="true"
                    @view="$emit('view', hito)"
                    @edit="$emit('edit', hito)"
                    @delete="$emit('delete', hito)"
                    @duplicate="$emit('duplicate', hito)"
                    @add-entregable="$emit('add-entregable', hito)"
                    @view-entregables="$emit('view-entregables', hito)"
                />

                <!-- Link para ver todos -->
                <Link
                    v-if="hasMoreHitos"
                    :href="`/admin/proyectos/${proyectoId}/hitos`"
                    class="block text-center mt-4 text-sm text-primary hover:underline"
                >
                    Ver todos los hitos ({{ hitos.length }})
                </Link>
            </div>

            <!-- Estado vacío -->
            <div v-else class="text-center py-6">
                <Target class="h-10 w-10 mx-auto text-muted-foreground mb-3" />
                <p class="text-muted-foreground mb-3">{{ emptyMessage }}</p>
                <Link
                    v-if="canCreate"
                    :href="`/admin/proyectos/${proyectoId}/hitos/create`"
                >
                    <Button variant="outline" size="sm">
                        <Plus class="h-4 w-4 mr-2" />
                        Crear Primer Hito
                    </Button>
                </Link>
            </div>
        </CardContent>
    </Card>
</template>
