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
import { Vote, ArrowLeft, Clock, AlertTriangle, CheckCircle, Timer, AlertCircle } from 'lucide-vue-next';
import { ref, computed, Teleport, Transition, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { toast } from 'sonner';

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

interface UrnaSession {
    opened_at: string;
    expires_at: string;
    remaining_seconds: number;
    remaining_formatted: string;
    warning_time: number;
    critical_time: number;
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
    urna_session?: UrnaSession;
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

// Variables para el cronómetro de sesión de urna
const remainingSeconds = ref(props.votacion.urna_session?.remaining_seconds || 0);
const sessionActive = ref(!!props.votacion.urna_session);
const sessionStatus = ref<'normal' | 'warning' | 'critical'>('normal');
const countdownInterval = ref<number | null>(null);
const checkSessionInterval = ref<number | null>(null);

// Función para enviar el voto
const submitVote = () => {
    form.post(route('user.votaciones.store', { votacion: props.votacion.id }), {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            // La redirección se maneja en el backend
        },
        onError: (errors) => {
            console.error('Errores de validación:', errors);
        }
    });
};

// Función para volver
const goBack = () => {
    router.get(route('user.votaciones.index'));
};

// Funciones para el cronómetro de sesión de urna
const formatTime = (seconds: number): string => {
    if (seconds <= 0) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const updateSessionStatus = () => {
    if (!props.votacion.urna_session) return;
    
    const warningTime = props.votacion.urna_session.warning_time;
    const criticalTime = props.votacion.urna_session.critical_time;
    
    if (remainingSeconds.value <= criticalTime) {
        sessionStatus.value = 'critical';
    } else if (remainingSeconds.value <= warningTime) {
        sessionStatus.value = 'warning';
    } else {
        sessionStatus.value = 'normal';
    }
};

const startCountdown = () => {
    if (countdownInterval.value) return;
    
    countdownInterval.value = window.setInterval(() => {
        if (remainingSeconds.value > 0) {
            remainingSeconds.value--;
            updateSessionStatus();
            
            // Mostrar alertas en momentos clave
            if (remainingSeconds.value === 120) {
                toast.warning('⏱️ Quedan 2 minutos para completar tu voto');
            } else if (remainingSeconds.value === 60) {
                toast.error('⚠️ Queda 1 minuto para completar tu voto');
            } else if (remainingSeconds.value === 0) {
                toast.error('❌ Tu sesión de votación ha expirado');
                setTimeout(() => {
                    router.get(route('user.votaciones.index'));
                }, 2000);
            }
        }
    }, 1000);
};

const checkUrnaSession = async () => {
    try {
        const response = await axios.get(route('user.votaciones.check-urna-session', { 
            votacion: props.votacion.id 
        }));
        
        if (response.data.active) {
            remainingSeconds.value = response.data.remaining_seconds;
            sessionActive.value = true;
            updateSessionStatus();
        } else {
            sessionActive.value = false;
            if (response.data.expired) {
                toast.error('Tu sesión de votación ha expirado');
                setTimeout(() => {
                    router.get(route('user.votaciones.index'));
                }, 2000);
            }
        }
    } catch (error) {
        console.error('Error verificando sesión de urna:', error);
    }
};

const startSessionCheck = () => {
    if (checkSessionInterval.value) return;
    
    // Verificar cada 30 segundos
    checkSessionInterval.value = window.setInterval(() => {
        checkUrnaSession();
    }, 30000);
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

// Hooks de ciclo de vida
onMounted(() => {
    if (props.votacion.urna_session) {
        updateSessionStatus();
        startCountdown();
        startSessionCheck();
    }
});

onUnmounted(() => {
    if (countdownInterval.value) {
        clearInterval(countdownInterval.value);
    }
    if (checkSessionInterval.value) {
        clearInterval(checkSessionInterval.value);
    }
});
</script>

<template>
    <Head :title="`Votar: ${votacion.titulo}`" />

    <UserLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-3 sm:gap-6 rounded-xl p-2 sm:p-4">
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

            <!-- Cronómetro de sesión de urna -->
            <Card v-if="votacion.urna_session && sessionActive" 
                  :class="{
                      'border-orange-500': sessionStatus === 'warning',
                      'border-red-500 animate-pulse': sessionStatus === 'critical'
                  }">
                <CardContent class="pt-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <Timer :class="{
                                'h-5 w-5': true,
                                'text-green-600': sessionStatus === 'normal',
                                'text-orange-600': sessionStatus === 'warning',
                                'text-red-600': sessionStatus === 'critical'
                            }" />
                            <div>
                                <p class="font-semibold">Tiempo de sesión de urna</p>
                                <p class="text-sm text-muted-foreground">
                                    Tu sesión para votar está activa
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p :class="{
                                'text-3xl font-bold': true,
                                'text-green-600': sessionStatus === 'normal',
                                'text-orange-600': sessionStatus === 'warning',
                                'text-red-600': sessionStatus === 'critical'
                            }">
                                {{ formatTime(remainingSeconds) }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                minutos restantes
                            </p>
                        </div>
                    </div>
                    
                    <!-- Barra de progreso -->
                    <div class="mt-4">
                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                            <div :class="{
                                'h-full transition-all duration-1000': true,
                                'bg-green-600': sessionStatus === 'normal',
                                'bg-orange-600': sessionStatus === 'warning',
                                'bg-red-600': sessionStatus === 'critical'
                            }"
                                 :style="`width: ${(remainingSeconds / (votacion.urna_session.remaining_seconds || 300)) * 100}%`">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Alertas según el estado -->
                    <div v-if="sessionStatus === 'warning'" class="mt-4 p-3 bg-orange-50 border border-orange-200 rounded-lg">
                        <div class="flex items-center gap-2">
                            <AlertCircle class="h-4 w-4 text-orange-600" />
                            <p class="text-sm text-orange-800">
                                Tu tiempo se está agotando. Por favor, completa tu voto pronto.
                            </p>
                        </div>
                    </div>
                    
                    <div v-if="sessionStatus === 'critical'" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center gap-2">
                            <AlertTriangle class="h-4 w-4 text-red-600" />
                            <p class="text-sm text-red-800 font-semibold">
                                ¡ATENCIÓN! Tu sesión está a punto de expirar. Envía tu voto ahora.
                            </p>
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
                    <form @submit.prevent="showConfirmDialog = true" class="space-y-6">
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

                        <!-- Espacio para la barra flotante -->
                        <div class="h-20"></div>
                    </form>
                </CardContent>
            </Card>

            <!-- Dialog de confirmación -->
            <AlertDialog v-model:open="showConfirmDialog">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Confirmar voto?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Una vez enviado tu voto, no podrás modificarlo. ¿Estás seguro de que quieres proceder?
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction @click="submitVote" class="bg-primary">
                            Confirmar Voto
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>

        <!-- Barra flotante con botón de voto y efecto glassmorphing -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="translate-y-4 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-4 opacity-0"
            >
                <div v-if="true" class="fixed bottom-0 left-0 right-0 z-50 px-2 sm:px-4 pb-2 sm:pb-4">
                    <div class="mx-auto max-w-7xl">
                        <div class="backdrop-blur-lg bg-white/80 dark:bg-gray-900/80 border border-gray-200/50 dark:border-gray-700/50 rounded-xl sm:rounded-2xl shadow-2xl p-3 sm:p-4">
                            <!-- Diseño responsive: centrado en móvil, con info en desktop -->
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                                <!-- Información de la votación - oculta en móvil, visible en desktop -->
                                <div class="hidden sm:flex items-center gap-4 text-sm text-muted-foreground">
                                    <div class="flex items-center gap-2">
                                        <Clock class="h-4 w-4" />
                                        <span>{{ timeRemaining }}</span>
                                    </div>
                                    <div class="h-4 w-px bg-gray-300 dark:bg-gray-600"></div>
                                    <span class="font-medium">{{ votacion.titulo }}</span>
                                </div>
                                
                                <!-- Botón de envío centrado -->
                                <div class="flex justify-center sm:justify-end">
                                    <Button 
                                        @click="showConfirmDialog = true"
                                        :disabled="!isFormValid || form.processing"
                                        class="bg-green-600 hover:bg-green-700 text-white border-green-600 hover:border-green-700 disabled:bg-gray-400 disabled:border-gray-400 min-w-[150px] sm:min-w-[180px]"
                                    >
                                        <template v-if="form.processing">
                                            <Clock class="mr-2 h-4 w-4 animate-spin" />
                                            Procesando tu voto de forma segura... No cierres esta ventana
                                        </template>
                                        <template v-else>
                                            <CheckCircle class="mr-2 h-4 w-4" />
                                            Enviar Voto
                                        </template>
                                    </Button>
                                </div>
                            </div>
                            
                            <!-- Barra de progreso compacta para móvil -->
                            <div class="sm:hidden mt-3 text-center">
                                <span class="text-xs text-muted-foreground">
                                    Tiempo restante: {{ timeRemaining }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </UserLayout>
</template>