<?php

namespace App\Services\Core;

use App\Jobs\Core\SendVerificationCodesJob;
use App\Models\Core\User;
use App\Models\Core\UserVerificationRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserVerificationService
{
    protected OTPService $otpService;
    protected WhatsAppService $whatsappService;

    public function __construct(OTPService $otpService, WhatsAppService $whatsappService)
    {
        $this->otpService = $otpService;
        $this->whatsappService = $whatsappService;
    }

    /**
     * Busca un usuario por su documento de identidad
     */
    public function findUserByDocument(string $documento): ?User
    {
        // Usar caché para mejorar performance (5 minutos)
        $cacheKey = "user_doc_{$documento}";
        
        return Cache::remember($cacheKey, 300, function () use ($documento) {
            return User::where('documento_identidad', $documento)->first();
        });
    }

    /**
     * Inicia el proceso de verificación para un usuario
     */
    public function initiateVerification(string $documento, string $ipAddress = null, string $userAgent = null): ?UserVerificationRequest
    {
        $user = $this->findUserByDocument($documento);
        
        if (!$user) {
            return null;
        }

        // Verificar si ya existe una verificación activa reciente
        $existingRequest = UserVerificationRequest::where('documento_identidad', $documento)
            ->where('status', 'pending')
            ->where('created_at', '>=', Carbon::now()->subMinutes(15))
            ->first();

        if ($existingRequest && !$existingRequest->hasExpired()) {
            // Si ya existe y no ha expirado, retornar la existente
            return $existingRequest;
        }

        // Crear nueva solicitud de verificación con token de sesión único
        return DB::transaction(function () use ($documento, $user, $ipAddress, $userAgent) {
            // Generar token de sesión único y seguro
            $sessionToken = bin2hex(random_bytes(32));
            
            $verificationRequest = UserVerificationRequest::create([
                'documento_identidad' => $documento,
                'user_id' => $user->id,
                'session_token' => $sessionToken,
                'status' => 'pending',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            return $verificationRequest;
        });
    }

    /**
     * Envía códigos de verificación por email y WhatsApp
     */
    public function sendVerificationCodes(UserVerificationRequest $request): bool
    {
        if (!$request->user) {
            return false;
        }

        $user = $request->user;
        
        // Verificar límite de reenvíos
        if (!$request->canResendCodes()) {
            Log::warning('Límite de reenvío de códigos excedido', [
                'documento' => $request->documento_identidad,
                'ip' => $request->ip_address,
            ]);
            return false;
        }

        // Generar códigos únicos para cada canal
        $emailCode = UserVerificationRequest::generateUniqueCode();
        $whatsappCode = UserVerificationRequest::generateUniqueCode();

        // Actualizar la solicitud con los códigos
        $request->update([
            'verification_code_email' => $emailCode,
            'verification_code_whatsapp' => $user->telefono ? $whatsappCode : null,
            'email_sent_at' => now(),
            'whatsapp_sent_at' => $user->telefono ? now() : null,
        ]);

        // Enviar códigos de forma asíncrona
        dispatch(new SendVerificationCodesJob($request));

        return true;
    }

    /**
     * Valida un código de verificación
     */
    public function validateCode(UserVerificationRequest $request, string $code, string $channel): bool
    {
        // Verificar si el código ha expirado
        if ($request->hasExpired()) {
            $request->update(['status' => 'failed']);
            return false;
        }

        $isValid = false;

        switch ($channel) {
            case 'email':
                if ($request->verification_code_email === $code) {
                    $request->markEmailAsVerified();
                    $isValid = true;
                }
                break;
                
            case 'whatsapp':
                if ($request->verification_code_whatsapp === $code) {
                    $request->markWhatsappAsVerified();
                    $isValid = true;
                }
                break;
        }

        if (!$isValid) {
            Log::warning('Código de verificación inválido', [
                'request_id' => $request->id,
                'channel' => $channel,
                'ip' => request()->ip(),
            ]);
        }

        return $isValid;
    }

    /**
     * Valida que el token de sesión sea correcto para una solicitud de verificación
     */
    public function validateSessionToken(int $verificationId, ?string $sessionToken): ?UserVerificationRequest
    {
        if (!$sessionToken) {
            Log::warning('Intento de acceso sin token de sesión', [
                'verification_id' => $verificationId,
                'ip' => request()->ip(),
            ]);
            return null;
        }

        $verificationRequest = UserVerificationRequest::find($verificationId);
        
        if (!$verificationRequest) {
            return null;
        }

        // Verificar que el token coincida
        if ($verificationRequest->session_token !== $sessionToken) {
            Log::warning('Token de sesión inválido', [
                'verification_id' => $verificationId,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            return null;
        }

        return $verificationRequest;
    }

    /**
     * Verifica si un usuario puede proceder con la actualización de datos
     */
    public function canProceedToUpdate(UserVerificationRequest $request): bool
    {
        // El usuario puede proceder si:
        // 1. La verificación está completa
        // 2. Han pasado más de 10 segundos desde el envío y no ha recibido códigos
        
        if ($request->isFullyVerified()) {
            return true;
        }

        // Verificar si han pasado 10 segundos desde el envío
        $tenSecondsAgo = Carbon::now()->subSeconds(10);
        
        if ($request->email_sent_at && $request->email_sent_at <= $tenSecondsAgo) {
            // Si no ha verificado ningún código después de 10 segundos, permitir continuar
            if (!$request->isEmailVerified() && !$request->isWhatsappVerified()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtiene las estadísticas de verificación para un documento
     */
    public function getVerificationStats(string $documento): array
    {
        $requests = UserVerificationRequest::where('documento_identidad', $documento)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'total_attempts' => $requests->count(),
            'successful' => $requests->where('status', 'verified')->count(),
            'failed' => $requests->where('status', 'failed')->count(),
            'pending' => $requests->where('status', 'pending')->count(),
            'last_attempt' => $requests->first()?->created_at,
            'can_retry' => $requests->first()?->canResendCodes() ?? true,
        ];
    }

    /**
     * Limpia solicitudes de verificación antiguas (más de 24 horas)
     */
    public function cleanOldRequests(): int
    {
        return UserVerificationRequest::where('created_at', '<', Carbon::now()->subDay())
            ->delete();
    }

    /**
     * Envía el código de verificación por email (método directo)
     */
    public function sendEmailCode(UserVerificationRequest $request): bool
    {
        if (!$request->user || !$request->verification_code_email) {
            Log::warning('No se puede enviar email - faltan datos', [
                'has_user' => !!$request->user,
                'has_code' => !!$request->verification_code_email,
                'request_id' => $request->id
            ]);
            return false;
        }

        try {
            $user = $request->user;
            $code = $request->verification_code_email;
            
            Log::debug('Preparando envío de email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'code' => $code,
                'request_id' => $request->id
            ]);
            
            // Verificar si el mailer está configurado correctamente
            Log::debug('Configuración de Mail', [
                'mailer' => config('mail.default'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name')
            ]);
            
            // Enviar email directamente
            \Mail::to($user->email)->send(new \App\Mail\Core\UserVerificationMail($user->name, $code, 'email'));
            
            Log::info('Código de verificación enviado por email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'code' => $code,
                'request_id' => $request->id,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error enviando código de verificación por email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $request->id,
            ]);
            return false;
        }
    }

    /**
     * Envía el código de verificación por WhatsApp (método directo)
     */
    public function sendWhatsappCode(UserVerificationRequest $request): bool
    {
        if (!$request->user || !$request->user->telefono || !$request->verification_code_whatsapp) {
            Log::warning('No se puede enviar WhatsApp - faltan datos', [
                'has_user' => !!$request->user,
                'has_phone' => !!($request->user->telefono ?? null),
                'has_code' => !!$request->verification_code_whatsapp,
                'request_id' => $request->id
            ]);
            return false;
        }

        try {
            $user = $request->user;
            $code = $request->verification_code_whatsapp;
            
            Log::debug('Preparando envío de WhatsApp', [
                'user_id' => $user->id,
                'phone' => substr($user->telefono, 0, 5) . '***',
                'code' => $code,
                'request_id' => $request->id
            ]);
            
            $message = $this->buildWhatsappMessage($user->name, $code);
            
            $sent = $this->whatsappService->sendMessage($user->telefono, $message);
            
            if ($sent) {
                Log::info('Código de verificación enviado por WhatsApp', [
                    'user_id' => $user->id,
                    'request_id' => $request->id,
                ]);
            } else {
                Log::warning('WhatsApp no pudo enviar el mensaje', [
                    'user_id' => $user->id,
                    'request_id' => $request->id,
                ]);
            }
            
            return $sent;
        } catch (\Exception $e) {
            Log::error('Error enviando código de verificación por WhatsApp', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $request->id,
            ]);
            return false;
        }
    }

    /**
     * Construye el mensaje de WhatsApp para verificación
     */
    protected function buildWhatsappMessage(string $userName, string $code): string
    {
        return "Hola {$userName},\n\n" .
               "Tu código de verificación para confirmar tu registro es: *{$code}*\n\n" .
               "Este código es válido por 15 minutos.\n" .
               "Si no solicitaste este código, puedes ignorar este mensaje.\n\n" .
               "Sistema de Votaciones";
    }
}