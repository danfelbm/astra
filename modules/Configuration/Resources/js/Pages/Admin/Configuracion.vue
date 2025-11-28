<script setup lang="ts">
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@modules/Core/Resources/js/components/ui/radio-group';
import { type BreadcrumbItemType } from '@modules/Core/Resources/js/types';
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { Head, router, useForm } from '@inertiajs/vue3';
import { Settings, ImageIcon, Type, Upload, X, Users, AlertCircle, ToggleLeft, ToggleRight, ChevronUp, ChevronDown, Trash2, Plus, Vote, FileText, Calendar, CheckCircle, Mail, Phone, MapPin, Clock, Download, Eye, Edit, Trash, Minus, ArrowRight, ArrowLeft } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import { Textarea } from '@modules/Core/Resources/js/components/ui/textarea';
import { Switch } from '@modules/Core/Resources/js/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@modules/Core/Resources/js/components/ui/select';
import RichTextEditor from '@modules/Core/Resources/js/components/ui/RichTextEditor.vue';

interface Configuracion {
    logo_display: 'logo_text' | 'logo_only';
    logo_text: string;
    logo_file: string | null;
}

interface ConfiguracionCandidaturas {
    bloqueo_activo: boolean;
    bloqueo_titulo: string;
    bloqueo_mensaje: string;
}

interface ConfiguracionLogin {
    mensaje_html: string;
}

interface DashboardCard {
    id: string;
    enabled: boolean;
    order: number;
    color: string;
    icon: string;
    title: string;
    description: string;
    buttonText: string;
    buttonLink: string;
}

interface ConfiguracionDashboard {
    hero_html: string;
    cards: DashboardCard[];
}

interface WelcomeLink {
    id: string;
    enabled: boolean;
    order: number;
    text: string;
    url: string;
    visibility: 'always' | 'logged_in' | 'logged_out';
    is_primary: boolean;
}

interface ConfiguracionWelcome {
    header: {
        logo_url: string;
        logo_text: string;
    };
    hero: {
        title_html: string;
        description_html: string;
    };
    links: WelcomeLink[];
    background_url: string;
}

interface Props {
    configuracion: Configuracion;
    configuracionCandidaturas: ConfiguracionCandidaturas;
    configuracionLogin: ConfiguracionLogin;
    configuracionDashboard: ConfiguracionDashboard;
    configuracionWelcome: ConfiguracionWelcome;
    canEdit?: boolean;
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
const isLoadingCandidaturas = ref(false);
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

// Form para candidaturas
const formCandidaturas = useForm({
    bloqueo_activo: props.configuracionCandidaturas.bloqueo_activo,
    bloqueo_titulo: props.configuracionCandidaturas.bloqueo_titulo,
    bloqueo_mensaje: props.configuracionCandidaturas.bloqueo_mensaje,
});

const saveCandidaturasConfiguration = () => {
    isLoadingCandidaturas.value = true;
    formCandidaturas.post(route('admin.configuracion.update.candidaturas'), {
        onFinish: () => {
            isLoadingCandidaturas.value = false;
        },
    });
};

// Form para mensaje de login
const formLogin = useForm({
    mensaje_html: props.configuracionLogin.mensaje_html,
});

const isLoadingLogin = ref(false);

const saveLoginConfiguration = () => {
    isLoadingLogin.value = true;
    formLogin.post(route('admin.configuracion.update.login'), {
        onFinish: () => {
            isLoadingLogin.value = false;
        },
    });
};

// Form para dashboard de usuarios
const formDashboard = useForm({
    hero_html: props.configuracionDashboard.hero_html,
    cards: props.configuracionDashboard.cards || [],
});

const isLoadingDashboard = ref(false);

// Catálogo de íconos disponibles
const availableIcons = [
    { value: 'Vote', label: 'Vote (Votación)' },
    { value: 'Users', label: 'Users (Usuarios)' },
    { value: 'FileText', label: 'FileText (Documento)' },
    { value: 'Calendar', label: 'Calendar (Calendario)' },
    { value: 'CheckCircle', label: 'CheckCircle (Check)' },
    { value: 'AlertCircle', label: 'AlertCircle (Alerta)' },
    { value: 'Settings', label: 'Settings (Configuración)' },
    { value: 'Mail', label: 'Mail (Correo)' },
    { value: 'Phone', label: 'Phone (Teléfono)' },
    { value: 'MapPin', label: 'MapPin (Ubicación)' },
    { value: 'Clock', label: 'Clock (Reloj)' },
    { value: 'Upload', label: 'Upload (Subir)' },
    { value: 'Download', label: 'Download (Descargar)' },
    { value: 'Eye', label: 'Eye (Ver)' },
    { value: 'Edit', label: 'Edit (Editar)' },
    { value: 'Trash', label: 'Trash (Eliminar)' },
    { value: 'Plus', label: 'Plus (Más)' },
    { value: 'Minus', label: 'Minus (Menos)' },
    { value: 'ArrowRight', label: 'ArrowRight (Flecha Der)' },
    { value: 'ArrowLeft', label: 'ArrowLeft (Flecha Izq)' }
];

// Catálogo de colores disponibles
const availableColors = [
    { value: 'blue', label: '🔵 Azul' },
    { value: 'green', label: '🟢 Verde' },
    { value: 'red', label: '🔴 Rojo' },
    { value: 'purple', label: '🟣 Morado' },
    { value: 'orange', label: '🟠 Naranja' },
    { value: 'yellow', label: '🟡 Amarillo' },
    { value: 'pink', label: '🩷 Rosa' },
    { value: 'indigo', label: '🔵 Índigo' },
    { value: 'teal', label: '🩵 Turquesa' },
    { value: 'gray', label: '⚫ Gris' }
];

const addCard = () => {
    formDashboard.cards.push({
        id: crypto.randomUUID(),
        enabled: true,
        order: formDashboard.cards.length,
        color: 'blue',
        icon: 'Vote',
        title: '',
        description: '',
        buttonText: '',
        buttonLink: ''
    });
};

const removeCard = (index: number) => {
    formDashboard.cards.splice(index, 1);
    // Reordenar los índices
    formDashboard.cards.forEach((card, idx) => {
        card.order = idx;
    });
};

const moveCardUp = (index: number) => {
    if (index === 0) return;
    const card = formDashboard.cards[index];
    formDashboard.cards.splice(index, 1);
    formDashboard.cards.splice(index - 1, 0, card);
    // Actualizar orden
    formDashboard.cards.forEach((c, idx) => {
        c.order = idx;
    });
};

const moveCardDown = (index: number) => {
    if (index === formDashboard.cards.length - 1) return;
    const card = formDashboard.cards[index];
    formDashboard.cards.splice(index, 1);
    formDashboard.cards.splice(index + 1, 0, card);
    // Actualizar orden
    formDashboard.cards.forEach((c, idx) => {
        c.order = idx;
    });
};

const saveDashboardConfiguration = () => {
    isLoadingDashboard.value = true;
    formDashboard.post(route('admin.configuracion.update.dashboard-user'), {
        onFinish: () => {
            isLoadingDashboard.value = false;
        },
    });
};

// Form para Welcome page
const formWelcome = useForm({
    header: {
        logo_file: null as File | null,
        remove_logo: false,
        logo_text: props.configuracionWelcome.header.logo_text
    },
    hero: {
        title_html: props.configuracionWelcome.hero.title_html,
        description_html: props.configuracionWelcome.hero.description_html
    },
    links: (props.configuracionWelcome.links || []).map(link => ({
        ...link,
        enabled: !!link.enabled,
        is_primary: !!link.is_primary
    })),
    background_file: null as File | null,
    remove_background: false,
    background_url: props.configuracionWelcome.background_url
});

const isLoadingWelcome = ref(false);

// Logo refs
const fileInputWelcomeRef = ref<HTMLInputElement>();
const previewWelcomeImage = ref<string | null>(null);

// Background refs
const fileInputBackgroundRef = ref<HTMLInputElement>();
const previewBackgroundImage = ref<string | null>(null);

// Computed para mostrar logo actual o preview
const currentWelcomeLogo = computed(() => {
    if (formWelcome.header.remove_logo) {
        return null;
    }
    if (previewWelcomeImage.value) {
        return previewWelcomeImage.value;
    }
    if (props.configuracionWelcome.header.logo_url) {
        return props.configuracionWelcome.header.logo_url.startsWith('/storage/')
            ? props.configuracionWelcome.header.logo_url
            : props.configuracionWelcome.header.logo_url;
    }
    return null;
});

// Computed para mostrar background actual o preview
const currentWelcomeBackground = computed(() => {
    if (formWelcome.remove_background) {
        return null;
    }
    if (previewBackgroundImage.value) {
        return previewBackgroundImage.value;
    }
    if (props.configuracionWelcome.background_url) {
        return props.configuracionWelcome.background_url.startsWith('/storage/')
            ? props.configuracionWelcome.background_url
            : props.configuracionWelcome.background_url;
    }
    return null;
});

// Funciones para manejar logo
const handleWelcomeFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        formWelcome.header.logo_file = file;
        formWelcome.header.remove_logo = false;

        const reader = new FileReader();
        reader.onload = (e) => {
            previewWelcomeImage.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removeWelcomeFile = () => {
    formWelcome.header.logo_file = null;
    previewWelcomeImage.value = null;
    formWelcome.header.remove_logo = true;
    if (fileInputWelcomeRef.value) {
        fileInputWelcomeRef.value.value = '';
    }
};

const triggerWelcomeFileInput = () => {
    fileInputWelcomeRef.value?.click();
};

// Funciones para manejar background
const handleBackgroundFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        formWelcome.background_file = file;
        formWelcome.remove_background = false;

        const reader = new FileReader();
        reader.onload = (e) => {
            previewBackgroundImage.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removeBackgroundFile = () => {
    formWelcome.background_file = null;
    previewBackgroundImage.value = null;
    formWelcome.remove_background = true;
    if (fileInputBackgroundRef.value) {
        fileInputBackgroundRef.value.value = '';
    }
};

const triggerBackgroundFileInput = () => {
    fileInputBackgroundRef.value?.click();
};

// Opciones de visibilidad
const visibilityOptions = [
    { value: 'always', label: '👁️ Siempre visible' },
    { value: 'logged_in', label: '🔐 Solo autenticados' },
    { value: 'logged_out', label: '🔓 Solo no autenticados' }
];

const addLink = () => {
    formWelcome.links.push({
        id: crypto.randomUUID(),
        enabled: true,
        order: formWelcome.links.length,
        text: '',
        url: '',
        visibility: 'always',
        is_primary: false
    });
};

const removeLink = (index: number) => {
    formWelcome.links.splice(index, 1);
    // Reordenar los índices
    formWelcome.links.forEach((link, idx) => {
        link.order = idx;
    });
};

const moveLinkUp = (index: number) => {
    if (index === 0) return;
    const link = formWelcome.links[index];
    formWelcome.links.splice(index, 1);
    formWelcome.links.splice(index - 1, 0, link);
    // Actualizar orden
    formWelcome.links.forEach((l, idx) => {
        l.order = idx;
    });
};

const moveLinkDown = (index: number) => {
    if (index === formWelcome.links.length - 1) return;
    const link = formWelcome.links[index];
    formWelcome.links.splice(index, 1);
    formWelcome.links.splice(index + 1, 0, link);
    // Actualizar orden
    formWelcome.links.forEach((l, idx) => {
        l.order = idx;
    });
};

const saveWelcomeConfiguration = () => {
    isLoadingWelcome.value = true;

    // Si hay archivo (logo o background), usar FormData
    const hasFile = formWelcome.header.logo_file !== null || formWelcome.background_file !== null;

    formWelcome.post(route('admin.configuracion.update.welcome'), {
        forceFormData: hasFile,
        onFinish: () => {
            isLoadingWelcome.value = false;
        },
    });
};
</script>

<template>
    <Head title="Configuración" />

    <AdminLayout :breadcrumbs="breadcrumbs">
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
                                    :disabled="!props.canEdit"
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
                                                <Button variant="link" class="p-0 h-auto" @click="triggerFileInput" :disabled="!props.canEdit">
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
                                                v-if="props.canEdit"
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
                                    :disabled="!props.canEdit"
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
                                    v-if="props.canEdit"
                                    @click="saveConfiguration"
                                    :disabled="isLoading || !form.isDirty"
                                    class="min-w-32"
                                >
                                    <Settings class="mr-2 h-4 w-4" />
                                    {{ isLoading ? 'Guardando...' : 'Guardar Cambios' }}
                                </Button>
                                <div v-else class="text-sm text-muted-foreground">
                                    No tienes permisos para editar la configuración
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Control de Candidaturas -->
                <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <Card class="border-0 shadow-none">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Users class="h-5 w-5" />
                                Control de Candidaturas
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Toggle de Bloqueo -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="space-y-1">
                                        <Label class="text-base font-medium">Bloqueo de Candidaturas</Label>
                                        <p class="text-sm text-muted-foreground">
                                            Cuando está activo, los usuarios no podrán crear nuevas candidaturas ni editar las que están en estado borrador
                                        </p>
                                    </div>
                                    <Button
                                        v-if="props.canEdit"
                                        @click="formCandidaturas.bloqueo_activo = !formCandidaturas.bloqueo_activo"
                                        :variant="formCandidaturas.bloqueo_activo ? 'default' : 'outline'"
                                        size="sm"
                                        class="min-w-24"
                                    >
                                        <component 
                                            :is="formCandidaturas.bloqueo_activo ? ToggleRight : ToggleLeft" 
                                            class="mr-2 h-4 w-4" 
                                        />
                                        {{ formCandidaturas.bloqueo_activo ? 'Activo' : 'Inactivo' }}
                                    </Button>
                                    <Badge v-else :variant="formCandidaturas.bloqueo_activo ? 'default' : 'secondary'">
                                        <component 
                                            :is="formCandidaturas.bloqueo_activo ? ToggleRight : ToggleLeft" 
                                            class="mr-2 h-4 w-4" 
                                        />
                                        {{ formCandidaturas.bloqueo_activo ? 'Activo' : 'Inactivo' }}
                                    </Badge>
                                </div>
                                
                                <!-- Indicador de estado -->
                                <div v-if="formCandidaturas.bloqueo_activo" 
                                     class="flex items-center gap-2 p-3 bg-yellow-50 dark:bg-yellow-950/20 rounded-lg">
                                    <AlertCircle class="h-5 w-5 text-yellow-600" />
                                    <span class="text-sm text-yellow-700 dark:text-yellow-400">
                                        El sistema de candidaturas está bloqueado. Los usuarios verán el mensaje configurado abajo.
                                    </span>
                                </div>
                            </div>

                            <!-- Título del Mensaje -->
                            <div class="space-y-3">
                                <Label for="bloqueo_titulo" class="text-base font-medium">
                                    Título del Mensaje de Bloqueo
                                </Label>
                                <Input
                                    id="bloqueo_titulo"
                                    v-model="formCandidaturas.bloqueo_titulo"
                                    placeholder="Ej: Sistema de Candidaturas Temporalmente Cerrado"
                                    maxlength="255"
                                    class="max-w-full"
                                    :disabled="!props.canEdit"
                                />
                                <p class="text-sm text-muted-foreground">
                                    Este título se mostrará cuando los usuarios intenten crear o editar candidaturas
                                </p>
                            </div>

                            <!-- Mensaje Detallado -->
                            <div class="space-y-3">
                                <Label for="bloqueo_mensaje" class="text-base font-medium">
                                    Mensaje Detallado
                                </Label>
                                <textarea
                                    id="bloqueo_mensaje"
                                    v-model="formCandidaturas.bloqueo_mensaje"
                                    placeholder="Ingresa el mensaje que verán los usuarios..."
                                    maxlength="1000"
                                    rows="4"
                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="!props.canEdit"
                                />
                                <p class="text-sm text-muted-foreground">
                                    Explica por qué el sistema está bloqueado y cuándo estará disponible (máximo 1000 caracteres)
                                </p>
                            </div>

                            <!-- Vista Previa -->
                            <div v-if="formCandidaturas.bloqueo_activo" class="border-t pt-4">
                                <Label class="text-base font-medium mb-3 block">Vista Previa del Mensaje</Label>
                                <div class="bg-muted/50 rounded-lg p-4">
                                    <div class="flex items-start gap-3">
                                        <AlertCircle class="h-6 w-6 text-yellow-600 flex-shrink-0 mt-0.5" />
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-lg mb-2">
                                                {{ formCandidaturas.bloqueo_titulo || 'Sin título' }}
                                            </h3>
                                            <p class="text-sm text-muted-foreground whitespace-pre-wrap">
                                                {{ formCandidaturas.bloqueo_mensaje || 'Sin mensaje' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Información Adicional -->
                            <div class="bg-blue-50 dark:bg-blue-950/20 rounded-lg p-4">
                                <h4 class="font-medium text-sm mb-2 text-blue-800 dark:text-blue-200">
                                    Excepciones al Bloqueo:
                                </h4>
                                <ul class="space-y-1 text-sm text-blue-700 dark:text-blue-300">
                                    <li class="flex items-start gap-2">
                                        <span class="text-blue-500 mt-0.5">•</span>
                                        <span>Las candidaturas en estado PENDIENTE, APROBADO o RECHAZADO no se ven afectadas</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-blue-500 mt-0.5">•</span>
                                        <span>Las candidaturas marcadas con "subsanar = 1" pueden editarse aunque esté el bloqueo activo</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-blue-500 mt-0.5">•</span>
                                        <span>Los administradores pueden marcar candidaturas individuales para subsanación desde el panel administrativo</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Save Button -->
                            <div class="flex justify-end pt-4 border-t">
                                <Button 
                                    v-if="props.canEdit"
                                    @click="saveCandidaturasConfiguration"
                                    :disabled="isLoadingCandidaturas || !formCandidaturas.isDirty"
                                    class="min-w-32"
                                >
                                    <Settings class="mr-2 h-4 w-4" />
                                    {{ isLoadingCandidaturas ? 'Guardando...' : 'Guardar Cambios' }}
                                </Button>
                                <div v-else class="text-sm text-muted-foreground">
                                    No tienes permisos para editar la configuración
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Mensaje de Ayuda en Login -->
                <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <Card class="border-0 shadow-none">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <AlertCircle class="h-5 w-5" />
                                Mensaje de Ayuda en Login
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Mensaje HTML -->
                            <div class="space-y-3">
                                <Label for="mensaje_login" class="text-base font-medium">
                                    Mensaje HTML de Ayuda
                                </Label>
                                <textarea
                                    id="mensaje_login"
                                    v-model="formLogin.mensaje_html"
                                    placeholder="Ingresa el mensaje HTML que verán los usuarios..."
                                    maxlength="2000"
                                    rows="5"
                                    class="flex min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="!props.canEdit"
                                />
                                <div class="flex items-center justify-between">
                                    <p class="text-sm text-muted-foreground">
                                        Puedes usar HTML básico como &lt;b&gt;, &lt;strong&gt;, &lt;a&gt;, etc. (máximo 2000 caracteres)
                                    </p>
                                    <span class="text-xs text-muted-foreground">
                                        {{ formLogin.mensaje_html.length }}/2000
                                    </span>
                                </div>
                            </div>

                            <!-- Vista Previa -->
                            <div class="border-t pt-4">
                                <Label class="text-base font-medium mb-3 block">Vista Previa del Mensaje</Label>
                                <div class="bg-muted/50 rounded-lg p-4">
                                    <p
                                        class="text-xs text-muted-foreground"
                                        v-html="formLogin.mensaje_html || 'Sin mensaje'"
                                    />
                                </div>
                            </div>

                            <!-- Información Adicional -->
                            <div class="bg-blue-50 dark:bg-blue-950/20 rounded-lg p-4">
                                <h4 class="font-medium text-sm mb-2 text-blue-800 dark:text-blue-200">
                                    Dónde se muestra este mensaje:
                                </h4>
                                <ul class="space-y-1 text-sm text-blue-700 dark:text-blue-300">
                                    <li class="flex items-start gap-2">
                                        <span class="text-blue-500 mt-0.5">•</span>
                                        <span>Aparece en la parte inferior de la página de inicio de sesión (Login/OTP)</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-blue-500 mt-0.5">•</span>
                                        <span>Es visible tanto para usuarios autenticados como no autenticados</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-blue-500 mt-0.5">•</span>
                                        <span>Ideal para información de contacto, soporte o avisos importantes</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Save Button -->
                            <div class="flex justify-end pt-4 border-t">
                                <Button
                                    v-if="props.canEdit"
                                    @click="saveLoginConfiguration"
                                    :disabled="isLoadingLogin || !formLogin.isDirty"
                                    class="min-w-32"
                                >
                                    <Settings class="mr-2 h-4 w-4" />
                                    {{ isLoadingLogin ? 'Guardando...' : 'Guardar Cambios' }}
                                </Button>
                                <div v-else class="text-sm text-muted-foreground">
                                    No tienes permisos para editar la configuración
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Personalización Dashboard de Usuarios -->
                <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <Card class="border-0 shadow-none">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Settings class="h-5 w-5" />
                                Personalización Dashboard de Usuarios
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Hero HTML -->
                            <div class="space-y-3">
                                <Label for="hero_html" class="text-base font-medium">
                                    Contenido del Hero (HTML)
                                </Label>
                                <RichTextEditor
                                    v-model="formDashboard.hero_html"
                                    placeholder="Ingresa el contenido HTML del hero..."
                                    :rows="6"
                                />
                                <div class="flex items-center justify-between">
                                    <p class="text-sm text-muted-foreground">
                                        Puedes usar HTML con clases Tailwind (máximo 5000 caracteres)
                                    </p>
                                    <span class="text-xs text-muted-foreground">
                                        {{ formDashboard.hero_html.length }}/5000
                                    </span>
                                </div>
                            </div>

                            <!-- Cards Builder -->
                            <div class="space-y-4 border-t pt-6">
                                <div class="flex items-center justify-between">
                                    <Label class="text-base font-medium">Cards de Navegación</Label>
                                    <Button
                                        v-if="props.canEdit"
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="addCard"
                                    >
                                        <Plus class="mr-2 h-4 w-4" />
                                        Agregar Card
                                    </Button>
                                </div>

                                <div class="space-y-4">
                                    <div
                                        v-for="(card, index) in formDashboard.cards"
                                        :key="card.id"
                                        class="border rounded-lg p-4 space-y-4"
                                    >
                                        <!-- Header del Card -->
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-muted-foreground">Card #{{ index + 1 }}</span>
                                                <Switch
                                                    :model-value="card.enabled"
                                                    @update:model-value="(val) => card.enabled = val"
                                                    :disabled="!props.canEdit"
                                                />
                                                <span class="text-xs text-muted-foreground">
                                                    {{ card.enabled ? 'Habilitado' : 'Deshabilitado' }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <Button
                                                    v-if="props.canEdit"
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="moveCardUp(index)"
                                                    :disabled="index === 0"
                                                >
                                                    <ChevronUp class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    v-if="props.canEdit"
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="moveCardDown(index)"
                                                    :disabled="index === formDashboard.cards.length - 1"
                                                >
                                                    <ChevronDown class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    v-if="props.canEdit"
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="removeCard(index)"
                                                    class="text-red-600 hover:text-red-700 hover:bg-red-50"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>

                                        <!-- Configuración del Card -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <!-- Color -->
                                            <div class="space-y-2">
                                                <Label>Color</Label>
                                                <Select v-model="card.color" :disabled="!props.canEdit">
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Selecciona un color" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem
                                                            v-for="color in availableColors"
                                                            :key="color.value"
                                                            :value="color.value"
                                                        >
                                                            {{ color.label }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <!-- Ícono -->
                                            <div class="space-y-2">
                                                <Label>Ícono</Label>
                                                <Select v-model="card.icon" :disabled="!props.canEdit">
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Selecciona un ícono" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem
                                                            v-for="icon in availableIcons"
                                                            :key="icon.value"
                                                            :value="icon.value"
                                                        >
                                                            {{ icon.label }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <!-- Título -->
                                            <div class="space-y-2">
                                                <Label>Título</Label>
                                                <Input
                                                    v-model="card.title"
                                                    placeholder="Ej: Participar en Votaciones"
                                                    maxlength="100"
                                                    :disabled="!props.canEdit"
                                                />
                                            </div>

                                            <!-- Texto del Botón -->
                                            <div class="space-y-2">
                                                <Label>Texto del Botón</Label>
                                                <Input
                                                    v-model="card.buttonText"
                                                    placeholder="Ej: Ver Votaciones"
                                                    maxlength="50"
                                                    :disabled="!props.canEdit"
                                                />
                                            </div>

                                            <!-- Descripción (full width) -->
                                            <div class="space-y-2 md:col-span-2">
                                                <Label>Descripción</Label>
                                                <Textarea
                                                    v-model="card.description"
                                                    placeholder="Descripción del card..."
                                                    maxlength="300"
                                                    rows="2"
                                                    :disabled="!props.canEdit"
                                                />
                                            </div>

                                            <!-- Link del Botón (full width) -->
                                            <div class="space-y-2 md:col-span-2">
                                                <Label>Enlace del Botón</Label>
                                                <Input
                                                    v-model="card.buttonLink"
                                                    placeholder="Ej: /miembro/votaciones"
                                                    maxlength="500"
                                                    :disabled="!props.canEdit"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="formDashboard.cards.length === 0" class="text-center py-8 text-muted-foreground">
                                        <p>No hay cards configurados. Haz clic en "Agregar Card" para crear uno.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div class="flex justify-end pt-4 border-t">
                                <Button
                                    v-if="props.canEdit"
                                    @click="saveDashboardConfiguration"
                                    :disabled="isLoadingDashboard || !formDashboard.isDirty"
                                    class="min-w-32"
                                >
                                    <Settings class="mr-2 h-4 w-4" />
                                    {{ isLoadingDashboard ? 'Guardando...' : 'Guardar Cambios' }}
                                </Button>
                                <div v-else class="text-sm text-muted-foreground">
                                    No tienes permisos para editar la configuración
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Personalización Página Principal (Welcome) -->
                <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <Card class="border-0 shadow-none">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Settings class="h-5 w-5" />
                                Personalización Página Principal
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Header -->
                            <div class="space-y-4 pb-4 border-b">
                                <h3 class="text-base font-medium">Logo y Header</h3>

                                <!-- Upload de Logo Welcome -->
                                <div class="space-y-3">
                                    <Label class="text-base font-medium">Logo Personalizado</Label>
                                    <div class="space-y-4">
                                        <!-- Input de archivo oculto -->
                                        <input
                                            ref="fileInputWelcomeRef"
                                            type="file"
                                            accept="image/*"
                                            class="hidden"
                                            @change="handleWelcomeFileSelect"
                                        />

                                        <!-- Zona de upload -->
                                        <div class="border-2 border-dashed border-muted-foreground/25 rounded-lg p-4">
                                            <div v-if="!currentWelcomeLogo" class="text-center">
                                                <Upload class="mx-auto h-8 w-8 text-muted-foreground" />
                                                <p class="mt-2 text-sm text-muted-foreground">
                                                    Arrastra un archivo aquí o
                                                    <Button variant="link" class="p-0 h-auto" @click="triggerWelcomeFileInput" :disabled="!props.canEdit">
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
                                                    :src="currentWelcomeLogo"
                                                    alt="Logo preview"
                                                    class="w-32 h-16 object-contain mx-auto rounded"
                                                />
                                                <Button
                                                    v-if="props.canEdit"
                                                    variant="outline"
                                                    size="sm"
                                                    class="mt-2 mx-auto block"
                                                    @click="removeWelcomeFile"
                                                >
                                                    <X class="mr-2 h-4 w-4" />
                                                    Quitar
                                                </Button>
                                            </div>
                                        </div>
                                        <p class="text-sm text-muted-foreground">
                                            Este logo se mostrará en el header de la página principal
                                        </p>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label>Texto del Logo</Label>
                                    <Input v-model="formWelcome.header.logo_text" placeholder="Colombia Humana" maxlength="100" :disabled="!props.canEdit" />
                                </div>
                            </div>

                            <!-- Hero -->
                            <div class="space-y-4 pb-4 border-b">
                                <h3 class="text-base font-medium">Hero Principal</h3>
                                <div class="space-y-3">
                                    <Label>Título Principal (HTML)</Label>
                                    <RichTextEditor v-model="formWelcome.hero.title_html" placeholder="<h1>Ágora</h1>" :rows="4" />
                                    <span class="text-xs text-muted-foreground">{{ formWelcome.hero.title_html.length }}/1000</span>
                                </div>
                                <div class="space-y-3">
                                    <Label>Descripción (HTML)</Label>
                                    <RichTextEditor v-model="formWelcome.hero.description_html" placeholder="Sistema de Votaciones..." :rows="4" />
                                    <span class="text-xs text-muted-foreground">{{ formWelcome.hero.description_html.length }}/2000</span>
                                </div>
                            </div>

                            <!-- Links Builder -->
                            <div class="space-y-4 pb-4 border-b">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-base font-medium">Enlaces de Navegación</h3>
                                    <Button v-if="props.canEdit" type="button" variant="outline" size="sm" @click="addLink">
                                        <Plus class="mr-2 h-4 w-4" />
                                        Agregar Enlace
                                    </Button>
                                </div>

                                <div class="space-y-4">
                                    <div v-for="(link, index) in formWelcome.links" :key="link.id" class="border rounded-lg p-4 space-y-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-muted-foreground">Enlace #{{ index + 1 }}</span>
                                                <Switch
                                                    :model-value="link.enabled"
                                                    @update:model-value="(val) => link.enabled = val"
                                                    :disabled="!props.canEdit"
                                                />
                                                <span class="text-xs text-muted-foreground">{{ link.enabled ? 'Habilitado' : 'Deshabilitado' }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <Button v-if="props.canEdit" type="button" variant="ghost" size="sm" @click="moveLinkUp(index)" :disabled="index === 0">
                                                    <ChevronUp class="h-4 w-4" />
                                                </Button>
                                                <Button v-if="props.canEdit" type="button" variant="ghost" size="sm" @click="moveLinkDown(index)" :disabled="index === formWelcome.links.length - 1">
                                                    <ChevronDown class="h-4 w-4" />
                                                </Button>
                                                <Button v-if="props.canEdit" type="button" variant="ghost" size="sm" @click="removeLink(index)" class="text-red-600">
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="space-y-2">
                                                <Label>Texto del Enlace</Label>
                                                <Input v-model="link.text" placeholder="Ver Asambleas" maxlength="100" :disabled="!props.canEdit" />
                                            </div>
                                            <div class="space-y-2">
                                                <Label>URL</Label>
                                                <Input v-model="link.url" placeholder="/miembro/asambleas" maxlength="500" :disabled="!props.canEdit" />
                                            </div>
                                            <div class="space-y-2">
                                                <Label>Visibilidad</Label>
                                                <Select v-model="link.visibility" :disabled="!props.canEdit">
                                                    <SelectTrigger>
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem v-for="option in visibilityOptions" :key="option.value" :value="option.value">
                                                            {{ option.label }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div class="space-y-2 flex items-end">
                                                <div class="flex items-center gap-2">
                                                    <Switch
                                                        :model-value="link.is_primary"
                                                        @update:model-value="(val) => link.is_primary = val"
                                                        :disabled="!props.canEdit"
                                                    />
                                                    <Label>Enlace Principal (Negrita)</Label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="formWelcome.links.length === 0" class="text-center py-8 text-muted-foreground">
                                        <p>No hay enlaces configurados. Haz clic en "Agregar Enlace" para crear uno.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Background -->
                            <div class="space-y-3">
                                <Label class="text-base font-medium">Imagen de Fondo Personalizada</Label>
                                <div class="space-y-4">
                                    <!-- Input de archivo oculto -->
                                    <input
                                        ref="fileInputBackgroundRef"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="handleBackgroundFileSelect"
                                    />

                                    <!-- Zona de upload -->
                                    <div class="border-2 border-dashed border-muted-foreground/25 rounded-lg p-4">
                                        <div v-if="!currentWelcomeBackground" class="text-center">
                                            <Upload class="mx-auto h-8 w-8 text-muted-foreground" />
                                            <p class="mt-2 text-sm text-muted-foreground">
                                                Arrastra un archivo aquí o
                                                <Button variant="link" class="p-0 h-auto" @click="triggerBackgroundFileInput" :disabled="!props.canEdit">
                                                    selecciona uno
                                                </Button>
                                            </p>
                                            <p class="text-xs text-muted-foreground mt-1">
                                                PNG, JPG hasta 2MB
                                            </p>
                                        </div>

                                        <!-- Preview de imagen -->
                                        <div v-else class="relative">
                                            <img
                                                :src="currentWelcomeBackground"
                                                alt="Background preview"
                                                class="w-full max-h-48 object-cover mx-auto rounded"
                                            />
                                            <Button
                                                v-if="props.canEdit"
                                                variant="outline"
                                                size="sm"
                                                class="mt-2 mx-auto block"
                                                @click="removeBackgroundFile"
                                            >
                                                <X class="mr-2 h-4 w-4" />
                                                Quitar
                                            </Button>
                                        </div>
                                    </div>
                                    <p class="text-sm text-muted-foreground">
                                        Esta imagen se mostrará como fondo de la página principal
                                    </p>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div class="flex justify-end pt-4 border-t">
                                <Button v-if="props.canEdit" @click="saveWelcomeConfiguration" :disabled="isLoadingWelcome || !formWelcome.isDirty" class="min-w-32">
                                    <Settings class="mr-2 h-4 w-4" />
                                    {{ isLoadingWelcome ? 'Guardando...' : 'Guardar Cambios' }}
                                </Button>
                                <div v-else class="text-sm text-muted-foreground">
                                    No tienes permisos para editar la configuración
                                </div>
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
    </AdminLayout>
</template>