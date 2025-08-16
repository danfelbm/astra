<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PinInput, PinInputGroup, PinInputSlot } from '@/components/ui/pin-input';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Mail } from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface AuthConfig {
    login_type: 'email' | 'documento';
    input_type: string;
    placeholder: string;
    pattern: string | null;
    label: string;
}

// Props
const props = defineProps<{
    status?: string;
    authConfig?: AuthConfig;
    censoredEmail?: string;
    otpCredential?: string;
    otpChannel?: string;
    whatsappEnabled?: boolean;
}>();

// Estados del proceso OTP
// Si ya tenemos un email censurado, significa que ya se envió el OTP
const step = ref<'credential' | 'otp'>(props.censoredEmail ? 'otp' : 'credential');
const censoredEmailRef = ref<string>(props.censoredEmail || '');  // Para almacenar el email censurado

// Configuración por defecto si no viene del servidor
const config = computed(() => props.authConfig || {
    login_type: 'email',
    input_type: 'email',
    placeholder: 'correo@ejemplo.com',
    pattern: null,
    label: 'Correo Electrónico',
});

const credentialForm = useForm({
    credential: '',
});

const otpForm = useForm({
    credential: props.otpCredential || '',
    otp_code: [] as string[],  // El PinInput espera un array de strings
});

// Form para enviar el OTP (con el código como string)
const submitForm = useForm({
    credential: '',
    otp_code: '',
});

const isRequestingOTP = ref(false);
const resendTimer = ref(0);
const timerInterval = ref<number | null>(null);

// Computed para mostrar tiempo restante
const canResend = computed(() => resendTimer.value === 0);
const resendText = computed(() => 
    resendTimer.value > 0 
        ? `Reenviar en ${resendTimer.value}s` 
        : 'Reenviar código'
);

// Función para iniciar timer de reenvío
const startResendTimer = (seconds: number = 60) => {
    resendTimer.value = seconds;
    if (timerInterval.value) {
        clearInterval(timerInterval.value);
    }
    timerInterval.value = window.setInterval(() => {
        resendTimer.value--;
        if (resendTimer.value <= 0) {
            clearInterval(timerInterval.value!);
            timerInterval.value = null;
        }
    }, 1000);
};

// Solicitar código OTP
const requestOTP = () => {
    isRequestingOTP.value = true;
    
    credentialForm.post(route('auth.request-otp'), {
        onSuccess: (page: any) => {
            // Obtener el email censurado de la respuesta
            if (page.props && page.props.censoredEmail) {
                censoredEmailRef.value = page.props.censoredEmail;
            }
            
            // Cambiar a paso OTP
            step.value = 'otp';
            otpForm.credential = credentialForm.credential;
            otpForm.clearErrors();
            
            // Iniciar timer de reenvío
            startResendTimer(60);
        },
        onError: () => {
            // Los errores se muestran automáticamente
        },
        onFinish: () => {
            isRequestingOTP.value = false;
        }
    });
};

// Verificar código OTP
const verifyOTP = () => {
    // Convertir array a string para enviar al servidor
    const otpCode = otpForm.otp_code.join('');
    
    // Actualizar el submitForm con los valores actuales
    submitForm.credential = otpForm.credential;
    submitForm.otp_code = otpCode;
    
    // Enviar con Inertia.js
    submitForm.post(route('auth.verify-otp'), {
        onSuccess: () => {
            // Redirección manejada por el backend
        },
        onError: () => {
            // Limpiar OTP en caso de error
            otpForm.otp_code = [];
        }
    });
};

// Reenviar código OTP
const resendOTP = () => {
    if (!canResend.value) return;
    
    isRequestingOTP.value = true;
    
    // Usar otpForm que ya tiene el credential guardado
    otpForm.post(route('auth.resend-otp'), {
        onSuccess: (page: any) => {
            // Actualizar el email censurado si viene en la respuesta
            if (page.props && page.props.censoredEmail) {
                censoredEmailRef.value = page.props.censoredEmail;
            }
            
            // Reiniciar timer
            startResendTimer(60);
        },
        onFinish: () => {
            isRequestingOTP.value = false;
        }
    });
};

// Volver al paso anterior
const goBackToCredential = () => {
    step.value = 'credential';
    otpForm.reset();
    if (timerInterval.value) {
        clearInterval(timerInterval.value);
        timerInterval.value = null;
    }
    resendTimer.value = 0;
};

// Cleanup al desmontar componente
import { onMounted, onUnmounted } from 'vue';

// Si ya estamos en el paso OTP, iniciar el timer
onMounted(() => {
    if (step.value === 'otp') {
        startResendTimer(60);
    }
});

onUnmounted(() => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value);
    }
});
</script>

<template>
    <AuthBase 
        :title="step === 'credential' ? 'Iniciar Sesión' : 'Verificar Código'" 
    >
        <Head title="Iniciar Sesión" />

        <!-- Descripción personalizada con texto en negrilla -->
        <div v-if="step === 'credential'" class="mb-4 text-center text-muted-foreground">
            <p v-if="config.login_type === 'documento'">
                Ingresa tu documento de identidad para recibir el código de verificación 
                <strong v-if="!whatsappEnabled">vía correo electrónico</strong>
                <strong v-else-if="props.otpChannel === 'whatsapp'">vía WhatsApp</strong>
                <strong v-else-if="props.otpChannel === 'both'">vía correo electrónico y WhatsApp</strong>
                <strong v-else>vía correo electrónico</strong>
            </p>
            <p v-else>
                Ingresa tu {{ config.label.toLowerCase() }} para recibir el código de verificación
            </p>
        </div>
        <div v-else class="mb-4 text-center text-muted-foreground">
            <p v-if="props.otpChannel === 'whatsapp'">
                Ingresa el código de 6 dígitos enviado a tu <strong>WhatsApp</strong>
            </p>
            <p v-else-if="props.otpChannel === 'both'">
                Ingresa el código de 6 dígitos enviado a tu <strong>correo y WhatsApp</strong>
            </p>
            <p v-else>
                Ingresa el código de 6 dígitos enviado a tu <strong>correo</strong>
            </p>
        </div>

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <!-- Paso 1: Solicitar Credencial (Email o Documento) -->
        <form v-if="step === 'credential'" @submit.prevent="requestOTP" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="credential">{{ config.label }}</Label>
                    <div class="relative">
                        <Mail v-if="config.login_type === 'email'" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            id="credential"
                            :type="config.input_type"
                            required
                            autofocus
                            tabindex="1"
                            :autocomplete="config.login_type === 'email' ? 'email' : 'off'"
                            :inputmode="config.login_type === 'documento' ? 'numeric' : undefined"
                            v-model="credentialForm.credential"
                            :placeholder="config.placeholder"
                            :pattern="config.pattern"
                            :class="{ 'pl-10': config.login_type === 'email' }"
                        />
                    </div>
                    <InputError :message="credentialForm.errors.credential" />
                </div>

                <Button 
                    type="submit" 
                    class="mt-4 w-full" 
                    tabindex="2" 
                    :disabled="credentialForm.processing || isRequestingOTP"
                >
                    <LoaderCircle v-if="credentialForm.processing || isRequestingOTP" class="mr-2 h-4 w-4 animate-spin" />
                    {{ credentialForm.processing || isRequestingOTP ? 'Enviando...' : 'Enviar Código' }}
                </Button>
            </div>
        </form>

        <!-- Paso 2: Verificar OTP -->
        <form v-else @submit.prevent="verifyOTP" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <!-- Mostrar credencial a la que se envió -->
                <div class="text-center">
                    <p class="text-sm text-muted-foreground">
                        <span v-if="config.login_type === 'email'">
                            <span v-if="props.otpChannel === 'whatsapp'">
                                Código enviado por WhatsApp al número asociado con <span class="font-medium">{{ censoredEmailRef || otpForm.credential }}</span>
                            </span>
                            <span v-else-if="props.otpChannel === 'both'">
                                Código enviado al email <span class="font-medium">{{ censoredEmailRef || otpForm.credential }}</span> y WhatsApp
                            </span>
                            <span v-else>
                                Código enviado al email <span class="font-medium">{{ censoredEmailRef || otpForm.credential }}</span>
                            </span>
                        </span>
                        <span v-else>
                            <span v-if="props.otpChannel === 'whatsapp'">
                                Código enviado por WhatsApp al número asociado con el documento <span class="font-medium">{{ otpForm.credential }}</span>
                            </span>
                            <span v-else-if="props.otpChannel === 'both'">
                                Código enviado al email <span class="font-medium">{{ censoredEmailRef }}</span> y WhatsApp asociados al documento <span class="font-medium">{{ otpForm.credential }}</span>
                            </span>
                            <span v-else>
                                Código enviado al email <span class="font-medium">{{ censoredEmailRef }}</span> asociado al documento <span class="font-medium">{{ otpForm.credential }}</span>
                            </span>
                        </span>
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label class="text-center">Código de Verificación</Label>
                    <div class="flex justify-center">
                        <PinInput 
                            v-model="otpForm.otp_code"
                            :length="6"
                            type="text"
                            placeholder="0"
                            class="gap-2"
                        >
                            <PinInputGroup>
                                <PinInputSlot 
                                    v-for="(id, index) in 6" 
                                    :key="id" 
                                    :index="index" 
                                    class="h-12 w-12 text-lg font-medium"
                                    :inputmode="'numeric'"
                                />
                            </PinInputGroup>
                        </PinInput>
                    </div>
                    <InputError :message="otpForm.errors.otp_code" />
                </div>

                <div class="flex flex-col gap-3">
                    <Button 
                        type="submit" 
                        class="w-full" 
                        :disabled="submitForm.processing || otpForm.otp_code.join('').length !== 6"
                    >
                        <LoaderCircle v-if="submitForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ submitForm.processing ? 'Verificando...' : 'Verificar Código' }}
                    </Button>

                    <!-- Botones secundarios -->
                    <div class="flex flex-col gap-2 text-center">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="resendOTP"
                            :disabled="!canResend || isRequestingOTP"
                        >
                            <LoaderCircle v-if="isRequestingOTP" class="mr-2 h-3 w-3 animate-spin" />
                            {{ isRequestingOTP ? 'Enviando...' : resendText }}
                        </Button>

                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="goBackToCredential"
                        >
                            {{ config.login_type === 'email' ? 'Cambiar correo electrónico' : 'Cambiar documento' }}
                        </Button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Enlace a registro -->
        <div v-if="step === 'credential'" class="mt-6 text-center text-sm text-muted-foreground">
            ¿Deseas ser precandidato por la Colombia Humana pero no eres militante del partido?
            <TextLink :href="route('register')" class="underline underline-offset-4">Regístrate aquí</TextLink>
        </div>

        <!-- Información adicional -->
        <div class="mt-6 text-center text-xs text-muted-foreground">
            <p v-if="step === 'credential'">
                Solo personas registradas como militantes pueden acceder al sistema.
            </p>
            <p v-else>
                El código expira en 10 minutos. Si no lo recibes, verifica tu bandeja de spam.
            </p>
            <br />
            <p>
                Si necesitas ayuda escribe a <b>soporte@colombiahumana.co</b> para soporte sobre la plataforma. Para inquietudes jurídicas a <b>juridicoelectoral@colombiahumana.co</b>
            </p>
        </div>
    </AuthBase>
</template>