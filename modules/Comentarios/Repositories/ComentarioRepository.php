<?php

namespace Modules\Comentarios\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Comentarios\Models\Comentario;

class ComentarioRepository
{
    /**
     * Obtiene comentarios paginados para un modelo específico.
     */
    public function getForModel(Model $model, int $perPage = 20): LengthAwarePaginator
    {
        return Comentario::query()
            ->where('commentable_type', get_class($model))
            ->where('commentable_id', $model->getKey())
            ->whereNull('parent_id')
            ->with([
                'autor:id,name,email',
                'reacciones',
                'comentarioCitado.autor:id,name,email',
                'respuestas', // Auto-recursivo
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Obtiene todos los comentarios de un modelo (sin paginar).
     */
    public function getAllForModel(Model $model): Collection
    {
        return Comentario::query()
            ->where('commentable_type', get_class($model))
            ->where('commentable_id', $model->getKey())
            ->whereNull('parent_id')
            ->with([
                'autor:id,name,email',
                'reacciones',
                'comentarioCitado.autor:id,name,email',
                'respuestas', // Auto-recursivo
            ])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Busca un comentario por ID con todas sus relaciones.
     */
    public function findWithRelations(int $id): ?Comentario
    {
        return Comentario::query()
            ->with([
                'autor:id,name,email',
                'reacciones',
                'comentarioCitado.autor:id,name,email',
                'respuestas', // Auto-recursivo
                'parent.autor:id,name,email',
            ])
            ->find($id);
    }

    /**
     * Cuenta comentarios de un modelo.
     */
    public function countForModel(Model $model): int
    {
        return Comentario::query()
            ->where('commentable_type', get_class($model))
            ->where('commentable_id', $model->getKey())
            ->count();
    }

    /**
     * Crea un nuevo comentario.
     */
    public function create(array $data): Comentario
    {
        return Comentario::create($data);
    }

    /**
     * Actualiza un comentario.
     */
    public function update(Comentario $comentario, array $data): Comentario
    {
        $comentario->update($data);
        return $comentario->fresh();
    }

    /**
     * Elimina un comentario (soft delete).
     */
    public function delete(Comentario $comentario): bool
    {
        return $comentario->delete();
    }

    /**
     * Obtiene respuestas de un comentario.
     */
    public function getRespuestas(Comentario $comentario): Collection
    {
        // La relación respuestas ya es auto-recursiva
        return $comentario->respuestas;
    }
}
