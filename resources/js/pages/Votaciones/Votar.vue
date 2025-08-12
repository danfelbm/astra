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
import AppLayout from '@/layouts/AppLayout.vue';
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
    type: 'text' | 'textarea' | 'select' | 'radio' | 'checkbox';
    title: string;
    description?: string;
    required: boolean;
    options?: string[];
}

interface Votacion {
    id: number;
    titulo: string;
    descripcion?: string;
    categoria: Categoria;
    formulario_config: FormField[];
    fecha_inicio: string;
    fecha_fin: string;
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
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Mis Votaciones', href: '/votaciones' },
    { title: 'Votar', href: '#' },
];

// Inicializar respuestas del formulario
const initializeFormData = () => {
    const data: Record<string, any> = {};
    
    props.votacion.formulario_config.forEach(field => {
        if (field.type === 'checkbox') {
            data[field.id] = [];
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
    form.post(`/votaciones/${props.votacion.id}/votar`, {
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
    router.get('/votaciones');
};

// Formatear fechas
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Calcular tiempo restante
const timeRemaining = computed(() => {
    const now = new Date();
    const endDate = new Date(props.votacion.fecha_fin);
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
        if (!field.required) return true;
        
        const value = form.respuestas[field.id];
        
        if (field.type === 'checkbox') {
            return Array.isArray(value) && value.length > 0;
        }
        
        return value && value.toString().trim() !== '';
    });
});
</script>

<template>
    <Head :title="`Votar: ${votacion.titulo}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
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
    </AppLayout>
</template>