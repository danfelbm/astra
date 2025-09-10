<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Progress } from '@modules/Core/Resources/js/components/ui/progress';
import { 
    Mail, MessageSquare, Eye, MousePointer, 
    TrendingUp, TrendingDown, Minus, AlertCircle,
    CheckCircle2, XCircle, Users, BarChart3
} from 'lucide-vue-next';
import type { CampanaMetrics } from '../composables/useCampanaTracking';

interface Props {
    metrics: CampanaMetrics;
    comparison?: {
        tasa_apertura_prev?: number;
        tasa_click_prev?: number;
        tasa_rebote_prev?: number;
    };
    showDetails?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showDetails: true
});

// Calcular tendencias
const getTrend = (current: number, previous?: number) => {
    if (!previous) return { icon: Minus, color: 'text-gray-400', text: '--' };
    
    const diff = current - previous;
    const percentChange = Math.abs((diff / previous) * 100).toFixed(1);
    
    if (Math.abs(diff) < 0.5) {
        return { icon: Minus, color: 'text-gray-400', text: '0%' };
    }
    
    if (diff > 0) {
        return { icon: TrendingUp, color: 'text-green-600', text: `+${percentChange}%` };
    }
    
    return { icon: TrendingDown, color: 'text-red-600', text: `-${percentChange}%` };
};

// Obtener color según métrica
const getMetricColor = (value: number, type: 'apertura' | 'click' | 'rebote' | 'entrega') => {
    const thresholds = {
        apertura: { excellent: 25, good: 20, warning: 15, poor: 10 },
        click: { excellent: 10, good: 5, warning: 3, poor: 2 },
        rebote: { excellent: 2, good: 5, warning: 10, poor: 15 }, // Inverso
        entrega: { excellent: 98, good: 95, warning: 90, poor: 85 },
    };
    
    const t = thresholds[type];
    
    if (type === 'rebote') {
        if (value <= t.excellent) return 'text-green-700';
        if (value <= t.good) return 'text-green-600';
        if (value <= t.warning) return 'text-yellow-600';
        return 'text-red-600';
    }
    
    if (value >= t.excellent) return 'text-green-700';
    if (value >= t.good) return 'text-green-600';
    if (value >= t.warning) return 'text-yellow-600';
    if (value >= t.poor) return 'text-orange-600';
    return 'text-red-600';
};

// Calcular métricas derivadas
const derivedMetrics = computed(() => {
    const m = props.metrics;
    
    return {
        tasaEntrega: (m?.total_enviados || 0) > 0 
            ? (((m?.total_enviados || 0) - (m?.total_fallidos || 0)) / (m?.total_enviados || 1) * 100)
            : 0,
        
        engagementRate: (m?.emails_enviados || 0) > 0
            ? (((m?.emails_abiertos || 0) + (m?.emails_con_click || 0)) / ((m?.emails_enviados || 1) * 2) * 100)
            : 0,
        
        ctr: (m?.emails_abiertos || 0) > 0
            ? ((m?.emails_con_click || 0) / (m?.emails_abiertos || 1) * 100)
            : 0,
        
        clicksPorUsuario: (m?.emails_con_click || 0) > 0
            ? ((m?.total_clicks || 0) / (m?.emails_con_click || 1))
            : 0,
        
        tasaExitoWhatsApp: (m?.whatsapp_enviados || 0) > 0
            ? (((m?.whatsapp_entregados || 0) / (m?.whatsapp_enviados || 1)) * 100)
            : 0,
    };
});

const formatNumber = (num: number): string => {
    if (num >= 1000000) {
        return `${(num / 1000000).toFixed(1)}M`;
    }
    if (num >= 1000) {
        return `${(num / 1000).toFixed(1)}K`;
    }
    return num.toString();
};
</script>

<template>
    <div class="space-y-4">
        <!-- Métricas principales -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <!-- Tasa de apertura -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Tasa de Apertura</CardTitle>
                    <Eye class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold" :class="getMetricColor(metrics?.tasa_apertura || 0, 'apertura')">
                        {{ (metrics?.tasa_apertura || 0).toFixed(1) }}%
                    </div>
                    <div class="flex items-center gap-2 mt-1">
                        <component 
                            :is="getTrend(metrics?.tasa_apertura || 0, comparison?.tasa_apertura_prev).icon" 
                            class="h-3 w-3"
                            :class="getTrend(metrics?.tasa_apertura || 0, comparison?.tasa_apertura_prev).color"
                        />
                        <span class="text-xs text-muted-foreground">
                            {{ getTrend(metrics?.tasa_apertura || 0, comparison?.tasa_apertura_prev).text }}
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground mt-2">
                        {{ formatNumber(metrics?.emails_abiertos || 0) }} de {{ formatNumber(metrics?.emails_enviados || 0) }}
                    </p>
                </CardContent>
            </Card>

            <!-- Tasa de clicks -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Tasa de Clicks</CardTitle>
                    <MousePointer class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold" :class="getMetricColor(metrics?.tasa_click || 0, 'click')">
                        {{ (metrics?.tasa_click || 0).toFixed(1) }}%
                    </div>
                    <div class="flex items-center gap-2 mt-1">
                        <component 
                            :is="getTrend(metrics?.tasa_click || 0, comparison?.tasa_click_prev).icon" 
                            class="h-3 w-3"
                            :class="getTrend(metrics?.tasa_click || 0, comparison?.tasa_click_prev).color"
                        />
                        <span class="text-xs text-muted-foreground">
                            {{ getTrend(metrics?.tasa_click || 0, comparison?.tasa_click_prev).text }}
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground mt-2">
                        {{ formatNumber(metrics?.emails_con_click || 0) }} usuarios
                    </p>
                </CardContent>
            </Card>

            <!-- Tasa de rebote -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Tasa de Rebote</CardTitle>
                    <AlertCircle class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold" :class="getMetricColor(metrics?.tasa_rebote || 0, 'rebote')">
                        {{ (metrics?.tasa_rebote || 0).toFixed(1) }}%
                    </div>
                    <div class="flex items-center gap-2 mt-1">
                        <component 
                            :is="getTrend(metrics?.tasa_rebote || 0, comparison?.tasa_rebote_prev).icon" 
                            class="h-3 w-3"
                            :class="getTrend(metrics?.tasa_rebote || 0, comparison?.tasa_rebote_prev).color"
                        />
                        <span class="text-xs text-muted-foreground">
                            {{ getTrend(metrics?.tasa_rebote || 0, comparison?.tasa_rebote_prev).text }}
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground mt-2">
                        {{ formatNumber(metrics?.emails_rebotados || 0) }} rebotes
                    </p>
                </CardContent>
            </Card>

            <!-- Engagement total -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Engagement</CardTitle>
                    <BarChart3 class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-purple-600">
                        {{ derivedMetrics.engagementRate.toFixed(1) }}%
                    </div>
                    <p class="text-xs text-muted-foreground mt-3">
                        Interacción general
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Detalles por canal -->
        <div v-if="showDetails" class="grid gap-4 md:grid-cols-2">
            <!-- Email -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="text-base flex items-center gap-2">
                            <Mail class="h-4 w-4" />
                            Email
                        </CardTitle>
                        <Badge variant="outline">
                            {{ formatNumber(metrics?.emails_enviados || 0) }} enviados
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Abiertos</span>
                            <span class="font-medium">{{ formatNumber(metrics?.emails_abiertos || 0) }}</span>
                        </div>
                        <Progress :value="metrics?.tasa_apertura || 0" class="h-2" />
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Con clicks</span>
                            <span class="font-medium">{{ formatNumber(metrics?.emails_con_click || 0) }}</span>
                        </div>
                        <Progress :value="metrics?.tasa_click || 0" class="h-2" />
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 pt-2 border-t">
                        <div>
                            <p class="text-xs text-muted-foreground">Total clicks</p>
                            <p class="text-lg font-semibold">{{ formatNumber(metrics?.total_clicks || 0) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Clicks/usuario</p>
                            <p class="text-lg font-semibold">{{ derivedMetrics.clicksPorUsuario.toFixed(1) }}</p>
                        </div>
                    </div>
                    
                    <div v-if="(metrics?.tiempo_promedio_apertura || 0) > 0" class="pt-2 border-t">
                        <p class="text-xs text-muted-foreground">Tiempo promedio hasta apertura</p>
                        <p class="text-sm font-medium">{{ (metrics?.tiempo_promedio_apertura || 0).toFixed(0) }} min</p>
                    </div>
                </CardContent>
            </Card>

            <!-- WhatsApp -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="text-base flex items-center gap-2">
                            <MessageSquare class="h-4 w-4" />
                            WhatsApp
                        </CardTitle>
                        <Badge variant="outline">
                            {{ formatNumber(metrics?.whatsapp_enviados || 0) }} enviados
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Entregados</span>
                            <span class="font-medium">{{ formatNumber(metrics?.whatsapp_entregados || 0) }}</span>
                        </div>
                        <Progress :value="derivedMetrics.tasaExitoWhatsApp" class="h-2" />
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Fallidos</span>
                            <span class="font-medium text-red-600">{{ formatNumber(metrics?.whatsapp_fallidos || 0) }}</span>
                        </div>
                        <Progress 
                            :value="(metrics?.whatsapp_enviados || 0) > 0 ? ((metrics?.whatsapp_fallidos || 0) / (metrics?.whatsapp_enviados || 1) * 100) : 0" 
                            class="h-2" 
                        />
                    </div>
                    
                    <div class="pt-2 border-t">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Tasa de éxito</span>
                            <span class="text-lg font-semibold" :class="getMetricColor(derivedMetrics.tasaExitoWhatsApp, 'entrega')">
                                {{ derivedMetrics.tasaExitoWhatsApp.toFixed(1) }}%
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Resumen general -->
        <Card v-if="showDetails">
            <CardHeader>
                <CardTitle class="text-base">Resumen General</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="flex items-center gap-3">
                        <Users class="h-8 w-8 text-blue-600" />
                        <div>
                            <p class="text-xs text-muted-foreground">Destinatarios</p>
                            <p class="text-xl font-bold">{{ formatNumber(metrics?.total_destinatarios || 0) }}</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <CheckCircle2 class="h-8 w-8 text-green-600" />
                        <div>
                            <p class="text-xs text-muted-foreground">Enviados</p>
                            <p class="text-xl font-bold">{{ formatNumber(metrics?.total_enviados || 0) }}</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <XCircle class="h-8 w-8 text-red-600" />
                        <div>
                            <p class="text-xs text-muted-foreground">Fallidos</p>
                            <p class="text-xl font-bold">{{ formatNumber(metrics?.total_fallidos || 0) }}</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <TrendingUp class="h-8 w-8 text-purple-600" />
                        <div>
                            <p class="text-xs text-muted-foreground">Tasa entrega</p>
                            <p class="text-xl font-bold">{{ derivedMetrics.tasaEntrega.toFixed(1) }}%</p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>