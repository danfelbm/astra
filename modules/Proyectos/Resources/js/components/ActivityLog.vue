<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Avatar, AvatarFallback, AvatarImage } from '@modules/Core/Resources/js/components/ui/avatar';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';
import { ChevronDown } from 'lucide-vue-next';

interface Usuario {
  id: number;
  name: string;
  email: string;
  avatar?: string;
}

interface ActividadProperties {
  entidad_tipo?: string;
  entidad_nombre?: string;
  entidad_url?: string;
  comentario_id?: number;
  contenido_preview?: string;
  [key: string]: any;
}

interface Actividad {
  id: number;
  description: string;
  causer: Usuario | null;
  created_at: string;
  subject_type?: string;
  subject_id?: number;
  event?: string;
  properties?: ActividadProperties;
}

interface Props {
  activities: Actividad[];
  title?: string;
  description?: string;
  emptyMessage?: string;
  showCard?: boolean;
  perPage?: number;
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Historial de Actividad',
  description: 'Registro completo de cambios y eventos',
  emptyMessage: 'No hay actividad registrada',
  showCard: true,
  perPage: 20
});

// Estado de paginación local
const currentPage = ref(1);

// Calcular actividades paginadas
const paginatedActivities = computed(() => {
  const start = 0;
  const end = currentPage.value * props.perPage;
  return props.activities.slice(start, end);
});

// Verificar si hay más actividades para cargar
const hasMore = computed(() => {
  return paginatedActivities.value.length < props.activities.length;
});

// Total de actividades
const totalActivities = computed(() => props.activities.length);

// Cargar más actividades
const loadMore = () => {
  currentPage.value++;
};

// Reset paginación cuando cambian las actividades (por filtros)
watch(() => props.activities, () => {
  currentPage.value = 1;
}, { deep: true });

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

// Parsear descripción para extraer partes clicables
const parseDescription = (activity: Actividad) => {
  const description = activity.description || '';
  const props = activity.properties;

  // Si hay entidad_url y entidad_nombre, hacerlos clicables
  if (props?.entidad_url && props?.entidad_nombre && props?.entidad_tipo) {
    // El patrón busca "en TipoEntidad 'NombreEntidad'"
    const pattern = new RegExp(`(.*)(en ${props.entidad_tipo} ')([^']+)('.*)`);
    const match = description.match(pattern);

    if (match) {
      return {
        before: match[1] + `en ${props.entidad_tipo} '`,
        linkText: match[3],
        linkUrl: props.entidad_url,
        after: match[4]
      };
    }
  }

  return { text: description };
};
</script>

<template>
  <Card v-if="showCard">
    <CardHeader>
      <div class="flex items-center justify-between">
        <div>
          <CardTitle>{{ title }}</CardTitle>
          <CardDescription>{{ description }}</CardDescription>
        </div>
        <span v-if="totalActivities > 0" class="text-xs text-muted-foreground">
          {{ paginatedActivities.length }} de {{ totalActivities }}
        </span>
      </div>
    </CardHeader>
    <CardContent>
      <div v-if="activities && activities.length > 0" class="space-y-4">
        <div
          v-for="activity in paginatedActivities"
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
              <!-- Descripción con link clicable -->
              <template v-if="parseDescription(activity).linkUrl">
                <span>{{ parseDescription(activity).before }}</span>
                <Link
                  :href="parseDescription(activity).linkUrl"
                  class="font-medium text-primary hover:underline"
                >
                  {{ parseDescription(activity).linkText }}
                </Link>
                <span>{{ parseDescription(activity).after }}</span>
              </template>
              <template v-else>
                {{ activity.description }}
              </template>
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

        <!-- Botón cargar más -->
        <div v-if="hasMore" class="pt-2 text-center">
          <Button
            variant="ghost"
            size="sm"
            @click="loadMore"
            class="text-muted-foreground"
          >
            <ChevronDown class="h-4 w-4 mr-1" />
            Cargar más ({{ totalActivities - paginatedActivities.length }} restantes)
          </Button>
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
        v-for="activity in paginatedActivities"
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
            <!-- Descripción con link clicable -->
            <template v-if="parseDescription(activity).linkUrl">
              <span>{{ parseDescription(activity).before }}</span>
              <Link
                :href="parseDescription(activity).linkUrl"
                class="font-medium text-primary hover:underline"
              >
                {{ parseDescription(activity).linkText }}
              </Link>
              <span>{{ parseDescription(activity).after }}</span>
            </template>
            <template v-else>
              {{ activity.description }}
            </template>
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

      <!-- Botón cargar más -->
      <div v-if="hasMore" class="pt-2 text-center">
        <Button
          variant="ghost"
          size="sm"
          @click="loadMore"
          class="text-muted-foreground"
        >
          <ChevronDown class="h-4 w-4 mr-1" />
          Cargar más ({{ totalActivities - paginatedActivities.length }} restantes)
        </Button>
      </div>
    </div>
    <div v-else class="text-center py-8">
      <p class="text-sm text-muted-foreground">{{ emptyMessage }}</p>
    </div>
  </div>
</template>
