<?php

namespace Modules\Campanas\Repositories;

use Modules\Campanas\Models\PlantillaEmail;
use Modules\Campanas\Models\PlantillaWhatsApp;
use Modules\Core\Services\TenantService;
use Modules\Core\Traits\HasAdvancedFilters;
use Modules\Core\Traits\HasGeographicFilters;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class PlantillaRepository
{
    use HasAdvancedFilters, HasGeographicFilters;

    public function __construct(
        private TenantService $tenantService
    ) {}

    /**
     * Obtener plantillas de email paginadas con filtros
     */
    public function getEmailsPaginated(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = PlantillaEmail::query()
            ->with(['createdBy'])
            ->withCount('campanas');
        
        // Aplicar filtros básicos
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->search . '%')
                  ->orWhere('asunto', 'like', '%' . $request->search . '%')
                  ->orWhere('descripcion', 'like', '%' . $request->search . '%');
            });
        }
        
        if ($request->filled('es_activa')) {
            $query->where('es_activa', $request->es_activa === 'true' || $request->es_activa === '1');
        }
        
        // Aplicar filtros avanzados si existen
        if ($request->filled('advanced_filters')) {
            $allowedFields = ['nombre', 'asunto', 'descripcion', 'es_activa', 'created_at'];
            $quickSearchFields = ['nombre', 'asunto', 'descripcion'];
            $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);
        }
        
        // Ordenamiento
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Obtener plantillas de WhatsApp paginadas con filtros
     */
    public function getWhatsAppsPaginated(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = PlantillaWhatsApp::query()
            ->with(['createdBy'])
            ->withCount('campanas');
        
        // Aplicar filtros básicos
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->search . '%')
                  ->orWhere('contenido', 'like', '%' . $request->search . '%')
                  ->orWhere('descripcion', 'like', '%' . $request->search . '%');
            });
        }
        
        if ($request->filled('es_activa')) {
            $query->where('es_activa', $request->es_activa === 'true' || $request->es_activa === '1');
        }
        
        // Aplicar filtros avanzados si existen
        if ($request->filled('advanced_filters')) {
            $allowedFields = ['nombre', 'descripcion', 'es_activa', 'usa_formato', 'created_at'];
            $quickSearchFields = ['nombre', 'contenido', 'descripcion'];
            $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);
        }
        
        // Ordenamiento
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Obtener todas las plantillas de email activas
     */
    public function getActiveEmails()
    {
        return PlantillaEmail::activas()
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Obtener todas las plantillas de WhatsApp activas
     */
    public function getActiveWhatsApps()
    {
        return PlantillaWhatsApp::activas()
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Obtener configuración de campos para filtros avanzados de emails
     */
    public function getEmailFilterFieldsConfig(): array
    {
        return [
            [
                'name' => 'nombre',
                'label' => 'Nombre',
                'type' => 'text',
            ],
            [
                'name' => 'asunto',
                'label' => 'Asunto',
                'type' => 'text',
            ],
            [
                'name' => 'descripcion',
                'label' => 'Descripción',
                'type' => 'text',
            ],
            [
                'name' => 'es_activa',
                'label' => 'Estado',
                'type' => 'select',
                'options' => [
                    ['value' => 'true', 'label' => 'Activa'],
                    ['value' => 'false', 'label' => 'Inactiva'],
                ],
            ],
            [
                'name' => 'created_at',
                'label' => 'Fecha de creación',
                'type' => 'date',
            ],
        ];
    }

    /**
     * Obtener configuración de campos para filtros avanzados de WhatsApp
     */
    public function getWhatsAppFilterFieldsConfig(): array
    {
        return [
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
                'name' => 'es_activa',
                'label' => 'Estado',
                'type' => 'select',
                'options' => [
                    ['value' => 'true', 'label' => 'Activa'],
                    ['value' => 'false', 'label' => 'Inactiva'],
                ],
            ],
            [
                'name' => 'usa_formato',
                'label' => 'Usa formato',
                'type' => 'select',
                'options' => [
                    ['value' => 'true', 'label' => 'Sí'],
                    ['value' => 'false', 'label' => 'No'],
                ],
            ],
            [
                'name' => 'created_at',
                'label' => 'Fecha de creación',
                'type' => 'date',
            ],
        ];
    }

    /**
     * Buscar plantilla de email por ID
     */
    public function findEmail(int $id): ?PlantillaEmail
    {
        return PlantillaEmail::with(['createdBy', 'campanas'])->find($id);
    }

    /**
     * Buscar plantilla de WhatsApp por ID
     */
    public function findWhatsApp(int $id): ?PlantillaWhatsApp
    {
        return PlantillaWhatsApp::with(['createdBy', 'campanas'])->find($id);
    }

    /**
     * Crear plantilla de email
     */
    public function createEmail(array $data): PlantillaEmail
    {
        if (Auth::check()) {
            $data['created_by'] = Auth::id();
        }
        
        return PlantillaEmail::create($data);
    }

    /**
     * Crear plantilla de WhatsApp
     */
    public function createWhatsApp(array $data): PlantillaWhatsApp
    {
        if (Auth::check()) {
            $data['created_by'] = Auth::id();
        }
        
        return PlantillaWhatsApp::create($data);
    }

    /**
     * Actualizar plantilla de email
     */
    public function updateEmail(PlantillaEmail $plantilla, array $data): bool
    {
        return $plantilla->update($data);
    }

    /**
     * Actualizar plantilla de WhatsApp
     */
    public function updateWhatsApp(PlantillaWhatsApp $plantilla, array $data): bool
    {
        return $plantilla->update($data);
    }

    /**
     * Eliminar plantilla de email
     */
    public function deleteEmail(PlantillaEmail $plantilla): bool
    {
        // Verificar si tiene campañas asociadas
        if ($plantilla->campanas()->exists()) {
            return false;
        }
        
        return $plantilla->delete();
    }

    /**
     * Eliminar plantilla de WhatsApp
     */
    public function deleteWhatsApp(PlantillaWhatsApp $plantilla): bool
    {
        // Verificar si tiene campañas asociadas
        if ($plantilla->campanas()->exists()) {
            return false;
        }
        
        return $plantilla->delete();
    }

    /**
     * Duplicar plantilla de email
     */
    public function duplicateEmail(PlantillaEmail $plantilla): PlantillaEmail
    {
        $nueva = $plantilla->replicate();
        $nueva->nombre = $plantilla->nombre . ' (Copia)';
        $nueva->es_activa = false;
        $nueva->created_by = Auth::id();
        $nueva->save();
        
        return $nueva;
    }

    /**
     * Duplicar plantilla de WhatsApp
     */
    public function duplicateWhatsApp(PlantillaWhatsApp $plantilla): PlantillaWhatsApp
    {
        $nueva = $plantilla->replicate();
        $nueva->nombre = $plantilla->nombre . ' (Copia)';
        $nueva->es_activa = false;
        $nueva->created_by = Auth::id();
        $nueva->save();
        
        return $nueva;
    }
}