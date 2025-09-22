<template>
    <div class="space-y-2">
        <!-- Label y contador -->
        <div class="flex justify-between items-center" v-if="label">
            <Label :for="inputId">{{ label }}</Label>
            <span class="text-xs text-muted-foreground">
                {{ selectedEtiquetas.length }} / {{ maxEtiquetas }} seleccionadas
            </span>
        </div>

        <!-- Campo de búsqueda y selección -->
        <Popover v-model:open="open">
            <PopoverTrigger asChild>
                <Button
                    :id="inputId"
                    variant="outline"
                    role="combobox"
                    :aria-expanded="open"
                    :disabled="disabled"
                    class="w-full justify-between"
                >
                    <span class="text-left truncate">
                        {{ placeholder }}
                    </span>
                    <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-full p-0" align="start">
                <Tabs v-model="viewMode" class="w-full">
                    <!-- Tabs para cambiar entre vistas -->
                    <div class="border-b px-3 pt-3">
                        <TabsList class="grid w-full grid-cols-2">
                            <TabsTrigger value="list">
                                <List class="h-4 w-4 mr-2" />
                                Lista
                            </TabsTrigger>
                            <TabsTrigger value="tree">
                                <TreePine class="h-4 w-4 mr-2" />
                                Árbol
                            </TabsTrigger>
                        </TabsList>
                    </div>

                    <!-- Vista de Lista (original) -->
                    <TabsContent value="list" class="mt-0">
                        <Command>
                            <CommandInput
                                placeholder="Buscar etiqueta..."
                                v-model="searchQuery"
                            />
                            <CommandEmpty>
                                <div class="p-2 text-sm">
                                    No se encontraron etiquetas.
                                    <Button
                                        v-if="allowCreate && searchQuery.length > 2"
                                        @click="createNewEtiqueta"
                                        variant="ghost"
                                        size="sm"
                                        class="w-full mt-1"
                                    >
                                        <Plus class="mr-2 h-3 w-3" />
                                        Crear "{{ searchQuery }}"
                                    </Button>
                                </div>
                            </CommandEmpty>
                            <CommandList>
                                <CommandGroup
                                    v-for="categoria in categoriasConEtiquetas"
                                    :key="categoria.id"
                                    :heading="categoria.nombre"
                                >
                                    <CommandItem
                                        v-for="etiqueta in categoria.etiquetas"
                                        :key="etiqueta.id"
                                        :value="etiqueta"
                                        @select="toggleEtiqueta(etiqueta)"
                                        :disabled="!isEtiquetaSelectable(etiqueta)"
                                        class="cursor-pointer"
                                    >
                                        <Check
                                            :class="[
                                                'mr-2 h-4 w-4',
                                                isSelected(etiqueta.id) ? 'opacity-100' : 'opacity-0'
                                            ]"
                                        />
                                        <component
                                            v-if="categoria.icono"
                                            :is="getIcon(categoria.icono)"
                                            class="mr-2 h-3 w-3 opacity-60"
                                        />
                                        {{ etiqueta.nombre }}
                                        <span v-if="etiqueta.parent" class="text-xs text-muted-foreground ml-1">
                                            ({{ etiqueta.parent.nombre }})
                                        </span>
                                        <span
                                            v-if="etiqueta.descripcion"
                                            class="ml-auto text-xs text-muted-foreground"
                                        >
                                            {{ etiqueta.descripcion }}
                                        </span>
                                    </CommandItem>
                                </CommandGroup>
                            </CommandList>
                        </Command>
                    </TabsContent>

                    <!-- Vista de Árbol -->
                    <TabsContent value="tree" class="mt-0">
                        <div class="p-2">
                            <Input
                                placeholder="Buscar en árbol..."
                                v-model="treeSearchQuery"
                                class="mb-2"
                            />
                            <div class="max-h-[300px] overflow-y-auto border rounded-lg p-2">
                                <div v-if="arbolFiltrado.length === 0" class="p-4 text-center text-sm text-muted-foreground">
                                    No se encontraron etiquetas
                                </div>
                                <div v-else>
                                    <EtiquetaSelectorTreeItem
                                        v-for="nodo in arbolFiltrado"
                                        :key="nodo.id"
                                        :etiqueta="nodo"
                                        :nivel="0"
                                        :selected-ids="modelValue"
                                        :expandidos="expandidos"
                                        :is-selectable="isEtiquetaSelectable"
                                        @toggle-expand="toggleExpansion"
                                        @toggle-select="toggleEtiqueta"
                                    />
                                </div>
                            </div>
                        </div>
                    </TabsContent>
                </Tabs>
            </PopoverContent>
        </Popover>

        <!-- Etiquetas seleccionadas -->
        <div v-if="selectedEtiquetas.length > 0" class="flex flex-wrap gap-1.5">
            <Badge
                v-for="etiqueta in selectedEtiquetas"
                :key="etiqueta.id"
                variant="secondary"
                class="pr-1"
            >
                <component
                    v-if="etiqueta.categoria?.icono"
                    :is="getIcon(etiqueta.categoria.icono)"
                    class="mr-1 h-3 w-3"
                />
                {{ etiqueta.nombre }}
                <button
                    @click="removeEtiqueta(etiqueta.id)"
                    :disabled="disabled"
                    class="ml-1 ring-offset-background rounded-full outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                >
                    <X class="h-3 w-3 hover:opacity-80" />
                </button>
            </Badge>
        </div>

        <!-- Descripción de ayuda -->
        <p v-if="description" class="text-sm text-muted-foreground">
            {{ description }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@modules/Core/Resources/js/components/ui/popover';
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@modules/Core/Resources/js/components/ui/tabs';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@modules/Core/Resources/js/components/ui/command';
import {
    Check, ChevronsUpDown, X, Plus, List, TreePine, ChevronRight
} from 'lucide-vue-next';
import type { Etiqueta, CategoriaEtiqueta } from '../types/etiquetas';
import { useEtiquetaHierarchy } from '../composables/useEtiquetaHierarchy';
import EtiquetaSelectorTreeItem from './EtiquetaSelectorTreeItem.vue';
import {
    Tag, Hash, Bookmark, Flag, Star, Heart,
    Zap, Target, Award, TrendingUp, Folder,
    Package, Box, Layers, Grid
} from 'lucide-vue-next';

// Props del componente
interface Props {
    modelValue: number[]; // Array de IDs de etiquetas seleccionadas
    categorias: CategoriaEtiqueta[]; // Categorías disponibles con sus etiquetas
    maxEtiquetas?: number; // Límite de etiquetas
    placeholder?: string;
    disabled?: boolean;
    allowCreate?: boolean; // Permitir crear nuevas etiquetas
    label?: string;
    description?: string;
}

const props = withDefaults(defineProps<Props>(), {
    maxEtiquetas: 10,
    placeholder: 'Seleccionar etiquetas...',
    disabled: false,
    allowCreate: false,
    label: '',
    description: ''
});

const emit = defineEmits<{
    'update:modelValue': [value: number[]];
    'create': [nombre: string, categoriaId: number];
}>();

// Estado local
const open = ref(false);
const searchQuery = ref('');
const treeSearchQuery = ref('');
const viewMode = ref<'list' | 'tree'>('list');
const inputId = `etiqueta-selector-${Math.random().toString(36).substr(2, 9)}`;

// Estado para vista de árbol
const arbolEtiquetas = ref<Etiqueta[]>([]);
const {
    etiquetas,
    expandidos,
    construirArbol,
    filtrarArbol,
    toggleExpansion,
    expandirTodos
} = useEtiquetaHierarchy(arbolEtiquetas);

// Computed para filtrar categorías y etiquetas según búsqueda
const categoriasConEtiquetas = computed(() => {
    if (!searchQuery.value) {
        return props.categorias;
    }

    const query = searchQuery.value.toLowerCase();

    return props.categorias
        .map(categoria => ({
            ...categoria,
            etiquetas: categoria.etiquetas?.filter(etiqueta =>
                etiqueta.nombre.toLowerCase().includes(query) ||
                etiqueta.descripcion?.toLowerCase().includes(query)
            ) || []
        }))
        .filter(categoria => categoria.etiquetas.length > 0);
});

// Computed para obtener las etiquetas seleccionadas completas
const selectedEtiquetas = computed(() => {
    const etiquetas: Etiqueta[] = [];

    props.categorias.forEach(categoria => {
        categoria.etiquetas?.forEach(etiqueta => {
            if (props.modelValue.includes(etiqueta.id)) {
                etiquetas.push({ ...etiqueta, categoria });
            }
        });
    });

    return etiquetas;
});

// Funciones para manejar la selección
function isSelected(etiquetaId: number): boolean {
    return props.modelValue.includes(etiquetaId);
}

function isEtiquetaSelectable(etiqueta: Etiqueta): boolean {
    if (props.disabled) return false;
    if (isSelected(etiqueta.id)) return true; // Siempre permitir deseleccionar
    return props.modelValue.length < props.maxEtiquetas;
}

function toggleEtiqueta(etiqueta: Etiqueta) {
    const etiquetaId = etiqueta.id;
    let newValue = [...props.modelValue];

    if (isSelected(etiquetaId)) {
        // Quitar etiqueta
        newValue = newValue.filter(id => id !== etiquetaId);
    } else if (newValue.length < props.maxEtiquetas) {
        // Agregar etiqueta
        newValue.push(etiquetaId);
    } else {
        // Alcanzó el límite
        return;
    }

    emit('update:modelValue', newValue);
}

function removeEtiqueta(etiquetaId: number) {
    const newValue = props.modelValue.filter(id => id !== etiquetaId);
    emit('update:modelValue', newValue);
}

// Función para crear nueva etiqueta
function createNewEtiqueta() {
    if (!props.allowCreate || !searchQuery.value) return;

    // Emitir evento para que el padre maneje la creación
    // Por defecto, usar la primera categoría
    const defaultCategoriaId = props.categorias[0]?.id;
    if (defaultCategoriaId) {
        emit('create', searchQuery.value, defaultCategoriaId);
        searchQuery.value = '';
        open.value = false;
    }
}

// Función para obtener el componente de icono
function getIcon(iconName: string) {
    const icons: Record<string, any> = {
        Tag, Hash, Bookmark, Flag, Star, Heart,
        Zap, Target, Award, TrendingUp, Folder,
        Package, Box, Layers, Grid
    };

    return icons[iconName] || Tag;
}

// Actualizar placeholder dinámicamente
const placeholder = computed(() => {
    if (selectedEtiquetas.value.length === 0) {
        return props.placeholder;
    }

    if (selectedEtiquetas.value.length === 1) {
        return selectedEtiquetas.value[0].nombre;
    }

    return `${selectedEtiquetas.value.length} etiquetas seleccionadas`;
});

// Computed para árbol filtrado
const arbolFiltrado = computed(() => {
    if (!treeSearchQuery.value) return arbolEtiquetas.value;
    return filtrarArbol(treeSearchQuery.value, arbolEtiquetas.value);
});

// Construir árbol cuando se abra el selector
watch(open, (newValue) => {
    if (newValue && viewMode.value === 'tree') {
        construirArbolDesdeCategarias();
    }
});

watch(viewMode, (newValue) => {
    if (newValue === 'tree' && open.value) {
        construirArbolDesdeCategarias();
    }
});

// Función para construir el árbol desde las categorías
function construirArbolDesdeCategarias() {
    const todasLasEtiquetas: Etiqueta[] = [];
    props.categorias.forEach(categoria => {
        if (categoria.etiquetas) {
            categoria.etiquetas.forEach(etiqueta => {
                todasLasEtiquetas.push({ ...etiqueta, categoria });
            });
        }
    });
    arbolEtiquetas.value = construirArbol(todasLasEtiquetas);
    // Expandir primer nivel por defecto
    arbolEtiquetas.value.forEach(raiz => {
        if (raiz.children && raiz.children.length > 0) {
            expandidos.value.add(raiz.id);
        }
    });
}

</script>

<style scoped>
/* Transiciones suaves para badges */
.badge-enter-active,
.badge-leave-active {
    transition: all 0.2s ease;
}

.badge-enter-from {
    opacity: 0;
    transform: scale(0.9);
}

.badge-leave-to {
    opacity: 0;
    transform: scale(0.9);
}
</style>