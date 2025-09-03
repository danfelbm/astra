<script lang="ts" setup>
import type { LabelProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { cn } from "@modules/Core/Resources/js/lib/utils"
import { Label } from '@modules/Core/Resources/js/components/ui/label'
import { useFormField } from "./useFormField"

const props = defineProps<LabelProps & { class?: HTMLAttributes["class"] }>()

const { error, formItemId } = useFormField()
</script>

<template>
  <Label
    data-slot="form-label"
    :data-error="!!error"
    :class="cn(
      'data-[error=true]:text-destructive-foreground',
      props.class,
    )"
    :for="formItemId"
  >
    <slot />
  </Label>
</template>
