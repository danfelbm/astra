<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Convocatoria;
use App\Models\Postulacion;
use App\Traits\HasAdvancedFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PostulacionController extends Controller
{
    use HasAdvancedFilters;
    /**
     * Lista de postulaciones para revisión administrativa
     */
    public function index(Request $request)
    {
        $query = Postulacion::with(['user', 'convocatoria.cargo', 'convocatoria.periodoElectoral', 'revisadoPor'])
            ->ordenadosPorFecha();

        // Definir campos permitidos para filtrar
        $allowedFields = [
            'convocatoria_id', 'user_id', 'estado', 'revisado_por',
            'created_at', 'updated_at', 'revisado_at'
        ];
        
        // Campos para búsqueda rápida
        $quickSearchFields = ['user.name', 'user.email', 'convocatoria.nombre'];

        // Aplicar filtros avanzados
        $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);
        
        // Mantener compatibilidad con filtros simples existentes
        $this->applySimpleFilters($query, $request);

        $postulaciones = $query->paginate(20)->withQueryString();

        // Formatear datos para el frontend
        $postulaciones->through(function ($postulacion) {
            // Validación defensiva - no incluir postulaciones corruptas
            if (!$postulacion || !$postulacion->id) {
                return null;
            }
            
            return [
                'id' => $postulacion->id,
                'usuario' => $postulacion->user ? [
                    'id' => $postulacion->user->id,
                    'name' => $postulacion->user->name,
                    'email' => $postulacion->user->email,
                ] : [
                    'id' => null,
                    'name' => 'Usuario eliminado',
                    'email' => 'N/A',
                ],
                'convocatoria' => $postulacion->convocatoria ? [
                    'id' => $postulacion->convocatoria->id,
                    'nombre' => $postulacion->convocatoria->nombre,
                    'cargo' => $postulacion->convocatoria->cargo?->nombre ?? 'N/A',
                    'periodo' => $postulacion->convocatoria->periodoElectoral?->nombre ?? 'N/A',
                ] : [
                    'id' => null,
                    'nombre' => 'Convocatoria eliminada',
                    'cargo' => 'N/A',
                    'periodo' => 'N/A',
                ],
                'estado' => $postulacion->estado,
                'estado_label' => $postulacion->estado_label,
                'estado_color' => $postulacion->estado_color,
                'fecha_postulacion' => $postulacion->getFechaPostulacionFormateada(),
                'tiene_candidatura_vinculada' => $postulacion->tieneCandidaturaVinculada(),
                'comentarios_admin' => $postulacion->comentarios_admin,
                'revisado_por' => $postulacion->revisadoPor ? [
                    'name' => $postulacion->revisadoPor->name,
                    'email' => $postulacion->revisadoPor->email,
                ] : null,
                'fecha_revision' => $postulacion->getFechaRevisionFormateada(),
                'created_at' => $postulacion->created_at?->format('d/m/Y H:i') ?? 'N/A',
            ];
        });

        // Filtrar elementos null de postulaciones corruptas
        $postulaciones->setCollection(
            $postulaciones->getCollection()->filter(function ($item) {
                return $item !== null;
            })
        );

        // Obtener convocatorias para filtro
        $convocatorias = Convocatoria::with('cargo')
            ->orderBy('nombre')
            ->get()
            ->map(function ($convocatoria) {
                return [
                    'id' => $convocatoria->id,
                    'nombre' => $convocatoria->nombre,
                    'cargo' => $convocatoria->cargo->nombre,
                ];
            });

        return Inertia::render('Admin/Postulaciones/Index', [
            'postulaciones' => $postulaciones,
            'convocatorias' => $convocatorias,
            'filters' => $request->only(['convocatoria_id', 'estado', 'search', 'tiene_candidatura', 'advanced_filters']),
            'filterFieldsConfig' => $this->getFilterFieldsConfig(),
        ]);
    }
    
    /**
     * Aplicar filtros simples para mantener compatibilidad
     */
    protected function applySimpleFilters($query, $request)
    {
        // Solo aplicar si no hay filtros avanzados
        if (!$request->filled('advanced_filters')) {
            if ($request->filled('convocatoria_id')) {
                $query->where('convocatoria_id', $request->convocatoria_id);
            }

            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }
            
            if ($request->filled('tiene_candidatura')) {
                if ($request->boolean('tiene_candidatura')) {
                    $query->whereNotNull('candidatura_snapshot');
                } else {
                    $query->whereNull('candidatura_snapshot');
                }
            }
        }
    }
    
    /**
     * Obtener configuración de campos para filtros avanzados
     */
    public function getFilterFieldsConfig(): array
    {
        // Cargar convocatorias para el select
        $convocatorias = Convocatoria::with('cargo')->get()->map(fn($c) => [
            'value' => $c->id,
            'label' => $c->nombre . ' - ' . ($c->cargo ? $c->cargo->nombre : 'Sin cargo')
        ]);
        
        // Cargar usuarios revisores
        $revisores = \App\Models\User::whereHas('roles', function($query) {
            $query->whereIn('name', ['admin', 'super_admin']);
        })->get()->map(fn($u) => [
            'value' => $u->id,
            'label' => $u->name
        ]);
        
        return [
            [
                'name' => 'convocatoria_id',
                'label' => 'Convocatoria',
                'type' => 'select',
                'options' => $convocatorias->toArray(),
            ],
            [
                'name' => 'user.name',
                'label' => 'Nombre del Postulante',
                'type' => 'text',
            ],
            [
                'name' => 'user.email',
                'label' => 'Email del Postulante',
                'type' => 'text',
            ],
            [
                'name' => 'estado',
                'label' => 'Estado',
                'type' => 'select',
                'options' => [
                    ['value' => 'borrador', 'label' => 'Borrador'],
                    ['value' => 'enviada', 'label' => 'Enviada'],
                    ['value' => 'en_revision', 'label' => 'En Revisión'],
                    ['value' => 'aceptada', 'label' => 'Aceptada'],
                    ['value' => 'rechazada', 'label' => 'Rechazada'],
                ],
            ],
            [
                'name' => 'revisado_por',
                'label' => 'Revisado Por',
                'type' => 'select',
                'options' => $revisores->toArray(),
            ],
            [
                'name' => 'candidatura_snapshot',
                'label' => 'Tiene Candidatura Vinculada',
                'type' => 'select',
                'options' => [
                    ['value' => 'not_null', 'label' => 'Sí'],
                    ['value' => 'null', 'label' => 'No'],
                ],
                'operators' => ['is_empty', 'is_not_empty'],
            ],
            [
                'name' => 'created_at',
                'label' => 'Fecha de Postulación',
                'type' => 'datetime',
            ],
            [
                'name' => 'revisado_at',
                'label' => 'Fecha de Revisión',
                'type' => 'datetime',
            ],
        ];
    }

    /**
     * Ver detalles completos de una postulación
     */
    public function show(Postulacion $postulacion)
    {
        // Validación adicional para datos corruptos
        if (!$postulacion || !$postulacion->id) {
            return redirect()->route('admin.postulaciones.index')
                ->with('error', 'La postulación solicitada no existe o está corrupta.');
        }
        
        
        $postulacion->load(['user', 'convocatoria.cargo', 'convocatoria.periodoElectoral', 'revisadoPor', 'historial.cambiadoPor']);

        return Inertia::render('Admin/Postulaciones/Show', [
            'postulacion' => [
                'id' => $postulacion->id,
                'usuario' => $postulacion->user ? [
                    'id' => $postulacion->user->id,
                    'name' => $postulacion->user->name,
                    'email' => $postulacion->user->email,
                ] : [
                    'id' => null,
                    'name' => 'Usuario eliminado',
                    'email' => 'N/A',
                ],
                'convocatoria' => $postulacion->convocatoria ? [
                    'id' => $postulacion->convocatoria->id,
                    'nombre' => $postulacion->convocatoria->nombre,
                    'descripcion' => $postulacion->convocatoria->descripcion,
                    'cargo' => $postulacion->convocatoria->cargo?->nombre ?? 'N/A',
                    'periodo' => $postulacion->convocatoria->periodoElectoral?->nombre ?? 'N/A',
                    'formulario_postulacion' => $postulacion->convocatoria->formulario_postulacion ?? [],
                    'rango_fechas' => $postulacion->convocatoria->getRangoFechas(),
                    'ubicacion' => $postulacion->convocatoria->getUbicacionTexto(),
                ] : [
                    'id' => null,
                    'nombre' => 'Convocatoria eliminada',
                    'descripcion' => 'Esta convocatoria ha sido eliminada',
                    'cargo' => 'N/A',
                    'periodo' => 'N/A',
                    'formulario_postulacion' => [],
                    'rango_fechas' => 'N/A',
                    'ubicacion' => 'N/A',
                ],
                'formulario_data' => $postulacion->formulario_data,
                'candidatura_snapshot' => $postulacion->candidatura_snapshot,
                'tiene_candidatura_vinculada' => $postulacion->tieneCandidaturaVinculada(),
                'estado' => $postulacion->estado,
                'estado_label' => $postulacion->estado_label,
                'estado_color' => $postulacion->estado_color,
                'fecha_postulacion' => $postulacion->getFechaPostulacionFormateada(),
                'comentarios_admin' => $postulacion->comentarios_admin,
                'revisado_por' => $postulacion->revisadoPor ? [
                    'name' => $postulacion->revisadoPor->name,
                    'email' => $postulacion->revisadoPor->email,
                ] : null,
                'fecha_revision' => $postulacion->getFechaRevisionFormateada(),
                'created_at' => $postulacion->created_at?->format('d/m/Y H:i') ?? 'N/A',
                'updated_at' => $postulacion->updated_at?->format('d/m/Y H:i') ?? 'N/A',
                // Historial de cambios - Ordenado por fecha más reciente primero
                'historial' => $postulacion->historial->sortByDesc('fecha_cambio')->values()->map(function ($registro) {
                    return [
                        'id' => $registro->id,
                        'estado_anterior' => $registro->estado_anterior,
                        'estado_nuevo' => $registro->estado_nuevo,
                        'estado_anterior_label' => $registro->estado_anterior_label,
                        'estado_nuevo_label' => $registro->estado_nuevo_label,
                        'estado_anterior_color' => $registro->estado_anterior_color,
                        'estado_nuevo_color' => $registro->estado_nuevo_color,
                        'resumen_cambio' => $registro->resumen_cambio,
                        'comentarios' => $registro->comentarios,
                        'motivo_cambio' => $registro->motivo_cambio,
                        'cambiado_por' => $registro->cambiadoPor ? [
                            'id' => $registro->cambiadoPor->id,
                            'name' => $registro->cambiadoPor->name,
                            'email' => $registro->cambiadoPor->email,
                        ] : [
                            'id' => null,
                            'name' => 'Usuario eliminado',
                            'email' => 'N/A',
                        ],
                        'fecha_cambio' => $registro->fecha_formateada,
                        'tiempo_pasado' => $registro->tiempo_pasado,
                        'tipo_cambio_icon' => $registro->tipo_cambio_icon,
                        'tipo_cambio' => $registro->tipo_cambio,
                        'metadatos' => $registro->metadatos,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Cambiar estado de una postulación
     */
    public function cambiarEstado(Request $request, Postulacion $postulacion)
    {
        $request->validate([
            'estado' => 'required|string|in:enviada,en_revision,aceptada,rechazada,borrador',
            'comentarios' => 'nullable|string|max:1000',
        ]);

        $admin = Auth::user();
        $nuevoEstado = $request->estado;
        $comentarios = $request->comentarios;

        $resultado = match($nuevoEstado) {
            'enviada' => $postulacion->enviar($admin),
            'en_revision' => $postulacion->marcarEnRevision($admin),
            'aceptada' => $postulacion->aceptar($admin, $comentarios),
            'rechazada' => $postulacion->rechazar($admin, $comentarios),
            'borrador' => $postulacion->volverABorrador($admin, $comentarios),
            default => false,
        };

        if (!$resultado) {
            throw ValidationException::withMessages([
                'estado' => 'No se puede cambiar al estado solicitado.'
            ]);
        }

        $mensajes = [
            'enviada' => 'Postulación marcada como enviada.',
            'en_revision' => 'Postulación marcada como en revisión.',
            'aceptada' => 'Postulación aceptada correctamente.',
            'rechazada' => 'Postulación rechazada. El usuario puede editarla y reenviarla.',
            'borrador' => 'Postulación devuelta a borrador.',
        ];

        return back()->with('success', $mensajes[$nuevoEstado]);
    }

    /**
     * Obtener estadísticas de postulaciones
     */
    public function estadisticas()
    {
        $stats = [
            'total' => Postulacion::count(),
            'borradores' => Postulacion::borradores()->count(),
            'enviadas' => Postulacion::enviadas()->count(),
            'en_revision' => Postulacion::enRevision()->count(),
            'aceptadas' => Postulacion::aceptadas()->count(),
            'rechazadas' => Postulacion::rechazadas()->count(),
        ];

        // Estadísticas por convocatoria
        $porConvocatoria = Postulacion::with('convocatoria')
            ->selectRaw('convocatoria_id, COUNT(*) as total')
            ->groupBy('convocatoria_id')
            ->get()
            ->map(function ($item) {
                return [
                    'convocatoria' => $item->convocatoria->nombre,
                    'total' => $item->total,
                ];
            });

        // Postulaciones con candidatura vinculada
        $conCandidatura = Postulacion::whereNotNull('candidatura_snapshot')->count();
        $sinCandidatura = Postulacion::whereNull('candidatura_snapshot')->count();

        return response()->json([
            'generales' => $stats,
            'por_convocatoria' => $porConvocatoria,
            'candidaturas_vinculadas' => [
                'con_candidatura' => $conCandidatura,
                'sin_candidatura' => $sinCandidatura,
            ],
        ]);
    }

    /**
     * Obtener postulaciones por estado específico
     */
    public function porEstado(string $estado)
    {
        $postulaciones = Postulacion::with(['user', 'convocatoria.cargo'])
            ->porEstado($estado)
            ->ordenadosPorFecha()
            ->get()
            ->map(function ($postulacion) {
                return [
                    'id' => $postulacion->id,
                    'usuario' => $postulacion->user->name,
                    'convocatoria' => $postulacion->convocatoria->nombre,
                    'cargo' => $postulacion->convocatoria->cargo->nombre,
                    'fecha_postulacion' => $postulacion->getFechaPostulacionFormateada(),
                    'tiene_candidatura' => $postulacion->tieneCandidaturaVinculada(),
                ];
            });

        return response()->json($postulaciones);
    }

    /**
     * Exportar datos de postulaciones para reportes
     */
    public function exportar(Request $request)
    {
        $query = Postulacion::with(['user', 'convocatoria.cargo', 'convocatoria.periodoElectoral']);

        // Aplicar mismos filtros que en index
        if ($request->filled('convocatoria_id')) {
            $query->where('convocatoria_id', $request->convocatoria_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $postulaciones = $query->get()->map(function ($postulacion) {
            $datos = [
                'ID' => $postulacion->id,
                'Usuario' => $postulacion->user->name,
                'Email' => $postulacion->user->email,
                'Convocatoria' => $postulacion->convocatoria->nombre,
                'Cargo' => $postulacion->convocatoria->cargo->nombre,
                'Periodo' => $postulacion->convocatoria->periodoElectoral->nombre,
                'Estado' => $postulacion->estado_label,
                'Fecha Postulación' => $postulacion->getFechaPostulacionFormateada(),
                'Tiene Candidatura' => $postulacion->tieneCandidaturaVinculada() ? 'Sí' : 'No',
                'Comentarios Admin' => $postulacion->comentarios_admin,
                'Fecha Creación' => $postulacion->created_at->format('d/m/Y H:i'),
            ];

            // Agregar datos del formulario dinámico
            if ($postulacion->formulario_data) {
                foreach ($postulacion->formulario_data as $campo => $valor) {
                    $datos["Campo: {$campo}"] = is_array($valor) ? implode(', ', $valor) : $valor;
                }
            }

            return $datos;
        });

        return response()->json($postulaciones);
    }

    /**
     * Vista de reportes y análisis
     */
    public function reportes()
    {
        // Estadísticas generales
        $estadisticas = [
            'total' => Postulacion::count(),
            'por_estado' => [
                'borradores' => Postulacion::borradores()->count(),
                'enviadas' => Postulacion::enviadas()->count(),
                'en_revision' => Postulacion::enRevision()->count(),
                'aceptadas' => Postulacion::aceptadas()->count(),
                'rechazadas' => Postulacion::rechazadas()->count(),
            ],
            'con_candidatura' => Postulacion::whereNotNull('candidatura_snapshot')->count(),
        ];

        // Top convocatorias por número de postulaciones
        $topConvocatorias = Convocatoria::withCount('postulaciones')
            ->orderBy('postulaciones_count', 'desc')
            ->take(10)
            ->get()
            ->map(function ($convocatoria) {
                return [
                    'nombre' => $convocatoria->nombre,
                    'total_postulaciones' => $convocatoria->postulaciones_count,
                ];
            });

        return Inertia::render('Admin/Postulaciones/Reportes', [
            'estadisticas' => $estadisticas,
            'top_convocatorias' => $topConvocatorias,
        ]);
    }
}
