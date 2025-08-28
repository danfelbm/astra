<?php

namespace App\Http\Controllers\Elecciones\Guest;

use App\Http\Controllers\Core\GuestController;


use App\Models\Elecciones\Postulacion;
use App\Models\Elecciones\Convocatoria;
use App\Models\Elecciones\Cargo;
use App\Models\Elecciones\PeriodoElectoral;
use App\Traits\HasAdvancedFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostulacionPublicController extends GuestController
{
    use HasAdvancedFilters;

    /**
     * Mostrar página pública de postulaciones aceptadas
     */
    public function index(Request $request): Response
    {
        // Obtener configuración de campos para filtros
        $filterFieldsConfig = $this->getFilterFieldsConfig();

        return Inertia::render('Guest/Postulaciones/PostulacionesAceptadas', [
            'filterFieldsConfig' => $filterFieldsConfig,
        ]);
    }

    /**
     * Obtener configuración de campos para filtros avanzados
     */
    public function getFilterFieldsConfig(): array
    {
        // Cargar convocatorias activas
        $convocatorias = Convocatoria::activas()
            ->with('cargo')
            ->get()
            ->map(fn($c) => [
                'value' => $c->id,
                'label' => $c->nombre . ' - ' . ($c->cargo ? $c->cargo->nombre : 'Sin cargo')
            ]);

        // Cargar cargos
        $cargos = Cargo::orderBy('nombre')->get()->map(fn($c) => [
            'value' => $c->id,
            'label' => $c->nombre
        ]);

        // Cargar periodos electorales
        $periodos = PeriodoElectoral::orderBy('nombre')->get()->map(fn($p) => [
            'value' => $p->id,
            'label' => $p->nombre
        ]);

        return [
            [
                'name' => 'postulaciones.convocatoria_id',
                'label' => 'Convocatoria',
                'type' => 'select',
                'options' => $convocatorias->toArray(),
                'placeholder' => 'Selecciona una convocatoria',
                'searchable' => true,
                'clearable' => true,
            ],
            [
                'name' => 'convocatorias.cargo_id',
                'label' => 'Cargo',
                'type' => 'select',
                'options' => $cargos->toArray(),
                'placeholder' => 'Selecciona un cargo',
                'searchable' => true,
                'clearable' => true,
            ],
            [
                'name' => 'convocatorias.periodo_electoral_id',
                'label' => 'Periodo Electoral',
                'type' => 'select',
                'options' => $periodos->toArray(),
                'placeholder' => 'Selecciona un periodo',
                'searchable' => true,
                'clearable' => true,
            ],
            [
                'name' => 'postulaciones.revisado_at',
                'label' => 'Fecha de Aceptación',
                'type' => 'date_range',
                'placeholder' => 'Selecciona un rango de fechas',
            ],
        ];
    }

    /**
     * Aplicar filtros simples para mantener compatibilidad
     */
    protected function applySimpleFilters($query, $request, $allowedFields = [])
    {
        // Solo aplicar si no hay filtros avanzados
        if (!$request->filled('advanced_filters')) {
            if ($request->filled('convocatoria_id')) {
                $query->where('convocatoria_id', $request->convocatoria_id);
            }

            if ($request->filled('cargo_id')) {
                $query->whereHas('convocatoria', function($q) use ($request) {
                    $q->where('cargo_id', $request->cargo_id);
                });
            }

            if ($request->filled('periodo_id')) {
                $query->whereHas('convocatoria', function($q) use ($request) {
                    $q->where('periodo_electoral_id', $request->periodo_id);
                });
            }
        }
    }
}