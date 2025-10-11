<script setup lang="ts">
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Switch } from "@modules/Core/Resources/js/components/ui/switch";
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
import {
    Plus,
    Edit,
    Trash2,
    Search,
    Settings,
    ChevronUp,
    ChevronDown,
    Hash,
    Type,
    Calendar,
    FileText,
    List,
    CheckSquare,
    Radio,
    File
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';

// Tipos de datos
interface CampoPersonalizado {
    id: number;
    nombre: string;
    slug: string;
    tipo: string;
    opciones?: any[];
    es_requerido: boolean;
    orden: number;
    activo: boolean;
    descripcion?: string;
    placeholder?: string;
    aplicar_para?: string[];
    created_at: string;
    updated_at: string;
}

interface PaginatedData {
    data: CampoPersonalizado[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

// Props del componente
interface Props {
    campos: PaginatedData;
    filters: {
        search?: string;
        tipo?: string;
        activo?: boolean;
    };
    tiposCampo?: Record<string, string>;
    tipos?: Record<string, string>; // Para compatibilidad
    entidadesDisponibles?: Record<string, string>;
}

const props = defineProps<Props>();

// Variables computed para compatibilidad con props antiguos
import { computed } from 'vue';
const tiposCampo = computed(() => props.tiposCampo || props.tipos || {});
const tipos = tiposCampo; // Para retrocompatibilidad

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Proyectos', href: '/admin/proyectos' },
    { title: 'Campos Personalizados', href: '/admin/campos-personalizados' },
];

// Filtros locales
const searchFilter = ref(props.filters.search || '');
const tipoFilter = ref(props.filters.tipo || '');
const activoFilter = ref(props.filters.activo !== undefined ? String(props.filters.activo) : '');

// Función para aplicar filtros
const applyFilters = () => {
    const params: any = {
        search: searchFilter.value,
        tipo: tipoFilter.value,
    };

    if (activoFilter.value !== '') {
        params.activo = activoFilter.value === '1';
    }

    router.get('/admin/campos-personalizados', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Watch para aplicar filtros con debounce
let searchTimeout: NodeJS.Timeout;
watch(searchFilter, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

// Aplicar filtros inmediatamente en selects
watch([tipoFilter, activoFilter], () => {
    applyFilters();
});

// Función para eliminar campo
const deleteCampo = (campo: CampoPersonalizado) => {
    if (confirm(`¿Estás seguro de eliminar el campo "${campo.nombre}"?\n\nEsto eliminará también todos los valores asociados en los proyectos.`)) {
        router.delete(`/admin/campos-personalizados/${campo.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Campo personalizado eliminado exitosamente');
            },
            onError: () => {
                toast.error('No se puede eliminar el campo porque tiene valores asociados');
            }
        });
    }
};

// Función para cambiar estado activo
const toggleActivo = (campo: CampoPersonalizado, newValue: boolean) => {
    router.patch(`/admin/campos-personalizados/${campo.id}/toggle-activo`,
        { activo: newValue },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success(`Campo ${newValue ? 'activado' : 'desactivado'} exitosamente`);
            },
            onError: () => {
                toast.error('Error al cambiar el estado del campo');
            }
        }
    );
};

// Función para mover campo arriba/abajo
const moverCampo = (campo: CampoPersonalizado, direccion: 'up' | 'down') => {
    const campos = props.campos.data;
    const index = campos.findIndex(c => c.id === campo.id);

    if ((direccion === 'up' && index > 0) || (direccion === 'down' && index < campos.length - 1)) {
        const newIndex = direccion === 'up' ? index - 1 : index + 1;
        const orden = campos.map(c => c.id);
        [orden[index], orden[newIndex]] = [orden[newIndex], orden[index]];

        router.post('/admin/campos-personalizados/reordenar', { orden }, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Orden actualizado');
            }
        });
    }
};

// Función para obtener icono del tipo
const getTipoIcon = (tipo: string) => {
    const icons: Record<string, any> = {
        'text': Type,
        'number': Hash,
        'date': Calendar,
        'textarea': FileText,
        'select': List,
        'checkbox': CheckSquare,
        'radio': Radio,
        'file': File
    };
    return icons[tipo] || Settings;
};

// Función para obtener color del tipo
const getTipoColor = (tipo: string) => {
    const colors: Record<string, string> = {
        'text': 'bg-blue-100 text-blue-800',
        'number': 'bg-purple-100 text-purple-800',
        'date': 'bg-green-100 text-green-800',
        'textarea': 'bg-yellow-100 text-yellow-800',
        'select': 'bg-orange-100 text-orange-800',
        'checkbox': 'bg-pink-100 text-pink-800',
        'radio': 'bg-indigo-100 text-indigo-800',
        'file': 'bg-gray-100 text-gray-800'
    };
    return colors[tipo] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Campos Personalizados" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header con título y botón -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Campos Personalizados
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Define campos adicionales para los proyectos
                    </p>
                </div>
                <Link href="/admin/campos-personalizados/create">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Campo
                    </Button>
                </Link>
            </div>

            <!-- Filtros -->
            <Card>
                <CardContent class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Búsqueda -->
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                            <Input
                                v-model="searchFilter"
                                type="text"
                                placeholder="Buscar campos..."
                                class="pl-10"
                            />
                        </div>

                        <!-- Filtro por tipo -->
                        <Select v-model="tipoFilter">
                            <SelectTrigger>
                                <SelectValue placeholder="Todos los tipos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos los tipos</SelectItem>
                                <SelectItem v-for="(label, key) in tipos" :key="key" :value="key">
                                    {{ label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Filtro por estado -->
                        <Select v-model="activoFilter">
                            <SelectTrigger>
                                <SelectValue placeholder="Todos los estados" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos los estados</SelectItem>
                                <SelectItem value="1">Activos</SelectItem>
                                <SelectItem value="0">Inactivos</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Botón limpiar -->
                        <Button
                            variant="outline"
                            @click="searchFilter = ''; tipoFilter = ''; activoFilter = ''; applyFilters()"
                        >
                            Limpiar Filtros
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Tabla de campos -->
            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-12">Orden</TableHead>
                                <TableHead>Campo</TableHead>
                                <TableHead>Slug</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Aplicar a</TableHead>
                                <TableHead class="text-center">Requerido</TableHead>
                                <TableHead class="text-center">Estado</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(campo, index) in campos.data" :key="campo.id">
                                <!-- Orden con botones -->
                                <TableCell>
                                    <div class="flex items-center gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            :disabled="index === 0"
                                            @click="moverCampo(campo, 'up')"
                                        >
                                            <ChevronUp class="h-3 w-3" />
                                        </Button>
                                        <span class="text-sm text-gray-500">{{ campo.orden }}</span>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            :disabled="index === campos.data.length - 1"
                                            @click="moverCampo(campo, 'down')"
                                        >
                                            <ChevronDown class="h-3 w-3" />
                                        </Button>
                                    </div>
                                </TableCell>

                                <!-- Nombre y descripción -->
                                <TableCell>
                                    <div>
                                        <p class="font-medium">{{ campo.nombre }}</p>
                                        <p v-if="campo.descripcion" class="text-xs text-gray-500">
                                            {{ campo.descripcion }}
                                        </p>
                                    </div>
                                </TableCell>

                                <!-- Slug -->
                                <TableCell>
                                    <code class="text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">
                                        {{ campo.slug }}
                                    </code>
                                </TableCell>

                                <!-- Tipo -->
                                <TableCell>
                                    <Badge :class="getTipoColor(campo.tipo)" class="flex items-center gap-1 w-fit">
                                        <component :is="getTipoIcon(campo.tipo)" class="h-3 w-3" />
                                        {{ (tiposCampo || tipos)?.[campo.tipo] || campo.tipo }}
                                    </Badge>
                                </TableCell>

                                <!-- Aplicar a -->
                                <TableCell>
                                    <div class="flex flex-wrap gap-1">
                                        <Badge
                                            v-if="!campo.aplicar_para || campo.aplicar_para.length === 0 || campo.aplicar_para.includes('proyectos')"
                                            variant="secondary"
                                            class="text-xs"
                                        >
                                            Proyectos
                                        </Badge>
                                        <Badge
                                            v-if="campo.aplicar_para?.includes('contratos')"
                                            variant="secondary"
                                            class="text-xs"
                                        >
                                            Contratos
                                        </Badge>
                                        <Badge
                                            v-if="campo.aplicar_para?.includes('hitos')"
                                            variant="secondary"
                                            class="text-xs"
                                        >
                                            Hitos
                                        </Badge>
                                        <Badge
                                            v-if="campo.aplicar_para?.includes('entregables')"
                                            variant="secondary"
                                            class="text-xs"
                                        >
                                            Entregables
                                        </Badge>
                                    </div>
                                </TableCell>

                                <!-- Requerido -->
                                <TableCell class="text-center">
                                    <Badge :class="campo.es_requerido ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'">
                                        {{ campo.es_requerido ? 'Sí' : 'No' }}
                                    </Badge>
                                </TableCell>

                                <!-- Estado -->
                                <TableCell class="text-center">
                                    <Switch
                                        :model-value="campo.activo"
                                        @update:model-value="(value) => toggleActivo(campo, value)"
                                    />
                                </TableCell>

                                <!-- Acciones -->
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link :href="`/admin/campos-personalizados/${campo.id}/edit`">
                                            <Button variant="outline" size="sm">
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            @click="deleteCampo(campo)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <!-- Mensaje cuando no hay campos -->
                            <TableRow v-if="campos.data.length === 0">
                                <TableCell colspan="7" class="text-center py-8">
                                    <p class="text-gray-500">No se encontraron campos personalizados</p>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Paginación -->
            <div v-if="campos.last_page > 1" class="flex justify-center gap-2">
                <template v-for="link in campos.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        preserve-state
                        preserve-scroll
                    >
                        <Button
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            v-html="link.label"
                        />
                    </Link>
                    <Button
                        v-else
                        variant="outline"
                        size="sm"
                        disabled
                        v-html="link.label"
                    />
                </template>
            </div>

            <!-- Resumen -->
            <div class="text-center text-sm text-gray-500">
                Mostrando {{ campos.data.length }} de {{ campos.total }} campos
            </div>
        </div>
    </AdminLayout>
</template>