<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { type BreadcrumbItemType } from '@/types';
import UserLayout from '@modules/Core/Resources/js/layouts/UserLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@modules/Core/Resources/js/components/ui/tabs';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import {
    ArrowLeft,
    Calendar,
    DollarSign,
    User,
    Building,
    FileText,
    Download,
    AlertTriangle,
    Clock,
    CheckCircle,
    XCircle,
    Info,
    Briefcase,
    Upload,
    Camera,
    ClipboardList,
    Eye
} from 'lucide-vue-next';
import type { Contrato, ValorCampoPersonalizadoContrato } from '@modules/Proyectos/Resources/js/types/contratos';

// Props
const props = defineProps<{
    contrato: Contrato & {
        proyecto: {
            id: number;
            nombre: string;
            estado: string;
            hitos?: any[];
        };
        responsable?: {
            id: number;
            name: string;
            email: string;
        };
        obligaciones?: Array<{
            id: number;
            nombre: string;
            descripcion?: string;
            fecha_vencimiento?: string;
            estado: string;
            evidencias?: Array<{
                id: number;
                tipo_evidencia: string;
                descripcion?: string;
                estado: string;
                created_at: string;
            }>;
        }>;
        campos_personalizados?: ValorCampoPersonalizadoContrato[];
    };
    authPermissions: string[];
    puedeSubirEvidencias?: boolean;
    entregablesDisponibles?: Array<{
        id: number;
        nombre: string;
        hito: string;
        estado: string;
    }>;
}>();

// Breadcrumbs para navegación
const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Dashboard', href: '/miembro/dashboard' },
    { title: 'Mis Contratos', href: '/miembro/mis-contratos' },
    { title: props.contrato.nombre, href: `/miembro/mis-contratos/${props.contrato.id}` },
];

// Computed
const estadoConfig = computed(() => {
    const configs = {
        'borrador': { color: 'secondary', icon: FileText },
        'activo': { color: 'success', icon: CheckCircle },
        'finalizado': { color: 'default', icon: CheckCircle },
        'cancelado': { color: 'destructive', icon: XCircle }
    };
    return configs[props.contrato.estado] || { color: 'default', icon: FileText };
});

const tipoConfig = computed(() => {
    const configs = {
        'servicio': { label: 'Servicio', icon: Briefcase },
        'obra': { label: 'Obra', icon: Building },
        'suministro': { label: 'Suministro', icon: FileText },
        'consultoria': { label: 'Consultoría', icon: User },
        'otro': { label: 'Otro', icon: FileText }
    };
    return configs[props.contrato.tipo] || { label: 'Otro', icon: FileText };
});

const diasRestantes = computed(() => {
    if (!props.contrato.fecha_fin || props.contrato.estado !== 'activo') return null;
    const hoy = new Date();
    const fin = new Date(props.contrato.fecha_fin);
    const diff = Math.ceil((fin.getTime() - hoy.getTime()) / (1000 * 60 * 60 * 24));
    return diff;
});

const estaVencido = computed(() => {
    return diasRestantes.value !== null && diasRestantes.value < 0;
});

const proximoVencer = computed(() => {
    return diasRestantes.value !== null && diasRestantes.value >= 0 && diasRestantes.value <= 30;
});

const canDownloadPDF = computed(() =>
    props.contrato.archivo_pdf && props.authPermissions.includes('contratos.view_own')
);

const formatDate = (date: string) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const formatCurrency = (amount: number) => {
    if (!amount) return '-';
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: props.contrato.moneda || 'USD'
    }).format(amount);
};

// Tabs válidos para validación
const validTabs = ['general', 'partes', 'financiera', 'obligaciones', 'campos', 'observaciones'];

// Estado para el tab activo - leer de URL query params
const getInitialTab = (): string => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabFromUrl = urlParams.get('tab');
    return tabFromUrl && validTabs.includes(tabFromUrl) ? tabFromUrl : 'general';
};

const activeTab = ref(getInitialTab());

// Sincronizar tab con URL usando query params
watch(activeTab, (newTab) => {
    const url = `/miembro/mis-contratos/${props.contrato.id}?tab=${newTab}`;
    router.get(url, {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: []
    });
});

</script>

<template>
    <UserLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-start">
            <div>
                <Link :href="route('user.mis-contratos.index')" class="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-2">
                    <ArrowLeft class="w-4 h-4 mr-2" />
                    Volver a Mis Contratos
                </Link>
                <h2 class="text-3xl font-bold tracking-tight">{{ contrato.nombre }}</h2>
                <div class="flex items-center gap-4 mt-2">
                    <Badge :variant="estadoConfig.color">
                        <component :is="estadoConfig.icon" class="w-3 h-3 mr-1" />
                        {{ contrato.estado }}
                    </Badge>
                    <Badge variant="outline">
                        <component :is="tipoConfig.icon" class="w-3 h-3 mr-1" />
                        {{ tipoConfig.label }}
                    </Badge>
                    <span class="text-muted-foreground">
                        Proyecto:
                        <Link
                            :href="route('user.mis-proyectos.show', contrato.proyecto.id)"
                            class="font-medium text-foreground hover:underline"
                        >
                            {{ contrato.proyecto.nombre }}
                        </Link>
                    </span>
                </div>
            </div>
            <div class="flex gap-2">
                <Button
                    v-if="canDownloadPDF"
                    @click="window.open(route('user.mis-contratos.descargar-pdf', contrato.id))"
                    variant="outline"
                >
                    <Download class="w-4 h-4 mr-2" />
                    Descargar PDF
                </Button>
            </div>
        </div>

        <!-- Alertas -->
        <Alert v-if="estaVencido" class="border-red-200 bg-red-50">
            <AlertTriangle class="h-4 w-4 text-red-600" />
            <AlertDescription class="text-red-800">
                Este contrato venció hace {{ Math.abs(diasRestantes) }} días.
            </AlertDescription>
        </Alert>

        <Alert v-else-if="proximoVencer" class="border-orange-200 bg-orange-50">
            <Clock class="h-4 w-4 text-orange-600" />
            <AlertDescription class="text-orange-800">
                Este contrato vence en {{ diasRestantes }} días.
            </AlertDescription>
        </Alert>

        <!-- Contenido principal -->
        <Tabs v-model="activeTab" class="space-y-4">
            <TabsList>
                <TabsTrigger value="general">Información General</TabsTrigger>
                <TabsTrigger value="partes">Partes Involucradas</TabsTrigger>
                <TabsTrigger value="financiera">Información Financiera</TabsTrigger>
                <TabsTrigger value="obligaciones" v-if="contrato.obligaciones?.length > 0">
                    <ClipboardList class="w-4 h-4 mr-2" />
                    Obligaciones y Evidencias
                </TabsTrigger>
                <TabsTrigger value="campos" v-if="contrato.campos_personalizados?.length > 0">
                    Campos Adicionales
                </TabsTrigger>
                <TabsTrigger value="observaciones" v-if="contrato.observaciones">
                    Observaciones
                </TabsTrigger>
            </TabsList>

            <!-- Tab: Información General -->
            <TabsContent value="general">
                <Card>
                    <CardHeader>
                        <CardTitle>Información General</CardTitle>
                        <CardDescription>
                            Detalles básicos del contrato
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid gap-6 md:grid-cols-2">
                            <!-- Descripción -->
                            <div v-if="contrato.descripcion" class="col-span-2">
                                <label class="text-sm font-medium text-muted-foreground">
                                    Descripción
                                </label>
                                <p class="mt-1">{{ contrato.descripcion }}</p>
                            </div>

                            <!-- Fechas -->
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">
                                    <Calendar class="w-4 h-4 inline mr-1" />
                                    Fecha de Inicio
                                </label>
                                <p class="mt-1 font-medium">{{ formatDate(contrato.fecha_inicio) }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-muted-foreground">
                                    <Calendar class="w-4 h-4 inline mr-1" />
                                    Fecha de Fin
                                </label>
                                <p class="mt-1 font-medium">{{ formatDate(contrato.fecha_fin) }}</p>
                            </div>

                            <!-- Duración -->
                            <div v-if="contrato.fecha_inicio && contrato.fecha_fin">
                                <label class="text-sm font-medium text-muted-foreground">
                                    <Clock class="w-4 h-4 inline mr-1" />
                                    Duración
                                </label>
                                <p class="mt-1 font-medium">
                                    {{ Math.ceil((new Date(contrato.fecha_fin).getTime() - new Date(contrato.fecha_inicio).getTime()) / (1000 * 60 * 60 * 24)) }} días
                                </p>
                            </div>

                            <!-- Días restantes -->
                            <div v-if="diasRestantes !== null && contrato.estado === 'activo'">
                                <label class="text-sm font-medium text-muted-foreground">
                                    <Clock class="w-4 h-4 inline mr-1" />
                                    Tiempo Restante
                                </label>
                                <p class="mt-1 font-medium" :class="{
                                    'text-red-600': estaVencido,
                                    'text-orange-600': proximoVencer,
                                    'text-green-600': !estaVencido && !proximoVencer
                                }">
                                    <span v-if="estaVencido">Vencido hace {{ Math.abs(diasRestantes) }} días</span>
                                    <span v-else-if="diasRestantes === 0">Vence hoy</span>
                                    <span v-else>{{ diasRestantes }} días restantes</span>
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- Tab: Partes Involucradas -->
            <TabsContent value="partes">
                <Card>
                    <CardHeader>
                        <CardTitle>Partes Involucradas</CardTitle>
                        <CardDescription>
                            Información de los responsables y contrapartes
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid gap-6 md:grid-cols-2">
                            <!-- Responsable -->
                            <div>
                                <h4 class="font-semibold mb-3 flex items-center">
                                    <User class="w-4 h-4 mr-2" />
                                    Responsable Interno
                                </h4>
                                <div v-if="contrato.responsable" class="space-y-2">
                                    <p>
                                        <span class="text-muted-foreground">Nombre:</span>
                                        <span class="ml-2">{{ contrato.responsable.name }}</span>
                                    </p>
                                    <p>
                                        <span class="text-muted-foreground">Email:</span>
                                        <a :href="`mailto:${contrato.responsable.email}`" class="ml-2 text-primary hover:underline">
                                            {{ contrato.responsable.email }}
                                        </a>
                                    </p>
                                </div>
                                <p v-else class="text-muted-foreground">
                                    No se ha asignado un responsable
                                </p>
                            </div>

                            <!-- Contraparte -->
                            <div>
                                <h4 class="font-semibold mb-3 flex items-center">
                                    <Building class="w-4 h-4 mr-2" />
                                    Contraparte
                                </h4>
                                <!-- Si la contraparte es un usuario del sistema -->
                                <div v-if="contrato.contraparte_user" class="space-y-2">
                                    <div class="rounded-lg border p-3 bg-blue-50 dark:bg-blue-950/20">
                                        <div class="flex items-start gap-3">
                                            <User class="w-5 h-5 text-blue-600 mt-1" />
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="font-medium">{{ contrato.contraparte_user.name }}</span>
                                                    <Badge variant="secondary" class="text-xs">
                                                        Usuario del Sistema
                                                    </Badge>
                                                </div>
                                                <p class="text-sm text-muted-foreground">
                                                    <a :href="`mailto:${contrato.contraparte_user.email}`" class="text-primary hover:underline">
                                                        {{ contrato.contraparte_user.email }}
                                                    </a>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Si la contraparte es externa (datos de texto) -->
                                <div v-else-if="contrato.contraparte_nombre || contrato.contraparte_identificacion ||
                                               contrato.contraparte_email || contrato.contraparte_telefono" class="space-y-2">
                                    <p v-if="contrato.contraparte_nombre">
                                        <span class="text-muted-foreground">Nombre:</span>
                                        <span class="ml-2">{{ contrato.contraparte_nombre }}</span>
                                    </p>
                                    <p v-if="contrato.contraparte_identificacion">
                                        <span class="text-muted-foreground">Identificación:</span>
                                        <span class="ml-2">{{ contrato.contraparte_identificacion }}</span>
                                    </p>
                                    <p v-if="contrato.contraparte_email">
                                        <span class="text-muted-foreground">Email:</span>
                                        <a :href="`mailto:${contrato.contraparte_email}`" class="ml-2 text-primary hover:underline">
                                            {{ contrato.contraparte_email }}
                                        </a>
                                    </p>
                                    <p v-if="contrato.contraparte_telefono">
                                        <span class="text-muted-foreground">Teléfono:</span>
                                        <span class="ml-2">{{ contrato.contraparte_telefono }}</span>
                                    </p>
                                </div>
                                <!-- Si no hay información de contraparte -->
                                <div v-else>
                                    <p class="text-muted-foreground">
                                        No se ha registrado información de la contraparte
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- Tab: Información Financiera -->
            <TabsContent value="financiera">
                <Card>
                    <CardHeader>
                        <CardTitle>Información Financiera</CardTitle>
                        <CardDescription>
                            Detalles económicos del contrato
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">
                                    <DollarSign class="w-4 h-4 inline mr-1" />
                                    Monto Total
                                </label>
                                <p class="mt-1 text-2xl font-bold">
                                    {{ formatCurrency(contrato.monto_total) }}
                                </p>
                            </div>

                            <div v-if="contrato.moneda">
                                <label class="text-sm font-medium text-muted-foreground">
                                    Moneda
                                </label>
                                <p class="mt-1">{{ contrato.moneda }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- Tab: Campos Personalizados -->
            <TabsContent value="campos" v-if="contrato.campos_personalizados?.length > 0">
                <Card>
                    <CardHeader>
                        <CardTitle>Campos Adicionales</CardTitle>
                        <CardDescription>
                            Información personalizada del contrato
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div
                                v-for="campo in contrato.campos_personalizados"
                                :key="campo.id"
                                :class="{ 'col-span-2': campo.campo?.tipo === 'textarea' }"
                            >
                                <label class="text-sm font-medium text-muted-foreground">
                                    {{ campo.campo?.nombre }}
                                </label>
                                <p class="mt-1" v-if="campo.campo?.tipo === 'checkbox'">
                                    <Badge v-if="campo.valor === 'true'" variant="default">Sí</Badge>
                                    <Badge v-else variant="secondary">No</Badge>
                                </p>
                                <p class="mt-1" v-else-if="campo.campo?.tipo === 'date'">
                                    {{ formatDate(campo.valor) }}
                                </p>
                                <p class="mt-1 whitespace-pre-wrap" v-else>
                                    {{ campo.valor || '-' }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- Tab: Obligaciones y Evidencias -->
            <TabsContent value="obligaciones" v-if="contrato.obligaciones?.length > 0">
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>Obligaciones y Evidencias</CardTitle>
                                <CardDescription>
                                    Gestione las evidencias de cumplimiento de sus obligaciones contractuales
                                </CardDescription>
                            </div>
                            <Button
                                v-if="puedeSubirEvidencias"
                                :href="route('user.mis-contratos.evidencias.create', contrato.id)"
                                :as="Link"
                                class="gap-2"
                            >
                                <Upload class="w-4 h-4" />
                                Subir Evidencias
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div
                            v-for="obligacion in contrato.obligaciones"
                            :key="obligacion.id"
                            class="border rounded-lg p-4"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h4 class="font-semibold">{{ obligacion.titulo }}</h4>
                                    <p v-if="obligacion.descripcion" class="text-sm text-muted-foreground mt-1">
                                        {{ obligacion.descripcion }}
                                    </p>
                                    <div v-if="obligacion.fecha_vencimiento" class="flex items-center gap-2 mt-2">
                                        <Calendar class="w-4 h-4 text-muted-foreground" />
                                        <span class="text-sm text-muted-foreground">
                                            Vence: {{ formatDate(obligacion.fecha_vencimiento) }}
                                        </span>
                                    </div>
                                </div>
                                <Badge
                                    :variant="obligacion.estado === 'cumplida' ? 'success' :
                                            obligacion.estado === 'pendiente' ? 'warning' : 'secondary'"
                                >
                                    {{ obligacion.estado }}
                                </Badge>
                            </div>

                            <!-- Evidencias de la obligación -->
                            <div v-if="obligacion.evidencias?.length > 0" class="mt-3 pt-3 border-t">
                                <p class="text-sm font-medium mb-2">Evidencias enviadas:</p>
                                <div class="space-y-2">
                                    <div
                                        v-for="evidencia in obligacion.evidencias"
                                        :key="evidencia.id"
                                        class="flex items-center justify-between bg-muted/30 rounded px-3 py-2"
                                    >
                                        <div class="flex items-center gap-2">
                                            <Camera v-if="evidencia.tipo_evidencia === 'imagen'" class="w-4 h-4 text-muted-foreground" />
                                            <FileText v-else class="w-4 h-4 text-muted-foreground" />
                                            <div>
                                                <p class="text-sm">{{ evidencia.descripcion || 'Sin descripción' }}</p>
                                                <p class="text-xs text-muted-foreground">
                                                    {{ new Date(evidencia.created_at).toLocaleDateString('es-ES') }}
                                                </p>
                                            </div>
                                        </div>
                                        <Badge
                                            :variant="evidencia.estado === 'aprobada' ? 'success' :
                                                    evidencia.estado === 'rechazada' ? 'destructive' : 'secondary'"
                                            class="text-xs"
                                        >
                                            {{ evidencia.estado }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="mt-3 pt-3 border-t">
                                <p class="text-sm text-muted-foreground italic">
                                    No hay evidencias registradas para esta obligación
                                </p>
                            </div>
                        </div>

                        <!-- Si no hay obligaciones -->
                        <div v-if="!contrato.obligaciones?.length" class="text-center py-8">
                            <ClipboardList class="w-12 h-12 mx-auto text-muted-foreground mb-3" />
                            <p class="text-muted-foreground">
                                No hay obligaciones registradas para este contrato
                            </p>
                        </div>

                        <!-- Botón adicional al final -->
                        <div v-if="contrato.obligaciones?.length > 0 && puedeSubirEvidencias" class="pt-4 border-t flex justify-center">
                            <Button
                                :href="route('user.mis-contratos.evidencias.index', contrato.id)"
                                :as="Link"
                                variant="outline"
                                class="gap-2"
                            >
                                <Eye class="w-4 h-4" />
                                Ver Todas las Evidencias
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- Tab: Observaciones -->
            <TabsContent value="observaciones" v-if="contrato.observaciones">
                <Card>
                    <CardHeader>
                        <CardTitle>Observaciones</CardTitle>
                        <CardDescription>
                            Notas y comentarios adicionales
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="prose max-w-none">
                            <p class="whitespace-pre-wrap">{{ contrato.observaciones }}</p>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>
        </Tabs>
        </div>
    </UserLayout>
</template>