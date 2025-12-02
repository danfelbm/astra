<script setup lang="ts">
/**
 * Tabs - Componente raíz de tabs
 * Provee el contexto para que TabsList pueda mostrar Select en móvil
 */
import type { TabsRootEmits, TabsRootProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { provide, computed } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { TabsRoot, useForwardPropsEmits } from "reka-ui"
import { cn } from "@modules/Core/Resources/js/lib/utils"
import { TABS_CONTEXT_KEY, type TabsContext } from "./keys"

const props = defineProps<TabsRootProps & { class?: HTMLAttributes["class"] }>()
const emits = defineEmits<TabsRootEmits>()

const delegatedProps = reactiveOmit(props, "class")
const forwarded = useForwardPropsEmits(delegatedProps, emits)

// Proveer el contexto del valor actual y función para cambiarlo
provide<TabsContext>(TABS_CONTEXT_KEY, {
  modelValue: computed(() => props.modelValue ?? props.defaultValue),
  updateValue: (value: string) => {
    emits('update:modelValue', value)
  }
})
</script>

<template>
  <TabsRoot
    data-slot="tabs"
    v-bind="forwarded"
    :class="cn('flex flex-col gap-2', props.class)"
  >
    <slot />
  </TabsRoot>
</template>
