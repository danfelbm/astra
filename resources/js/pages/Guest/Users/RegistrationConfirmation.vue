<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Search, UserCheck, AlertCircle, Loader2 } from 'lucide-vue-next';
import { PinInput, PinInputGroup, PinInputSlot } from '@/components/ui/pin-input';
import axios from 'axios';
import { toast } from 'vue-sonner';

interface UserFound {
    name: string;
    documento_identidad: string;
    email?: string;
    telefono?: string;
    has_email: boolean;
    has_phone: boolean;
    created_at: string;
}

interface CensoRestriction {
    restricted: boolean;
    message: string;
    votacion_titulo?: string;
    limite_censo?: string;
    user_created_at?: string;
}

// Estados de búsqueda
const isSearching = ref(false);
const documentoIdentidad = ref('');
const userFound = ref<UserFound | null>(null);
const verificationId = ref<number | null>(null);
const errorMessage = ref('');
const censoRestriction = ref<CensoRestriction | null>(null);

// Estados de verificación
const verificationStep = ref<'search' | 'found' | 'verifying' | 'timeout'>('search');
const verificationCodes = ref({
    email: '',
    whatsapp: ''
});
const isVerifying = ref(false);
const canProceedToUpdate = ref(false);
const timeoutSeconds = ref(0);
const timeoutInterval = ref<any>(null);

// Estados de envío
const isSendingCodes = ref(false);
const codesSent = ref(false);
const channels = ref({
    email: false,
    whatsapp: false
});

// Búsqueda de usuario por documento
const searchUser = async () => {
    if (!documentoIdentidad.value) {
        toast.error('Por favor ingresa un número de documento');
        return;
    }

    isSearching.value = true;
    errorMessage.value = '';
    userFound.value = null;
    censoRestriction.value = null;
    
    try {
        const response = await axios.post('/confirmar-registro/buscar', {
            documento_identidad: documentoIdentidad.value
        });
        
        if (response.data.success) {
            userFound.value = response.data.user;
            verificationId.value = response.data.verification_id;
            censoRestriction.value = response.data.censo_restriction || null;
            verificationStep.value = 'found';
            toast.success('Usuario encontrado correctamente');
        }
    } catch (error: any) {
        if (error.response?.status === 404) {
            errorMessage.value = 'No se encontró ningún usuario registrado con ese documento.';
        } else {
            errorMessage.value = error.response?.data?.message || 'Error al buscar el usuario';
        }
        toast.error(errorMessage.value);
    } finally {
        isSearching.value = false;
    }
};

// Enviar códigos de verificación
const sendVerificationCodes = async () => {
    if (!verificationId.value) return;
    
    isSendingCodes.value = true;
    
    try {
        const response = await axios.post('/confirmar-registro/enviar-verificacion', {
            verification_id: verificationId.value
        });
        
        if (response.data.success) {
            codesSent.value = true;
            channels.value = response.data.channels;
            verificationStep.value = 'verifying';
            startTimeout();
            
            toast.success('Códigos de verificación enviados');
            
            // Mensaje específico por canal
            if (channels.value.email && channels.value.whatsapp) {
                toast.info('Revisa tu email y WhatsApp');
            } else if (channels.value.email) {
                toast.info('Revisa tu email');
            } else if (channels.value.whatsapp) {
                toast.info('Revisa tu WhatsApp');
            }
        }
    } catch (error: any) {
        if (error.response?.status === 429) {
            errorMessage.value = 'Has excedido el límite de intentos. Por favor, espera 1 hora.';
        } else {
            errorMessage.value = error.response?.data?.message || 'Error al enviar códigos';
        }
        toast.error(errorMessage.value);
    } finally {
        isSendingCodes.value = false;
    }
};

// Iniciar temporizador de 10 segundos
const startTimeout = () => {
    timeoutSeconds.value = 10;
    timeoutInterval.value = setInterval(() => {
        timeoutSeconds.value--;
        if (timeoutSeconds.value <= 0) {
            clearInterval(timeoutInterval.value);
            checkTimeout();
        }
    }, 1000);
};

// Verificar si puede proceder después del timeout
const checkTimeout = async () => {
    if (!verificationId.value) return;
    
    try {
        const response = await axios.post('/confirmar-registro/check-timeout', {
            verification_id: verificationId.value
        });
        
        if (response.data.can_proceed) {
            canProceedToUpdate.value = true;
            // No cambiar automáticamente el estado, esperar acción del usuario
        }
    } catch (error) {
        console.error('Error checking timeout:', error);
    }
};

// Verificar código
const verifyCode = async (channel: 'email' | 'whatsapp') => {
    const codeArray = channel === 'email' ? verificationCodes.value.email : verificationCodes.value.whatsapp;
    
    // Convertir array a string si es necesario
    const code = Array.isArray(codeArray) ? codeArray.join('') : String(codeArray);
    
    if (!code || code.length !== 6) {
        toast.error('Por favor ingresa un código de 6 dígitos');
        return;
    }
    
    isVerifying.value = true;
    
    try {
        const response = await axios.post('/confirmar-registro/verificar-codigo', {
            verification_id: verificationId.value,
            code: code,
            channel: channel
        });
        
        if (response.data.success) {
            toast.success('Código verificado correctamente');
            
            if (response.data.fully_verified || response.data.can_proceed) {
                proceedToUpdate();
            } else {
                // Marcar el canal como verificado visualmente
                if (channel === 'email') {
                    channels.value.email = false;
                } else {
                    channels.value.whatsapp = false;
                }
            }
        }
    } catch (error: any) {
        if (error.response?.data?.expired) {
            errorMessage.value = 'Los códigos han expirado. Por favor, solicita nuevos códigos.';
            verificationStep.value = 'found';
            codesSent.value = false;
        } else {
            errorMessage.value = error.response?.data?.message || 'Código incorrecto';
        }
        toast.error(errorMessage.value);
    } finally {
        isVerifying.value = false;
    }
};

// Proceder a actualización de datos
const proceedToUpdate = () => {
    window.location.href = `/confirmar-registro/actualizar-datos?verification_id=${verificationId.value}`;
};

// Reiniciar búsqueda
const resetSearch = () => {
    documentoIdentidad.value = '';
    userFound.value = null;
    verificationId.value = null;
    censoRestriction.value = null;
    verificationStep.value = 'search';
    codesSent.value = false;
    canProceedToUpdate.value = false;
    errorMessage.value = '';
    verificationCodes.value = { email: '', whatsapp: '' };
    clearInterval(timeoutInterval.value);
};

// Computed para mostrar mensaje del timer
const timerMessage = computed(() => {
    if (timeoutSeconds.value > 0) {
        return `Si no recibes los códigos, podrás actualizar tus datos en ${timeoutSeconds.value} segundos`;
    }
    return '';
});

// Función para censurar nombre
const censorName = (name: string): string => {
    if (!name) return '';
    const parts = name.split(' ');
    return parts.map(part => {
        if (part.length <= 2) return part;
        const firstTwo = part.substring(0, 2);
        const rest = '*'.repeat(part.length - 2);
        return firstTwo + rest;
    }).join(' ');
};

// Función para censurar email
const censorEmail = (email: string): string => {
    if (!email) return '';
    const [username, domain] = email.split('@');
    if (!username || !domain) return email;
    
    const [domainName, tld] = domain.split('.');
    if (!domainName || !tld) return email;
    
    // Censurar username: primeras 2 letras y última
    let censoredUsername = '';
    if (username.length <= 3) {
        censoredUsername = username;
    } else {
        const firstTwo = username.substring(0, 2);
        const lastOne = username.substring(username.length - 1);
        const stars = '*'.repeat(username.length - 3);
        censoredUsername = firstTwo + stars + lastOne;
    }
    
    // Censurar dominio: primera letra y resto con asteriscos
    const censoredDomain = domainName[0] + '*'.repeat(domainName.length - 1);
    
    return `${censoredUsername}@${censoredDomain}.${tld}`;
};

// Función para censurar teléfono
const censorPhone = (phone: string): string => {
    if (!phone) return '';
    // Mantener los primeros 5 caracteres y el último
    if (phone.length <= 6) return phone;
    
    const first = phone.substring(0, 5);
    const last = phone.substring(phone.length - 1);
    const stars = '*'.repeat(phone.length - 6);
    
    return first + stars + last;
};

// Función para formatear fecha de registro en GMT-5 (Hora Colombia)
const formatRegistrationDate = (dateString: string): string => {
    if (!dateString) return '';
    
    // Asegurar que la fecha se interprete como UTC agregando 'Z' si no está presente
    const utcDateString = dateString.includes('Z') || dateString.includes('+') 
        ? dateString 
        : dateString + 'Z';
    
    const date = new Date(utcDateString);
    
    // Configurar opciones para mostrar en zona horaria de Bogotá (GMT-5)
    const options: Intl.DateTimeFormatOptions = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
        timeZone: 'America/Bogota' // Forzar GMT-5
    };
    
    // Usar locale es-CO para formato colombiano
    return date.toLocaleDateString('es-CO', options) + ' (GMT-5)';
};
</script>

<template>
    <Head title="Confirmar Registro" />
    
    <GuestLayout 
        title="Confirmación de Registro"
        description="Verifica si estás registrado en el sistema y actualiza tus datos de contacto"
    >
        <div class="max-w-2xl mx-auto">
            <!-- Estado: Búsqueda inicial -->
            <Card v-if="verificationStep === 'search'" class="shadow-lg">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Search class="w-5 h-5" />
                        Buscar Registro
                    </CardTitle>
                    <CardDescription>
                        Ingresa tu número de documento para verificar si estás registrado
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="searchUser" class="space-y-4">
                        <div>
                            <Label for="documento">Número de Documento</Label>
                            <Input
                                id="documento"
                                v-model="documentoIdentidad"
                                type="text"
                                placeholder="Ej: 1234567890"
                                :disabled="isSearching"
                                class="mt-1"
                                autofocus
                            />
                        </div>
                        
                        <Alert v-if="errorMessage" variant="destructive">
                            <AlertCircle class="h-4 w-4" />
                            <AlertDescription>{{ errorMessage }}</AlertDescription>
                        </Alert>
                        
                        <Button 
                            type="submit" 
                            :disabled="isSearching || !documentoIdentidad"
                            class="w-full"
                        >
                            <Loader2 v-if="isSearching" class="mr-2 h-4 w-4 animate-spin" />
                            {{ isSearching ? 'Buscando...' : 'Buscar' }}
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <!-- Estado: Usuario encontrado -->
            <Card v-else-if="verificationStep === 'found'" class="shadow-lg">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <UserCheck class="w-5 h-5 text-green-600" />
                        Usuario Encontrado
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="bg-muted p-4 rounded-lg space-y-2">
                        <p><strong>Nombre:</strong> {{ censorName(userFound?.name || '') }}</p>
                        <p><strong>Documento:</strong> {{ userFound?.documento_identidad }}</p>
                        <p><strong>Registrado en:</strong> {{ formatRegistrationDate(userFound?.created_at || '') }}</p>
                        <div class="flex flex-col gap-2 mt-3">
                            <div v-if="userFound?.has_email && userFound?.email" class="flex items-center gap-2">
                                <span class="text-sm text-green-600">✓</span>
                                <span class="text-sm"><strong>Email:</strong> {{ censorEmail(userFound.email) }}</span>
                            </div>
                            <div v-if="userFound?.has_phone && userFound?.telefono" class="flex items-center gap-2">
                                <span class="text-sm text-green-600">✓</span>
                                <span class="text-sm"><strong>Teléfono:</strong> {{ censorPhone(userFound.telefono) }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mensaje de restricción por censo si aplica -->
                    <Alert v-if="censoRestriction && censoRestriction.restricted" variant="destructive" class="my-4">
                        <AlertCircle class="h-4 w-4" />
                        <AlertTitle>Restricción de Censo Electoral</AlertTitle>
                        <AlertDescription class="mt-2">
                            {{ censoRestriction.message }}
                        </AlertDescription>
                    </Alert>
                    
                    <Alert v-if="!censoRestriction || !censoRestriction.restricted">
                        <AlertDescription class="text-foreground">
                            ¿Tus datos son correctos? Puedes validarlos a continuación y actualizarlos si deseas. Te enviaremos códigos de verificación a tu email y/o teléfono registrados (via WhatsApp).
                        </AlertDescription>
                    </Alert>
                    
                    <div class="flex gap-3">
                        <Button 
                            @click="sendVerificationCodes"
                            :disabled="isSendingCodes || (censoRestriction && censoRestriction.restricted)"
                            class="flex-1"
                        >
                            <Loader2 v-if="isSendingCodes" class="mr-2 h-4 w-4 animate-spin" />
                            {{ isSendingCodes ? 'Enviando...' : (censoRestriction && censoRestriction.restricted ? 'Validación no disponible' : 'Validar Datos') }}
                        </Button>
                        <Button 
                            @click="resetSearch"
                            variant="outline"
                        >
                            Buscar otro
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Estado: Verificación de códigos -->
            <Card v-else-if="verificationStep === 'verifying'" class="shadow-lg">
                <CardHeader>
                    <CardTitle>Valida datos existentes</CardTitle>
                    <CardDescription>
                        Si tus datos son correctos puedes validarlos a continuación; ingresa los códigos que recibiste por email y por whatsapp
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Mensaje de timeout y botón de actualización movidos arriba -->
                    <Alert v-if="timerMessage" class="border-green-200 bg-green-50">
                        <AlertDescription class="text-green-700">{{ timerMessage }}</AlertDescription>
                    </Alert>

                    <!-- Opción para actualizar datos si no recibió códigos -->
                    <div v-if="canProceedToUpdate" class="pb-4 border-b">
                        <div class="space-y-3">
                            <p class="text-sm text-muted-foreground">
                                Si no recibiste los códigos, puedes esperar un poco y actualizar tus datos:
                            </p>
                            <Button 
                                @click="proceedToUpdate" 
                                variant="default"
                                class="w-full"
                            >
                                ¿Deseas actualizar datos?
                            </Button>
                        </div>
                    </div>

                    <!-- Código Email -->
                    <div v-if="channels.email" class="space-y-2">
                        <Label>Código de Email</Label>
                        <div class="flex gap-2 items-center">
                            <PinInput
                                v-model="verificationCodes.email"
                                :disabled="isVerifying"
                                placeholder="○"
                                @complete="() => verifyCode('email')"
                            >
                                <PinInputGroup>
                                    <PinInputSlot v-for="(_, index) in 6" :key="index" :index="index" />
                                </PinInputGroup>
                            </PinInput>
                            <Button 
                                @click="verifyCode('email')"
                                :disabled="isVerifying || (Array.isArray(verificationCodes.email) ? verificationCodes.email.join('').length !== 6 : verificationCodes.email.length !== 6)"
                                size="sm"
                            >
                                Verificar
                            </Button>
                        </div>
                    </div>

                    <!-- Código WhatsApp -->
                    <div v-if="channels.whatsapp" class="space-y-2">
                        <Label>Código de WhatsApp</Label>
                        <div class="flex gap-2 items-center">
                            <PinInput
                                v-model="verificationCodes.whatsapp"
                                :disabled="isVerifying"
                                placeholder="○"
                                @complete="() => verifyCode('whatsapp')"
                            >
                                <PinInputGroup>
                                    <PinInputSlot v-for="(_, index) in 6" :key="index" :index="index" />
                                </PinInputGroup>
                            </PinInput>
                            <Button 
                                @click="verifyCode('whatsapp')"
                                :disabled="isVerifying || (Array.isArray(verificationCodes.whatsapp) ? verificationCodes.whatsapp.join('').length !== 6 : verificationCodes.whatsapp.length !== 6)"
                                size="sm"
                            >
                                Verificar
                            </Button>
                        </div>
                    </div>

                    <Alert v-if="errorMessage" variant="destructive">
                        <AlertCircle class="h-4 w-4" />
                        <AlertDescription>{{ errorMessage }}</AlertDescription>
                    </Alert>
                </CardContent>
            </Card>

            <!-- Estado: Timeout alcanzado (eliminado - ya no es necesario) -->
        </div>
    </GuestLayout>
</template>