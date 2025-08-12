<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle, ArrowLeft, Calendar, Hash, MapPin, Copy } from 'lucide-vue-next';
import { ref } from 'vue';

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

interface Voto {
    id: number;
    token_unico: string;
    respuestas: Record<string, any>;
    created_at: string;
    ip_address: string;
}

interface Props {
    votacion: Votacion;
    voto: Voto;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Mis Votaciones', href: '/votaciones' },
    { title: 'Mi Voto', href: '#' },
];

const tokenCopied = ref(false);

// Función para volver
const goBack = () => {
    router.get('/votaciones');
};

// Función para copiar token
const copyToken = async () => {
    try {
        await navigator.clipboard.writeText(props.voto.token_unico);
        tokenCopied.value = true;
        setTimeout(() => {
            tokenCopied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Error al copiar token:', err);
    }
};

// Función para verificar token públicamente
const verificarToken = () => {
    const url = `/verificar-token/${encodeURIComponent(props.voto.token_unico)}`;
    window.open(url, '_blank');
};

// Formatear fechas
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};

// Función para mostrar valor de respuesta de forma legible
const formatRespuesta = (field: FormField, valor: any) => {
    if (!valor && valor !== 0) return 'Sin respuesta';
    
    if (field.type === 'checkbox') {
        if (Array.isArray(valor) && valor.length > 0) {
            return valor.join(', ');
        }
        return 'Sin respuesta';
    }
    
    return valor.toString();
};
</script>

<template>
    <Head :title="`Mi Voto: ${votacion.titulo}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold flex items-center gap-2">
                        <CheckCircle class="h-8 w-8 text-green-600" />
                        Mi Voto
                    </h1>
                    <p class="text-muted-foreground">
                        Resumen de tu participación en esta votación
                    </p>
                </div>
                <Button variant="outline" @click="goBack">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver
                </Button>
            </div>

            <!-- Info de la votación -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ votacion.titulo }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <p v-if="votacion.descripcion" class="text-muted-foreground">
                            {{ votacion.descripcion }}
                        </p>
                        
                        <div class="flex flex-wrap gap-4 text-sm">
                            <div class="flex items-center gap-2">
                                <Badge variant="outline">{{ votacion.categoria.nombre }}</Badge>
                            </div>
                            <div class="flex items-center gap-2 text-muted-foreground">
                                <Calendar class="h-4 w-4" />
                                {{ formatDate(votacion.fecha_inicio) }} - {{ formatDate(votacion.fecha_fin) }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Información del voto -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Hash class="h-5 w-5" />
                        Información del Voto
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Fecha y Hora</Label>
                                <p class="text-lg">{{ formatDate(voto.created_at) }}</p>
                            </div>
                            
                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">ID del Voto</Label>
                                <p class="text-lg font-mono">#{{ voto.id }}</p>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">IP Address</Label>
                                <p class="text-lg font-mono">{{ voto.ip_address }}</p>
                            </div>
                            
                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Token de Verificación Firmado</Label>
                                <div class="flex items-center gap-2 mb-2">
                                    <p class="text-xs font-mono bg-muted p-2 rounded flex-1 break-all">
                                        {{ voto.token_unico }}
                                    </p>
                                    <Button 
                                        variant="outline" 
                                        size="sm" 
                                        @click="copyToken"
                                        :class="{ 'bg-green-50 border-green-200': tokenCopied }"
                                    >
                                        <Copy class="h-4 w-4" />
                                        {{ tokenCopied ? 'Copiado!' : 'Copiar' }}
                                    </Button>
                                </div>
                                <p class="text-xs text-muted-foreground mb-2">
                                    Token firmado digitalmente que contiene tus respuestas. Verificable públicamente.
                                </p>
                                <div class="flex gap-2">
                                    <Button 
                                        variant="outline" 
                                        size="sm"
                                        @click="verificarToken"
                                        class="text-xs"
                                    >
                                        <MapPin class="h-3 w-3 mr-1" />
                                        Verificar Públicamente
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Respuestas del formulario -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <CheckCircle class="h-5 w-5" />
                        Mis Respuestas
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-6">
                        <div
                            v-for="(field, index) in votacion.formulario_config"
                            :key="field.id"
                            class="space-y-2"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <Label class="text-base font-medium">
                                        {{ index + 1 }}. {{ field.title }}
                                        <span v-if="field.required" class="text-red-500">*</span>
                                    </Label>
                                    <p v-if="field.description" class="text-sm text-muted-foreground mt-1">
                                        {{ field.description }}
                                    </p>
                                </div>
                                <Badge variant="secondary" class="ml-4">
                                    {{ field.type === 'text' ? 'Texto' : 
                                       field.type === 'textarea' ? 'Texto largo' :
                                       field.type === 'select' ? 'Selección' :
                                       field.type === 'radio' ? 'Opción única' :
                                       field.type === 'checkbox' ? 'Múltiple' : field.type }}
                                </Badge>
                            </div>
                            
                            <div class="bg-muted/30 rounded-lg p-4">
                                <p class="text-lg">{{ formatRespuesta(field, voto.respuestas[field.id]) }}</p>
                            </div>
                            
                            <Separator v-if="index < votacion.formulario_config.length - 1" class="mt-6" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Nota final -->
            <Card class="border-green-200 bg-green-50/50">
                <CardContent class="pt-6">
                    <div class="flex items-start gap-3">
                        <CheckCircle class="h-5 w-5 text-green-600 mt-0.5" />
                        <div>
                            <p class="font-medium text-green-800">Voto registrado exitosamente</p>
                            <p class="text-sm text-green-700 mt-1">
                                Tu voto ha sido firmado digitalmente y almacenado de forma segura. 
                                El token contiene tus respuestas y puede ser verificado públicamente para confirmar la integridad del proceso electoral.
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>