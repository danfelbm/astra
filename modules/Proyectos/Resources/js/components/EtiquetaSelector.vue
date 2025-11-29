<template>
    <div class="space-y-2">
        <!-- Label y contador -->
        <div class="flex justify-between items-center" v-if="label">
            <Label :for="inputId">{{ label }}</Label>
            <span class="text-xs text-muted-foreground">
                {{ selectedEtiquetas.length }} / {{ maxEtiquetas }} seleccionadas
            </span>
        </div>

        <!-- Trigger que abre el modal -->
        <Button
            :id="inputId"
            type="button"
            @click="open = true"
            variant="outline"
            role="combobox"
            :aria-expanded="open"
            :disabled="disabled"
            class="w-full justify-between"
        >
            <span class="text-left truncate">
                {{ placeholderText }}
            </span>
            <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>

        <!-- Modal Dialog -->
        <Dialog v-model:open="open">
            <DialogContent class="max-w-2xl max-h-[80vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle>Seleccionar Etiquetas</DialogTitle>
                    <DialogDescription>
                        Selecciona hasta {{ maxEtiquetas }} etiquetas para categorizar
                    </DialogDescription>
                </DialogHeader>

                <!-- Buscador -->
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="searchQuery"
                        placeholder="Buscar etiqueta..."
                        class="pl-10"
                    />
                </div>

                <!-- Lista con categorías colapsables -->
                <div class="flex-1 overflow-y-auto border rounded-lg min-h-[200px] max-h-[400px]">
                    <!-- Sin resultados -->
                    <div v-if="categoriasConEtiquetas.length === 0" class="p-8 text-center">
                        <Tag class="h-12 w-12 mx-auto mb-3 text-muted-foreground" />
                        <p class="text-muted-foreground">
                            {{ searchQuery ? 'No se encontraron etiquetas' : 'No hay etiquetas disponibles' }}
                        </p>
                        <!-- Opción de crear nueva etiqueta -->
                        <Button
                            v-if="allowCreate && searchQuery.length > 2"
                            type="button"
                            @click="createNewEtiqueta"
                            variant="ghost"
                            size="sm"
                            class="mt-2"
                        >
                            <Plus class="mr-2 h-3 w-3" />
                            Crear "{{ searchQuery }}"
                        </Button>
                    </div>

                    <!-- Categorías con Collapsible -->
                    <div v-else class="p-2 space-y-1">
                        <Collapsible
                            v-for="categoria in categoriasConEtiquetas"
                            :key="categoria.id"
                            v-model:open="expandedCategories[categoria.id]"
                        >
                            <CollapsibleTrigger class="flex items-center justify-between w-full p-3 hover:bg-muted/50 rounded-lg transition-colors">
                                <div class="flex items-center gap-2">
                                    <component
                                        v-if="categoria.icono"
                                        :is="getIcon(categoria.icono)"
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <span class="font-medium">{{ categoria.nombre }}</span>
                                    <Badge variant="secondary" class="text-xs">
                                        {{ categoria.etiquetas?.length || 0 }}
                                    </Badge>
                                </div>
                                <ChevronDown
                                    class="h-4 w-4 text-muted-foreground transition-transform duration-200"
                                    :class="{ 'rotate-180': expandedCategories[categoria.id] }"
                                />
                            </CollapsibleTrigger>

                            <CollapsibleContent>
                                <div class="ml-4 space-y-1 pb-2">
                                    <div
                                        v-for="etiqueta in categoria.etiquetas"
                                        :key="etiqueta.id"
                                        @click="toggleEtiqueta(etiqueta)"
                                        :class="[
                                            'flex items-center gap-3 p-2 rounded-md cursor-pointer transition-colors',
                                            isSelected(etiqueta.id) ? 'bg-primary/10' : 'hover:bg-muted',
                                            !isEtiquetaSelectable(etiqueta) && !isSelected(etiqueta.id) && 'opacity-50 cursor-not-allowed'
                                        ]"
                                    >
                                        <Checkbox
                                            :checked="isSelected(etiqueta.id)"
                                            :disabled="!isEtiquetaSelectable(etiqueta)"
                                            @click.stop
                                        />
                                        <span class="flex-1">{{ etiqueta.nombre }}</span>
                                        <span v-if="etiqueta.parent" class="text-xs text-muted-foreground">
                                            ({{ etiqueta.parent.nombre }})
                                        </span>
                                        <span
                                            v-if="etiqueta.descripcion"
                                            class="text-xs text-muted-foreground truncate max-w-[200px]"
                                        >
                                            {{ etiqueta.descripcion }}
                                        </span>
                                    </div>
                                </div>
                            </CollapsibleContent>
                        </Collapsible>
                    </div>
                </div>

                <DialogFooter>
                    <div class="flex items-center justify-between w-full">
                        <span class="text-sm text-muted-foreground">
                            {{ selectedEtiquetas.length }} / {{ maxEtiquetas }} seleccionadas
                        </span>
                        <div class="flex gap-2">
                            <Button type="button" variant="outline" @click="open = false">
                                Cancelar
                            </Button>
                            <Button type="button" @click="open = false">
                                Aceptar
                            </Button>
                        </div>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Etiquetas seleccionadas como badges -->
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
/**
 * EtiquetaSelector - Selector de etiquetas con modal y categorías colapsables
 *
 * Permite seleccionar múltiples etiquetas organizadas por categorías.
 * Las categorías son colapsables y se expanden por defecto al abrir el modal.
 */
import { ref, computed, watch } from 'vue';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { Checkbox } from '@modules/Core/Resources/js/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@modules/Core/Resources/js/components/ui/dialog';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@modules/Core/Resources/js/components/ui/collapsible';
import {
    ChevronsUpDown, X, Plus, Search, ChevronDown,
    Tag, Hash, Bookmark, Flag, Star, Heart,
    Zap, Target, Award, TrendingUp, Folder,
    Package, Box, Layers, Grid
} from 'lucide-vue-next';
import type { Etiqueta, CategoriaEtiqueta } from '../types/etiquetas';

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
const inputId = `etiqueta-selector-${Math.random().toString(36).substr(2, 9)}`;

// Estado para categorías expandidas
const expandedCategories = ref<Record<number, boolean>>({});

// Inicializar todas las categorías expandidas cuando se abre el modal
watch(open, (newValue) => {
    if (newValue) {
        // Expandir todas las categorías por defecto
        props.categorias.forEach(cat => {
            expandedCategories.value[cat.id] = true;
        });
        // Limpiar búsqueda al abrir
        searchQuery.value = '';
    }
});

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

// Computed para el texto del placeholder
const placeholderText = computed(() => {
    if (selectedEtiquetas.value.length === 0) {
        return props.placeholder;
    }

    if (selectedEtiquetas.value.length === 1) {
        return selectedEtiquetas.value[0].nombre;
    }

    return `${selectedEtiquetas.value.length} etiquetas seleccionadas`;
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
