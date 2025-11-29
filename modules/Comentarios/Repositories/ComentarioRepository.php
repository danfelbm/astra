<?php

namespace Modules\Comentarios\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Comentarios\Contracts\ComentarioRepositoryInterface;
use Modules\Comentarios\Models\Comentario;

class ComentarioRepository implements ComentarioRepositoryInterface
{
    /**
     * Niveles máximos de anidamiento por defecto.
     */
    private const DEFAULT_MAX_NIVELES = 3;

    /**
     * Obtiene comentarios paginados para un modelo específico.
     * Optimizado con carga limitada de respuestas.
     *
     * @param string $sort Opciones: 'recientes', 'antiguos', 'populares'
     * @param int $maxNiveles Niveles máximos de respuestas a cargar (default: 3)
     */
    public function getForModel(
        Model $model,
        int $perPage = 20,
        string $sort = 'recientes',
        int $maxNiveles = self::DEFAULT_MAX_NIVELES
    ): LengthAwarePaginator {
        $query = Comentario::query()
            ->where('commentable_type', get_class($model))
            ->where('commentable_id', $model->getKey())
            ->whereNull('parent_id')
            ->with($this->buildRelacionesConProfundidad($maxNiveles));

        // Aplicar ordenamiento
        match ($sort) {
            'antiguos' => $query->orderBy('created_at'),
            'populares' => $query->withCount('reacciones')->orderByDesc('reacciones_count'),
            default => $query->orderByDesc('created_at'), // recientes
        };

        $result = $query->paginate($perPage);

        // Post-procesar para agregar resumen de reacciones optimizado
        $this->enrichWithReaccionesResumen($result->getCollection());

        return $result;
    }

    /**
     * Construye el array de relaciones con profundidad limitada.
     * Evita recursión infinita limitando niveles de anidamiento.
     */
    private function buildRelacionesConProfundidad(int $niveles): array
    {
        $relaciones = [
            'autor:id,name,email',
            'reacciones',
            'comentarioCitado.autor:id,name,email',
        ];

        if ($niveles > 0) {
            // Construir relación anidada con límite
            $respuestasRelacion = $this->buildRespuestasAnidadas($niveles);
            $relaciones[] = $respuestasRelacion;
        }

        return $relaciones;
    }

    /**
     * Construye la cadena de relaciones anidadas para respuestas.
     * Ejemplo con niveles=3: 'respuestasLimitadas.respuestasLimitadas.respuestasLimitadas'
     */
    private function buildRespuestasAnidadas(int $niveles): string
    {
        $partes = [];
        for ($i = 0; $i < $niveles; $i++) {
            $partes[] = 'respuestasLimitadas';
        }
        return implode('.', $partes);
    }

    /**
     * Enriquece la colección con resumen de reacciones calculado en SQL.
     */
    private function enrichWithReaccionesResumen(Collection $comentarios): void
    {
        if ($comentarios->isEmpty()) {
            return;
        }

        // Recolectar todos los IDs de comentarios (incluyendo respuestas anidadas)
        $ids = $this->collectAllComentarioIds($comentarios);

        if (empty($ids)) {
            return;
        }

        // Obtener resumen de reacciones con SQL agrupado
        $reaccionesMap = $this->getReaccionesResumenBatch($ids);

        // Asignar a cada comentario
        $this->assignReaccionesRecursivo($comentarios, $reaccionesMap);
    }

    /**
     * Recolecta todos los IDs de comentarios usando iteración (sin recursión).
     * Más eficiente en memoria que el enfoque recursivo.
     */
    private function collectAllComentarioIds(Collection $comentarios): array
    {
        $ids = [];
        $stack = $comentarios->all();

        while (!empty($stack)) {
            $comentario = array_pop($stack);
            $ids[] = $comentario->id;

            // Agregar respuestas al stack si están cargadas
            if ($comentario->relationLoaded('respuestasLimitadas') && $comentario->respuestasLimitadas->isNotEmpty()) {
                foreach ($comentario->respuestasLimitadas as $respuesta) {
                    $stack[] = $respuesta;
                }
            }
        }

        return $ids;
    }

    /**
     * Obtiene resumen de reacciones para múltiples comentarios.
     * Usa dos queries separadas para evitar límite de GROUP_CONCAT.
     */
    private function getReaccionesResumenBatch(array $comentarioIds): array
    {
        $userId = auth()->id();

        // Query 1: Conteos y verificación de usuario actual (sin GROUP_CONCAT)
        $query = DB::table('comentario_reacciones')
            ->select([
                'comentario_id',
                'emoji',
                DB::raw('COUNT(*) as count'),
            ])
            ->whereIn('comentario_id', $comentarioIds)
            ->groupBy('comentario_id', 'emoji');

        // Agregar verificación de usuario actual con binding seguro
        if ($userId) {
            $query->selectRaw('MAX(CASE WHEN user_id = ? THEN 1 ELSE 0 END) as usuario_reacciono', [$userId]);
        } else {
            $query->selectRaw('0 as usuario_reacciono');
        }

        $resumen = $query->get();

        // Query 2: Lista de user_ids (sin límite de tamaño)
        $usuariosPorReaccion = DB::table('comentario_reacciones')
            ->select('comentario_id', 'emoji', 'user_id')
            ->whereIn('comentario_id', $comentarioIds)
            ->get()
            ->groupBy(fn($r) => "{$r->comentario_id}_{$r->emoji}");

        // Organizar por comentario_id
        $map = [];
        foreach ($resumen as $reaccion) {
            $key = "{$reaccion->comentario_id}_{$reaccion->emoji}";
            $userIds = $usuariosPorReaccion->get($key, collect())->pluck('user_id')->map(fn($id) => (int) $id)->toArray();

            $map[$reaccion->comentario_id][] = [
                'emoji' => $reaccion->emoji,
                'simbolo' => Comentario::EMOJIS[$reaccion->emoji] ?? $reaccion->emoji,
                'count' => (int) $reaccion->count,
                'usuarios' => $userIds,
                'usuario_actual_reacciono' => (bool) $reaccion->usuario_reacciono,
            ];
        }

        return $map;
    }

    /**
     * Asigna reacciones resumen a comentarios usando iteración.
     */
    private function assignReaccionesRecursivo(Collection $comentarios, array $reaccionesMap): void
    {
        $stack = $comentarios->all();

        while (!empty($stack)) {
            $comentario = array_pop($stack);

            // Asignar resumen de reacciones
            $comentario->setAttribute(
                'reacciones_resumen_optimizado',
                $reaccionesMap[$comentario->id] ?? []
            );

            // Agregar respuestas al stack si están cargadas
            if ($comentario->relationLoaded('respuestasLimitadas') && $comentario->respuestasLimitadas->isNotEmpty()) {
                foreach ($comentario->respuestasLimitadas as $respuesta) {
                    $stack[] = $respuesta;
                }
            }
        }
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
            ->with($this->buildRelacionesConProfundidad(self::DEFAULT_MAX_NIVELES))
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
                'respuestasLimitadas',
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
     * Obtiene respuestas de un comentario con profundidad limitada.
     */
    public function getRespuestas(Comentario $comentario, int $maxNiveles = self::DEFAULT_MAX_NIVELES): Collection
    {
        return $comentario->respuestasLimitadas()
            ->with($this->buildRelacionesConProfundidad($maxNiveles - 1))
            ->get();
    }

    /**
     * Carga respuestas adicionales para un comentario (para "cargar más").
     * Útil cuando hay más respuestas que el límite inicial.
     */
    public function cargarRespuestasAdicionales(int $comentarioId, int $offset = 0, int $limit = 10): Collection
    {
        return Comentario::query()
            ->where('parent_id', $comentarioId)
            ->with([
                'autor:id,name,email',
                'reacciones',
                'comentarioCitado.autor:id,name,email',
            ])
            ->withCount('respuestasDirectas as total_respuestas_directas')
            ->orderBy('created_at', 'asc')
            ->skip($offset)
            ->take($limit)
            ->get();
    }

    /**
     * Obtiene resumen de reacciones optimizado con SQL para un comentario.
     */
    public function getReaccionesResumen(int $comentarioId): array
    {
        return $this->getReaccionesResumenBatch([$comentarioId])[$comentarioId] ?? [];
    }
}
