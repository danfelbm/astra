<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Settings, ImageIcon, Type, Upload, X } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';

interface Configuracion {
    logo_display: 'logo_text' | 'logo_only';
    logo_text: string;
    logo_file: string | null;
}

interface Props {
    configuracion: Configuracion;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Configuración', href: '/admin/configuracion' },
];

// Form setup
const form = useForm({
    logo_display: props.configuracion.logo_display,
    logo_text: props.configuracion.logo_text,
    logo_file: null as File | null,
    remove_logo: false,
});

const isLoading = ref(false);
const fileInputRef = ref<HTMLInputElement>();
const previewImage = ref<string | null>(null);

// Computed para mostrar imagen actual o preview
const currentLogo = computed(() => {
    if (form.remove_logo) {
        return null;
    }
    if (previewImage.value) {
        return previewImage.value;
    }
    if (props.configuracion.logo_file) {
        return `/storage/${props.configuracion.logo_file}`;
    }
    return null;
});

// Funciones para manejar archivo
const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (file) {
        form.logo_file = file;
        form.remove_logo = false; // Si se selecciona un nuevo archivo, no eliminar
        
        // Crear preview
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImage.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removeFile = () => {
    form.logo_file = null;
    previewImage.value = null;
    form.remove_logo = true;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const triggerFileInput = () => {
    fileInputRef.value?.click();
};

const saveConfiguration = () => {
    isLoading.value = true;
    form.post(route('admin.configuracion.update'), {
        forceFormData: true, // Necesario para file uploads
        onSuccess: () => {
            // Recargar página para aplicar cambios
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        },
        onFinish: () => {
            isLoading.value = false;
        },
    });
};
</script>

<template>
    <Head title="Configuración" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Configuración del Sistema</h1>
                    <p class="text-muted-foreground">
                        Personaliza la apariencia y comportamiento del sistema
                    </p>
                </div>
            </div>

            <!-- Configuration Sections -->
            <div class="grid gap-4 md:grid-cols-1 max-w-4xl">
                <!-- Logo Configuration -->
                <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <Card class="border-0 shadow-none">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Settings class="h-5 w-5" />
                                Configuración del Logo
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Texto del Logo -->
                            <div class="space-y-3">
                                <Label for="logo_text" class="text-base font-medium">Texto del Logo</Label>
                                <Input
                                    id="logo_text"
                                    v-model="form.logo_text"
                                    placeholder="Ingresa el texto que aparecerá junto al logo"
                                    maxlength="50"
                                    class="max-w-md"
                                />
                                <p class="text-sm text-muted-foreground">
                                    Este texto aparecerá junto al logo cuando esté habilitado (máximo 50 caracteres)
                                </p>
                            </div>

                            <!-- Upload de Logo -->
                            <div class="space-y-3">
                                <Label class="text-base font-medium">Logo Personalizado (Opcional)</Label>
                                <div class="space-y-4">
                                    <!-- Input de archivo oculto -->
                                    <input
                                        ref="fileInputRef"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="handleFileSelect"
                                    />
                                    
                                    <!-- Zona de upload -->
                                    <div class="border-2 border-dashed border-muted-foreground/25 rounded-lg p-4">
                                        <div v-if="!currentLogo" class="text-center">
                                            <Upload class="mx-auto h-8 w-8 text-muted-foreground" />
                                            <p class="mt-2 text-sm text-muted-foreground">
                                                Arrastra un archivo aquí o 
                                                <Button variant="link" class="p-0 h-auto" @click="triggerFileInput">
                                                    selecciona uno
                                                </Button>
                                            </p>
                                            <p class="text-xs text-muted-foreground mt-1">
                                                PNG, JPG, SVG hasta 2MB
                                            </p>
                                        </div>
                                        
                                        <!-- Preview de imagen -->
                                        <div v-else class="relative">
                                            <img 
                                                :src="currentLogo" 
                                                alt="Logo preview"
                                                class="w-16 h-16 object-contain mx-auto rounded"
                                            />
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="mt-2 mx-auto block"
                                                @click="removeFile"
                                            >
                                                <X class="mr-2 h-4 w-4" />
                                                Quitar
                                            </Button>
                                        </div>
                                    </div>
                                    <p class="text-sm text-muted-foreground">
                                        Si no subes un logo personalizado, se usará el logo por defecto del sistema
                                    </p>
                                </div>
                            </div>

                            <!-- Visualización del Logo -->
                            <div class="space-y-4">
                                <Label class="text-base font-medium">Visualización en el Sidebar</Label>
                                <RadioGroup 
                                    v-model="form.logo_display" 
                                    class="space-y-3"
                                >
                                    <div class="flex items-center space-x-3 p-4 border rounded-lg hover:bg-muted/50 transition-colors">
                                        <RadioGroupItem value="logo_text" id="logo_text" />
                                        <div class="flex-1">
                                            <Label for="logo_text" class="flex items-center gap-2 font-medium cursor-pointer">
                                                <div class="flex items-center gap-2">
                                                    <ImageIcon class="h-4 w-4" />
                                                    <Type class="h-4 w-4" />
                                                </div>
                                                Logo + Texto
                                            </Label>
                                            <p class="text-sm text-muted-foreground mt-1">
                                                Muestra el logo junto con el texto personalizado
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-3 p-4 border rounded-lg hover:bg-muted/50 transition-colors">
                                        <RadioGroupItem value="logo_only" id="logo_only" />
                                        <div class="flex-1">
                                            <Label for="logo_only" class="flex items-center gap-2 font-medium cursor-pointer">
                                                <ImageIcon class="h-4 w-4" />
                                                Solo Logo
                                            </Label>
                                            <p class="text-sm text-muted-foreground mt-1">
                                                Muestra únicamente el logo, sin texto
                                            </p>
                                        </div>
                                    </div>
                                </RadioGroup>
                            </div>

                            <!-- Preview Section -->
                            <div class="border-t pt-4">
                                <Label class="text-base font-medium mb-3 block">Vista Previa</Label>
                                <div class="bg-muted/50 rounded-lg p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex aspect-square size-8 items-center justify-center rounded-md text-sidebar-primary-foreground">
                                            <img 
                                                v-if="currentLogo" 
                                                :src="currentLogo" 
                                                alt="Logo" 
                                                class="object-contain"
                                            />
                                            <svg v-else class="size-5 fill-current text-white dark:text-black" viewBox="0 0 24 24">
                                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                                            </svg>
                                        </div>
                                        <div v-if="form.logo_display === 'logo_text'" class="grid flex-1 text-left text-sm">
                                            <span class="mb-0.5 truncate font-semibold leading-none">{{ form.logo_text }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div class="flex justify-end pt-4 border-t">
                                <Button 
                                    @click="saveConfiguration"
                                    :disabled="isLoading || !form.isDirty"
                                    class="min-w-32"
                                >
                                    <Settings class="mr-2 h-4 w-4" />
                                    {{ isLoading ? 'Guardando...' : 'Guardar Cambios' }}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Future Configuration Sections Placeholder -->
                <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border border-dashed">
                    <Card class="border-0 shadow-none">
                        <CardContent class="pt-6">
                            <div class="text-center py-8">
                                <Settings class="mx-auto h-12 w-12 text-muted-foreground/50" />
                                <h3 class="mt-4 text-lg font-medium text-muted-foreground">Más opciones próximamente</h3>
                                <p class="text-sm text-muted-foreground mt-2">
                                    Esta sección se expandirá con más opciones de configuración en futuras actualizaciones.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>