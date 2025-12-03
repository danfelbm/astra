<?php

namespace Modules\Proyectos\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Proyectos\Models\Hito;
use Modules\Proyectos\Repositories\CampoPersonalizadoRepository;
use Modules\Proyectos\Repositories\CategoriaEtiquetaRepository;

/**
 * Controlador API para obtener y actualizar detalles de hitos.
 * Usado por el HitoDetallesModal en el frontend.
 */
class HitoApiController
{
    public function __construct(
        private CampoPersonalizadoRepository $campoPersonalizadoRepository,
        private CategoriaEtiquetaRepository $categoriaEtiquetaRepository
    ) {}

    /**
     * Obtener detalles completos de un hito para el modal.
     */
    public function detalles(Hito $hito): JsonResponse
    {
        // Verificar que el usuario tiene acceso al hito
        $user = auth()->user();
        $proyecto = $hito->proyecto;

        // Verificar acceso: mismo criterio que MisProyectosController para consistencia
        $tieneAcceso = $user->hasAdministrativeAccess() ||
            // Responsable del hito
            $hito->responsable_id === $user->id ||
            // Responsable del proyecto
            $proyecto->responsable_id === $user->id ||
            // Creador del proyecto
            $proyecto->created_by === $user->id ||
            // Gestor del proyecto
            $proyecto->gestores()->where('user_id', $user->id)->exists() ||
            // Participante del proyecto (cualquier rol)
            $proyecto->participantes()->where('user_id', $user->id)->exists() ||
            // Acceso vía contratos (responsable o contraparte)
            $proyecto->contratos()
                ->where(function ($q) use ($user) {
                    $q->where('responsable_id', $user->id)
                      ->orWhere('contraparte_user_id', $user->id);
                })->exists() ||
            // Responsable o asignado a algún entregable del hito
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

        // Obtener datos adicionales para edición inline
        $estados = [
            ['value' => 'pendiente', 'label' => 'Pendiente'],
            ['value' => 'en_progreso', 'label' => 'En Progreso'],
            ['value' => 'completado', 'label' => 'Completado'],
            ['value' => 'cancelado', 'label' => 'Cancelado'],
        ];

        // Obtener hitos disponibles para selector de padre (excepto el actual y sus hijos)
        $hitosDisponibles = Hito::where('proyecto_id', $proyecto->id)
            ->where('id', '!=', $hito->id)
            ->whereNotIn('id', $hito->descendants()->pluck('id')->toArray())
            ->orderBy('orden')
            ->get(['id', 'nombre', 'nivel'])
            ->map(fn($h) => [
                'value' => (string) $h->id,
                'label' => str_repeat('— ', $h->nivel) . $h->nombre
            ]);

        // Obtener categorías de etiquetas para hitos
        $categorias = $this->categoriaEtiquetaRepository->getCategoriasConEtiquetasPorTipo('hito');

        // Endpoint de búsqueda de usuarios del proyecto
        $searchUsersEndpoint = route('admin.proyectos.search-users');

        return response()->json([
            'hito' => $hito,
            'estadisticas' => $estadisticas,
            'actividades' => $actividades,
            'usuariosActividades' => $usuariosActividades,
            'usuariosEntregables' => $usuariosEntregables,
            'camposPersonalizados' => $camposPersonalizados,
            'valoresCamposPersonalizados' => $valoresCamposPersonalizados,
            // Datos para edición inline
            'estados' => $estados,
            'hitosDisponibles' => $hitosDisponibles,
            'categorias' => $categorias,
            'searchUsersEndpoint' => $searchUsersEndpoint,
            // Permisos (incluir verificación de gestor/responsable del proyecto)
            'canEdit' => $user->can('hitos.edit') || $this->puedeEditarHito($user, $hito),
            'canDelete' => $user->can('hitos.delete'),
            'canManageEntregables' => $user->can('hitos.manage_deliverables') || $user->can('entregables.create') || $this->puedeEditarHito($user, $hito),
            'canComplete' => $user->can('entregables.complete') || $this->puedeEditarHito($user, $hito),
        ]);
    }

    /**
     * Actualizar un campo individual del hito (edición inline).
     */
    public function updateField(Request $request, Hito $hito): JsonResponse
    {
        $user = auth()->user();

        // Verificar permiso de edición
        $canEdit = $user->can('hitos.edit') || $this->puedeEditarHito($user, $hito);
        if (!$canEdit) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para editar este hito'
            ], 403);
        }

        // Validar request básico
        $request->validate([
            'field' => 'required|string',
            'value' => 'nullable',
        ]);

        $field = $request->input('field');
        // Para archivos usar file(), para otros valores usar input()
        $value = $request->hasFile('value') ? $request->file('value') : $request->input('value');

        // Campos directos del modelo que se pueden editar
        $camposDirectos = ['nombre', 'descripcion', 'fecha_inicio', 'fecha_fin', 'estado', 'responsable_id', 'parent_id', 'orden'];

        DB::beginTransaction();
        try {
            // Deshabilitar log automático del modelo para evitar duplicados
            // El trait LogsActivity verifica $this->enableLoggingModelsEvents
            $hito->disableLogging();

            // Manejar campos directos
            if (in_array($field, $camposDirectos)) {
                $this->validarCampoDirecto($field, $value, $hito);
                // Capturar valor anterior antes de modificar
                $valorAnterior = $hito->$field;

                // Para campos de relación, obtener nombres legibles
                $valorAnteriorLegible = $valorAnterior;
                $valorNuevoLegible = $value;

                if ($field === 'responsable_id') {
                    // Obtener nombre del responsable anterior
                    $valorAnteriorLegible = $valorAnterior
                        ? \Modules\Core\Models\User::find($valorAnterior)?->name ?? "(ID: {$valorAnterior})"
                        : '(ninguno)';
                    // Obtener nombre del nuevo responsable
                    $valorNuevoLegible = $value
                        ? \Modules\Core\Models\User::find($value)?->name ?? "(ID: {$value})"
                        : '(ninguno)';
                } elseif ($field === 'parent_id') {
                    // Obtener nombre del hito padre anterior
                    $valorAnteriorLegible = $valorAnterior
                        ? Hito::find($valorAnterior)?->nombre ?? "(ID: {$valorAnterior})"
                        : '(ninguno)';
                    // Obtener nombre del nuevo hito padre
                    $valorNuevoLegible = $value
                        ? Hito::find($value)?->nombre ?? "(ID: {$value})"
                        : '(ninguno)';
                }

                $hito->$field = $value;
                $hito->save();

                // Registrar audit log manual si hubo cambio
                if ($valorAnterior != $value) {
                    $hito->enableLogging();
                    $hito->logStateChange($field, $valorAnteriorLegible, $valorNuevoLegible);
                    $hito->disableLogging(); // Mantener deshabilitado por si hay más operaciones
                }
            }
            // Manejar etiquetas
            elseif ($field === 'etiquetas') {
                $etiquetaIds = is_array($value) ? $value : [];
                // Cargar y capturar etiquetas anteriores (nombres)
                $hito->load('etiquetas');
                $nombresAnteriores = $hito->etiquetas->pluck('nombre')->toArray();
                $idsAnteriores = $hito->etiquetas->pluck('id')->toArray();
                $hito->etiquetas()->sync($etiquetaIds);

                // Obtener nombres de las nuevas etiquetas
                $nombresNuevos = [];
                if (count($etiquetaIds)) {
                    $nombresNuevos = \Modules\Proyectos\Models\Etiqueta::whereIn('id', $etiquetaIds)
                        ->pluck('nombre')->toArray();
                }

                // Registrar audit log si hubo cambio
                if ($idsAnteriores != $etiquetaIds) {
                    $hito->enableLogging();
                    $hito->logStateChange(
                        'etiquetas',
                        count($nombresAnteriores) ? implode(', ', $nombresAnteriores) : '(ninguna)',
                        count($nombresNuevos) ? implode(', ', $nombresNuevos) : '(ninguna)'
                    );
                    $hito->disableLogging();
                }
            }
            // Manejar campos personalizados
            elseif (str_starts_with($field, 'campo_personalizado_')) {
                $campoId = (int) str_replace('campo_personalizado_', '', $field);
                // Capturar valor anterior
                $valoresActuales = $hito->getCamposPersonalizadosValues();
                $valorAnterior = $valoresActuales[$campoId] ?? null;

                // Si es archivo, obtener nombre original antes de guardar
                $valorNuevoLegible = $value;
                if ($value instanceof \Illuminate\Http\UploadedFile) {
                    $valorNuevoLegible = $value->getClientOriginalName();
                }

                $hito->setCampoPersonalizadoValue($campoId, $value);

                // Obtener el valor final guardado (nombre del archivo procesado)
                $hito->load('camposPersonalizados');
                $valoresFinales = $hito->getCamposPersonalizadosValues();
                $valorFinal = $valoresFinales[$campoId] ?? null;

                // Registrar audit log si hubo cambio
                if ($valorAnterior != $valorFinal) {
                    $hito->enableLogging();
                    $hito->logStateChange(
                        $field,
                        $valorAnterior ?? '(vacío)',
                        $valorNuevoLegible ?? '(vacío)'
                    );
                    $hito->disableLogging();
                }
            }
            else {
                return response()->json([
                    'success' => false,
                    'message' => "Campo '{$field}' no es editable"
                ], 400);
            }

            DB::commit();

            // Recargar relaciones para devolver datos actualizados
            $hito->load(['responsable:id,name,email,avatar', 'parent:id,nombre', 'etiquetas']);

            // Rehabilitar logging del modelo
            $hito->enableLogging();

            return response()->json([
                'success' => true,
                'message' => 'Campo actualizado correctamente',
                'hito' => $hito,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $hito->enableLogging(); // Rehabilitar logging en caso de error
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Validar un campo directo antes de guardar.
     */
    private function validarCampoDirecto(string $field, $value, Hito $hito): void
    {
        $rules = match ($field) {
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'estado' => ['required', Rule::in(['pendiente', 'en_progreso', 'completado', 'cancelado'])],
            'responsable_id' => ['nullable', 'exists:users,id'],
            'parent_id' => ['nullable', 'exists:hitos,id'],
            'orden' => ['nullable', 'integer', 'min:1'],
            default => [],
        };

        if (!empty($rules)) {
            validator(['value' => $value], ['value' => $rules])->validate();
        }

        // Validación adicional: parent_id no puede ser el mismo hito ni un descendiente
        if ($field === 'parent_id' && $value) {
            if ($value == $hito->id) {
                throw new \Exception('El hito padre no puede ser el mismo hito');
            }
            if ($hito->descendants()->where('id', $value)->exists()) {
                throw new \Exception('El hito padre no puede ser un descendiente del hito actual');
            }
        }
    }

    /**
     * Verificar si el usuario puede editar el hito (sin permiso global).
     */
    private function puedeEditarHito($user, Hito $hito): bool
    {
        $proyecto = $hito->proyecto;

        return $user->hasAdministrativeAccess() ||
            $hito->responsable_id === $user->id ||
            $proyecto->responsable_id === $user->id ||
            $proyecto->gestores()->where('user_id', $user->id)->exists();
    }
}
