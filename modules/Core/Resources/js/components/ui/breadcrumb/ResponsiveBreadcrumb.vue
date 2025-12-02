<script setup lang="ts">
/**
 * ResponsiveBreadcrumb - Breadcrumb con soporte responsive
 * En móvil muestra un Select dropdown, en desktop muestra los breadcrumbs normales
 */
import type { HTMLAttributes } from 'vue'
import { computed } from 'vue'
import { useMediaQuery } from '@vueuse/core'
import { Link, router } from '@inertiajs/vue3'
import { ChevronRight, Home } from 'lucide-vue-next'
import { cn } from '@modules/Core/Resources/js/lib/utils'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from '@modules/Core/Resources/js/components/ui/select'

// Tipo para los items de breadcrumb
export interface BreadcrumbItemData {
    title: string
    href: string
}

interface Props {
    items: BreadcrumbItemData[]
    class?: HTMLAttributes['class']
    // Mostrar ícono de Home en el primer item (desktop)
    showHomeIcon?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    items: () => [],
    showHomeIcon: false
})

// Detectar si es móvil (menos de 640px = breakpoint sm de Tailwind)
const isMobile = useMediaQuery('(max-width: 639px)')

// El item actual es el último del array
const currentItem = computed(() => {
    return props.items.length > 0 ? props.items[props.items.length - 1] : null
})

// Items navegables (todos excepto el último que es la página actual)
const navigableItems = computed(() => {
    return props.items.slice(0, -1)
})

// Handler para navegación desde el Select
const handleSelectChange = (href: string) => {
    if (href && href !== '#') {
        router.visit(href)
    }
}

// Valor actual para el Select (el href del item actual)
const currentValue = computed(() => {
    return currentItem.value?.href || ''
})
</script>

<template>
    <!-- Versión móvil: Select dropdown -->
    <div v-if="isMobile && items.length > 0" :class="cn('w-full', props.class)">
        <Select
            :model-value="currentValue"
            @update:model-value="handleSelectChange"
        >
            <SelectTrigger class="w-full h-9 text-sm" size="sm">
                <SelectValue>
                    <div class="flex items-center gap-2 truncate">
                        <ChevronRight class="h-3.5 w-3.5 text-muted-foreground shrink-0" />
                        <span class="truncate">{{ currentItem?.title }}</span>
                    </div>
                </SelectValue>
            </SelectTrigger>
            <SelectContent align="start">
                <!-- Mostrar todos los items como opciones navegables -->
                <SelectItem
                    v-for="(item, index) in items"
                    :key="index"
                    :value="item.href"
                    :disabled="index === items.length - 1"
                >
                    <div class="flex items-center gap-2">
                        <!-- Indicador de profundidad -->
                        <span
                            v-if="index > 0"
                            class="text-muted-foreground"
                            :style="{ paddingLeft: `${(index) * 8}px` }"
                        >
                            <ChevronRight class="h-3 w-3 inline" />
                        </span>
                        <Home v-else-if="showHomeIcon" class="h-3.5 w-3.5" />
                        <span :class="{ 'font-medium': index === items.length - 1 }">
                            {{ item.title }}
                        </span>
                    </div>
                </SelectItem>
            </SelectContent>
        </Select>
    </div>

    <!-- Versión desktop: Breadcrumb tradicional -->
    <nav
        v-else-if="items.length > 0"
        aria-label="breadcrumb"
        :class="props.class"
    >
        <ol class="flex flex-wrap items-center gap-1.5 break-words text-sm text-muted-foreground sm:gap-2.5">
            <template v-for="(item, index) in items" :key="index">
                <!-- Item del breadcrumb -->
                <li class="inline-flex items-center gap-1.5">
                    <!-- Último item: página actual (no clickeable) -->
                    <span
                        v-if="index === items.length - 1"
                        role="link"
                        aria-disabled="true"
                        aria-current="page"
                        class="font-normal text-foreground"
                    >
                        {{ item.title }}
                    </span>
                    <!-- Items intermedios: links clickeables -->
                    <Link
                        v-else
                        :href="item.href"
                        class="transition-colors hover:text-foreground inline-flex items-center gap-1"
                    >
                        <Home v-if="index === 0 && showHomeIcon" class="h-3.5 w-3.5" />
                        {{ item.title }}
                    </Link>
                </li>
                <!-- Separador (excepto después del último item) -->
                <li
                    v-if="index !== items.length - 1"
                    role="presentation"
                    aria-hidden="true"
                    class="[&>svg]:h-3.5 [&>svg]:w-3.5"
                >
                    <ChevronRight />
                </li>
            </template>
        </ol>
    </nav>
</template>
