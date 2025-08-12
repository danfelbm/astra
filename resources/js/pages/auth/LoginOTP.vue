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
}>();

// Estados del proceso OTP
const step = ref<'credential' | 'otp'>('credential');

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
    credential: '',
    otp_code: '',
});

const isRequestingOTP = ref(false);
const resendTimer = ref(0);
const timerInterval = ref<NodeJS.Timeout | null>(null);

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
    timerInterval.value = setInterval(() => {
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
        onSuccess: () => {
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
    // Asegurar que el OTP sea string y actualizar el form directamente
    const otpCode = Array.isArray(otpForm.otp_code) ? otpForm.otp_code.join('') : String(otpForm.otp_code);
    otpForm.otp_code = otpCode;
    
    // Enviar con Inertia.js (no usar data: param)
    otpForm.post(route('auth.verify-otp'), {
        onSuccess: (response) => {
            // Redirección manejada por el backend
        },
        onError: () => {
            // Limpiar OTP en caso de error
            otpForm.otp_code = '';
        }
    });
};

// Reenviar código OTP
const resendOTP = () => {
    if (!canResend.value) return;
    
    isRequestingOTP.value = true;
    
    // Usar otpForm que ya tiene el credential guardado
    otpForm.post(route('auth.resend-otp'), {
        onSuccess: () => {
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
import { onUnmounted } from 'vue';
onUnmounted(() => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value);
    }
});
</script>

<template>
    <AuthBase 
        :title="step === 'credential' ? 'Iniciar Sesión' : 'Verificar Código'" 
        :description="step === 'credential' ? `Ingresa tu ${config.label.toLowerCase()} para recibir el código de verificación` : 'Ingresa el código de 6 dígitos enviado a tu correo'"
    >
        <Head title="Iniciar Sesión" />

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
                            Código enviado a: <span class="font-medium">{{ otpForm.credential }}</span>
                        </span>
                        <span v-else>
                            Código enviado al email asociado al documento: <span class="font-medium">{{ otpForm.credential }}</span>
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
                        :disabled="otpForm.processing || otpForm.otp_code.length !== 6"
                    >
                        <LoaderCircle v-if="otpForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ otpForm.processing ? 'Verificando...' : 'Verificar Código' }}
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

        <!-- Información adicional -->
        <div class="mt-6 text-center text-xs text-muted-foreground">
            <p v-if="step === 'credential'">
                Solo usuarios autorizados pueden acceder al sistema de votaciones.
            </p>
            <p v-else>
                El código expira en 10 minutos. Si no lo recibes, verifica tu bandeja de spam.
            </p>
        </div>
    </AuthBase>
</template>