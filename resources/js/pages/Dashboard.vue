<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { FileText, ExternalLink, MoreHorizontal, Users, Mail } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const props = defineProps<{
    name?: string;
    hasAssemblyAccess?: boolean;
}>();
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 rounded-xl p-4">
            <!-- Hero Banner -->
            <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4 sm:p-6 md:p-8">
                <div class="relative z-10">
                    <h1 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold mb-2 sm:mb-3 md:mb-4 text-gray-900 dark:text-white">
                        Bienvenido a la plataforma de participación en línea
                    </h1>
                    <p class="text-sm sm:text-base md:text-lg text-gray-600 dark:text-gray-300 max-w-2xl">
                        Explora las herramientas disponibles para gestionar candidaturas, participar en convocatorias y asambleas del movimiento.
                    </p>
                </div>
                <div class="absolute top-0 right-0 w-32 sm:w-48 md:w-64 h-32 sm:h-48 md:h-64 bg-blue-100 dark:bg-blue-900 opacity-20 rounded-full -translate-y-16 sm:-translate-y-24 md:-translate-y-32 translate-x-16 sm:translate-x-24 md:translate-x-32"></div>
                <div class="absolute bottom-0 left-0 w-24 sm:w-32 md:w-48 h-24 sm:h-32 md:h-48 bg-blue-50 dark:bg-blue-800 opacity-30 rounded-full translate-y-12 sm:translate-y-16 md:translate-y-24 -translate-x-12 sm:-translate-x-16 md:-translate-x-24"></div>
            </div>

            <!-- Cards Grid -->
            <div class="grid gap-4 sm:gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 max-w-6xl">
                <!-- Primera Card: Acceso a Asamblea -->
                <Card :class="[
                    'border-l-4 transition-shadow',
                    props.hasAssemblyAccess
                        ? 'border-l-purple-500 hover:shadow-lg cursor-pointer'
                        : 'border-l-gray-300 opacity-60 cursor-not-allowed'
                ]">
                    <Link v-if="props.hasAssemblyAccess" href="/asambleas/1" class="block h-full">
                        <CardHeader class="pb-3">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-purple-100 rounded-lg dark:bg-purple-900">
                                    <Users class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                                </div>
                                <div>
                                    <CardTitle class="text-lg text-purple-700 dark:text-purple-400">
                                        Asamblea Nacional
                                    </CardTitle>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <CardDescription class="text-sm">
                                Accede a la Asamblea Nacional para participar en las discusiones y votaciones.
                            </CardDescription>
                            <Button variant="outline" size="sm" class="mt-4 w-full group">
                                Ingresar
                                <Users class="ml-2 h-4 w-4 group-hover:scale-110 transition-transform" />
                            </Button>
                        </CardContent>
                    </Link>
                    <div v-else class="block h-full">
                        <CardHeader class="pb-3">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-gray-100 rounded-lg dark:bg-gray-800">
                                    <Users class="h-6 w-6 text-gray-400" />
                                </div>
                                <div>
                                    <CardTitle class="text-lg text-gray-500">
                                        Asamblea Nacional
                                    </CardTitle>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <CardDescription class="text-sm text-gray-400">
                                No tienes acceso a esta asamblea. Contacta a soporte@colombiahumana.co para solicitar acceso.
                            </CardDescription>
                            <Button variant="outline" size="sm" class="mt-4 w-full" disabled>
                                <Mail class="mr-2 h-4 w-4" />
                                Contactar soporte
                            </Button>
                        </CardContent>
                    </div>
                </Card>

                <!-- Segunda Card: Crear Candidatura -->
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

                <!-- Tercera Card: Documentación -->
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
            </div>
        </div>
    </AppLayout>
</template>
