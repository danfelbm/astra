<script setup lang="ts">
/**
 * HitosEntregablesTabs - Vista de entregables con tabs por estado
 */
import { ref, toRef } from 'vue';
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@modules/Core/Resources/js/components/ui/tabs';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { AlertCircle, Clock, CheckCircle, XCircle, FileText } from 'lucide-vue-next';
import type { Entregable, EstadoEntregable } from '@modules/Proyectos/Resources/js/types/hitos';
import { useEntregablesView, ESTADO_CONFIG, ESTADOS_ORDENADOS } from '@modules/Proyectos/Resources/js/composables/useEntregablesView';
import HitosEntregableCard from './HitosEntregableCard.vue';

// Props
interface Props {
    entregables: Entregable[];
    canEdit?: boolean;
    canDelete?: boolean;
    canComplete?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canEdit: false,
    canDelete: false,
    canComplete: false,
});

// Emits
const emit = defineEmits<{
    'view': [entregable: Entregable];
    'edit': [entregable: Entregable];
    'delete': [entregable: Entregable];
    'complete': [entregable: Entregable];
    'change-status': [entregable: Entregable, nuevoEstado: EstadoEntregable];
    'show-comentarios': [entregable: Entregable];
    'show-actividad': [entregable: Entregable];
}>();

// Composable
const entregablesRef = toRef(props, 'entregables');
const { entregablesAgrupados } = useEntregablesView(entregablesRef);

// Estado local
const expandedIds = ref<Set<number>>(new Set());
const activeTab = ref<EstadoEntregable>('pendiente');

const toggleExpand = (id: number) => {
    if (expandedIds.value.has(id)) {
        expandedIds.value.delete(id);
    } else {
        expandedIds.value.add(id);
    }
};

const isExpanded = (id: number) => expandedIds.value.has(id);

// Configuración de tabs
const tabsConfig: Record<EstadoEntregable, {
    icon: typeof AlertCircle;
    iconClass: string;
    label: string;
    key: keyof typeof entregablesAgrupados.value;
}> = {
    pendiente: { icon: AlertCircle, iconClass: 'text-yellow-600', label: 'Pendientes', key: 'pendientes' },
    en_progreso: { icon: Clock, iconClass: 'text-blue-600', label: 'En Progreso', key: 'en_progreso' },
    completado: { icon: CheckCircle, iconClass: 'text-green-600', label: 'Completados', key: 'completados' },
    cancelado: { icon: XCircle, iconClass: 'text-red-600', label: 'Cancelados', key: 'cancelados' },
};

// Handlers
const handleComplete = (entregable: Entregable) => {
    emit('complete', entregable);
};

const handleChangeStatus = (entregable: Entregable, nuevoEstado: EstadoEntregable) => {
    emit('change-status', entregable, nuevoEstado);
};

// Obtener entregables para un tab
const getEntregablesForTab = (estado: EstadoEntregable): Entregable[] => {
    const config = tabsConfig[estado];
    return entregablesAgrupados.value[config.key];
};
</script>

<template>
    <Tabs v-model="activeTab" class="w-full">
        <!-- Lista de tabs -->
        <TabsList class="w-full grid grid-cols-4 h-auto">
            <TabsTrigger
                v-for="estado in ESTADOS_ORDENADOS"
                :key="estado"
                :value="estado"
                class="gap-1.5 py-2 px-2 data-[state=active]:bg-background"
            >
                <component
                    :is="tabsConfig[estado].icon"
                    class="h-4 w-4 flex-shrink-0"
                    :class="tabsConfig[estado].iconClass"
                />
                <span class="hidden sm:inline text-xs">{{ tabsConfig[estado].label }}</span>
                <Badge variant="secondary" class="ml-1 text-xs px-1.5 py-0">
                    {{ getEntregablesForTab(estado).length }}
                </Badge>
            </TabsTrigger>
        </TabsList>

        <!-- Contenido de cada tab -->
        <TabsContent
            v-for="estado in ESTADOS_ORDENADOS"
            :key="estado"
            :value="estado"
            class="mt-4"
        >
            <div v-if="getEntregablesForTab(estado).length > 0" class="space-y-2">
                <HitosEntregableCard
                    v-for="entregable in getEntregablesForTab(estado)"
                    :key="entregable.id"
                    :entregable="entregable"
                    :can-edit="canEdit"
                    :can-delete="canDelete"
                    :can-complete="canComplete"
                    :expanded="isExpanded(entregable.id)"
                    variant="default"
                    @toggle-expand="toggleExpand(entregable.id)"
                    @view="emit('view', entregable)"
                    @edit="emit('edit', entregable)"
                    @delete="emit('delete', entregable)"
                    @complete="handleComplete(entregable)"
                    @change-status="(nuevoEstado) => handleChangeStatus(entregable, nuevoEstado)"
                    @show-comentarios="emit('show-comentarios', entregable)"
                    @show-actividad="emit('show-actividad', entregable)"
                />
            </div>

            <!-- Estado vacío del tab -->
            <div v-else class="text-center py-8 border rounded-lg bg-muted/30">
                <component
                    :is="tabsConfig[estado].icon"
                    class="h-10 w-10 mx-auto mb-3"
                    :class="tabsConfig[estado].iconClass + '/50'"
                />
                <p class="text-muted-foreground text-sm">
                    No hay entregables {{ tabsConfig[estado].label.toLowerCase() }}
                </p>
            </div>
        </TabsContent>
    </Tabs>
</template>
