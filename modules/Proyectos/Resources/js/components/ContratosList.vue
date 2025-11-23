<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Card, CardContent } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Checkbox } from '@modules/Core/Resources/js/components/ui/checkbox';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from '@modules/Core/Resources/js/components/ui/table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger
} from '@modules/Core/Resources/js/components/ui/dropdown-menu';
import {
    Calendar,
    DollarSign,
    MoreHorizontal,
    Eye,
    Edit,
    Copy,
    Trash2,
    AlertCircle,
    FileText,
    Download,
    List
} from 'lucide-vue-next';
import type { Contrato, EstadoContrato } from '../types/contratos';
import {
    getEstadoLabel,
    getTipoLabel,
    formatMonto,
    calcularDiasRestantes
} from '../types/contratos';
import { useToast } from '@modules/Core/Resources/js/composables/useToast';

interface Props {
    contratos: Contrato[];
    loading?: boolean;
    showProyecto?: boolean;
    showActions?: boolean;
    showSelection?: boolean;
    selectedIds?: number[];
    actionsStyle?: 'dropdown' | 'buttons';
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    showProyecto: true,
    showActions: true,
    showSelection: false,
    selectedIds: () => [],
    actionsStyle: 'dropdown'
});

const emit = defineEmits<{
    'select': [contrato: Contrato];
    'selectAll': [selected: boolean];
    'edit': [contrato: Contrato];
    'delete': [contrato: Contrato];
    'duplicate': [contrato: Contrato];
    'changeStatus': [contrato: Contrato, estado: EstadoContrato];
    'download': [contrato: Contrato];
}>();

const toast = useToast();

// Estado local
const selectedContratos = ref<Set<number>>(new Set(props.selectedIds));
const sortColumn = ref<string>('fecha_inicio');
const sortDirection = ref<'asc' | 'desc'>('desc');

// Computed
const allSelected = computed(() => {
    return props.contratos.length > 0 &&
           props.contratos.every(c => selectedContratos.value.has(c.id));
});

const someSelected = computed(() => {
    return props.contratos.some(c => selectedContratos.value.has(c.id)) &&
           !allSelected.value;
});

const sortedContratos = computed(() => {
    const sorted = [...props.contratos];

    sorted.sort((a, b) => {
        let aVal = a[sortColumn.value];
        let bVal = b[sortColumn.value];

        // Manejar valores nulos
        if (aVal === null || aVal === undefined) return 1;
        if (bVal === null || bVal === undefined) return -1;

        // Comparar según el tipo
        if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase();
            bVal = (bVal as string).toLowerCase();
        }

        if (sortDirection.value === 'asc') {
            return aVal > bVal ? 1 : -1;
        } else {
            return aVal < bVal ? 1 : -1;
        }
    });

    return sorted;
});

// Métodos
const getEstadoBadgeClass = (estado: string) => {
    const clases = {
        'borrador': 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
        'activo': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'finalizado': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'cancelado': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return clases[estado] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
};

const getTipoBadgeClass = (tipo: string) => {
    const clases = {
        'servicio': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        'obra': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        'suministro': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
        'consultoria': 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
        'otro': 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
    };
    return clases[tipo] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
};

const formatDate = (date: string | undefined) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const toggleSort = (column: string) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
};

const toggleSelection = (contratoId: number) => {
    if (selectedContratos.value.has(contratoId)) {
        selectedContratos.value.delete(contratoId);
    } else {
        selectedContratos.value.add(contratoId);
    }
};

const toggleSelectAll = () => {
    if (allSelected.value) {
        selectedContratos.value.clear();
    } else {
        props.contratos.forEach(c => selectedContratos.value.add(c.id));
    }
    emit('selectAll', allSelected.value);
};

const handleAction = (action: string, contrato: Contrato) => {
    switch (action) {
        case 'view':
            router.get(route('admin.contratos.show', contrato.id));
            break;
        case 'edit':
            emit('edit', contrato);
            router.get(route('admin.contratos.edit', contrato.id));
            break;
        case 'obligations':
            router.get(route('admin.obligaciones.index', { contrato_id: contrato.id }));
            break;
        case 'duplicate':
            emit('duplicate', contrato);
            break;
        case 'delete':
            emit('delete', contrato);
            break;
        case 'download':
            if (contrato.archivo_pdf) {
                emit('download', contrato);
                window.open(contrato.archivo_pdf, '_blank');
            } else {
                toast.error('Este contrato no tiene archivo PDF');
            }
            break;
    }
};

const cambiarEstado = (contrato: Contrato, nuevoEstado: EstadoContrato) => {
    emit('changeStatus', contrato, nuevoEstado);
};

// Exportar selección (para el componente padre)
defineExpose({
    selectedContratos
});
</script>

<template>
    <div class="space-y-4">
        <!-- Tabla de contratos -->
        <Card>
            <CardContent class="p-0">
                <div v-if="loading" class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                </div>

                <Table v-else>
                    <TableHeader>
                        <TableRow>
                            <TableHead v-if="showSelection" class="w-12">
                                <Checkbox
                                    :checked="allSelected"
                                    :indeterminate="someSelected"
                                    @update:checked="toggleSelectAll"
                                />
                            </TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-50"
                                @click="toggleSort('nombre')"
                            >
                                <div class="flex items-center gap-1">
                                    Nombre
                                    <span v-if="sortColumn === 'nombre'" class="text-xs">
                                        {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </div>
                            </TableHead>
                            <TableHead v-if="showProyecto">Proyecto</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead>Tipo</TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-50"
                                @click="toggleSort('fecha_inicio')"
                            >
                                <div class="flex items-center gap-1">
                                    Fecha Inicio
                                    <span v-if="sortColumn === 'fecha_inicio'" class="text-xs">
                                        {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </div>
                            </TableHead>
                            <TableHead>Fecha Fin</TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-50"
                                @click="toggleSort('monto_total')"
                            >
                                <div class="flex items-center gap-1">
                                    Monto
                                    <span v-if="sortColumn === 'monto_total'" class="text-xs">
                                        {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </div>
                            </TableHead>
                            <TableHead>Responsable</TableHead>
                            <TableHead v-if="showActions && actionsStyle === 'dropdown'" class="text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="sortedContratos.length > 0">
                            <TableRow
                                v-for="contrato in sortedContratos"
                                :key="contrato.id"
                                :class="{ 'bg-gray-50': selectedContratos.has(contrato.id) }"
                            >
                                <TableCell v-if="showSelection">
                                    <Checkbox
                                        :checked="selectedContratos.has(contrato.id)"
                                        @update:checked="() => toggleSelection(contrato.id)"
                                    />
                                </TableCell>
                                <TableCell class="font-medium">
                                    <div class="flex flex-col gap-2">
                                        <div class="flex items-center gap-2">
                                            <FileText class="h-4 w-4 text-gray-400" />
                                            <div>
                                                {{ contrato.nombre }}
                                                <div class="flex items-center gap-1 mt-1">
                                                    <Badge
                                                        v-if="contrato.esta_vencido"
                                                        variant="destructive"
                                                        class="text-xs"
                                                    >
                                                        Vencido
                                                    </Badge>
                                                    <Badge
                                                        v-else-if="contrato.esta_proximo_vencer"
                                                        variant="outline"
                                                        class="text-xs text-yellow-600 border-yellow-600"
                                                    >
                                                        <AlertCircle class="h-3 w-3 mr-1" />
                                                        {{ contrato.dias_restantes }} días
                                                    </Badge>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Acciones como botones inline -->
                                        <div v-if="showActions && actionsStyle === 'buttons'" class="flex items-center gap-1 flex-wrap">
                                            <Link :href="route('admin.contratos.show', contrato.id)">
                                                <Button variant="outline" size="sm" class="h-7 text-xs">
                                                    Ver detalles
                                                </Button>
                                            </Link>
                                            <Link :href="route('admin.contratos.edit', contrato.id)">
                                                <Button variant="outline" size="sm" class="h-7 text-xs">
                                                    Editar
                                                </Button>
                                            </Link>
                                            <Link :href="route('admin.obligaciones.index', { contrato_id: contrato.id })">
                                                <Button variant="outline" size="sm" class="h-7 text-xs">
                                                    Ver obligaciones
                                                </Button>
                                            </Link>
                                            <Button
                                                v-if="contrato.estado === 'borrador'"
                                                variant="outline"
                                                size="sm"
                                                class="h-7 text-xs text-green-600 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-950"
                                                @click="cambiarEstado(contrato, 'activo')"
                                            >
                                                Activar
                                            </Button>
                                            <Button
                                                v-if="contrato.estado === 'activo'"
                                                variant="outline"
                                                size="sm"
                                                class="h-7 text-xs text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-950"
                                                @click="cambiarEstado(contrato, 'finalizado')"
                                            >
                                                Finalizar
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="h-7 text-xs text-purple-600 hover:text-purple-700 hover:bg-purple-50 dark:hover:bg-purple-950"
                                                @click="handleAction('duplicate', contrato)"
                                            >
                                                Duplicar
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="h-7 text-xs text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950"
                                                @click="handleAction('delete', contrato)"
                                            >
                                                Eliminar
                                            </Button>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell v-if="showProyecto">
                                    <Link
                                        v-if="contrato.proyecto"
                                        :href="route('admin.proyectos.show', contrato.proyecto.id)"
                                        class="text-blue-600 hover:underline"
                                    >
                                        {{ contrato.proyecto.nombre }}
                                    </Link>
                                    <span v-else class="text-gray-400">-</span>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="getEstadoBadgeClass(contrato.estado)">
                                        {{ getEstadoLabel(contrato.estado) }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="getTipoBadgeClass(contrato.tipo)" variant="outline">
                                        {{ getTipoLabel(contrato.tipo) }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-1">
                                        <Calendar class="h-4 w-4 text-gray-400" />
                                        {{ formatDate(contrato.fecha_inicio) }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    {{ formatDate(contrato.fecha_fin) }}
                                </TableCell>
                                <TableCell>
                                    <div v-if="contrato.monto_total" class="flex items-center gap-1">
                                        <DollarSign class="h-4 w-4 text-gray-400" />
                                        {{ formatMonto(contrato.monto_total, contrato.moneda) }}
                                    </div>
                                    <span v-else class="text-gray-400">-</span>
                                </TableCell>
                                <TableCell>
                                    {{ contrato.responsable?.name || '-' }}
                                </TableCell>
                                <TableCell v-if="showActions && actionsStyle === 'dropdown'" class="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="sm">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuLabel>Acciones</DropdownMenuLabel>
                                            <DropdownMenuSeparator />

                                            <DropdownMenuItem @click="handleAction('view', contrato)">
                                                <Eye class="h-4 w-4 mr-2" />
                                                Ver detalles
                                            </DropdownMenuItem>

                                            <DropdownMenuItem @click="handleAction('edit', contrato)">
                                                <Edit class="h-4 w-4 mr-2" />
                                                Editar
                                            </DropdownMenuItem>

                                            <DropdownMenuItem @click="handleAction('obligations', contrato)">
                                                <List class="h-4 w-4 mr-2" />
                                                Ver obligaciones
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-if="contrato.archivo_pdf"
                                                @click="handleAction('download', contrato)"
                                            >
                                                <Download class="h-4 w-4 mr-2" />
                                                Descargar PDF
                                            </DropdownMenuItem>

                                            <DropdownMenuSeparator />

                                            <DropdownMenuItem
                                                v-if="contrato.estado === 'borrador'"
                                                @click="cambiarEstado(contrato, 'activo')"
                                            >
                                                Activar
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-if="contrato.estado === 'activo'"
                                                @click="cambiarEstado(contrato, 'finalizado')"
                                            >
                                                Finalizar
                                            </DropdownMenuItem>

                                            <DropdownMenuItem @click="handleAction('duplicate', contrato)">
                                                <Copy class="h-4 w-4 mr-2" />
                                                Duplicar
                                            </DropdownMenuItem>

                                            <DropdownMenuSeparator />

                                            <DropdownMenuItem
                                                class="text-red-600"
                                                @click="handleAction('delete', contrato)"
                                            >
                                                <Trash2 class="h-4 w-4 mr-2" />
                                                Eliminar
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                        </template>
                        <TableRow v-else>
                            <TableCell
                                :colspan="showSelection ? 10 : 9"
                                class="text-center py-8 text-gray-500"
                            >
                                <FileText class="h-12 w-12 text-gray-300 mx-auto mb-2" />
                                <p>No se encontraron contratos</p>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <!-- Información de selección -->
        <div v-if="showSelection && selectedContratos.size > 0" class="text-sm text-gray-600">
            {{ selectedContratos.size }} contrato(s) seleccionado(s)
        </div>
    </div>
</template>