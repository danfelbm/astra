<script setup lang="ts">
import { ResponsiveBreadcrumb } from "./ui/breadcrumb";
import { SidebarTrigger } from "./ui/sidebar";
import { usePage } from '@inertiajs/vue3';
import TenantSelector from "./TenantSelector.vue";
import type { BreadcrumbItemType, SharedData } from '@/types';

// Obtener datos del usuario y tenant
const page = usePage<SharedData>();
const user = page.props.auth.user;
const isSuperAdmin = page.props.auth.isSuperAdmin || false;
const currentTenant = (page.props as any).tenant?.current;
const availableTenants = (page.props as any).tenant?.available;

withDefaults(defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-border px-3 sm:px-6 transition-[width,height] ease-linear group-has-[[data-collapsible=icon]]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2 flex-1 min-w-0">
            <SidebarTrigger class="-ml-1 shrink-0" />
            <ResponsiveBreadcrumb
                v-if="breadcrumbs.length > 0"
                :items="breadcrumbs"
                class="flex-1 min-w-0"
            />
        </div>

        <!-- Tenant Selector para Super Admins -->
        <div class="flex items-center gap-4 shrink-0">
            <TenantSelector
                :isSuperAdmin="isSuperAdmin"
                :currentTenant="currentTenant"
                :availableTenants="availableTenants"
            />
        </div>
    </header>
</template>
