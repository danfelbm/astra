<script setup lang="ts">
import { ref, computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Checkbox } from '@modules/Core/Resources/js/components/ui/checkbox';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@modules/Core/Resources/js/components/ui/table';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@modules/Core/Resources/js/components/ui/alert-dialog';
import {
    Calendar, Clock, CheckCircle, AlertCircle, XCircle,
    User, Edit, Trash2, FileText,
    Flag, Users, ChevronDown, ChevronRight
} from 'lucide-vue-next';
import type { Entregable } from '@modules/Proyectos/Resources/js/types/hitos';
import type { UploadedFile } from '@modules/Comentarios/Resources/js/types/comentarios';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import StatusChangeModal from './StatusChangeModal.vue';

interface Props {
    entregables: Entregable[];
    canEdit?: boolean;
    canDelete?: boolean;
    canComplete?: boolean;
    viewMode?: 'table' | 'cards' | 'list';
}

const props = withDefaults(defineProps<Props>(), {
    canEdit: false,
    canDelete: false,
    canComplete: false,
    viewMode: 'list',
});

const emit = defineEmits<{
    'edit': [entregable: Entregable];
    'delete': [entregable: Entregable];
    'complete': [entregable: Entregable, observaciones: string, archivos: UploadedFile[]];
    'update-status': [entregable: Entregable, estado: string, observaciones: string, archivos: UploadedFile[]];
}>();

// Estado local
const selectedEntregables = ref<number[]>([]);
const expandedEntregables = ref<number[]>([]);
const deleteConfirmDialog = ref(false);
const entregableToDelete = ref<Entregable | null>(null);

// Estado para el modal de cambio de estado
const statusChangeModalOpen = ref(false);
const entregableToChange = ref<Entregable | null>(null);
const nuevoEstadoPendiente = ref<'pendiente' | 'en_progreso' | 'completado' | 'cancelado'>('pendiente');
const statusChangeLoading = ref(false);

// Computed
const entregablesAgrupados = computed(() => {
    return {
        pendientes: props.entregables.filter(e => e.estado === 'pendiente'),
        en_progreso: props.entregables.filter(e => e.estado === 'en_progreso'),
        completados: props.entregables.filter(e => e.estado === 'completado'),
        cancelados: props.entregables.filter(e => e.estado === 'cancelado'),
    };
});

// Funciones de utilidad
const getEstadoColor = (estado: string) => {
    const colors: Record<string, string> = {
        pendiente: 'bg-gray-100 text-gray-800',
        en_progreso: 'bg-blue-100 text-blue-800',
        completado: 'bg-green-100 text-green-800',
        cancelado: 'bg-red-100 text-red-800',
    };
    return colors[estado] || 'bg-gray-100 text-gray-800';
};

const getPrioridadColor = (prioridad: string) => {
    const colors: Record<string, string> = {
        baja: 'bg-gray-100 text-gray-600',
        media: 'bg-yellow-100 text-yellow-800',
        alta: 'bg-red-100 text-red-800',
    };
    return colors[prioridad] || 'bg-gray-100 text-gray-600';
};

const getEstadoIcon = (estado: string) => {
    switch (estado) {
        case 'completado':
            return CheckCircle;
        case 'en_progreso':
            return Clock;
        case 'pendiente':
            return AlertCircle;
        case 'cancelado':
            return XCircle;
        default:
            return FileText;
    }
};

const formatDate = (date: string | null) => {
    if (!date) return 'Sin fecha';
    return format(new Date(date), 'dd MMM yyyy', { locale: es });
};

const formatDateTime = (date: string | null) => {
    if (!date) return '-';
    return format(new Date(date), "dd MMM yyyy 'a las' HH:mm", { locale: es });
};

const getDiasRestantes = (fechaFin: string | null) => {
    if (!fechaFin) return null;
    const dias = Math.ceil((new Date(fechaFin).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));
    if (dias < 0) return `Vencido hace ${Math.abs(dias)} días`;
    if (dias === 0) return 'Vence hoy';
    if (dias === 1) return 'Vence mañana';
    return `${dias} días`;
};

// Funciones de acciones
const toggleExpanded = (entregableId: number) => {
    const index = expandedEntregables.value.indexOf(entregableId);
    if (index === -1) {
        expandedEntregables.value.push(entregableId);
    } else {
        expandedEntregables.value.splice(index, 1);
    }
};

const isExpanded = (entregableId: number) => {
    return expandedEntregables.value.includes(entregableId);
};

const handleEdit = (entregable: Entregable) => {
    emit('edit', entregable);
};

const handleDelete = (entregable: Entregable) => {
    entregableToDelete.value = entregable;
    deleteConfirmDialog.value = true;
};

const confirmDelete = () => {
    if (entregableToDelete.value) {
        emit('delete', entregableToDelete.value);
    }
    deleteConfirmDialog.value = false;
    entregableToDelete.value = null;
};

// Abre el modal para completar
const handleComplete = (entregable: Entregable) => {
    entregableToChange.value = entregable;
    nuevoEstadoPendiente.value = 'completado';
    statusChangeModalOpen.value = true;
};

// Abre el modal para cambio de estado
const handleStatusChange = (entregable: Entregable, estado: string) => {
    entregableToChange.value = entregable;
    nuevoEstadoPendiente.value = estado as 'pendiente' | 'en_progreso' | 'completado' | 'cancelado';
    statusChangeModalOpen.value = true;
};

// Confirma el cambio de estado con observaciones y archivos
const confirmStatusChange = (observaciones: string, archivos: UploadedFile[]) => {
    if (!entregableToChange.value) return;

    if (nuevoEstadoPendiente.value === 'completado') {
        emit('complete', entregableToChange.value, observaciones, archivos);
    } else {
        emit('update-status', entregableToChange.value, nuevoEstadoPendiente.value, observaciones, archivos);
    }

    // Cerrar modal y limpiar estado
    statusChangeModalOpen.value = false;
    entregableToChange.value = null;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Vista de Lista (Por defecto) -->
        <div v-if="viewMode === 'list'" class="space-y-6">
            <!-- Pendientes -->
            <div v-if="entregablesAgrupados.pendientes.length > 0">
                <h3 class="font-semibold mb-3 flex items-center gap-2">
                    <AlertCircle class="h-5 w-5 text-yellow-600" />
                    Pendientes ({{ entregablesAgrupados.pendientes.length }})
                </h3>
                <div class="space-y-2">
                    <Card v-for="entregable in entregablesAgrupados.pendientes" :key="entregable.id">
                        <CardContent class="p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <!-- Encabezado del entregable -->
                                    <div class="flex items-center gap-2 mb-2">
                                        <Button
                                            @click="toggleExpanded(entregable.id)"
                                            variant="ghost"
                                            size="sm"
                                            class="h-6 w-6 p-0"
                                        >
                                            <ChevronRight v-if="!isExpanded(entregable.id)" class="h-4 w-4" />
                                            <ChevronDown v-else class="h-4 w-4" />
                                        </Button>
                                        <h4 class="font-medium">{{ entregable.nombre }}</h4>
                                        <Badge :class="getPrioridadColor(entregable.prioridad)" variant="outline">
                                            {{ entregable.prioridad }}
                                        </Badge>
                                        <Badge :class="getEstadoColor(entregable.estado)">
                                            {{ entregable.estado }}
                                        </Badge>
                                    </div>

                                    <!-- Detalles expandidos -->
                                    <div v-if="isExpanded(entregable.id)" class="ml-8 space-y-3">
                                        <p v-if="entregable.descripcion" class="text-sm text-muted-foreground">
                                            {{ entregable.descripcion }}
                                        </p>

                                        <div class="flex flex-wrap gap-4 text-sm">
                                            <div class="flex items-center gap-1 text-muted-foreground">
                                                <Calendar class="h-4 w-4" />
                                                <span>{{ formatDate(entregable.fecha_inicio) }} - {{ formatDate(entregable.fecha_fin) }}</span>
                                            </div>
                                            <div v-if="entregable.responsable" class="flex items-center gap-1 text-muted-foreground">
                                                <User class="h-4 w-4" />
                                                <span>{{ entregable.responsable.name }}</span>
                                            </div>
                                            <div v-if="getDiasRestantes(entregable.fecha_fin)"
                                                 class="flex items-center gap-1"
                                                 :class="{ 'text-red-600': getDiasRestantes(entregable.fecha_fin)?.includes('Vencido') }">
                                                <Clock class="h-4 w-4" />
                                                <span>{{ getDiasRestantes(entregable.fecha_fin) }}</span>
                                            </div>
                                        </div>

                                        <!-- Usuarios asignados -->
                                        <div v-if="entregable.usuarios && entregable.usuarios.length > 0" class="flex items-center gap-2">
                                            <Users class="h-4 w-4 text-muted-foreground" />
                                            <div class="flex gap-1">
                                                <Badge v-for="usuario in entregable.usuarios" :key="usuario.id" variant="secondary">
                                                    {{ usuario.name }}
                                                </Badge>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- Acciones como botones en fila -->
                            <div v-if="canEdit || canDelete || canComplete" class="flex items-center gap-1 mt-3 pt-3 border-t">
                                <Button v-if="canComplete" variant="outline" size="sm" class="h-7 text-xs" @click="handleComplete(entregable)">
                                    <CheckCircle class="h-3 w-3 mr-1" />
                                    Completar
                                </Button>
                                <Button v-if="canEdit" variant="outline" size="sm" class="h-7 text-xs" @click="handleStatusChange(entregable, 'en_progreso')">
                                    <Clock class="h-3 w-3 mr-1" />
                                    En progreso
                                </Button>
                                <Button v-if="canEdit" variant="ghost" size="sm" class="h-7 text-xs" @click="handleEdit(entregable)">
                                    <Edit class="h-3 w-3 mr-1" />
                                    Editar
                                </Button>
                                <Button v-if="canDelete" variant="ghost" size="sm" class="h-7 text-xs text-red-600 hover:text-red-700 hover:bg-red-50" @click="handleDelete(entregable)">
                                    <Trash2 class="h-3 w-3 mr-1" />
                                    Eliminar
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- En Progreso -->
            <div v-if="entregablesAgrupados.en_progreso.length > 0">
                <h3 class="font-semibold mb-3 flex items-center gap-2">
                    <Clock class="h-5 w-5 text-blue-600" />
                    En Progreso ({{ entregablesAgrupados.en_progreso.length }})
                </h3>
                <div class="space-y-2">
                    <Card v-for="entregable in entregablesAgrupados.en_progreso" :key="entregable.id"
                          class="border-l-4 border-l-blue-500">
                        <CardContent class="p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <!-- Encabezado del entregable -->
                                    <div class="flex items-center gap-2 mb-2">
                                        <Button
                                            @click="toggleExpanded(entregable.id)"
                                            variant="ghost"
                                            size="sm"
                                            class="h-6 w-6 p-0"
                                        >
                                            <ChevronRight v-if="!isExpanded(entregable.id)" class="h-4 w-4" />
                                            <ChevronDown v-else class="h-4 w-4" />
                                        </Button>
                                        <h4 class="font-medium">{{ entregable.nombre }}</h4>
                                        <Badge :class="getPrioridadColor(entregable.prioridad)" variant="outline">
                                            {{ entregable.prioridad }}
                                        </Badge>
                                        <Badge class="bg-blue-100 text-blue-800">
                                            En Progreso
                                        </Badge>
                                    </div>

                                    <!-- Detalles expandidos -->
                                    <div v-if="isExpanded(entregable.id)" class="ml-8 space-y-3">
                                        <p v-if="entregable.descripcion" class="text-sm text-muted-foreground">
                                            {{ entregable.descripcion }}
                                        </p>

                                        <div class="flex flex-wrap gap-4 text-sm">
                                            <div class="flex items-center gap-1 text-muted-foreground">
                                                <Calendar class="h-4 w-4" />
                                                <span>Fecha límite: {{ formatDate(entregable.fecha_fin) }}</span>
                                            </div>
                                            <div v-if="entregable.responsable" class="flex items-center gap-1 text-muted-foreground">
                                                <User class="h-4 w-4" />
                                                <span>{{ entregable.responsable.name }}</span>
                                            </div>
                                            <div v-if="getDiasRestantes(entregable.fecha_fin)"
                                                 class="flex items-center gap-1"
                                                 :class="{ 'text-red-600': getDiasRestantes(entregable.fecha_fin)?.includes('Vencido') }">
                                                <Clock class="h-4 w-4" />
                                                <span>{{ getDiasRestantes(entregable.fecha_fin) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- Acciones como botones en fila -->
                            <div v-if="canEdit || canDelete || canComplete" class="flex items-center gap-1 mt-3 pt-3 border-t">
                                <Button v-if="canComplete" variant="outline" size="sm" class="h-7 text-xs" @click="handleComplete(entregable)">
                                    <CheckCircle class="h-3 w-3 mr-1" />
                                    Completar
                                </Button>
                                <Button v-if="canEdit" variant="ghost" size="sm" class="h-7 text-xs" @click="handleEdit(entregable)">
                                    <Edit class="h-3 w-3 mr-1" />
                                    Editar
                                </Button>
                                <Button v-if="canDelete" variant="ghost" size="sm" class="h-7 text-xs text-red-600 hover:text-red-700 hover:bg-red-50" @click="handleDelete(entregable)">
                                    <Trash2 class="h-3 w-3 mr-1" />
                                    Eliminar
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Completados -->
            <div v-if="entregablesAgrupados.completados.length > 0">
                <h3 class="font-semibold mb-3 flex items-center gap-2">
                    <CheckCircle class="h-5 w-5 text-green-600" />
                    Completados ({{ entregablesAgrupados.completados.length }})
                </h3>
                <div class="space-y-2">
                    <Card v-for="entregable in entregablesAgrupados.completados" :key="entregable.id"
                          class="border-l-4 border-l-green-500 bg-green-50/30">
                        <CardContent class="p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <!-- Encabezado del entregable -->
                                    <div class="flex items-center gap-2 mb-2">
                                        <CheckCircle class="h-5 w-5 text-green-600" />
                                        <h4 class="font-medium line-through text-muted-foreground">{{ entregable.nombre }}</h4>
                                        <Badge class="bg-green-100 text-green-800">
                                            Completado
                                        </Badge>
                                    </div>

                                    <!-- Información de completado -->
                                    <div class="ml-7 space-y-1 text-sm text-muted-foreground">
                                        <div v-if="entregable.completado_at" class="flex items-center gap-1">
                                            <Clock class="h-3 w-3" />
                                            <span>Completado: {{ formatDateTime(entregable.completado_at) }}</span>
                                        </div>
                                        <div v-if="entregable.completado_por_usuario" class="flex items-center gap-1">
                                            <User class="h-3 w-3" />
                                            <span>Por: {{ entregable.completado_por_usuario.name }}</span>
                                        </div>
                                        <p v-if="entregable.observaciones_estado" class="italic mt-2">
                                            "{{ entregable.observaciones_estado }}"
                                        </p>
                                    </div>
                                </div>

                            </div>

                            <!-- Acciones como botones en fila -->
                            <div v-if="canEdit || canDelete" class="flex items-center gap-1 mt-3 pt-3 border-t">
                                <Button v-if="canEdit" variant="outline" size="sm" class="h-7 text-xs" @click="handleStatusChange(entregable, 'pendiente')">
                                    <AlertCircle class="h-3 w-3 mr-1" />
                                    Reabrir
                                </Button>
                                <Button v-if="canDelete" variant="ghost" size="sm" class="h-7 text-xs text-red-600 hover:text-red-700 hover:bg-red-50" @click="handleDelete(entregable)">
                                    <Trash2 class="h-3 w-3 mr-1" />
                                    Eliminar
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Mensaje si no hay entregables -->
            <div v-if="entregables.length === 0" class="text-center py-8">
                <FileText class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                <p class="text-muted-foreground">No hay entregables asignados</p>
            </div>
        </div>

        <!-- Alert Dialog de Confirmación de Eliminación -->
        <AlertDialog v-model:open="deleteConfirmDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>¿Estás seguro?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Esta acción no se puede deshacer. Se eliminará permanentemente el entregable
                        "{{ entregableToDelete?.nombre }}".
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="confirmDelete">
                        Eliminar
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Modal de Cambio de Estado -->
        <StatusChangeModal
            v-model:open="statusChangeModalOpen"
            :entregable-nombre="entregableToChange?.nombre"
            :estado-actual="entregableToChange?.estado as 'pendiente' | 'en_progreso' | 'completado' | 'cancelado'"
            :nuevo-estado="nuevoEstadoPendiente"
            :loading="statusChangeLoading"
            @confirm="confirmStatusChange"
        />
    </div>
</template>