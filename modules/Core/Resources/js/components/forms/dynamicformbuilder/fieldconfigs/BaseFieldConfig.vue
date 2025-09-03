<script setup lang="ts" generic="T extends Record<string, any>">
import type { FormField } from "@modules/Core/Resources/js/types/forms";

interface Props {
    modelValue: T;
    field: FormField;
    disabled?: boolean;
}

interface Emits {
    (e: 'update:modelValue', value: T): void;
}

defineProps<Props>();
defineEmits<Emits>();
</script>

<template>
    <!-- Componente base para configuraciones de campo -->
    <!-- Los componentes hijos deben implementar su propio template -->
    <slot />
</template>

<style scoped>
/* Estilos compartidos para configuraciones de campo */
</style>