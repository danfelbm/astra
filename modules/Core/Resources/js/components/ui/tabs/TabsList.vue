<script setup lang="ts">
/**
 * TabsList - Lista de tabs con soporte responsive
 * En móvil muestra un Select dropdown, en desktop muestra los tabs normales
 */
import type { TabsListProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { ref, provide, inject } from "vue"
import { reactiveOmit, useMediaQuery } from "@vueuse/core"
import { TabsList } from "reka-ui"
import { cn } from "@modules/Core/Resources/js/lib/utils"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from "@modules/Core/Resources/js/components/ui/select"
import {
  TABS_CONTEXT_KEY,
  TABS_LIST_KEY,
  type TabInfo,
  type TabsContext,
  type TabsListContext
} from "./keys"

const props = defineProps<TabsListProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")

// Detectar si es móvil (menos de 640px = breakpoint sm de Tailwind)
const isMobile = useMediaQuery('(max-width: 639px)')

// Registro de tabs
const registeredTabs = ref<TabInfo[]>([])

// Inject el contexto de Tabs
const tabsContext = inject<TabsContext | null>(TABS_CONTEXT_KEY, null)

// Funciones para registrar/desregistrar tabs
const registerTab = (tab: TabInfo) => {
  // Evitar duplicados
  const exists = registeredTabs.value.find(t => t.value === tab.value)
  if (!exists) {
    registeredTabs.value.push(tab)
  }
}

const unregisterTab = (value: string) => {
  const index = registeredTabs.value.findIndex(t => t.value === value)
  if (index > -1) {
    registeredTabs.value.splice(index, 1)
  }
}

// Proveer funciones de registro a los TabsTrigger hijos
provide<TabsListContext>(TABS_LIST_KEY, {
  registerTab,
  unregisterTab,
  isMobile
})

// Handler para cambio en el Select
const handleSelectChange = (value: string) => {
  tabsContext?.updateValue(value)
}

// Obtener el tab actual para mostrar su label
const currentTab = () => {
  return registeredTabs.value.find(t => t.value === tabsContext?.modelValue.value)
}
</script>

<template>
  <!-- Select para móvil -->
  <div v-if="isMobile" class="w-full" data-slot="tabs-list-mobile">
    <Select
      :model-value="tabsContext?.modelValue.value"
      @update:model-value="handleSelectChange"
    >
      <SelectTrigger class="w-full">
        <SelectValue>
          <div class="flex items-center gap-2">
            <!-- Ícono: componente o HTML extraído -->
            <component
              v-if="currentTab()?.icon"
              :is="currentTab()?.icon"
              class="h-4 w-4"
            />
            <span
              v-else-if="currentTab()?.iconHtml"
              v-html="currentTab()?.iconHtml"
            />
            <span>{{ currentTab()?.label || 'Seleccionar...' }}</span>
            <span
              v-if="currentTab()?.badge"
              class="ml-auto bg-muted text-muted-foreground rounded-full px-2 py-0.5 text-xs"
            >
              {{ currentTab()?.badge }}
            </span>
          </div>
        </SelectValue>
      </SelectTrigger>
      <SelectContent>
        <SelectItem
          v-for="tab in registeredTabs"
          :key="tab.value"
          :value="tab.value"
          :disabled="tab.disabled"
        >
          <div class="flex items-center gap-2">
            <!-- Ícono: componente o HTML extraído -->
            <component
              v-if="tab.icon"
              :is="tab.icon"
              class="h-4 w-4"
            />
            <span
              v-else-if="tab.iconHtml"
              v-html="tab.iconHtml"
            />
            <span>{{ tab.label }}</span>
            <span
              v-if="tab.badge"
              class="ml-auto bg-secondary text-secondary-foreground rounded-full px-2 py-0.5 text-xs"
            >
              {{ tab.badge }}
            </span>
          </div>
        </SelectItem>
      </SelectContent>
    </Select>
  </div>

  <!--
    TabsList normal - siempre montado para que los TabsTrigger estén dentro
    del RovingFocusGroup. Se oculta visualmente en móvil con v-show.
  -->
  <TabsList
    v-show="!isMobile"
    data-slot="tabs-list"
    v-bind="delegatedProps"
    :class="cn(
      'bg-muted text-muted-foreground inline-flex h-9 w-fit items-center justify-center rounded-lg p-[3px]',
      props.class,
    )"
  >
    <slot />
  </TabsList>
</template>
