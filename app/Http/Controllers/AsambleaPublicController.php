<?php

namespace App\Http\Controllers;

use App\Models\Asamblea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AsambleaPublicController extends Controller
{
    /**
     * Display a listing of asambleas for the authenticated user
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        
        // Obtener asambleas donde el usuario es participante
        $query = $user->asambleas()
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
            $asambleasPublicas = Asamblea::activas()
                ->porTerritorio(
                    $user->territorio_id,
                    $user->departamento_id,
                    $user->municipio_id,
                    $user->localidad_id
                )
                ->with(['territorio', 'departamento', 'municipio', 'localidad'])
                ->whereDoesntHave('participantes', function($q) use ($user) {
                    $q->where('usuario_id', $user->id);
                })
                ->get();
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

        $asamblea->load([
            'territorio', 
            'departamento', 
            'municipio', 
            'localidad',
            'participantes' => function($query) {
                $query->orderBy('asamblea_usuario.tipo_participacion')
                      ->orderBy('users.name');
            }
        ]);

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

        // Preparar lista de participantes (solo mostrar si es participante)
        $participantes = [];
        if ($esParticipante) {
            $participantes = $asamblea->participantes->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'email' => $p->email,
                    'tipo_participacion' => $p->pivot->tipo_participacion,
                    'asistio' => $p->pivot->asistio,
                    'hora_registro' => $p->pivot->hora_registro,
                ];
            });
        }

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
                'alcanza_quorum' => $asamblea->alcanzaQuorum(),
                'asistentes_count' => $asamblea->getAsistentesCount(),
                'participantes_count' => $asamblea->getParticipantesCount(),
            ],
            'esParticipante' => $esParticipante,
            'esDesuTerritorio' => $esDesuTerritorio,
            'miParticipacion' => $miParticipacion,
            'participantes' => $participantes,
        ]);
    }
}
