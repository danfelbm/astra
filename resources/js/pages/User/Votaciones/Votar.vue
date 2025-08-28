<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { type BreadcrumbItemType } from '@/types';
import UserLayout from "@/layouts/UserLayout.vue";
import DynamicFormRenderer from '@/components/forms/DynamicFormRenderer.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Vote, ArrowLeft, Clock, AlertTriangle } from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface Categoria {
    id: number;
    nombre: string;
    descripcion?: string;
    activa: boolean;
}

interface FormField {
    id: string;
    type: 'text' | 'textarea' | 'select' | 'radio' | 'checkbox' | 'convocatoria' | 'perfil_candidatura' | 'file' | 'date' | 'disclaimer' | 'repeater';
    title: string;
    description?: string;
    required: boolean;
    options?: string[];
    [key: string]: any; // Para campos adicionales específicos del tipo
}

interface Votacion {
    id: number;
    titulo: string;
    descripcion?: string;
    categoria: Categoria;
    formulario_config: FormField[];
    fecha_inicio: string;
    fecha_fin: string;
    fecha_inicio_local?: string;
    fecha_fin_local?: string;
    timezone: string;
    estado: 'borrador' | 'activa' | 'finalizada';
    resultados_publicos: boolean;
}

interface CandidatoElegible {
    id: number;
    name: string;
    email?: string;
    cargo?: string;
    territorio?: string;
    departamento?: string;
    municipio?: string;
    localidad?: string;
}

interface Props {
    votacion: Votacion;
    candidatosElegibles?: Record<string, CandidatoElegible[]>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Dashboard', href: '/miembro/dashboard' },
    { title: 'Mis Votaciones', href: '/miembro/votaciones' },
    { title: 'Votar', href: '#' },
];

// Inicializar respuestas del formulario
const initializeFormData = () => {
    const data: Record<string, any> = {};
    
    props.votacion.formulario_config.forEach(field => {
        if (field.type === 'checkbox') {
            data[field.id] = [];
        } else if (field.type === 'convocatoria') {
            // Inicializar con undefined para distinguir "no seleccionado" de "voto en blanco"
            data[field.id] = undefined;
        } else {
            data[field.id] = '';
        }
    });
    
    return { respuestas: data };
};

const form = useForm(initializeFormData());

const showConfirmDialog = ref(false);

// Función para enviar el voto
const submitVote = () => {
    // DEBUG: Logs completos para troubleshooting
    console.log('=== INICIANDO ENVÍO DE VOTO ===');
    console.log('Timestamp:', new Date().toISOString());
    console.log('Votación ID:', props.votacion.id);
    console.log('Form data:', JSON.stringify(form.respuestas, null, 2));
    console.log('Form errors antes de envío:', form.errors);
    console.log('Form processing state:', form.processing);
    console.log('Route generada:', route('user.votaciones.store', { votacion: props.votacion.id }));
    
    // Verificar si hay preventDefault necesario
    console.log('Dialog open state:', showConfirmDialog.value);
    
    // Intentar capturar el evento real
    const event = window.event;
    if (event) {
        console.log('Event detected:', event.type);
        if (event.preventDefault) {
            event.preventDefault();
            console.log('preventDefault called on event');
        }
    }
    
    try {
        console.log('Enviando POST request...');
        
        form.post(route('user.votaciones.store', { votacion: props.votacion.id }), {
            preserveScroll: true, // Importante para producción
            preserveState: false,
            onStart: () => {
                console.log('Request started');
                console.log('Form processing:', form.processing);
            },
            onProgress: (progress) => {
                console.log('Upload progress:', progress);
            },
            onSuccess: (page) => {
                console.log('=== VOTO ENVIADO EXITOSAMENTE ===');
                console.log('Response page:', page);
                console.log('Props after success:', page.props);
                // La redirección se maneja en el backend
            },
            onError: (errors) => {
                console.error('=== ERROR AL ENVIAR VOTO ===');
                console.error('Errores de validación:', errors);
                console.error('Form errors después:', form.errors);
                console.error('Form data en error:', form.respuestas);
                
                // Mostrar alerta para debugging
                alert(`Error al enviar voto: ${JSON.stringify(errors)}`);
            },
            onFinish: () => {
                console.log('Request finished');
                console.log('Form processing final:', form.processing);
                console.log('Dialog state final:', showConfirmDialog.value);
            },
            onCancel: () => {
                console.log('Request was cancelled');
            }
        });
        
        console.log('POST request initiated');
    } catch (error: any) {
        console.error('=== EXCEPCIÓN CAPTURADA ===');
        console.error('Error type:', error?.constructor?.name);
        console.error('Error message:', error?.message);
        console.error('Error stack:', error?.stack);
        console.error('Full error:', error);
        
        // Alerta de emergencia
        alert(`Excepción al enviar voto: ${error?.message || 'Error desconocido'}`);
    }
    
    console.log('submitVote function completed');
};

// Función para volver
const goBack = () => {
    router.get(route('user.votaciones.index'));
};

// Obtener abreviación de zona horaria
const getTimezoneAbbreviation = (timezone: string): string => {
    const abbreviations: Record<string, string> = {
        'America/Bogota': 'GMT-5',
        'America/Mexico_City': 'GMT-6',
        'America/Lima': 'GMT-5',
        'America/Argentina/Buenos_Aires': 'GMT-3',
        'America/Santiago': 'GMT-3',
        'America/Caracas': 'GMT-4',
        'America/La_Paz': 'GMT-4',
        'America/Guayaquil': 'GMT-5',
        'America/Asuncion': 'GMT-3',
        'America/Montevideo': 'GMT-3',
        'America/Panama': 'GMT-5',
        'America/Costa_Rica': 'GMT-6',
        'America/Guatemala': 'GMT-6',
        'America/Havana': 'GMT-5',
        'America/Santo_Domingo': 'GMT-4',
        // Añadir más zonas horarias según sea necesario
    };
    return abbreviations[timezone] || timezone;
};

// Formatear fechas con zona horaria
const formatDate = (dateString: string, includeTimezone: boolean = true) => {
    // Usar fecha_local si está disponible, si no usar fecha original
    const dateToUse = dateString.includes('local') 
        ? dateString 
        : (dateString === props.votacion.fecha_inicio && props.votacion.fecha_inicio_local)
            ? props.votacion.fecha_inicio_local
            : (dateString === props.votacion.fecha_fin && props.votacion.fecha_fin_local)
                ? props.votacion.fecha_fin_local
                : dateString;
    
    const formatted = new Date(dateToUse).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
    
    if (includeTimezone && props.votacion.timezone) {
        const tz = getTimezoneAbbreviation(props.votacion.timezone);
        return `${formatted} (${tz})`;
    }
    return formatted;
};

// Calcular tiempo restante usando la fecha local con timezone
const timeRemaining = computed(() => {
    const now = new Date();
    // Usar fecha_fin_local si está disponible para cálculo más preciso
    const endDate = new Date(props.votacion.fecha_fin_local || props.votacion.fecha_fin);
    const diff = endDate.getTime() - now.getTime();
    
    if (diff <= 0) return 'Expirada';
    
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    
    if (hours > 24) {
        const days = Math.floor(hours / 24);
        return `${days} día${days > 1 ? 's' : ''}`;
    } else if (hours > 0) {
        return `${hours}h ${minutes}m`;
    } else {
        return `${minutes}m`;
    }
});

// Verificar si el formulario está completo
const isFormValid = computed(() => {
    return props.votacion.formulario_config.every(field => {
        const value = form.respuestas[field.id];
        
        // Para campos de tipo convocatoria, SIEMPRE requerir selección explícita
        // (independientemente del flag required)
        if (field.type === 'convocatoria') {
            // undefined = no seleccionado (inválido)
            // null = voto en blanco seleccionado (válido)
            // string = candidato seleccionado (válido)
            return value !== undefined;
        }
        
        // Para otros campos, respetar el flag required
        if (!field.required) return true;
        
        if (field.type === 'checkbox') {
            return Array.isArray(value) && value.length > 0;
        }
        
        return value && value.toString().trim() !== '';
    });
});
</script>

<template>
    <Head :title="`Votar: ${votacion.titulo}`" />

    <UserLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">{{ votacion.titulo }}</h1>
                    <p class="text-muted-foreground">
                        {{ votacion.descripcion }}
                    </p>
                </div>
                <Button variant="outline" @click="goBack">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver
                </Button>
            </div>

            <!-- Info de la votación -->
            <Card>
                <CardContent class="pt-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex items-center gap-2">
                            <Badge variant="outline">{{ votacion.categoria.nombre }}</Badge>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Clock class="h-4 w-4" />
                            Termina: {{ formatDate(votacion.fecha_fin) }}
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <AlertTriangle class="h-4 w-4 text-orange-600" />
                            <span :class="timeRemaining === 'Expirada' ? 'text-red-600' : 'text-orange-600'">
                                {{ timeRemaining === 'Expirada' ? 'Votación expirada' : `Tiempo restante: ${timeRemaining}` }}
                            </span>
                        </div>
                    </div>
                    <!-- Información adicional de horarios con zona horaria -->
                    <div class="mt-4 pt-4 border-t">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div class="text-muted-foreground">
                                <span class="font-medium">Periodo de votación:</span>
                                <div class="mt-1">
                                    Inicio: {{ formatDate(votacion.fecha_inicio) }}
                                </div>
                                <div>
                                    Fin: {{ formatDate(votacion.fecha_fin) }}
                                </div>
                            </div>
                            <div class="text-muted-foreground">
                                <span class="font-medium">Zona horaria:</span>
                                <div class="mt-1 font-semibold text-primary">
                                    {{ getTimezoneAbbreviation(votacion.timezone) }}
                                    <span class="text-xs text-muted-foreground ml-1">({{ votacion.timezone }})</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Formulario de votación -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Vote class="h-5 w-5" />
                        Formulario de Votación
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="(event) => { 
                        console.log('=== FORM SUBMIT EVENT ===');
                        console.log('Event type:', event.type);
                        console.log('Default prevented by Vue');
                        console.log('Opening dialog...');
                        showConfirmDialog = true;
                        console.log('Dialog opened:', showConfirmDialog);
                    }" class="space-y-6">
                        <!-- Usar DynamicFormRenderer para renderizar el formulario -->
                        <DynamicFormRenderer
                            :fields="votacion.formulario_config"
                            v-model="form.respuestas"
                            :candidatos-elegibles="candidatosElegibles || {}"
                            :errors="form.errors"
                            :disabled="form.processing"
                            title=""
                            description=""
                            context="votacion"
                        />

                        <!-- Botón de envío -->
                        <div class="flex justify-end pt-6 border-t">
                            <Button 
                                type="submit" 
                                :disabled="!isFormValid || form.processing"
                                class="min-w-32"
                            >
                                <Vote class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Enviando...' : 'Enviar Voto' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Dialog de confirmación -->
            <AlertDialog v-model:open="showConfirmDialog" @update:open="(value) => {
                console.log('Dialog state changed to:', value);
            }">
                <AlertDialogContent @escape-key-down="(event) => {
                    console.log('Escape key pressed in dialog');
                }" @pointer-down-outside="(event) => {
                    console.log('Clicked outside dialog');
                }">
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Confirmar voto?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Una vez enviado tu voto, no podrás modificarlo. ¿Estás seguro de que quieres proceder?
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel @click="() => {
                            console.log('Cancel button clicked');
                            showConfirmDialog = false;
                        }">Cancelar</AlertDialogCancel>
                        <AlertDialogAction @click.prevent="(event) => {
                            console.log('=== CONFIRM BUTTON CLICKED ===');
                            console.log('Event:', event);
                            console.log('Preventing default action');
                            event.preventDefault();
                            event.stopPropagation();
                            console.log('Calling submitVote...');
                            submitVote();
                            console.log('submitVote called, closing dialog');
                            showConfirmDialog = false;
                        }" class="bg-primary">
                            Confirmar Voto
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </UserLayout>
</template>