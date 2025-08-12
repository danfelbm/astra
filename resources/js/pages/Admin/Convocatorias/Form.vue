<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { DateTimePicker } from '@/components/ui/datetime-picker';
import DynamicFormBuilder from '@/components/forms/DynamicFormBuilder.vue';
import GeographicRestrictions from '@/components/forms/GeographicRestrictions.vue';
import { type BreadcrumbItemType } from '@/types';
import { type FormField, type GeographicRestrictions as GeographicRestrictionsType } from '@/types/forms';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Clock, MapPin, Megaphone, Settings, Users } from 'lucide-vue-next';
import { computed, ref, watch, onMounted } from 'vue';

interface Cargo {
    id: number;
    nombre: string;
    ruta_jerarquica: string;
}

interface PeriodoElectoral {
    id: number;
    nombre: string;
    fecha_inicio: string;
    fecha_fin: string;
    estado: string;
    estado_label: string;
}

interface Convocatoria {
    id: number;
    nombre: string;
    descripcion?: string;
    fecha_apertura: string;
    fecha_cierre: string;
    cargo_id: number;
    periodo_electoral_id: number;
    territorio_id?: number;
    departamento_id?: number;
    municipio_id?: number;
    localidad_id?: number;
    formulario_postulacion?: FormField[];
    estado: string;
    activo: boolean;
}

interface Props {
    convocatoria?: Convocatoria | null;
}

const props = defineProps<Props>();

const isEditing = computed(() => !!props.convocatoria);

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Convocatorias', href: '/admin/convocatorias' },
    { title: isEditing.value ? 'Editar' : 'Crear', href: '#' },
];

// Estado para tabs
const activeTab = ref('general');

// Opciones para selectores
const cargosDisponibles = ref<Cargo[]>([]);
const periodosDisponibles = ref<PeriodoElectoral[]>([]);
const loadingCargos = ref(false);
const loadingPeriodos = ref(false);

// Función para inicializar datos del formulario
const getInitialFormData = () => {
    if (!props.convocatoria) {
        return {
            nombre: '',
            descripcion: '',
            fecha_apertura: null,
            fecha_cierre: null,
            cargo_id: null,
            periodo_electoral_id: null,
            territorio_id: null,
            departamento_id: null,
            municipio_id: null,
            localidad_id: null,
            formulario_postulacion: [],
            estado: 'borrador',
            activo: true,
        };
    }

    const convocatoria = props.convocatoria;
    
    return {
        nombre: convocatoria.nombre || '',
        descripcion: convocatoria.descripcion || '',
        fecha_apertura: convocatoria.fecha_apertura ? new Date(convocatoria.fecha_apertura) : null,
        fecha_cierre: convocatoria.fecha_cierre ? new Date(convocatoria.fecha_cierre) : null,
        cargo_id: convocatoria.cargo_id || null,
        periodo_electoral_id: convocatoria.periodo_electoral_id || null,
        territorio_id: convocatoria.territorio_id || null,
        departamento_id: convocatoria.departamento_id || null,
        municipio_id: convocatoria.municipio_id || null,
        localidad_id: convocatoria.localidad_id || null,
        formulario_postulacion: convocatoria.formulario_postulacion || [],
        estado: convocatoria.estado || 'borrador',
        activo: convocatoria.activo,
    };
};

// Formulario principal
const form = useForm(getInitialFormData());

// Restricciones geográficas
const geographicRestrictions = ref<GeographicRestrictionsType>({
    territorios_ids: form.territorio_id ? [form.territorio_id] : [],
    departamentos_ids: form.departamento_id ? [form.departamento_id] : [],
    municipios_ids: form.municipio_id ? [form.municipio_id] : [],
    localidades_ids: form.localidad_id ? [form.localidad_id] : [],
});

// Sincronizar restricciones geográficas con el formulario
watch(geographicRestrictions, (newValue) => {
    form.territorio_id = newValue.territorios_ids[0] || null;
    form.departamento_id = newValue.departamentos_ids[0] || null;
    form.municipio_id = newValue.municipios_ids[0] || null;
    form.localidad_id = newValue.localidades_ids[0] || null;
}, { deep: true });

// Cargar datos iniciales
onMounted(async () => {
    await Promise.all([
        cargarCargos(),
        cargarPeriodos(),
    ]);
});

// Función para cargar cargos disponibles
const cargarCargos = async () => {
    try {
        loadingCargos.value = true;
        const response = await fetch('/admin/cargos-for-convocatorias');
        cargosDisponibles.value = await response.json();
    } catch (error) {
        console.error('Error loading cargos:', error);
    } finally {
        loadingCargos.value = false;
    }
};

// Función para cargar periodos disponibles
const cargarPeriodos = async () => {
    try {
        loadingPeriodos.value = true;
        const response = await fetch('/admin/periodos-disponibles');
        periodosDisponibles.value = await response.json();
    } catch (error) {
        console.error('Error loading periodos:', error);
    } finally {
        loadingPeriodos.value = false;
    }
};

// Enviar formulario
const submit = () => {
    if (isEditing.value) {
        form.put(`/admin/convocatorias/${props.convocatoria!.id}`, {
            onError: (errors) => {
                console.error('Errores de validación:', errors);
            }
        });
    } else {
        form.post('/admin/convocatorias', {
            onError: (errors) => {
                console.error('Errores de validación:', errors);
            }
        });
    }
};

// Validaciones reactivas
const fechaAperturaError = computed(() => {
    if (!form.fecha_apertura) return '';
    
    const fechaApertura = form.fecha_apertura instanceof Date ? form.fecha_apertura : new Date(form.fecha_apertura);
    
    if (isNaN(fechaApertura.getTime())) return 'Fecha de apertura inválida';
    
    return '';
});

const fechaCierreError = computed(() => {
    if (!form.fecha_cierre || !form.fecha_apertura) return '';
    
    const fechaApertura = form.fecha_apertura instanceof Date ? form.fecha_apertura : new Date(form.fecha_apertura);
    const fechaCierre = form.fecha_cierre instanceof Date ? form.fecha_cierre : new Date(form.fecha_cierre);
    
    if (isNaN(fechaApertura.getTime()) || isNaN(fechaCierre.getTime())) return 'Fechas inválidas';
    
    if (fechaCierre <= fechaApertura) {
        return 'La fecha de cierre debe ser posterior a la fecha de apertura';
    }
    
    return '';
});

// Validación de fechas dentro del periodo electoral
const fechasPeriodoError = computed(() => {
    if (!form.fecha_apertura || !form.fecha_cierre || !form.periodo_electoral_id) return '';
    
    const periodoSeleccionado = periodosDisponibles.value.find(p => p.id === form.periodo_electoral_id);
    if (!periodoSeleccionado) return '';
    
    const fechaApertura = form.fecha_apertura instanceof Date ? form.fecha_apertura : new Date(form.fecha_apertura);
    const fechaCierre = form.fecha_cierre instanceof Date ? form.fecha_cierre : new Date(form.fecha_cierre);
    const periodoInicio = new Date(periodoSeleccionado.fecha_inicio);
    const periodoFin = new Date(periodoSeleccionado.fecha_fin);
    
    if (fechaApertura < periodoInicio || fechaApertura > periodoFin ||
        fechaCierre < periodoInicio || fechaCierre > periodoFin) {
        return 'Las fechas deben estar dentro del periodo electoral seleccionado';
    }
    
    return '';
});

// Cálculo de duración de la convocatoria
const duracionConvocatoria = computed(() => {
    if (!form.fecha_apertura || !form.fecha_cierre) return '';
    
    const inicio = form.fecha_apertura instanceof Date ? form.fecha_apertura : new Date(form.fecha_apertura);
    const fin = form.fecha_cierre instanceof Date ? form.fecha_cierre : new Date(form.fecha_cierre);
    
    if (isNaN(inicio.getTime()) || isNaN(fin.getTime())) return '';
    
    const diferenciaDias = Math.ceil((fin.getTime() - inicio.getTime()) / (1000 * 60 * 60 * 24));
    
    if (diferenciaDias < 0) return '';
    
    if (diferenciaDias < 1) {
        const horas = Math.ceil((fin.getTime() - inicio.getTime()) / (1000 * 60 * 60));
        return `${horas} hora${horas !== 1 ? 's' : ''}`;
    }
    
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

// Validación básica del formulario
const isFormValid = computed(() => {
    return form.nombre.trim().length > 0 && 
           form.fecha_apertura && 
           form.fecha_cierre && 
           form.cargo_id &&
           form.periodo_electoral_id &&
           !fechaAperturaError.value && 
           !fechaCierreError.value &&
           !fechasPeriodoError.value;
});

// Formatear fechas para mostrar
const formatearFecha = (fecha: Date | string | null) => {
    if (!fecha) return '';
    
    const fechaObj = fecha instanceof Date ? fecha : new Date(fecha);
    
    if (isNaN(fechaObj.getTime())) return '';
    
    return fechaObj.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Obtener el cargo seleccionado
const cargoSeleccionado = computed(() => {
    if (!form.cargo_id) return null;
    return cargosDisponibles.value.find(c => c.id === form.cargo_id);
});

// Obtener el periodo seleccionado
const periodoSeleccionado = computed(() => {
    if (!form.periodo_electoral_id) return null;
    return periodosDisponibles.value.find(p => p.id === form.periodo_electoral_id);
});
</script>

<template>
    <Head :title="isEditing ? 'Editar Convocatoria' : 'Nueva Convocatoria'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">
                        {{ isEditing ? 'Editar Convocatoria' : 'Nueva Convocatoria' }}
                    </h1>
                    <p class="text-muted-foreground">
                        {{ isEditing ? 'Modifica los datos de la convocatoria electoral' : 'Crea una nueva convocatoria con formulario de postulación personalizado' }}
                    </p>
                </div>
                <Button variant="outline" @click="$inertia.visit('/admin/convocatorias')">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver
                </Button>
            </div>

            <!-- Formulario con Tabs -->
            <Card>
                <CardHeader>
                    <CardTitle>
                        {{ isEditing ? 'Datos de la Convocatoria' : 'Información de la Nueva Convocatoria' }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit">
                        <Tabs v-model="activeTab" class="w-full">
                            <TabsList class="grid w-full grid-cols-2">
                                <TabsTrigger value="general" class="flex items-center gap-2">
                                    <Megaphone class="h-4 w-4" />
                                    Información General
                                </TabsTrigger>
                                <TabsTrigger value="formulario" class="flex items-center gap-2">
                                    <Settings class="h-4 w-4" />
                                    Formulario de Postulación
                                </TabsTrigger>
                            </TabsList>

                            <!-- Tab 1: Información General -->
                            <TabsContent value="general" class="space-y-6">
                                <div class="grid gap-6 md:grid-cols-2">
                                    <div class="space-y-4">
                                        <!-- Nombre -->
                                        <div>
                                            <Label for="nombre">Nombre de la Convocatoria *</Label>
                                            <Input
                                                id="nombre"
                                                v-model="form.nombre"
                                                placeholder="Ej: Elección Alcalde 2024, Postulación Senado..."
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
                                                placeholder="Descripción detallada de la convocatoria..."
                                                rows="4"
                                                :class="{ 'border-destructive': form.errors.descripcion }"
                                            />
                                            <p v-if="form.errors.descripcion" class="text-sm text-destructive mt-1">
                                                {{ form.errors.descripcion }}
                                            </p>
                                        </div>

                                        <!-- Estado -->
                                        <div>
                                            <Label for="estado">Estado *</Label>
                                            <Select v-model="form.estado">
                                                <SelectTrigger :class="{ 'border-destructive': form.errors.estado }">
                                                    <SelectValue placeholder="Seleccionar estado" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="borrador">Borrador</SelectItem>
                                                    <SelectItem value="activa">Activa</SelectItem>
                                                    <SelectItem value="cerrada">Cerrada</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <p v-if="form.errors.estado" class="text-sm text-destructive mt-1">
                                                {{ form.errors.estado }}
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
                                                Convocatoria activa
                                            </Label>
                                        </div>
                                        <p class="text-xs text-muted-foreground">
                                            Solo las convocatorias activas pueden recibir postulaciones.
                                        </p>
                                    </div>

                                    <div class="space-y-4">
                                        <!-- Fecha de Apertura -->
                                        <div>
                                            <Label for="fecha_apertura">Fecha y Hora de Apertura *</Label>
                                            <DateTimePicker
                                                id="fecha_apertura"
                                                v-model="form.fecha_apertura"
                                                placeholder="Seleccionar fecha y hora de apertura"
                                                :class="{ 'border-destructive': form.errors.fecha_apertura || fechaAperturaError }"
                                            />
                                            <p v-if="fechaAperturaError" class="text-sm text-destructive mt-1">
                                                {{ fechaAperturaError }}
                                            </p>
                                            <p v-if="form.errors.fecha_apertura" class="text-sm text-destructive mt-1">
                                                {{ form.errors.fecha_apertura }}
                                            </p>
                                        </div>

                                        <!-- Fecha de Cierre -->
                                        <div>
                                            <Label for="fecha_cierre">Fecha y Hora de Cierre *</Label>
                                            <DateTimePicker
                                                id="fecha_cierre"
                                                v-model="form.fecha_cierre"
                                                placeholder="Seleccionar fecha y hora de cierre"
                                                :class="{ 'border-destructive': form.errors.fecha_cierre || fechaCierreError }"
                                            />
                                            <p v-if="fechaCierreError" class="text-sm text-destructive mt-1">
                                                {{ fechaCierreError }}
                                            </p>
                                            <p v-if="form.errors.fecha_cierre" class="text-sm text-destructive mt-1">
                                                {{ form.errors.fecha_cierre }}
                                            </p>
                                            <p v-if="fechasPeriodoError" class="text-sm text-destructive mt-1">
                                                {{ fechasPeriodoError }}
                                            </p>
                                        </div>

                                        <!-- Cargo -->
                                        <div>
                                            <Label for="cargo_id">Cargo *</Label>
                                            <Select v-model="form.cargo_id">
                                                <SelectTrigger :class="{ 'border-destructive': form.errors.cargo_id }">
                                                    <SelectValue :placeholder="loadingCargos ? 'Cargando cargos...' : 'Seleccionar cargo'" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="cargo in cargosDisponibles"
                                                        :key="cargo.id"
                                                        :value="cargo.id"
                                                    >
                                                        {{ cargo.ruta_jerarquica }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <p v-if="form.errors.cargo_id" class="text-sm text-destructive mt-1">
                                                {{ form.errors.cargo_id }}
                                            </p>
                                        </div>

                                        <!-- Periodo Electoral -->
                                        <div>
                                            <Label for="periodo_electoral_id">Periodo Electoral *</Label>
                                            <Select v-model="form.periodo_electoral_id">
                                                <SelectTrigger :class="{ 'border-destructive': form.errors.periodo_electoral_id }">
                                                    <SelectValue :placeholder="loadingPeriodos ? 'Cargando periodos...' : 'Seleccionar periodo'" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="periodo in periodosDisponibles"
                                                        :key="periodo.id"
                                                        :value="periodo.id"
                                                    >
                                                        {{ periodo.nombre }} ({{ periodo.estado_label }})
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <p v-if="form.errors.periodo_electoral_id" class="text-sm text-destructive mt-1">
                                                {{ form.errors.periodo_electoral_id }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Restricciones Geográficas -->
                                <GeographicRestrictions
                                    v-model="geographicRestrictions"
                                    title="Restricciones Geográficas (Opcional)"
                                    description="Define las regiones donde estará disponible esta convocatoria. Si no seleccionas nada, estará disponible nacionalmente."
                                />

                                <!-- Vista previa de la convocatoria -->
                                <div v-if="form.fecha_apertura && form.fecha_cierre" class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                                    <Label class="text-blue-800 font-medium">Vista previa de la convocatoria:</Label>
                                    <div class="mt-2 space-y-2">
                                        <div class="flex items-center gap-2 text-blue-700">
                                            <Calendar class="h-4 w-4" />
                                            <span class="font-medium">{{ formatearFecha(form.fecha_apertura) }}</span>
                                            <span class="text-blue-500">→</span>
                                            <span class="font-medium">{{ formatearFecha(form.fecha_cierre) }}</span>
                                        </div>
                                        <div v-if="duracionConvocatoria" class="flex items-center gap-2 text-blue-600">
                                            <Clock class="h-4 w-4" />
                                            <span>Duración: {{ duracionConvocatoria }}</span>
                                        </div>
                                        <div v-if="cargoSeleccionado" class="flex items-center gap-2 text-blue-600">
                                            <Users class="h-4 w-4" />
                                            <span>Cargo: {{ cargoSeleccionado.ruta_jerarquica }}</span>
                                        </div>
                                        <div v-if="periodoSeleccionado" class="flex items-center gap-2 text-blue-600">
                                            <Calendar class="h-4 w-4" />
                                            <span>Periodo: {{ periodoSeleccionado.nombre }}</span>
                                        </div>
                                    </div>
                                </div>
                            </TabsContent>

                            <!-- Tab 2: Formulario de Postulación -->
                            <TabsContent value="formulario" class="space-y-6">
                                <DynamicFormBuilder
                                    v-model="form.formulario_postulacion"
                                    title="Formulario de Postulación"
                                    description="Define los campos que deberán completar los postulantes. Estos campos se mostrarán además de la información básica requerida."
                                />
                                
                                <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                                    <p class="text-yellow-800 text-sm">
                                        <strong>Nota:</strong> En el futuro, aquí también podrás agregar un campo especial "Perfil de Candidatura" 
                                        que permitirá a los usuarios vincular sus perfiles de candidatura aprobados a sus postulaciones.
                                    </p>
                                </div>
                            </TabsContent>
                        </Tabs>

                        <!-- Errores generales -->
                        <div v-if="form.errors.delete" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-red-800 text-sm">{{ form.errors.delete }}</p>
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex justify-between mt-8">
                            <Button variant="outline" type="button" @click="$inertia.visit('/admin/convocatorias')">
                                Cancelar
                            </Button>
                            <Button 
                                type="submit" 
                                :disabled="form.processing || !isFormValid"
                            >
                                {{ form.processing ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Crear') }} Convocatoria
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>