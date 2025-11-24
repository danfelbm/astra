<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Card, CardContent } from "@modules/Core/Resources/js/components/ui/card";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@modules/Core/Resources/js/components/ui/table";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@modules/Core/Resources/js/components/ui/accordion";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@modules/Core/Resources/js/components/ui/dialog";
import { Textarea } from "@modules/Core/Resources/js/components/ui/textarea";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import EvidenciaFilters from "./EvidenciaFilters.vue";
import { FileText, ExternalLink, Download, Image, CheckCircle, XCircle, Eye } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

// Interfaces
interface Evidencia {
    id: number;
    tipo_evidencia: string;
    descripcion?: string;
    archivo_url?: string;
    estado: string;
    created_at: string;
    usuario?: {
        id: number;
        name: string;
    };
    obligacion_titulo?: string;
    contrato_id: number;
    contrato_numero?: string;
    contrato_nombre?: string;
    _contrato?: any;
}

interface Contrato {
    id: number;
    nombre: string;
    numero_contrato?: string;
    obligaciones?: any[];
}

interface Props {
    /** Contratos del proyecto */
    contratos?: Contrato[];
    /** ID del proyecto (requerido para rutas) */
    proyectoId: number;
    /** Modo de visualización: 'admin' o 'user' */
    modo: 'admin' | 'user';
    /** Si el usuario puede cambiar el estado de evidencias */
    puedeGestionarEstado?: boolean;
    /** Función para formatear fechas */
    formatDate: (date: string) => string;
}

const props = withDefaults(defineProps<Props>(), {
    puedeGestionarEstado: false,
    contratos: () => []
});

// Estado para filtros de evidencias
const filtrosEvidencias = ref({
    contrato_id: null,
    fecha_inicio: null,
    fecha_fin: null,
    tipo: null,
    estado: null,
    usuario_id: null
});

// Estado para modal de gestión de estado
const showEstadoModal = ref(false);
const evidenciaSeleccionada = ref<Evidencia | null>(null);
const accionSeleccionada = ref<'aprobar' | 'rechazar' | null>(null);
const observaciones = ref('');
const procesandoEstado = ref(false);

// Función para obtener todas las evidencias de los contratos
const todasLasEvidencias = computed(() => {
    const evidencias: Evidencia[] = [];
    if (props.contratos) {
        props.contratos.forEach(contrato => {
            if (contrato.obligaciones) {
                contrato.obligaciones.forEach((obligacion: any) => {
                    if (obligacion.evidencias) {
                        obligacion.evidencias.forEach((evidencia: any) => {
                            evidencias.push({
                                ...evidencia,
                                contrato_id: contrato.id,
                                contrato_numero: contrato.numero_contrato,
                                contrato_nombre: contrato.nombre,
                                obligacion_titulo: obligacion.titulo,
                                _contrato: contrato
                            });
                        });
                    }
                });
            }
        });
    }
    return evidencias;
});

// Evidencias filtradas según los filtros activos
const evidenciasFiltradas = computed(() => {
    let result = todasLasEvidencias.value;

    if (filtrosEvidencias.value.contrato_id) {
        result = result.filter(e => e.contrato_id === filtrosEvidencias.value.contrato_id);
    }

    if (filtrosEvidencias.value.tipo) {
        result = result.filter(e => e.tipo_evidencia === filtrosEvidencias.value.tipo);
    }

    if (filtrosEvidencias.value.estado) {
        result = result.filter(e => e.estado === filtrosEvidencias.value.estado);
    }

    if (filtrosEvidencias.value.usuario_id) {
        result = result.filter(e => e.usuario?.id === filtrosEvidencias.value.usuario_id);
    }

    if (filtrosEvidencias.value.fecha_inicio || filtrosEvidencias.value.fecha_fin) {
        result = result.filter(e => {
            const fecha = new Date(e.created_at);
            if (filtrosEvidencias.value.fecha_inicio) {
                const fechaInicio = new Date(filtrosEvidencias.value.fecha_inicio);
                if (fecha < fechaInicio) return false;
            }
            if (filtrosEvidencias.value.fecha_fin) {
                const fechaFin = new Date(filtrosEvidencias.value.fecha_fin);
                fechaFin.setHours(23, 59, 59, 999);
                if (fecha > fechaFin) return false;
            }
            return true;
        });
    }

    return result;
});

// Evidencias agrupadas por contrato
const evidenciasAgrupadasPorContrato = computed(() => {
    const grupos: Record<number, any> = {};

    evidenciasFiltradas.value.forEach(evidencia => {
        if (!grupos[evidencia.contrato_id]) {
            grupos[evidencia.contrato_id] = {
                contrato: evidencia._contrato,
                evidencias: []
            };
        }
        grupos[evidencia.contrato_id].evidencias.push(evidencia);
    });

    return Object.values(grupos);
});

// Función para obtener la ruta del contrato según el modo
const getContratoRoute = (contratoId: number) => {
    return props.modo === 'admin'
        ? `/admin/contratos/${contratoId}`
        : `/miembro/mis-contratos/${contratoId}`;
};

// Función para obtener la ruta de la evidencia según el modo
const getEvidenciaRoute = (contratoId: number, evidenciaId: number) => {
    return props.modo === 'admin'
        ? `/admin/contratos/${contratoId}/evidencias/${evidenciaId}`
        : `/miembro/mis-contratos/${contratoId}/evidencias/${evidenciaId}`;
};

// Función para abrir modal de cambio de estado
const abrirModalEstado = (evidencia: Evidencia, accion: 'aprobar' | 'rechazar') => {
    evidenciaSeleccionada.value = evidencia;
    accionSeleccionada.value = accion;
    observaciones.value = '';
    showEstadoModal.value = true;
};

// Función para cambiar el estado de la evidencia
const cambiarEstado = async () => {
    if (!evidenciaSeleccionada.value || !accionSeleccionada.value) return;

    procesandoEstado.value = true;

    const endpoint = `/api/proyectos/${props.proyectoId}/evidencias/${evidenciaSeleccionada.value.id}/${accionSeleccionada.value}`;

    router.post(endpoint, {
        observaciones: observaciones.value || null
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(
                accionSeleccionada.value === 'aprobar'
                    ? 'Evidencia aprobada exitosamente'
                    : 'Evidencia rechazada'
            );
            showEstadoModal.value = false;
            evidenciaSeleccionada.value = null;
            accionSeleccionada.value = null;
            observaciones.value = '';

            // Recargar la página para actualizar los datos
            router.reload({ only: ['proyecto'] });
        },
        onError: (errors) => {
            toast.error('Error al cambiar el estado de la evidencia');
            console.error(errors);
        },
        onFinish: () => {
            procesandoEstado.value = false;
        }
    });
};
</script>

<template>
    <!-- Filtros de evidencias -->
    <EvidenciaFilters
        v-if="todasLasEvidencias.length > 0"
        v-model="filtrosEvidencias"
        :contratos="contratos || []"
        :evidencias="todasLasEvidencias"
    />

    <!-- Evidencias agrupadas por contrato -->
    <div v-if="evidenciasAgrupadasPorContrato.length > 0" class="mt-4">
        <Accordion type="multiple" class="space-y-4" collapsible>
            <AccordionItem
                v-for="grupo in evidenciasAgrupadasPorContrato"
                :key="grupo.contrato.id"
                :value="`contrato-${grupo.contrato.id}`"
                class="border rounded-lg bg-card"
            >
                <AccordionTrigger class="px-4 py-3 hover:no-underline hover:bg-gray-50 dark:hover:bg-gray-800 rounded-t-lg">
                    <div class="flex items-center justify-between w-full pr-4">
                        <div class="flex items-center gap-3">
                            <FileText class="h-5 w-5 text-gray-500" />
                            <div class="text-left">
                                <div class="font-semibold">
                                    {{ grupo.contrato.nombre }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ grupo.contrato.numero_contrato || 'Sin número' }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <Badge variant="secondary">
                                {{ grupo.evidencias.length }} evidencia(s)
                            </Badge>
                            <Link
                                :href="getContratoRoute(grupo.contrato.id)"
                                @click.stop
                                class="text-blue-600 hover:text-blue-800 flex items-center gap-1"
                            >
                                Ver contrato
                                <ExternalLink class="h-3 w-3" />
                            </Link>
                        </div>
                    </div>
                </AccordionTrigger>
                <AccordionContent class="px-4 pb-4">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Obligación</TableHead>
                                <TableHead>Descripción</TableHead>
                                <TableHead v-if="modo === 'admin'">Usuario</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Fecha</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="evidencia in grupo.evidencias"
                                :key="evidencia.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800"
                            >
                                <TableCell>
                                    <Badge variant="outline">{{ evidencia.tipo_evidencia }}</Badge>
                                </TableCell>
                                <TableCell>{{ evidencia.obligacion_titulo }}</TableCell>
                                <TableCell>
                                    <span class="text-sm text-gray-600">{{ evidencia.descripcion || '-' }}</span>
                                </TableCell>
                                <TableCell v-if="modo === 'admin'">
                                    <span class="text-sm">{{ evidencia.usuario?.name || '-' }}</span>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        :class="{
                                            'bg-yellow-100 text-yellow-800': evidencia.estado === 'pendiente',
                                            'bg-green-100 text-green-800': evidencia.estado === 'aprobada',
                                            'bg-red-100 text-red-800': evidencia.estado === 'rechazada'
                                        }"
                                    >
                                        {{ evidencia.estado }}
                                    </Badge>
                                </TableCell>
                                <TableCell>{{ formatDate(evidencia.created_at) }}</TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Botón de ver detalles -->
                                        <Link
                                            :href="getEvidenciaRoute(evidencia.contrato_id, evidencia.id)"
                                            class="inline-flex items-center text-blue-600 hover:text-blue-800"
                                        >
                                            <Eye class="h-4 w-4" />
                                        </Link>

                                        <!-- Botón de descargar archivo -->
                                        <a
                                            v-if="evidencia.archivo_url"
                                            :href="evidencia.archivo_url"
                                            target="_blank"
                                            class="inline-flex items-center text-blue-600 hover:text-blue-800"
                                        >
                                            <Download class="h-4 w-4" />
                                        </a>

                                        <!-- Botones de aprobar/rechazar (solo si puede gestionar) -->
                                        <template v-if="puedeGestionarEstado && evidencia.estado === 'pendiente'">
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                @click="abrirModalEstado(evidencia, 'aprobar')"
                                                class="h-8 w-8 p-0 text-green-600 hover:text-green-800 hover:bg-green-50"
                                            >
                                                <CheckCircle class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                @click="abrirModalEstado(evidencia, 'rechazar')"
                                                class="h-8 w-8 p-0 text-red-600 hover:text-red-800 hover:bg-red-50"
                                            >
                                                <XCircle class="h-4 w-4" />
                                            </Button>
                                        </template>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </AccordionContent>
            </AccordionItem>
        </Accordion>
    </div>

    <!-- Estado vacío -->
    <Card v-else-if="todasLasEvidencias.length === 0" class="mt-4">
        <CardContent class="py-8">
            <div class="text-center">
                <Image class="mx-auto h-12 w-12 text-gray-400" />
                <p class="mt-2 text-sm text-gray-600">No hay evidencias cargadas</p>
                <p class="text-xs text-gray-500 mt-1">Las evidencias se cargan desde los contratos</p>
            </div>
        </CardContent>
    </Card>

    <!-- Sin resultados después de filtrar -->
    <Card v-else class="mt-4">
        <CardContent class="py-8">
            <div class="text-center">
                <Image class="mx-auto h-12 w-12 text-gray-400" />
                <p class="mt-2 text-sm text-gray-600">No hay evidencias que coincidan con los filtros</p>
                <p class="text-xs text-gray-500 mt-1">Intenta ajustar los criterios de búsqueda</p>
            </div>
        </CardContent>
    </Card>

    <!-- Modal para cambiar estado de evidencia -->
    <Dialog v-model:open="showEstadoModal">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {{ accionSeleccionada === 'aprobar' ? 'Aprobar Evidencia' : 'Rechazar Evidencia' }}
                </DialogTitle>
                <DialogDescription>
                    {{ accionSeleccionada === 'aprobar'
                        ? 'Estás a punto de aprobar esta evidencia. Puedes agregar observaciones opcionales.'
                        : 'Estás a punto de rechazar esta evidencia. Es recomendable agregar el motivo del rechazo.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <div>
                    <Label for="observaciones">
                        Observaciones {{ accionSeleccionada === 'rechazar' ? '(recomendado)' : '(opcional)' }}
                    </Label>
                    <Textarea
                        id="observaciones"
                        v-model="observaciones"
                        :placeholder="accionSeleccionada === 'rechazar'
                            ? 'Explica el motivo del rechazo...'
                            : 'Agrega comentarios adicionales...'"
                        rows="4"
                        class="mt-2"
                    />
                </div>
            </div>

            <DialogFooter>
                <Button
                    variant="outline"
                    @click="showEstadoModal = false"
                    :disabled="procesandoEstado"
                >
                    Cancelar
                </Button>
                <Button
                    :variant="accionSeleccionada === 'aprobar' ? 'default' : 'destructive'"
                    @click="cambiarEstado"
                    :disabled="procesandoEstado"
                >
                    {{ procesandoEstado ? 'Procesando...' : (accionSeleccionada === 'aprobar' ? 'Aprobar' : 'Rechazar') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
