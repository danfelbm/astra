<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { FileText, ExternalLink, MoreHorizontal } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

defineProps<{
    name?: string;
}>();
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 rounded-xl p-4">
            <!-- Hero Banner -->
            <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-8">
                <div class="relative z-10">
                    <h1 class="text-4xl font-bold mb-4 text-gray-900 dark:text-white">
                        ¡Te damos la bienvenida al Sistema de Votaciones!
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl">
                        Explora las herramientas disponibles para gestionar candidaturas, participar en convocatorias y administrar el sistema electoral digital.
                    </p>
                </div>
                <div class="absolute top-0 right-0 w-64 h-64 bg-blue-100 dark:bg-blue-900 opacity-20 rounded-full -translate-y-32 translate-x-32"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-blue-50 dark:bg-blue-800 opacity-30 rounded-full translate-y-24 -translate-x-24"></div>
            </div>

            <!-- Cards Grid -->
            <div class="grid gap-6 md:grid-cols-3">
                <!-- Primera Card: Crear Candidatura -->
                <Card class="border-l-4 border-l-green-500 hover:shadow-lg transition-shadow cursor-pointer">
                    <Link href="/candidaturas/create" class="block h-full">
                        <CardHeader class="pb-3">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-green-100 rounded-lg dark:bg-green-900">
                                    <FileText class="h-6 w-6 text-green-600 dark:text-green-400" />
                                </div>
                                <div>
                                    <CardTitle class="text-lg text-green-700 dark:text-green-400">
                                        Crear candidatura
                                    </CardTitle>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <CardDescription class="text-sm">
                                Crea y gestiona tu perfil de candidatura con información completa para participar en procesos electorales.
                            </CardDescription>
                            <Button variant="outline" size="sm" class="mt-4 w-full group">
                                Comenzar
                                <FileText class="ml-2 h-4 w-4 group-hover:scale-110 transition-transform" />
                            </Button>
                        </CardContent>
                    </Link>
                </Card>

                <!-- Segunda Card: Documentación -->
                <Card class="border-l-4 border-l-blue-500 hover:shadow-lg transition-shadow cursor-pointer">
                    <a href="https://colombiahumana.co/postulaciones" target="_blank" rel="noopener noreferrer" class="block h-full">
                        <CardHeader class="pb-3">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-blue-100 rounded-lg dark:bg-blue-900">
                                    <ExternalLink class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <CardTitle class="text-lg text-blue-700 dark:text-blue-400">
                                        Consultar documentación sobre este proceso
                                    </CardTitle>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <CardDescription class="text-sm">
                                Accede a la documentación oficial para conocer más detalles sobre los procesos de postulación y convocatorias.
                            </CardDescription>
                            <Button variant="outline" size="sm" class="mt-4 w-full group">
                                Ver documentación
                                <ExternalLink class="ml-2 h-4 w-4 group-hover:scale-110 transition-transform" />
                            </Button>
                        </CardContent>
                    </a>
                </Card>

                <!-- Tercer espacio vacío -->
                <div class="h-full min-h-[200px]">
                    <!-- Espacio en blanco intencional -->
                </div>
            </div>
        </div>
    </AppLayout>
</template>
