<?php

namespace App\Services\Core;

use App\Jobs\Core\NotifyUpdateApprovalJob;
use App\Jobs\Core\NotifyUpdateRejectionJob;
use App\Models\Core\User;
use App\Models\Core\UserUpdateRequest;
use App\Models\Core\UserVerificationRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserUpdateService
{
    protected WhatsAppService $whatsappService;
    protected TenantService $tenantService;

    public function __construct(WhatsAppService $whatsappService, TenantService $tenantService)
    {
        $this->whatsappService = $whatsappService;
        $this->tenantService = $tenantService;
    }

    /**
     * Crea una nueva solicitud de actualización de datos
     */
    public function createUpdateRequest(
        User $user,
        array $data,
        array $documents = [],
        string $ipAddress = null,
        string $userAgent = null
    ): ?UserUpdateRequest {
        // Verificar si el usuario ya tiene una solicitud pendiente
        if (UserUpdateRequest::userHasPendingRequest($user->id)) {
            Log::warning('Usuario ya tiene solicitud pendiente', [
                'user_id' => $user->id,
            ]);
            return null;
        }

        return DB::transaction(function () use ($user, $data, $documents, $ipAddress, $userAgent) {
            // Procesar documentos si existen
            $documentPaths = [];
            if (!empty($documents)) {
                $documentPaths = $this->processDocuments($documents, $user->id);
            }

            // Crear la solicitud
            $request = UserUpdateRequest::create([
                'user_id' => $user->id,
                'new_email' => $data['email'] ?? null,
                'new_telefono' => $data['telefono'] ?? null,
                'new_territorio_id' => $data['territorio_id'] ?? null,
                'new_departamento_id' => $data['departamento_id'] ?? null,
                'new_municipio_id' => $data['municipio_id'] ?? null,
                'new_localidad_id' => $data['localidad_id'] ?? null,
                'documentos_soporte' => $documentPaths,
                'current_email' => $user->email,
                'current_telefono' => $user->telefono,
                'current_territorio_id' => $user->territorio_id,
                'current_departamento_id' => $user->departamento_id,
                'current_municipio_id' => $user->municipio_id,
                'current_localidad_id' => $user->localidad_id,
                'status' => 'pending',
                'tenant_id' => $this->tenantService->getCurrentTenant()?->id,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            Log::info('Solicitud de actualización creada', [
                'request_id' => $request->id,
                'user_id' => $user->id,
                'has_documents' => !empty($documentPaths),
            ]);

            return $request;
        });
    }

    /**
     * Crea una solicitud desde una verificación no completada
     */
    public function createFromFailedVerification(
        UserVerificationRequest $verificationRequest,
        array $data,
        array $documents = []
    ): ?UserUpdateRequest {
        if (!$verificationRequest->user) {
            return null;
        }

        // Marcar la verificación como fallida si no está ya marcada
        if ($verificationRequest->status === 'pending') {
            $verificationRequest->update(['status' => 'failed']);
        }

        return $this->createUpdateRequest(
            $verificationRequest->user,
            $data,
            $documents,
            $verificationRequest->ip_address,
            $verificationRequest->user_agent
        );
    }

    /**
     * Procesa los documentos subidos
     */
    protected function processDocuments(array $files, int $userId): array
    {
        $paths = [];
        
        foreach ($files as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            // Generar nombre único para el archivo
            $fileName = sprintf(
                'user_%d_%s_%s.%s',
                $userId,
                date('YmdHis'),
                uniqid(),
                $file->getClientOriginalExtension()
            );

            // Definir ruta de almacenamiento
            $path = sprintf('uploads/user-updates/%d/%s', $userId, $fileName);

            // Almacenar el archivo
            $storedPath = Storage::disk('public')->putFileAs(
                dirname($path),
                $file,
                basename($path)
            );

            if ($storedPath) {
                $paths[] = $storedPath;
            }
        }

        return $paths;
    }

    /**
     * Aprueba una solicitud de actualización
     */
    public function approveRequest(UserUpdateRequest $request, User $admin, ?string $notes = null): bool
    {
        Log::info('[UserUpdateService::approveRequest] INICIO', [
            'request_id' => $request->id,
            'status' => $request->status,
            'admin_id' => $admin->id,
            'notes' => $notes
        ]);

        if (!$request->isPending()) {
            Log::warning('[UserUpdateService::approveRequest] Solicitud no está pendiente', [
                'request_id' => $request->id,
                'status' => $request->status
            ]);
            return false;
        }

        Log::info('[UserUpdateService::approveRequest] Llamando a request->approve', [
            'request_id' => $request->id
        ]);

        $approved = $request->approve($admin, $notes);

        Log::info('[UserUpdateService::approveRequest] Resultado de request->approve', [
            'request_id' => $request->id,
            'approved' => $approved,
            'new_status' => $request->fresh()->status
        ]);

        if ($approved) {
            // Enviar notificación al usuario
            dispatch(new NotifyUpdateApprovalJob($request));
            
            Log::info('[UserUpdateService::approveRequest] Aprobación exitosa', [
                'request_id' => $request->id,
                'admin_id' => $admin->id,
                'cambios_aplicados' => $request->hasDataChanges()
            ]);
        } else {
            Log::error('[UserUpdateService::approveRequest] Error en aprobación', [
                'request_id' => $request->id,
                'admin_id' => $admin->id
            ]);
        }

        return $approved;
    }

    /**
     * Rechaza una solicitud de actualización
     */
    public function rejectRequest(UserUpdateRequest $request, User $admin, ?string $notes = null): bool
    {
        Log::info('[UserUpdateService::rejectRequest] INICIO', [
            'request_id' => $request->id,
            'status' => $request->status,
            'admin_id' => $admin->id,
            'notes' => $notes
        ]);

        if (!$request->isPending()) {
            Log::warning('[UserUpdateService::rejectRequest] Solicitud no está pendiente', [
                'request_id' => $request->id,
                'status' => $request->status
            ]);
            return false;
        }

        Log::info('[UserUpdateService::rejectRequest] Llamando a request->reject', [
            'request_id' => $request->id
        ]);

        $rejected = $request->reject($admin, $notes);

        Log::info('[UserUpdateService::rejectRequest] Resultado de request->reject', [
            'request_id' => $request->id,
            'rejected' => $rejected,
            'new_status' => $request->fresh()->status
        ]);

        if ($rejected) {
            // Enviar notificación al usuario
            dispatch(new NotifyUpdateRejectionJob($request));
            
            Log::info('[UserUpdateService::rejectRequest] Rechazo exitoso', [
                'request_id' => $request->id,
                'admin_id' => $admin->id
            ]);
        } else {
            Log::error('[UserUpdateService::rejectRequest] Error en rechazo', [
                'request_id' => $request->id,
                'admin_id' => $admin->id
            ]);
        }

        return $rejected;
    }

    /**
     * Obtiene las solicitudes pendientes para revisión
     */
    public function getPendingRequests(int $perPage = 15)
    {
        return UserUpdateRequest::with(['user', 'admin'])
            ->pending()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Obtiene las estadísticas de solicitudes
     */
    public function getRequestStats(): array
    {
        $tenantId = $this->tenantService->getCurrentTenant()?->id;
        
        $query = UserUpdateRequest::query();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return [
            'total' => $query->count(),
            'pending' => (clone $query)->pending()->count(),
            'approved' => (clone $query)->approved()->count(),
            'rejected' => (clone $query)->rejected()->count(),
            'with_documents' => (clone $query)->withDocuments()->count(),
            'today' => (clone $query)->whereDate('created_at', today())->count(),
            'this_week' => (clone $query)->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
        ];
    }

    /**
     * Valida los documentos subidos
     */
    public function validateDocuments(array $files): array
    {
        $errors = [];
        $maxSize = 10 * 1024 * 1024; // 10MB
        $allowedExtensions = ['pdf', 'docx', 'doc', 'png', 'jpg', 'jpeg'];

        foreach ($files as $index => $file) {
            if (!$file || !$file->isValid()) {
                $errors[] = "Archivo {$index} no es válido";
                continue;
            }

            // Validar tamaño
            if ($file->getSize() > $maxSize) {
                $errors[] = "Archivo {$file->getClientOriginalName()} excede el tamaño máximo de 10MB";
            }

            // Validar extensión
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, $allowedExtensions)) {
                $errors[] = "Archivo {$file->getClientOriginalName()} tiene un formato no permitido";
            }
        }

        return $errors;
    }

    /**
     * Envía notificación de aprobación
     */
    public function sendApprovalNotification(UserUpdateRequest $request): void
    {
        $user = $request->user;
        
        // Mensaje de email
        $emailMessage = $this->buildApprovalEmailMessage($request);
        
        // Enviar email
        try {
            \Mail::to($user->email)->send(new \App\Mail\Core\UpdateApprovedMail($user, $request));
        } catch (\Exception $e) {
            Log::error('Error enviando email de aprobación', [
                'error' => $e->getMessage(),
                'request_id' => $request->id,
            ]);
        }

        // Enviar WhatsApp si tiene teléfono
        if ($user->telefono) {
            $whatsappMessage = $this->buildApprovalWhatsappMessage($request);
            $this->whatsappService->sendMessage($user->telefono, $whatsappMessage);
        }
    }

    /**
     * Envía notificación de rechazo
     */
    public function sendRejectionNotification(UserUpdateRequest $request): void
    {
        $user = $request->user;
        
        // Enviar email
        try {
            \Mail::to($user->email)->send(new \App\Mail\Core\UpdateRejectedMail($user, $request));
        } catch (\Exception $e) {
            Log::error('Error enviando email de rechazo', [
                'error' => $e->getMessage(),
                'request_id' => $request->id,
            ]);
        }

        // Enviar WhatsApp si tiene teléfono
        if ($user->telefono) {
            $whatsappMessage = $this->buildRejectionWhatsappMessage($request);
            $this->whatsappService->sendMessage($user->telefono, $whatsappMessage);
        }
    }

    /**
     * Construye mensaje de aprobación para WhatsApp
     */
    protected function buildApprovalWhatsappMessage(UserUpdateRequest $request): string
    {
        $changes = $request->getChangesSummary();
        $message = "Hola {$request->user->name},\n\n";
        $message .= "✅ Tu solicitud de actualización de datos ha sido *APROBADA*.\n\n";
        
        if (!empty($changes)) {
            $message .= "Cambios aplicados:\n";
            foreach ($changes as $field => $values) {
                $fieldName = $field === 'email' ? 'Email' : 'Teléfono';
                $message .= "• {$fieldName}: {$values['new']}\n";
            }
            $message .= "\n";
        }
        
        $message .= "Los cambios ya están activos en tu cuenta.\n\n";
        $message .= "Sistema de Votaciones";
        
        return $message;
    }

    /**
     * Construye mensaje de rechazo para WhatsApp
     */
    protected function buildRejectionWhatsappMessage(UserUpdateRequest $request): string
    {
        $message = "Hola {$request->user->name},\n\n";
        $message .= "❌ Tu solicitud de actualización de datos ha sido *RECHAZADA*.\n\n";
        
        if ($request->admin_notes) {
            $message .= "Motivo: {$request->admin_notes}\n\n";
        }
        
        $message .= "Por favor, verifica la información y vuelve a intentarlo.\n\n";
        $message .= "Sistema de Votaciones";
        
        return $message;
    }

    /**
     * Construye mensaje de aprobación para email
     */
    protected function buildApprovalEmailMessage(UserUpdateRequest $request): string
    {
        $changes = $request->getChangesSummary();
        $message = "<h3>Solicitud Aprobada</h3>";
        $message .= "<p>Tu solicitud de actualización de datos ha sido aprobada.</p>";
        
        if (!empty($changes)) {
            $message .= "<h4>Cambios aplicados:</h4><ul>";
            foreach ($changes as $field => $values) {
                $fieldName = $field === 'email' ? 'Email' : 'Teléfono';
                $message .= "<li><strong>{$fieldName}:</strong> {$values['current']} → {$values['new']}</li>";
            }
            $message .= "</ul>";
        }
        
        return $message;
    }
}