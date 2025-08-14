<?php

namespace App\Services;

use App\Jobs\SendOTPEmailJob;
use App\Jobs\SendOTPWhatsAppJob;
use App\Mail\OTPCodeMail;
use App\Models\OTP;
use App\Models\User;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OTPService
{
    /**
     * Generar un código OTP de 6 dígitos para un email
     * @param string $email Email del usuario
     * @param string|null $phone Número de teléfono opcional para WhatsApp
     */
    public function generateOTP(string $email, ?string $phone = null): string
    {
        // Limpiar códigos expirados
        $this->cleanExpiredOTPs();

        // Invalidar códigos OTP previos para el mismo email
        OTP::where('email', $email)->update(['usado' => true]);

        // Generar nuevo código de 6 dígitos
        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Determinar canal de envío
        $channel = $this->determineChannel($email, $phone);
        
        // Crear registro OTP con expiración configurada
        $expirationMinutes = (int) config('services.otp.expiration_minutes', 10);
        $otp = OTP::create([
            'email' => $email,
            'codigo' => $codigo,
            'expira_en' => Carbon::now()->addMinutes($expirationMinutes),
            'usado' => false,
            'canal_enviado' => $channel,
            'telefono_destino' => $phone,
        ]);

        // Enviar OTP según el canal determinado
        $this->sendOTP($email, $phone, $codigo, $channel);

        return $codigo;
    }

    /**
     * Validar un código OTP para un email específico
     */
    public function validateOTP(string $email, string $codigo): bool
    {
        $otp = OTP::where('email', $email)
            ->where('codigo', $codigo)
            ->vigentes()
            ->first();

        if (!$otp) {
            Log::warning('Intento de validación OTP fallido', [
                'email' => $email,
                'codigo' => $codigo,
                'ip' => request()->ip(),
            ]);
            return false;
        }

        // Marcar OTP como usado
        $otp->markAsUsed();


        return true;
    }

    /**
     * Verificar si un email tiene un OTP válido pendiente
     */
    public function hasValidOTP(string $email): bool
    {
        return OTP::where('email', $email)
            ->vigentes()
            ->exists();
    }

    /**
     * Obtener tiempo restante de expiración del OTP en minutos
     */
    public function getExpirationTime(string $email): ?int
    {
        $otp = OTP::where('email', $email)
            ->vigentes()
            ->first();

        if (!$otp) {
            return null;
        }

        return $otp->expira_en->diffInMinutes(Carbon::now());
    }

    /**
     * Limpiar códigos OTP expirados automáticamente
     */
    public function cleanExpiredOTPs(): void
    {
        $deletedCount = OTP::where('expira_en', '<', Carbon::now())
            ->orWhere('usado', true)
            ->delete();

    }

    /**
     * Enviar email con código OTP
     */
    private function sendOTPEmail(string $email, string $codigo): void
    {
        try {
            // Obtener datos del usuario
            $user = User::where('email', $email)->first();
            $userName = $user ? $user->name : 'Usuario';
            $expirationMinutes = (int) config('services.otp.expiration_minutes', 10);

            // Determinar si enviar inmediatamente o usar cola
            $sendImmediately = config('services.otp.send_immediately', true);
            
            if ($sendImmediately) {
                // Envío inmediato (síncrono)
                $mail = new OTPCodeMail($codigo, $userName, $expirationMinutes);
                Mail::to($email)->send($mail);
                Log::info("OTP enviado inmediatamente a {$email}");
            } else {
                // Envío mediante cola (asíncrono) usando Job
                SendOTPEmailJob::dispatch($email, $codigo, $userName, $expirationMinutes);
                Log::info("OTP encolado para envío a {$email}");
            }

        } catch (\Exception $e) {
            Log::error("Error enviando email OTP a {$email}: " . $e->getMessage());
            
            // En caso de error con envío inmediato, intentar con cola como fallback
            if (config('services.otp.send_immediately', true)) {
                try {
                    Log::info("Intentando envío mediante cola como fallback para {$email}");
                    $user = User::where('email', $email)->first();
                    $userName = $user ? $user->name : 'Usuario';
                    SendOTPEmailJob::dispatch($email, $codigo, $userName, 10);
                } catch (\Exception $fallbackError) {
                    Log::error("Fallback también falló: " . $fallbackError->getMessage());
                }
            }
            
            // No propagar el error para no bloquear la generación del OTP
            // El usuario puede solicitar reenvío si no recibe el email
        }
    }

    /**
     * Obtener estadísticas de OTPs para monitoreo
     */
    public function getStats(): array
    {
        return [
            'total_activos' => OTP::vigentes()->count(),
            'total_expirados' => OTP::where('expira_en', '<', Carbon::now())->count(),
            'total_usados' => OTP::where('usado', true)->count(),
            'por_canal' => [
                'email' => OTP::where('canal_enviado', 'email')->count(),
                'whatsapp' => OTP::where('canal_enviado', 'whatsapp')->count(),
                'both' => OTP::where('canal_enviado', 'both')->count(),
            ],
        ];
    }

    /**
     * Determinar canal de envío basado en configuración y disponibilidad
     */
    private function determineChannel(string $email, ?string $phone): string
    {
        $configChannel = config('services.otp.channel', 'email');
        $whatsappEnabled = config('services.whatsapp.enabled', false);
        
        // Si WhatsApp no está habilitado, usar email
        if (!$whatsappEnabled) {
            return 'email';
        }
        
        // Si no hay teléfono, usar email
        if (empty($phone)) {
            return 'email';
        }
        
        // Retornar el canal configurado
        return $configChannel;
    }

    /**
     * Enviar OTP según el canal especificado
     */
    private function sendOTP(string $email, ?string $phone, string $codigo, string $channel): void
    {
        switch ($channel) {
            case 'whatsapp':
                $this->sendOTPWhatsApp($phone, $codigo, $email);
                break;
            
            case 'both':
                // Enviar por ambos canales
                $this->sendOTPEmail($email, $codigo);
                $this->sendOTPWhatsApp($phone, $codigo, $email);
                break;
            
            case 'email':
            default:
                $this->sendOTPEmail($email, $codigo);
                break;
        }
    }

    /**
     * Enviar OTP por WhatsApp
     */
    private function sendOTPWhatsApp(?string $phone, string $codigo, string $email): void
    {
        if (empty($phone)) {
            Log::warning("No se puede enviar OTP por WhatsApp sin número de teléfono", ['email' => $email]);
            return;
        }

        try {
            // Obtener datos del usuario
            $user = User::where('email', $email)->first();
            $userName = $user ? $user->name : 'Usuario';
            $expirationMinutes = (int) config('services.otp.expiration_minutes', 10);

            // Determinar si enviar inmediatamente o usar cola
            $sendImmediately = config('services.otp.send_immediately', true);
            
            if ($sendImmediately) {
                // Envío inmediato (síncrono)
                $whatsappService = new WhatsAppService();
                $message = $whatsappService->getOTPMessageTemplate($codigo, $userName, $expirationMinutes);
                $sent = $whatsappService->sendMessage($phone, $message);
                
                if ($sent) {
                    Log::info("OTP enviado inmediatamente por WhatsApp a {$phone}");
                    
                    // Actualizar timestamp de envío
                    OTP::where('email', $email)
                        ->where('codigo', $codigo)
                        ->update(['whatsapp_sent_at' => Carbon::now()]);
                } else {
                    Log::error("Fallo al enviar OTP por WhatsApp a {$phone}");
                    
                    // Fallback a email si está configurado
                    if (config('services.otp.channel') === 'whatsapp') {
                        Log::info("Intentando fallback a email para {$email}");
                        $this->sendOTPEmail($email, $codigo);
                    }
                }
            } else {
                // Envío mediante cola (asíncrono) usando Job
                SendOTPWhatsAppJob::dispatch($phone, $codigo, $userName, $expirationMinutes);
                Log::info("OTP encolado para envío por WhatsApp a {$phone}");
            }

        } catch (\Exception $e) {
            Log::error("Error enviando OTP por WhatsApp a {$phone}: " . $e->getMessage());
            
            // En caso de error, intentar con email como fallback si el canal principal es WhatsApp
            if (config('services.otp.channel') === 'whatsapp') {
                try {
                    Log::info("Intentando envío por email como fallback para {$email}");
                    $this->sendOTPEmail($email, $codigo);
                } catch (\Exception $fallbackError) {
                    Log::error("Fallback de email también falló: " . $fallbackError->getMessage());
                }
            }
        }
    }
}