// Tipos para el sistema de Obligaciones de Contratos

export interface ObligacionContrato {
  id: number;
  contrato_id: number;
  parent_id: number | null;
  titulo: string;
  descripcion?: string;
  // Campos deprecados - mantenidos por compatibilidad
  fecha_vencimiento?: string;
  estado?: EstadoObligacion;
  prioridad?: PrioridadObligacion;
  orden: number;
  nivel: number;
  path?: string;
  // responsable_id eliminado - se obtiene desde contrato
  porcentaje_cumplimiento?: number;
  notas_cumplimiento?: string;
  cumplido_at?: string;
  cumplido_por?: number;
  // Campos activos
  archivos_adjuntos?: ArchivoAdjunto[];
  tenant_id?: number;
  created_by?: number;
  updated_by?: number;
  created_at?: string;
  updated_at?: string;

  // Relaciones
  contrato?: any;
  padre?: ObligacionContrato;
  hijos?: ObligacionContrato[];
  // responsable eliminado - se obtiene desde contrato
  cumplido_por_usuario?: any;
  creador?: any;
  actualizador?: any;

  // Accessors computados
  estado_label?: string;
  estado_color?: string;
  prioridad_label?: string;
  prioridad_color?: string;
  dias_restantes?: number | null;
  esta_vencida?: boolean;
  esta_proxima_vencer?: boolean;
  tiene_hijos?: boolean;
  total_hijos?: number;
  hijos_completados?: number;
  ruta_completa?: BreadcrumbItem[];
}

export type EstadoObligacion = 'pendiente' | 'en_progreso' | 'cumplida' | 'vencida' | 'cancelada';
export type PrioridadObligacion = 'baja' | 'media' | 'alta';

export interface ArchivoAdjunto {
  ruta: string;
  nombre_original: string;
  tamaño?: number;
  tipo?: string;
  subido_por?: number;
  subido_at?: string;
}

export interface BreadcrumbItem {
  id: number;
  titulo: string;
}

// Formulario de creación/edición
export interface ObligacionFormData {
  contrato_id: number;
  parent_id?: number | null;
  titulo: string;
  descripcion?: string;
  archivos?: File[];
  archivos_eliminar?: string[];
}

// Filtros para búsqueda
export interface ObligacionFilters {
  search?: string;
  contrato_id?: number;
  ver_todas?: boolean;
  sort_field?: string;
  sort_direction?: 'asc' | 'desc';
}

// Estadísticas
export interface ObligacionEstadisticas {
  total: number;
  pendientes: number;
  en_progreso: number;
  cumplidas: number;
  vencidas: number;
  proximas_vencer: number;
  alta_prioridad: number;
  porcentaje_cumplimiento: number;
  promedio_progreso?: number;
}

// EstadisticasResponsable eliminado - columna responsable_id deprecada

// Timeline agrupado por mes
export interface TimelineGroup {
  [key: string]: ObligacionContrato[];
}

// Respuesta del servidor
export interface ObligacionResponse {
  success: boolean;
  message?: string;
  obligacion?: ObligacionContrato;
  actualizadas?: number;
  requiere_confirmacion?: boolean;
  total_hijos?: number;
}

// Props para componentes
export interface ObligacionTreeProps {
  obligaciones: ObligacionContrato[];
  contratoId: number;
  editable?: boolean;
  selectable?: boolean;
  expandedNodes?: number[];
  selectedNode?: number | null;
  onSelect?: (obligacion: ObligacionContrato) => void;
  onEdit?: (obligacion: ObligacionContrato) => void;
  onDelete?: (obligacion: ObligacionContrato) => void;
  onComplete?: (obligacion: ObligacionContrato) => void;
  onAddChild?: (parent: ObligacionContrato) => void;
  onMove?: (obligacion: ObligacionContrato, newParentId: number | null, newOrder: number) => void;
  onReorder?: (items: number[], parentId: number | null) => void;
}

export interface ObligacionItemProps {
  obligacion: ObligacionContrato;
  nivel: number;
  expanded: boolean;
  selected: boolean;
  editable?: boolean;
  onToggle: () => void;
  onSelect: () => void;
  onEdit?: () => void;
  onDelete?: () => void;
  onComplete?: () => void;
  onAddChild?: () => void;
  onDragStart?: (e: DragEvent) => void;
  onDragOver?: (e: DragEvent) => void;
  onDrop?: (e: DragEvent) => void;
}

export interface ObligacionFormProps {
  obligacion?: ObligacionContrato;
  contratoId: number;
  parentId?: number | null;
  onSubmit: (data: ObligacionFormData) => void;
  onCancel: () => void;
  loading?: boolean;
  errors?: Record<string, string[]>;
}

// Eventos del sistema
export interface ObligacionEvent {
  type: 'created' | 'updated' | 'deleted' | 'completed' | 'moved' | 'reordered';
  obligacionId: number;
  parentId?: number | null;
  contratoId: number;
  data?: any;
}

// Configuración del árbol
export interface TreeConfig {
  draggable: boolean;
  collapsible: boolean;
  checkable: boolean;
  showActions: boolean;
  showProgress: boolean;
  // showResponsable eliminado - se obtiene desde contrato
  showDates: boolean;
  maxDepth?: number;
  defaultExpanded: boolean;
}

// Para drag & drop
export interface DragData {
  obligacionId: number;
  parentId: number | null;
  orden: number;
  nivel: number;
}

// Estado del formulario
export interface FormState {
  isSubmitting: boolean;
  errors: Record<string, string[]>;
  touched: Record<string, boolean>;
  isDirty: boolean;
}

// Permisos del usuario
export interface ObligacionPermisos {
  canView: boolean;
  canCreate: boolean;
  canEdit: boolean;
  canDelete: boolean;
  canComplete: boolean;
  canExport: boolean;
  canViewOwn: boolean;
  canCompleteOwn: boolean;
}

export default ObligacionContrato;