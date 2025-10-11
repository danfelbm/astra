# useHitos Composable - Guía de Uso

## Actualización con Soporte de Jerarquía

El composable `useHitos` ahora incluye funcionalidades completas para manejar jerarquías de hitos.

---

## 📦 Importación

```typescript
import { useHitos } from '@modules/Proyectos/Resources/js/composables/useHitos';
```

---

## 🚀 Uso Básico (Sin Jerarquía)

Para operaciones CRUD básicas sin necesidad de jerarquía:

```typescript
const {
  createHito,
  updateHito,
  deleteHito,
  duplicateHito
} = useHitos();

// Crear hito
createHito(proyectoId, {
  nombre: 'Nuevo Hito',
  descripcion: 'Descripción',
  estado: 'pendiente'
});

// Actualizar hito
updateHito(proyectoId, hitoId, {
  nombre: 'Nombre Actualizado'
});

// Eliminar hito
deleteHito(proyectoId, hitoId, 'Nombre del Hito');

// Duplicar hito
duplicateHito(proyectoId, hitoId);
```

---

## 🌳 Uso con Jerarquía

Para aprovechar las funciones de jerarquía, inicializa el composable con los hitos:

```typescript
import { computed } from 'vue';

// Opción 1: Pasar array directamente
const hitos = ref<Hito[]>([...]);
const {
  deleteHitoWithHierarchyCheck,
  moveHitoToParent,
  canBeParent,
  getHitosStatsWithHierarchy,
  hierarchy
} = useHitos({ hitos: hitos.value });

// Opción 2: Pasar función que retorna array (recomendado)
const props = defineProps<{ hitos: Hito[] }>();
const {
  deleteHitoWithHierarchyCheck,
  moveHitoToParent,
  hierarchy
} = useHitos({
  hitos: () => props.hitos
});
```

---

## 🔥 Nuevas Funciones de Jerarquía

### 1. `deleteHitoWithHierarchyCheck()`

Elimina un hito con advertencia si tiene sub-hitos:

```typescript
const { deleteHitoWithHierarchyCheck } = useHitos({ hitos });

// Advertirá si el hito tiene hijos
deleteHitoWithHierarchyCheck(proyectoId, hito);
```

**Comportamiento:**
- Si el hito no tiene hijos: Mensaje estándar de confirmación
- Si el hito tiene hijos: Mensaje mostrando cuántos sub-hitos serán eliminados
- Toast final indica cuántos hitos fueron eliminados

---

### 2. `moveHitoToParent()`

Mueve un hito a otro padre en la jerarquía con validación de ciclos:

```typescript
const { moveHitoToParent } = useHitos({ hitos });

// Mover hito a nuevo padre
moveHitoToParent(
  proyectoId,
  hitoId,
  newParentId, // o null para mover a raíz
  hitoNombre
);
```

**Características:**
- Valida automáticamente que no se creen ciclos
- Muestra mensaje de confirmación con nombres legibles
- Toast con feedback del movimiento

---

### 3. `canBeParent()`

Valida si un hito puede ser padre de otro (evita ciclos):

```typescript
const { canBeParent } = useHitos({ hitos });

if (canBeParent(hitoId, potentialParentId)) {
  // Permitir la operación
} else {
  // Mostrar error: "Se crearía un ciclo"
}
```

**Uso común:**
- Validación en formularios antes de submit
- Deshabilitar opciones en selectores
- Validación en drag & drop

---

### 4. `getHitosStatsWithHierarchy()`

Estadísticas extendidas con información de jerarquía:

```typescript
const { getHitosStatsWithHierarchy } = useHitos({ hitos });

const stats = getHitosStatsWithHierarchy(hitos);

console.log(stats);
// {
//   // Estadísticas básicas
//   total: 10,
//   pendientes: 3,
//   enProgreso: 5,
//   completados: 2,
//   vencidos: 1,
//   proximosVencer: 2,
//
//   // Estadísticas de jerarquía (si está habilitada)
//   raices: 2,
//   conPadre: 8,
//   niveles: 3,
//   porNivel: { 0: 2, 1: 5, 2: 3 }
// }
```

---

### 5. Acceso al Composable `hierarchy`

Acceso directo a todas las utilidades de `useHitoHierarchy`:

```typescript
const { hierarchy } = useHitos({ hitos });

if (hierarchy) {
  // Árbol jerárquico completo
  const arbol = hierarchy.arbolHitos.value;

  // Lista plana con niveles
  const conNivel = hierarchy.hitosConNivel.value;

  // Obtener descendientes de un hito
  const descendientes = hierarchy.getDescendientes(hitoId);

  // Obtener ancestros de un hito
  const ancestros = hierarchy.getAncestros(hitoId);

  // Obtener ruta completa (breadcrumb)
  const rutaCompleta = hierarchy.getRutaCompleta(hitoId);
  // "Hito Padre / Hito Hijo / Hito Actual"

  // Obtener nivel de un hito
  const nivel = hierarchy.getNivel(hitoId);

  // Hitos disponibles como padres (excluye descendientes)
  const disponibles = hierarchy.getHitosDisponiblesComoPadres(hitoId);
}
```

---

## 📊 Ejemplo Completo: Componente con Jerarquía

```typescript
<script setup lang="ts">
import { computed } from 'vue';
import { useHitos } from '@modules/Proyectos/Resources/js/composables/useHitos';
import type { Hito } from '@modules/Proyectos/Resources/js/types/hitos';

interface Props {
  proyectoId: number;
  hitos: Hito[];
}

const props = defineProps<Props>();

// Inicializar composable con jerarquía
const {
  deleteHitoWithHierarchyCheck,
  moveHitoToParent,
  canBeParent,
  getHitosStatsWithHierarchy,
  hierarchy,
  processing
} = useHitos({
  hitos: () => props.hitos
});

// Estadísticas con jerarquía
const stats = computed(() =>
  getHitosStatsWithHierarchy(props.hitos)
);

// Árbol jerárquico
const arbolHitos = computed(() =>
  hierarchy?.arbolHitos.value || []
);

// Manejar eliminación con check de hijos
const handleDelete = (hito: Hito) => {
  deleteHitoWithHierarchyCheck(props.proyectoId, hito);
};

// Manejar movimiento en jerarquía
const handleMove = (hitoId: number, newParentId: number | null) => {
  const hito = props.hitos.find(h => h.id === hitoId);
  if (hito) {
    moveHitoToParent(props.proyectoId, hitoId, newParentId, hito.nombre);
  }
};

// Validar selección de padre
const isValidParent = (hitoId: number, parentId: number) => {
  return canBeParent(hitoId, parentId);
};
</script>

<template>
  <div>
    <!-- Estadísticas -->
    <div>
      <p>Total: {{ stats.total }}</p>
      <p>Raíces: {{ stats.raices }}</p>
      <p>Con Padre: {{ stats.conPadre }}</p>
      <p>Niveles: {{ stats.niveles }}</p>
    </div>

    <!-- Lista de hitos con acciones -->
    <div v-for="hito in hitos" :key="hito.id">
      <h3>{{ hito.nombre }}</h3>

      <button
        @click="handleDelete(hito)"
        :disabled="processing"
      >
        Eliminar
      </button>

      <!-- Selector de nuevo padre -->
      <select @change="handleMove(hito.id, $event.target.value)">
        <option :value="null">Raíz</option>
        <option
          v-for="h in hitos"
          :key="h.id"
          :value="h.id"
          :disabled="!isValidParent(hito.id, h.id)"
        >
          {{ h.nombre }}
        </option>
      </select>
    </div>

    <!-- Vista de árbol -->
    <div v-if="hierarchy">
      <h3>Vista Jerárquica</h3>
      <!-- Renderizar árbol recursivamente -->
    </div>
  </div>
</template>
```

---

## ⚡ Funciones Existentes (Sin Cambios)

Todas las funciones originales siguen disponibles y funcionando:

```typescript
const {
  // Estado
  loading,
  processing,

  // Entregables
  completeEntregable,

  // Reordenamiento
  reorderHitos,
  reorderEntregables,

  // Estadísticas básicas
  calculateOverallProgress,
  getHitosStats,

  // Helpers de formato
  formatEstado,
  getEstadoColor,
  getPrioridadColor
} = useHitos();
```

---

## 🎯 Casos de Uso Recomendados

| Situación | Función Recomendada |
|-----------|-------------------|
| Lista simple de hitos | `useHitos()` sin opciones |
| Vista con jerarquía | `useHitos({ hitos })` |
| Eliminar con validación | `deleteHitoWithHierarchyCheck()` |
| Drag & drop jerárquico | `moveHitoToParent()` + `canBeParent()` |
| Dashboard con stats | `getHitosStatsWithHierarchy()` |
| Selector de padre | `hierarchy.getHitosDisponiblesComoPadres()` |
| Breadcrumb | `hierarchy.getRutaCompleta()` |

---

## 📝 Notas Importantes

1. **Retrocompatibilidad**: El composable es 100% compatible con código existente. Si no pasas opciones, funciona exactamente igual que antes.

2. **Performance**: Solo se inicializa `useHitoHierarchy` si pasas la opción `hitos`, evitando overhead innecesario.

3. **Validación de ciclos**: Las funciones de jerarquía validan automáticamente que no se creen ciclos en el árbol.

4. **Feedback al usuario**: Todas las operaciones muestran toasts informativos con detalles específicos de jerarquía.

5. **Null safety**: Todas las funciones de jerarquía verifican si `hierarchy` está disponible antes de usarlo.

---

## 🐛 Troubleshooting

**Problema**: `hierarchy` es `null`
- **Solución**: Asegúrate de inicializar el composable con `useHitos({ hitos })`

**Problema**: "Funcionalidad de jerarquía no disponible"
- **Solución**: Inicializa con `useHitos({ hitos })` en lugar de `useHitos()`

**Problema**: No se detectan ciclos
- **Solución**: Verifica que el array de hitos tenga la propiedad `parent_id` correctamente poblada

---

## 🔗 Ver También

- [useHitoHierarchy.ts](./useHitoHierarchy.ts) - Composable especializado en jerarquía
- [HitoHierarchySelector.vue](../components/HitoHierarchySelector.vue) - Selector visual de jerarquía
- [HitoTreeView.vue](../components/HitoTreeView.vue) - Vista de árbol completa
