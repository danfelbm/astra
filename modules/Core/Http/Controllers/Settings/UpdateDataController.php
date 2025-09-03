<?php

namespace Modules\Core\Http\Controllers\Settings;

use Modules\Core\Http\Controllers\Base\Controller;
use Modules\Users\Models\UserUpdateRequest;
use Modules\Geografico\Models\Departamento;
use Modules\Geografico\Models\Localidad;
use Modules\Geografico\Models\Municipio;
use Modules\Geografico\Models\Territorio;
use Modules\Users\Services\UserUpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UpdateDataController extends Controller
{
    protected UserUpdateService $updateService;

    public function __construct(UserUpdateService $updateService)
    {
        $this->updateService = $updateService;
    }

    /**
     * Muestra el formulario de solicitud de cambio de residencia
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        
        // Verificar si el usuario tiene una solicitud pendiente
        $hasPendingRequest = UserUpdateRequest::userHasPendingRequest($user->id);
        
        // Si tiene solicitud pendiente, obtenerla para mostrar el estado
        $pendingRequest = null;
        if ($hasPendingRequest) {
            $pendingRequest = UserUpdateRequest::where('user_id', $user->id)
                ->pending()
                ->with(['newTerritorio', 'newDepartamento', 'newMunicipio', 'newLocalidad'])
                ->first();
        }
        
        // Cargar relaciones geográficas actuales del usuario
        $user->load(['territorio', 'departamento', 'municipio', 'localidad']);
        
        return Inertia::render('User/Settings/UpdateData', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'telefono' => $user->telefono,
                'territorio_id' => $user->territorio_id,
                'territorio' => $user->territorio,
                'departamento_id' => $user->departamento_id,
                'departamento' => $user->departamento,
                'municipio_id' => $user->municipio_id,
                'municipio' => $user->municipio,
                'localidad_id' => $user->localidad_id,
                'localidad' => $user->localidad,
            ],
            'hasPendingRequest' => $hasPendingRequest,
            'pendingRequest' => $pendingRequest ? [
                'id' => $pendingRequest->id,
                'status' => $pendingRequest->status,
                'created_at' => $pendingRequest->created_at->format('Y-m-d H:i:s'),
                'new_territorio' => $pendingRequest->newTerritorio,
                'new_departamento' => $pendingRequest->newDepartamento,
                'new_municipio' => $pendingRequest->newMunicipio,
                'new_localidad' => $pendingRequest->newLocalidad,
            ] : null,
        ]);
    }

    /**
     * Procesa la solicitud de cambio de residencia
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        // Verificar que no tenga solicitud pendiente
        if (UserUpdateRequest::userHasPendingRequest($user->id)) {
            return back()->withErrors([
                'error' => 'Ya tienes una solicitud de actualización pendiente. Por favor espera a que sea procesada.',
            ]);
        }
        
        // Validar los datos de entrada
        $validated = $request->validate([
            'territorio_id' => [
                'required',
                'integer',
                Rule::exists(Territorio::class, 'id'),
            ],
            'departamento_id' => [
                'required',
                'integer',
                Rule::exists(Departamento::class, 'id')
                    ->where('territorio_id', $request->territorio_id),
            ],
            'municipio_id' => [
                'required',
                'integer',
                Rule::exists(Municipio::class, 'id')
                    ->where('departamento_id', $request->departamento_id),
            ],
            'localidad_id' => [
                'nullable',
                'integer',
                Rule::exists(Localidad::class, 'id')
                    ->where('municipio_id', $request->municipio_id),
            ],
            'documentos' => [
                'nullable',
                'array',
                'max:3', // Máximo 3 documentos
            ],
            'documentos.*' => [
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120', // 5MB máximo por archivo
            ],
        ], [
            'territorio_id.required' => 'El territorio es obligatorio.',
            'territorio_id.exists' => 'El territorio seleccionado no es válido.',
            'departamento_id.required' => 'El departamento es obligatorio.',
            'departamento_id.exists' => 'El departamento seleccionado no es válido para este territorio.',
            'municipio_id.required' => 'El municipio es obligatorio.',
            'municipio_id.exists' => 'El municipio seleccionado no es válido para este departamento.',
            'localidad_id.exists' => 'La localidad seleccionada no es válida para este municipio.',
            'documentos.max' => 'Puedes subir máximo 3 documentos.',
            'documentos.*.mimes' => 'Los documentos deben ser PDF, JPG, JPEG o PNG.',
            'documentos.*.max' => 'Cada documento no puede superar los 5MB.',
        ]);
        
        // Verificar que realmente hay un cambio
        $hasChange = ($validated['territorio_id'] != $user->territorio_id) ||
                    ($validated['departamento_id'] != $user->departamento_id) ||
                    ($validated['municipio_id'] != $user->municipio_id) ||
                    ($validated['localidad_id'] != $user->localidad_id);
        
        if (!$hasChange) {
            return back()->withErrors([
                'error' => 'La ubicación seleccionada es la misma que tu ubicación actual.',
            ]);
        }
        
        try {
            // Crear la solicitud de actualización
            $updateRequest = $this->updateService->createUpdateRequest(
                $user,
                $validated,
                $request->file('documentos') ?? [],
                $request->ip(),
                $request->userAgent()
            );
            
            if (!$updateRequest) {
                return back()->withErrors([
                    'error' => 'No se pudo crear la solicitud. Es posible que ya tengas una solicitud pendiente.',
                ]);
            }
            
            return redirect()->route('update-data.edit')
                ->with('success', 'Tu solicitud de cambio de residencia ha sido enviada. Te notificaremos cuando sea procesada.');
                
        } catch (\Exception $e) {
            \Log::error('Error al crear solicitud de cambio de residencia', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return back()->withErrors([
                'error' => 'Ocurrió un error al procesar tu solicitud. Por favor, intenta nuevamente.',
            ]);
        }
    }
    
    /**
     * Cancela una solicitud pendiente
     */
    public function cancel(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        $pendingRequest = UserUpdateRequest::where('user_id', $user->id)
            ->pending()
            ->first();
            
        if (!$pendingRequest) {
            return back()->withErrors([
                'error' => 'No tienes solicitudes pendientes para cancelar.',
            ]);
        }
        
        try {
            DB::transaction(function () use ($pendingRequest) {
                // Eliminar archivos si existen
                if (!empty($pendingRequest->documentos_soporte)) {
                    foreach ($pendingRequest->documentos_soporte as $path) {
                        \Storage::disk('public')->delete($path);
                    }
                }
                
                // Eliminar la solicitud
                $pendingRequest->delete();
            });
            
            return redirect()->route('update-data.edit')
                ->with('success', 'Tu solicitud ha sido cancelada exitosamente.');
                
        } catch (\Exception $e) {
            \Log::error('Error al cancelar solicitud', [
                'user_id' => $user->id,
                'request_id' => $pendingRequest->id,
                'error' => $e->getMessage(),
            ]);
            
            return back()->withErrors([
                'error' => 'No se pudo cancelar la solicitud. Por favor, intenta nuevamente.',
            ]);
        }
    }
}