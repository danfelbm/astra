<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle, Copy, Home, FileText } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

// Props
interface Props {
    formulario: {
        titulo: string;
        mensaje_confirmacion?: string;
    };
    codigoConfirmacion: string;
}

const props = defineProps<Props>();

// Estado local
const copied = ref(false);

// Copiar código al portapapeles
const copyToClipboard = () => {
    navigator.clipboard.writeText(props.codigoConfirmacion).then(() => {
        copied.value = true;
        toast.success('Código copiado al portapapeles');
        
        setTimeout(() => {
            copied.value = false;
        }, 3000);
    }).catch(() => {
        toast.error('No se pudo copiar el código');
    });
};
</script>

<template>
    <Head title="Formulario Enviado" />
    
    <div class="min-h-screen bg-gradient-to-br from-green-50 via-white to-blue-50 flex items-center justify-center px-4">
        <div class="max-w-md w-full">
            <!-- Animación de éxito -->
            <div class="mb-8 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4 animate-bounce">
                    <CheckCircle class="h-12 w-12 text-green-600" />
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">¡Formulario Enviado!</h1>
                <p class="text-gray-600">
                    Tu respuesta ha sido registrada exitosamente
                </p>
            </div>
            
            <!-- Card con información -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ formulario.titulo }}</CardTitle>
                    <CardDescription>
                        {{ formulario.mensaje_confirmacion || 'Gracias por completar el formulario. Hemos recibido tu respuesta correctamente.' }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Código de confirmación -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600">Código de confirmación</span>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="copyToClipboard"
                                class="h-8"
                            >
                                <Copy class="h-4 w-4 mr-1" />
                                {{ copied ? 'Copiado' : 'Copiar' }}
                            </Button>
                        </div>
                        <div class="font-mono text-2xl font-bold text-center py-2 text-gray-900">
                            {{ codigoConfirmacion }}
                        </div>
                        <p class="text-xs text-center text-gray-500 mt-2">
                            Guarda este código para futuras referencias
                        </p>
                    </div>
                    
                    <!-- Información adicional -->
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <FileText class="h-5 w-5 text-blue-600" />
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-900">
                                    ¿Qué sigue?
                                </h3>
                                <div class="mt-2 text-sm text-blue-800">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Recibirás un correo de confirmación si proporcionaste tu email</li>
                                        <li>Puedes usar el código de confirmación para consultar tu respuesta</li>
                                        <li>Si necesitas hacer cambios, contacta al administrador</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="flex flex-col gap-2">
                        <Link :href="route('dashboard')">
                            <Button class="w-full">
                                <Home class="mr-2 h-4 w-4" />
                                Ir al inicio
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>
            
            <!-- Pie de página -->
            <div class="mt-6 text-center text-sm text-gray-500">
                <p>
                    Si tienes alguna pregunta, no dudes en 
                    <a href="#" class="text-blue-600 hover:underline">contactarnos</a>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

.animate-bounce {
    animation: bounce 2s ease-in-out infinite;
}
</style>