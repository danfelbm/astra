<script setup lang="ts">
import type { ProgressRootProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { computed } from "vue"
import {
  ProgressIndicator,
  ProgressRoot,

} from "reka-ui"
import { cn } from "@modules/Core/Resources/js/lib/utils"

interface ProgressProps extends ProgressRootProps {
  class?: HTMLAttributes["class"]
  indicatorClass?: HTMLAttributes["class"]
}

const props = withDefaults(
  defineProps<ProgressProps>(),
  {
    modelValue: 0,
  },
)

const delegatedProps = reactiveOmit(props, "class", "indicatorClass")

// Clases de color dinámicas según el porcentaje
const indicatorColorClass = computed(() => {
  const value = props.modelValue ?? 0

  if (value === 0) {
    return 'bg-gray-500'
  } else if (value < 30) {
    return 'bg-red-500'
  } else if (value < 60) {
    return 'bg-orange-500'
  } else if (value < 80) {
    return 'bg-yellow-500'
  } else if (value < 100) {
    return 'bg-blue-500'
  } else {
    return 'bg-green-500'
  }
})

const backgroundColorClass = computed(() => {
  const value = props.modelValue ?? 0

  if (value === 0) {
    return 'bg-gray-500/20'
  } else if (value < 30) {
    return 'bg-red-500/20'
  } else if (value < 60) {
    return 'bg-orange-500/20'
  } else if (value < 80) {
    return 'bg-yellow-500/20'
  } else if (value < 100) {
    return 'bg-blue-500/20'
  } else {
    return 'bg-green-500/20'
  }
})
</script>

<template>
  <ProgressRoot
    data-slot="progress"
    v-bind="delegatedProps"
    :class="
      cn(
        'relative h-2 w-full overflow-hidden rounded-full',
        backgroundColorClass,
        props.class,
      )
    "
  >
    <ProgressIndicator
      data-slot="progress-indicator"
      :class="cn(
        'h-full w-full flex-1 transition-all',
        indicatorColorClass,
        props.indicatorClass
      )"
      :style="`transform: translateX(-${100 - (props.modelValue ?? 0)}%);`"
    />
  </ProgressRoot>
</template>
