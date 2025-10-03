<script setup lang="ts">
import UserLayout from "@modules/Core/Resources/js/layouts/UserLayout.vue";
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "../../components/ui/card";
import { Button } from "../../components/ui/button";
import {
    Vote, Users, ArrowRight, FileText, Calendar, CheckCircle,
    AlertCircle, Settings, Mail, Phone, MapPin, Clock, Upload,
    Download, Eye, Edit, Trash, Plus, Minus, ArrowLeft
} from 'lucide-vue-next';
import { computed } from 'vue';

interface DashboardCard {
    id: string;
    enabled: boolean;
    order: number;
    color: string;
    icon: string;
    title: string;
    description: string;
    buttonText: string;
    buttonLink: string;
}

interface DashboardConfig {
    hero_html: string;
    cards: DashboardCard[];
}

interface Props {
    hasAssemblyAccess: boolean;
    dashboardConfig: DashboardConfig;
    // Props de permisos de usuario
    canViewDashboard: boolean;
}

const props = defineProps<Props>();

// Mapeo de íconos
const iconMap: Record<string, any> = {
    Vote, Users, FileText, Calendar, CheckCircle, AlertCircle,
    Settings, Mail, Phone, MapPin, Clock, Upload, Download,
    Eye, Edit, Trash, Plus, Minus, ArrowRight, ArrowLeft
};

const getIcon = (iconName: string) => iconMap[iconName] || Vote;

// Mapeo de colores
const colorClasses: Record<string, any> = {
    blue: {
        border: 'border-blue-200 dark:border-blue-800',
        text: 'text-blue-700 dark:text-blue-300',
        button: 'bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700'
    },
    green: {
        border: 'border-green-200 dark:border-green-800',
        text: 'text-green-700 dark:text-green-300',
        button: 'bg-green-600 hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-700'
    },
    red: {
        border: 'border-red-200 dark:border-red-800',
        text: 'text-red-700 dark:text-red-300',
        button: 'bg-red-600 hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-700'
    },
    purple: {
        border: 'border-purple-200 dark:border-purple-800',
        text: 'text-purple-700 dark:text-purple-300',
        button: 'bg-purple-600 hover:bg-purple-700 dark:bg-purple-600 dark:hover:bg-purple-700'
    },
    orange: {
        border: 'border-orange-200 dark:border-orange-800',
        text: 'text-orange-700 dark:text-orange-300',
        button: 'bg-orange-600 hover:bg-orange-700 dark:bg-orange-600 dark:hover:bg-orange-700'
    },
    yellow: {
        border: 'border-yellow-200 dark:border-yellow-800',
        text: 'text-yellow-700 dark:text-yellow-300',
        button: 'bg-yellow-600 hover:bg-yellow-700 dark:bg-yellow-600 dark:hover:bg-yellow-700'
    },
    pink: {
        border: 'border-pink-200 dark:border-pink-800',
        text: 'text-pink-700 dark:text-pink-300',
        button: 'bg-pink-600 hover:bg-pink-700 dark:bg-pink-600 dark:hover:bg-pink-700'
    },
    indigo: {
        border: 'border-indigo-200 dark:border-indigo-800',
        text: 'text-indigo-700 dark:text-indigo-300',
        button: 'bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-600 dark:hover:bg-indigo-700'
    },
    teal: {
        border: 'border-teal-200 dark:border-teal-800',
        text: 'text-teal-700 dark:text-teal-300',
        button: 'bg-teal-600 hover:bg-teal-700 dark:bg-teal-600 dark:hover:bg-teal-700'
    },
    gray: {
        border: 'border-gray-200 dark:border-gray-800',
        text: 'text-gray-700 dark:text-gray-300',
        button: 'bg-gray-600 hover:bg-gray-700 dark:bg-gray-600 dark:hover:bg-gray-700'
    }
};

// Filtrar y ordenar cards habilitados
const enabledCards = computed(() => {
    return props.dashboardConfig.cards
        .filter(card => card.enabled)
        .sort((a, b) => a.order - b.order);
});
</script>

<template>
    <Head title="Dashboard" />

    <UserLayout>
        <div class="flex h-full flex-1 flex-col rounded-xl p-4">
            <!-- Hero Banner Dinámico -->
            <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4 sm:p-6 md:p-8">
                <div class="relative z-10" v-html="dashboardConfig.hero_html"></div>
                <div class="absolute top-0 right-0 w-32 sm:w-48 md:w-64 h-32 sm:h-48 md:h-64 bg-blue-100 dark:bg-blue-900 opacity-20 rounded-full -translate-y-16 sm:-translate-y-24 md:-translate-y-32 translate-x-16 sm:translate-x-24 md:translate-x-32"></div>
                <div class="absolute bottom-0 left-0 w-24 sm:w-32 md:w-48 h-24 sm:h-32 md:h-48 bg-blue-50 dark:bg-blue-800 opacity-30 rounded-full translate-y-12 sm:translate-y-16 md:translate-y-24 -translate-x-12 sm:-translate-x-16 md:-translate-x-24"></div>
            </div>

            <!-- Cards dinámicos de acceso rápido -->
            <div class="mt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Card
                        v-for="card in enabledCards"
                        :key="card.id"
                        class="hover:shadow-lg transition-shadow duration-300"
                        :class="colorClasses[card.color]?.border || colorClasses.blue.border"
                    >
                        <CardHeader class="pb-3">
                            <CardTitle
                                class="flex items-center gap-2"
                                :class="colorClasses[card.color]?.text || colorClasses.blue.text"
                            >
                                <component :is="getIcon(card.icon)" class="h-6 w-6" />
                                {{ card.title }}
                            </CardTitle>
                            <CardDescription>
                                {{ card.description }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="pt-0">
                            <Link :href="card.buttonLink">
                                <Button
                                    class="w-full group"
                                    :class="colorClasses[card.color]?.button || colorClasses.blue.button"
                                >
                                    {{ card.buttonText }}
                                    <ArrowRight class="ml-2 h-4 w-4 group-hover:translate-x-1 transition-transform" />
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </UserLayout>
</template>
