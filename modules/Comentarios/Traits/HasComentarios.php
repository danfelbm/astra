<?php

namespace Modules\Comentarios\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Comentarios\Models\Comentario;

/**
 * Trait para agregar funcionalidad de comentarios a cualquier modelo.
 *
 * Uso:
 * ```php
 * use Modules\Comentarios\Traits\HasComentarios;
 *
 * class Hito extends Model
 * {
 *     use HasComentarios;
 * }
 * ```
 *
 * Luego en el controlador:
 * ```php
 * $hito->comentarios; // Todos los comentarios
 * $hito->comentariosRaiz; // Solo comentarios principales (sin respuestas)
 * $hito->total_comentarios; // Contador
 * ```
 */
trait HasComentarios
{
    /**
     * Todos los comentarios asociados al modelo.
     */
    public function comentarios(): MorphMany
    {
        return $this->morphMany(Comentario::class, 'commentable');
    }

    /**
     * Solo comentarios raíz (sin padre).
     * Ordenados por fecha descendente.
     */
    public function comentariosRaiz(): MorphMany
    {
        return $this->comentarios()
            ->whereNull('parent_id')
            ->orderByDesc('created_at');
    }

    /**
     * Comentarios raíz con todas sus respuestas cargadas recursivamente.
     * Incluye autor, reacciones y comentarios citados.
     */
    public function comentariosConRespuestas(): MorphMany
    {
        return $this->comentariosRaiz()
            ->with([
                'autor:id,name,email',
                'reacciones',
                'comentarioCitado.autor:id,name,email',
                'respuestasRecursivas.autor:id,name,email',
                'respuestasRecursivas.reacciones',
                'respuestasRecursivas.comentarioCitado.autor:id,name,email',
            ]);
    }

    /**
     * Total de comentarios (incluyendo respuestas).
     */
    public function getTotalComentariosAttribute(): int
    {
        return $this->comentarios()->count();
    }

    /**
     * Total de comentarios raíz (sin contar respuestas).
     */
    public function getTotalComentariosRaizAttribute(): int
    {
        return $this->comentariosRaiz()->count();
    }

    /**
     * Verifica si el modelo tiene comentarios.
     */
    public function tieneComentarios(): bool
    {
        return $this->comentarios()->exists();
    }

    /**
     * Obtiene el último comentario del modelo.
     */
    public function ultimoComentario(): ?Comentario
    {
        return $this->comentarios()
            ->with('autor:id,name,email')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Obtiene comentarios paginados para API.
     */
    public function comentariosPaginados(int $perPage = 20)
    {
        return $this->comentariosConRespuestas()->paginate($perPage);
    }
}
