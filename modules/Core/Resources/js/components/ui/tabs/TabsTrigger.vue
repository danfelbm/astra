<script setup lang="ts">
/**
 * TabsTrigger - Botón de tab que se registra con TabsList
 * Props opcionales para móvil: label, icon, badge
 */
import type { TabsTriggerProps } from "reka-ui"
import type { HTMLAttributes, Component } from "vue"
import { inject, onMounted, onUnmounted, ref } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { TabsTrigger, useForwardProps } from "reka-ui"
import { cn } from "@modules/Core/Resources/js/lib/utils"
import { TABS_LIST_KEY, type TabsListContext } from "./keys"

interface Props extends TabsTriggerProps {
  class?: HTMLAttributes["class"]
  // Props opcionales para el Select en móvil
  label?: string
  icon?: Component
  badge?: string | number
}

const props = defineProps<Props>()

const delegatedProps = reactiveOmit(props, "class", "label", "icon", "badge")
const forwardedProps = useForwardProps(delegatedProps)

// Inject las funciones de registro del TabsList padre
const tabsListContext = inject<TabsListContext | null>(TABS_LIST_KEY, null)

// Referencia al componente (no al elemento DOM directamente)
const triggerRef = ref<InstanceType<typeof TabsTrigger> | null>(null)
const extractedLabel = ref<string>('')
const extractedIconHtml = ref<string>('')

// Extraer label del contenido si no se pasó como prop
const getLabel = (): string => {
  if (props.label) return props.label
  if (extractedLabel.value) return extractedLabel.value
  return props.value
}

// Registrar el tab al montarse
onMounted(() => {
  // Obtener el elemento DOM desde la instancia del componente
  const el = (triggerRef.value as any)?.$el as HTMLElement | undefined

  // Intentar extraer el texto y el ícono del slot después del montaje
  if (el) {
    // Extraer texto si no hay label prop
    if (!props.label) {
      const text = el.textContent?.trim() || ''
      // Limpiar texto (quitar badges numéricos al final)
      extractedLabel.value = text.replace(/\d+$/, '').trim()
    }

    // Extraer ícono SVG si no hay icon prop
    if (!props.icon) {
      const svg = el.querySelector('svg')
      if (svg) {
        // Clonar el SVG y asegurar clases consistentes para el Select
        const clonedSvg = svg.cloneNode(true) as SVGElement
        clonedSvg.setAttribute('class', 'h-4 w-4 shrink-0')
        extractedIconHtml.value = clonedSvg.outerHTML
      }
    }
  }

  if (tabsListContext) {
    tabsListContext.registerTab({
      value: props.value,
      label: getLabel(),
      icon: props.icon,
      iconHtml: extractedIconHtml.value || undefined,
      badge: props.badge,
      disabled: props.disabled
    })
  }
})

// Desregistrar al desmontarse
onUnmounted(() => {
  if (tabsListContext) {
    tabsListContext.unregisterTab(props.value)
  }
})
</script>

<template>
  <TabsTrigger
    ref="triggerRef"
    data-slot="tabs-trigger"
    v-bind="forwardedProps"
    :class="cn(
      `data-[state=active]:bg-background dark:data-[state=active]:text-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:outline-ring dark:data-[state=active]:border-input dark:data-[state=active]:bg-input/30 text-foreground dark:text-muted-foreground inline-flex h-[calc(100%-1px)] flex-1 items-center justify-center gap-1.5 rounded-md border border-transparent px-2 py-1 text-sm font-medium whitespace-nowrap transition-[color,box-shadow] focus-visible:ring-[3px] focus-visible:outline-1 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:shadow-sm [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4`,
      props.class,
    )"
  >
    <slot />
  </TabsTrigger>
</template>
