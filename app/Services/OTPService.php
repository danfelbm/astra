<?php

namespace App\Services;

use App\Jobs\SendOTPEmailJob;
use App\Mail\OTPCodeMail;
use App\Models\OTP;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OTPService
{
    /**
     * Generar un código OTP de 6 dígitos para un email
     */
    public function generateOTP(string $email): string
    {
        // Limpiar códigos expirados
        $this->cleanExpiredOTPs();

        // Invalidar códigos OTP previos para el mismo email
        OTP::where('email', $email)->update(['usado' => true]);

        // Generar nuevo código de 6 dígitos
        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Crear registro OTP con expiración de 10 minutos
        OTP::create([
            'email' => $email,
            'codigo' => $codigo,
            'expira_en' => Carbon::now()->addMinutes(10),
            'usado' => false,
        ]);


        // Enviar email con código OTP
        $this->sendOTPEmail($email, $codigo);

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
            $expirationMinutes = config('services.otp.expiration_minutes', 10);

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
        ];
    }
}