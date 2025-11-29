<?php

namespace Modules\Comentarios\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Comentarios\Models\Comentario;

/**
 * Contrato para el repositorio de comentarios.
 * Permite inyección de dependencias y testing con mocks.
 */
interface ComentarioRepositoryInterface
{
    /**
     * Obtiene comentarios paginados para un modelo específico.
     *
     * @param Model $model Modelo commentable
     * @param int $perPage Comentarios por página
     * @param string $sort Ordenamiento: 'recientes', 'antiguos', 'populares'
     * @param int $maxNiveles Niveles máximos de respuestas a cargar
     */
    public function getForModel(
        Model $model,
        int $perPage = 20,
        string $sort = 'recientes',
        int $maxNiveles = 3
    ): LengthAwarePaginator;

    /**
     * Obtiene todos los comentarios de un modelo (sin paginar).
     */
    public function getAllForModel(Model $model): Collection;

    /**
     * Busca un comentario por ID con todas sus relaciones.
     */
    public function findWithRelations(int $id): ?Comentario;

    /**
     * Cuenta comentarios de un modelo.
     */
    public function countForModel(Model $model): int;

    /**
     * Crea un nuevo comentario.
     */
    public function create(array $data): Comentario;

    /**
     * Actualiza un comentario.
     */
    public function update(Comentario $comentario, array $data): Comentario;

    /**
     * Elimina un comentario (soft delete).
     */
    public function delete(Comentario $comentario): bool;

    /**
     * Obtiene respuestas de un comentario con profundidad limitada.
     */
    public function getRespuestas(Comentario $comentario, int $maxNiveles = 3): Collection;

    /**
     * Carga respuestas adicionales para un comentario (lazy loading).
     */
    public function cargarRespuestasAdicionales(int $comentarioId, int $offset = 0, int $limit = 10): Collection;

    /**
     * Obtiene resumen de reacciones optimizado con SQL.
     */
    public function getReaccionesResumen(int $comentarioId): array;
}
