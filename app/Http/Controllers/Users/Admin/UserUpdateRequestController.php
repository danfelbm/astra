<?php

namespace App\Http\Controllers\Users\Admin;

use App\Http\Controllers\Core\AdminController;
use App\Models\Core\UserUpdateRequest;
use App\Services\Core\UserUpdateService;
use App\Traits\HasAdvancedFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserUpdateRequestController extends AdminController
{
    use HasAdvancedFilters;

    protected UserUpdateService $updateService;

    public function __construct(UserUpdateService $updateService)
    {
        parent::__construct();
        $this->updateService = $updateService;
    }

    /**
     * Lista de solicitudes de actualización
     */
    public function index(Request $request): Response
    {
        $query = UserUpdateRequest::with(['user', 'admin'])
            ->orderBy('created_at', 'desc');

        // Aplicar filtros avanzados
        $query = $this->applyAdvancedFilters($query, $request);

        // Filtros específicos
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('has_documents')) {
            if ($request->has_documents === 'true') {
                $query->withDocuments();
            } else {
                $query->whereNull('documentos_soporte')
                    ->orWhere('documentos_soporte', '[]');
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $requests = $query->paginate(15)->withQueryString();
        
        // Añadir campos from y to a la paginación
        $requestsArray = $requests->toArray();
        if (!isset($requestsArray['from'])) {
            $requestsArray['from'] = ($requests->currentPage() - 1) * $requests->perPage() + 1;
            $requestsArray['to'] = min($requests->currentPage() * $requests->perPage(), $requests->total());
        }

        // Obtener estadísticas
        $stats = $this->updateService->getRequestStats();

        return Inertia::render('Admin/Users/UpdateRequests/Index', [
            'requests' => $requestsArray,
            'stats' => $stats,
            'filters' => $request->all(['status', 'has_documents', 'date_from', 'date_to', 'search']),
            'filterFieldsConfig' => $this->getFilterFieldsConfig(),
        ]);
    }

    /**
     * Muestra el detalle de una solicitud
     */
    public function show(UserUpdateRequest $updateRequest): Response
    {
        $updateRequest->load(['user', 'admin']);
        
        // Protección adicional si el usuario no existe
        if (!$updateRequest->user) {
            \Log::error('[UserUpdateRequestController::show] Usuario asociado no encontrado', [
                'request_id' => $updateRequest->id
            ]);
            abort(404, 'Usuario asociado no encontrado');
        }

        return Inertia::render('Admin/Users/UpdateRequests/Show', [
            'updateRequest' => [
                'id' => $updateRequest->id,
                'user' => [
                    'id' => $updateRequest->user->id,
                    'name' => $updateRequest->user->name,
                    'email' => $updateRequest->user->email,
                    'telefono' => $updateRequest->user->telefono,
                    'documento_identidad' => $updateRequest->user->documento_identidad,
                ],
                'new_email' => $updateRequest->new_email,
                'new_telefono' => $updateRequest->new_telefono,
                'current_email' => $updateRequest->current_email,
                'current_telefono' => $updateRequest->current_telefono,
                'documentos_soporte' => $updateRequest->getDocumentInfo(),
                'status' => $updateRequest->status,
                'admin' => $updateRequest->admin ? [
                    'id' => $updateRequest->admin->id,
                    'name' => $updateRequest->admin->name,
                ] : null,
                'admin_notes' => $updateRequest->admin_notes,
                'approved_at' => $updateRequest->approved_at?->format('Y-m-d H:i:s'),
                'rejected_at' => $updateRequest->rejected_at?->format('Y-m-d H:i:s'),
                'created_at' => $updateRequest->created_at->format('Y-m-d H:i:s'),
                'changes_summary' => $updateRequest->getChangesSummary(),
                'has_changes' => $updateRequest->hasDataChanges(),
            ],
        ]);
    }

    /**
     * Aprueba una solicitud
     */
    public function approve(Request $request, UserUpdateRequest $updateRequest)
    {
        \Log::info('[UserUpdateRequestController::approve] INICIO', [
            'request_id' => $updateRequest->id,
            'request_status' => $updateRequest->status,
            'user_id' => auth()->id(),
            'notes' => $request->notes,
            'request_data' => $request->all()
        ]);

        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        if (!$updateRequest->isPending()) {
            \Log::warning('[UserUpdateRequestController::approve] Solicitud ya procesada', [
                'request_id' => $updateRequest->id,
                'current_status' => $updateRequest->status
            ]);
            return back()->with('error', 'Esta solicitud ya ha sido procesada.');
        }

        \Log::info('[UserUpdateRequestController::approve] Llamando a updateService->approveRequest', [
            'request_id' => $updateRequest->id
        ]);

        $approved = $this->updateService->approveRequest(
            $updateRequest,
            auth()->user(),
            $request->notes
        );

        \Log::info('[UserUpdateRequestController::approve] Resultado de aprobación', [
            'request_id' => $updateRequest->id,
            'approved' => $approved,
            'new_status' => $updateRequest->fresh()->status
        ]);

        if ($approved) {
            return redirect()->route('admin.update-requests.index')
                ->with('success', 'Solicitud aprobada correctamente. Los cambios han sido aplicados.');
        }

        \Log::error('[UserUpdateRequestController::approve] Error al aprobar', [
            'request_id' => $updateRequest->id
        ]);
        return back()->with('error', 'No se pudo aprobar la solicitud.');
    }

    /**
     * Rechaza una solicitud
     */
    public function reject(Request $request, UserUpdateRequest $updateRequest)
    {
        \Log::info('[UserUpdateRequestController::reject] INICIO', [
            'request_id' => $updateRequest->id,
            'request_status' => $updateRequest->status,
            'user_id' => auth()->id(),
            'notes' => $request->notes,
            'request_data' => $request->all()
        ]);

        $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        if (!$updateRequest->isPending()) {
            \Log::warning('[UserUpdateRequestController::reject] Solicitud ya procesada', [
                'request_id' => $updateRequest->id,
                'current_status' => $updateRequest->status
            ]);
            return back()->with('error', 'Esta solicitud ya ha sido procesada.');
        }

        \Log::info('[UserUpdateRequestController::reject] Llamando a updateService->rejectRequest', [
            'request_id' => $updateRequest->id
        ]);

        $rejected = $this->updateService->rejectRequest(
            $updateRequest,
            auth()->user(),
            $request->notes
        );

        \Log::info('[UserUpdateRequestController::reject] Resultado de rechazo', [
            'request_id' => $updateRequest->id,
            'rejected' => $rejected,
            'new_status' => $updateRequest->fresh()->status
        ]);

        if ($rejected) {
            return redirect()->route('admin.update-requests.index')
                ->with('success', 'Solicitud rechazada correctamente.');
        }

        \Log::error('[UserUpdateRequestController::reject] Error al rechazar', [
            'request_id' => $updateRequest->id
        ]);
        return back()->with('error', 'No se pudo rechazar la solicitud.');
    }

    /**
     * Descarga un documento soporte
     */
    public function downloadDocument(Request $request, UserUpdateRequest $updateRequest)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        // Verificar que el documento pertenece a esta solicitud
        if (!in_array($request->path, $updateRequest->documentos_soporte ?? [])) {
            abort(404, 'Documento no encontrado');
        }

        // Verificar que el archivo existe
        if (!\Storage::disk('public')->exists($request->path)) {
            abort(404, 'Archivo no encontrado');
        }

        return \Storage::disk('public')->download($request->path);
    }

    /**
     * Obtiene la configuración de campos para filtros
     */
    protected function getFilterFieldsConfig(): array
    {
        return [
            'user.name' => [
                'label' => 'Nombre del Usuario',
                'type' => 'text',
                'placeholder' => 'Buscar por nombre...',
            ],
            'user.email' => [
                'label' => 'Email',
                'type' => 'text',
                'placeholder' => 'Buscar por email...',
            ],
            'user.documento_identidad' => [
                'label' => 'Documento',
                'type' => 'text',
                'placeholder' => 'Buscar por documento...',
            ],
            'status' => [
                'label' => 'Estado',
                'type' => 'select',
                'options' => [
                    ['value' => 'pending', 'label' => 'Pendiente'],
                    ['value' => 'approved', 'label' => 'Aprobada'],
                    ['value' => 'rejected', 'label' => 'Rechazada'],
                ],
            ],
            'created_at' => [
                'label' => 'Fecha de Solicitud',
                'type' => 'date',
            ],
        ];
    }

    /**
     * Exporta las solicitudes a CSV
     */
    public function export(Request $request)
    {
        $query = UserUpdateRequest::with(['user', 'admin']);

        // Aplicar los mismos filtros que en index
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $requests = $query->get();

        // Generar CSV
        $csv = "ID,Usuario,Documento,Email Actual,Email Nuevo,Teléfono Actual,Teléfono Nuevo,Estado,Admin,Fecha Solicitud,Fecha Procesamiento\n";
        
        foreach ($requests as $req) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $req->id,
                $req->user->name,
                $req->user->documento_identidad,
                $req->current_email,
                $req->new_email ?? '',
                $req->current_telefono ?? '',
                $req->new_telefono ?? '',
                $req->status,
                $req->admin?->name ?? '',
                $req->created_at->format('Y-m-d H:i:s'),
                ($req->approved_at ?? $req->rejected_at)?->format('Y-m-d H:i:s') ?? ''
            );
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="solicitudes_actualizacion_' . date('Y-m-d') . '.csv"',
        ]);
    }
}