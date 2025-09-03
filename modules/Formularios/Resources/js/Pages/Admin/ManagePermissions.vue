<script setup lang="ts">
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Alert, AlertDescription, AlertTitle } from "@modules/Core/Resources/js/components/ui/alert";
import { AlertCircle } from 'lucide-vue-next';
import type { BreadcrumbItem } from "@modules/Core/Resources/js/types";

const props = defineProps<{
    formulario?: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Formularios', href: '/admin/formularios' },
    { title: 'Gestionar Permisos', href: '#' }
];
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Gestionar Permisos - Formulario" />
        
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Card>
                <CardHeader>
                    <CardTitle>Gestionar Permisos del Formulario</CardTitle>
                    <CardDescription>
                        Configuración de permisos para el formulario
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Alert>
                        <AlertCircle class="h-4 w-4" />
                        <AlertTitle>En desarrollo</AlertTitle>
                        <AlertDescription>
                            Esta funcionalidad está en desarrollo y estará disponible próximamente.
                        </AlertDescription>
                    </Alert>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>