<script setup lang="ts">
import AppLogo from "./AppLogo.vue";
import AppLogoIcon from "./AppLogoIcon.vue";
import { Avatar, AvatarFallback, AvatarImage } from "./ui/avatar";
import { Button } from "./ui/button";
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator, BreadcrumbEllipsis } from "./ui/breadcrumb";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "./ui/dropdown-menu";
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
import { usePermissions } from "@modules/Core/Resources/js/composables/usePermissions";
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
const { hasPermission, hasAnyPermission } = usePermissions();

const isCurrentRoute = (url: string) => {
    return page.url === url;
};

// Definir todos los elementos del menú con sus permisos requeridos
const allNavItems: Array<NavItem & { permission?: string | string[] }> = [
    {
        title: 'Dashboard',
        href: '/miembro/dashboard',
        icon: LayoutGrid,
        // Dashboard no requiere permiso específico, solo autenticación
    },
    {
        title: 'Votaciones',
        href: '/miembro/votaciones',
        icon: CheckSquare,
        permission: 'votaciones.view_public',
    },
    {
        title: 'Candidaturas',
        href: '/miembro/candidaturas',
        icon: User,
        permission: ['candidaturas.view_own', 'candidaturas.create_own'],
    },
    {
        title: 'Postulaciones',
        href: '/miembro/postulaciones',
        icon: FileText,
        permission: 'postulaciones.view_own',
    },
    {
        title: 'Asambleas',
        href: '/miembro/asambleas',
        icon: Users,
        permission: 'asambleas.view_public',
    },
    {
        title: 'Mis Proyectos',
        href: '/miembro/mis-proyectos',
        icon: FolderOpen,
        permission: 'proyectos.view_own',
    },
    {
        title: 'Mis Contratos',
        href: '/miembro/mis-contratos',
        icon: FileText,
        permission: 'contratos.view_own',
    },
    {
        title: 'Formularios',
        href: '/miembro/formularios',
        icon: FileText,
        permission: 'formularios.view_public',
    },
];

// Filtrar elementos del menú según los permisos del usuario
const mainNavItems = computed(() => {
    return allNavItems.filter(item => {
        // Si no requiere permiso específico, mostrarlo siempre
        if (!item.permission) {
            return true;
        }

        // Si es un array de permisos, verificar si tiene al menos uno
        if (Array.isArray(item.permission)) {
            return hasAnyPermission(item.permission);
        }

        // Si es un permiso único, verificarlo
        return hasPermission(item.permission);
    });
});
</script>

<template>
    <div>
        <div class="border-b border-border">
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

        <div v-if="props.breadcrumbs.length >= 1" class="flex w-full border-b border-border">
            <div class="mx-auto flex h-12 w-full items-center justify-start px-2 sm:px-4 text-neutral-500 md:max-w-7xl">
                <Breadcrumb>
                    <BreadcrumbList>
                        <template v-for="(breadcrumb, index) in props.breadcrumbs" :key="index">
                            <!-- Item del breadcrumb -->
                            <BreadcrumbItem
                                :class="[
                                    props.breadcrumbs.length > 3 && index > 0 && index < props.breadcrumbs.length - 1
                                        ? 'hidden md:inline-flex'
                                        : ''
                                ]"
                            >
                                <BreadcrumbLink
                                    v-if="breadcrumb.href && breadcrumb.href !== '#' && index < props.breadcrumbs.length - 1"
                                    :as-child="true"
                                >
                                    <Link :href="breadcrumb.href" class="max-w-[120px] sm:max-w-none truncate">
                                        {{ breadcrumb.title }}
                                    </Link>
                                </BreadcrumbLink>
                                <BreadcrumbPage v-else class="max-w-[150px] sm:max-w-none truncate">
                                    {{ breadcrumb.title }}
                                </BreadcrumbPage>
                            </BreadcrumbItem>

                            <!-- Separador normal (oculto en mobile para items intermedios) -->
                            <BreadcrumbSeparator
                                v-if="index < props.breadcrumbs.length - 1"
                                :class="[
                                    props.breadcrumbs.length > 3 && index >= 1 && index < props.breadcrumbs.length - 1
                                        ? 'hidden md:inline-flex'
                                        : '',
                                    props.breadcrumbs.length > 3 && index === 0 ? 'hidden md:inline-flex' : ''
                                ]"
                            />

                            <!-- Ellipsis dropdown para mobile (después del primer item) -->
                            <template v-if="props.breadcrumbs.length > 3 && index === 0">
                                <BreadcrumbSeparator class="md:hidden" />
                                <BreadcrumbItem class="md:hidden">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger class="flex items-center gap-1">
                                            <BreadcrumbEllipsis class="h-4 w-4" />
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="start">
                                            <DropdownMenuItem
                                                v-for="(hiddenBreadcrumb, hiddenIndex) in props.breadcrumbs.slice(1, -1)"
                                                :key="hiddenIndex"
                                                :as-child="true"
                                            >
                                                <Link
                                                    :href="hiddenBreadcrumb.href || '#'"
                                                    class="w-full"
                                                >
                                                    {{ hiddenBreadcrumb.title }}
                                                </Link>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </BreadcrumbItem>
                                <BreadcrumbSeparator class="md:hidden" />
                            </template>
                        </template>
                    </BreadcrumbList>
                </Breadcrumb>
            </div>
        </div>
    </div>
</template>
