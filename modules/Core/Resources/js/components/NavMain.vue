<script setup lang="ts">
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "./ui/collapsible";
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem, SidebarMenuSub, SidebarMenuSubButton, SidebarMenuSubItem } from "./ui/sidebar";
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import type { Component } from 'vue';
import { ref } from 'vue';

interface NavItem {
    title: string;
    url?: string;
    icon: Component;
    isCollapsible?: boolean;
    items?: NavItem[];
}

const props = defineProps<{
    items: NavItem[];
}>();

const page = usePage<SharedData>();

// Estado para controlar qué secciones están abiertas
const openSections = ref<Record<string, boolean>>({});

// Función para verificar si una URL está activa
const isActiveUrl = (url: string | undefined): boolean => {
    if (!url) return false;
    return page.url === url || page.url.startsWith(url + '/');
};

// Función para verificar si una sección contiene la página activa
const sectionContainsActive = (item: NavItem): boolean => {
    if (item.url && isActiveUrl(item.url)) return true;
    if (item.items) {
        return item.items.some(subItem => isActiveUrl(subItem.url));
    }
    return false;
};

// Inicializar secciones abiertas basado en la URL actual
props.items.forEach(item => {
    if (item.isCollapsible && sectionContainsActive(item)) {
        openSections.value[item.title] = true;
    }
});
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Sistema de Votaciones</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <!-- Item colapsable con subitems -->
                <SidebarMenuItem v-if="item.isCollapsible && item.items">
                    <Collapsible 
                        v-model:open="openSections[item.title]"
                        :default-open="sectionContainsActive(item)"
                    >
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton 
                                class="w-full"
                                :is-active="sectionContainsActive(item)"
                            >
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                                <ChevronRight 
                                    class="ml-auto transition-transform" 
                                    :class="{ 'rotate-90': openSections[item.title] }"
                                />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem 
                                    v-for="subItem in item.items" 
                                    :key="subItem.title"
                                >
                                    <SidebarMenuSubButton 
                                        as-child 
                                        :is-active="isActiveUrl(subItem.url)"
                                    >
                                        <Link :href="subItem.url" class="flex items-center gap-2 w-full min-w-0 overflow-hidden">
                                            <component :is="subItem.icon" class="h-4 w-4 shrink-0" />
                                            <span class="block truncate flex-1 min-w-0">{{ subItem.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </Collapsible>
                </SidebarMenuItem>
                
                <!-- Item simple con link directo -->
                <SidebarMenuItem v-else>
                    <SidebarMenuButton 
                        as-child 
                        :is-active="isActiveUrl(item.url)"
                    >
                        <Link :href="item.url">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
