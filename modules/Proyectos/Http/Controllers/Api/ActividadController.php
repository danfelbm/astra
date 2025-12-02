<?php

namespace Modules\Proyectos\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Proyectos\Models\Hito;
use Modules\Proyectos\Models\Entregable;

/**
 * Controlador API para obtener actividades (audit log) de hitos y entregables.
 * Usado por el ActividadSheet en el frontend.
 */
class ActividadController
{
    /**
     * Obtener actividades de un hito (incluye actividades de sus entregables).
     */
    public function hitoActividades(Request $request, Hito $hito): JsonResponse
    {
        // Verificar que el usuario tiene acceso al hito
        $user = auth()->user();

        // Permitir si es admin, responsable del hito/proyecto, o tiene entregables asignados
        $tieneAcceso = $user->hasAdministrativeAccess() ||
            $hito->responsable_id === $user->id ||
            $hito->proyecto->responsable_id === $user->id ||
            $hito->proyecto->gestores()->where('user_id', $user->id)->exists() ||
            $hito->entregables()->where(function ($q) use ($user) {
                $q->where('responsable_id', $user->id)
                  ->orWhereHas('usuarios', function ($q2) use ($user) {
                      $q2->where('users.id', $user->id);
                  });
            })->exists();

        if (!$tieneAcceso) {
            return response()->json(['message' => 'No tienes acceso a este hito'], 403);
        }

        // Obtener actividades del hito
        $actividades = $hito->getActivityLogs();

        // Incluir actividades de los entregables del hito
        $hito->load('entregables');
        foreach ($hito->entregables as $entregable) {
            $actividades = $actividades->merge($entregable->getActivityLogs());
        }

        // Ordenar por fecha descendente y limitar
        $actividades = $actividades
            ->sortByDesc('created_at')
            ->take(100)
            ->values();

        // Extraer usuarios únicos para filtros
        $usuarios = $actividades
            ->pluck('causer')
            ->filter()
            ->unique('id')
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ])
            ->values();

        return response()->json([
            'actividades' => $actividades,
            'usuarios' => $usuarios
        ]);
    }

    /**
     * Obtener actividades de un entregable específico.
     */
    public function entregableActividades(Request $request, Entregable $entregable): JsonResponse
    {
        // Verificar que el usuario tiene acceso al entregable
        $user = auth()->user();
        $hito = $entregable->hito;
        $proyecto = $hito->proyecto;

        // Permitir si es admin, responsable, gestor, o usuario asignado
        $tieneAcceso = $user->hasAdministrativeAccess() ||
            $entregable->responsable_id === $user->id ||
            $hito->responsable_id === $user->id ||
            $proyecto->responsable_id === $user->id ||
            $proyecto->gestores()->where('user_id', $user->id)->exists() ||
            $entregable->usuarios()->where('users.id', $user->id)->exists();

        if (!$tieneAcceso) {
            return response()->json(['message' => 'No tienes acceso a este entregable'], 403);
        }

        // Obtener actividades del entregable
        $actividades = $entregable->getActivityLogs()
            ->sortByDesc('created_at')
            ->take(100)
            ->values();

        // Extraer usuarios únicos para filtros
        $usuarios = $actividades
            ->pluck('causer')
            ->filter()
            ->unique('id')
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ])
            ->values();

        return response()->json([
            'actividades' => $actividades,
            'usuarios' => $usuarios
        ]);
    }
}
