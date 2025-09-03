<template>
    <div class="max-w-2xl mx-auto">
        <Card>
            <CardHeader>
                <CardTitle>
                    <div class="flex items-center gap-2">
                        <ShieldCheck class="h-5 w-5" />
                        Verificación de Identidad
                    </div>
                </CardTitle>
                <CardDescription>
                    Hemos enviado códigos de verificación a tu email y WhatsApp
                </CardDescription>
            </CardHeader>
            
            <CardContent class="space-y-6">
                <!-- Información del usuario -->
                <Alert>
                    <User class="h-4 w-4" />
                    <AlertTitle>Usuario encontrado</AlertTitle>
                    <AlertDescription>
                        <div class="mt-2 space-y-1">
                            <p><strong>Nombre:</strong> {{ user.name }}</p>
                            <p><strong>Documento:</strong> {{ user.documento_identidad }}</p>
                            <p><strong>Email:</strong> {{ maskEmail(user.email) }}</p>
                            <p v-if="user.telefono"><strong>Teléfono:</strong> {{ maskPhone(user.telefono) }}</p>
                        </div>
                    </AlertDescription>
                </Alert>

                <!-- Códigos de verificación -->
                <div class="space-y-4">
                    <!-- Email -->
                    <div>
                        <Label>Código enviado por Email</Label>
                        <div class="flex gap-2 mt-1">
                            <VerificationCodeInput
                                v-model="emailCode"
                                :disabled="emailVerified"
                                :error="emailError"
                                @complete="verifyEmailCode"
                            />
                            <Badge v-if="emailVerified" variant="success">
                                <CheckCircle class="h-3 w-3 mr-1" />
                                Verificado
                            </Badge>
                        </div>
                        <p v-if="emailError" class="text-sm text-destructive mt-1">
                            {{ emailError }}
                        </p>
                    </div>

                    <!-- WhatsApp -->
                    <div v-if="user.telefono">
                        <Label>Código enviado por WhatsApp</Label>
                        <div class="flex gap-2 mt-1">
                            <VerificationCodeInput
                                v-model="whatsappCode"
                                :disabled="whatsappVerified"
                                :error="whatsappError"
                                @complete="verifyWhatsappCode"
                            />
                            <Badge v-if="whatsappVerified" variant="success">
                                <CheckCircle class="h-3 w-3 mr-1" />
                                Verificado
                            </Badge>
                        </div>
                        <p v-if="whatsappError" class="text-sm text-destructive mt-1">
                            {{ whatsappError }}
                        </p>
                    </div>
                </div>

                <!-- Timer y botón de timeout -->
                <div v-if="!canProceed" class="space-y-3">
                    <div class="flex items-center justify-between p-4 bg-muted rounded-lg">
                        <div class="flex items-center gap-2">
                            <Timer class="h-4 w-4 text-muted-foreground" />
                            <span class="text-sm">
                                {{ timeoutEnabled ? 'Tiempo agotado' : `Esperando códigos: ${timeoutSeconds}s` }}
                            </span>
                        </div>
                        
                        <Button 
                            v-if="!timeoutEnabled"
                            variant="ghost"
                            size="sm"
                            @click="resendCodes"
                            :disabled="resendLoading"
                        >
                            <RefreshCw class="h-4 w-4 mr-1" :class="{ 'animate-spin': resendLoading }" />
                            Reenviar códigos
                        </Button>
                    </div>

                    <Alert v-if="timeoutEnabled" variant="warning">
                        <AlertCircle class="h-4 w-4" />
                        <AlertTitle>¿No recibiste los códigos?</AlertTitle>
                        <AlertDescription>
                            Puedes actualizar tu información de contacto para recibir futuras comunicaciones.
                        </AlertDescription>
                    </Alert>
                </div>

                <!-- Acciones -->
                <div class="flex justify-between">
                    <Button
                        variant="outline"
                        @click="$emit('back')"
                    >
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Volver
                    </Button>

                    <Button
                        v-if="canProceed || timeoutEnabled"
                        @click="proceed"
                        :disabled="processing"
                    >
                        {{ timeoutEnabled ? 'Actualizar datos' : 'Continuar' }}
                        <ArrowRight class="h-4 w-4 ml-2" />
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { Alert, AlertDescription, AlertTitle } from "@modules/Core/Resources/js/components/ui/alert";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import VerificationCodeInput from "@modules/Core/Resources/js/components/forms/VerificationCodeInput.vue";
import { 
    ShieldCheck, 
    User, 
    CheckCircle, 
    Timer, 
    RefreshCw, 
    AlertCircle,
    ArrowLeft,
    ArrowRight 
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';

interface User {
    id: number;
    name: string;
    email: string;
    telefono?: string;
    documento_identidad: string;
}

interface Props {
    user: User;
    verificationRequestId: number;
    initialTimeout?: number;
}

const props = withDefaults(defineProps<Props>(), {
    initialTimeout: 10
});

const emit = defineEmits<{
    back: [];
    verified: [data: { email: boolean; whatsapp: boolean }];
    timeout: [];
}>();

const emailCode = ref('');
const whatsappCode = ref('');
const emailVerified = ref(false);
const whatsappVerified = ref(false);
const emailError = ref('');
const whatsappError = ref('');
const timeoutSeconds = ref(props.initialTimeout);
const timeoutEnabled = ref(false);
const timeoutInterval = ref<number | null>(null);
const processing = ref(false);
const resendLoading = ref(false);

const canProceed = computed(() => {
    const hasEmail = emailVerified.value || !props.user.email;
    const hasWhatsapp = whatsappVerified.value || !props.user.telefono;
    return hasEmail && hasWhatsapp;
});

const maskEmail = (email: string) => {
    const [local, domain] = email.split('@');
    const masked = local.substring(0, 2) + '***';
    return `${masked}@${domain}`;
};

const maskPhone = (phone: string) => {
    const cleaned = phone.replace(/\D/g, '');
    return `***${cleaned.slice(-4)}`;
};

const startTimeout = () => {
    timeoutInterval.value = setInterval(() => {
        timeoutSeconds.value--;
        if (timeoutSeconds.value <= 0) {
            clearInterval(timeoutInterval.value!);
            timeoutEnabled.value = true;
            emit('timeout');
        }
    }, 1000);
};

const verifyEmailCode = async () => {
    if (emailCode.value.length !== 6) return;
    
    try {
        const response = await axios.post(route('registro.confirmacion.verify-code'), {
            verification_id: props.verificationRequestId,
            code: String(emailCode.value),
            channel: 'email'
        });
        
        if (response.data.success) {
            emailVerified.value = true;
            emailError.value = '';
            toast.success('Email verificado', {
                description: 'Tu email ha sido verificado correctamente'
            });
        }
    } catch (error: any) {
        emailError.value = error.response?.data?.message || 'Código inválido';
    }
};

const verifyWhatsappCode = async () => {
    if (whatsappCode.value.length !== 6) return;
    
    try {
        const response = await axios.post(route('registro.confirmacion.verify-code'), {
            verification_id: props.verificationRequestId,
            code: String(whatsappCode.value),
            channel: 'whatsapp'
        });
        
        if (response.data.success) {
            whatsappVerified.value = true;
            whatsappError.value = '';
            toast.success('WhatsApp verificado', {
                description: 'Tu WhatsApp ha sido verificado correctamente'
            });
        }
    } catch (error: any) {
        whatsappError.value = error.response?.data?.message || 'Código inválido';
    }
};

const resendCodes = async () => {
    resendLoading.value = true;
    
    try {
        await axios.post(route('registro.confirmacion.send-verification'), {
            verification_id: props.verificationRequestId
        });
        
        toast.info('Códigos reenviados', {
            description: 'Revisa tu email y WhatsApp'
        });
        
        // Reiniciar timer
        timeoutSeconds.value = props.initialTimeout;
        timeoutEnabled.value = false;
        if (timeoutInterval.value) {
            clearInterval(timeoutInterval.value);
        }
        startTimeout();
    } catch (error: any) {
        toast.error('Error al reenviar códigos', {
            description: error.response?.data?.message || 'Intenta nuevamente'
        });
    } finally {
        resendLoading.value = false;
    }
};

const proceed = () => {
    processing.value = true;
    
    if (timeoutEnabled.value) {
        // Ir al formulario de actualización
        router.get(route('registro.confirmacion.update-form'));
    } else {
        // Notificar verificación exitosa
        emit('verified', {
            email: emailVerified.value,
            whatsapp: whatsappVerified.value
        });
    }
};

onMounted(() => {
    startTimeout();
});

onUnmounted(() => {
    if (timeoutInterval.value) {
        clearInterval(timeoutInterval.value);
    }
});
</script>