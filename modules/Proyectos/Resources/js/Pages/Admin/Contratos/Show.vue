<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@modules/Core/Resources/js/components/ui/tabs';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import {
    Calendar, DollarSign, User, FileText, Edit, Trash2, Copy, Download,
    Building2, Phone, Mail, Hash, AlertCircle, Clock, CheckCircle,
    XCircle, ArrowLeft, ExternalLink
} from 'lucide-vue-next';
import { useToast } from '@modules/Core/Resources/js/composables/useToast';

// Tipos
interface User {
    id: number;
    name: string;
    email: string;
    phone?: string;
    telefono?: string; // Campo adicional que existe en el modelo User
}

interface Participante {
    id: number;
    name: string;
    email: string;
    pivot: {
        rol: 'participante' | 'observador' | 'aprobador';
        notas?: string;
    };
}

interface Contrato {
    id: number;
    nombre: string;
    descripcion?: string;
    fecha_inicio: string;
    fecha_fin?: string;
    estado: 'borrador' | 'activo' | 'finalizado' | 'cancelado';
    tipo: string;
    monto_total?: number;
    monto_formateado?: string;
    moneda: string;
    archivo_pdf?: string;
    observaciones?: string;
    dias_restantes?: number;
    porcentaje_transcurrido?: number;
    esta_vencido?: boolean;
    esta_proximo_vencer?: boolean;
    created_at: string;
    updated_at: string;
    proyecto: {
        id: number;
        nombre: string;
        estado: string;
    };
    responsable?: {
        id: number;
        name: string;
        email: string;
    };
    contraparte_user_id?: number;
    contraparte_user?: User; // Laravel envía con snake_case
    contraparte_nombre?: string;
    contraparte_identificacion?: string;
    contraparte_email?: string;
    contraparte_telefono?: string;
    participantes?: Participante[];
    created_by?: {
        id: number;
        name: string;
    };
    updated_by?: {
        id: number;
        name: string;
    };
    campos_personalizados?: Array<{
        campo: {
            id: number;
            nombre: string;
            tipo: string;
        };
        valor: any;
        valor_formateado?: string;
    }>;
}

const props = defineProps<{
    contrato: Contrato;
    can: {
        edit: boolean;
        delete: boolean;
        change_status: boolean;
    };
}>();

const toast = useToast();
const activeTab = ref('general');

// Breadcrumbs para navegación
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Contratos', href: '/admin/contratos' },
    { title: props.contrato.nombre, href: `/admin/contratos/${props.contrato.id}` },
];

// Computed
const estadoConfig = computed(() => {
    const configs = {
        'borrador': {
            color: 'bg-gray-100 text-gray-800',
            icon: FileText,
            label: 'Borrador'
        },
        'activo': {
            color: 'bg-green-100 text-green-800',
            icon: CheckCircle,
            label: 'Activo'
        },
        'finalizado': {
            color: 'bg-blue-100 text-blue-800',
            icon: CheckCircle,
            label: 'Finalizado'
        },
        'cancelado': {
            color: 'bg-red-100 text-red-800',
            icon: XCircle,
            label: 'Cancelado'
        }
    };
    return configs[props.contrato.estado] || configs.borrador;
});

const tipoConfig = computed(() => {
    const tipos = {
        'servicio': 'Servicio',
        'obra': 'Obra',
        'suministro': 'Suministro',
        'consultoria': 'Consultoría',
        'otro': 'Otro'
    };
    return tipos[props.contrato.tipo] || 'Otro';
});

const progresoClass = computed(() => {
    if (props.contrato.estado === 'cancelado') return 'bg-red-500';
    if (props.contrato.estado === 'finalizado') return 'bg-blue-500';
    if (props.contrato.esta_vencido) return 'bg-red-500';
    if (props.contrato.esta_proximo_vencer) return 'bg-yellow-500';
    return 'bg-green-500';
});

// Métodos
const formatDate = (date: string) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const formatDateTime = (date: string) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const cambiarEstado = (nuevoEstado: string) => {
    if (confirm(`¿Está seguro de cambiar el estado a ${nuevoEstado}?`)) {
        router.post(route('admin.contratos.cambiar-estado', props.contrato.id), {
            estado: nuevoEstado
        }, {
            onSuccess: () => {
                toast.success('Estado actualizado exitosamente');
            }
        });
    }
};

const eliminarContrato = () => {
    if (confirm('¿Está seguro de eliminar este contrato? Esta acción no se puede deshacer.')) {
        router.delete(route('admin.contratos.destroy', props.contrato.id), {
            onSuccess: () => {
                toast.success('Contrato eliminado exitosamente');
            }
        });
    }
};

const duplicarContrato = () => {
    if (confirm('¿Desea duplicar este contrato?')) {
        router.post(route('admin.contratos.duplicar', props.contrato.id), {}, {
            onSuccess: () => {
                toast.success('Contrato duplicado exitosamente');
            }
        });
    }
};

const descargarPDF = () => {
    if (props.contrato.archivo_pdf) {
        window.open(props.contrato.archivo_pdf, '_blank');
    }
};
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('admin.contratos.index')">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Volver
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold">{{ contrato.nombre }}</h1>
                        <div class="flex items-center gap-4 mt-2">
                            <Badge :class="estadoConfig.color">
                                <component :is="estadoConfig.icon" class="h-4 w-4 mr-1" />
                                {{ estadoConfig.label }}
                            </Badge>
                            <Badge variant="outline">
                                {{ tipoConfig }}
                            </Badge>
                            <Badge v-if="contrato.esta_vencido" variant="destructive">
                                Vencido
                            </Badge>
                            <Badge v-else-if="contrato.esta_proximo_vencer" class="bg-yellow-100 text-yellow-800">
                                Vence en {{ contrato.dias_restantes }} días
                            </Badge>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Button
                        v-if="contrato.archivo_pdf"
                        variant="outline"
                        @click="descargarPDF"
                    >
                        <Download class="h-4 w-4 mr-2" />
                        Descargar PDF
                    </Button>
                    <Button
                        v-if="can.edit"
                        variant="outline"
                        @click="duplicarContrato"
                    >
                        <Copy class="h-4 w-4 mr-2" />
                        Duplicar
                    </Button>
                    <Link v-if="can.edit" :href="route('admin.contratos.edit', contrato.id)">
                        <Button>
                            <Edit class="h-4 w-4 mr-2" />
                            Editar
                        </Button>
                    </Link>
                    <Button
                        v-if="can.delete"
                        variant="destructive"
                        @click="eliminarContrato"
                    >
                        <Trash2 class="h-4 w-4 mr-2" />
                        Eliminar
                    </Button>
                </div>
            </div>

            <!-- Alertas -->
            <Alert v-if="contrato.esta_vencido" variant="destructive">
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>
                    Este contrato venció el {{ formatDate(contrato.fecha_fin) }}.
                </AlertDescription>
            </Alert>

            <Alert v-else-if="contrato.esta_proximo_vencer" variant="warning">
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>
                    Este contrato vencerá en {{ contrato.dias_restantes }} días ({{ formatDate(contrato.fecha_fin) }}).
                </AlertDescription>
            </Alert>

            <!-- Acciones rápidas de estado -->
            <Card v-if="can.change_status && contrato.estado !== 'cancelado'">
                <CardHeader>
                    <CardTitle class="text-lg">Cambio de Estado</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex gap-2">
                        <Button
                            v-if="contrato.estado === 'borrador'"
                            variant="outline"
                            size="sm"
                            @click="cambiarEstado('activo')"
                        >
                            Activar Contrato
                        </Button>
                        <Button
                            v-if="contrato.estado === 'activo'"
                            variant="outline"
                            size="sm"
                            @click="cambiarEstado('finalizado')"
                        >
                            Finalizar Contrato
                        </Button>
                        <Button
                            v-if="contrato.estado === 'finalizado'"
                            variant="outline"
                            size="sm"
                            @click="cambiarEstado('activo')"
                        >
                            Reactivar
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            class="text-red-600"
                            @click="cambiarEstado('cancelado')"
                        >
                            Cancelar Contrato
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Información principal -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Columna principal -->
                <div class="lg:col-span-2 space-y-6">
                    <Tabs v-model="activeTab">
                        <TabsList>
                            <TabsTrigger value="general">Información General</TabsTrigger>
                            <TabsTrigger value="financiero">Financiero</TabsTrigger>
                            <TabsTrigger value="contraparte">Contraparte</TabsTrigger>
                            <TabsTrigger value="participantes" v-if="contrato.participantes?.length">
                                Participantes ({{ contrato.participantes.length }})
                            </TabsTrigger>
                            <TabsTrigger value="campos" v-if="contrato.campos_personalizados?.length">
                                Campos Adicionales
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="general">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Información General</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div v-if="contrato.descripcion">
                                        <h4 class="text-sm font-medium text-gray-600">Descripción</h4>
                                        <p class="mt-1">{{ contrato.descripcion }}</p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-600">Fecha de Inicio</h4>
                                            <p class="mt-1 flex items-center gap-2">
                                                <Calendar class="h-4 w-4" />
                                                {{ formatDate(contrato.fecha_inicio) }}
                                            </p>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-600">Fecha de Fin</h4>
                                            <p class="mt-1 flex items-center gap-2">
                                                <Calendar class="h-4 w-4" />
                                                {{ formatDate(contrato.fecha_fin) }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Barra de progreso -->
                                    <div v-if="contrato.porcentaje_transcurrido !== undefined && contrato.estado === 'activo'">
                                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                                            <span>Progreso del contrato</span>
                                            <span>{{ contrato.porcentaje_transcurrido }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-3">
                                            <div
                                                :class="progresoClass"
                                                class="h-3 rounded-full transition-all duration-300"
                                                :style="{ width: `${contrato.porcentaje_transcurrido}%` }"
                                            />
                                        </div>
                                    </div>

                                    <div v-if="contrato.observaciones">
                                        <h4 class="text-sm font-medium text-gray-600">Observaciones</h4>
                                        <p class="mt-1 text-gray-700">{{ contrato.observaciones }}</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="financiero">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Información Financiera</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div v-if="contrato.monto_total">
                                        <h4 class="text-sm font-medium text-gray-600">Monto Total</h4>
                                        <p class="mt-1 text-2xl font-bold flex items-center gap-2">
                                            <DollarSign class="h-6 w-6" />
                                            {{ contrato.monto_formateado || contrato.monto_total }}
                                            <span class="text-sm text-gray-600">{{ contrato.moneda }}</span>
                                        </p>
                                    </div>
                                    <div v-else>
                                        <p class="text-gray-500">No se ha especificado información financiera</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="contraparte">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Información de la Contraparte</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <!-- Si la contraparte es un usuario del sistema -->
                                    <div v-if="contrato.contraparte_user">
                                        <div class="rounded-lg border p-4 bg-blue-50 dark:bg-blue-950/20">
                                            <div class="flex items-start gap-4">
                                                <User class="h-8 w-8 text-blue-600 mt-1" />
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <h3 class="font-semibold text-lg">{{ contrato.contraparte_user.name }}</h3>
                                                        <Badge class="bg-blue-100 text-blue-800">
                                                            Usuario del Sistema
                                                        </Badge>
                                                    </div>
                                                    <div class="space-y-2 text-sm">
                                                        <div class="flex items-center gap-2">
                                                            <Mail class="h-4 w-4 text-gray-400" />
                                                            <a :href="`mailto:${contrato.contraparte_user.email}`" class="text-blue-600 hover:underline">
                                                                {{ contrato.contraparte_user.email }}
                                                            </a>
                                                        </div>
                                                        <div v-if="contrato.contraparte_user.phone || contrato.contraparte_user.telefono" class="flex items-center gap-2">
                                                            <Phone class="h-4 w-4 text-gray-400" />
                                                            <a :href="`tel:${contrato.contraparte_user.phone || contrato.contraparte_user.telefono}`" class="text-blue-600 hover:underline">
                                                                {{ contrato.contraparte_user.phone || contrato.contraparte_user.telefono }}
                                                            </a>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <Hash class="h-4 w-4 text-gray-400" />
                                                            <span class="text-gray-600">ID: #{{ contrato.contraparte_user.id }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Si la contraparte es externa (datos de texto) -->
                                    <div v-else-if="contrato.contraparte_nombre || contrato.contraparte_identificacion ||
                                                   contrato.contraparte_email || contrato.contraparte_telefono">
                                        <div class="space-y-4">
                                            <div v-if="contrato.contraparte_nombre" class="flex items-center gap-2">
                                                <Building2 class="h-4 w-4 text-gray-400" />
                                                <div>
                                                    <h4 class="text-sm font-medium text-gray-600">Nombre</h4>
                                                    <p>{{ contrato.contraparte_nombre }}</p>
                                                </div>
                                            </div>

                                            <div v-if="contrato.contraparte_identificacion" class="flex items-center gap-2">
                                                <Hash class="h-4 w-4 text-gray-400" />
                                                <div>
                                                    <h4 class="text-sm font-medium text-gray-600">Identificación</h4>
                                                    <p>{{ contrato.contraparte_identificacion }}</p>
                                                </div>
                                            </div>

                                            <div v-if="contrato.contraparte_email" class="flex items-center gap-2">
                                                <Mail class="h-4 w-4 text-gray-400" />
                                                <div>
                                                    <h4 class="text-sm font-medium text-gray-600">Email</h4>
                                                    <a :href="`mailto:${contrato.contraparte_email}`" class="text-blue-600 hover:underline">
                                                        {{ contrato.contraparte_email }}
                                                    </a>
                                                </div>
                                            </div>

                                            <div v-if="contrato.contraparte_telefono" class="flex items-center gap-2">
                                                <Phone class="h-4 w-4 text-gray-400" />
                                                <div>
                                                    <h4 class="text-sm font-medium text-gray-600">Teléfono</h4>
                                                    <a :href="`tel:${contrato.contraparte_telefono}`" class="text-blue-600 hover:underline">
                                                        {{ contrato.contraparte_telefono }}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else>
                                        <p class="text-gray-500">No se ha especificado información de la contraparte</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="participantes" v-if="contrato.participantes?.length">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Participantes del Contrato</CardTitle>
                                    <CardDescription>
                                        Usuarios del sistema asociados a este contrato
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-4">
                                        <div
                                            v-for="participante in contrato.participantes"
                                            :key="participante.id"
                                            class="flex items-center justify-between p-4 rounded-lg border hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors"
                                        >
                                            <div class="flex items-center gap-3">
                                                <User class="h-8 w-8 text-gray-400" />
                                                <div>
                                                    <p class="font-medium">{{ participante.name }}</p>
                                                    <p class="text-sm text-gray-500">{{ participante.email }}</p>
                                                    <p v-if="participante.pivot.notas" class="text-sm text-gray-600 mt-1">
                                                        {{ participante.pivot.notas }}
                                                    </p>
                                                </div>
                                            </div>
                                            <Badge
                                                :variant="
                                                    participante.pivot.rol === 'aprobador' ? 'default' :
                                                    participante.pivot.rol === 'observador' ? 'secondary' :
                                                    'outline'
                                                "
                                            >
                                                {{
                                                    participante.pivot.rol === 'aprobador' ? 'Aprobador' :
                                                    participante.pivot.rol === 'observador' ? 'Observador' :
                                                    'Participante'
                                                }}
                                            </Badge>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="campos" v-if="contrato.campos_personalizados?.length">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Campos Adicionales</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-4">
                                        <div v-for="campo in contrato.campos_personalizados" :key="campo.campo.id">
                                            <h4 class="text-sm font-medium text-gray-600">{{ campo.campo.nombre }}</h4>
                                            <p class="mt-1">{{ campo.valor_formateado || campo.valor || '-' }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>

                <!-- Columna lateral -->
                <div class="space-y-6">
                    <!-- Proyecto -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Proyecto</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Link
                                :href="route('admin.proyectos.show', contrato.proyecto.id)"
                                class="text-blue-600 hover:underline flex items-center gap-2"
                            >
                                {{ contrato.proyecto.nombre }}
                                <ExternalLink class="h-4 w-4" />
                            </Link>
                            <Badge variant="outline" class="mt-2">
                                {{ contrato.proyecto.estado }}
                            </Badge>
                        </CardContent>
                    </Card>

                    <!-- Responsable -->
                    <Card v-if="contrato.responsable">
                        <CardHeader>
                            <CardTitle class="text-lg">Responsable</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center gap-3">
                                <User class="h-8 w-8 text-gray-400" />
                                <div>
                                    <p class="font-medium">{{ contrato.responsable.name }}</p>
                                    <a
                                        :href="`mailto:${contrato.responsable.email}`"
                                        class="text-sm text-blue-600 hover:underline"
                                    >
                                        {{ contrato.responsable.email }}
                                    </a>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Metadatos -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Información del Sistema</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm">
                            <div>
                                <span class="text-gray-600">ID:</span>
                                <span class="ml-2 font-mono">#{{ contrato.id }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Creado:</span>
                                <span class="ml-2">{{ formatDateTime(contrato.created_at) }}</span>
                                <span v-if="contrato.created_by" class="block text-xs text-gray-500 ml-2">
                                    por {{ contrato.created_by.name }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-600">Última actualización:</span>
                                <span class="ml-2">{{ formatDateTime(contrato.updated_at) }}</span>
                                <span v-if="contrato.updated_by" class="block text-xs text-gray-500 ml-2">
                                    por {{ contrato.updated_by.name }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>