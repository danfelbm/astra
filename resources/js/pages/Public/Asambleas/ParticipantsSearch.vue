<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { 
    Calendar,
    MapPin, 
    Search,
    Info,
    CheckCircle,
    XCircle,
    Loader2,
    ArrowLeft
} from 'lucide-vue-next';
import { ref } from 'vue';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';

interface Asamblea {
    id: number;
    nombre: string;
    descripcion?: string;
    fecha_inicio: string;
    fecha_fin: string;
    lugar?: string;
    ubicacion_completa: string;
    public_participants_mode: 'list' | 'search';
}

interface Props {
    asamblea: Asamblea;
}

const props = defineProps<Props>();

// Estado del formulario de búsqueda
const searchTerm = ref('');
const searching = ref(false);
const searchResult = ref<{
    found: boolean;
    message: string;
} | null>(null);
const searchError = ref('');

// Formatear fecha
const formatearFecha = (fecha: string) => {
    if (!fecha) return '';
    return format(new Date(fecha), 'PPP', { locale: es });
};

// Realizar búsqueda
const performSearch = async () => {
    // Validar entrada
    if (!searchTerm.value || searchTerm.value.length < 3) {
        searchError.value = 'Por favor ingrese al menos 3 caracteres para buscar.';
        searchResult.value = null;
        return;
    }

    // Limpiar errores y resultados previos
    searchError.value = '';
    searchResult.value = null;
    searching.value = true;

    try {
        const response = await axios.post(`/public-api/asambleas/${props.asamblea.id}/buscar-participante`, {
            search: searchTerm.value,
        });

        searchResult.value = response.data;
    } catch (error: any) {
        console.error('Error buscando participante:', error);
        
        if (error.response?.status === 422) {
            // Error de validación
            const errors = error.response.data.errors;
            searchError.value = errors.search ? errors.search[0] : 'Error en la búsqueda.';
        } else if (error.response?.status === 404) {
            // Asamblea no disponible
            searchError.value = 'La búsqueda no está disponible en este momento.';
        } else {
            searchError.value = 'Ocurrió un error al realizar la búsqueda. Por favor intente nuevamente.';
        }
    } finally {
        searching.value = false;
    }
};

// Limpiar búsqueda
const clearSearch = () => {
    searchTerm.value = '';
    searchResult.value = null;
    searchError.value = '';
};

// Manejar Enter en el input
const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Enter') {
        performSearch();
    }
};
</script>

<template>
    <div>
        <Head :title="`Buscar Participantes - ${asamblea.nombre}`" />
        
        <div class="min-h-screen bg-gray-50">
            <!-- Header público -->
            <div class="bg-white shadow-sm border-b">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div class="flex items-start justify-between">
                        <div class="flex flex-col gap-4">
                            <h1 class="text-2xl font-bold text-gray-900">
                                {{ asamblea.nombre }}
                            </h1>
                            <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                                <div class="flex items-center gap-1">
                                    <Calendar class="h-4 w-4" />
                                    <span>{{ formatearFecha(asamblea.fecha_inicio) }} - {{ formatearFecha(asamblea.fecha_fin) }}</span>
                                </div>
                                <div v-if="asamblea.lugar" class="flex items-center gap-1">
                                    <MapPin class="h-4 w-4" />
                                    <span>{{ asamblea.ubicacion_completa }}</span>
                                </div>
                            </div>
                        </div>
                        <Button 
                            variant="outline" 
                            size="sm"
                            @click="() => window.history.back()"
                        >
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Volver
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Información -->
                <Alert class="mb-6">
                    <Info class="h-4 w-4" />
                    <AlertTitle>Verificación de Participantes</AlertTitle>
                    <AlertDescription>
                        Utilice este buscador para verificar si una persona está registrada como participante de la asamblea.
                        Puede buscar por nombre completo o número de documento de identidad (cédula).
                    </AlertDescription>
                </Alert>

                <!-- Card de búsqueda -->
                <Card>
                    <CardHeader>
                        <CardTitle>
                            <Search class="inline-block mr-2 h-5 w-5" />
                            Buscar Participante
                        </CardTitle>
                        <CardDescription>
                            Ingrese los datos de la persona que desea verificar
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <!-- Campo de búsqueda -->
                            <div class="space-y-2">
                                <Label for="search">Búsqueda</Label>
                                <div class="flex gap-2">
                                    <Input
                                        id="search"
                                        v-model="searchTerm"
                                        type="text"
                                        placeholder="Ingrese nombre completo o número de cédula..."
                                        class="flex-1"
                                        @keydown="handleKeydown"
                                        :disabled="searching"
                                    />
                                    <Button 
                                        @click="performSearch"
                                        :disabled="searching || !searchTerm"
                                    >
                                        <Loader2 v-if="searching" class="mr-2 h-4 w-4 animate-spin" />
                                        <Search v-else class="mr-2 h-4 w-4" />
                                        Buscar
                                    </Button>
                                    <Button 
                                        v-if="searchTerm || searchResult"
                                        variant="outline"
                                        @click="clearSearch"
                                    >
                                        Limpiar
                                    </Button>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Mínimo 3 caracteres. La búsqueda es sensible a mayúsculas y minúsculas.
                                </p>
                            </div>

                            <!-- Error de búsqueda -->
                            <Alert v-if="searchError" variant="destructive">
                                <XCircle class="h-4 w-4" />
                                <AlertTitle>Error</AlertTitle>
                                <AlertDescription>{{ searchError }}</AlertDescription>
                            </Alert>

                            <!-- Resultado de búsqueda -->
                            <div v-if="searchResult" class="mt-6">
                                <!-- Participante encontrado -->
                                <Alert v-if="searchResult.found" class="border-green-200 bg-green-50">
                                    <CheckCircle class="h-4 w-4 text-green-600" />
                                    <AlertTitle class="text-green-800">Participante Confirmado</AlertTitle>
                                    <AlertDescription class="text-green-700">
                                        {{ searchResult.message }}
                                    </AlertDescription>
                                </Alert>

                                <!-- Participante no encontrado -->
                                <Alert v-else class="border-orange-200 bg-orange-50">
                                    <Info class="h-4 w-4 text-orange-600" />
                                    <AlertTitle class="text-orange-800">No Encontrado</AlertTitle>
                                    <AlertDescription class="text-orange-700">
                                        {{ searchResult.message }}
                                    </AlertDescription>
                                </Alert>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Información adicional -->
                <Card class="mt-6">
                    <CardHeader>
                        <CardTitle class="text-base">Información Importante</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul class="list-disc list-inside space-y-2 text-sm text-gray-600">
                            <li>La búsqueda solo confirmará si una persona es participante o no.</li>
                            <li>No se mostrará información personal adicional de los participantes.</li>
                            <li>Para búsquedas exactas, use el documento de identidad completo.</li>
                            <li>Si tiene dudas sobre su participación, contacte a la organización.</li>
                        </ul>
                    </CardContent>
                </Card>

                <!-- Footer informativo -->
                <div class="mt-6 text-center text-sm text-gray-500">
                    <p>Esta herramienta de verificación respeta la privacidad de los participantes.</p>
                    <p class="mt-1">
                        Para más información sobre la asamblea, contacte a la organización.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>