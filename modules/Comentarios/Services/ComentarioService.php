<?php

namespace Modules\Comentarios\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Comentarios\Models\Comentario;
use Modules\Comentarios\Models\ComentarioReaccion;
use Modules\Comentarios\Models\ComentarioMencion;
use Modules\Comentarios\Repositories\ComentarioRepository;
use Modules\Core\Models\User;

class ComentarioService
{
    public function __construct(
        private ComentarioRepository $repository
    ) {}

    /**
     * Crea un nuevo comentario para un modelo.
     */
    public function crear(Model $commentable, array $data): array
    {
        DB::beginTransaction();

        try {
            // Preparar datos del comentario
            $comentarioData = [
                'commentable_type' => get_class($commentable),
                'commentable_id' => $commentable->getKey(),
                'contenido' => $data['contenido'],
                'parent_id' => $data['parent_id'] ?? null,
                'quoted_comentario_id' => $data['quoted_comentario_id'] ?? null,
                'created_by' => auth()->id(),
            ];

            // Crear comentario
            $comentario = $this->repository->create($comentarioData);

            // Procesar menciones (@usuario)
            $this->procesarMenciones($comentario);

            // Registrar actividad (usando el trait HasAuditLog del modelo si existe)
            if (method_exists($commentable, 'logAction')) {
                $commentable->logAction('comentario_agregado', "Nuevo comentario agregado", [
                    'comentario_id' => $comentario->id,
                    'contenido_preview' => $comentario->contenido_truncado,
                ]);
            }

            DB::commit();

            // Recargar con relaciones
            $comentario = $this->repository->findWithRelations($comentario->id);

            return [
                'success' => true,
                'comentario' => $comentario,
                'message' => 'Comentario creado exitosamente',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Error al crear el comentario: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Crea una respuesta a un comentario existente.
     */
    public function responder(Comentario $parent, array $data, Model $commentable): array
    {
        $data['parent_id'] = $parent->id;
        return $this->crear($commentable, $data);
    }

    /**
     * Crea un comentario citando otro.
     */
    public function citarYResponder(Comentario $citado, array $data, Model $commentable): array
    {
        $data['quoted_comentario_id'] = $citado->id;
        return $this->crear($commentable, $data);
    }

    /**
     * Actualiza un comentario existente.
     */
    public function actualizar(Comentario $comentario, array $data): array
    {
        // Verificar que el usuario puede editar
        if (!$comentario->puedeSerEditadoPor()) {
            return [
                'success' => false,
                'message' => 'No tienes permisos para editar este comentario o ha expirado el tiempo de edición',
            ];
        }

        DB::beginTransaction();

        try {
            // Actualizar contenido
            $comentario->update([
                'contenido' => $data['contenido'],
                'es_editado' => true,
                'editado_at' => now(),
                'updated_by' => auth()->id(),
            ]);

            // Reprocesar menciones (eliminar antiguas y crear nuevas)
            $comentario->menciones()->delete();
            $this->procesarMenciones($comentario);

            DB::commit();

            // Recargar con relaciones
            $comentario = $this->repository->findWithRelations($comentario->id);

            return [
                'success' => true,
                'comentario' => $comentario,
                'message' => 'Comentario actualizado exitosamente',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Error al actualizar el comentario: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Elimina un comentario.
     */
    public function eliminar(Comentario $comentario): array
    {
        // Verificar que el usuario puede eliminar
        if (!$comentario->puedeSerEliminadoPor()) {
            return [
                'success' => false,
                'message' => 'No tienes permisos para eliminar este comentario',
            ];
        }

        DB::beginTransaction();

        try {
            // Registrar actividad antes de eliminar
            $commentable = $comentario->commentable;
            if ($commentable && method_exists($commentable, 'logAction')) {
                $commentable->logAction('comentario_eliminado', "Comentario eliminado", [
                    'comentario_id' => $comentario->id,
                    'contenido_preview' => $comentario->contenido_truncado,
                ]);
            }

            // Soft delete (las respuestas se eliminan en cascada por la FK)
            $this->repository->delete($comentario);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Comentario eliminado exitosamente',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Error al eliminar el comentario: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Toggle de reacción (agregar/quitar emoji).
     */
    public function toggleReaccion(Comentario $comentario, string $emoji): array
    {
        // Validar emoji
        if (!array_key_exists($emoji, Comentario::EMOJIS)) {
            return [
                'success' => false,
                'message' => 'Emoji no válido',
            ];
        }

        $userId = auth()->id();

        // Buscar reacción existente
        $reaccionExistente = ComentarioReaccion::where('comentario_id', $comentario->id)
            ->where('user_id', $userId)
            ->where('emoji', $emoji)
            ->first();

        if ($reaccionExistente) {
            // Eliminar reacción
            $reaccionExistente->delete();
            $accion = 'removed';
        } else {
            // Agregar reacción
            ComentarioReaccion::create([
                'comentario_id' => $comentario->id,
                'user_id' => $userId,
                'emoji' => $emoji,
            ]);
            $accion = 'added';
        }

        // Recargar reacciones
        $comentario->load('reacciones');

        return [
            'success' => true,
            'accion' => $accion,
            'reacciones' => $comentario->reacciones_resumen,
            'message' => $accion === 'added' ? 'Reacción agregada' : 'Reacción eliminada',
        ];
    }

    /**
     * Procesa y guarda las menciones encontradas en el contenido.
     */
    private function procesarMenciones(Comentario $comentario): void
    {
        $nombresUsuarios = $comentario->extraerMenciones();

        if (empty($nombresUsuarios)) {
            return;
        }

        // Buscar usuarios por nombre
        $usuarios = User::whereIn('name', $nombresUsuarios)->get();

        foreach ($usuarios as $usuario) {
            ComentarioMencion::create([
                'comentario_id' => $comentario->id,
                'user_id' => $usuario->id,
                'notificado' => false, // TODO: Implementar notificaciones
            ]);
        }

        // TODO: En desarrollo posterior, enviar notificaciones a usuarios mencionados
        // $this->notificarMenciones($comentario, $usuarios);
    }

    /**
     * TODO: Implementar en desarrollo posterior.
     * Envía notificaciones a los usuarios mencionados.
     */
    // private function notificarMenciones(Comentario $comentario, Collection $usuarios): void
    // {
    //     foreach ($usuarios as $usuario) {
    //         // Notification::send($usuario, new MencionEnComentario($comentario));
    //     }
    // }
}
