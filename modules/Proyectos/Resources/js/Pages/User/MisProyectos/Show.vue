<script setup lang="ts">
import UserLayout from "@modules/Core/Resources/js/layouts/UserLayout.vue";
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Progress } from "@modules/Core/Resources/js/components/ui/progress";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@modules/Core/Resources/js/components/ui/tabs";
import { Avatar, AvatarFallback, AvatarImage } from "@modules/Core/Resources/js/components/ui/avatar";
import { Separator } from "@modules/Core/Resources/js/components/ui/separator";
import { ref, computed, watch, defineAsyncComponent } from 'vue';
import {
    ArrowLeft,
    Edit,
    Calendar,
    User,
    Clock,
    Target,
    Flag,
    CheckCircle,
    PauseCircle,
    PlayCircle,
    Ban,
    AlertCircle,
    Tag,
    Users as UsersIcon,
    FileText,
    Image,
    Milestone,
    UserPlus,
    Info,
    Plus,
} from 'lucide-vue-next';
import EtiquetaDisplay from "@modules/Proyectos/Resources/js/components/EtiquetaDisplay.vue";
import ContratoCard from "@modules/Proyectos/Resources/js/components/ContratoCard.vue";
import HitoCard from "@modules/Proyectos/Resources/js/components/HitoCard.vue";
import HitosDashboard from "@modules/Proyectos/Resources/js/components/HitosDashboard.vue";
import EvidenciasDisplay from "@modules/Proyectos/Resources/js/components/EvidenciasDisplay.vue";
import CamposPersonalizadosDisplay from "@modules/Proyectos/Resources/js/components/CamposPersonalizadosDisplay.vue";
import ProyectoEstadoCard from "@modules/Proyectos/Resources/js/components/ProyectoEstadoCard.vue";
import ProyectoInfoCard from "@modules/Proyectos/Resources/js/components/ProyectoInfoCard.vue";
import type { Etiqueta } from '@modules/Proyectos/Resources/js/types/etiquetas';
import type { Contrato } from '@modules/Proyectos/Resources/js/types/contratos';
import type { Hito, Entregable } from '@modules/Proyectos/Resources/js/types/hitos';
import { toast } from 'vue-sonner';

// Interfaces
interface User {
    id: number;
    name: string;
    email: string;
}

interface CampoPersonalizado {
    id: number;
    campo_personalizado_id: number;
    valor: any;
    campo_personalizado: {
        id: number;
        nombre: string;
        tipo: string;
    };
}

interface Participante extends User {
    pivot?: {
        rol: string;
    };
}

interface Proyecto {
    id: number;
    nombre: string;
    descripcion?: string;
    fecha_inicio: string;
    fecha_fin?: string;
    estado: string;
    estado_label: string;
    estado_color: string;
    prioridad: string;
    prioridad_label: string;
    prioridad_color: string;
    responsable?: User;
    creador?: User;
    etiquetas?: Etiqueta[];
    participantes?: Participante[];
    contratos?: Contrato[];
    hitos?: Hito[];
    porcentaje_completado: number;
    duracion_dias?: number;
    campos_personalizados?: CampoPersonalizado[];
    created_at: string;
    updated_at: string;
}

interface Props {
    proyecto: Proyecto;
    totales?: {
        usuarios: number;
        contratos: number;
        evidencias: number;
        hitos: number;
    };
    canEdit?: boolean;
    canDelete?: boolean;
}

const props = defineProps<Props>();

// Tabs válidos para validación
const validTabs = ['general', 'usuarios', 'evidencias', 'hitos'];

// Estado para el tab activo - leer de URL query params
const getInitialTab = (): string => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabFromUrl = urlParams.get('tab');
    return tabFromUrl && validTabs.includes(tabFromUrl) ? tabFromUrl : 'general';
};

const activeTab = ref(getInitialTab());

// Sincronizar tab con URL usando query params
watch(activeTab, (newTab) => {
    const url = `/miembro/mis-proyectos/${props.proyecto.id}?tab=${newTab}`;
    router.get(url, {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: []
    });
});

// Obtener usuario actual
const page = usePage();
const currentUserId = computed(() => (page.props.auth as any)?.user?.id);

// Computed para verificar si el usuario es gestor del proyecto
const esGestorDelProyecto = computed(() => {
    const userId = currentUserId.value;
    if (!userId) return false;

    // Es el responsable del proyecto
    if (props.proyecto.responsable?.id === userId) {
        return true;
    }
    // Es gestor del proyecto (verificar en participantes con rol gestor)
    if (props.proyecto.participantes) {
        return props.proyecto.participantes.some(
            p => p.id === userId && p.pivot?.rol === 'gestor'
        );
    }
    return false;
});

// Lazy loading para tabs pesados
const usuariosCargados = ref(false);
const contratosCargados = ref(false);
const evidenciasCargadas = ref(false);
const hitosCargados = ref(false);

// Función para obtener el ícono del estado
const getEstadoIcon = (estado: string) => {
    const icons = {
        'planificacion': Target,
        'en_progreso': PlayCircle,
        'pausado': PauseCircle,
        'completado': CheckCircle,
        'cancelado': Ban
    };
    return icons[estado] || AlertCircle;
};

// Función para obtener color del badge de estado
const getEstadoColor = (estado: string) => {
    const colors: Record<string, string> = {
        'planificacion': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'en_progreso': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'pausado': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        'completado': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'cancelado': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return colors[estado] || 'bg-gray-100 text-gray-800';
};

// Función para obtener color del badge de prioridad
const getPrioridadColor = (prioridad: string) => {
    const colors: Record<string, string> = {
        'baja': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        'media': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'alta': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        'critica': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return colors[prioridad] || 'bg-gray-100 text-gray-800';
};

// Función para formatear fecha
const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Función para calcular días restantes
const getDiasRestantes = (fechaFin: string) => {
    if (!fechaFin) return null;
    const dias = Math.ceil((new Date(fechaFin).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));
    if (dias < 0) return { texto: `Vencido hace ${Math.abs(dias)} días`, urgente: true };
    if (dias === 0) return { texto: 'Vence hoy', urgente: true };
    if (dias === 1) return { texto: 'Vence mañana', urgente: true };
    if (dias <= 7) return { texto: `${dias} días restantes`, urgente: true };
    return { texto: `${dias} días restantes`, urgente: false };
};

const diasRestantes = props.proyecto.fecha_fin ? getDiasRestantes(props.proyecto.fecha_fin) : null;


// Función para obtener el inicial del nombre
const getInitials = (name: string) => {
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

// Navegar a la vista de detalle del hito
const navigateToHito = (hito: Hito) => {
    router.visit(`/miembro/mis-hitos/${hito.id}`);
};

// Navegar a editar hito (solo para gestores)
const handleEditHito = (hito: Hito) => {
    router.visit(`/miembro/mis-proyectos/${props.proyecto.id}/hitos/${hito.id}/edit`);
};

// Handler para completar un entregable (incluye observaciones)
const handleCompleteEntregable = (entregable: Entregable, observaciones: string) => {
    router.post(`/miembro/mis-hitos/${entregable.hito_id}/entregables/${entregable.id}/completar`, {
        notas: observaciones
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Entregable marcado como completado');
        },
        onError: () => {
            toast.error('Error al completar el entregable');
        }
    });
};

// Handler para actualizar estado de un entregable (incluye observaciones)
const handleUpdateEntregableStatus = (entregable: Entregable, estado: string, observaciones: string) => {
    router.put(`/miembro/mis-hitos/${entregable.hito_id}/entregables/${entregable.id}/estado`, {
        estado,
        observaciones
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Estado del entregable actualizado');
        },
        onError: () => {
            toast.error('Error al actualizar el estado');
        }
    });
};

// Handler para añadir un entregable a un hito (solo gestores)
const handleAddEntregable = (hito: Hito) => {
    router.visit(`/miembro/mis-proyectos/${props.proyecto.id}/hitos/${hito.id}/entregables/create`);
};

// Handler para editar un entregable (solo gestores)
const handleEditEntregable = (entregable: Entregable, hito: Hito) => {
    router.visit(`/miembro/mis-proyectos/${props.proyecto.id}/hitos/${hito.id}/entregables/${entregable.id}/edit`);
};
</script>

<template>
    <Head :title="`Mi Proyecto: ${proyecto.nombre}`" />

    <UserLayout>
        <div class="flex h-full flex-1 flex-col rounded-xl p-4">
            <!-- Header con navegación -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <Link href="/miembro/mis-proyectos">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ proyecto.nombre }}
                        </h1>
                        <p v-if="proyecto.descripcion" class="text-gray-600 dark:text-gray-400 mt-1">
                            {{ proyecto.descripcion }}
                        </p>
                    </div>
                </div>
                <Link v-if="canEdit" :href="`/miembro/mis-proyectos/${proyecto.id}/edit`">
                    <Button>
                        <Edit class="mr-2 h-4 w-4" />
                        Editar Proyecto
                    </Button>
                </Link>
            </div>

            <!-- Navegación con Tabs -->
            <Tabs v-model="activeTab" class="w-full">
                <TabsList class="grid w-full grid-cols-4">
                    <TabsTrigger value="general">
                        <Info class="mr-2 h-4 w-4" />
                        General
                    </TabsTrigger>
                    <TabsTrigger value="hitos">
                        <Milestone class="mr-2 h-4 w-4" />
                        Hitos y Entregables
                        <Badge v-if="totales?.hitos" class="ml-2 h-5 px-1.5" variant="secondary">
                            {{ totales.hitos }}
                        </Badge>
                    </TabsTrigger>
                    <TabsTrigger value="usuarios">
                        <UsersIcon class="mr-2 h-4 w-4" />
                        Personas
                        <Badge v-if="totales?.usuarios || totales?.contratos" class="ml-2 h-5 px-1.5" variant="secondary">
                            {{ (totales?.usuarios || 0) + (totales?.contratos || 0) }}
                        </Badge>
                    </TabsTrigger>
                    <TabsTrigger value="evidencias">
                        <Image class="mr-2 h-4 w-4" />
                        Evidencias
                        <Badge v-if="totales?.evidencias" class="ml-2 h-5 px-1.5" variant="secondary">
                            {{ totales.evidencias }}
                        </Badge>
                    </TabsTrigger>
                </TabsList>

                <!-- Tab de Información General -->
                <TabsContent value="general" class="space-y-4 mt-6">
                    <!-- Información del Proyecto (componente reutilizable) -->
                    <ProyectoInfoCard
                        :responsable="proyecto.responsable"
                        :creador="proyecto.creador"
                        :created-at="proyecto.created_at"
                        :updated-at="proyecto.updated_at"
                    />

                    <!-- Estado del Proyecto (componente reutilizable) -->
                    <ProyectoEstadoCard
                        :estado="proyecto.estado"
                        :estado-label="proyecto.estado_label"
                        :prioridad="proyecto.prioridad"
                        :prioridad-label="proyecto.prioridad_label"
                        :porcentaje-completado="proyecto.porcentaje_completado"
                        :fecha-inicio="proyecto.fecha_inicio"
                        :fecha-fin="proyecto.fecha_fin"
                        :duracion-dias="proyecto.duracion_dias"
                    />

                    <!-- Información Adicional (Campos Personalizados) -->
                    <CamposPersonalizadosDisplay
                        v-if="proyecto.campos_personalizados && proyecto.campos_personalizados.length > 0"
                        :valores="proyecto.campos_personalizados"
                        titulo="Información Adicional"
                    />
                </TabsContent>

                <!-- Tab de Usuarios del Proyecto -->
                <TabsContent value="usuarios" class="space-y-4 mt-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Usuarios del Proyecto</CardTitle>
                            <CardDescription>
                                Personas asignadas y colaborando en este proyecto
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <!-- Responsable del proyecto -->
                                <div class="pb-4 border-b">
                                    <h4 class="text-sm font-medium mb-3">Responsable del Proyecto</h4>
                                    <div v-if="proyecto.responsable" class="flex items-center gap-3">
                                        <Avatar class="h-10 w-10">
                                            <AvatarImage :src="proyecto.responsable.avatar" />
                                            <AvatarFallback>{{ getInitials(proyecto.responsable.name) }}</AvatarFallback>
                                        </Avatar>
                                        <div>
                                            <p class="font-medium">{{ proyecto.responsable.name }}</p>
                                            <p class="text-sm text-gray-500">{{ proyecto.responsable.email }}</p>
                                        </div>
                                        <Badge class="ml-auto">Responsable</Badge>
                                    </div>
                                    <p v-else class="text-gray-500">Sin responsable asignado</p>
                                </div>

                                <!-- Participantes -->
                                <div v-if="proyecto.participantes && proyecto.participantes.length > 0">
                                    <h4 class="text-sm font-medium mb-3">Participantes</h4>
                                    <div class="space-y-2">
                                        <div
                                            v-for="participante in proyecto.participantes"
                                            :key="participante.id"
                                            class="flex items-center gap-3 p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800"
                                        >
                                            <Avatar class="h-8 w-8">
                                                <AvatarImage :src="participante.avatar" />
                                                <AvatarFallback>{{ getInitials(participante.name) }}</AvatarFallback>
                                            </Avatar>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium">{{ participante.name }}</p>
                                                <p class="text-xs text-gray-500">{{ participante.email }}</p>
                                            </div>
                                            <Badge v-if="participante.pivot?.rol" variant="outline" class="text-xs">
                                                {{ participante.pivot.rol }}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                                <div v-else>
                                    <p class="text-gray-500 text-center py-4">No hay participantes adicionales</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Contratos del Proyecto -->
                    <Card v-if="proyecto.contratos && proyecto.contratos.length > 0" class="mt-6">
                        <CardHeader>
                            <CardTitle>Contratos del Proyecto</CardTitle>
                            <CardDescription>
                                Haz clic en cualquier contrato para ver sus detalles
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <ContratoCard
                                    v-for="contrato in proyecto.contratos"
                                    :key="contrato.id"
                                    :contrato="contrato"
                                    :show-proyecto="false"
                                    :show-actions="false"
                                    view-mode="user"
                                    compact
                                />
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Tab de Evidencias -->
                <TabsContent value="evidencias" class="space-y-4 mt-6">
                    <EvidenciasDisplay
                        :contratos="proyecto.contratos || []"
                        :proyecto-id="proyecto.id"
                        modo="user"
                        :puede-gestionar-estado="esGestorDelProyecto"
                        :format-date="formatDate"
                    />
                </TabsContent>

                <!-- Tab de Hitos y Entregables -->
                <TabsContent value="hitos" class="space-y-4 mt-6">
                    <HitosDashboard
                        :hitos="proyecto.hitos || []"
                        :proyecto-id="proyecto.id"
                        :can-edit="esGestorDelProyecto"
                        :can-manage-deliverables="esGestorDelProyecto"
                        :can-complete="esGestorDelProyecto"
                        base-url="/miembro/mis-proyectos"
                        @view-hito="navigateToHito"
                        @edit-hito="handleEditHito"
                        @add-entregable="handleAddEntregable"
                        @edit-entregable="handleEditEntregable"
                        @complete-entregable="handleCompleteEntregable"
                        @update-entregable-status="handleUpdateEntregableStatus"
                    />
                </TabsContent>
            </Tabs>
        </div>
    </UserLayout>
</template>