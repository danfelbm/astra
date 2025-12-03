<?php

namespace Modules\Proyectos\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Proyectos\Models\Entregable;
use Modules\Proyectos\Repositories\CampoPersonalizadoRepository;
use Modules\Proyectos\Repositories\CategoriaEtiquetaRepository;

/**
 * Controlador API para obtener y actualizar detalles de entregables.
 * Usado por el EntregableDetallesModal en el frontend.
 */
class EntregableApiController
{
    public function __construct(
        private CampoPersonalizadoRepository $campoPersonalizadoRepository,
        private CategoriaEtiquetaRepository $categoriaEtiquetaRepository
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

        // Verificar acceso: mismo criterio que MisProyectosController para consistencia
        $tieneAcceso = $user->hasAdministrativeAccess() ||
            // Responsable directo del entregable o asignado
            $entregable->responsable_id === $user->id ||
            $entregable->usuarios()->where('users.id', $user->id)->exists() ||
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
                })->exists();

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

        // Datos adicionales para edición inline
        $estados = [
            ['value' => 'pendiente', 'label' => 'Pendiente'],
            ['value' => 'en_progreso', 'label' => 'En Progreso'],
            ['value' => 'completado', 'label' => 'Completado'],
            ['value' => 'cancelado', 'label' => 'Cancelado'],
        ];

        $prioridades = [
            ['value' => 'baja', 'label' => 'Baja', 'color' => 'bg-blue-100 text-blue-800'],
            ['value' => 'media', 'label' => 'Media', 'color' => 'bg-yellow-100 text-yellow-800'],
            ['value' => 'alta', 'label' => 'Alta', 'color' => 'bg-red-100 text-red-800'],
        ];

        // Obtener categorías de etiquetas para entregables
        $categorias = $this->categoriaEtiquetaRepository->getCategoriasConEtiquetasPorTipo('entregable');

        // Endpoint de búsqueda de usuarios del proyecto
        $searchUsersEndpoint = route('admin.proyectos.search-users');

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
            // Datos para edición inline
            'estados' => $estados,
            'prioridades' => $prioridades,
            'categorias' => $categorias,
            'searchUsersEndpoint' => $searchUsersEndpoint,
            // Permisos
            'canEdit' => $user->can('entregables.edit') || $entregable->puedeSerEditadoPor($user),
            'canDelete' => $user->can('entregables.delete'),
            'canComplete' => $entregable->puedeSerCompletadoPor($user),
        ]);
    }

    /**
     * Actualizar un campo individual del entregable (edición inline).
     */
    public function updateField(Request $request, Entregable $entregable): JsonResponse
    {
        $user = auth()->user();

        // Verificar permiso de edición
        $canEdit = $user->can('entregables.edit') || $entregable->puedeSerEditadoPor($user);
        if (!$canEdit) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para editar este entregable'
            ], 403);
        }

        // Validar request básico
        $request->validate([
            'field' => 'required|string',
            'value' => 'nullable',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $field = $request->input('field');
        // Para archivos usar file(), para otros valores usar input()
        $value = $request->hasFile('value') ? $request->file('value') : $request->input('value');
        $observaciones = $request->input('observaciones');

        // Campos directos del modelo que se pueden editar
        $camposDirectos = ['nombre', 'descripcion', 'fecha_inicio', 'fecha_fin', 'estado', 'prioridad', 'responsable_id', 'notas', 'orden'];

        DB::beginTransaction();
        try {
            // Manejar campos directos
            if (in_array($field, $camposDirectos)) {
                $this->validarCampoDirecto($field, $value, $entregable);
                // Capturar valor anterior antes de modificar
                $valorAnterior = $entregable->$field;

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
                }

                // Manejo especial para cambio de estado
                if ($field === 'estado') {
                    $estadoAnterior = $entregable->estado;
                    $entregable->estado = $value;

                    // Si cambia a completado, registrar quién y cuándo
                    if ($value === 'completado' && $estadoAnterior !== 'completado') {
                        $entregable->completado_at = now();
                        $entregable->completado_por = $user->id;
                        if ($observaciones) {
                            $entregable->observaciones_estado = $observaciones;
                        }
                    }
                    // Si deja de estar completado, limpiar los campos
                    elseif ($value !== 'completado' && $estadoAnterior === 'completado') {
                        $entregable->completado_at = null;
                        $entregable->completado_por = null;
                        $entregable->observaciones_estado = null;
                    }
                } else {
                    $entregable->$field = $value;
                }

                // Usar saveQuietly() para evitar que el trait LogsActivity genere log automático
                $entregable->saveQuietly();

                // Registrar audit log manual si hubo cambio
                if ($valorAnterior != $value) {
                    $entregable->logStateChange($field, $valorAnteriorLegible, $valorNuevoLegible);
                }
            }
            // Manejar etiquetas
            elseif ($field === 'etiquetas') {
                $etiquetaIds = is_array($value) ? $value : [];
                // Cargar y capturar etiquetas anteriores (nombres)
                $entregable->load('etiquetas');
                $nombresAnteriores = $entregable->etiquetas->pluck('nombre')->toArray();
                $idsAnteriores = $entregable->etiquetas->pluck('id')->toArray();
                $entregable->etiquetas()->sync($etiquetaIds);

                // Obtener nombres de las nuevas etiquetas
                $nombresNuevos = [];
                if (count($etiquetaIds)) {
                    $nombresNuevos = \Modules\Proyectos\Models\Etiqueta::whereIn('id', $etiquetaIds)
                        ->pluck('nombre')->toArray();
                }

                // Registrar audit log si hubo cambio
                if ($idsAnteriores != $etiquetaIds) {
                    $entregable->logStateChange(
                        'etiquetas',
                        count($nombresAnteriores) ? implode(', ', $nombresAnteriores) : '(ninguna)',
                        count($nombresNuevos) ? implode(', ', $nombresNuevos) : '(ninguna)'
                    );
                }
            }
            // Manejar colaboradores (usuarios asignados)
            elseif ($field === 'usuarios') {
                $usuarios = is_array($value) ? $value : [];
                // Cargar y capturar usuarios anteriores (nombres)
                $entregable->load('usuarios');
                $nombresAnteriores = $entregable->usuarios->pluck('name')->toArray();
                $idsAnteriores = $entregable->usuarios->pluck('id')->toArray();

                $syncData = [];
                foreach ($usuarios as $u) {
                    $syncData[$u['user_id']] = ['rol' => $u['rol'] ?? 'colaborador'];
                }
                $entregable->usuarios()->sync($syncData);

                // Obtener nombres de los nuevos usuarios
                $nuevosIds = collect($usuarios)->pluck('user_id')->toArray();
                $nombresNuevos = [];
                if (count($nuevosIds)) {
                    $nombresNuevos = \Modules\Core\Models\User::whereIn('id', $nuevosIds)
                        ->pluck('name')->toArray();
                }

                // Registrar audit log si hubo cambio
                if ($idsAnteriores != $nuevosIds) {
                    $entregable->logStateChange(
                        'usuarios',
                        count($nombresAnteriores) ? implode(', ', $nombresAnteriores) : '(ninguno)',
                        count($nombresNuevos) ? implode(', ', $nombresNuevos) : '(ninguno)'
                    );
                }
            }
            // Manejar campos personalizados
            elseif (str_starts_with($field, 'campo_personalizado_')) {
                $campoId = (int) str_replace('campo_personalizado_', '', $field);
                // Capturar valor anterior
                $valoresActuales = $entregable->getCamposPersonalizadosValues();
                $valorAnterior = $valoresActuales[$campoId] ?? null;

                // Si es archivo, obtener nombre original antes de guardar
                $valorNuevoLegible = $value;
                if ($value instanceof \Illuminate\Http\UploadedFile) {
                    $valorNuevoLegible = $value->getClientOriginalName();
                }

                $entregable->setCampoPersonalizadoValue($campoId, $value);

                // Obtener el valor final guardado (nombre del archivo procesado)
                $entregable->load('camposPersonalizados');
                $valoresFinales = $entregable->getCamposPersonalizadosValues();
                $valorFinal = $valoresFinales[$campoId] ?? null;

                // Registrar audit log si hubo cambio
                if ($valorAnterior != $valorFinal) {
                    $entregable->logStateChange(
                        $field,
                        $valorAnterior ?? '(vacío)',
                        $valorNuevoLegible ?? '(vacío)'
                    );
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
            $entregable->load([
                'responsable:id,name,email,avatar',
                'usuarios:id,name,email,avatar',
                'etiquetas',
                'completadoPor:id,name,email'
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
            ])->values();

            return response()->json([
                'success' => true,
                'message' => 'Campo actualizado correctamente',
                'entregable' => $entregable,
                'usuariosAsignados' => $usuariosAsignados,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Validar un campo directo antes de guardar.
     */
    private function validarCampoDirecto(string $field, $value, Entregable $entregable): void
    {
        $rules = match ($field) {
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            // Estado y prioridad son nullable para permitir entregables sin valores asignados
            'estado' => ['nullable', Rule::in(['pendiente', 'en_progreso', 'completado', 'cancelado'])],
            'prioridad' => ['nullable', Rule::in(['baja', 'media', 'alta'])],
            'responsable_id' => ['nullable', 'exists:users,id'],
            'notas' => ['nullable', 'string', 'max:5000'],
            'orden' => ['nullable', 'integer', 'min:1'],
            default => [],
        };

        if (!empty($rules)) {
            validator(['value' => $value], ['value' => $rules])->validate();
        }
    }
}
