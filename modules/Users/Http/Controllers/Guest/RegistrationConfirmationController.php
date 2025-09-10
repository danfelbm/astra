<?php

namespace Modules\Users\Http\Controllers\Guest;

use Modules\Core\Http\Controllers\GuestController;
use Modules\Core\Models\UserVerificationRequest;
use Modules\Votaciones\Models\Votacion;
use Modules\Users\Services\UserUpdateService;
use Modules\Core\Services\UserVerificationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationConfirmationController extends GuestController
{
    protected UserVerificationService $verificationService;
    protected UserUpdateService $updateService;

    public function __construct(
        UserVerificationService $verificationService,
        UserUpdateService $updateService
    ) {
        $this->verificationService = $verificationService;
        $this->updateService = $updateService;
    }

    /**
     * Muestra la página inicial de confirmación de registro
     */
    public function index(): Response
    {
        return Inertia::render('Modules/Users/Guest/Users/RegistrationConfirmation', [
            'layout' => $this->getLayout(),
        ]);
    }

    /**
     * Busca un usuario por su documento de identidad
     */
    public function search(Request $request)
    {
        $request->validate([
            'documento_identidad' => 'required|string|max:20',
        ]);

        $documento = $request->documento_identidad;
        $user = $this->verificationService->findUserByDocument($documento);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ningún usuario registrado con ese documento.',
            ], 404);
        }

        // Verificar restricciones de límite de censo
        $censoRestriction = $this->checkCensoRestrictions($user);

        // Crear o recuperar solicitud de verificación
        $verificationRequest = $this->verificationService->initiateVerification(
            $documento,
            $request->ip(),
            $request->userAgent()
        );

        $responseData = [
            'success' => true,
            'user' => [
                'name' => $user->name,
                'documento_identidad' => $user->documento_identidad,
                'email' => $user->email,
                'telefono' => $user->telefono,
                'has_email' => !empty($user->email),
                'has_phone' => !empty($user->telefono),
                'created_at' => $user->created_at,
            ],
            'verification_id' => $verificationRequest->id,
        ];

        // Agregar información sobre restricción de censo si existe
        if ($censoRestriction) {
            $responseData['censo_restriction'] = $censoRestriction;
        }

        $response = response()->json($responseData);

        // Guardar token de sesión en cookie segura httpOnly
        // Cookie válida por 30 minutos, httpOnly para mayor seguridad
        if ($verificationRequest->session_token) {
            $response->cookie(
                'verification_token',
                $verificationRequest->session_token,
                30, // minutos
                null, // path
                null, // domain
                true, // secure (solo HTTPS en producción)
                true, // httpOnly
                false, // raw
                'strict' // sameSite
            );
        }

        return $response;
    }

    /**
     * Envía códigos de verificación
     */
    public function sendVerification(Request $request)
    {
        $request->validate([
            'verification_id' => 'required|exists:user_verification_requests,id',
        ]);

        // Validar token de sesión
        $sessionToken = $request->cookie('verification_token');
        $verificationRequest = $this->verificationService->validateSessionToken($request->verification_id, $sessionToken);
        
        if (!$verificationRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión inválida. Por favor, inicia el proceso nuevamente.',
            ], 403);
        }

        // Verificar límite de intentos
        if (!$verificationRequest->canResendCodes()) {
            return response()->json([
                'success' => false,
                'message' => 'Has excedido el límite de intentos. Por favor, espera 1 hora antes de intentar nuevamente.',
            ], 429);
        }

        // Enviar códigos
        $sent = $this->verificationService->sendVerificationCodes($verificationRequest);

        if (!$sent) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron enviar los códigos de verificación. Por favor, intenta nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Códigos de verificación enviados correctamente.',
            'channels' => [
                'email' => !empty($verificationRequest->verification_code_email),
                'whatsapp' => !empty($verificationRequest->verification_code_whatsapp),
            ],
            'expiration_minutes' => 15,
        ]);
    }

    /**
     * Verifica un código ingresado
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'verification_id' => 'required|exists:user_verification_requests,id',
            'code' => 'required|string|size:6',
            'channel' => 'required|in:email,whatsapp',
        ]);

        // Validar token de sesión
        $sessionToken = $request->cookie('verification_token');
        $verificationRequest = $this->verificationService->validateSessionToken($request->verification_id, $sessionToken);
        
        if (!$verificationRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión inválida. Por favor, inicia el proceso nuevamente.',
            ], 403);
        }

        // Verificar expiración
        if ($verificationRequest->hasExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Los códigos han expirado. Por favor, solicita nuevos códigos.',
                'expired' => true,
            ], 400);
        }

        // Validar código
        $isValid = $this->verificationService->validateCode(
            $verificationRequest,
            $request->code,
            $request->channel
        );

        if (!$isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Código incorrecto. Por favor, verifica e intenta nuevamente.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Código verificado correctamente.',
            'fully_verified' => $verificationRequest->fresh()->isFullyVerified(),
            'can_proceed' => $this->verificationService->canProceedToUpdate($verificationRequest->fresh()),
        ]);
    }

    /**
     * Muestra el formulario de actualización de datos
     */
    public function showUpdateForm(Request $request): Response|RedirectResponse
    {
        $request->validate([
            'verification_id' => 'required|exists:user_verification_requests,id',
        ]);

        // Validar token de sesión
        $sessionToken = $request->cookie('verification_token');
        $verificationRequest = $this->verificationService->validateSessionToken($request->verification_id, $sessionToken);
        
        if (!$verificationRequest) {
            return redirect()->route('registro.confirmacion.index')
                ->with('error', 'Sesión inválida. Por favor, inicia el proceso nuevamente.');
        }

        // Cargar relación con user y datos geográficos
        $verificationRequest->load(['user.territorio', 'user.departamento', 'user.municipio', 'user.localidad']);

        // Verificar si puede proceder
        if (!$this->verificationService->canProceedToUpdate($verificationRequest)) {
            return redirect()->route('registro.confirmacion.index')
                ->with('error', 'No tienes permiso para acceder a esta página.');
        }

        return Inertia::render('Modules/Users/Guest/Users/UpdateDataForm', [
            'layout' => $this->getLayout(),
            'verification' => [
                'id' => $verificationRequest->id,
                'is_verified' => $verificationRequest->isFullyVerified(),
            ],
            'user' => [
                'name' => $verificationRequest->user->name,
                'email' => $verificationRequest->user->email,
                'telefono' => $verificationRequest->user->telefono,
                'documento_identidad' => $verificationRequest->user->documento_identidad,
                'territorio_id' => $verificationRequest->user->territorio_id,
                'territorio' => $verificationRequest->user->territorio,
                'departamento_id' => $verificationRequest->user->departamento_id,
                'departamento' => $verificationRequest->user->departamento,
                'municipio_id' => $verificationRequest->user->municipio_id,
                'municipio' => $verificationRequest->user->municipio,
                'localidad_id' => $verificationRequest->user->localidad_id,
                'localidad' => $verificationRequest->user->localidad,
            ],
        ]);
    }

    /**
     * Procesa la solicitud de actualización de datos
     */
    public function submitUpdate(Request $request)
    {
        $request->validate([
            'verification_id' => 'required|exists:user_verification_requests,id',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'territorio_id' => 'nullable|exists:territorios,id',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'municipio_id' => 'nullable|exists:municipios,id',
            'localidad_id' => 'nullable|exists:localidades,id',
            'documentos' => 'nullable|array|max:5',
            'documentos.*' => 'file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240', // 10MB
        ]);

        // Validar token de sesión
        $sessionToken = $request->cookie('verification_token');
        $verificationRequest = $this->verificationService->validateSessionToken($request->verification_id, $sessionToken);
        
        if (!$verificationRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión inválida. Por favor, inicia el proceso nuevamente.',
            ], 403);
        }

        // Cargar relación con user
        $verificationRequest->load('user');

        // Verificar si puede proceder
        if (!$this->verificationService->canProceedToUpdate($verificationRequest)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para realizar esta acción.',
            ], 403);
        }

        // Validar que haya cambios
        $hasChanges = false;
        if ($request->email && $request->email !== $verificationRequest->user->email) {
            $hasChanges = true;
        }
        if ($request->telefono && $request->telefono !== $verificationRequest->user->telefono) {
            $hasChanges = true;
        }
        // Verificar cambios en ubicación
        if ($request->has('territorio_id') && $request->territorio_id != $verificationRequest->user->territorio_id) {
            $hasChanges = true;
        }
        if ($request->has('departamento_id') && $request->departamento_id != $verificationRequest->user->departamento_id) {
            $hasChanges = true;
        }
        if ($request->has('municipio_id') && $request->municipio_id != $verificationRequest->user->municipio_id) {
            $hasChanges = true;
        }
        if ($request->has('localidad_id') && $request->localidad_id != $verificationRequest->user->localidad_id) {
            $hasChanges = true;
        }

        if (!$hasChanges && empty($request->file('documentos'))) {
            return response()->json([
                'success' => false,
                'message' => 'No se detectaron cambios para actualizar.',
            ], 400);
        }

        // Validar documentos si se enviaron
        $documents = $request->file('documentos') ?? [];
        if (!empty($documents)) {
            $errors = $this->updateService->validateDocuments($documents);
            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores en los documentos enviados.',
                    'errors' => $errors,
                ], 400);
            }
        }

        // Crear solicitud de actualización
        $updateRequest = null;
        
        if ($verificationRequest->isFullyVerified()) {
            // Si está verificado, crear solicitud normal
            $updateRequest = $this->updateService->createUpdateRequest(
                $verificationRequest->user,
                [
                    'email' => $request->email,
                    'telefono' => $request->telefono,
                    'territorio_id' => $request->territorio_id,
                    'departamento_id' => $request->departamento_id,
                    'municipio_id' => $request->municipio_id,
                    'localidad_id' => $request->localidad_id,
                ],
                $documents,
                $request->ip(),
                $request->userAgent()
            );
        } else {
            // Si no está verificado, crear desde verificación fallida
            $updateRequest = $this->updateService->createFromFailedVerification(
                $verificationRequest,
                [
                    'email' => $request->email,
                    'telefono' => $request->telefono,
                    'territorio_id' => $request->territorio_id,
                    'departamento_id' => $request->departamento_id,
                    'municipio_id' => $request->municipio_id,
                    'localidad_id' => $request->localidad_id,
                ],
                $documents
            );
        }

        if (!$updateRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Ya tienes una solicitud de actualización pendiente.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tu solicitud ha sido enviada correctamente. Te notificaremos cuando sea procesada.',
            'request_id' => $updateRequest->id,
        ]);
    }

    /**
     * Verifica si han pasado 10 segundos para habilitar el botón
     */
    public function checkTimeout(Request $request)
    {
        $request->validate([
            'verification_id' => 'required|exists:user_verification_requests,id',
        ]);

        // Validar token de sesión
        $sessionToken = $request->cookie('verification_token');
        $verificationRequest = $this->verificationService->validateSessionToken($request->verification_id, $sessionToken);
        
        if (!$verificationRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión inválida. Por favor, inicia el proceso nuevamente.',
            ], 403);
        }
        
        $canProceed = $this->verificationService->canProceedToUpdate($verificationRequest);

        return response()->json([
            'can_proceed' => $canProceed,
            'seconds_elapsed' => $verificationRequest->email_sent_at 
                ? now()->diffInSeconds($verificationRequest->email_sent_at)
                : 0,
        ]);
    }

    /**
     * Verifica si el usuario tiene restricciones por límite de censo en votaciones activas
     * 
     * @param \Modules\Core\Models\User $user
     * @return array|null Información sobre la restricción si existe, null si no hay restricciones
     */
    protected function checkCensoRestrictions($user): ?array
    {
        // Obtener votaciones activas con límite de censo configurado
        $votacionesActivas = Votacion::where('estado', 'activa')
            ->whereNotNull('limite_censo')
            ->where(function ($query) use ($user) {
                // Verificar si el usuario coincide con alguna restricción geográfica
                $query->whereJsonContains('territorios_ids', $user->territorio_id)
                    ->when($user->departamento_id, function ($q) use ($user) {
                        $q->orWhereJsonContains('departamentos_ids', $user->departamento_id);
                    })
                    ->when($user->municipio_id, function ($q) use ($user) {
                        $q->orWhereJsonContains('municipios_ids', $user->municipio_id);
                    })
                    ->when($user->localidad_id, function ($q) use ($user) {
                        $q->orWhereJsonContains('localidades_ids', $user->localidad_id);
                    })
                    // También incluir votaciones sin restricciones geográficas (aplican a todos)
                    ->orWhere(function ($subquery) {
                        $subquery->whereNull('territorios_ids')
                            ->whereNull('departamentos_ids')
                            ->whereNull('municipios_ids')
                            ->whereNull('localidades_ids');
                    })
                    ->orWhere(function ($subquery) {
                        // O votaciones con arrays vacíos
                        $subquery->where('territorios_ids', '[]')
                            ->where('departamentos_ids', '[]')
                            ->where('municipios_ids', '[]')
                            ->where('localidades_ids', '[]');
                    });
            })
            ->get();

        // Verificar cada votación para ver si el usuario excede el límite del censo
        foreach ($votacionesActivas as $votacion) {
            // Comparar fecha de creación del usuario con límite del censo
            if ($user->created_at && $votacion->limite_censo) {
                // Si el usuario fue creado DESPUÉS del límite del censo
                if ($user->created_at > $votacion->limite_censo) {
                    return [
                        'restricted' => true,
                        'message' => $votacion->mensaje_limite_censo ?: 
                            'Tu registro en el sistema es posterior a la fecha límite del censo electoral para esta votación. Por favor, contacta con el administrador si consideras que esto es un error.',
                        'votacion_titulo' => $votacion->titulo,
                        'limite_censo' => $votacion->limite_censo,
                        'user_created_at' => $user->created_at,
                    ];
                }
            }
        }

        return null;
    }
}