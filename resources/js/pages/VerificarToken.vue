<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Alert, AlertDescription } from '@/components/ui/alert';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Search, CheckCircle, XCircle, Shield, Clock, Hash, AlertTriangle, Copy, ExternalLink } from 'lucide-vue-next';
import { ref, computed } from 'vue';

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
    categoria: string;
    formulario_config: FormField[];
    fecha_inicio: string;
    fecha_fin: string;
    estado: string;
}

interface VoteData {
    votacion_id: number;
    respuestas: Record<string, any>;
    timestamp: string;
    vote_hash: string;
}

interface VerificationDetails {
    format_valid: boolean;
    signature_valid: boolean;
    hash_valid: boolean;
    votacion_exists: boolean;
    verified_at?: string;
}

interface Verification {
    is_valid: boolean;
    error?: string;
    token: string;
    vote_data?: VoteData;
    votacion?: Votacion;
    verification_details: VerificationDetails;
}

interface Props {
    token?: string;
    verification?: Verification;
}

const props = defineProps<Props>();

const searchToken = ref(props.token || '');
const isSearching = ref(false);
const currentVerification = ref<Verification | null>(props.verification || null);

// Estado de verificación
const verificationStatus = computed(() => {
    if (!currentVerification.value) return null;
    
    const v = currentVerification.value;
    if (v.is_valid) {
        return {
            icon: CheckCircle,
            color: 'text-green-600',
            bgColor: 'bg-green-50',
            borderColor: 'border-green-200',
            label: 'VÁLIDO',
            description: 'Token verificado criptográficamente'
        };
    } else {
        return {
            icon: XCircle,
            color: 'text-red-600',
            bgColor: 'bg-red-50',
            borderColor: 'border-red-200',
            label: 'INVÁLIDO',
            description: v.error || 'Token no válido o corrupto'
        };
    }
});

// Buscar token
const searchForToken = () => {
    if (!searchToken.value.trim()) return;
    
    isSearching.value = true;
    router.get(`/verificar-token/${encodeURIComponent(searchToken.value.trim())}`, {}, {
        onFinish: () => {
            isSearching.value = false;
        }
    });
};

// Formatear fecha
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

// Formatear respuesta según tipo de campo
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

// Copiar token
const tokenCopied = ref(false);
const copyToken = async () => {
    if (!currentVerification.value?.token) return;
    
    try {
        await navigator.clipboard.writeText(currentVerification.value.token);
        tokenCopied.value = true;
        setTimeout(() => {
            tokenCopied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Error al copiar token:', err);
    }
};

// Limpiar búsqueda
const clearSearch = () => {
    searchToken.value = '';
    currentVerification.value = null;
    router.get('/verificar-token');
};
</script>

<template>
    <Head title="Verificación de Tokens de Votación" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <!-- Header -->
            <div class="text-center">
                <h1 class="text-4xl font-bold flex items-center justify-center gap-3">
                    <Shield class="h-10 w-10 text-blue-600" />
                    Verificación de Tokens
                </h1>
                <p class="text-muted-foreground mt-2 max-w-2xl mx-auto">
                    Verifica la autenticidad e integridad de cualquier token de votación de forma pública y anónima.
                    Utiliza criptografía digital para garantizar que los votos no han sido alterados.
                </p>
            </div>

            <!-- Buscador -->
            <Card class="max-w-2xl mx-auto w-full">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Search class="h-5 w-5" />
                        Buscar Token
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div>
                            <Label for="token-input">Token de Votación</Label>
                            <Input
                                id="token-input"
                                v-model="searchToken"
                                placeholder="Pega aquí tu token de votación para verificar..."
                                @keyup.enter="searchForToken"
                                class="font-mono text-sm"
                            />
                        </div>
                        <div class="flex gap-2">
                            <Button 
                                @click="searchForToken" 
                                :disabled="!searchToken.trim() || isSearching"
                                class="flex-1"
                            >
                                <Search class="mr-2 h-4 w-4" />
                                {{ isSearching ? 'Verificando...' : 'Verificar Token' }}
                            </Button>
                            <Button 
                                v-if="currentVerification" 
                                @click="clearSearch"
                                variant="outline"
                            >
                                Limpiar
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Resultado de verificación -->
            <div v-if="currentVerification" class="max-w-4xl mx-auto w-full space-y-6">
                <!-- Estado de verificación -->
                <Card :class="[verificationStatus?.borderColor, verificationStatus?.bgColor]">
                    <CardContent class="pt-6">
                        <div class="flex items-center gap-4">
                            <component 
                                :is="verificationStatus?.icon"
                                :class="['h-12 w-12', verificationStatus?.color]"
                            />
                            <div class="flex-1">
                                <h2 :class="['text-2xl font-bold', verificationStatus?.color]">
                                    {{ verificationStatus?.label }}
                                </h2>
                                <p :class="['text-sm', verificationStatus?.color]">
                                    {{ verificationStatus?.description }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Información del token -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Hash class="h-5 w-5" />
                            Información del Token
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Token</Label>
                                <div class="flex items-center gap-2">
                                    <p class="text-xs font-mono bg-muted p-3 rounded flex-1 break-all">
                                        {{ currentVerification.token }}
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
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <Label class="text-muted-foreground">Formato</Label>
                                    <div class="flex items-center gap-1">
                                        <component 
                                            :is="currentVerification.verification_details.format_valid ? CheckCircle : XCircle"
                                            :class="[
                                                'h-4 w-4',
                                                currentVerification.verification_details.format_valid ? 'text-green-600' : 'text-red-600'
                                            ]"
                                        />
                                        <span>{{ currentVerification.verification_details.format_valid ? 'Válido' : 'Inválido' }}</span>
                                    </div>
                                </div>
                                <div>
                                    <Label class="text-muted-foreground">Firma Digital</Label>
                                    <div class="flex items-center gap-1">
                                        <component 
                                            :is="currentVerification.verification_details.signature_valid ? CheckCircle : XCircle"
                                            :class="[
                                                'h-4 w-4',
                                                currentVerification.verification_details.signature_valid ? 'text-green-600' : 'text-red-600'
                                            ]"
                                        />
                                        <span>{{ currentVerification.verification_details.signature_valid ? 'Válida' : 'Inválida' }}</span>
                                    </div>
                                </div>
                                <div>
                                    <Label class="text-muted-foreground">Integridad</Label>
                                    <div class="flex items-center gap-1">
                                        <component 
                                            :is="currentVerification.verification_details.hash_valid ? CheckCircle : XCircle"
                                            :class="[
                                                'h-4 w-4',
                                                currentVerification.verification_details.hash_valid ? 'text-green-600' : 'text-red-600'
                                            ]"
                                        />
                                        <span>{{ currentVerification.verification_details.hash_valid ? 'Íntegro' : 'Alterado' }}</span>
                                    </div>
                                </div>
                                <div>
                                    <Label class="text-muted-foreground">Votación</Label>
                                    <div class="flex items-center gap-1">
                                        <component 
                                            :is="currentVerification.verification_details.votacion_exists ? CheckCircle : XCircle"
                                            :class="[
                                                'h-4 w-4',
                                                currentVerification.verification_details.votacion_exists ? 'text-green-600' : 'text-red-600'
                                            ]"
                                        />
                                        <span>{{ currentVerification.verification_details.votacion_exists ? 'Existe' : 'No existe' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Información de la votación -->
                <Card v-if="currentVerification.votacion">
                    <CardHeader>
                        <CardTitle>{{ currentVerification.votacion.titulo }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <p v-if="currentVerification.votacion.descripcion" class="text-muted-foreground">
                                {{ currentVerification.votacion.descripcion }}
                            </p>
                            
                            <div class="flex flex-wrap gap-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <Badge variant="outline">{{ currentVerification.votacion.categoria }}</Badge>
                                </div>
                                <div class="flex items-center gap-2 text-muted-foreground">
                                    <Clock class="h-4 w-4" />
                                    {{ formatDate(currentVerification.votacion.fecha_inicio) }} - {{ formatDate(currentVerification.votacion.fecha_fin) }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Respuestas del voto -->
                <Card v-if="currentVerification.vote_data && currentVerification.votacion">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <CheckCircle class="h-5 w-5" />
                            Respuestas del Voto
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-6">
                                <div>
                                    <Label class="text-muted-foreground">Fecha del Voto</Label>
                                    <p class="font-medium">{{ formatDate(currentVerification.vote_data.timestamp) }}</p>
                                </div>
                                <div>
                                    <Label class="text-muted-foreground">Hash de Verificación</Label>
                                    <p class="font-mono text-xs break-all bg-muted p-2 rounded">{{ currentVerification.vote_data.vote_hash }}</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div
                                    v-for="(field, index) in currentVerification.votacion.formulario_config"
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
                                        <p class="text-lg">{{ formatRespuesta(field, currentVerification.vote_data.respuestas[field.id]) }}</p>
                                    </div>
                                    
                                    <Separator v-if="index < currentVerification.votacion.formulario_config.length - 1" class="mt-6" />
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Información adicional -->
                <Alert>
                    <Shield class="h-4 w-4" />
                    <AlertDescription>
                        <strong>Verificación criptográfica:</strong> Este token ha sido verificado usando criptografía digital RSA-2048 con SHA-256.
                        La verificación confirma que el voto proviene del sistema oficial y no ha sido alterado.
                        <br><br>
                        <strong>Privacidad:</strong> Esta verificación es completamente anónima. No se puede determinar la identidad del votante a partir de este token.
                    </AlertDescription>
                </Alert>
            </div>

            <!-- Información general -->
            <div v-else class="max-w-2xl mx-auto text-center space-y-4">
                <Alert>
                    <AlertTriangle class="h-4 w-4" />
                    <AlertDescription>
                        <strong>¿Cómo funciona?</strong><br>
                        Cada voto genera un token firmado digitalmente que contiene las respuestas del formulario.
                        Puedes verificar públicamente que tu voto fue procesado correctamente sin revelar tu identidad.
                    </AlertDescription>
                </Alert>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="text-center p-4">
                        <Shield class="h-8 w-8 text-blue-600 mx-auto mb-2" />
                        <h3 class="font-medium">Seguridad</h3>
                        <p class="text-muted-foreground">Criptografía RSA-2048</p>
                    </div>
                    <div class="text-center p-4">
                        <CheckCircle class="h-8 w-8 text-green-600 mx-auto mb-2" />
                        <h3 class="font-medium">Verificable</h3>
                        <p class="text-muted-foreground">Integridad garantizada</p>
                    </div>
                    <div class="text-center p-4">
                        <ExternalLink class="h-8 w-8 text-purple-600 mx-auto mb-2" />
                        <h3 class="font-medium">Público</h3>
                        <p class="text-muted-foreground">Sin autenticación</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>