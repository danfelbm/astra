<script setup lang="ts">
/**
 * Componente reutilizable para mostrar información del proyecto:
 * responsable, creador, fechas de creación/actualización, y opcionalmente ID y estado activo.
 * Se usa tanto en Admin/Show como en User/Show.
 */
import { Card, CardContent, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { User } from 'lucide-vue-next';

interface UserInfo {
    id: number;
    name: string;
    email?: string;
}

interface Props {
    // Datos del proyecto
    proyectoId?: number;
    activo?: boolean;
    responsable?: UserInfo | null;
    creador?: UserInfo | null;
    createdAt: string;
    updatedAt: string;
    // Opciones de visualización
    titulo?: string;
    showId?: boolean;
    showActivo?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    titulo: 'Información del Proyecto',
    showId: false,
    showActivo: false
});

// Función para formatear fecha
const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ titulo }}</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- ID del Proyecto (solo Admin) -->
            <div v-if="showId && proyectoId">
                <p class="text-sm text-gray-600 dark:text-gray-400">ID del Proyecto</p>
                <p class="font-mono">#{{ proyectoId }}</p>
            </div>

            <!-- Estado Activo/Inactivo (solo Admin) -->
            <div v-if="showActivo && activo !== undefined">
                <p class="text-sm text-gray-600 dark:text-gray-400">Estado</p>
                <Badge :class="activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                    {{ activo ? 'Activo' : 'Inactivo' }}
                </Badge>
            </div>

            <!-- Responsable -->
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Responsable</p>
                <div v-if="responsable" class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <User class="h-5 w-5 text-gray-500" />
                    </div>
                    <div>
                        <p class="font-medium">{{ responsable.name }}</p>
                        <p class="text-sm text-gray-500">{{ responsable.email }}</p>
                    </div>
                </div>
                <p v-else class="text-gray-500">Sin asignar</p>
            </div>

            <!-- Creado por -->
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Creado por</p>
                <p class="font-medium">{{ creador?.name || 'Sistema' }}</p>
                <p class="text-xs text-gray-500">{{ formatDate(createdAt) }}</p>
            </div>

            <!-- Última actualización -->
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Última actualización</p>
                <p class="text-sm">{{ formatDate(updatedAt) }}</p>
            </div>
        </CardContent>
    </Card>
</template>
