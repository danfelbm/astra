<script setup lang="ts">
import { SidebarGroup, SidebarGroupContent, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from "./ui/sidebar";
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';

interface Props {
    items: NavItem[];
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <SidebarGroup :class="`group-data-[collapsible=icon]:p-0 ${$props.class || ''}`">
        <SidebarGroupContent>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in items" :key="item.title">
                    <!-- Enlaces internos usando Inertia -->
                    <SidebarMenuButton
                        v-if="item.isInternal"
                        class="text-sidebar-foreground/70 hover:text-sidebar-foreground cursor-pointer"
                        as-child
                    >
                        <Link :href="item.url || item.href">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                    
                    <!-- Enlaces externos -->
                    <SidebarMenuButton
                        v-else
                        class="text-sidebar-foreground/70 hover:text-sidebar-foreground"
                        as-child
                    >
                        <a :href="item.href || item.url" target="_blank" rel="noopener noreferrer">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
