<?php

namespace App\Repositories\Asamblea;

use App\Models\Asamblea\Asamblea;
use App\Models\Core\User;
use App\Services\Core\TenantService;
use App\Traits\HasAdvancedFilters;
use App\Traits\HasGeographicFilters;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class AsambleaRepository
{
    use HasAdvancedFilters, HasGeographicFilters;

    public function __construct(
        private TenantService $tenantService
    ) {}

    /**
     * Obtener participantes de una asamblea con filtros avanzados
     */
    public function getParticipantesPaginated(Asamblea $asamblea, Request $request, int $perPage = 20): LengthAwarePaginator
    {
        // Construir query manualmente con los joins necesarios
        $query = User::query()
            ->join('asamblea_usuario', 'users.id', '=', 'asamblea_usuario.usuario_id')
            ->leftJoin('users as updater', 'asamblea_usuario.updated_by', '=', 'updater.id')
            ->where('asamblea_usuario.asamblea_id', $asamblea->id);
        
        // Aplicar filtro de tenant si no es super admin
        if (!Auth::user()->hasRole('super_admin')) {
            $tenantId = $this->tenantService->getCurrentTenant()?->id;
            if ($tenantId) {
                $query->where('asamblea_usuario.tenant_id', $tenantId);
            }
        }
        
        $query->select('users.*', 
                'asamblea_usuario.tipo_participacion',
                'asamblea_usuario.asistio',
                'asamblea_usuario.hora_registro',
                'asamblea_usuario.updated_by',
                'updater.name as updated_by_name',
                'asamblea_usuario.tenant_id'
            );

        // Aplicar filtros
        $this->applyParticipantesFilters($query, $request);
        
        // Ordenamiento
        $query->orderBy('asamblea_usuario.tipo_participacion')
              ->orderBy('users.name');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Aplicar filtros específicos para participantes
     */
    private function applyParticipantesFilters($query, Request $request): void
    {
        // Definir campos permitidos para filtrar (con prefijos de tabla para evitar ambigüedad)
        $allowedFields = [
            'users.name', 'users.email', 'users.documento_identidad', 
            'asamblea_usuario.tipo_participacion', 'asamblea_usuario.asistio',
            'users.territorio_id', 'users.departamento_id', 
            'users.municipio_id', 'users.localidad_id',
        ];
        
        // Campos para búsqueda rápida (con prefijos de tabla)
        $quickSearchFields = ['users.name', 'users.email', 'users.documento_identidad'];

        // Aplicar filtros avanzados
        $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);
        
        // Aplicar filtros simples si existen
        if ($request->filled('tipo_participacion') && !$request->filled('advanced_filters')) {
            $query->where('asamblea_usuario.tipo_participacion', $request->tipo_participacion);
        }
        
        if ($request->has('asistio') && !$request->filled('advanced_filters')) {
            $query->where('asamblea_usuario.asistio', $request->asistio === 'true' || $request->asistio === '1');
        }
    }

    /**
     * Obtener configuración de campos para filtros avanzados de participantes
     */
    public function getParticipantesFilterFieldsConfig(): array
    {
        // Campos básicos del usuario (con prefijos de tabla para coincidencia con backend)
        $basicFields = [
            [
                'name' => 'users.name',
                'label' => 'Nombre',
                'type' => 'text',
            ],
            [
                'name' => 'users.email',
                'label' => 'Email',
                'type' => 'text',
            ],
            [
                'name' => 'users.documento_identidad',
                'label' => 'Documento de Identidad',
                'type' => 'text',
            ],
            [
                'name' => 'asamblea_usuario.tipo_participacion',
                'label' => 'Tipo de Participación',
                'type' => 'select',
                'options' => [
                    ['value' => 'asistente', 'label' => 'Asistente'],
                    ['value' => 'moderador', 'label' => 'Moderador'],
                    ['value' => 'secretario', 'label' => 'Secretario'],
                ],
            ],
            [
                'name' => 'asamblea_usuario.asistio',
                'label' => 'Asistencia',
                'type' => 'select',
                'options' => [
                    ['value' => 1, 'label' => 'Presente'],
                    ['value' => 0, 'label' => 'Ausente'],
                ],
            ],
        ];
        
        // Obtener campos geográficos en cascada para usuarios (con prefijos de tabla)
        $geographicFields = $this->getUserGeographicFilterFields();
        
        // Agregar prefijo 'users.' a los campos geográficos y actualizar referencias de cascada
        $geographicFields = array_map(function($field) {
            // Agregar prefijo al nombre del campo
            if (isset($field['name']) && !str_contains($field['name'], '.')) {
                $field['name'] = 'users.' . $field['name'];
            }
            
            // Actualizar cascadeFrom para que coincida con el nuevo nombre con prefijo
            if (isset($field['cascadeFrom']) && !str_contains($field['cascadeFrom'], '.')) {
                $field['cascadeFrom'] = 'users.' . $field['cascadeFrom'];
            }
            
            // Actualizar cascadeChildren para que coincidan con los nuevos nombres con prefijo
            if (isset($field['cascadeChildren']) && is_array($field['cascadeChildren'])) {
                $field['cascadeChildren'] = array_map(function($child) {
                    return !str_contains($child, '.') ? 'users.' . $child : $child;
                }, $field['cascadeChildren']);
            }
            
            return $field;
        }, $geographicFields);
        
        // Combinar todos los campos
        return array_merge($basicFields, $geographicFields);
    }

    /**
     * Obtener asambleas paginadas con filtros avanzados para el índice
     */
    public function getAllPaginated(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = Asamblea::with(['territorio', 'departamento', 'municipio', 'localidad'])
            ->withCount('participantes');

        // Definir campos permitidos para filtrar
        $allowedFields = [
            'nombre', 'descripcion', 'tipo', 'estado', 'lugar',
            'fecha_inicio', 'fecha_fin', 'territorio_id', 'departamento_id', 
            'municipio_id', 'localidad_id', 'activo', 'quorum_minimo',
            'zoom_enabled', 'zoom_meeting_id', 'zoom_meeting_type',
            'created_at', 'updated_at', 'participantes_count'
        ];
        
        // Campos para búsqueda rápida
        $quickSearchFields = ['nombre', 'descripcion', 'lugar'];

        // Aplicar filtros avanzados
        $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);
        
        // Mantener compatibilidad con filtros simples existentes
        $this->applySimpleFilters($query, $request);

        $asambleas = $query->ordenadoPorFecha()
            ->paginate($perPage)
            ->withQueryString();

        // Transformar los datos
        $asambleas->getCollection()->transform(function ($asamblea) {
            return $this->transformAsambleaForIndex($asamblea);
        });

        return $asambleas;
    }

    /**
     * Aplicar filtros simples para mantener compatibilidad
     */
    private function applySimpleFilters($query, Request $request): void
    {
        // Solo aplicar si no hay filtros avanzados
        if (!$request->filled('advanced_filters')) {
            // Filtro por tipo
            if ($request->filled('tipo')) {
                $query->where('tipo', $request->tipo);
            }

            // Filtro por estado
            if ($request->filled('estado')) {
                switch ($request->estado) {
                    case 'programada':
                        $query->programadas();
                        break;
                    case 'en_curso':
                        $query->enCurso();
                        break;
                    case 'finalizada':
                        $query->finalizadas();
                        break;
                    case 'cancelada':
                        $query->canceladas();
                        break;
                }
            }

            // Filtro por estado activo
            if ($request->filled('activo')) {
                $query->where('activo', $request->activo === '1');
            }
        }
    }

    /**
     * Transformar asamblea para la vista index
     */
    private function transformAsambleaForIndex($asamblea): array
    {
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
            'territorio' => $asamblea->territorio,
            'departamento' => $asamblea->departamento,
            'municipio' => $asamblea->municipio,
            'localidad' => $asamblea->localidad,
            'ubicacion_completa' => $asamblea->getUbicacionCompleta(),
            'quorum_minimo' => $asamblea->quorum_minimo,
            'activo' => $asamblea->activo,
            'acta_url' => $asamblea->acta_url,
            'created_at' => $asamblea->created_at,
            'estado_temporal' => $asamblea->getEstadoTemporal(),
            'duracion' => $asamblea->getDuracion(),
            'tiempo_restante' => $asamblea->getTiempoRestante(),
            'rango_fechas' => $asamblea->getRangoFechas(),
            'participantes_count' => $asamblea->participantes_count,
        ];
    }

    /**
     * Obtener configuración de campos para filtros avanzados del índice
     */
    public function getIndexFilterFieldsConfig(): array
    {
        // Campos básicos
        $basicFields = [
            [
                'name' => 'nombre',
                'label' => 'Nombre',
                'type' => 'text',
            ],
            [
                'name' => 'descripcion', 
                'label' => 'Descripción',
                'type' => 'text',
            ],
            [
                'name' => 'tipo',
                'label' => 'Tipo',
                'type' => 'select',
                'options' => [
                    ['value' => 'ordinaria', 'label' => 'Ordinaria'],
                    ['value' => 'extraordinaria', 'label' => 'Extraordinaria'],
                ],
            ],
            [
                'name' => 'estado',
                'label' => 'Estado',
                'type' => 'select',
                'options' => [
                    ['value' => 'programada', 'label' => 'Programada'],
                    ['value' => 'en_curso', 'label' => 'En Curso'],
                    ['value' => 'finalizada', 'label' => 'Finalizada'],
                    ['value' => 'cancelada', 'label' => 'Cancelada'],
                ],
            ],
            [
                'name' => 'lugar',
                'label' => 'Lugar',
                'type' => 'text',
            ],
            [
                'name' => 'activo',
                'label' => 'Activo',
                'type' => 'select',
                'options' => [
                    ['value' => 1, 'label' => 'Activo'],
                    ['value' => 0, 'label' => 'Inactivo'],
                ],
            ],
            [
                'name' => 'zoom_enabled',
                'label' => 'Zoom Habilitado',
                'type' => 'select',
                'options' => [
                    ['value' => 1, 'label' => 'Sí'],
                    ['value' => 0, 'label' => 'No'],
                ],
            ],
            [
                'name' => 'fecha_inicio',
                'label' => 'Fecha de Inicio',
                'type' => 'date',
            ],
            [
                'name' => 'fecha_fin',
                'label' => 'Fecha de Fin',
                'type' => 'date',
            ],
        ];

        // Obtener campos geográficos
        $geographicFields = $this->getGeographicFilterFields();

        // Combinar todos los campos
        return array_merge($basicFields, $geographicFields);
    }

    /**
     * Obtener votaciones de una asamblea con datos enriquecidos
     */
    public function getVotacionesWithSyncData(Asamblea $asamblea): array
    {
        $votaciones = $asamblea->votaciones()
            ->with('categoria')
            ->withCount('votantes')
            ->get();

        return $votaciones->map(function ($votacion) use ($asamblea) {
            return [
                'id' => $votacion->id,
                'titulo' => $votacion->titulo,
                'descripcion' => $votacion->descripcion,
                'estado' => $votacion->estado,
                'fecha_inicio' => $votacion->fecha_inicio,
                'fecha_fin' => $votacion->fecha_fin,
                'categoria' => $votacion->categoria,
                'votantes_count' => $votacion->votantes_count,
                'sincronizados_count' => $this->countSincronizados($asamblea, $votacion),
                'puede_sincronizar' => $votacion->estado === 'activa'
            ];
        })->toArray();
    }

    /**
     * Contar participantes sincronizados desde una asamblea a una votación
     */
    private function countSincronizados(Asamblea $asamblea, $votacion): int
    {
        return \DB::table('votacion_usuario')
            ->where('votacion_id', $votacion->id)
            ->where('origen_id', $asamblea->id)
            ->where('model_type', 'App\Models\Asamblea\Asamblea')
            ->count();
    }
}