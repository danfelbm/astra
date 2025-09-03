<script setup lang="ts">
import AuthLayout from "@modules/Core/Resources/js/layouts/auth/AuthSimpleLayout.vue";

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <AuthLayout :title="title" :description="description">
        <slot />
    </AuthLayout>
</template>
