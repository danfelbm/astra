<?php

namespace App\Http\Controllers\Votaciones\User;

use App\Http\Controllers\Core\UserController;


use App\Models\Votaciones\Votacion;
use App\Models\Votaciones\Voto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ResultadosController extends UserController
{
    /**
     * Mostrar la página principal de resultados con las tres pestañas.
     */
    public function show(Votacion $votacion): Response
    {
        // Verificar permisos para ver resultados
        abort_unless(auth()->user()->can('votaciones.view_results'), 403, 'No tienes permisos para ver resultados de votaciones');
        
        // Verificar que los resultados sean públicos y visibles
        if (!$votacion->resultadosVisibles()) {
            abort(404, 'Los resultados de esta votación no están disponibles públicamente.');
        }

        $votacion->load('categoria');

        return Inertia::render('User/Votaciones/Resultados', [
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
                'es_admin' => auth()->user()?->hasAdministrativeAccess() ?? false,
            ],
        ]);
    }

    /**
     * Obtener datos consolidados por pregunta para la pestaña 1.
     */
    public function consolidado(Votacion $votacion)
    {
        // Verificar permisos para ver resultados
        abort_unless(auth()->user()->can('votaciones.view_results'), 403, 'No tienes permisos para ver resultados de votaciones');
        
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

            if (in_array($pregunta['type'], ['select', 'radio', 'convocatoria', 'perfil_candidatura'])) {
                // Para preguntas de opción única (incluye convocatoria y perfil_candidatura)
                $respuestas = DB::table('votos')
                    ->where('votacion_id', $votacion->id)
                    ->select(DB::raw("JSON_EXTRACT(respuestas, '$.{$preguntaId}') as respuesta"))
                    ->get();

                // Procesar respuestas considerando valores null (voto en blanco) y strings
                $respuestasProcesadas = $respuestas->map(function($item) {
                    // JSON_EXTRACT devuelve null para valores null, y string con comillas para strings
                    if ($item->respuesta === null || $item->respuesta === 'null') {
                        return 'Voto en blanco';
                    }
                    // Remover comillas del JSON si es un string
                    return trim($item->respuesta, '"');
                })->filter(); // Filtrar valores vacíos

                $conteos = $respuestasProcesadas->countBy();
                
                // Si es tipo convocatoria o perfil_candidatura, no tenemos opciones predefinidas
                if (in_array($pregunta['type'], ['convocatoria', 'perfil_candidatura'])) {
                    // Crear opciones desde las respuestas obtenidas
                    foreach ($conteos as $opcion => $cantidad) {
                        $porcentaje = $totalVotos > 0 ? round(($cantidad / $totalVotos) * 100, 2) : 0;
                        
                        $preguntaData['respuestas'][] = [
                            'opcion' => $opcion,
                            'cantidad' => $cantidad,
                            'porcentaje' => $porcentaje,
                        ];
                    }
                } else {
                    // Para select/radio usar opciones predefinidas
                    foreach ($pregunta['options'] as $opcion) {
                        $cantidad = $conteos[$opcion] ?? 0;
                        $porcentaje = $totalVotos > 0 ? round(($cantidad / $totalVotos) * 100, 2) : 0;
                        
                        $preguntaData['respuestas'][] = [
                            'opcion' => $opcion,
                            'cantidad' => $cantidad,
                            'porcentaje' => $porcentaje,
                        ];
                    }
                }
                
                $preguntaData['total_respuestas'] = $respuestasProcesadas->count();
                
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
        // Verificar permisos para ver resultados
        abort_unless(auth()->user()->can('votaciones.view_results'), 403, 'No tienes permisos para ver resultados de votaciones');
        
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
                // JOIN con la tabla departamentos para obtener el nombre
                $query->leftJoin('departamentos', 'users.departamento_id', '=', 'departamentos.id')
                      ->select('users.departamento_id as grupo_id',
                              'departamentos.nombre as departamento_nombre',
                              DB::raw('COUNT(*) as total_votos'))
                      ->groupBy('users.departamento_id', 'departamentos.nombre');
                break;
            case 'municipio':
                // JOIN con las tablas municipios y departamentos para obtener los nombres
                $query->leftJoin('municipios', 'users.municipio_id', '=', 'municipios.id')
                      ->leftJoin('departamentos', 'users.departamento_id', '=', 'departamentos.id')
                      ->select('users.municipio_id as grupo_id',
                              'municipios.nombre as municipio_nombre',
                              'users.departamento_id',
                              'departamentos.nombre as departamento_nombre',
                              DB::raw('COUNT(*) as total_votos'))
                      ->groupBy('users.municipio_id', 'municipios.nombre', 'users.departamento_id', 'departamentos.nombre');
                break;
            default: // territorio
                // JOIN con la tabla territorios para obtener el nombre
                $query->leftJoin('territorios', 'users.territorio_id', '=', 'territorios.id')
                      ->select('users.territorio_id as grupo_id',
                              'territorios.nombre as territorio_nombre',
                              DB::raw('COUNT(*) as total_votos'))
                      ->groupBy('users.territorio_id', 'territorios.nombre');
                break;
        }

        $resultados = $query->orderBy('total_votos', 'desc')->get();

        // Obtener el total general para calcular porcentajes
        $totalVotos = $votacion->votos()->count();

        $resultadosConPorcentaje = $resultados->map(function ($resultado) use ($totalVotos, $agrupacion) {
            $data = [
                'grupo_id' => $resultado->grupo_id,
                'total_votos' => $resultado->total_votos,
                'porcentaje' => $totalVotos > 0 ? round(($resultado->total_votos / $totalVotos) * 100, 2) : 0,
            ];

            // Agregar nombres según la agrupación
            switch ($agrupacion) {
                case 'departamento':
                    $data['departamento_nombre'] = $resultado->departamento_nombre;
                    break;
                case 'municipio':
                    $data['municipio_nombre'] = $resultado->municipio_nombre;
                    $data['departamento_id'] = $resultado->departamento_id;
                    $data['departamento_nombre'] = $resultado->departamento_nombre;
                    break;
                default: // territorio
                    $data['territorio_nombre'] = $resultado->territorio_nombre;
                    break;
            }

            return $data;
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
        // Verificar permisos para ver resultados
        abort_unless(auth()->user()->can('votaciones.view_results'), 403, 'No tienes permisos para ver resultados de votaciones');
        
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

    /**
     * Obtener ranking de opciones votadas desde un territorio específico.
     * Usado en la tab "Por Territorio" cuando se expande una fila.
     */
    public function rankingPorTerritorio(Votacion $votacion, Request $request)
    {
        // Verificar permisos para ver resultados
        abort_unless(auth()->user()->can('votaciones.view_results'), 403, 'No tienes permisos para ver resultados de votaciones');
        
        // Verificar que los resultados sean públicos y visibles
        if (!$votacion->resultadosVisibles()) {
            abort(404, 'Los resultados de esta votación no están disponibles públicamente.');
        }

        $agrupacion = $request->get('agrupacion', 'territorio'); // territorio, departamento, municipio
        $grupoId = $request->get('grupo_id'); // ID del territorio/departamento/municipio específico
        $preguntaId = $request->get('pregunta_id'); // ID de la pregunta a analizar (opcional)

        // Construir query base para obtener votos del territorio específico
        $query = DB::table('votos')
            ->join('users', 'votos.usuario_id', '=', 'users.id')
            ->where('votos.votacion_id', $votacion->id);

        // Filtrar por el grupo geográfico específico
        switch ($agrupacion) {
            case 'departamento':
                $query->where('users.departamento_id', $grupoId);
                break;
            case 'municipio':
                $query->where('users.municipio_id', $grupoId);
                break;
            default: // territorio
                $query->where('users.territorio_id', $grupoId);
                break;
        }

        $votos = $query->select('votos.respuestas')->get();
        $totalVotosGrupo = $votos->count();

        // Si no hay votos, retornar vacío
        if ($totalVotosGrupo === 0) {
            return response()->json([
                'preguntas' => [],
                'total_votos_grupo' => 0,
                'agrupacion' => $agrupacion,
                'grupo_id' => $grupoId,
            ]);
        }

        // Procesar resultados por pregunta
        $formularioConfig = $votacion->formulario_config;
        $resultadosPorPregunta = [];

        foreach ($formularioConfig as $pregunta) {
            // Si se especificó una pregunta específica, solo procesar esa
            if ($preguntaId && $pregunta['id'] !== $preguntaId) {
                continue;
            }

            $preguntaData = [
                'id' => $pregunta['id'],
                'titulo' => $pregunta['title'],
                'tipo' => $pregunta['type'],
                'respuestas' => [],
            ];

            // Procesar según el tipo de pregunta
            if (in_array($pregunta['type'], ['select', 'radio', 'convocatoria', 'perfil_candidatura'])) {
                // Contar respuestas para esta pregunta
                $conteos = [];
                foreach ($votos as $voto) {
                    $respuestas = json_decode($voto->respuestas, true);
                    $respuesta = $respuestas[$pregunta['id']] ?? null;
                    
                    if ($respuesta === null) {
                        $respuesta = 'Voto en blanco';
                    }
                    
                    if (!isset($conteos[$respuesta])) {
                        $conteos[$respuesta] = 0;
                    }
                    $conteos[$respuesta]++;
                }

                // Ordenar por cantidad de votos
                arsort($conteos);

                // Formatear respuestas
                foreach ($conteos as $opcion => $cantidad) {
                    $porcentaje = round(($cantidad / $totalVotosGrupo) * 100, 2);
                    $preguntaData['respuestas'][] = [
                        'opcion' => $opcion,
                        'cantidad' => $cantidad,
                        'porcentaje' => $porcentaje,
                    ];
                }
            } elseif ($pregunta['type'] === 'checkbox') {
                // Para opciones múltiples
                $todasLasOpciones = [];
                foreach ($votos as $voto) {
                    $respuestas = json_decode($voto->respuestas, true);
                    $opciones = $respuestas[$pregunta['id']] ?? [];
                    if (is_array($opciones)) {
                        $todasLasOpciones = array_merge($todasLasOpciones, $opciones);
                    }
                }
                
                $conteos = array_count_values($todasLasOpciones);
                arsort($conteos);
                
                foreach ($conteos as $opcion => $cantidad) {
                    $porcentaje = round(($cantidad / $totalVotosGrupo) * 100, 2);
                    $preguntaData['respuestas'][] = [
                        'opcion' => $opcion,
                        'cantidad' => $cantidad,
                        'porcentaje' => $porcentaje,
                    ];
                }
            }

            // Solo agregar si tiene respuestas
            if (!empty($preguntaData['respuestas'])) {
                $resultadosPorPregunta[] = $preguntaData;
            }
        }

        return response()->json([
            'preguntas' => $resultadosPorPregunta,
            'total_votos_grupo' => $totalVotosGrupo,
            'agrupacion' => $agrupacion,
            'grupo_id' => $grupoId,
        ]);
    }

    /**
     * Obtener distribución geográfica de votos para una opción específica.
     * Usado en la tab "Consolidado" cuando se expande una opción.
     */
    public function distribucionGeograficaPorOpcion(Votacion $votacion, Request $request)
    {
        // Verificar permisos para ver resultados
        abort_unless(auth()->user()->can('votaciones.view_results'), 403, 'No tienes permisos para ver resultados de votaciones');
        
        // Verificar que los resultados sean públicos y visibles
        if (!$votacion->resultadosVisibles()) {
            abort(404, 'Los resultados de esta votación no están disponibles públicamente.');
        }

        $preguntaId = $request->get('pregunta_id');
        $opcion = $request->get('opcion');
        $agrupacion = $request->get('agrupacion', 'territorio'); // territorio, departamento, municipio

        // Obtener todos los votos con esa opción seleccionada
        $query = DB::table('votos')
            ->join('users', 'votos.usuario_id', '=', 'users.id')
            ->where('votos.votacion_id', $votacion->id);

        // Filtrar por respuesta específica
        // Para opciones simples (select, radio)
        $query->where(function($q) use ($preguntaId, $opcion) {
            // Buscar donde la respuesta es exactamente la opción
            $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(respuestas, '$.{$preguntaId}')) = ?", [$opcion])
              // O para checkbox donde la opción está en el array
              ->orWhereRaw("JSON_CONTAINS(JSON_EXTRACT(respuestas, '$.{$preguntaId}'), ?, '$')", [json_encode($opcion)]);
        });

        // Agrupar por ubicación geográfica
        switch ($agrupacion) {
            case 'departamento':
                $query->leftJoin('departamentos', 'users.departamento_id', '=', 'departamentos.id')
                      ->select(
                          'users.departamento_id as grupo_id',
                          'departamentos.nombre as nombre_grupo',
                          DB::raw('COUNT(*) as total_votos')
                      )
                      ->groupBy('users.departamento_id', 'departamentos.nombre');
                break;
            case 'municipio':
                $query->leftJoin('municipios', 'users.municipio_id', '=', 'municipios.id')
                      ->leftJoin('departamentos', 'users.departamento_id', '=', 'departamentos.id')
                      ->select(
                          'users.municipio_id as grupo_id',
                          'municipios.nombre as nombre_grupo',
                          'departamentos.nombre as departamento_nombre',
                          DB::raw('COUNT(*) as total_votos')
                      )
                      ->groupBy('users.municipio_id', 'municipios.nombre', 'departamentos.nombre');
                break;
            default: // territorio
                $query->leftJoin('territorios', 'users.territorio_id', '=', 'territorios.id')
                      ->select(
                          'users.territorio_id as grupo_id',
                          'territorios.nombre as nombre_grupo',
                          DB::raw('COUNT(*) as total_votos')
                      )
                      ->groupBy('users.territorio_id', 'territorios.nombre');
                break;
        }

        $resultados = $query->orderBy('total_votos', 'desc')
                           ->limit(20) // Top 20 ubicaciones
                           ->get();

        // Obtener total de votos para esta opción
        $totalVotosOpcion = DB::table('votos')
            ->where('votacion_id', $votacion->id)
            ->where(function($q) use ($preguntaId, $opcion) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(respuestas, '$.{$preguntaId}')) = ?", [$opcion])
                  ->orWhereRaw("JSON_CONTAINS(JSON_EXTRACT(respuestas, '$.{$preguntaId}'), ?, '$')", [json_encode($opcion)]);
            })
            ->count();

        // Calcular porcentajes
        $resultadosConPorcentaje = $resultados->map(function ($resultado) use ($totalVotosOpcion) {
            $data = [
                'grupo_id' => $resultado->grupo_id,
                'nombre_grupo' => $resultado->nombre_grupo ?: 'Sin especificar',
                'total_votos' => $resultado->total_votos,
                'porcentaje' => $totalVotosOpcion > 0 ? round(($resultado->total_votos / $totalVotosOpcion) * 100, 2) : 0,
            ];
            
            // Agregar departamento para municipios
            if (isset($resultado->departamento_nombre)) {
                $data['departamento_nombre'] = $resultado->departamento_nombre;
            }
            
            return $data;
        });

        return response()->json([
            'distribucion' => $resultadosConPorcentaje,
            'total_votos_opcion' => $totalVotosOpcion,
            'pregunta_id' => $preguntaId,
            'opcion' => $opcion,
            'agrupacion' => $agrupacion,
        ]);
    }
}
