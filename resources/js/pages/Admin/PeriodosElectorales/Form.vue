<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { DateTimePicker } from '@/components/ui/datetime-picker';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Clock } from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface PeriodoElectoral {
    id: number;
    nombre: string;
    descripcion?: string;
    fecha_inicio: string;
    fecha_fin: string;
    activo: boolean;
}

interface Props {
    periodo?: PeriodoElectoral | null;
}

const props = defineProps<Props>();

const isEditing = computed(() => !!props.periodo);

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Periodos Electorales', href: '/admin/periodos-electorales' },
    { title: isEditing.value ? 'Editar' : 'Crear', href: '#' },
];

// Función para inicializar datos del formulario
const getInitialFormData = () => {
    if (!props.periodo) {
        return {
            nombre: '',
            descripcion: '',
            fecha_inicio: null,
            fecha_fin: null,
            activo: true,
        };
    }

    const periodo = props.periodo;
    
    return {
        nombre: periodo.nombre || '',
        descripcion: periodo.descripcion || '',
        fecha_inicio: periodo.fecha_inicio ? new Date(periodo.fecha_inicio) : null,
        fecha_fin: periodo.fecha_fin ? new Date(periodo.fecha_fin) : null,
        activo: periodo.activo,
    };
};

// Formulario principal
const form = useForm(getInitialFormData());

// Enviar formulario
const submit = () => {
    if (isEditing.value) {
        form.put(`/admin/periodos-electorales/${props.periodo!.id}`, {
            onError: (errors) => {
                console.error('Errores de validación:', errors);
            }
        });
    } else {
        form.post('/admin/periodos-electorales', {
            onError: (errors) => {
                console.error('Errores de validación:', errors);
            }
        });
    }
};

// Validaciones reactivas
const fechaInicioError = computed(() => {
    if (!form.fecha_inicio) return '';
    
    const fechaInicio = form.fecha_inicio instanceof Date ? form.fecha_inicio : new Date(form.fecha_inicio);
    
    if (isNaN(fechaInicio.getTime())) return 'Fecha de inicio inválida';
    
    return '';
});

const fechaFinError = computed(() => {
    if (!form.fecha_fin || !form.fecha_inicio) return '';
    
    const fechaInicio = form.fecha_inicio instanceof Date ? form.fecha_inicio : new Date(form.fecha_inicio);
    const fechaFin = form.fecha_fin instanceof Date ? form.fecha_fin : new Date(form.fecha_fin);
    
    if (isNaN(fechaInicio.getTime()) || isNaN(fechaFin.getTime())) return 'Fechas inválidas';
    
    if (fechaFin <= fechaInicio) {
        return 'La fecha de fin debe ser posterior a la fecha de inicio';
    }
    
    return '';
});

// Cálculo de duración del periodo
const duracionPeriodo = computed(() => {
    if (!form.fecha_inicio || !form.fecha_fin) return '';
    
    const inicio = form.fecha_inicio instanceof Date ? form.fecha_inicio : new Date(form.fecha_inicio);
    const fin = form.fecha_fin instanceof Date ? form.fecha_fin : new Date(form.fecha_fin);
    
    // Validar que las fechas sean válidas
    if (isNaN(inicio.getTime()) || isNaN(fin.getTime())) return '';
    
    const diferenciaDias = Math.ceil((fin.getTime() - inicio.getTime()) / (1000 * 60 * 60 * 24));
    
    if (diferenciaDias < 0) return ''; // Fechas inválidas
    
    if (diferenciaDias < 30) {
        return `${diferenciaDias} día${diferenciaDias !== 1 ? 's' : ''}`;
    }
    
    const meses = Math.floor(diferenciaDias / 30);
    const diasRestantes = diferenciaDias % 30;
    
    let resultado = `${meses} mes${meses !== 1 ? 'es' : ''}`;
    
    if (diasRestantes > 0) {
        resultado += ` y ${diasRestantes} día${diasRestantes !== 1 ? 's' : ''}`;
    }
    
    return resultado;
});

// Determinar estado temporal del periodo
const estadoPeriodo = computed(() => {
    if (!form.fecha_inicio || !form.fecha_fin) return null;
    
    const now = new Date();
    const inicio = form.fecha_inicio instanceof Date ? form.fecha_inicio : new Date(form.fecha_inicio);
    const fin = form.fecha_fin instanceof Date ? form.fecha_fin : new Date(form.fecha_fin);
    
    // Validar que las fechas sean válidas
    if (isNaN(inicio.getTime()) || isNaN(fin.getTime())) return null;
    
    if (fin < now) {
        return { label: 'Finalizado', color: 'text-gray-600', icon: Calendar };
    }
    
    if (inicio <= now && fin >= now) {
        return { label: 'Vigente', color: 'text-green-600', icon: Clock };
    }
    
    return { label: 'Próximo', color: 'text-blue-600', icon: Clock };
});

// Validación básica del formulario
const isFormValid = computed(() => {
    return form.nombre.trim().length > 0 && 
           form.fecha_inicio && 
           form.fecha_fin && 
           !fechaInicioError.value && 
           !fechaFinError.value;
});

// Formatear fechas para mostrar
const formatearFecha = (fecha: Date | string | null) => {
    if (!fecha) return '';
    
    // Asegurar que tenemos un objeto Date válido
    const fechaObj = fecha instanceof Date ? fecha : new Date(fecha);
    
    // Verificar que es una fecha válida
    if (isNaN(fechaObj.getTime())) return '';
    
    return fechaObj.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head :title="isEditing ? 'Editar Periodo Electoral' : 'Nuevo Periodo Electoral'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">
                        {{ isEditing ? 'Editar Periodo Electoral' : 'Nuevo Periodo Electoral' }}
                    </h1>
                    <p class="text-muted-foreground">
                        {{ isEditing ? 'Modifica los datos del periodo electoral' : 'Define un nuevo marco temporal para procesos electorales' }}
                    </p>
                </div>
                <Button variant="outline" @click="$inertia.visit('/admin/periodos-electorales')">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver
                </Button>
            </div>

            <!-- Formulario -->
            <Card>
                <CardHeader>
                    <CardTitle>
                        {{ isEditing ? 'Datos del Periodo' : 'Información del Nuevo Periodo' }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <form @submit.prevent="submit">
                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="space-y-4">
                                <!-- Nombre -->
                                <div>
                                    <Label for="nombre">Nombre del Periodo *</Label>
                                    <Input
                                        id="nombre"
                                        v-model="form.nombre"
                                        placeholder="Ej: Elecciones 2024-2028, Periodo Presidencial 2026..."
                                        :class="{ 'border-destructive': form.errors.nombre }"
                                    />
                                    <p v-if="form.errors.nombre" class="text-sm text-destructive mt-1">
                                        {{ form.errors.nombre }}
                                    </p>
                                </div>

                                <!-- Descripción -->
                                <div>
                                    <Label for="descripcion">Descripción (opcional)</Label>
                                    <Textarea
                                        id="descripcion"
                                        v-model="form.descripcion"
                                        placeholder="Descripción detallada del periodo electoral..."
                                        rows="4"
                                        :class="{ 'border-destructive': form.errors.descripcion }"
                                    />
                                    <p v-if="form.errors.descripcion" class="text-sm text-destructive mt-1">
                                        {{ form.errors.descripcion }}
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <!-- Fecha de Inicio -->
                                <div>
                                    <Label for="fecha_inicio">Fecha y Hora de Inicio *</Label>
                                    <DateTimePicker
                                        id="fecha_inicio"
                                        v-model="form.fecha_inicio"
                                        placeholder="Seleccionar fecha y hora de inicio"
                                        :class="{ 'border-destructive': form.errors.fecha_inicio || fechaInicioError }"
                                    />
                                    <p v-if="fechaInicioError" class="text-sm text-destructive mt-1">
                                        {{ fechaInicioError }}
                                    </p>
                                    <p v-if="form.errors.fecha_inicio" class="text-sm text-destructive mt-1">
                                        {{ form.errors.fecha_inicio }}
                                    </p>
                                </div>

                                <!-- Fecha de Fin -->
                                <div>
                                    <Label for="fecha_fin">Fecha y Hora de Fin *</Label>
                                    <DateTimePicker
                                        id="fecha_fin"
                                        v-model="form.fecha_fin"
                                        placeholder="Seleccionar fecha y hora de fin"
                                        :class="{ 'border-destructive': form.errors.fecha_fin || fechaFinError }"
                                    />
                                    <p v-if="fechaFinError" class="text-sm text-destructive mt-1">
                                        {{ fechaFinError }}
                                    </p>
                                    <p v-if="form.errors.fecha_fin" class="text-sm text-destructive mt-1">
                                        {{ form.errors.fecha_fin }}
                                    </p>
                                </div>

                                <!-- Estado Activo -->
                                <div class="flex items-center space-x-2">
                                    <Checkbox
                                        id="activo"
                                        :checked="form.activo"
                                        @update:checked="form.activo = $event"
                                    />
                                    <Label for="activo">
                                        Periodo activo
                                    </Label>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Solo los periodos activos pueden ser utilizados en convocatorias.
                                </p>
                            </div>
                        </div>

                        <!-- Vista previa del periodo -->
                        <div v-if="form.fecha_inicio && form.fecha_fin" class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <Label class="text-blue-800 font-medium">Vista previa del periodo:</Label>
                            <div class="mt-2 space-y-2">
                                <div class="flex items-center gap-2 text-blue-700">
                                    <Calendar class="h-4 w-4" />
                                    <span class="font-medium">{{ formatearFecha(form.fecha_inicio) }}</span>
                                    <span class="text-blue-500">→</span>
                                    <span class="font-medium">{{ formatearFecha(form.fecha_fin) }}</span>
                                </div>
                                <div v-if="duracionPeriodo" class="flex items-center gap-2 text-blue-600">
                                    <Clock class="h-4 w-4" />
                                    <span>Duración: {{ duracionPeriodo }}</span>
                                </div>
                                <div v-if="estadoPeriodo" class="flex items-center gap-2" :class="estadoPeriodo.color">
                                    <component :is="estadoPeriodo.icon" class="h-4 w-4" />
                                    <span>Estado: {{ estadoPeriodo.label }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Errores generales -->
                        <div v-if="form.errors.delete" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-red-800 text-sm">{{ form.errors.delete }}</p>
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex justify-between mt-8">
                            <Button variant="outline" type="button" @click="$inertia.visit('/admin/periodos-electorales')">
                                Cancelar
                            </Button>
                            <Button 
                                type="submit" 
                                :disabled="form.processing || !isFormValid"
                            >
                                {{ form.processing ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Crear') }} Periodo
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>