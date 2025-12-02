<?php

namespace Modules\Proyectos\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Proyectos\Models\Hito;
use Modules\Proyectos\Repositories\CampoPersonalizadoRepository;

/**
 * Controlador API para obtener detalles de hitos.
 * Usado por el HitoDetallesModal en el frontend.
 */
class HitoApiController
{
    public function __construct(
        private CampoPersonalizadoRepository $campoPersonalizadoRepository
    ) {}

    /**
     * Obtener detalles completos de un hito para el modal.
     */
    public function detalles(Hito $hito): JsonResponse
    {
        // Verificar que el usuario tiene acceso al hito
        $user = auth()->user();
        $proyecto = $hito->proyecto;

        $tieneAcceso = $user->hasAdministrativeAccess() ||
            $hito->responsable_id === $user->id ||
            $proyecto->responsable_id === $user->id ||
            $proyecto->gestores()->where('user_id', $user->id)->exists() ||
            $hito->entregables()->where(function ($q) use ($user) {
                $q->where('responsable_id', $user->id)
                  ->orWhereHas('usuarios', function ($q2) use ($user) {
                      $q2->where('users.id', $user->id);
                  });
            })->exists();

        if (!$tieneAcceso) {
            return response()->json(['message' => 'No tienes acceso a este hito'], 403);
        }

        // Cargar relaciones necesarias
        $hito->load([
            'proyecto:id,nombre,descripcion',
            'responsable:id,name,email,avatar',
            'parent:id,nombre',
            'etiquetas',
            'entregables' => function ($query) {
                $query->with(['responsable:id,name,email,avatar', 'usuarios:id,name,email,avatar'])
                      ->orderBy('orden');
            },
            'camposPersonalizados.campoPersonalizado'
        ]);

        // Calcular estadísticas del hito
        $estadisticas = [
            'total_entregables' => $hito->entregables->count(),
            'entregables_completados' => $hito->entregables->where('estado', 'completado')->count(),
            'entregables_pendientes' => $hito->entregables->where('estado', 'pendiente')->count(),
            'entregables_en_progreso' => $hito->entregables->where('estado', 'en_progreso')->count(),
            'entregables_cancelados' => $hito->entregables->where('estado', 'cancelado')->count(),
            'porcentaje_completado' => $hito->porcentaje_completado,
            'dias_restantes' => $hito->dias_restantes,
            'esta_vencido' => $hito->esta_vencido,
        ];

        // Obtener campos personalizados con sus valores
        $camposPersonalizados = $this->campoPersonalizadoRepository->getActivosParaHitos();
        $valoresCamposPersonalizados = $hito->getCamposPersonalizadosValues();

        // Obtener actividades acumuladas del hito + entregables
        $actividadesHito = $hito->getActivityLogs();
        $actividadesEntregables = collect();

        foreach ($hito->entregables as $entregable) {
            $actividadesEntregables = $actividadesEntregables->merge($entregable->getActivityLogs());
        }

        // Combinar actividades y ordenar por fecha descendente
        $actividades = $actividadesHito
            ->merge($actividadesEntregables)
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

        // Obtener usuarios únicos de los entregables para filtros
        $usuariosEntregables = $hito->entregables
            ->pluck('responsable')
            ->filter()
            ->unique('id')
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email
            ])
            ->values();

        return response()->json([
            'hito' => $hito,
            'estadisticas' => $estadisticas,
            'actividades' => $actividades,
            'usuariosActividades' => $usuariosActividades,
            'usuariosEntregables' => $usuariosEntregables,
            'camposPersonalizados' => $camposPersonalizados,
            'valoresCamposPersonalizados' => $valoresCamposPersonalizados,
            'canEdit' => $user->can('hitos.edit'),
            'canDelete' => $user->can('hitos.delete'),
            'canManageEntregables' => $user->can('hitos.manage_deliverables') || $user->can('entregables.create'),
            'canComplete' => $user->can('entregables.complete'),
        ]);
    }
}
