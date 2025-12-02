<script setup lang="ts">
/**
 * ActividadSheet - Sheet lateral para mostrar actividad/audit log.
 * Carga las actividades dinámicamente por API al abrir.
 */
import { ref, watch } from 'vue';
import { Sheet, SheetContent } from '@modules/Core/Resources/js/components/ui/sheet';
import { CardTitle, CardDescription } from '@modules/Core/Resources/js/components/ui/card';
import { Skeleton } from '@modules/Core/Resources/js/components/ui/skeleton';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import ActivityLog from '@modules/Proyectos/Resources/js/components/ActivityLog.vue';
import { Activity, RefreshCw } from 'lucide-vue-next';
import { useActividades } from '@modules/Proyectos/Resources/js/composables/useActividades';

interface Props {
    open: boolean;
    actividadType: 'hitos' | 'entregables';
    actividadId: number;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

// Ref para el ID (necesario para el composable)
const idRef = ref<number | null>(props.actividadId);

// Composable para cargar actividades
const { actividades, loading, error, cargar } = useActividades(props.actividadType, idRef);

// Cargar cuando se abre el sheet (immediate para deeplinks donde ya está abierto)
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        idRef.value = props.actividadId;
        cargar();
    }
}, { immediate: true });

// También recargar si cambia el ID mientras está abierto
watch(() => props.actividadId, (newId) => {
    if (props.open && newId !== idRef.value) {
        idRef.value = newId;
        cargar();
    }
});
</script>

<template>
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent side="right" class="sm:max-w-lg w-full overflow-y-auto">
            <div class="mt-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <Activity class="h-5 w-5" />
                            Actividad
                        </CardTitle>
                        <CardDescription>
                            Historial de cambios y eventos
                        </CardDescription>
                    </div>
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="cargar"
                        :disabled="loading"
                        title="Recargar"
                    >
                        <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
                    </Button>
                </div>

                <!-- Estado de carga -->
                <div v-if="loading && actividades.length === 0" class="space-y-4">
                    <div v-for="i in 5" :key="i" class="flex gap-3">
                        <Skeleton class="h-8 w-8 rounded-full flex-shrink-0" />
                        <div class="flex-1 space-y-2">
                            <Skeleton class="h-4 w-3/4" />
                            <Skeleton class="h-3 w-1/2" />
                        </div>
                    </div>
                </div>

                <!-- Error -->
                <div v-else-if="error" class="text-center py-8 text-destructive">
                    <p>{{ error }}</p>
                    <Button variant="outline" size="sm" class="mt-2" @click="cargar">
                        Reintentar
                    </Button>
                </div>

                <!-- Lista de actividades -->
                <ActivityLog
                    v-else
                    :activities="actividades"
                    :show-card="false"
                    empty-message="No hay actividad registrada"
                />
            </div>
        </SheetContent>
    </Sheet>
</template>
