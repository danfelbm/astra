<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Clock, Eye, Settings, UserCheck, AlertCircle, Mail, MessageSquare, Send } from 'lucide-vue-next';
import AdvancedFilters from '@/components/filters/AdvancedFilters.vue';
import Pagination from '@/components/ui/pagination/Pagination.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Checkbox } from '@/components/ui/checkbox';
import type { AdvancedFilterConfig } from '@/types/filters';
import { ref, watch } from 'vue';

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface Candidatura {
    id: number;
    usuario: Usuario;
    estado: string;
    estado_label: string;
    estado_color: string;
    version: number;
    comentarios_admin?: string;
    aprobado_por?: Usuario;
    fecha_aprobacion?: string;
    created_at: string;
    updated_at: string;
    tiene_datos: boolean;
    campos_llenados: number;
    total_campos: number;
    porcentaje_completado: number;
    esta_pendiente: boolean;
}

interface Props {
    candidaturas: {
        data: Candidatura[];
        links: any[];
        current_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        estado?: string;
        search?: string;
    
        advanced_filters?: string;};
    filterFieldsConfig: any[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Candidaturas', href: '#' },
];

// Filtros reactivos
const filters = ref({
    estado: props.filters.estado || 'null',
    search: props.filters.search || '',
});

// Aplicar filtros cuando cambien
watch(filters, (newFilters) => {
    // Convertir "null" strings a strings vacíos para el backend
    const cleanFilters = Object.entries(newFilters).reduce((acc, [key, value]) => {
        acc[key] = value === 'null' ? '' : value;
        return acc;
    }, {} as Record<string, string>);
    
    router.get('/admin/candidaturas', cleanFilters, {
        preserveState: true,
        replace: true,
    });
}, { deep: true });

// Configuración para el componente de filtros avanzados
const filterConfig: AdvancedFilterConfig = {
    fields: props.filterFieldsConfig || [],
    showQuickSearch: true,
    quickSearchPlaceholder: 'Buscar por nombre o email...',
    quickSearchFields: ['user.name', 'user.email'],
    maxNestingLevel: 2,
    allowSaveFilters: true,
    debounceTime: 500,
    autoApply: false,
};

// Helper para obtener route
const { route } = window as any;

// Variables reactivas para el modal de recordatorios
const modalAbierto = ref(false);
const enviandoRecordatorios = ref(false);
const incluirEmail = ref(true);
const incluirWhatsApp = ref(true);
const estadisticasBorradores = ref({
    total_borradores: 0,
    con_email: 0,
    con_telefono: 0,
    sin_email: 0,
    sin_telefono: 0
});

// Código de estadísticas removido - se moverá a un dashboard unificado

// Función para formatear fecha
const formatearFecha = (fecha: string) => {
    return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Función para abrir modal de recordatorios
const abrirModalRecordatorios = async () => {
    try {
        // Obtener estadísticas de candidaturas en borrador
        const response = await fetch(route('admin.candidaturas.estadisticas-borradores'));
        const stats = await response.json();
        estadisticasBorradores.value = stats;
        modalAbierto.value = true;
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
        alert('Error cargando estadísticas de candidaturas en borrador');
    }
};

// Función para enviar recordatorios
const enviarRecordatorios = async () => {
    if (!incluirEmail.value && !incluirWhatsApp.value) {
        alert('Debes seleccionar al menos un tipo de recordatorio (email o WhatsApp)');
        return;
    }

    enviandoRecordatorios.value = true;
    
    try {
        const response = await fetch(route('admin.candidaturas.enviar-recordatorios'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                incluir_email: incluirEmail.value,
                incluir_whatsapp: incluirWhatsApp.value
            })
        });

        const result = await response.json();
        
        if (result.success) {
            modalAbierto.value = false;
            alert(`Recordatorios enviados exitosamente!\n\n` +
                  `• Candidaturas procesadas: ${result.contadores.total_candidaturas}\n` +
                  `• Correos programados: ${result.contadores.emails_enviados}\n` +
                  `• WhatsApps programados: ${result.contadores.whatsapps_enviados}\n` +
                  `• Errores: ${result.contadores.errores}\n\n` +
                  `Los mensajes se están enviando en segundo plano respetando los límites de velocidad configurados.`);
        } else {
            alert(`Error: ${result.message}`);
        }
    } catch (error) {
        console.error('Error enviando recordatorios:', error);
        alert('Error enviando recordatorios. Por favor intenta de nuevo.');
    } finally {
        enviandoRecordatorios.value = false;
    }
};
</script>

<template>
    <Head title="Candidaturas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Candidaturas</h1>
                    <p class="text-muted-foreground">
                        Revisa y gestiona los perfiles de candidatura de los usuarios
                    </p>
                </div>
                <div class="flex gap-3">
                    <!-- Botón de Recordatorios -->
                    <Button 
                        @click="abrirModalRecordatorios"
                        variant="outline"
                        class="bg-orange-50 border-orange-200 text-orange-700 hover:bg-orange-100 hover:border-orange-300"
                    >
                        <Send class="mr-2 h-4 w-4" />
                        Recordatorios
                    </Button>
                    
                    <!-- Botón de Configuración -->
                    <Link href="/admin/candidaturas/configuracion">
                        <Button>
                            <Settings class="mr-2 h-4 w-4" />
                            Configuración
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Advanced Filters -->
            <AdvancedFilters
                :config="filterConfig"
                :route="route('admin.candidaturas.index')"
                :initial-filters="{
                    quickSearch: filters.search,
                    rootGroup: filters.advanced_filters ? JSON.parse(filters.advanced_filters) : undefined
                }"
            />

            <!-- Lista de Candidaturas -->
            <Card>
                <CardContent class="pt-6">
                    <div v-if="candidaturas.data.length === 0" class="text-center py-8">
                        <UserCheck class="mx-auto h-12 w-12 text-muted-foreground" />
                        <h3 class="mt-4 text-lg font-medium">No hay candidaturas</h3>
                        <p class="text-muted-foreground">No se encontraron candidaturas con los filtros aplicados.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="candidatura in candidaturas.data"
                            :key="candidatura.id"
                            class="border rounded-lg p-4 hover:bg-muted/50 transition-colors"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="text-lg font-semibold">{{ candidatura.usuario.name }}</h3>
                                        <Badge :class="candidatura.estado_color">
                                            {{ candidatura.estado_label }}
                                        </Badge>
                                        <Badge v-if="candidatura.esta_pendiente" variant="outline" class="bg-blue-50 text-blue-700">
                                            Pendiente Revisión
                                        </Badge>
                                    </div>
                                    
                                    <p class="text-muted-foreground mb-3">
                                        {{ candidatura.usuario.email }}
                                    </p>

                                    <div class="grid gap-2 md:grid-cols-2 lg:grid-cols-4 text-sm">
                                        <div class="flex items-center gap-2">
                                            <Clock class="h-4 w-4 text-muted-foreground" />
                                            <span class="font-medium">Versión:</span>
                                            <span>{{ candidatura.version }}</span>
                                        </div>
                                        
                                        <div class="flex items-center gap-2">
                                            <AlertCircle class="h-4 w-4 text-muted-foreground" />
                                            <span class="font-medium">Datos:</span>
                                            <span v-if="candidatura.total_campos > 0">
                                                {{ candidatura.campos_llenados }} / {{ candidatura.total_campos }}
                                                <span class="text-muted-foreground">({{ candidatura.porcentaje_completado }}%)</span>
                                            </span>
                                            <span v-else>Sin configuración</span>
                                        </div>
                                        
                                        <div v-if="candidatura.aprobado_por" class="flex items-center gap-2">
                                            <UserCheck class="h-4 w-4 text-muted-foreground" />
                                            <span class="font-medium">Aprobado por:</span>
                                            <span>{{ candidatura.aprobado_por.name }}</span>
                                        </div>
                                        
                                        <div class="flex items-center gap-2">
                                            <Clock class="h-4 w-4 text-muted-foreground" />
                                            <span class="font-medium">Actualizado:</span>
                                            <span>{{ formatearFecha(candidatura.updated_at) }}</span>
                                        </div>
                                    </div>

                                    <div v-if="candidatura.comentarios_admin" class="mt-3 p-2 bg-blue-50 dark:bg-blue-950/20 rounded border-l-4 border-blue-200 dark:border-blue-800">
                                        <p class="text-sm text-blue-800 dark:text-blue-200">
                                            <span class="font-medium">Comentarios de la comisión:</span>
                                            {{ candidatura.comentarios_admin }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 ml-4">
                                    <Link :href="`/admin/candidaturas/${candidatura.id}`">
                                        <Button variant="outline" size="sm">
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paginación -->
                    <Pagination :links="candidaturas.links" />
                </CardContent>
            </Card>

            <!-- Modal de Recordatorios -->
            <Dialog v-model:open="modalAbierto">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2">
                            <Send class="h-5 w-5 text-orange-600" />
                            Enviar Recordatorios Masivos
                        </DialogTitle>
                        <DialogDescription>
                            Envía recordatorios por correo y/o WhatsApp a todas las candidaturas en estado borrador.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="space-y-6">
                        <!-- Estadísticas de candidaturas en borrador -->
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <div class="flex items-center gap-2 mb-3">
                                <AlertCircle class="h-5 w-5 text-orange-600" />
                                <h3 class="font-medium text-orange-900">Candidaturas en Borrador</h3>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-orange-700">Total en borrador:</span>
                                    <span class="font-medium text-orange-900">{{ estadisticasBorradores.total_borradores }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-orange-700">Con email:</span>
                                    <span class="font-medium text-orange-900">{{ estadisticasBorradores.con_email }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-orange-700">Con teléfono:</span>
                                    <span class="font-medium text-orange-900">{{ estadisticasBorradores.con_telefono }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-red-600">Sin contacto:</span>
                                    <span class="font-medium text-red-700">
                                        {{ Math.max(0, estadisticasBorradores.total_borradores - Math.max(estadisticasBorradores.con_email, estadisticasBorradores.con_telefono)) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Opciones de envío -->
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-900">Selecciona los tipos de recordatorio:</h4>
                            
                            <div class="space-y-3">
                                <div class="flex items-center space-x-3">
                                    <Checkbox 
                                        :id="'include-email'" 
                                        v-model="incluirEmail" 
                                        :disabled="estadisticasBorradores.con_email === 0"
                                    />
                                    <div class="flex items-center gap-2">
                                        <Mail class="h-4 w-4 text-blue-600" />
                                        <Label :for="'include-email'" class="flex-1">
                                            Correo electrónico
                                            <span class="text-sm text-muted-foreground ml-1">
                                                ({{ estadisticasBorradores.con_email }} candidaturas)
                                            </span>
                                        </Label>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-3">
                                    <Checkbox 
                                        :id="'include-whatsapp'" 
                                        v-model="incluirWhatsApp"
                                        :disabled="estadisticasBorradores.con_telefono === 0"
                                    />
                                    <div class="flex items-center gap-2">
                                        <MessageSquare class="h-4 w-4 text-green-600" />
                                        <Label :for="'include-whatsapp'" class="flex-1">
                                            WhatsApp
                                            <span class="text-sm text-muted-foreground ml-1">
                                                ({{ estadisticasBorradores.con_telefono }} candidaturas)
                                            </span>
                                        </Label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información adicional -->
                        <div class="text-xs text-muted-foreground bg-gray-50 border border-gray-200 rounded p-3">
                            <p class="mb-1">• Los mensajes se envían respetando los límites de velocidad configurados</p>
                            <p class="mb-1">• Emails: 2 por segundo | WhatsApp: 5 por segundo</p>
                            <p>• El proceso se ejecuta en segundo plano</p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button 
                            variant="outline" 
                            @click="modalAbierto = false"
                            :disabled="enviandoRecordatorios"
                        >
                            Cancelar
                        </Button>
                        <Button 
                            @click="enviarRecordatorios"
                            :disabled="enviandoRecordatorios || estadisticasBorradores.total_borradores === 0 || (!incluirEmail && !incluirWhatsApp)"
                            class="bg-orange-600 hover:bg-orange-700"
                        >
                            <Send class="mr-2 h-4 w-4" />
                            {{ enviandoRecordatorios ? 'Enviando...' : `Enviar Recordatorios (${estadisticasBorradores.total_borradores})` }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>