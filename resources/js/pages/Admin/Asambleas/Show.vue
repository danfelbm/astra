<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AdvancedFilters from '@/components/filters/AdvancedFilters.vue';
import type { AdvancedFilterConfig } from '@/types/filters';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { 
    ArrowLeft, 
    Calendar, 
    MapPin, 
    Users, 
    Edit, 
    UserPlus,
    UserMinus,
    Clock,
    CheckCircle,
    XCircle,
    FileText,
    ChevronLeft,
    ChevronRight
} from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { toast } from 'vue-sonner';
import axios from 'axios';

interface Territorio {
    id: number;
    nombre: string;
}

interface Departamento {
    id: number;
    nombre: string;
}

interface Municipio {
    id: number;
    nombre: string;
}

interface Localidad {
    id: number;
    nombre: string;
}

interface Participante {
    id: number;
    name: string;
    email: string;
    pivot: {
        tenant_id: number;
        tipo_participacion: 'asistente' | 'moderador' | 'secretario';
        asistio: boolean;
        hora_registro?: string;
    };
}

interface Asamblea {
    id: number;
    nombre: string;
    descripcion?: string;
    tipo: 'ordinaria' | 'extraordinaria';
    tipo_label: string;
    estado: 'programada' | 'en_curso' | 'finalizada' | 'cancelada';
    estado_label: string;
    estado_color: string;
    fecha_inicio: string;
    fecha_fin: string;
    territorio?: Territorio;
    departamento?: Departamento;
    municipio?: Municipio;
    localidad?: Localidad;
    lugar?: string;
    ubicacion_completa: string;
    quorum_minimo?: number;
    activo: boolean;
    acta_url?: string;
    duracion: string;
    tiempo_restante: string;
    rango_fechas: string;
    alcanza_quorum: boolean;
    asistentes_count: number;
    participantes_count: number;
}

interface Props {
    asamblea: Asamblea;
    puede_gestionar_participantes: boolean;
    filterFieldsConfig: any[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Asambleas', href: '/admin/asambleas' },
    { title: props.asamblea.nombre, href: '#' },
];

// Helper para obtener route
const { route } = window as any;

// Estado para participantes paginados
const participantes = ref<Participante[]>([]);
const participantesPagination = ref<any>({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
    from: 0,
    to: 0,
});
const loadingParticipantes = ref(false);
const currentFilters = ref<any>({});

// Configuración para el componente de filtros avanzados
const filterConfig: AdvancedFilterConfig = {
    fields: props.filterFieldsConfig || [],
    showQuickSearch: true,
    quickSearchPlaceholder: 'Buscar por nombre o email...',
    quickSearchFields: ['name', 'email'],
    maxNestingLevel: 1,
    allowSaveFilters: false,
    debounceTime: 500,
    autoApply: false,
};

// Estado para el modal de añadir participantes
const modalAñadirAbierto = ref(false);
const participantesDisponibles = ref<any[]>([]);
const participantesSeleccionados = ref<number[]>([]);
const tipoParticipacionNuevo = ref('asistente');

// Estado para registrar asistencia
const registrandoAsistencia = ref<number | null>(null);

// Formatear fecha
const formatearFecha = (fecha: string) => {
    if (!fecha) return '';
    return format(new Date(fecha), 'PPPpp', { locale: es });
};

// Obtener clase para tipo de participación
const getTipoParticipacionBadge = (tipo: string) => {
    switch (tipo) {
        case 'moderador':
            return 'bg-purple-100 text-purple-800';
        case 'secretario':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

// Obtener estadísticas
const estadisticas = computed(() => {
    const moderadores = participantes.value.filter(p => p.pivot?.tipo_participacion === 'moderador').length;
    const secretarios = participantes.value.filter(p => p.pivot?.tipo_participacion === 'secretario').length;
    const asistentes = participantes.value.filter(p => p.pivot?.tipo_participacion === 'asistente').length;
    const presentes = participantes.value.filter(p => p.pivot?.asistio === true).length;
    
    return {
        moderadores,
        secretarios,
        asistentes,
        presentes,
        total: participantesPagination.value.total || 0,
    };
});

// Cargar participantes con filtros y paginación
const cargarParticipantes = async (page = 1, filters = {}) => {
    loadingParticipantes.value = true;
    try {
        const params = {
            page,
            ...filters,
        };
        
        const response = await axios.get(route('admin.asambleas.participantes-list', props.asamblea.id), {
            params,
        });
        
        // Mapear los datos correctamente desde el join
        participantes.value = response.data.participantes.data.map((p: any) => ({
            ...p,
            pivot: {
                tenant_id: p.tenant_id,
                tipo_participacion: p.tipo_participacion || 'asistente',
                asistio: p.asistio === 1 || p.asistio === true || p.asistio === '1',
                hora_registro: p.hora_registro,
            }
        }));
        
        participantesPagination.value = {
            current_page: response.data.participantes.current_page,
            last_page: response.data.participantes.last_page,
            per_page: response.data.participantes.per_page,
            total: response.data.participantes.total,
            from: response.data.participantes.from,
            to: response.data.participantes.to,
        };
    } catch (error) {
        console.error('Error cargando participantes:', error);
    } finally {
        loadingParticipantes.value = false;
    }
};

// Cambiar página
const changePage = (page: number) => {
    cargarParticipantes(page, currentFilters.value);
};

// Aplicar filtros
const handleApplyFilters = (filters: any) => {
    currentFilters.value = filters;
    cargarParticipantes(1, filters);
};

// Limpiar filtros
const handleClearFilters = () => {
    currentFilters.value = {};
    cargarParticipantes(1, {});
};

// Cargar participantes disponibles
const abrirModalAñadir = async () => {
    try {
        const response = await fetch(route('admin.asambleas.manage-participantes', props.asamblea.id));
        const data = await response.json();
        participantesDisponibles.value = data.participantes_disponibles;
        modalAñadirAbierto.value = true;
    } catch (error) {
        console.error('Error cargando participantes:', error);
    }
};

// Refrescar lista de participantes después de cambios
const refrescarParticipantes = () => {
    cargarParticipantes(participantesPagination.value.current_page, currentFilters.value);
};

// Añadir participantes seleccionados
const añadirParticipantes = () => {
    if (participantesSeleccionados.value.length === 0) return;

    const cantidadSeleccionados = participantesSeleccionados.value.length;
    
    router.post(route('admin.asambleas.manage-participantes', props.asamblea.id), {
        participante_ids: participantesSeleccionados.value,
        tipo_participacion: tipoParticipacionNuevo.value,
    }, {
        preserveScroll: true,
        preserveState: true,
        only: [], // No actualizar ningún prop
        onSuccess: () => {
            modalAñadirAbierto.value = false;
            participantesSeleccionados.value = [];
            tipoParticipacionNuevo.value = 'asistente';
            
            toast.success(`${cantidadSeleccionados} participante(s) añadido(s) exitosamente`, {
                duration: 2000,
            });
            
            // Recargar la lista solo si se añadieron participantes
            // para actualizar la paginación y mostrar los nuevos
            cargarParticipantes(participantesPagination.value.current_page, currentFilters.value);
        },
        onError: () => {
            toast.error('Error al añadir participantes', {
                description: 'Por favor intenta nuevamente',
                duration: 3000,
            });
        },
    });
};

// Remover participante
const removerParticipante = (participanteId: number) => {
    const participante = participantes.value.find(p => p.id === participanteId);
    const nombreParticipante = participante?.name || 'Participante';
    
    if (!confirm(`¿Estás seguro de remover a ${nombreParticipante} de la asamblea?`)) return;

    // Eliminación optimista: remover inmediatamente de la lista
    const indice = participantes.value.findIndex(p => p.id === participanteId);
    const participanteRemovido = participantes.value[indice];
    
    if (indice !== -1) {
        participantes.value.splice(indice, 1);
        // Actualizar el contador total
        participantesPagination.value.total -= 1;
    }

    router.delete(route('admin.asambleas.manage-participantes', props.asamblea.id), {
        data: { participante_id: participanteId },
        preserveScroll: true,
        preserveState: true,
        only: [], // No actualizar ningún prop
        onSuccess: () => {
            toast.success(`${nombreParticipante} removido de la asamblea`, {
                duration: 2000,
            });
        },
        onError: () => {
            // Restaurar si hay error
            if (participanteRemovido && indice !== -1) {
                participantes.value.splice(indice, 0, participanteRemovido);
                participantesPagination.value.total += 1;
            }
            toast.error('Error al remover participante', {
                duration: 3000,
            });
        },
    });
};

// Actualizar tipo de participación
const actualizarTipoParticipacion = (participanteId: number, tipo: string) => {
    const participante = participantes.value.find(p => p.id === participanteId);
    const nombreParticipante = participante?.name || 'Participante';
    const tipoAnterior = participante?.pivot?.tipo_participacion;
    
    // Actualización optimista
    if (participante && participante.pivot) {
        participante.pivot.tipo_participacion = tipo;
    }
    
    router.put(route('admin.asambleas.manage-participantes', props.asamblea.id), {
        participante_id: participanteId,
        tipo_participacion: tipo,
    }, {
        preserveScroll: true,
        preserveState: true,
        only: [], // No actualizar ningún prop
        onSuccess: () => {
            const tipoLabel = tipo.charAt(0).toUpperCase() + tipo.slice(1);
            toast.success(`${nombreParticipante} asignado como ${tipoLabel}`, {
                duration: 2000,
            });
        },
        onError: () => {
            // Revertir en caso de error
            if (participante && participante.pivot && tipoAnterior) {
                participante.pivot.tipo_participacion = tipoAnterior;
            }
            toast.error('Error al actualizar el tipo de participación', {
                duration: 3000,
            });
        },
    });
};

// Registrar asistencia
const registrarAsistencia = (participanteId: number, asistio: boolean) => {
    // Evitar múltiples clicks mientras se procesa
    if (registrandoAsistencia.value === participanteId) return;
    
    registrandoAsistencia.value = participanteId;
    
    // Actualización optimista: actualizar el estado local inmediatamente
    const participante = participantes.value.find(p => p.id === participanteId);
    const nombreParticipante = participante?.name || 'Participante';
    
    if (participante && participante.pivot) {
        const estadoAnterior = participante.pivot.asistio;
        participante.pivot.asistio = asistio;
        if (asistio) {
            participante.pivot.hora_registro = new Date().toISOString();
        } else {
            participante.pivot.hora_registro = null;
        }
    }
    
    // Usar router de Inertia pero sin recargar la página
    router.put(route('admin.asambleas.manage-participantes', props.asamblea.id), {
        participante_id: participanteId,
        asistio: asistio,
    }, {
        preserveScroll: true,
        preserveState: true,
        only: [], // No actualizar ningún prop, solo ejecutar la acción
        onSuccess: () => {
            // Mostrar notificación de éxito
            if (asistio) {
                toast.success(`${nombreParticipante} marcado como presente`, {
                    duration: 2000,
                });
            } else {
                toast.info(`${nombreParticipante} marcado como ausente`, {
                    duration: 2000,
                });
            }
        },
        onError: () => {
            // Si hay error, revertir el cambio optimista
            if (participante && participante.pivot) {
                participante.pivot.asistio = !asistio;
                participante.pivot.hora_registro = null;
            }
            
            // Mostrar notificación de error
            toast.error('Error al actualizar la asistencia', {
                description: 'Por favor intenta nuevamente',
                duration: 3000,
            });
        },
        onFinish: () => {
            registrandoAsistencia.value = null;
        },
    });
};

// Navegar a edición
const editarAsamblea = () => {
    router.visit(route('admin.asambleas.edit', props.asamblea.id));
};

// Volver al listado
const volver = () => {
    router.visit(route('admin.asambleas.index'));
};

// Cargar participantes al montar el componente
onMounted(() => {
    cargarParticipantes();
});
</script>

<template>
    <Head :title="`Asamblea: ${asamblea.nombre}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">{{ asamblea.nombre }}</h1>
                    <div class="mt-2 flex items-center gap-2">
                        <Badge :class="asamblea.estado_color">
                            {{ asamblea.estado_label }}
                        </Badge>
                        <Badge :class="asamblea.tipo === 'ordinaria' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'">
                            {{ asamblea.tipo_label }}
                        </Badge>
                        <Badge v-if="asamblea.activo" class="bg-green-100 text-green-800">
                            Activa
                        </Badge>
                        <Badge v-else class="bg-red-100 text-red-800">
                            Inactiva
                        </Badge>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="volver">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                    <Button @click="editarAsamblea">
                        <Edit class="mr-2 h-4 w-4" />
                        Editar
                    </Button>
                </div>
            </div>

            <!-- Información General -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Fechas</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm">
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                                <span>{{ asamblea.rango_fechas }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <Clock class="h-4 w-4 text-muted-foreground" />
                                <span>Duración: {{ asamblea.duracion }}</span>
                            </div>
                            <p class="text-sm font-medium">{{ asamblea.tiempo_restante }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Ubicación</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm">
                                <MapPin class="h-4 w-4 text-muted-foreground" />
                                <span>{{ asamblea.lugar || 'No especificado' }}</span>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                {{ asamblea.ubicacion_completa }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium">Quórum</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <Users class="h-4 w-4 text-muted-foreground" />
                                <span class="text-2xl font-bold">
                                    {{ asamblea.asistentes_count }} / {{ asamblea.quorum_minimo || '∞' }}
                                </span>
                            </div>
                            <Badge v-if="asamblea.alcanza_quorum" class="bg-green-100 text-green-800">
                                <CheckCircle class="mr-1 h-3 w-3" />
                                Quórum alcanzado
                            </Badge>
                            <Badge v-else-if="asamblea.quorum_minimo" class="bg-yellow-100 text-yellow-800">
                                <XCircle class="mr-1 h-3 w-3" />
                                Quórum no alcanzado
                            </Badge>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Descripción -->
            <Card v-if="asamblea.descripcion">
                <CardHeader>
                    <CardTitle>Descripción</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm">{{ asamblea.descripcion }}</p>
                </CardContent>
            </Card>

            <!-- Filtros Avanzados -->
            <AdvancedFilters
                :config="filterConfig"
                @apply="handleApplyFilters"
                @clear="handleClearFilters"
            />

            <!-- Participantes -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Participantes</CardTitle>
                            <CardDescription>
                                {{ participantesPagination.total }} participantes registrados
                            </CardDescription>
                        </div>
                        <div class="flex gap-2">
                            <Badge variant="outline">
                                Moderadores: {{ estadisticas.moderadores }}
                            </Badge>
                            <Badge variant="outline">
                                Secretarios: {{ estadisticas.secretarios }}
                            </Badge>
                            <Badge variant="outline">
                                Asistentes: {{ estadisticas.asistentes }}
                            </Badge>
                            <Badge variant="outline">
                                Presentes: {{ estadisticas.presentes }}
                            </Badge>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="puede_gestionar_participantes" class="mb-4">
                        <Button @click="abrirModalAñadir">
                            <UserPlus class="mr-2 h-4 w-4" />
                            Añadir Participantes
                        </Button>
                    </div>

                    <div v-if="loadingParticipantes" class="flex justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                    </div>
                    
                    <Table v-else>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Tipo de Participación</TableHead>
                                <TableHead>Asistencia</TableHead>
                                <TableHead>Hora de Registro</TableHead>
                                <TableHead v-if="puede_gestionar_participantes" class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="participante in participantes" :key="participante.id">
                                <TableCell class="font-medium">{{ participante.name }}</TableCell>
                                <TableCell>{{ participante.email }}</TableCell>
                                <TableCell>
                                    <Badge :class="getTipoParticipacionBadge(participante.pivot?.tipo_participacion || 'asistente')">
                                        {{ participante.pivot?.tipo_participacion || 'asistente' }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div v-if="puede_gestionar_participantes && asamblea.estado === 'en_curso'" class="flex items-center gap-2">
                                        <div class="relative">
                                            <Checkbox
                                                :checked="participante.pivot?.asistio || false"
                                                :disabled="registrandoAsistencia === participante.id"
                                                @update:checked="(value) => registrarAsistencia(participante.id, value)"
                                                :class="registrandoAsistencia === participante.id ? 'opacity-50' : ''"
                                            />
                                            <div v-if="registrandoAsistencia === participante.id" class="absolute inset-0 flex items-center justify-center">
                                                <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-primary"></div>
                                            </div>
                                        </div>
                                        <span 
                                            v-if="participante.pivot?.asistio" 
                                            class="text-green-600 transition-all duration-300"
                                        >
                                            Presente
                                        </span>
                                        <span 
                                            v-else 
                                            class="text-gray-400 transition-all duration-300"
                                        >
                                            Ausente
                                        </span>
                                    </div>
                                    <div v-else>
                                        <Badge v-if="participante.pivot?.asistio" class="bg-green-100 text-green-800">
                                            Presente
                                        </Badge>
                                        <Badge v-else variant="outline">
                                            Ausente
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    {{ participante.pivot?.hora_registro ? formatearFecha(participante.pivot.hora_registro) : '-' }}
                                </TableCell>
                                <TableCell v-if="puede_gestionar_participantes" class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Select 
                                            :model-value="participante.pivot?.tipo_participacion || 'asistente'"
                                            @update:model-value="(value) => actualizarTipoParticipacion(participante.id, value)"
                                        >
                                            <SelectTrigger class="w-32">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="asistente">Asistente</SelectItem>
                                                <SelectItem value="moderador">Moderador</SelectItem>
                                                <SelectItem value="secretario">Secretario</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="removerParticipante(participante.id)"
                                        >
                                            <UserMinus class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="participantes.length === 0">
                                <TableCell colspan="6" class="text-center py-8">
                                    <p class="text-muted-foreground">No hay participantes registrados</p>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Paginación -->
                    <div v-if="participantesPagination.last_page > 1" class="flex items-center justify-between mt-4">
                        <div class="text-sm text-muted-foreground">
                            Mostrando {{ participantesPagination.from }} a {{ participantesPagination.to }} de {{ participantesPagination.total }} resultados
                        </div>
                        <div class="flex items-center space-x-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="participantesPagination.current_page === 1"
                                @click="changePage(participantesPagination.current_page - 1)"
                            >
                                <ChevronLeft class="h-4 w-4" />
                                Anterior
                            </Button>
                            <div class="text-sm">
                                Página {{ participantesPagination.current_page }} de {{ participantesPagination.last_page }}
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="participantesPagination.current_page === participantesPagination.last_page"
                                @click="changePage(participantesPagination.current_page + 1)"
                            >
                                Siguiente
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Acta -->
            <Card v-if="asamblea.acta_url">
                <CardHeader>
                    <CardTitle>Acta de la Asamblea</CardTitle>
                </CardHeader>
                <CardContent>
                    <a 
                        :href="asamblea.acta_url" 
                        target="_blank"
                        class="flex items-center gap-2 text-blue-600 hover:underline"
                    >
                        <FileText class="h-4 w-4" />
                        Ver Acta
                    </a>
                </CardContent>
            </Card>
        </div>

        <!-- Modal Añadir Participantes -->
        <Dialog v-model:open="modalAñadirAbierto">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Añadir Participantes</DialogTitle>
                    <DialogDescription>
                        Selecciona los usuarios que deseas añadir como participantes de la asamblea
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium">Tipo de Participación</label>
                        <Select v-model="tipoParticipacionNuevo">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="asistente">Asistente</SelectItem>
                                <SelectItem value="moderador">Moderador</SelectItem>
                                <SelectItem value="secretario">Secretario</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="max-h-96 overflow-y-auto border rounded-lg p-4">
                        <div v-for="usuario in participantesDisponibles" :key="usuario.id" class="flex items-center space-x-2 py-2">
                            <Checkbox
                                :id="`usuario-${usuario.id}`"
                                :checked="participantesSeleccionados.includes(usuario.id)"
                                @update:checked="(value) => {
                                    if (value) {
                                        participantesSeleccionados.push(usuario.id);
                                    } else {
                                        const index = participantesSeleccionados.indexOf(usuario.id);
                                        if (index > -1) participantesSeleccionados.splice(index, 1);
                                    }
                                }"
                            />
                            <label 
                                :for="`usuario-${usuario.id}`"
                                class="flex-1 cursor-pointer text-sm"
                            >
                                {{ usuario.name }} ({{ usuario.email }})
                            </label>
                        </div>
                        <p v-if="participantesDisponibles.length === 0" class="text-center text-muted-foreground">
                            No hay usuarios disponibles para añadir
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="modalAñadirAbierto = false">
                        Cancelar
                    </Button>
                    <Button 
                        @click="añadirParticipantes"
                        :disabled="participantesSeleccionados.length === 0"
                    >
                        Añadir {{ participantesSeleccionados.length }} Participante(s)
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>