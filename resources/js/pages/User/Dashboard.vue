<script setup lang="ts">
import UserLayout from "@/layouts/UserLayout.vue";
import { Head } from '@inertiajs/vue3';

interface Props {
    hasAssemblyAccess: boolean;
    // Props de permisos de usuario
    canViewDashboard: boolean;
}

defineProps<Props>();
</script>

<template>
    <Head title="Dashboard" />

    <UserLayout>
        <div class="flex h-full flex-1 flex-col rounded-xl p-4">
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
        </div>
    </UserLayout>
</template>
