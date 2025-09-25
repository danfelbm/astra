<script setup lang="ts">
import AppLogo from "./AppLogo.vue";
import AppLogoIcon from "./AppLogoIcon.vue";
import { Avatar, AvatarFallback, AvatarImage } from "./ui/avatar";
import { Button } from "./ui/button";
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from "./ui/breadcrumb";
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from "./ui/dropdown-menu";
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from "./ui/navigation-menu";
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from "./ui/sheet";
import UserMenuContent from "./UserMenuContent.vue";
import { getInitials } from "@modules/Core/Resources/js/composables/useInitials";
import type { BreadcrumbItemType, NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { CheckSquare, FileText, FolderOpen, LayoutGrid, Menu, User, Users } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);

const isCurrentRoute = (url: string) => {
    return page.url === url;
};

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/miembro/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Votaciones',
        href: '/miembro/votaciones',
        icon: CheckSquare,
    },
    {
        title: 'Candidaturas',
        href: '/miembro/candidaturas',
        icon: User,
    },
    {
        title: 'Postulaciones',
        href: '/miembro/postulaciones',
        icon: FileText,
    },
    {
        title: 'Asambleas',
        href: '/miembro/asambleas',
        icon: Users,
    },
    {
        title: 'Mis Proyectos',
        href: '/miembro/mis-proyectos',
        icon: FolderOpen,
    },
    {
        title: 'Mis Contratos',
        href: '/miembro/mis-contratos',
        icon: FileText,
    },
    {
        title: 'Formularios',
        href: '/miembro/formularios',
        icon: FileText,
    },
];
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80">
            <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button variant="ghost" size="icon" class="mr-2 h-9 w-9">
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" class="w-[300px] p-6">
                            <SheetTitle class="sr-only">Navigation Menu</SheetTitle>
                            <SheetHeader class="flex justify-start text-left">
                                <AppLogoIcon class="size-6 fill-current text-black dark:text-white" />
                            </SheetHeader>
                            <nav class="-mx-3 space-y-1 py-6">
                                <Link
                                    v-for="item in mainNavItems"
                                    :key="item.title"
                                    :href="item.href"
                                    :class="[
                                        'flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-accent',
                                        isCurrentRoute(item.href) 
                                            ? 'bg-accent text-accent-foreground font-semibold' 
                                            : 'text-muted-foreground hover:text-accent-foreground'
                                    ]"
                                >
                                    <component v-if="item.icon" :is="item.icon" class="h-5 w-5" />
                                    {{ item.title }}
                                </Link>
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link :href="route('user.dashboard')" class="flex items-center gap-x-2">
                    <AppLogo class="hidden h-6 xl:block" />
                </Link>

                <!-- Desktop Menu -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <NavigationMenu class="ml-10">
                        <NavigationMenuList class="space-x-1">
                            <NavigationMenuItem v-for="item in mainNavItems" :key="item.href">
                                <NavigationMenuLink :as-child="true">
                                    <Link 
                                        :href="item.href"
                                        :class="[
                                            navigationMenuTriggerStyle(),
                                            'flex items-center gap-2 px-3 py-2',
                                            isCurrentRoute(item.href) 
                                                ? 'bg-accent text-accent-foreground' 
                                                : 'hover:bg-accent/50'
                                        ]"
                                    >
                                        <component v-if="item.icon" :is="item.icon" class="h-4 w-4" />
                                        {{ item.title }}
                                    </Link>
                                </NavigationMenuLink>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <!-- User Menu -->
                <div class="ml-auto">
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative h-10 w-10 rounded-full focus-within:ring-2 focus-within:ring-primary"
                            >
                                <Avatar class="h-8 w-8">
                                    <AvatarImage v-if="auth.user.avatar_url" :src="auth.user.avatar_url" :alt="auth.user.name" />
                                    <AvatarFallback class="bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white">
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <div v-if="props.breadcrumbs.length >= 1" class="flex w-full border-b border-sidebar-border/70">
            <div class="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl">
                <Breadcrumb>
                    <BreadcrumbList>
                        <template v-for="(breadcrumb, index) in props.breadcrumbs" :key="index">
                            <BreadcrumbItem>
                                <BreadcrumbLink 
                                    v-if="breadcrumb.href && breadcrumb.href !== '#' && index < props.breadcrumbs.length - 1"
                                    :as-child="true"
                                >
                                    <Link :href="breadcrumb.href">
                                        {{ breadcrumb.title }}
                                    </Link>
                                </BreadcrumbLink>
                                <BreadcrumbPage v-else>
                                    {{ breadcrumb.title }}
                                </BreadcrumbPage>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator v-if="index < props.breadcrumbs.length - 1" />
                        </template>
                    </BreadcrumbList>
                </Breadcrumb>
            </div>
        </div>
    </div>
</template>
