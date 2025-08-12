<?php

namespace App\Http\Controllers;

use App\Models\Votacion;
use App\Models\Voto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ResultadosController extends Controller
{
    /**
     * Mostrar la página principal de resultados con las tres pestañas.
     */
    public function show(Votacion $votacion): Response
    {
        // Verificar que los resultados sean públicos y visibles
        if (!$votacion->resultadosVisibles()) {
            abort(404, 'Los resultados de esta votación no están disponibles públicamente.');
        }

        $votacion->load('categoria');

        return Inertia::render('Votaciones/Resultados', [
            'votacion' => [
                'id' => $votacion->id,
                'titulo' => $votacion->titulo,
                'descripcion' => $votacion->descripcion,
                'categoria' => $votacion->categoria,
                'formulario_config' => $votacion->formulario_config,
                'fecha_inicio' => $votacion->fecha_inicio->format('Y-m-d H:i:s'),
                'fecha_fin' => $votacion->fecha_fin->format('Y-m-d H:i:s'),
                'fecha_publicacion_resultados' => $votacion->fecha_publicacion_resultados?->format('Y-m-d H:i:s'),
                'total_votos' => $votacion->votos()->count(),
            ],
            'user' => [
                'es_admin' => (auth()->user()?->isAdmin() || auth()->user()?->isSuperAdmin()) ?? false,
            ],
        ]);
    }

    /**
     * Obtener datos consolidados por pregunta para la pestaña 1.
     */
    public function consolidado(Votacion $votacion)
    {
        // Verificar que los resultados sean públicos y visibles
        if (!$votacion->resultadosVisibles()) {
            abort(404, 'Los resultados de esta votación no están disponibles públicamente.');
        }

        $formularioConfig = $votacion->formulario_config;
        $resultados = [];
        $totalVotos = $votacion->votos()->count();

        foreach ($formularioConfig as $pregunta) {
            $preguntaId = $pregunta['id'];
            $preguntaData = [
                'id' => $preguntaId,
                'titulo' => $pregunta['title'],
                'tipo' => $pregunta['type'],
                'opciones' => $pregunta['options'] ?? [],
                'respuestas' => [],
                'total_respuestas' => 0,
            ];

            if (in_array($pregunta['type'], ['select', 'radio'])) {
                // Para preguntas de opción única
                $respuestas = DB::table('votos')
                    ->where('votacion_id', $votacion->id)
                    ->select(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(respuestas, '$.{$preguntaId}')) as respuesta"))
                    ->whereNotNull(DB::raw("JSON_EXTRACT(respuestas, '$.{$preguntaId}')"))
                    ->get();

                $conteos = $respuestas->countBy('respuesta');
                
                foreach ($pregunta['options'] as $opcion) {
                    $cantidad = $conteos[$opcion] ?? 0;
                    $porcentaje = $totalVotos > 0 ? round(($cantidad / $totalVotos) * 100, 2) : 0;
                    
                    $preguntaData['respuestas'][] = [
                        'opcion' => $opcion,
                        'cantidad' => $cantidad,
                        'porcentaje' => $porcentaje,
                    ];
                }
                
                $preguntaData['total_respuestas'] = $respuestas->count();
                
            } elseif ($pregunta['type'] === 'checkbox') {
                // Para preguntas de opción múltiple
                $respuestas = DB::table('votos')
                    ->where('votacion_id', $votacion->id)
                    ->select(DB::raw("JSON_EXTRACT(respuestas, '$.{$preguntaId}') as respuesta"))
                    ->whereNotNull(DB::raw("JSON_EXTRACT(respuestas, '$.{$preguntaId}')"))
                    ->get();

                $todasLasOpciones = [];
                foreach ($respuestas as $respuesta) {
                    $opciones = json_decode($respuesta->respuesta, true);
                    if (is_array($opciones)) {
                        $todasLasOpciones = array_merge($todasLasOpciones, $opciones);
                    }
                }
                
                $conteos = collect($todasLasOpciones)->countBy();
                
                foreach ($pregunta['options'] as $opcion) {
                    $cantidad = $conteos[$opcion] ?? 0;
                    $porcentaje = $totalVotos > 0 ? round(($cantidad / $totalVotos) * 100, 2) : 0;
                    
                    $preguntaData['respuestas'][] = [
                        'opcion' => $opcion,
                        'cantidad' => $cantidad,
                        'porcentaje' => $porcentaje,
                    ];
                }
                
                $preguntaData['total_respuestas'] = $respuestas->count();
                
            } else {
                // Para preguntas abiertas (text, textarea)
                $respuestas = DB::table('votos')
                    ->where('votacion_id', $votacion->id)
                    ->select(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(respuestas, '$.{$preguntaId}')) as respuesta"))
                    ->whereNotNull(DB::raw("JSON_EXTRACT(respuestas, '$.{$preguntaId}')"))
                    ->where(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(respuestas, '$.{$preguntaId}'))"), '!=', '')
                    ->get();

                $preguntaData['respuestas'] = $respuestas->pluck('respuesta')->toArray();
                $preguntaData['total_respuestas'] = $respuestas->count();
            }

            $resultados[] = $preguntaData;
        }

        return response()->json([
            'resultados' => $resultados,
            'total_votos' => $totalVotos,
        ]);
    }

    /**
     * Obtener resultados agrupados por territorio para la pestaña 2.
     */
    public function territorio(Votacion $votacion, Request $request)
    {
        // Verificar que los resultados sean públicos y visibles
        if (!$votacion->resultadosVisibles()) {
            abort(404, 'Los resultados de esta votación no están disponibles públicamente.');
        }

        $agrupacion = $request->get('agrupacion', 'territorio'); // territorio, departamento, municipio
        
        $query = DB::table('votos')
            ->join('users', 'votos.usuario_id', '=', 'users.id')
            ->where('votos.votacion_id', $votacion->id);

        switch ($agrupacion) {
            case 'departamento':
                $query->select('users.departamento_id as grupo_id', 
                              DB::raw('COUNT(*) as total_votos'))
                      ->groupBy('users.departamento_id');
                break;
            case 'municipio':
                $query->select('users.municipio_id as grupo_id',
                              'users.departamento_id',
                              DB::raw('COUNT(*) as total_votos'))
                      ->groupBy('users.municipio_id', 'users.departamento_id');
                break;
            default: // territorio
                $query->select('users.territorio_id as grupo_id',
                              DB::raw('COUNT(*) as total_votos'))
                      ->groupBy('users.territorio_id');
                break;
        }

        $resultados = $query->orderBy('total_votos', 'desc')->get();

        // Obtener el total general para calcular porcentajes
        $totalVotos = $votacion->votos()->count();

        $resultadosConPorcentaje = $resultados->map(function ($resultado) use ($totalVotos) {
            return [
                'grupo_id' => $resultado->grupo_id,
                'departamento_id' => $resultado->departamento_id ?? null,
                'total_votos' => $resultado->total_votos,
                'porcentaje' => $totalVotos > 0 ? round(($resultado->total_votos / $totalVotos) * 100, 2) : 0,
            ];
        });

        return response()->json([
            'resultados' => $resultadosConPorcentaje,
            'agrupacion' => $agrupacion,
            'total_votos' => $totalVotos,
        ]);
    }

    /**
     * Obtener lista de tokens públicos para la pestaña 3.
     */
    public function tokens(Votacion $votacion, Request $request)
    {
        // Verificar que los resultados sean públicos y visibles
        if (!$votacion->resultadosVisibles()) {
            abort(404, 'Los resultados de esta votación no están disponibles públicamente.');
        }

        $busqueda = $request->get('busqueda');
        $perPage = $request->get('per_page', 20);

        $query = $votacion->votos()
            ->select(['id', 'token_unico', 'created_at'])
            ->orderBy('created_at', 'desc');

        if ($busqueda) {
            $query->where('token_unico', 'like', '%' . $busqueda . '%');
        }

        $tokens = $query->paginate($perPage);

        return response()->json([
            'tokens' => $tokens->items(),
            'pagination' => [
                'current_page' => $tokens->currentPage(),
                'last_page' => $tokens->lastPage(),
                'per_page' => $tokens->perPage(),
                'total' => $tokens->total(),
            ],
        ]);
    }
}
