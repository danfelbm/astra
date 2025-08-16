<?php

namespace App\Http\Controllers;

use App\Models\Asamblea;
use App\Models\User;
use App\Traits\HasAdvancedFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AsambleaPublicController extends Controller
{
    use HasAdvancedFilters;

    /**
     * Display a listing of asambleas for the authenticated user
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        
        // Obtener asambleas donde el usuario es participante (solo activas)
        $query = $user->asambleas()
            ->where('activo', true)
            ->with(['territorio', 'departamento', 'municipio', 'localidad']);

        // Aplicar filtro por estado si se proporciona
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Aplicar filtro por tipo si se proporciona
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        // Aplicar búsqueda si se proporciona
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%")
                  ->orWhere('lugar', 'like', "%{$search}%");
            });
        }

        // También incluir asambleas públicas del territorio del usuario si tiene asignado
        if ($user->territorio_id || $user->departamento_id || $user->municipio_id || $user->localidad_id) {
            $queryPublicas = Asamblea::activas()
                ->porTerritorio(
                    $user->territorio_id,
                    $user->departamento_id,
                    $user->municipio_id,
                    $user->localidad_id
                )
                ->with(['territorio', 'departamento', 'municipio', 'localidad'])
                ->whereDoesntHave('participantes', function($q) use ($user) {
                    $q->where('usuario_id', $user->id);
                });

            // Aplicar los mismos filtros que a las asambleas del usuario
            if ($request->filled('estado')) {
                $queryPublicas->where('estado', $request->estado);
            }

            if ($request->filled('tipo')) {
                $queryPublicas->where('tipo', $request->tipo);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $queryPublicas->where(function($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%")
                      ->orWhere('lugar', 'like', "%{$search}%");
                });
            }

            $asambleasPublicas = $queryPublicas->ordenadoPorFecha()->get();
        } else {
            $asambleasPublicas = collect();
        }

        $asambleas = $query->ordenadoPorFecha()
            ->paginate(12)
            ->withQueryString();

        // Enriquecer datos con información de estado para el frontend
        $asambleas->getCollection()->transform(function ($asamblea) use ($user) {
            $participante = $asamblea->participantes->find($user->id);
            
            return [
                'id' => $asamblea->id,
                'nombre' => $asamblea->nombre,
                'descripcion' => $asamblea->descripcion,
                'tipo' => $asamblea->tipo,
                'tipo_label' => $asamblea->getTipoLabel(),
                'estado' => $asamblea->estado,
                'estado_label' => $asamblea->getEstadoLabel(),
                'estado_color' => $asamblea->getEstadoColor(),
                'fecha_inicio' => $asamblea->fecha_inicio,
                'fecha_fin' => $asamblea->fecha_fin,
                'lugar' => $asamblea->lugar,
                'ubicacion_completa' => $asamblea->getUbicacionCompleta(),
                'duracion' => $asamblea->getDuracion(),
                'tiempo_restante' => $asamblea->getTiempoRestante(),
                'rango_fechas' => $asamblea->getRangoFechas(),
                'mi_participacion' => $participante ? [
                    'tipo' => $participante->pivot->tipo_participacion,
                    'asistio' => $participante->pivot->asistio,
                    'hora_registro' => $participante->pivot->hora_registro,
                ] : null,
            ];
        });

        return Inertia::render('Asambleas/Index', [
            'asambleas' => $asambleas,
            'asambleasPublicas' => $asambleasPublicas->map(function ($asamblea) {
                return [
                    'id' => $asamblea->id,
                    'nombre' => $asamblea->nombre,
                    'descripcion' => $asamblea->descripcion,
                    'tipo_label' => $asamblea->getTipoLabel(),
                    'estado_label' => $asamblea->getEstadoLabel(),
                    'estado_color' => $asamblea->getEstadoColor(),
                    'fecha_inicio' => $asamblea->fecha_inicio,
                    'ubicacion_completa' => $asamblea->getUbicacionCompleta(),
                    'rango_fechas' => $asamblea->getRangoFechas(),
                ];
            }),
            'filters' => $request->only(['estado', 'tipo', 'search']),
        ]);
    }

    /**
     * Display the specified asamblea
     */
    public function show(Asamblea $asamblea): Response
    {
        $user = Auth::user();
        
        // Verificar que el usuario sea participante o que la asamblea sea de su territorio
        $esParticipante = $asamblea->participantes()->where('usuario_id', $user->id)->exists();
        $esDesuTerritorio = false;
        
        if (!$esParticipante) {
            // Verificar si la asamblea es del territorio del usuario
            if ($user->localidad_id && $asamblea->localidad_id === $user->localidad_id) {
                $esDesuTerritorio = true;
            } elseif ($user->municipio_id && $asamblea->municipio_id === $user->municipio_id) {
                $esDesuTerritorio = true;
            } elseif ($user->departamento_id && $asamblea->departamento_id === $user->departamento_id) {
                $esDesuTerritorio = true;
            } elseif ($user->territorio_id && $asamblea->territorio_id === $user->territorio_id) {
                $esDesuTerritorio = true;
            }
            
            if (!$esDesuTerritorio) {
                abort(403, 'No tienes permisos para ver esta asamblea');
            }
        }

        // Cargar relaciones y conteos eficientemente
        $asamblea->load([
            'territorio', 
            'departamento', 
            'municipio', 
            'localidad'
        ]);
        
        // Cargar conteos solo si es participante para optimizar
        if ($esParticipante) {
            $asamblea->loadCount([
                'participantes',
                'participantes as asistentes_count' => function ($query) {
                    $query->where('asamblea_usuario.asistio', true);
                }
            ]);
        }

        // Obtener información de mi participación si soy participante
        $miParticipacion = null;
        if ($esParticipante) {
            $participante = $asamblea->participantes->find($user->id);
            $miParticipacion = [
                'tipo' => $participante->pivot->tipo_participacion,
                'asistio' => $participante->pivot->asistio,
                'hora_registro' => $participante->pivot->hora_registro,
            ];
        }

        // Los participantes se cargarán dinámicamente via AJAX

        return Inertia::render('Asambleas/Show', [
            'asamblea' => [
                'id' => $asamblea->id,
                'nombre' => $asamblea->nombre,
                'descripcion' => $asamblea->descripcion,
                'tipo' => $asamblea->tipo,
                'tipo_label' => $asamblea->getTipoLabel(),
                'estado' => $asamblea->estado,
                'estado_label' => $asamblea->getEstadoLabel(),
                'estado_color' => $asamblea->getEstadoColor(),
                'fecha_inicio' => $asamblea->fecha_inicio,
                'fecha_fin' => $asamblea->fecha_fin,
                'lugar' => $asamblea->lugar,
                'territorio' => $asamblea->territorio,
                'departamento' => $asamblea->departamento,
                'municipio' => $asamblea->municipio,
                'localidad' => $asamblea->localidad,
                'ubicacion_completa' => $asamblea->getUbicacionCompleta(),
                'quorum_minimo' => $asamblea->quorum_minimo,
                'acta_url' => $asamblea->acta_url,
                'duracion' => $asamblea->getDuracion(),
                'tiempo_restante' => $asamblea->getTiempoRestante(),
                'rango_fechas' => $asamblea->getRangoFechas(),
                // Usar conteos precalculados si están disponibles, sino usar los métodos
                'alcanza_quorum' => $esParticipante ? 
                    ($asamblea->quorum_minimo ? ($asamblea->asistentes_count ?? 0) >= $asamblea->quorum_minimo : true) : 
                    false,
                'asistentes_count' => $esParticipante ? ($asamblea->asistentes_count ?? 0) : 0,
                'participantes_count' => $esParticipante ? ($asamblea->participantes_count ?? 0) : 0,
                // Campos de videoconferencia
                'zoom_enabled' => $asamblea->zoom_enabled,
                'zoom_integration_type' => $asamblea->zoom_integration_type,
                'zoom_meeting_id' => $asamblea->zoom_meeting_id,
                'zoom_meeting_password' => $asamblea->zoom_meeting_password,
                'zoom_occurrence_ids' => $asamblea->zoom_occurrence_ids,
                'zoom_join_url' => $asamblea->zoom_join_url,
                'zoom_start_url' => $asamblea->zoom_start_url,
                'zoom_estado' => $asamblea->getZoomEstado(),
                'zoom_estado_mensaje' => $asamblea->getZoomEstadoMensaje(),
            ],
            'esParticipante' => $esParticipante,
            'esDesuTerritorio' => $esDesuTerritorio,
            'miParticipacion' => $miParticipacion,
        ]);
    }

    /**
     * Obtener participantes paginados con filtros
     */
    public function getParticipantes(Request $request, Asamblea $asamblea)
    {
        $user = Auth::user();
        
        // Verificar que el usuario sea participante o que la asamblea sea de su territorio
        $esParticipante = $asamblea->participantes()->where('usuario_id', $user->id)->exists();
        $esDesuTerritorio = false;
        
        if (!$esParticipante) {
            // Verificar si la asamblea es del territorio del usuario
            if ($user->localidad_id && $asamblea->localidad_id === $user->localidad_id) {
                $esDesuTerritorio = true;
            } elseif ($user->municipio_id && $asamblea->municipio_id === $user->municipio_id) {
                $esDesuTerritorio = true;
            } elseif ($user->departamento_id && $asamblea->departamento_id === $user->departamento_id) {
                $esDesuTerritorio = true;
            } elseif ($user->territorio_id && $asamblea->territorio_id === $user->territorio_id) {
                $esDesuTerritorio = true;
            }
            
            if (!$esDesuTerritorio) {
                abort(403, 'No tienes permisos para ver los participantes de esta asamblea');
            }
        }

        // Solo mostrar participantes si es participante (no si solo es de su territorio)
        if (!$esParticipante) {
            return response()->json([
                'participantes' => ['data' => [], 'total' => 0, 'current_page' => 1, 'last_page' => 1],
                'filterFieldsConfig' => [],
            ]);
        }

        // Construir query con joins para incluir datos geográficos
        $query = User::query()
            ->join('asamblea_usuario', 'users.id', '=', 'asamblea_usuario.usuario_id')
            ->leftJoin('users as updater', 'asamblea_usuario.updated_by', '=', 'updater.id')
            ->leftJoin('territorios', 'users.territorio_id', '=', 'territorios.id')
            ->leftJoin('departamentos', 'users.departamento_id', '=', 'departamentos.id')
            ->leftJoin('municipios', 'users.municipio_id', '=', 'municipios.id')
            ->leftJoin('localidades', 'users.localidad_id', '=', 'localidades.id')
            ->where('asamblea_usuario.asamblea_id', $asamblea->id);
        
        $query->select('users.*', 
                'asamblea_usuario.tipo_participacion',
                'asamblea_usuario.asistio',
                'asamblea_usuario.hora_registro',
                'asamblea_usuario.updated_by',
                'updater.name as updated_by_name',
                'territorios.nombre as territorio_nombre',
                'departamentos.nombre as departamento_nombre',
                'municipios.nombre as municipio_nombre',
                'localidades.nombre as localidad_nombre'
            );

        // Definir campos permitidos para filtrar (con tabla especificada para evitar ambigüedad)
        $allowedFields = [
            'name', 'email', 'tipo_participacion', 'asistio',
            'users.territorio_id', 'users.departamento_id', 'users.municipio_id', 'users.localidad_id',
        ];
        
        // Campos para búsqueda rápida
        $quickSearchFields = ['name', 'email'];

        // Aplicar filtros avanzados
        $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);
        
        // Aplicar filtros simples si existen
        if ($request->filled('tipo_participacion') && !$request->filled('advanced_filters')) {
            $query->where('asamblea_usuario.tipo_participacion', $request->tipo_participacion);
        }
        
        if ($request->has('asistio') && !$request->filled('advanced_filters')) {
            $query->where('asamblea_usuario.asistio', $request->asistio === 'true' || $request->asistio === '1');
        }

        // Ordenamiento
        $query->orderBy('asamblea_usuario.tipo_participacion')
              ->orderBy('users.name');

        $participantes = $query->paginate(20)->withQueryString();

        // Transformar datos para incluir información geográfica
        $participantes->getCollection()->transform(function ($participante) {
            return [
                'id' => $participante->id,
                'name' => $participante->name,
                'email' => $participante->email,
                'tipo_participacion' => $participante->tipo_participacion,
                'asistio' => $participante->asistio,
                'hora_registro' => $participante->hora_registro,
                'updated_by' => $participante->updated_by,
                'updated_by_name' => $participante->updated_by_name,
                'territorio_nombre' => $participante->territorio_nombre,
                'departamento_nombre' => $participante->departamento_nombre,
                'municipio_nombre' => $participante->municipio_nombre,
                'localidad_nombre' => $participante->localidad_nombre,
            ];
        });

        return response()->json([
            'participantes' => $participantes,
            'filterFieldsConfig' => $this->getParticipantesFilterFieldsConfig(),
        ]);
    }

    /**
     * Obtener configuración de campos para filtros avanzados de participantes
     * Los campos geográficos se manejan en el frontend con useGeographicFilters
     */
    protected function getParticipantesFilterFieldsConfig(): array
    {
        // Solo campos básicos del usuario
        // Los campos geográficos se añadirán dinámicamente en el frontend
        return [
            [
                'name' => 'name',
                'label' => 'Nombre',
                'type' => 'text',
            ],
            [
                'name' => 'email',
                'label' => 'Email',
                'type' => 'text',
            ],
            [
                'name' => 'tipo_participacion',
                'label' => 'Tipo de Participación',
                'type' => 'select',
                'options' => [
                    ['value' => 'asistente', 'label' => 'Asistente'],
                    ['value' => 'moderador', 'label' => 'Moderador'],
                    ['value' => 'secretario', 'label' => 'Secretario'],
                ],
            ],
            [
                'name' => 'asistio',
                'label' => 'Asistencia',
                'type' => 'select',
                'options' => [
                    ['value' => 1, 'label' => 'Presente'],
                    ['value' => 0, 'label' => 'Ausente'],
                ],
            ],
        ];
    }

    /**
     * Marcar asistencia del usuario actual a la asamblea
     */
    public function marcarAsistencia(Request $request, Asamblea $asamblea)
    {
        $user = Auth::user();
        
        // Verificar que el usuario sea participante
        $esParticipante = $asamblea->participantes()
            ->where('usuario_id', $user->id)
            ->exists();
        
        if (!$esParticipante) {
            return response()->json([
                'success' => false,
                'message' => 'No eres participante de esta asamblea'
            ], 403);
        }
        
        // Verificar que la asamblea esté en curso
        if ($asamblea->estado !== 'en_curso') {
            return response()->json([
                'success' => false,
                'message' => 'La asamblea no está en curso'
            ], 400);
        }
        
        // Actualizar asistencia
        $asamblea->participantes()->updateExistingPivot($user->id, [
            'asistio' => true,
            'hora_registro' => now(),
            'updated_by' => $user->id  // Auto-registro
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Asistencia registrada exitosamente'
        ]);
    }
}
