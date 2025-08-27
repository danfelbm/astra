<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Lock, LogIn, Home } from 'lucide-vue-next';

interface Props {
    mensaje?: string;
}

const props = withDefaults(defineProps<Props>(), {
    mensaje: 'No tienes permisos para acceder a este formulario.',
});
</script>

<template>
    <Head title="Acceso denegado" />
    
    <GuestLayout>
        <div class="container max-w-2xl mx-auto py-8 px-4">
            <Card>
                <CardHeader>
                    <div class="flex justify-center mb-4">
                        <Lock class="h-16 w-16 text-red-500" />
                    </div>
                    <CardTitle class="text-center text-2xl">Acceso Denegado</CardTitle>
                    <CardDescription class="text-center text-lg mt-2">
                        {{ props.mensaje }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Alert class="mb-6" variant="destructive">
                        <AlertTitle>Sin autorización</AlertTitle>
                        <AlertDescription>
                            Este formulario tiene restricciones de acceso. Puede ser que:
                            <ul class="list-disc list-inside mt-2">
                                <li>Necesitas iniciar sesión para acceder</li>
                                <li>El formulario está limitado a ciertos usuarios</li>
                                <li>No tienes los permisos necesarios</li>
                                <li>El formulario está reservado para un grupo específico</li>
                            </ul>
                        </AlertDescription>
                    </Alert>
                    
                    <div class="flex justify-center gap-4">
                        <Link href="/login">
                            <Button variant="default">
                                <LogIn class="mr-2 h-4 w-4" />
                                Iniciar Sesión
                            </Button>
                        </Link>
                        
                        <Link href="/miembro/formularios">
                            <Button variant="outline">
                                <Home class="mr-2 h-4 w-4" />
                                Ver otros formularios
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </GuestLayout>
</template>