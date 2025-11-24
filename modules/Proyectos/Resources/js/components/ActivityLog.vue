<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@modules/Core/Resources/js/components/ui/avatar';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';

interface Usuario {
  id: number;
  name: string;
  email: string;
  avatar?: string;
}

interface Actividad {
  id: number;
  description: string;
  causer: Usuario;
  created_at: string;
  subject_type?: string; // Para identificar el tipo de modelo (Proyecto, Hito, Entregable)
  properties?: {
    attributes?: any;
    old?: any;
  };
}

interface Props {
  activities: Actividad[];
  title?: string;
  description?: string;
  emptyMessage?: string;
  showCard?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Historial de Actividad',
  description: 'Registro completo de cambios y eventos',
  emptyMessage: 'No hay actividad registrada',
  showCard: true
});

// Formatear fecha de manera relativa
const formatRelativeDate = (dateString: string) => {
  try {
    const date = parseISO(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Hace un momento';
    if (diffMins < 60) return `Hace ${diffMins} ${diffMins === 1 ? 'minuto' : 'minutos'}`;
    if (diffHours < 24) return `Hace ${diffHours} ${diffHours === 1 ? 'hora' : 'horas'}`;
    if (diffDays < 7) return `Hace ${diffDays} ${diffDays === 1 ? 'día' : 'días'}`;

    return format(date, "d 'de' MMMM 'de' yyyy 'a las' HH:mm", { locale: es });
  } catch {
    return dateString;
  }
};

// Obtener iniciales para el avatar
const getInitials = (name: string) => {
  if (!name) return 'SI';
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
};

// Extraer nombre del modelo desde subject_type
const getModelName = (subjectType?: string) => {
  if (!subjectType) return '';
  const parts = subjectType.split('\\');
  return parts[parts.length - 1];
};
</script>

<template>
  <Card v-if="showCard">
    <CardHeader>
      <CardTitle>{{ title }}</CardTitle>
      <CardDescription>{{ description }}</CardDescription>
    </CardHeader>
    <CardContent>
      <div v-if="activities && activities.length > 0" class="space-y-4">
        <div
          v-for="activity in activities"
          :key="activity.id"
          class="flex gap-3 pb-4 border-b last:border-0 dark:border-gray-700"
        >
          <Avatar class="h-8 w-8 flex-shrink-0">
            <AvatarImage v-if="activity.causer?.avatar" :src="activity.causer.avatar" />
            <AvatarFallback>
              {{ getInitials(activity.causer?.name || 'Sistema') }}
            </AvatarFallback>
          </Avatar>
          <div class="flex-1 min-w-0">
            <p class="text-sm">
              <span class="font-medium">{{ activity.causer?.name || 'Sistema' }}</span>
              {{ activity.description }}
            </p>
            <div class="flex items-center gap-2 mt-1">
              <p class="text-xs text-muted-foreground">
                {{ formatRelativeDate(activity.created_at) }}
              </p>
              <span v-if="activity.subject_type" class="text-xs text-muted-foreground">
                • {{ getModelName(activity.subject_type) }}
              </span>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="text-center py-8">
        <p class="text-sm text-muted-foreground">{{ emptyMessage }}</p>
      </div>
    </CardContent>
  </Card>

  <!-- Sin Card wrapper (para uso dentro de otro Card) -->
  <div v-else>
    <div v-if="activities && activities.length > 0" class="space-y-4">
      <div
        v-for="activity in activities"
        :key="activity.id"
        class="flex gap-3 pb-4 border-b last:border-0 dark:border-gray-700"
      >
        <Avatar class="h-8 w-8 flex-shrink-0">
          <AvatarImage v-if="activity.causer?.avatar" :src="activity.causer.avatar" />
          <AvatarFallback>
            {{ getInitials(activity.causer?.name || 'Sistema') }}
          </AvatarFallback>
        </Avatar>
        <div class="flex-1 min-w-0">
          <p class="text-sm">
            <span class="font-medium">{{ activity.causer?.name || 'Sistema' }}</span>
            {{ activity.description }}
          </p>
          <div class="flex items-center gap-2 mt-1">
            <p class="text-xs text-muted-foreground">
              {{ formatRelativeDate(activity.created_at) }}
            </p>
            <span v-if="activity.subject_type" class="text-xs text-muted-foreground">
              • {{ getModelName(activity.subject_type) }}
            </span>
          </div>
        </div>
      </div>
    </div>
    <div v-else class="text-center py-8">
      <p class="text-sm text-muted-foreground">{{ emptyMessage }}</p>
    </div>
  </div>
</template>
