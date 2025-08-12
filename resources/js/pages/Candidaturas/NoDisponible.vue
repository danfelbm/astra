<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ArrowLeft, Settings, AlertTriangle } from 'lucide-vue-next';

interface Props {
    mensaje: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Mi Candidatura', href: '#' },
];
</script>

<template>
    <Head title="Candidaturas No Disponibles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Mi Candidatura</h1>
                    <p class="text-muted-foreground">
                        Sistema de candidaturas
                    </p>
                </div>
                <Button variant="outline" @click="$inertia.visit('/dashboard')">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver al Dashboard
                </Button>
            </div>

            <!-- Mensaje de No Disponible -->
            <Card class="border-yellow-200 bg-yellow-50">
                <CardContent class="p-12 text-center">
                    <div class="flex flex-col items-center space-y-4">
                        <div class="p-4 rounded-full bg-yellow-100">
                            <AlertTriangle class="h-12 w-12 text-yellow-600" />
                        </div>
                        
                        <div class="space-y-2">
                            <h3 class="text-xl font-semibold text-yellow-800">
                                Sistema No Disponible
                            </h3>
                            <p class="text-yellow-700 max-w-md">
                                {{ mensaje }}
                            </p>
                        </div>

                        <div class="mt-6 p-4 bg-white rounded-lg border border-yellow-200 max-w-lg">
                            <div class="flex items-start gap-3">
                                <Settings class="h-5 w-5 text-yellow-600 mt-0.5" />
                                <div class="text-left">
                                    <h4 class="font-medium text-yellow-800 mb-1">
                                        ¿Qué está pasando?
                                    </h4>
                                    <p class="text-sm text-yellow-700">
                                        Los administradores están configurando los campos del formulario de candidaturas. 
                                        Este proceso es necesario para definir qué información debes proporcionar en tu perfil.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200 max-w-lg">
                            <div class="text-left">
                                <h4 class="font-medium text-blue-800 mb-1">
                                    ¿Qué puedes hacer?
                                </h4>
                                <ul class="text-sm text-blue-700 space-y-1">
                                    <li>• Vuelve a intentar más tarde</li>
                                    <li>• Contacta con los administradores si es urgente</li>
                                    <li>• Mientras tanto, puedes explorar las votaciones disponibles</li>
                                </ul>
                            </div>
                        </div>

                        <div class="flex gap-3 mt-8">
                            <Button variant="outline" @click="$inertia.visit('/dashboard')">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Volver al Dashboard
                            </Button>
                            <Button @click="$inertia.reload()">
                                Intentar de Nuevo
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Información Adicional -->
            <div class="grid gap-4 md:grid-cols-2 mt-6">
                <Card>
                    <CardContent class="p-6">
                        <h3 class="font-semibold mb-2">¿Qué es una candidatura?</h3>
                        <p class="text-sm text-muted-foreground">
                            Tu perfil de candidatura es un formulario personalizado que contiene tu información 
                            personal, académica y profesional. Una vez aprobado, podrás usarlo para postularte 
                            a diferentes convocatorias sin tener que completar formularios adicionales.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <h3 class="font-semibold mb-2">Proceso de candidatura</h3>
                        <div class="space-y-2 text-sm text-muted-foreground">
                            <p>1. Completas tu perfil con la información requerida</p>
                            <p>2. Los administradores revisan y aprueban tu candidatura</p>
                            <p>3. Puedes usar tu perfil aprobado para postularte a convocatorias</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>