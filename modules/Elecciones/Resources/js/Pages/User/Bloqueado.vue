<script setup lang="ts">
import { Card, CardContent } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { type BreadcrumbItemType } from "@modules/Core/Resources/js/types";
import UserLayout from "@modules/Core/Resources/js/layouts/UserLayout.vue";
import { Head, Link } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft } from 'lucide-vue-next';

interface Props {
    titulo: string;
    mensaje: string;
    tipo: 'crear' | 'editar';
    candidatura_id?: number;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Dashboard', href: '/miembro/dashboard' },
    { title: 'Mi Candidatura', href: '/miembro/candidaturas' },
    { title: props.tipo === 'crear' ? 'Crear' : 'Editar', href: '#' },
];

const accionTexto = props.tipo === 'crear' ? 'Crear Candidatura' : 'Editar Candidatura';
</script>

<template>
    <Head :title="accionTexto" />

    <UserLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">{{ accionTexto }}</h1>
                    <p class="text-muted-foreground">
                        Sistema temporalmente no disponible
                    </p>
                </div>
            </div>

            <!-- Mensaje de Bloqueo -->
            <div class="max-w-3xl mx-auto w-full">
                <Card class="border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-950/20">
                    <CardContent class="p-8">
                        <div class="flex flex-col items-center text-center">
                            <div class="mb-4 p-3 rounded-full bg-yellow-100 dark:bg-yellow-900/30">
                                <AlertCircle class="h-12 w-12 text-yellow-600" />
                            </div>
                            
                            <h2 class="text-2xl font-semibold mb-3 text-yellow-800 dark:text-yellow-200">
                                {{ titulo }}
                            </h2>
                            
                            <p class="text-yellow-700 dark:text-yellow-300 mb-6 whitespace-pre-wrap max-w-2xl">
                                {{ mensaje }}
                            </p>

                            <Link href="/miembro/candidaturas">
                                <Button variant="outline" size="lg">
                                    <ArrowLeft class="mr-2 h-4 w-4" />
                                    Volver al Dashboard
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <!-- Información adicional -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-muted-foreground">
                        Si crees que esto es un error o necesitas asistencia urgente,
                        <br />
                        por favor contacta al administrador del sistema.
                    </p>
                </div>
            </div>
        </div>
    </UserLayout>
</template>