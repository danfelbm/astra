<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription } from '@/components/ui/alert';
import DynamicFormBuilder from '@/components/forms/DynamicFormBuilder.vue';
import { ArrowLeft, Save, Eye } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';
import { ref, computed, watch, onMounted } from 'vue';
import type { FormField } from '@/types/forms';

// Props
interface Props {
    formulario: {
        id: number;
        titulo: string;
        descripcion?: string;
        slug: string;
        categoria_id?: number;
        configuracion_campos: FormField[];
        configuracion_general?: any;
        tipo_acceso: string;
        permite_visitantes: boolean;
        requiere_captcha: boolean;
        fecha_inicio?: string;
        fecha_fin?: string;
        limite_respuestas?: number;
        limite_por_usuario: number;
        emails_notificacion?: string[];
        mensaje_confirmacion?: string;
        estado: string;
        activo: boolean;
    };
    categorias: Array<{
        id: number;
        nombre: string;
        descripcion?: string;
    }>;
}

const props = defineProps<Props>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Formularios', href: '/admin/formularios' },
    { title: 'Editar', href: '#' },
];

// Form - Inicializar con datos existentes
const form = useForm({
    titulo: props.formulario.titulo,
    descripcion: props.formulario.descripcion || '',
    categoria_id: props.formulario.categoria_id || null,
    configuracion_campos: props.formulario.configuracion_campos || [],
    configuracion_general: props.formulario.configuracion_general || {},
    tipo_acceso: props.formulario.tipo_acceso,
    permite_visitantes: props.formulario.permite_visitantes,
    requiere_captcha: props.formulario.requiere_captcha,
    fecha_inicio: props.formulario.fecha_inicio || null,
    fecha_fin: props.formulario.fecha_fin || null,
    limite_respuestas: props.formulario.limite_respuestas || null,
    limite_por_usuario: props.formulario.limite_por_usuario,
    emails_notificacion: props.formulario.emails_notificacion || [],
    mensaje_confirmacion: props.formulario.mensaje_confirmacion || '',
    estado: props.formulario.estado,
    activo: props.formulario.activo,
    _method: 'PUT', // Para Laravel
});

// Estado local
const activeTab = ref('informacion');
const emailsNotificacion = ref('');
const showPreview = ref(false);
const publicUrl = ref('');

// Inicializar emails en formato string y URL pública
onMounted(() => {
    if (props.formulario.emails_notificacion && props.formulario.emails_notificacion.length > 0) {
        emailsNotificacion.value = props.formulario.emails_notificacion.join(', ');
    }
    // Generar URL pública solo en el cliente
    publicUrl.value = `${window.location.origin}/formularios/${props.formulario.slug}`;
});

// Computed
const isValid = computed(() => {
    return form.titulo && 
           form.configuracion_campos.length > 0 &&
           form.limite_por_usuario >= 1;
});

// Métodos
const handleSubmit = () => {
    // Procesar emails de notificación
    if (emailsNotificacion.value) {
        form.emails_notificacion = emailsNotificacion.value
            .split(',')
            .map(email => email.trim())
            .filter(email => email.length > 0);
    } else {
        form.emails_notificacion = [];
    }
    
    form.post(route('admin.formularios.update', props.formulario.id), {
        preserveScroll: true,
        onError: () => {
            // Manejar errores
        },
    });
};

const handleCancel = () => {
    router.get(route('admin.formularios.index'));
};

const handleFieldsUpdate = (fields: FormField[]) => {
    form.configuracion_campos = fields;
};

// Watch para fechas
watch(() => form.fecha_inicio, (newVal) => {
    if (newVal && form.fecha_fin && newVal > form.fecha_fin) {
        form.fecha_fin = null;
    }
});
</script>

<template>
    <Head title="Editar Formulario" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Encabezado -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Editar Formulario</h1>
                    <p class="text-muted-foreground">
                        Modifica la configuración del formulario
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="handleCancel">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Cancelar
                    </Button>
                    <Button @click="showPreview = !showPreview" variant="outline">
                        <Eye class="mr-2 h-4 w-4" />
                        {{ showPreview ? 'Ocultar' : 'Ver' }} Vista Previa
                    </Button>
                    <Button @click="handleSubmit" :disabled="form.processing || !isValid">
                        <Save class="mr-2 h-4 w-4" />
                        Guardar Cambios
                    </Button>
                </div>
            </div>
            
            <!-- Información del slug -->
            <Alert v-if="publicUrl" class="mb-6">
                <AlertDescription>
                    <strong>URL pública:</strong> {{ publicUrl }}
                </AlertDescription>
            </Alert>
            
            <!-- Errores -->
            <Alert v-if="form.errors && Object.keys(form.errors).length > 0" class="mb-6" variant="destructive">
                <AlertDescription>
                    <ul class="list-disc list-inside">
                        <li v-for="(error, key) in form.errors" :key="key">
                            {{ error }}
                        </li>
                    </ul>
                </AlertDescription>
            </Alert>
            
            <!-- Tabs -->
            <Tabs v-model="activeTab" class="space-y-6">
                <TabsList class="grid w-full grid-cols-2">
                    <TabsTrigger value="informacion">Información General</TabsTrigger>
                    <TabsTrigger value="formulario">Constructor de Formulario</TabsTrigger>
                </TabsList>
                
                <!-- Tab: Información General -->
                <TabsContent value="informacion" class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Información Básica</CardTitle>
                            <CardDescription>
                                Define los datos principales del formulario
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label htmlFor="titulo">Título *</Label>
                                <Input
                                    id="titulo"
                                    v-model="form.titulo"
                                    placeholder="Ej: Encuesta de Satisfacción"
                                    :class="{ 'border-destructive': form.errors.titulo }"
                                />
                                <p v-if="form.errors.titulo" class="text-sm text-destructive mt-1">
                                    {{ form.errors.titulo }}
                                </p>
                            </div>
                            
                            <div>
                                <Label htmlFor="descripcion">Descripción</Label>
                                <Textarea
                                    id="descripcion"
                                    v-model="form.descripcion"
                                    placeholder="Describe el propósito del formulario"
                                    rows="3"
                                />
                            </div>
                            
                            <div>
                                <Label htmlFor="categoria">Categoría</Label>
                                <Select v-model="form.categoria_id">
                                    <SelectTrigger id="categoria">
                                        <SelectValue placeholder="Selecciona una categoría" />
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
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader>
                            <CardTitle>Control de Acceso</CardTitle>
                            <CardDescription>
                                Define quién puede llenar el formulario
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label htmlFor="tipo_acceso">Tipo de Acceso</Label>
                                <Select v-model="form.tipo_acceso">
                                    <SelectTrigger id="tipo_acceso">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="publico">Público</SelectItem>
                                        <SelectItem value="autenticado">Usuarios Autenticados</SelectItem>
                                        <SelectItem value="con_permiso">Con Permiso Específico</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="space-y-0.5">
                                    <Label>Permitir Visitantes</Label>
                                    <p class="text-sm text-muted-foreground">
                                        Usuarios no autenticados pueden llenar el formulario
                                    </p>
                                </div>
                                <Switch v-model="form.permite_visitantes" />
                            </div>
                            
                            <div v-if="form.permite_visitantes" class="flex items-center justify-between">
                                <div class="space-y-0.5">
                                    <Label>Requiere Captcha</Label>
                                    <p class="text-sm text-muted-foreground">
                                        Protección contra bots para visitantes
                                    </p>
                                </div>
                                <Switch v-model="form.requiere_captcha" />
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader>
                            <CardTitle>Vigencia y Límites</CardTitle>
                            <CardDescription>
                                Controla la disponibilidad del formulario
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="fecha_inicio">Fecha de Inicio</Label>
                                    <Input
                                        id="fecha_inicio"
                                        type="datetime-local"
                                        v-model="form.fecha_inicio"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="fecha_fin">Fecha de Fin</Label>
                                    <Input
                                        id="fecha_fin"
                                        type="datetime-local"
                                        v-model="form.fecha_fin"
                                        :min="form.fecha_inicio"
                                    />
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="limite_respuestas">Límite Total de Respuestas</Label>
                                    <Input
                                        id="limite_respuestas"
                                        type="number"
                                        v-model.number="form.limite_respuestas"
                                        placeholder="Sin límite"
                                        min="1"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="limite_por_usuario">Respuestas por Usuario *</Label>
                                    <Input
                                        id="limite_por_usuario"
                                        type="number"
                                        v-model.number="form.limite_por_usuario"
                                        min="1"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader>
                            <CardTitle>Configuración Adicional</CardTitle>
                            <CardDescription>
                                Opciones avanzadas del formulario
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label htmlFor="estado">Estado</Label>
                                <Select v-model="form.estado">
                                    <SelectTrigger id="estado">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="borrador">Borrador</SelectItem>
                                        <SelectItem value="publicado">Publicado</SelectItem>
                                        <SelectItem value="archivado">Archivado</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            
                            <div>
                                <Label htmlFor="emails">Emails de Notificación</Label>
                                <Input
                                    id="emails"
                                    v-model="emailsNotificacion"
                                    placeholder="email1@ejemplo.com, email2@ejemplo.com"
                                />
                                <p class="text-sm text-muted-foreground mt-1">
                                    Separa múltiples emails con comas
                                </p>
                            </div>
                            
                            <div>
                                <Label htmlFor="mensaje">Mensaje de Confirmación</Label>
                                <Textarea
                                    id="mensaje"
                                    v-model="form.mensaje_confirmacion"
                                    placeholder="Mensaje personalizado al completar el formulario"
                                    rows="3"
                                />
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="space-y-0.5">
                                    <Label>Formulario Activo</Label>
                                    <p class="text-sm text-muted-foreground">
                                        El formulario estará disponible
                                    </p>
                                </div>
                                <Switch v-model="form.activo" />
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
                
                <!-- Tab: Constructor de Formulario -->
                <TabsContent value="formulario" class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Constructor de Formulario</CardTitle>
                            <CardDescription>
                                Arrastra y suelta campos para construir tu formulario
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <DynamicFormBuilder
                                v-model="form.configuracion_campos"
                                @update:modelValue="handleFieldsUpdate"
                                :disabled="false"
                                :showEditableOption="true"
                            />
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>