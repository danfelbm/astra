<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Crear Nueva Votación</DialogTitle>
                <DialogDescription>
                    Cree una votación rápida que se asociará automáticamente a la asamblea actual.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Tabs para organizar el contenido -->
                <Tabs v-model="activeTab" class="w-full">
                    <TabsList class="grid w-full grid-cols-2">
                        <TabsTrigger value="basic">Datos Básicos</TabsTrigger>
                        <TabsTrigger value="form">Formulario</TabsTrigger>
                    </TabsList>

                    <!-- Tab: Datos Básicos -->
                    <TabsContent value="basic" class="space-y-4 mt-4">
                        <div class="space-y-2">
                            <Label for="titulo" required>Título de la Votación</Label>
                            <Input
                                id="titulo"
                                v-model="form.titulo"
                                placeholder="Ej: Elección de Junta Directiva 2024"
                                :class="{ 'border-red-500': form.errors.titulo }"
                            />
                            <span v-if="form.errors.titulo" class="text-sm text-red-500">
                                {{ form.errors.titulo }}
                            </span>
                        </div>

                        <div class="space-y-2">
                            <Label for="descripcion">Descripción</Label>
                            <Textarea
                                id="descripcion"
                                v-model="form.descripcion"
                                placeholder="Describe brevemente el propósito de esta votación"
                                rows="3"
                                :class="{ 'border-red-500': form.errors.descripcion }"
                            />
                            <span v-if="form.errors.descripcion" class="text-sm text-red-500">
                                {{ form.errors.descripcion }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="categoria">Categoría</Label>
                                <Select v-model="form.categoria_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar categoría" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem 
                                            v-for="categoria in categorias" 
                                            :key="categoria.id"
                                            :value="categoria.id.toString()"
                                        >
                                            {{ categoria.nombre }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <span v-if="form.errors.categoria_id" class="text-sm text-red-500">
                                    {{ form.errors.categoria_id }}
                                </span>
                            </div>

                            <div class="space-y-2">
                                <Label for="timezone">Zona Horaria</Label>
                                <Select v-model="form.timezone">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar zona horaria" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="America/Bogota">Colombia (GMT-5)</SelectItem>
                                        <SelectItem value="America/Mexico_City">México (GMT-6)</SelectItem>
                                        <SelectItem value="America/Buenos_Aires">Argentina (GMT-3)</SelectItem>
                                        <SelectItem value="America/Lima">Perú (GMT-5)</SelectItem>
                                        <SelectItem value="America/Santiago">Chile (GMT-3)</SelectItem>
                                        <SelectItem value="America/Caracas">Venezuela (GMT-4)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="fecha_inicio" required>Fecha y Hora de Inicio</Label>
                                <DateTimePicker
                                    v-model="form.fecha_inicio"
                                    placeholder="Seleccionar fecha y hora"
                                    :class="{ 'border-red-500': form.errors.fecha_inicio }"
                                />
                                <span v-if="form.errors.fecha_inicio" class="text-sm text-red-500">
                                    {{ form.errors.fecha_inicio }}
                                </span>
                            </div>

                            <div class="space-y-2">
                                <Label for="fecha_fin" required>Fecha y Hora de Fin</Label>
                                <DateTimePicker
                                    v-model="form.fecha_fin"
                                    placeholder="Seleccionar fecha y hora"
                                    :class="{ 'border-red-500': form.errors.fecha_fin || fechaFinError }"
                                />
                                <span v-if="fechaFinError" class="text-sm text-red-500">
                                    {{ fechaFinError }}
                                </span>
                                <span v-else-if="form.errors.fecha_fin" class="text-sm text-red-500">
                                    {{ form.errors.fecha_fin }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center space-x-2">
                                <Switch
                                    id="resultados_publicos"
                                    v-model="form.resultados_publicos"
                                />
                                <Label for="resultados_publicos" class="cursor-pointer">
                                    Resultados públicos al finalizar
                                </Label>
                            </div>

                            <div v-if="form.resultados_publicos" class="pl-7 space-y-2">
                                <Label for="fecha_publicacion">
                                    Fecha de publicación de resultados (opcional)
                                </Label>
                                <DateTimePicker
                                    v-model="form.fecha_publicacion_resultados"
                                    placeholder="Dejar vacío para publicar al finalizar"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Si no especifica una fecha, los resultados se publicarán automáticamente al finalizar la votación
                                </p>
                            </div>
                        </div>
                    </TabsContent>

                    <!-- Tab: Formulario -->
                    <TabsContent value="form" class="space-y-4 mt-4">
                        <Alert>
                            <Info class="h-4 w-4" />
                            <AlertTitle>Configuración del Formulario</AlertTitle>
                            <AlertDescription>
                                Configure las preguntas que los votantes deberán responder.
                            </AlertDescription>
                        </Alert>

                        <DynamicFormBuilder
                            v-model="form.formulario_config"
                            :errors="form.errors.formulario_config"
                        />
                    </TabsContent>
                </Tabs>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="handleClose">
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        <Save v-else class="mr-2 h-4 w-4" />
                        Crear Votación
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { DateTimePicker } from '@/components/ui/datetime-picker';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import DynamicFormBuilder from '@/components/forms/DynamicFormBuilder.vue';
import { Save, Loader2, Info } from 'lucide-vue-next';
import axios from 'axios';

interface Props {
    open: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
    created: [votacionId: number];
}>();

// Estado del componente
const activeTab = ref('basic');
const categorias = ref([]);

// Formulario
const form = useForm({
    titulo: '',
    descripcion: '',
    categoria_id: null,
    formulario_config: [],
    fecha_inicio: '',
    fecha_fin: '',
    estado: 'activa',
    resultados_publicos: false,
    fecha_publicacion_resultados: '',
    timezone: 'America/Bogota',
});

// Validación de fechas
const fechaFinError = computed(() => {
    if (!form.fecha_fin || !form.fecha_inicio) return '';
    const fechaFin = new Date(form.fecha_fin);
    const fechaInicio = new Date(form.fecha_inicio);
    if (fechaFin <= fechaInicio) {
        return 'La fecha de fin debe ser posterior a la fecha de inicio';
    }
    return '';
});

// Cargar categorías al montar
onMounted(async () => {
    try {
        const response = await axios.get('/api/categorias-votacion');
        categorias.value = response.data;
    } catch (error) {
        console.error('Error cargando categorías:', error);
    }
});

// Manejar cierre
const handleClose = () => {
    if (!form.processing) {
        emit('close');
    }
};

// Enviar formulario
const submit = () => {
    if (fechaFinError.value) {
        return;
    }

    form.post(route('admin.votaciones.store.quick'), {
        preserveScroll: true,
        onSuccess: (page) => {
            // Obtener el ID de la votación creada desde la respuesta
            const votacionId = page.props.flash?.votacion_id;
            if (votacionId) {
                emit('created', votacionId);
            }
            form.reset();
        },
        onError: () => {
            // Los errores se manejan automáticamente
        },
    });
};
</script>