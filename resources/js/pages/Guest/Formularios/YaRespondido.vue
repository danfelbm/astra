<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, Home } from 'lucide-vue-next';

interface Props {
    mensaje?: string;
}

const props = withDefaults(defineProps<Props>(), {
    mensaje: 'Ya has respondido este formulario.',
});
</script>

<template>
    <Head title="Formulario ya respondido" />
    
    <GuestLayout>
        <div class="container max-w-2xl mx-auto py-8 px-4">
            <Card>
                <CardHeader>
                    <div class="flex justify-center mb-4">
                        <CheckCircle class="h-16 w-16 text-green-500" />
                    </div>
                    <CardTitle class="text-center text-2xl">Formulario Completado</CardTitle>
                    <CardDescription class="text-center text-lg mt-2">
                        {{ props.mensaje }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Alert class="mb-6">
                        <AlertTitle>Información</AlertTitle>
                        <AlertDescription>
                            Este formulario solo puede ser completado una vez por usuario. 
                            Si necesitas hacer cambios o tienes alguna pregunta, por favor contacta al administrador.
                        </AlertDescription>
                    </Alert>
                    
                    <div class="flex justify-center gap-4">
                        <Link href="/miembro/formularios">
                            <Button variant="default">
                                <Home class="mr-2 h-4 w-4" />
                                Ver otros formularios
                            </Button>
                        </Link>
                        
                        <Link href="/miembro/dashboard">
                            <Button variant="outline">
                                Ir al Dashboard
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </GuestLayout>
</template>