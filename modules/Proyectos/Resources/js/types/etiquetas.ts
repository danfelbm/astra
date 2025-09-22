export interface CategoriaEtiqueta {
    id: number;
    nombre: string;
    slug: string;
    color: string;
    icono?: string;
    descripcion?: string;
    orden: number;
    activo: boolean;
    etiquetas?: Etiqueta[];
    etiquetas_count?: number;
    created_at?: string;
    updated_at?: string;
}

export interface Etiqueta {
    id: number;
    nombre: string;
    slug: string;
    categoria_etiqueta_id: number;
    parent_id?: number | null;
    nivel: number;
    ruta?: string;
    color?: string;
    descripcion?: string;
    usos_count: number;
    categoria?: CategoriaEtiqueta;
    parent?: Etiqueta;
    children?: Etiqueta[];
    ancestros?: Etiqueta[];
    tiene_hijos?: boolean;
    ruta_completa?: string;
    nombre_completo?: string;
    nombre_jerarquico?: string;
    color_efectivo?: string;
    color_class?: string;
    created_at?: string;
    updated_at?: string;
}

export interface ProyectoEtiqueta {
    proyecto_id: number;
    etiqueta_id: number;
    orden?: number;
    created_at?: string;
}