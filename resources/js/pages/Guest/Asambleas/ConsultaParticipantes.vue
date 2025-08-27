<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Head, Link } from '@inertiajs/vue3';
import { Calendar, MapPin, Users, Info, ArrowLeft, ExternalLink, Search, Eye } from 'lucide-vue-next';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';

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
    lugar?: string;
    ubicacion_completa: string;
    participantes_count: number;
    public_participants_mode: 'list' | 'search';
    public_participants_mode_label: string;
}

interface PaginatedData {
    data: Asamblea[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number;
    to?: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Props {
    asambleas: PaginatedData;
}

const props = defineProps<Props>();

// Formatear fecha
const formatearFecha = (fecha: string) => {
    if (!fecha) return '';
    return format(new Date(fecha), 'dd/MM/yyyy', { locale: es });
};

// Formatear hora
const formatearHora = (fecha: string) => {
    if (!fecha) return '';
    return format(new Date(fecha), 'HH:mm', { locale: es });
};

// Obtener icono según el modo
const getModeIcon = (mode: string) => {
    return mode === 'list' ? Eye : Search;
};

// Obtener color del badge según el modo
const getModeColor = (mode: string) => {
    return mode === 'list' 
        ? 'bg-blue-100 text-blue-800' 
        : 'bg-purple-100 text-purple-800';
};
</script>

<template>
    <Head title="Consulta de Participantes" />
    
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Consulta de Participantes</h1>
                        <p class="text-muted-foreground mt-1">
                            Asambleas con consulta pública de participantes habilitada
                        </p>
                    </div>
                    <a href="/">
                        <Button variant="outline">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Volver al inicio
                        </Button>
                    </a>
                </div>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Información -->
            <Alert class="mb-6">
                <Info class="h-4 w-4" />
                <AlertTitle>Consulta Pública de Participantes</AlertTitle>
                <AlertDescription>
                    Las siguientes asambleas permiten la consulta pública de sus participantes. 
                    La información mostrada respeta las políticas de privacidad establecidas.
                </AlertDescription>
            </Alert>

            <!-- Tabla de asambleas -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>
                                <Users class="inline-block mr-2 h-5 w-5" />
                                Asambleas con Consulta Pública
                            </CardTitle>
                            <CardDescription>
                                {{ asambleas.total }} {{ asambleas.total === 1 ? 'asamblea disponible' : 'asambleas disponibles' }}
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Asamblea</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead>Ubicación</TableHead>
                                    <TableHead class="text-center">Participantes</TableHead>
                                    <TableHead class="text-center">Modo</TableHead>
                                    <TableHead class="text-center">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="asamblea in asambleas.data" :key="asamblea.id">
                                    <TableCell class="font-medium">
                                        <div>
                                            <p class="font-semibold">{{ asamblea.nombre }}</p>
                                            <p class="text-sm text-muted-foreground">{{ asamblea.tipo_label }}</p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="asamblea.estado_color">
                                            {{ asamblea.estado_label }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm">
                                            <p>{{ formatearFecha(asamblea.fecha_inicio) }}</p>
                                            <p class="text-muted-foreground">
                                                {{ formatearHora(asamblea.fecha_inicio) }} - {{ formatearHora(asamblea.fecha_fin) }}
                                            </p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-1">
                                            <MapPin class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                                            <span class="text-sm truncate max-w-[200px]">
                                                {{ asamblea.ubicacion_completa || 'No especificada' }}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <Badge variant="secondary">
                                            {{ asamblea.participantes_count }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <Badge :class="getModeColor(asamblea.public_participants_mode)">
                                            <component 
                                                :is="getModeIcon(asamblea.public_participants_mode)" 
                                                class="h-3 w-3 mr-1" 
                                            />
                                            {{ asamblea.public_participants_mode_label }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <Link :href="`/asambleas/${asamblea.id}/participantes-publico`">
                                            <Button size="sm" variant="outline">
                                                <ExternalLink class="h-4 w-4 mr-1" />
                                                Ver
                                            </Button>
                                        </Link>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="asambleas.data.length === 0">
                                    <TableCell :colspan="7" class="text-center py-8">
                                        <div class="flex flex-col items-center gap-2 text-muted-foreground">
                                            <Users class="h-8 w-8" />
                                            <p>No hay asambleas con consulta pública disponible</p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="asambleas.last_page > 1" class="mt-6 flex items-center justify-between">
                        <p class="text-sm text-muted-foreground">
                            Mostrando {{ (asambleas.current_page - 1) * asambleas.per_page + 1 }} a 
                            {{ Math.min(asambleas.current_page * asambleas.per_page, asambleas.total) }} de 
                            {{ asambleas.total }} asambleas
                        </p>
                        <div class="flex gap-2">
                            <template v-for="link in asambleas.links" :key="link.label">
                                <Link 
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'px-3 py-1 text-sm border rounded',
                                        link.active 
                                            ? 'bg-primary text-primary-foreground' 
                                            : 'bg-background hover:bg-accent'
                                    ]"
                                    v-html="link.label"
                                />
                                <span 
                                    v-else
                                    :class="[
                                        'px-3 py-1 text-sm border rounded',
                                        'bg-muted text-muted-foreground cursor-not-allowed'
                                    ]"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Footer informativo -->
            <div class="mt-6 text-center text-sm text-gray-500">
                <p>La información mostrada es de carácter público y cumple con las políticas de privacidad.</p>
                <p class="mt-1">
                    Para acceder a información completa de las asambleas, 
                    <Link href="/login" class="text-blue-600 hover:underline">inicie sesión</Link>.
                </p>
            </div>
        </div>
    </div>
</template>