<script setup lang="ts">
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { Textarea } from "@modules/Core/Resources/js/components/ui/textarea";
import { Switch } from "@modules/Core/Resources/js/components/ui/switch";
import { Alert, AlertDescription } from "@modules/Core/Resources/js/components/ui/alert";
import { type BreadcrumbItemType } from '@/types';
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import WhatsAppTemplateEditor from "@modules/Campanas/Resources/js/Components/WhatsAppTemplateEditor.vue";
import { Head, useForm, router } from '@inertiajs/vue3';
import { ArrowLeft, Save, MessageSquare, Info, AlertCircle, Smartphone } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { toast } from 'vue-sonner';

interface PlantillaWhatsApp {
    id: number;
    nombre: string;
    descripcion?: string;
    contenido: string;
    usa_formato: boolean;
    es_activa: boolean;
    variables_usadas?: string[];
}

interface Props {
    plantilla?: PlantillaWhatsApp | null;
    variablesDisponibles?: any;
}

const props = defineProps<Props>();

const isEditing = computed(() => !!props.plantilla?.id);

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Campañas', href: '/admin/envio-campanas' },
    { title: 'Plantillas WhatsApp', href: '/admin/campanas/plantillas-whatsapp' },
    { title: isEditing.value ? 'Editar Plantilla' : 'Nueva Plantilla', href: '#' },
];

const form = useForm({
    nombre: props.plantilla?.nombre || '',
    descripcion: props.plantilla?.descripcion || '',
    contenido: props.plantilla?.contenido || '',
    usa_formato: props.plantilla?.usa_formato ?? true,
    es_activa: props.plantilla?.es_activa ?? true,
});

const variablesUsadas = ref<string[]>([]);

const handleContentChange = (content: string) => {
    form.contenido = content;
};

const handleFormatoChange = (valor: boolean) => {
    form.usa_formato = valor;
};

const handleVariablesUpdate = (variables: string[]) => {
    variablesUsadas.value = variables;
};

const submit = () => {
    if (isEditing.value) {
        form.put(`/admin/campanas/plantillas-whatsapp/${props.plantilla?.id}`, {
            onSuccess: () => {
                toast.success('Plantilla actualizada exitosamente');
            },
            onError: () => {
                toast.error('Error al actualizar la plantilla');
            }
        });
    } else {
        form.post('/admin/campanas/plantillas-whatsapp', {
            onSuccess: () => {
                toast.success('Plantilla creada exitosamente');
            },
            onError: () => {
                toast.error('Error al crear la plantilla');
            }
        });
    }
};

const canSubmit = computed(() => {
    return form.nombre && form.contenido && !form.processing;
});

const sendTestMessage = () => {
    const phone = prompt('Ingresa el número de WhatsApp para la prueba (con código de país):');
    if (!phone) return;

    router.post(`/admin/campanas/plantillas-whatsapp/${props.plantilla?.id || 'preview'}/test`, {
        phone,
        ...form.data()
    }, {
        onSuccess: () => {
            toast.success(`Mensaje de prueba enviado a ${phone}`);
        },
        onError: () => {
            toast.error('Error al enviar el mensaje de prueba');
        }
    });
};
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head :title="isEditing ? 'Editar Plantilla WhatsApp' : 'Nueva Plantilla WhatsApp'" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">
                        {{ isEditing ? 'Editar Plantilla' : 'Nueva Plantilla de WhatsApp' }}
                    </h1>
                    <p class="text-muted-foreground mt-1">
                        {{ isEditing 
                            ? 'Modifica los detalles de la plantilla de WhatsApp' 
                            : 'Crea una nueva plantilla para tus campañas de WhatsApp' 
                        }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <router-link href="/admin/campanas/plantillas-whatsapp">
                        <Button type="button" variant="outline">
                            <ArrowLeft class="w-4 h-4 mr-2" />
                            Cancelar
                        </Button>
                    </router-link>
                    <Button
                        v-if="isEditing"
                        type="button"
                        variant="outline"
                        @click="sendTestMessage"
                    >
                        <Smartphone class="w-4 h-4 mr-2" />
                        Enviar Prueba
                    </Button>
                    <Button
                        type="submit"
                        :disabled="!canSubmit"
                    >
                        <Save class="w-4 h-4 mr-2" />
                        {{ isEditing ? 'Actualizar' : 'Guardar' }} Plantilla
                    </Button>
                </div>
            </div>

            <Alert v-if="Object.keys(form.errors).length > 0" variant="destructive">
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>
                    <ul class="list-disc pl-4">
                        <li v-for="(error, key) in form.errors" :key="key">
                            {{ error }}
                        </li>
                    </ul>
                </AlertDescription>
            </Alert>

            <Card>
                <CardHeader>
                    <CardTitle>Información Básica</CardTitle>
                    <CardDescription>
                        Define el nombre y descripción de la plantilla
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="nombre">
                                Nombre de la Plantilla <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="nombre"
                                v-model="form.nombre"
                                placeholder="Ej: Recordatorio de evento"
                                :error="form.errors.nombre"
                            />
                            <p v-if="form.errors.nombre" class="text-sm text-destructive mt-1">
                                {{ form.errors.nombre }}
                            </p>
                        </div>
                        <div>
                            <Label htmlFor="es_activa">Estado</Label>
                            <div class="flex items-center space-x-2 mt-2">
                                <Switch
                                    id="es_activa"
                                    v-model="form.es_activa"
                                />
                                <Label for="es_activa" class="cursor-pointer">
                                    {{ form.es_activa ? 'Activa' : 'Inactiva' }}
                                </Label>
                            </div>
                        </div>
                    </div>
                    <div>
                        <Label htmlFor="descripcion">Descripción</Label>
                        <Textarea
                            id="descripcion"
                            v-model="form.descripcion"
                            placeholder="Describe el propósito de esta plantilla..."
                            rows="3"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Contenido del Mensaje</CardTitle>
                    <CardDescription>
                        Define el contenido del mensaje de WhatsApp
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <WhatsAppTemplateEditor
                        :modelValue="form.contenido"
                        :usaFormato="form.usa_formato"
                        @update:modelValue="handleContentChange"
                        @update:usaFormato="handleFormatoChange"
                        @update:variables="handleVariablesUpdate"
                    />
                    <p v-if="form.errors.contenido" class="text-sm text-destructive mt-1">
                        {{ form.errors.contenido }}
                    </p>
                </CardContent>
            </Card>

            <Alert v-if="variablesUsadas.length > 0">
                <Info class="h-4 w-4" />
                <AlertDescription>
                    <p class="font-medium mb-2">Variables utilizadas:</p>
                    <div class="flex flex-wrap gap-2">
                        <code 
                            v-for="variable in variablesUsadas" 
                            :key="variable"
                            class="px-2 py-1 bg-muted rounded text-sm"
                        >
                            {{ variable }}
                        </code>
                    </div>
                </AlertDescription>
            </Alert>
            </form>
        </div>
    </AdminLayout>
</template>