<script setup lang="ts">
import AppContent from "../components/AppContent.vue";
import AppShell from "../components/AppShell.vue";
import AppHeader from "../components/AppHeader.vue";
import UserTopbar from "../components/UserTopbar.vue";
import { Toaster } from "../components/ui/sonner";
import UpdateLocationModal from "../components/modals/UpdateLocationModal.vue";
import type { BreadcrumbItemType } from '@/types';
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
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

// Para usuarios regulares, la información de ubicación es más importante
const locationDataIncomplete = computed(() => {
    if (!user.value) return false;
    
    return !user.value.name ||
           !user.value.documento_identidad ||
           !user.value.territorio_id ||
           !user.value.departamento_id ||
           !user.value.municipio_id ||
           !user.value.telefono;
});

// Verificar si el usuario tiene acceso a admin (usando el campo del backend)
const hasAdminAccess = computed(() => {
    // Usar hasAdministrativeRole que viene del backend y verifica is_administrative
    return page.props.auth?.hasAdministrativeRole || false;
});
</script>

<template>
    <AppShell variant="header">
        <!-- Topbar con información de ubicación y ayuda -->
        <UserTopbar />
        
        <!-- Header horizontal para usuarios (sin sidebar) -->
        <AppHeader :breadcrumbs="breadcrumbs" />
        
        <!-- Contenido principal usando AppContent -->
        <AppContent variant="header">
            <div class="container mx-auto px-4 py-6">
                
                <!-- Mensaje de acceso a admin si corresponde -->
                <div
                    v-if="hasAdminAccess"
                    class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950"
                >
                    <div class="flex items-center">
                        <svg class="mr-2 h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            Tienes acceso administrativo. 
                            <Link 
                                :href="route('admin.dashboard')" 
                                class="font-medium underline hover:no-underline"
                            >
                                Ir al panel de administración
                            </Link>
                        </p>
                    </div>
                </div>
                
                <!-- Contenido de la página -->
                <slot />
            </div>
        </AppContent>
        
        <!-- Modal de actualización de ubicación (más prominente para usuarios) -->
        <UpdateLocationModal
            v-if="locationDataIncomplete"
            :open="locationDataIncomplete"
        />
        
        <!-- Notificaciones toast -->
        <Toaster />
    </AppShell>
</template>