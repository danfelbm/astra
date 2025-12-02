<?php

namespace Modules\Proyectos\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Proyectos\Models\Entregable;
use Modules\Proyectos\Repositories\CampoPersonalizadoRepository;

/**
 * Controlador API para obtener detalles de entregables.
 * Usado por el EntregableDetallesModal en el frontend.
 */
class EntregableApiController
{
    public function __construct(
        private CampoPersonalizadoRepository $campoPersonalizadoRepository
    ) {}

    /**
     * Obtener detalles completos de un entregable para el modal.
     */
    public function detalles(Entregable $entregable): JsonResponse
    {
        // Verificar que el usuario tiene acceso al entregable
        $user = auth()->user();
        $hito = $entregable->hito;
        $proyecto = $hito->proyecto;

        $tieneAcceso = $user->hasAdministrativeAccess() ||
            $entregable->responsable_id === $user->id ||
            $hito->responsable_id === $user->id ||
            $proyecto->responsable_id === $user->id ||
            $proyecto->gestores()->where('user_id', $user->id)->exists() ||
            $entregable->usuarios()->where('users.id', $user->id)->exists();

        if (!$tieneAcceso) {
            return response()->json(['message' => 'No tienes acceso a este entregable'], 403);
        }

        // Cargar relaciones necesarias
        $entregable->load([
            'hito:id,nombre,estado,porcentaje_completado,proyecto_id',
            'hito.proyecto:id,nombre,descripcion',
            'responsable:id,name,email,avatar',
            'completadoPor:id,name,email',
            'usuarios:id,name,email,avatar',
            'etiquetas',
            'evidencias' => function ($query) {
                $query->with([
                    'usuario:id,name,email',
                    'obligacion.contrato:id,nombre'
                ]);
            },
            'camposPersonalizados.campoPersonalizado'
        ]);

        // Preparar usuarios asignados para el frontend
        $usuariosAsignados = $entregable->usuarios->map(fn($u) => [
            'user_id' => $u->id,
            'user' => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'avatar' => $u->avatar,
            ],
            'rol' => $u->pivot->rol,
            'created_at' => $u->pivot->created_at,
        ])->values();

        // Obtener actividades del entregable
        $actividades = $entregable->getActivityLogs()
            ->sortByDesc('created_at')
            ->take(100)
            ->values();

        // Obtener usuarios únicos de las actividades para los filtros
        $usuariosActividades = $actividades
            ->pluck('causer')
            ->filter()
            ->unique('id')
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email
            ])
            ->values();

        // Obtener campos personalizados con sus valores
        $camposPersonalizados = $this->campoPersonalizadoRepository->getActivosParaEntregables();
        $valoresCamposPersonalizados = $entregable->getCamposPersonalizadosValues();

        // Extraer contratos únicos de las evidencias para filtros
        $contratosRelacionados = $entregable->evidencias
            ->filter(fn($e) => $e->obligacion?->contrato)
            ->map(fn($e) => $e->obligacion->contrato)
            ->unique('id')
            ->map(fn($c) => [
                'id' => $c->id,
                'nombre' => $c->nombre
            ])
            ->values();

        return response()->json([
            'entregable' => $entregable,
            'proyecto' => [
                'id' => $proyecto->id,
                'nombre' => $proyecto->nombre,
            ],
            'hito' => [
                'id' => $hito->id,
                'nombre' => $hito->nombre,
                'estado' => $hito->estado,
                'porcentaje_completado' => $hito->porcentaje_completado,
            ],
            'usuariosAsignados' => $usuariosAsignados,
            'actividades' => $actividades,
            'usuariosActividades' => $usuariosActividades,
            'camposPersonalizados' => $camposPersonalizados,
            'valoresCamposPersonalizados' => $valoresCamposPersonalizados,
            'contratosRelacionados' => $contratosRelacionados,
            'canEdit' => $user->can('entregables.edit') || $entregable->puedeSerEditadoPor($user),
            'canDelete' => $user->can('entregables.delete'),
            'canComplete' => $entregable->puedeSerCompletadoPor($user),
        ]);
    }
}
