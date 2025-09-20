/**
 * Tipos TypeScript para el sistema de etiquetas
 */

/**
 * Representa una categoría de etiquetas
 */
export interface CategoriaEtiqueta {
    id: number;
    nombre: string;
    slug: string;
    color: string;
    icono?: string;
    descripcion?: string;
    orden: number;
    activo: boolean;
    tenant_id?: number;
    etiquetas?: Etiqueta[];
    etiquetas_count?: number;
    created_at?: string;
    updated_at?: string;
}

/**
 * Representa una etiqueta individual
 */
export interface Etiqueta {
    id: number;
    nombre: string;
    slug: string;
    categoria_etiqueta_id: number;
    color?: string;
    descripcion?: string;
    usos_count: number;
    tenant_id?: number;
    categoria?: CategoriaEtiqueta;
    proyectos?: ProyectoSimple[];
    proyectos_count?: number;
    created_at?: string;
    updated_at?: string;
    // Campos del pivot cuando viene de proyecto_etiqueta
    pivot?: {
        proyecto_id: number;
        etiqueta_id: number;
        orden: number;
        created_at?: string;
    };
}

/**
 * Proyecto simplificado para relaciones con etiquetas
 */
export interface ProyectoSimple {
    id: number;
    nombre: string;
    estado: string;
}

/**
 * Props para componentes de etiquetas
 */
export interface EtiquetaSelectorProps {
    modelValue: number[]; // Array de IDs de etiquetas seleccionadas
    categorias: CategoriaEtiqueta[]; // Categorías disponibles con sus etiquetas
    maxEtiquetas?: number; // Límite de etiquetas
    placeholder?: string;
    disabled?: boolean;
    allowCreate?: boolean; // Permitir crear nuevas etiquetas
}

export interface EtiquetaDisplayProps {
    etiquetas: Etiqueta[];
    showCategoria?: boolean; // Mostrar el nombre de la categoría
    interactive?: boolean; // Permitir click en las etiquetas
    maxVisible?: number; // Límite de etiquetas visibles
    size?: 'sm' | 'md' | 'lg';
}

export interface EtiquetaManagerProps {
    proyecto: {
        id: number;
        etiquetas: Etiqueta[];
    };
    categorias: CategoriaEtiqueta[];
    canManage: boolean;
}

/**
 * Requests para API
 */
export interface CreateEtiquetaRequest {
    nombre: string;
    categoria_etiqueta_id: number;
    color?: string;
    descripcion?: string;
}

export interface UpdateEtiquetaRequest extends Partial<CreateEtiquetaRequest> {}

export interface CreateCategoriaEtiquetaRequest {
    nombre: string;
    color: string;
    icono?: string;
    descripcion?: string;
    orden?: number;
}

export interface UpdateCategoriaEtiquetaRequest extends Partial<CreateCategoriaEtiquetaRequest> {
    activo?: boolean;
}

/**
 * Responses de API
 */
export interface EtiquetaSugerenciaResponse {
    etiquetas: Etiqueta[];
    basado_en: 'frecuencia' | 'categoria' | 'similar_proyecto';
}

export interface EtiquetaSearchResponse {
    data: Etiqueta[];
    meta: {
        total: number;
        per_page: number;
        current_page: number;
    };
}

/**
 * Opciones de filtrado
 */
export interface EtiquetaFilterOptions {
    categoria_id?: number;
    search?: string;
    activas_only?: boolean;
    sort_by?: 'nombre' | 'usos_count' | 'created_at';
    sort_direction?: 'asc' | 'desc';
}

/**
 * Colores disponibles para etiquetas (basado en shadcn-vue)
 */
export type EtiquetaColor =
    | 'gray'
    | 'red'
    | 'orange'
    | 'amber'
    | 'yellow'
    | 'lime'
    | 'green'
    | 'emerald'
    | 'teal'
    | 'cyan'
    | 'sky'
    | 'blue'
    | 'indigo'
    | 'violet'
    | 'purple'
    | 'fuchsia'
    | 'pink'
    | 'rose';

/**
 * Iconos sugeridos de lucide-vue-next
 */
export type EtiquetaIcon =
    | 'Tag'
    | 'Hash'
    | 'Bookmark'
    | 'Flag'
    | 'Star'
    | 'Heart'
    | 'Zap'
    | 'Target'
    | 'Award'
    | 'TrendingUp'
    | 'Folder'
    | 'Package'
    | 'Box'
    | 'Layers'
    | 'Grid';