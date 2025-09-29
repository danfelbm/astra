import type { User } from '@modules/Core/Resources/js/types';
import type { ObligacionContrato } from './obligaciones';
import type { Entregable } from './hitos';

export type TipoEvidencia = 'imagen' | 'video' | 'audio' | 'documento';
export type EstadoEvidencia = 'pendiente' | 'aprobada' | 'rechazada';

export interface EvidenciaMetadata {
    mime_type?: string;
    size?: number;
    duration?: number;
    captured_at?: string;
    width?: number;
    height?: number;
}

export interface ArchivoInfo {
    path: string;
    nombre: string;
    url: string;
    indice: number;
}

export interface ArchivosPorTipo {
    paths: string[];
    nombres: string[];
    count: number;
}

export interface ArchivosPorTipoMap {
    imagen: ArchivosPorTipo;
    video: ArchivosPorTipo;
    audio: ArchivosPorTipo;
    documento: ArchivosPorTipo;
}

export interface Evidencia {
    id: number;
    obligacion_id: number;
    user_id: number;
    tipo_evidencia: TipoEvidencia;
    archivo_path: string;
    archivo_nombre: string | null;
    archivos_paths: string[] | null;
    archivos_nombres: string[] | null;
    total_archivos: number;
    tipos_archivos: Record<string, TipoEvidencia> | null;
    descripcion: string | null;
    metadata: EvidenciaMetadata | null;
    estado: EstadoEvidencia;
    observaciones_admin: string | null;
    revisado_at: string | null;
    revisado_por: number | null;
    created_at: string;
    updated_at: string;

    // Accessors
    tipo_evidencia_label: string;
    estado_label: string;
    estado_color: string;
    archivo_url: string | null;
    archivo_size_formatted: string | null;
    archivos_urls: string[];
    archivos_info: ArchivoInfo[];
    es_imagen: boolean;
    es_video: boolean;
    es_audio: boolean;
    es_documento: boolean;
    tiene_multiples_archivos: boolean;
    archivos_por_tipo: ArchivosPorTipoMap;

    // Relaciones
    obligacion?: ObligacionContrato;
    usuario?: User;
    revisor?: User;
    entregables?: Entregable[];
}

export interface EvidenciaFormData {
    obligacion_id: number | null;
    tipo_evidencia: TipoEvidencia | null;
    archivo_path: string | null;
    archivo_nombre: string | null;
    archivos_paths: string[];
    archivos_nombres: string[];
    tipos_archivos: Record<string, TipoEvidencia> | null;
    descripcion: string | null;
    entregable_ids: number[];  // Cambiado de 'entregables' a 'entregable_ids' para coincidir con backend
    metadata: EvidenciaMetadata | null;
}

export interface TipoEvidenciaOption {
    value: TipoEvidencia;
    label: string;
    accept?: string;
    maxSize?: number;
    icon?: string;
}

export interface EstadisticasEvidencias {
    total: number;
    pendientes: number;
    aprobadas: number;
    rechazadas: number;
    por_tipo?: Record<TipoEvidencia, number>;
    porcentaje_aprobacion?: number;
}

export interface CaptureConfig {
    video?: MediaStreamConstraints['video'];
    audio?: MediaStreamConstraints['audio'];
    maxDuration?: number; // En segundos
    quality?: number; // 0-1 para imágenes/video
}

export interface FileValidationResult {
    valid: boolean;
    error?: string;
    metadata?: EvidenciaMetadata;
}