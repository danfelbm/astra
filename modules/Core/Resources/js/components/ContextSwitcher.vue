<script setup lang="ts">
import { Button } from "./ui/button";
import { Shield, User, ExternalLink } from 'lucide-vue-next';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const currentContext = computed(() => {
    const path = window.location.pathname;
    if (path.startsWith('/admin')) return 'admin';
    if (path.startsWith('/miembro')) return 'user';
    return 'guest';
});

const canAccessAdmin = computed(() => {
    // Usar hasAdministrativeRole que viene del backend y verifica is_administrative
    return page.props.auth?.hasAdministrativeRole || false;
});

const canAccessUser = computed(() => {
    return user.value?.roles?.some((role: any) => role.name === 'user');
});

const availableContexts = computed(() => {
    const contexts = [];
    if (canAccessAdmin.value) contexts.push('admin');
    if (canAccessUser.value) contexts.push('user');
    return contexts;
});
</script>

<template>
    <div v-if="availableContexts.length > 1" class="flex gap-2">
        <Button
            v-if="canAccessAdmin && currentContext !== 'admin'"
            variant="outline"
            size="sm"
            as-child
        >
            <Link href="/admin/dashboard" class="flex items-center">
                <Shield class="h-4 w-4 mr-2" />
                Panel Admin
            </Link>
        </Button>
        
        <Button
            v-if="canAccessUser && currentContext !== 'user'"
            variant="outline"
            size="sm"
            as-child
        >
            <Link href="/miembro/dashboard" class="flex items-center">
                <User class="h-4 w-4 mr-2" />
                Vista Usuario
            </Link>
        </Button>
    </div>
</template>