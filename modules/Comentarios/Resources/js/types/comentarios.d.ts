/**
 * Tipos TypeScript para el módulo de Comentarios
 */

// Usuario básico para relaciones
export interface UsuarioBasico {
    id: number;
    name: string;
    email: string;
}

// Emojis permitidos
export type EmojiKey = 'thumbs_up' | 'thumbs_down' | 'heart' | 'laugh' | 'clap' | 'fire' | 'check' | 'eyes';

export const EMOJIS: Record<EmojiKey, string> = {
    thumbs_up: '👍',
    thumbs_down: '👎',
    heart: '❤️',
    laugh: '😄',
    clap: '👏',
    fire: '🔥',
    check: '✅',
    eyes: '👀',
};

// Reacción individual
export interface Reaccion {
    id: number;
    comentario_id: number;
    user_id: number;
    emoji: EmojiKey;
    created_at: string;
    simbolo?: string;
}

// Resumen de reacciones agrupadas
export interface ReaccionResumen {
    emoji: EmojiKey;
    simbolo: string;
    count: number;
    usuarios: number[];
    usuario_actual_reacciono: boolean;
}

// Mención de usuario
export interface Mencion {
    id: number;
    comentario_id: number;
    user_id: number;
    notificado: boolean;
    created_at: string;
    user?: UsuarioBasico;
}

// Comentario principal
export interface Comentario {
    id: number;
    commentable_type: string;
    commentable_id: number;
    parent_id: number | null;
    nivel: number;
    contenido: string;
    contenido_plain: string;
    quoted_comentario_id: number | null;
    es_editado: boolean;
    editado_at: string | null;
    created_by: number;
    updated_by: number | null;
    tenant_id: number | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;

    // Relaciones
    autor?: UsuarioBasico;
    respuestas?: Comentario[];
    respuestas_limitadas?: Comentario[]; // Respuestas con profundidad controlada
    comentario_citado?: Comentario | null;
    reacciones?: Reaccion[];
    menciones?: Mencion[];

    // Accessors computados
    contenido_truncado?: string;
    fecha_relativa?: string;
    fecha_formateada?: string;
    es_editable?: boolean;
    es_eliminable?: boolean;
    tiempo_restante_edicion?: number | null;
    tiene_respuestas?: boolean;
    total_respuestas?: number;
    total_respuestas_anidadas?: number; // Contador de respuestas profundas no cargadas
    reacciones_resumen?: ReaccionResumen[];
}

// Datos para crear comentario
export interface ComentarioCreateData {
    contenido: string;
    parent_id?: number | null;
    quoted_comentario_id?: number | null;
}

// Datos para actualizar comentario
export interface ComentarioUpdateData {
    contenido: string;
}

// Respuesta de la API
export interface ApiResponse<T> {
    success: boolean;
    message?: string;
    data?: T;
    comentario?: Comentario;
    reacciones?: ReaccionResumen[];
    accion?: 'added' | 'removed';
}

// Link de paginación (estructura de Laravel)
export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

// Respuesta paginada
export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: PaginationLink[];
}

// Props del panel de comentarios
export interface ComentariosPanelProps {
    commentableType: string;
    commentableId: number;
    canCreate?: boolean;
    canReact?: boolean;
}

// Estado del modo de edición/respuesta
export type ComentarioFormMode = 'create' | 'edit' | 'reply' | 'quote';

export interface ComentarioFormState {
    mode: ComentarioFormMode;
    comentarioId?: number;
    parentId?: number | null;
    quotedId?: number | null;
    contenidoInicial?: string;
}
