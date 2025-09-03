<script setup lang="ts">
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { Head, router } from '@inertiajs/vue3';
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft } from 'lucide-vue-next';
import CsvImportWizard from "@modules/Core/Resources/js/components/imports/CsvImportWizard.vue";

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Usuarios', href: '/admin/usuarios' },
    { title: 'Nueva Importación', href: '#' },
];

// Navegar de vuelta
const goBack = () => {
    router.get('/admin/usuarios');
};
</script>

<template>
    <Head title="Nueva Importación CSV" />
    
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Nueva Importación CSV</h1>
                    <p class="text-muted-foreground">
                        Importa usuarios desde un archivo CSV
                    </p>
                </div>
                <Button variant="outline" @click="goBack">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver a Usuarios
                </Button>
            </div>

            <!-- Wizard de Importación -->
            <CsvImportWizard 
                mode="general"
                :redirect-on-success="true"
            />
        </div>
    </AdminLayout>
</template>