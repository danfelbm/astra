<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, Home } from 'lucide-vue-next';

interface Props {
    mensaje?: string;
}

const props = withDefaults(defineProps<Props>(), {
    mensaje: 'Este formulario no está disponible en este momento.',
});
</script>

<template>
    <Head title="Formulario no disponible" />
    
    <GuestLayout>
        <div class="container max-w-2xl mx-auto py-8 px-4">
            <Card>
                <CardHeader>
                    <div class="flex justify-center mb-4">
                        <AlertCircle class="h-16 w-16 text-yellow-500" />
                    </div>
                    <CardTitle class="text-center text-2xl">Formulario No Disponible</CardTitle>
                    <CardDescription class="text-center text-lg mt-2">
                        {{ props.mensaje }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Alert class="mb-6">
                        <AlertTitle>¿Por qué no puedo acceder?</AlertTitle>
                        <AlertDescription>
                            El formulario puede no estar disponible por varias razones:
                            <ul class="list-disc list-inside mt-2">
                                <li>El período de respuesta ha finalizado</li>
                                <li>El formulario aún no ha sido publicado</li>
                                <li>Se ha alcanzado el límite máximo de respuestas</li>
                                <li>El formulario ha sido archivado</li>
                            </ul>
                        </AlertDescription>
                    </Alert>
                    
                    <div class="flex justify-center gap-4">
                        <Button @click="$inertia.visit('/miembro/formularios')" variant="default">
                            <Home class="mr-2 h-4 w-4" />
                            Ver formularios disponibles
                        </Button>
                        
                        <Button @click="window.history.back()" variant="outline">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Regresar
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </GuestLayout>
</template>