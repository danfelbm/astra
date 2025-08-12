<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    class?: string;
}

defineProps<Props>();

const page = usePage();
const logoDisplay = computed(() => page.props.config?.['app.logo_display'] || 'logo_text');
const logoText = computed(() => page.props.config?.['app.logo_text'] || 'Laravel Starter Kit');
const logoFile = computed(() => page.props.config?.['app.logo_file']);
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md text-sidebar-primary-foreground">
        <img 
            v-if="logoFile" 
            :src="`/storage/${logoFile}`" 
            alt="Logo" 
            class="object-contain"
        />
        <AppLogoIcon v-else class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div v-if="logoDisplay === 'logo_text'" class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate font-semibold leading-none">{{ logoText }}</span>
    </div>
</template>
