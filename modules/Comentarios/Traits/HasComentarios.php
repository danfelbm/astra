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
     * Agrega un comentario con contexto/metadata arbitrario.
     * El módulo origen es responsable de enviar todos los datos necesarios.
     *
     * Este método es AGNÓSTICO: no interpreta ni valida los estados o colores.
     * Cada módulo que lo use debe enviar su propia configuración de labels/colores.
     *
     * @param array $data [
     *   'contenido' => string (requerido),
     *   'tipo' => string (ej: 'cambio_estado', 'nota_admin'),
     *   'estado_anterior' => string|null,
     *   'estado_nuevo' => string|null,
     *   'label_anterior' => string|null,
     *   'label_nuevo' => string|null,
     *   'color_anterior' => string|null (ej: 'yellow', 'green', 'red'),
     *   'color_nuevo' => string|null,
     *   'extra' => array (datos adicionales opcionales)
     * ]
     * @return Comentario|null
     */
    public function agregarComentarioConContexto(array $data): ?Comentario
    {
        // Si no hay contenido, no crear comentario
        if (empty($data['contenido'])) {
            return null;
        }

        return $this->comentarios()->create([
            'commentable_type' => get_class($this),
            'commentable_id' => $this->getKey(),
            'contenido' => $data['contenido'],
            'contenido_plain' => strip_tags($data['contenido']),
            'metadata' => [
                'tipo' => $data['tipo'] ?? 'general',
                'estado_anterior' => $data['estado_anterior'] ?? null,
                'estado_nuevo' => $data['estado_nuevo'] ?? null,
                'label_anterior' => $data['label_anterior'] ?? null,
                'label_nuevo' => $data['label_nuevo'] ?? null,
                'color_anterior' => $data['color_anterior'] ?? null,
                'color_nuevo' => $data['color_nuevo'] ?? null,
                'extra' => $data['extra'] ?? [],
            ],
            'created_by' => auth()->id(),
            'tenant_id' => auth()->user()?->tenant_id,
        ]);
    }

}
