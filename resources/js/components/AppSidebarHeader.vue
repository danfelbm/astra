<script setup lang="ts">
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Link, usePage } from '@inertiajs/vue3';
import TenantSelector from '@/components/TenantSelector.vue';
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
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-[[data-collapsible=icon]]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs.length > 0">
                <Breadcrumb>
                    <BreadcrumbList>
                        <template v-for="(item, index) in breadcrumbs" :key="index">
                            <BreadcrumbItem>
                                <template v-if="index === breadcrumbs.length - 1">
                                    <BreadcrumbPage>{{ item.title }}</BreadcrumbPage>
                                </template>
                                <template v-else>
                                    <BreadcrumbLink as-child>
                                        <Link :href="item.href" class="transition-colors hover:text-foreground">
                                            {{ item.title }}
                                        </Link>
                                    </BreadcrumbLink>
                                </template>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" />
                        </template>
                    </BreadcrumbList>
                </Breadcrumb>
            </template>
        </div>

        <!-- Tenant Selector para Super Admins -->
        <div class="flex items-center gap-4">
            <TenantSelector 
                :isSuperAdmin="isSuperAdmin"
                :currentTenant="currentTenant"
                :availableTenants="availableTenants"
            />
        </div>
    </header>
</template>
