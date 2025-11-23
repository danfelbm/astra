<script setup lang="ts">
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from "@modules/Core/Resources/js/components/ui/table";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from "@modules/Core/Resources/js/components/ui/select";
import { Plus, Edit, Trash2, Save, X, Tag, Palette, ChevronDown, ChevronRight, TreePine, List } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import type { CategoriaEtiqueta, Etiqueta } from '@modules/Proyectos/Resources/js/types/etiquetas';
import EtiquetaHierarchySelector from '@modules/Proyectos/Resources/js/components/EtiquetaHierarchySelector.vue';
import EtiquetaTreeView from '@modules/Proyectos/Resources/js/components/EtiquetaTreeView.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@modules/Core/Resources/js/components/ui/tabs";

// Props
interface Props {
    categorias: {
        data: CategoriaEtiqueta[];
        current_page: number;
        last_page: number;
        total: number;
    };
    estadisticas?: {
        total_categorias: number;
        total_etiquetas: number;
        categorias_activas: number;
    };
    colores: Record<string, string>;
    iconos: Record<string, string>;
}

const props = defineProps<Props>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Proyectos', href: '/admin/proyectos' },
    { title: 'Categorías de Etiquetas', href: '/admin/categorias-etiquetas' },
];

// Estado para edición/creación
const editingId = ref<number | null>(null);
const creatingNew = ref(false);

// Estado para filas expandidas
const expandedRows = ref<Set<number>>(new Set());
const creatingEtiqueta = ref<number | null>(null);
const editingEtiqueta = ref<number | null>(null);

// Estado para controlar la vista (tabla o árbol)
const viewMode = ref<'table' | 'tree'>('table');

// Formulario para categorías
const form = useForm({
    nombre: '',
    color: 'gray',
    icono: 'Tag',
    descripcion: '',
    orden: 0
});

// Formulario para etiquetas
const etiquetaForm = useForm({
    nombre: '',
    descripcion: '',
    categoria_etiqueta_id: 0,
    parent_id: null as number | null
});

// Iniciar creación de nueva categoría
const startCreating = () => {
    creatingNew.value = true;
    editingId.value = null;
    form.reset();
};

// Iniciar edición
const startEditing = (categoria: CategoriaEtiqueta) => {
    editingId.value = categoria.id;
    creatingNew.value = false;
    form.nombre = categoria.nombre;
    form.color = categoria.color;
    form.icono = categoria.icono || 'Tag';
    form.descripcion = categoria.descripcion || '';
    form.orden = categoria.orden;
};

// Cancelar edición/creación
const cancelEditing = () => {
    editingId.value = null;
    creatingNew.value = false;
    form.reset();
};

// Guardar nueva categoría
const saveNew = () => {
    form.post('/admin/categorias-etiquetas', {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Categoría creada exitosamente');
            cancelEditing();
        },
        onError: () => {
            toast.error('Error al crear la categoría');
        }
    });
};

// Actualizar categoría existente
const updateCategoria = (id: number) => {
    form.put(`/admin/categorias-etiquetas/${id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Categoría actualizada');
            cancelEditing();
        },
        onError: () => {
            toast.error('Error al actualizar');
        }
    });
};

// Eliminar categoría
const deleteCategoria = (categoria: CategoriaEtiqueta) => {
    if (confirm(`¿Eliminar la categoría "${categoria.nombre}"? Las etiquetas se reasignarán.`)) {
        router.delete(`/admin/categorias-etiquetas/${categoria.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Categoría eliminada');
            }
        });
    }
};

// Toggle activo/inactivo
const toggleActive = (categoria: CategoriaEtiqueta) => {
    router.patch(`/admin/categorias-etiquetas/${categoria.id}/toggle-active`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(categoria.activo ? 'Categoría desactivada' : 'Categoría activada');
        }
    });
};

// Función para expandir/contraer filas
const toggleExpanded = (categoriaId: number) => {
    const expanded = expandedRows.value;
    if (expanded.has(categoriaId)) {
        expanded.delete(categoriaId);
    } else {
        expanded.add(categoriaId);
    }
};

// Funciones para gestión de etiquetas
const startCreatingEtiqueta = (categoriaId: number) => {
    creatingEtiqueta.value = categoriaId;
    editingEtiqueta.value = null;
    etiquetaForm.reset();
    etiquetaForm.categoria_etiqueta_id = categoriaId;
};

const startEditingEtiqueta = (etiqueta: Etiqueta) => {
    editingEtiqueta.value = etiqueta.id;
    creatingEtiqueta.value = null;
    etiquetaForm.nombre = etiqueta.nombre;
    etiquetaForm.descripcion = etiqueta.descripcion || '';
    etiquetaForm.categoria_etiqueta_id = etiqueta.categoria_etiqueta_id;
    etiquetaForm.parent_id = etiqueta.parent_id || null;
};

const cancelEtiquetaEditing = () => {
    creatingEtiqueta.value = null;
    editingEtiqueta.value = null;
    etiquetaForm.reset();
};

const saveNewEtiqueta = () => {
    etiquetaForm.post('/admin/etiquetas', {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Etiqueta creada exitosamente');
            cancelEtiquetaEditing();
        },
        onError: () => {
            toast.error('Error al crear la etiqueta');
        }
    });
};

const updateEtiqueta = (etiquetaId: number) => {
    etiquetaForm.put(`/admin/etiquetas/${etiquetaId}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Etiqueta actualizada exitosamente');
            cancelEtiquetaEditing();
        },
        onError: () => {
            toast.error('Error al actualizar la etiqueta');
        }
    });
};

const deleteEtiqueta = (etiquetaId: number) => {
    if (confirm('¿Estás seguro de eliminar esta etiqueta?')) {
        router.delete(`/admin/etiquetas/${etiquetaId}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Etiqueta eliminada exitosamente');
            },
            onError: () => {
                toast.error('Error al eliminar la etiqueta');
            }
        });
    }
};

// Obtener clase de color para preview
const getColorClass = (color: string): string => {
    const colorMap: Record<string, string> = {
        gray: 'bg-gray-500',
        red: 'bg-red-500',
        orange: 'bg-orange-500',
        amber: 'bg-amber-500',
        yellow: 'bg-yellow-500',
        lime: 'bg-lime-500',
        green: 'bg-green-500',
        emerald: 'bg-emerald-500',
        teal: 'bg-teal-500',
        cyan: 'bg-cyan-500',
        sky: 'bg-sky-500',
        blue: 'bg-blue-500',
        indigo: 'bg-indigo-500',
        violet: 'bg-violet-500',
        purple: 'bg-purple-500',
        fuchsia: 'bg-fuchsia-500',
        pink: 'bg-pink-500',
        rose: 'bg-rose-500'
    };
    return colorMap[color] || 'bg-gray-500';
};

// Helper para obtener todas las etiquetas en formato plano para el TreeView
const getAllEtiquetas = (): Etiqueta[] => {
    const etiquetas: Etiqueta[] = [];
    props.categorias.data.forEach(categoria => {
        if (categoria.etiquetas) {
            categoria.etiquetas.forEach(etiqueta => {
                etiquetas.push({
                    ...etiqueta,
                    categoria: {
                        id: categoria.id,
                        nombre: categoria.nombre,
                        color: categoria.color,
                        icono: categoria.icono,
                        descripcion: categoria.descripcion,
                        orden: categoria.orden,
                        activo: categoria.activo
                    }
                } as Etiqueta);
            });
        }
    });
    return etiquetas;
};

// Manejar edición de etiqueta desde el árbol
const handleEditEtiqueta = (etiqueta: Etiqueta) => {
    // Encontrar la categoría de la etiqueta
    const categoria = props.categorias.data.find(c => c.id === etiqueta.categoria_etiqueta_id);
    if (categoria) {
        // Expandir la categoría si no lo está
        expandedRows.value.add(categoria.id);
        // Cambiar a vista de tabla
        viewMode.value = 'table';
        // Iniciar edición de la etiqueta
        startEditingEtiqueta(etiqueta);
    }
};

// Manejar eliminación de etiqueta desde el árbol
const handleDeleteEtiqueta = (etiqueta: Etiqueta) => {
    deleteEtiqueta(etiqueta.id);
};
</script>

<template>
    <Head title="Categorías de Etiquetas" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">Categorías de Etiquetas</h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Administra las categorías para organizar las etiquetas de proyectos
                    </p>
                </div>
                <Button @click="startCreating" v-if="!creatingNew">
                    <Plus class="mr-2 h-4 w-4" />
                    Nueva Categoría
                </Button>
            </div>

            <!-- Formulario de creación (si está activo) -->
            <Card v-if="creatingNew">
                <CardHeader>
                    <CardTitle>Nueva Categoría</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <!-- Nombre -->
                        <div>
                            <Label>Nombre *</Label>
                            <Input
                                v-model="form.nombre"
                                placeholder="Nombre de la categoría"
                                :class="{ 'border-red-500': form.errors.nombre }"
                            />
                            <p v-if="form.errors.nombre" class="text-xs text-red-500 mt-1">
                                {{ form.errors.nombre }}
                            </p>
                        </div>

                        <!-- Color -->
                        <div>
                            <Label>Color</Label>
                            <Select v-model="form.color">
                                <SelectTrigger>
                                    <div class="flex items-center gap-2">
                                        <div :class="getColorClass(form.color)" class="w-4 h-4 rounded"></div>
                                        <SelectValue :placeholder="form.color" />
                                    </div>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="(label, key) in colores" :key="key" :value="key">
                                        <div class="flex items-center gap-2">
                                            <div :class="getColorClass(key)" class="w-4 h-4 rounded"></div>
                                            {{ label }}
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Icono -->
                        <div>
                            <Label>Icono</Label>
                            <Select v-model="form.icono">
                                <SelectTrigger>
                                    <SelectValue :placeholder="form.icono" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="(label, key) in iconos" :key="key" :value="key">
                                        {{ label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Orden -->
                        <div>
                            <Label>Orden</Label>
                            <Input
                                v-model.number="form.orden"
                                type="number"
                                placeholder="0"
                            />
                        </div>

                        <!-- Descripción -->
                        <div>
                            <Label>Descripción</Label>
                            <Input
                                v-model="form.descripcion"
                                placeholder="Descripción opcional"
                            />
                        </div>
                    </div>

                    <div class="flex gap-2 mt-4">
                        <Button @click="saveNew" :disabled="form.processing">
                            <Save class="mr-2 h-4 w-4" />
                            Guardar
                        </Button>
                        <Button variant="outline" @click="cancelEditing">
                            <X class="mr-2 h-4 w-4" />
                            Cancelar
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Vistas de categorías y etiquetas -->
            <Tabs v-model="viewMode">
                <Card>
                    <CardHeader>
                        <div class="flex items-center">
                            <TabsList>
                                <TabsTrigger value="table">
                                    <List class="h-4 w-4 mr-2" />
                                    Vista de Tabla
                                </TabsTrigger>
                                <TabsTrigger value="tree">
                                    <TreePine class="h-4 w-4 mr-2" />
                                    Vista de Árbol
                                </TabsTrigger>
                            </TabsList>
                        </div>
                    </CardHeader>

                    <TabsContent value="table" class="mt-0">
                        <CardContent class="p-0">
                            <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Color</TableHead>
                                <TableHead>Etiquetas</TableHead>
                                <TableHead>Orden</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-for="categoria in categorias.data" :key="categoria.id">
                                <!-- Fila de edición -->
                                <TableRow v-if="editingId === categoria.id">
                                    <TableCell>
                                        <Input v-model="form.nombre" class="w-full" />
                                    </TableCell>
                                    <TableCell>
                                        <Select v-model="form.color">
                                            <SelectTrigger class="w-32">
                                                <div class="flex items-center gap-2">
                                                    <div :class="getColorClass(form.color)" class="w-4 h-4 rounded"></div>
                                                    <SelectValue />
                                                </div>
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="(label, key) in colores" :key="key" :value="key">
                                                    <div class="flex items-center gap-2">
                                                        <div :class="getColorClass(key)" class="w-4 h-4 rounded"></div>
                                                        {{ label }}
                                                    </div>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </TableCell>
                                    <TableCell>
                                        {{ categoria.etiquetas_count || 0 }}
                                    </TableCell>
                                    <TableCell>
                                        <Input v-model.number="form.orden" type="number" class="w-20" />
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="categoria.activo ? 'default' : 'secondary'">
                                            {{ categoria.activo ? 'Activa' : 'Inactiva' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button
                                                size="sm"
                                                @click="updateCategoria(categoria.id)"
                                                :disabled="form.processing"
                                            >
                                                <Save class="h-4 w-4 mr-2" />
                                                Guardar
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                @click="cancelEditing"
                                            >
                                                <X class="h-4 w-4 mr-2" />
                                                Cancelar
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <!-- Fila normal -->
                                <TableRow v-else>
                                    <TableCell class="font-medium">
                                        <div class="flex items-center gap-2">
                                            <Tag class="h-4 w-4 text-muted-foreground" />
                                            {{ categoria.nombre }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <div :class="getColorClass(categoria.color)" class="w-4 h-4 rounded"></div>
                                            {{ categoria.color }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline">
                                            {{ categoria.etiquetas_count || 0 }} etiquetas
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        {{ categoria.orden }}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            :variant="categoria.activo ? 'default' : 'secondary'"
                                            class="cursor-pointer"
                                            @click="toggleActive(categoria)"
                                        >
                                            {{ categoria.activo ? 'Activa' : 'Inactiva' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                @click="toggleExpanded(categoria.id)"
                                            >
                                                <ChevronRight
                                                    v-if="!expandedRows.has(categoria.id)"
                                                    class="h-4 w-4 mr-2"
                                                />
                                                <ChevronDown
                                                    v-else
                                                    class="h-4 w-4 mr-2"
                                                />
                                                Ver y añadir etiquetas
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                @click="startEditing(categoria)"
                                            >
                                                <Edit class="h-4 w-4 mr-2" />
                                                Editar
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                @click="deleteCategoria(categoria)"
                                                class="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 class="h-4 w-4 mr-2" />
                                                Eliminar
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <!-- Fila expandida con etiquetas -->
                                <TableRow v-if="expandedRows.has(categoria.id)" class="bg-gray-50 dark:bg-gray-900/50">
                                    <TableCell colspan="6" class="p-0">
                                        <div class="p-4 space-y-4">
                                            <!-- Header de etiquetas -->
                                            <div class="flex items-center justify-between">
                                                <h4 class="font-medium flex items-center gap-2">
                                                    <Tag class="h-4 w-4" />
                                                    Etiquetas de {{ categoria.nombre }}
                                                </h4>
                                                <Button
                                                    size="sm"
                                                    @click="startCreatingEtiqueta(categoria.id)"
                                                    v-if="creatingEtiqueta !== categoria.id"
                                                >
                                                    <Plus class="mr-2 h-4 w-4" />
                                                    Nueva Etiqueta
                                                </Button>
                                            </div>

                                            <!-- Formulario para nueva etiqueta -->
                                            <Card v-if="creatingEtiqueta === categoria.id" class="border-dashed">
                                                <CardContent class="pt-4">
                                                    <div class="space-y-4">
                                                        <!-- Nombre -->
                                                        <div>
                                                            <Label>Nombre *</Label>
                                                            <Input
                                                                v-model="etiquetaForm.nombre"
                                                                placeholder="Nombre de la etiqueta"
                                                                :class="{ 'border-red-500': etiquetaForm.errors.nombre }"
                                                            />
                                                        </div>

                                                        <!-- Descripción -->
                                                        <div>
                                                            <Label>Descripción</Label>
                                                            <Input
                                                                v-model="etiquetaForm.descripcion"
                                                                placeholder="Descripción opcional"
                                                            />
                                                        </div>

                                                        <!-- Etiqueta Padre -->
                                                        <div>
                                                            <EtiquetaHierarchySelector
                                                                v-model="etiquetaForm.parent_id"
                                                                :categorias="[categoria]"
                                                                label="Etiqueta Padre (Opcional)"
                                                                placeholder="Seleccionar etiqueta padre..."
                                                            />
                                                        </div>
                                                    </div>
                                                    <div class="flex gap-2 mt-4">
                                                        <Button @click="saveNewEtiqueta" :disabled="etiquetaForm.processing">
                                                            <Save class="mr-2 h-4 w-4" />
                                                            Guardar
                                                        </Button>
                                                        <Button variant="outline" @click="cancelEtiquetaEditing">
                                                            <X class="mr-2 h-4 w-4" />
                                                            Cancelar
                                                        </Button>
                                                    </div>
                                                </CardContent>
                                            </Card>

                                            <!-- Lista de etiquetas -->
                                            <div v-if="categoria.etiquetas && categoria.etiquetas.length > 0" class="space-y-2">
                                                <div
                                                    v-for="etiqueta in categoria.etiquetas"
                                                    :key="etiqueta.id"
                                                    class="flex items-center justify-between p-3 border rounded-lg bg-white dark:bg-gray-800"
                                                >
                                                    <!-- Formulario de edición -->
                                                    <div v-if="editingEtiqueta === etiqueta.id" class="flex-1">
                                                        <div class="space-y-4">
                                                            <Input
                                                                v-model="etiquetaForm.nombre"
                                                                placeholder="Nombre"
                                                            />
                                                            <Input
                                                                v-model="etiquetaForm.descripcion"
                                                                placeholder="Descripción"
                                                            />
                                                            <EtiquetaHierarchySelector
                                                                v-model="etiquetaForm.parent_id"
                                                                :categorias="[categoria]"
                                                                :excluded-id="etiqueta.id"
                                                                label=""
                                                                placeholder="Etiqueta padre..."
                                                            />
                                                            <div class="flex gap-2">
                                                                <Button
                                                                    size="sm"
                                                                    @click="updateEtiqueta(etiqueta.id)"
                                                                    :disabled="etiquetaForm.processing"
                                                                >
                                                                    <Save class="h-4 w-4 mr-2" />
                                                                    Guardar
                                                                </Button>
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                    @click="cancelEtiquetaEditing"
                                                                >
                                                                    <X class="h-4 w-4 mr-2" />
                                                                    Cancelar
                                                                </Button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Vista normal -->
                                                    <div v-else class="flex-1 flex items-center justify-between">
                                                        <div>
                                                            <div class="flex items-center gap-2">
                                                                <span class="font-medium">{{ etiqueta.nombre }}</span>
                                                                <Badge v-if="etiqueta.parent" variant="outline" class="text-xs">
                                                                    {{ etiqueta.parent.nombre }}
                                                                </Badge>
                                                                <Badge v-if="etiqueta.tiene_hijos" variant="secondary" class="text-xs">
                                                                    Tiene hijos
                                                                </Badge>
                                                            </div>
                                                            <p v-if="etiqueta.descripcion" class="text-sm text-gray-500">
                                                                {{ etiqueta.descripcion }}
                                                            </p>
                                                            <p v-if="etiqueta.ruta_completa && etiqueta.parent" class="text-xs text-muted-foreground mt-1">
                                                                Ruta: {{ etiqueta.ruta_completa }}
                                                            </p>
                                                        </div>
                                                        <div class="flex gap-2">
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                @click="startEditingEtiqueta(etiqueta)"
                                                            >
                                                                <Edit class="h-4 w-4 mr-2" />
                                                                Editar
                                                            </Button>
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                @click="deleteEtiqueta(etiqueta.id)"
                                                            >
                                                                <Trash2 class="h-4 w-4 mr-2" />
                                                                Eliminar
                                                            </Button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Mensaje si no hay etiquetas -->
                                            <div v-else-if="creatingEtiqueta !== categoria.id" class="text-center py-8 text-gray-500">
                                                <Tag class="h-8 w-8 mx-auto mb-2 opacity-50" />
                                                <p>No hay etiquetas en esta categoría</p>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    class="mt-2"
                                                    @click="startCreatingEtiqueta(categoria.id)"
                                                >
                                                    <Plus class="mr-2 h-4 w-4" />
                                                    Crear primera etiqueta
                                                </Button>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </template>

                            <!-- Si no hay categorías -->
                            <TableRow v-if="categorias.data.length === 0">
                                <TableCell colspan="6" class="text-center text-muted-foreground py-8">
                                    No hay categorías creadas. Crea una nueva para empezar.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                            </Table>
                        </CardContent>
                    </TabsContent>

                    <!-- Vista de Árbol -->
                    <TabsContent value="tree" class="mt-0">
                        <CardContent>
                            <EtiquetaTreeView
                                :etiquetas="getAllEtiquetas()"
                                :show-stats="true"
                                @edit="handleEditEtiqueta"
                                @delete="handleDeleteEtiqueta"
                            />
                        </CardContent>
                    </TabsContent>
                </Card>
            </Tabs>
        </div>
    </AdminLayout>
</template>