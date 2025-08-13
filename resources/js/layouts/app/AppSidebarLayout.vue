<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import UpdateLocationModal from '@/components/modals/UpdateLocationModal.vue';
import type { BreadcrumbItemType } from '@/types';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import 'vue-sonner/style.css';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// Obtener datos del usuario actual
const page = usePage();
const user = computed(() => page.props.auth?.user);

// Verificar si el usuario necesita completar su información de ubicación
// Nombre, documento_identidad, territorio, departamento, municipio y teléfono son obligatorios
// Localidad es opcional (no bloquea si falta)
const locationDataIncomplete = computed(() => {
    if (!user.value) return false; // Si no hay usuario, no mostrar modal
    
    return !user.value.name || 
           !user.value.documento_identidad || 
           !user.value.territorio_id || 
           !user.value.departamento_id || 
           !user.value.municipio_id || 
           !user.value.telefono;
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <Toaster class="pointer-events-auto" />
        
        <!-- Modal obligatorio para actualizar ubicación -->
        <UpdateLocationModal :open="locationDataIncomplete" />
    </AppShell>
</template>
