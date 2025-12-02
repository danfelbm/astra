/**
 * Re-exports para componentes de HitosDashboard
 */

// Componente principal (orquestador)
export { default as HitosEntregablesPanel } from './HitosEntregablesPanel.vue';

// Componentes de vista
export { default as HitosEntregablesList } from './HitosEntregablesList.vue';
export { default as HitosEntregablesTabs } from './HitosEntregablesTabs.vue';
export { default as HitosEntregablesKanban } from './HitosEntregablesKanban.vue';

// Componentes atómicos
export { default as HitosEntregableCard } from './HitosEntregableCard.vue';
export { default as HitosKanbanColumn } from './HitosKanbanColumn.vue';
export { default as HitosViewModeToggle } from './HitosViewModeToggle.vue';

// Modales de detalles
export { default as HitoDetallesModal } from './HitoDetallesModal.vue';
export { default as EntregableDetallesModal } from './EntregableDetallesModal.vue';
