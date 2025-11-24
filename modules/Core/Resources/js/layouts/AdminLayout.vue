<script setup lang="ts">
import AppContent from "../components/AppContent.vue";
import AppShell from "../components/AppShell.vue";
import AppSidebar from "../components/AppSidebar.vue";
import AppSidebarHeader from "../components/AppSidebarHeader.vue";
import { Toaster } from "../components/ui/sonner";
import UpdateLocationModal from "../components/modals/UpdateLocationModal.vue";
import type { BreadcrumbItemType } from '@/types';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useFlashMessages } from '../composables/useFlashMessages';
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

// Activar sistema de mensajes flash
useFlashMessages();

// Verificar si el usuario necesita completar su información de ubicación
// Para administradores, esto es menos crítico pero aún útil
const locationDataIncomplete = computed(() => {
    if (!user.value) return false;
    
    return !user.value.nombre ||
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
        
        <!-- Botón flotante "Ver como usuario" eliminado - Ya está disponible en el menú lateral -->
        
        <!-- Modal de actualización de ubicación (menos intrusivo para admins) -->
        <UpdateLocationModal
            v-if="locationDataIncomplete && !$page.url.startsWith('/admin/usuarios')"
            :show="false"
        />
        
        <!-- Notificaciones toast -->
        <!-- Notificaciones toast -->
        <Toaster :duration="6000" rich-colors close-button />
    </AppShell>
</template>