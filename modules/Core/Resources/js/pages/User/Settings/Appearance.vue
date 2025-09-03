<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from "../../../components/AppearanceTabs.vue";
import HeadingSmall from "../../../components/HeadingSmall.vue";
import { type BreadcrumbItem } from '@/types';

import UserLayout from "@modules/Core/Resources/js/layouts/UserLayout.vue";
import SettingsLayout from "@modules/Core/Resources/js/layouts/settings/Layout.vue";

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: '/miembro/settings/appearance',
    },
];
</script>

<template>
    <UserLayout :breadcrumbs="breadcrumbItems">
        <Head title="Appearance settings" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Appearance settings" description="Update your account's appearance settings" />
                <AppearanceTabs />
            </div>
        </SettingsLayout>
    </UserLayout>
</template>
