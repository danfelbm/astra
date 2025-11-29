<?php

namespace Modules\Comentarios\Contracts;

use Illuminate\Database\Eloquent\Model;
use Modules\Comentarios\Models\Comentario;

/**
 * Contrato para el servicio de comentarios.
 * Define la lógica de negocio del módulo.
 */
interface ComentarioServiceInterface
{
    /**
     * Crea un nuevo comentario para un modelo.
     *
     * @return array{success: bool, comentario?: Comentario, message: string}
     */
    public function crear(Model $commentable, array $data): array;

    /**
     * Crea una respuesta a un comentario existente.
     *
     * @return array{success: bool, comentario?: Comentario, message: string}
     */
    public function responder(Comentario $parent, array $data, Model $commentable): array;

    /**
     * Crea un comentario citando otro.
     *
     * @return array{success: bool, comentario?: Comentario, message: string}
     */
    public function citarYResponder(Comentario $citado, array $data, Model $commentable): array;

    /**
     * Actualiza un comentario existente.
     *
     * @return array{success: bool, comentario?: Comentario, message: string}
     */
    public function actualizar(Comentario $comentario, array $data): array;

    /**
     * Elimina un comentario.
     *
     * @return array{success: bool, message: string}
     */
    public function eliminar(Comentario $comentario): array;

    /**
     * Toggle de reacción (agregar/quitar emoji).
     *
     * @return array{success: bool, accion?: string, reacciones?: array, message: string}
     */
    public function toggleReaccion(Comentario $comentario, string $emoji): array;
}
