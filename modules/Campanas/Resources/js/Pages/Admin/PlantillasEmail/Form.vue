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
import EmailTemplateEditor from "@modules/Campanas/Resources/js/Components/EmailTemplateEditor.vue";
import { Head, useForm, router } from '@inertiajs/vue3';
import { ArrowLeft, Save, Mail, Info, AlertCircle, Eye } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { useTemplateVariables } from '@modules/Campanas/Resources/js/composables/useTemplateVariables';

interface PlantillaEmail {
    id: number;
    nombre: string;
    descripcion?: string;
    asunto: string;
    contenido_html: string;
    contenido_texto?: string;
    variables_usadas?: string[];
    es_activa: boolean;
    created_at?: string;
    updated_at?: string;
}

interface Props {
    plantilla?: PlantillaEmail | null;
    variablesDisponibles?: any;
}

const props = defineProps<Props>();

const isEditing = computed(() => !!props.plantilla?.id);

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Campañas', href: '/admin/campanas' },
    { title: 'Plantillas Email', href: '/admin/campanas/plantillas-email' },
    { 
        title: isEditing.value ? 'Editar Plantilla' : 'Nueva Plantilla', 
        href: '#' 
    },
];

const form = useForm({
    nombre: props.plantilla?.nombre || '',
    descripcion: props.plantilla?.descripcion || '',
    asunto: props.plantilla?.asunto || '',
    contenido_html: props.plantilla?.contenido_html || '',
    contenido_texto: props.plantilla?.contenido_texto || '',
    es_activa: props.plantilla?.es_activa ?? true,
});

// Variables usadas en la plantilla
const variablesUsadas = ref<string[]>([]);
const showPreview = ref(false);

const handleVariablesUpdate = (variables: string[]) => {
    variablesUsadas.value = variables;
};

// Extraer variables del asunto también
const extractAllVariables = () => {
    const { extractUsedVariables } = useTemplateVariables();
    const allContent = `${form.asunto} ${form.contenido_html}`;
    variablesUsadas.value = extractUsedVariables(allContent);
};

// Watch para generar versión de texto plano automáticamente y extraer variables
watch(() => form.contenido_html, (content) => {
    if (content) {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = content;
        form.contenido_texto = tempDiv.textContent || tempDiv.innerText || '';
    }
    extractAllVariables();
});

// Watch para extraer variables del asunto también
watch(() => form.asunto, () => {
    extractAllVariables();
});

// Guardar plantilla
const submit = () => {
    if (isEditing.value) {
        form.put(`/admin/campanas/plantillas-email/${props.plantilla?.id}`, {
            onSuccess: () => {
                toast.success('Plantilla actualizada exitosamente');
            },
            onError: (errors) => {
                toast.error('Error al actualizar la plantilla');
                console.error(errors);
            }
        });
    } else {
        form.post('/admin/campanas/plantillas-email', {
            onSuccess: () => {
                toast.success('Plantilla creada exitosamente');
            },
            onError: (errors) => {
                toast.error('Error al crear la plantilla');
                console.error(errors);
            }
        });
    }
};

// Vista previa
const togglePreview = () => {
    showPreview.value = !showPreview.value;
};

// Validar antes de enviar
const canSubmit = computed(() => {
    return form.nombre && form.asunto && form.contenido_html && !form.processing;
});

// Test de envío
const sendTestEmail = () => {
    const email = prompt('Ingresa el email de destino para la prueba:');
    if (!email) return;

    router.post(`/admin/campanas/plantillas-email/${props.plantilla?.id || 'preview'}/test`, {
        email,
        ...form.data()
    }, {
        onSuccess: () => {
            toast.success(`Email de prueba enviado a ${email}`);
        },
        onError: () => {
            toast.error('Error al enviar el email de prueba');
        }
    });
};
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head :title="isEditing ? 'Editar Plantilla Email' : 'Nueva Plantilla Email'" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Header con botones de acción -->
                <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">
                        {{ isEditing ? 'Editar Plantilla' : 'Nueva Plantilla de Email' }}
                    </h1>
                    <p class="text-muted-foreground mt-1">
                        {{ isEditing 
                            ? 'Modifica los detalles de la plantilla de email' 
                            : 'Crea una nueva plantilla para tus campañas de email' 
                        }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <router-link href="/admin/campanas/plantillas-email">
                        <Button type="button" variant="outline">
                            <ArrowLeft class="w-4 h-4 mr-2" />
                            Cancelar
                        </Button>
                    </router-link>
                    <Button
                        type="button"
                        variant="outline"
                        @click="togglePreview"
                    >
                        <Eye class="w-4 h-4 mr-2" />
                        Vista Previa
                    </Button>
                    <Button
                        v-if="isEditing"
                        type="button"
                        variant="outline"
                        @click="sendTestEmail"
                    >
                        <Mail class="w-4 h-4 mr-2" />
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

            <!-- Errores de validación -->
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

            <!-- Información básica -->
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
                                placeholder="Ej: Bienvenida nuevos usuarios"
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
                            <p class="text-sm text-muted-foreground mt-1">
                                Las plantillas inactivas no pueden ser usadas en campañas
                            </p>
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

            <!-- Contenido del Email -->
            <Card>
                <CardHeader>
                    <CardTitle>Contenido del Email</CardTitle>
                    <CardDescription>
                        Define el asunto y el contenido del correo electrónico
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div>
                        <Label htmlFor="asunto">
                            Asunto del Email <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="asunto"
                            v-model="form.asunto"
                            placeholder="Ej: {{nombre}}, bienvenido a nuestra comunidad"
                            :error="form.errors.asunto"
                        />
                        <p v-if="form.errors.asunto" class="text-sm text-destructive mt-1">
                            {{ form.errors.asunto }}
                        </p>
                        <p class="text-sm text-muted-foreground mt-1">
                            Puedes usar variables como {{nombre}}, {{email}}, etc.
                        </p>
                    </div>

                    <!-- Editor de contenido HTML -->
                    <div>
                        <Label>
                            Contenido HTML <span class="text-destructive">*</span>
                        </Label>
                        <div class="mt-2">
                            <EmailTemplateEditor
                                v-model="form.contenido_html"
                                @update:variables="handleVariablesUpdate"
                            />
                        </div>
                        <p v-if="form.errors.contenido_html" class="text-sm text-destructive mt-1">
                            {{ form.errors.contenido_html }}
                        </p>
                    </div>

                    <!-- Información sobre variables -->
                    <Alert v-if="variablesUsadas.length > 0">
                        <Info class="h-4 w-4" />
                        <AlertDescription>
                            <p class="font-medium mb-2">Variables utilizadas en esta plantilla:</p>
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
                </CardContent>
            </Card>

            <!-- Vista previa modal -->
            <div v-if="showPreview" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
                <Card class="w-full max-w-4xl max-h-[90vh] overflow-auto">
                    <CardHeader>
                        <div class="flex justify-between items-center">
                            <CardTitle>Vista Previa del Email</CardTitle>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="togglePreview"
                            >
                                ✕
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Asunto:</p>
                                <p class="font-medium">{{ form.asunto || '(Sin asunto)' }}</p>
                            </div>
                            <div class="border rounded-lg p-4 bg-white">
                                <div v-html="form.contenido_html || '<p>Sin contenido</p>'" />
                            </div>
                        </div>
                    </CardContent>
                    </Card>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>