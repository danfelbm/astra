<?php

namespace Modules\Comentarios\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Comentarios\Http\Requests\StoreComentarioRequest;
use Modules\Comentarios\Http\Requests\UpdateComentarioRequest;
use Modules\Comentarios\Models\Comentario;
use Modules\Comentarios\Repositories\ComentarioRepository;
use Modules\Comentarios\Services\ComentarioService;

class ComentarioController extends Controller
{
    public function __construct(
        private ComentarioService $service,
        private ComentarioRepository $repository
    ) {}

    /**
     * Lista comentarios de un modelo.
     * GET /api/comentarios/{type}/{id}
     */
    public function index(Request $request, string $type, int $id): JsonResponse
    {
        // Resolver modelo
        $modelClass = $this->resolveModelClass($type);

        if (!$modelClass) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de modelo no válido',
            ], 400);
        }

        $model = $modelClass::find($id);

        if (!$model) {
            return response()->json([
                'success' => false,
                'message' => 'Modelo no encontrado',
            ], 404);
        }

        $perPage = $request->input('per_page', config('comentarios.por_pagina', 20));
        $comentarios = $this->repository->getForModel($model, $perPage);

        return response()->json([
            'success' => true,
            'data' => $comentarios,
        ]);
    }

    /**
     * Crea un nuevo comentario.
     * POST /api/comentarios/{type}/{id}
     */
    public function store(StoreComentarioRequest $request, string $type, int $id): JsonResponse
    {
        // Resolver modelo
        $modelClass = $this->resolveModelClass($type);

        if (!$modelClass) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de modelo no válido',
            ], 400);
        }

        $model = $modelClass::find($id);

        if (!$model) {
            return response()->json([
                'success' => false,
                'message' => 'Modelo no encontrado',
            ], 404);
        }

        $result = $this->service->crear($model, $request->validated());

        return response()->json($result, $result['success'] ? 201 : 422);
    }

    /**
     * Actualiza un comentario.
     * PUT /api/comentarios/{comentario}
     */
    public function update(UpdateComentarioRequest $request, Comentario $comentario): JsonResponse
    {
        $result = $this->service->actualizar($comentario, $request->validated());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Elimina un comentario.
     * DELETE /api/comentarios/{comentario}
     */
    public function destroy(Comentario $comentario): JsonResponse
    {
        $result = $this->service->eliminar($comentario);

        return response()->json($result, $result['success'] ? 200 : 403);
    }

    /**
     * Toggle de reacción (agregar/quitar emoji).
     * POST /api/comentarios/{comentario}/reaccion
     */
    public function toggleReaccion(Request $request, Comentario $comentario): JsonResponse
    {
        $request->validate([
            'emoji' => ['required', 'string', 'max:50'],
        ]);

        $result = $this->service->toggleReaccion($comentario, $request->input('emoji'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Obtiene usuarios para autocomplete de menciones.
     * GET /api/comentarios/usuarios/buscar
     */
    public function buscarUsuarios(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $usuarios = \Modules\Core\Models\User::query()
            ->where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->select('id', 'name', 'email')
            ->limit(10)
            ->get();

        return response()->json(['data' => $usuarios]);
    }

    /**
     * Resuelve el tipo de modelo a partir del string.
     */
    private function resolveModelClass(string $type): ?string
    {
        $modelos = config('comentarios.modelos', []);
        return $modelos[$type] ?? null;
    }
}
