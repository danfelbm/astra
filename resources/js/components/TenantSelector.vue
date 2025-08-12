<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { Building2, Check, ChevronDown } from 'lucide-vue-next';

interface Tenant {
    id: number;
    name: string;
    subdomain: string;
    active: boolean;
    subscription_plan: string;
}

interface Props {
    currentTenant?: Tenant;
    availableTenants?: Tenant[];
    isSuperAdmin: boolean;
}

const props = defineProps<Props>();

const isLoading = ref(false);

const planBadgeVariant = computed(() => {
    if (!props.currentTenant) return 'outline';
    
    const variants: Record<string, string> = {
        'basic': 'secondary',
        'professional': 'default',
        'enterprise': 'destructive',
    };
    return variants[props.currentTenant.subscription_plan] || 'outline';
});

const planLabel = computed(() => {
    if (!props.currentTenant) return '';
    
    const labels: Record<string, string> = {
        'basic': 'Básico',
        'professional': 'Profesional',
        'enterprise': 'Empresarial',
    };
    return labels[props.currentTenant.subscription_plan] || props.currentTenant.subscription_plan;
});

const handleTenantSwitch = async (tenantId: number) => {
    if (!props.isSuperAdmin || isLoading.value) return;
    
    isLoading.value = true;
    
    try {
        await router.post('/admin/tenants/switch', {
            tenant_id: tenantId,
        }, {
            preserveState: true,
            onFinish: () => {
                isLoading.value = false;
                // Recargar la página para reflejar el cambio de tenant
                window.location.reload();
            },
            onError: () => {
                isLoading.value = false;
            }
        });
    } catch (error) {
        console.error('Error switching tenant:', error);
        isLoading.value = false;
    }
};
</script>

<template>
    <div v-if="isSuperAdmin && currentTenant" class="flex items-center gap-2">
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" class="flex items-center gap-2 h-auto py-2 px-3">
                    <Building2 class="h-4 w-4" />
                    <div class="flex flex-col items-start text-left">
                        <span class="text-sm font-medium">{{ currentTenant.name }}</span>
                        <div class="flex items-center gap-1">
                            <Badge 
                                :variant="planBadgeVariant" 
                                class="text-xs px-1 py-0"
                            >
                                {{ planLabel }}
                            </Badge>
                            <Badge 
                                :variant="currentTenant.active ? 'default' : 'secondary'" 
                                class="text-xs px-1 py-0"
                            >
                                {{ currentTenant.active ? 'Activo' : 'Inactivo' }}
                            </Badge>
                        </div>
                    </div>
                    <ChevronDown class="h-4 w-4 opacity-50" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-80">
                <DropdownMenuLabel>
                    <div class="flex items-center justify-between">
                        <span>Cambiar Organización</span>
                        <Badge variant="outline" class="text-xs">
                            Super Admin
                        </Badge>
                    </div>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                
                <div v-if="availableTenants?.length" class="max-h-60 overflow-y-auto">
                    <DropdownMenuItem
                        v-for="tenant in availableTenants"
                        :key="tenant.id"
                        @click="handleTenantSwitch(tenant.id)"
                        :disabled="isLoading || tenant.id === currentTenant.id"
                        class="flex items-start justify-between p-3 cursor-pointer"
                    >
                        <div class="flex items-start gap-3 flex-1">
                            <div class="mt-1">
                                <Building2 class="h-4 w-4 text-muted-foreground" />
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-sm">{{ tenant.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ tenant.subdomain }}</p>
                                <div class="flex items-center gap-1 mt-1">
                                    <Badge 
                                        :variant="tenant.subscription_plan === 'enterprise' ? 'destructive' : 
                                                 tenant.subscription_plan === 'professional' ? 'default' : 'secondary'" 
                                        class="text-xs px-1 py-0"
                                    >
                                        {{ tenant.subscription_plan }}
                                    </Badge>
                                    <Badge 
                                        :variant="tenant.active ? 'default' : 'secondary'" 
                                        class="text-xs px-1 py-0"
                                    >
                                        {{ tenant.active ? 'Activo' : 'Inactivo' }}
                                    </Badge>
                                </div>
                            </div>
                            <div v-if="tenant.id === currentTenant.id" class="mt-1">
                                <Check class="h-4 w-4 text-primary" />
                            </div>
                        </div>
                    </DropdownMenuItem>
                </div>
                
                <div v-else class="p-3 text-sm text-muted-foreground text-center">
                    No hay otros tenants disponibles
                </div>
                
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <a href="/admin/tenants" class="flex items-center gap-2">
                        <Building2 class="h-4 w-4" />
                        Gestionar Tenants
                    </a>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
    
    <!-- Indicador simple para usuarios no super admin -->
    <div v-else-if="currentTenant" class="flex items-center gap-2 px-3 py-2 bg-muted/50 rounded-lg">
        <Building2 class="h-4 w-4 text-muted-foreground" />
        <div class="flex flex-col text-left">
            <span class="text-sm font-medium">{{ currentTenant.name }}</span>
            <Badge 
                :variant="planBadgeVariant" 
                class="text-xs w-fit"
            >
                {{ planLabel }}
            </Badge>
        </div>
    </div>
</template>