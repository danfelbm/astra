<template>
    <div class="etiqueta-tree-view">
        <!-- Controles del árbol -->
        <div class="mb-4 flex gap-2 items-center">
            <Input
                v-model="busqueda"
                placeholder="Buscar etiquetas..."
                class="max-w-sm"
                @input="debouncedSearch"
            >
                <template #prefix>
                    <Search class="h-4 w-4 text-muted-foreground" />
                </template>
            </Input>

            <Button
                variant="outline"
                size="sm"
                @click="expandirTodos"
            >
                <ChevronDown class="h-4 w-4 mr-1" />
                Expandir todo
            </Button>

            <Button
                variant="outline"
                size="sm"
                @click="colapsarTodos"
            >
                <ChevronRight class="h-4 w-4 mr-1" />
                Colapsar todo
            </Button>

            <div class="ml-auto flex items-center gap-2">
                <Badge variant="secondary">{{ estadisticas.totalNodos }} etiquetas</Badge>
                <Badge variant="outline">{{ estadisticas.profundidadMaxima }} niveles</Badge>
            </div>
        </div>

        <!-- Árbol de etiquetas -->
        <div class="border rounded-lg p-4 bg-white dark:bg-gray-800">
            <div v-if="loading" class="flex justify-center py-8">
                <Loader2 class="h-8 w-8 animate-spin text-muted-foreground" />
            </div>

            <div v-else-if="arbolFiltrado.length === 0" class="text-center py-8 text-muted-foreground">
                No se encontraron etiquetas
            </div>

            <div v-else class="space-y-1">
                <EtiquetaTreeNode
                    v-for="nodo in arbolFiltrado"
                    :key="nodo.id"
                    :etiqueta="nodo"
                    :nivel="0"
                    :expandidos="expandidos"
                    :drag-drop-helpers="dragDropHelpers"
                    @toggle="toggleExpansion"
                    @edit="$emit('edit', $event)"
                    @delete="$emit('delete', $event)"
                    @drop="handleDrop"
                />
            </div>
        </div>

        <!-- Estadísticas del árbol -->
        <div v-if="showStats" class="mt-4 grid grid-cols-4 gap-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Total</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ estadisticas.totalNodos }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Raíces</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ estadisticas.totalRaices }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Con hijos</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ estadisticas.totalConHijos }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Profundidad</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ estadisticas.profundidadMaxima }}</div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Search, ChevronDown, ChevronRight, Loader2 } from 'lucide-vue-next';
import EtiquetaTreeNode from './EtiquetaTreeNode.vue';
import { useEtiquetaHierarchy } from '../composables/useEtiquetaHierarchy';
import type { Etiqueta } from '../types/etiquetas';
import axios from 'axios';
import { toast } from 'vue-sonner';

interface Props {
    etiquetas?: Etiqueta[];
    categoriaId?: number | null;
    showStats?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showStats: false
});

const emit = defineEmits<{
    edit: [etiqueta: Etiqueta];
    delete: [etiqueta: Etiqueta];
    moved: [data: { etiquetaId: number, nuevoPadreId: number | null }];
}>();

const loading = ref(false);
const busqueda = ref('');
const etiquetasData = ref<Etiqueta[]>([]);

const {
    etiquetas,
    expandidos,
    estadisticas,
    construirArbol,
    filtrarArbol,
    validarRelacionPadreHijo,
    toggleExpansion,
    expandirTodos,
    colapsarTodos,
    dragDropHelpers
} = useEtiquetaHierarchy(etiquetasData);

// Cargar etiquetas si no se proporcionan
const cargarEtiquetas = async () => {
    if (props.etiquetas) {
        etiquetasData.value = construirArbol(props.etiquetas);
        return;
    }

    loading.value = true;
    try {
        const response = await axios.get('/admin/etiquetas/arbol', {
            params: {
                categoria_id: props.categoriaId
            }
        });

        if (response.data.success) {
            etiquetasData.value = response.data.arbol;
        }
    } catch (error) {
        console.error('Error cargando árbol de etiquetas:', error);
        toast.error('Error al cargar las etiquetas');
    } finally {
        loading.value = false;
    }
};

// Árbol filtrado por búsqueda
const arbolFiltrado = computed(() => {
    if (!busqueda.value) return etiquetas.value;
    return filtrarArbol(busqueda.value, etiquetas.value);
});

// Búsqueda con debounce
let searchTimeout: NodeJS.Timeout;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        // La búsqueda se aplica automáticamente a través del computed
    }, 300);
};

// Manejar drop con llamada al API
const handleDrop = async (data: { hijoId: number, padreId: number | null }) => {
    if (!data.valido) return;

    loading.value = true;
    try {
        const response = await axios.post(`/admin/etiquetas/${data.hijoId}/establecer-padre`, {
            parent_id: data.padreId
        });

        if (response.data.success) {
            toast.success('Etiqueta movida exitosamente');
            emit('moved', {
                etiquetaId: data.hijoId,
                nuevoPadreId: data.padreId
            });
            // Recargar árbol
            await cargarEtiquetas();
        }
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Error al mover la etiqueta');
    } finally {
        loading.value = false;
    }
};

// Watch para cambios en props
watch(() => props.etiquetas, (nuevasEtiquetas) => {
    if (nuevasEtiquetas) {
        etiquetasData.value = construirArbol(nuevasEtiquetas);
    }
});

onMounted(() => {
    cargarEtiquetas();
});
</script>

<style scoped>
.etiqueta-tree-view {
    width: 100%;
}

.tree-node {
    user-select: none;
}
</style>