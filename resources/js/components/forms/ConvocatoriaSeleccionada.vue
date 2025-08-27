<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Calendar, MapPin, AlertCircle, ExternalLink, Briefcase } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

interface Convocatoria {
    id: number;
    nombre: string;
    cargo: string;
    periodo: string;
    fecha_cierre: string;
    estado_temporal: 'abierta' | 'futura' | 'cerrada' | 'borrador';
}

interface PostulacionExistente {
    id: number;
    convocatoria: Convocatoria;
    estado: string;
    fecha_postulacion: string | null;
}

interface Props {
    postulacion: PostulacionExistente;
}

const props = defineProps<Props>();

// Obtener color del estado de la convocatoria
const getEstadoColor = (estado: string) => {
    switch (estado) {
        case 'abierta':
            return 'bg-green-100 text-green-800';
        case 'futura':
            return 'bg-blue-100 text-blue-800';
        case 'cerrada':
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-yellow-100 text-yellow-800';
    }
};

// Obtener label del estado
const getEstadoLabel = (estado: string) => {
    switch (estado) {
        case 'abierta':
            return 'Abierta';
        case 'futura':
            return 'Próxima';
        case 'cerrada':
            return 'Cerrada';
        default:
            return 'Borrador';
    }
};

// Obtener color del estado de la postulación
const getEstadoPostulacionColor = (estado: string) => {
    switch (estado) {
        case 'enviada':
            return 'bg-yellow-100 text-yellow-800';
        case 'en_revision':
            return 'bg-blue-100 text-blue-800';
        case 'aceptada':
            return 'bg-green-100 text-green-800';
        case 'rechazada':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

// Obtener label del estado de la postulación
const getEstadoPostulacionLabel = (estado: string) => {
    switch (estado) {
        case 'enviada':
            return 'Enviada';
        case 'en_revision':
            return 'En revisión';
        case 'aceptada':
            return 'Aceptada';
        case 'rechazada':
            return 'Rechazada';
        default:
            return estado;
    }
};
</script>

<template>
    <div class="space-y-4">
        <!-- Mensaje informativo -->
        <Alert class="border-blue-200 bg-blue-50">
            <AlertCircle class="h-4 w-4 text-blue-600" />
            <AlertTitle class="text-blue-900">Convocatoria ya seleccionada</AlertTitle>
            <AlertDescription class="text-blue-800">
                <p>Ya has elegido una convocatoria para tu candidatura. La información de tu postulación se muestra a continuación.</p>
                <p class="mt-2">
                    Si deseas postularte a un cargo distinto, por favor ve al 
                    <Link 
                        :href="route('user.postulaciones.index')" 
                        class="font-semibold underline hover:text-blue-900 inline-flex items-center gap-1"
                    >
                        menú de Postulaciones
                        <ExternalLink class="h-3 w-3" />
                    </Link>. Si necesitas ayuda, escríbenos a: <b>soporte@colombiahumana.co</b>.
                </p>
            </AlertDescription>
        </Alert>

        <!-- Card con información de la convocatoria -->
        <Card class="border-2 bg-gray-50">
            <CardHeader>
                <div class="flex items-start justify-between">
                    <div class="space-y-1">
                        <CardTitle class="text-lg">
                            {{ props.postulacion.convocatoria.nombre }}
                        </CardTitle>
                        <CardDescription class="text-sm">
                            Convocatoria seleccionada en tu candidatura
                        </CardDescription>
                    </div>
                    <div class="flex gap-2">
                        <Badge 
                            :class="getEstadoColor(props.postulacion.convocatoria.estado_temporal)"
                            class="text-xs"
                        >
                            {{ getEstadoLabel(props.postulacion.convocatoria.estado_temporal) }}
                        </Badge>
                        <Badge 
                            :class="getEstadoPostulacionColor(props.postulacion.estado)"
                            class="text-xs"
                        >
                            Postulación {{ getEstadoPostulacionLabel(props.postulacion.estado) }}
                        </Badge>
                    </div>
                </div>
            </CardHeader>
            
            <CardContent class="space-y-3">
                <!-- Información del cargo -->
                <div class="flex items-start gap-2">
                    <Briefcase class="h-4 w-4 text-gray-500 mt-0.5" />
                    <div class="text-sm">
                        <span class="text-gray-600">Cargo:</span>
                        <span class="font-medium text-gray-900 ml-1">
                            {{ props.postulacion.convocatoria.cargo }}
                        </span>
                    </div>
                </div>

                <!-- Periodo electoral -->
                <div class="flex items-start gap-2">
                    <Calendar class="h-4 w-4 text-gray-500 mt-0.5" />
                    <div class="text-sm">
                        <span class="text-gray-600">Periodo:</span>
                        <span class="font-medium text-gray-900 ml-1">
                            {{ props.postulacion.convocatoria.periodo }}
                        </span>
                    </div>
                </div>

                <!-- Fecha de cierre -->
                <div class="flex items-start gap-2">
                    <Calendar class="h-4 w-4 text-gray-500 mt-0.5" />
                    <div class="text-sm">
                        <span class="text-gray-600">Fecha de cierre:</span>
                        <span class="font-medium text-gray-900 ml-1">
                            {{ props.postulacion.convocatoria.fecha_cierre }}
                        </span>
                    </div>
                </div>

                <!-- Fecha de postulación -->
                <div v-if="props.postulacion.fecha_postulacion" class="flex items-start gap-2">
                    <Calendar class="h-4 w-4 text-gray-500 mt-0.5" />
                    <div class="text-sm">
                        <span class="text-gray-600">Fecha de tu postulación:</span>
                        <span class="font-medium text-gray-900 ml-1">
                            {{ props.postulacion.fecha_postulacion }}
                        </span>
                    </div>
                </div>

                <!-- Separador visual -->
                <div class="border-t pt-3 mt-3">
                    <p class="text-xs text-gray-500">
                        Esta convocatoria fue seleccionada cuando creaste tu candidatura. 
                        No es posible cambiarla desde este formulario para mantener la integridad 
                        de tu postulación y su historial de seguimiento.
                    </p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>