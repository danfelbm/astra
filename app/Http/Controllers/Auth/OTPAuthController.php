<?php

namespace App\Http\Controllers\Auth;

use App\Enums\LoginType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GlobalSettingsService;
use App\Services\OTPService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OTPAuthController extends Controller
{
    protected OTPService $otpService;

    public function __construct(OTPService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Mostrar página de login OTP
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/LoginOTP', [
            'status' => $request->session()->get('status'),
            'authConfig' => GlobalSettingsService::getAuthConfig(),
            'censoredEmail' => $request->session()->get('censored_email'),
            'otpCredential' => $request->session()->get('otp_credential'),
            'otpChannel' => $request->session()->get('otp_channel', 'email'),
            'whatsappEnabled' => config('services.whatsapp.enabled', false),
        ]);
    }

    /**
     * Censurar email para mostrar de forma segura
     */
    protected function censorEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }
        
        $localPart = $parts[0];
        $domainParts = explode('.', $parts[1]);
        
        // Censurar parte local (mostrar 2 al inicio, 1 al final)
        $localLength = strlen($localPart);
        if ($localLength > 3) {
            $localCensored = substr($localPart, 0, 2) . str_repeat('*', $localLength - 3) . substr($localPart, -1);
        } else {
            $localCensored = $localPart;
        }
        
        // Censurar dominio (mostrar 1 al inicio, 1 al final antes del punto)
        $domain = $domainParts[0];
        $domainLength = strlen($domain);
        if ($domainLength > 2) {
            $domainCensored = substr($domain, 0, 1) . str_repeat('*', $domainLength - 2) . substr($domain, -1);
        } else {
            $domainCensored = $domain;
        }
        
        // Mantener la extensión sin censurar
        $extension = count($domainParts) > 1 ? '.' . implode('.', array_slice($domainParts, 1)) : '';
        
        return $localCensored . '@' . $domainCensored . $extension;
    }

    /**
     * Solicitar código OTP por email o documento
     */
    public function requestOTP(Request $request): RedirectResponse
    {
        $loginType = GlobalSettingsService::getLoginType();
        
        // Determinar las reglas de validación según el tipo de login
        $rules = [];
        $messages = [];
        $fieldName = 'credential'; // Campo genérico que viene del frontend
        
        if ($loginType === LoginType::EMAIL) {
            $rules[$fieldName] = 'required|email';
            $messages = [
                'credential.required' => 'El correo electrónico es requerido.',
                'credential.email' => 'El correo electrónico debe ser una dirección válida.',
            ];
        } else {
            $rules[$fieldName] = 'required|string|regex:/^[0-9]+$/';
            $messages = [
                'credential.required' => 'El documento de identidad es requerido.',
                'credential.string' => 'El documento debe ser un valor válido.',
                'credential.regex' => 'El documento solo debe contener números.',
            ];
        }
        
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $credential = $request->input('credential');
        
        // Buscar usuario según el tipo de login
        if ($loginType === LoginType::EMAIL) {
            $user = User::where('email', $credential)
                ->where('activo', true)
                ->first();
            $email = $credential;
        } else {
            // Login por documento
            $user = User::where('documento_identidad', $credential)
                ->where('activo', true)
                ->first();
            
            if ($user) {
                $email = $user->email;
                
                // Verificar que el usuario tenga email configurado
                if (empty($email)) {
                    throw ValidationException::withMessages([
                        'credential' => ['El usuario no tiene un correo electrónico configurado. Contacte al administrador.'],
                    ]);
                }
            }
        }

        if (!$user) {
            $errorMessage = $loginType === LoginType::EMAIL 
                ? 'El usuario no existe o no está autorizado para votar.'
                : 'No se encontró un usuario con ese documento de identidad.';
                
            throw ValidationException::withMessages([
                'credential' => [$errorMessage],
            ]);
        }

        // Siempre generar nuevo código (invalidará el anterior automáticamente)

        // Obtener número de WhatsApp del usuario si existe
        $phone = $user->getWhatsAppNumber();
        
        // Determinar el canal que se usará
        $channel = $this->determineOTPChannel($email, $phone);
        
        // Generar nuevo código OTP (esto también envía el email/WhatsApp)
        $codigo = $this->otpService->generateOTP($email, $phone);

        // Censurar el email para mostrar de forma segura
        $censoredEmail = $this->censorEmail($email);
        
        // Guardar en sesión para mostrar en la vista
        $request->session()->put('censored_email', $censoredEmail);
        $request->session()->put('otp_credential', $credential);
        $request->session()->put('otp_channel', $channel);
        
        // Mensaje de éxito según el canal
        $successMessage = $this->getSuccessMessage($channel);
        
        // Para Inertia.js, redirigir de vuelta con mensaje de éxito
        return back()->with('success', $successMessage);
    }

    /**
     * Verificar código OTP y autenticar usuario
     */
    public function verifyOTP(Request $request): RedirectResponse
    {
        $loginType = GlobalSettingsService::getLoginType();
        
        // Determinar las reglas de validación
        $rules = [
            'credential' => $loginType === LoginType::EMAIL ? 'required|email' : 'required|string|regex:/^[0-9]+$/',
            'otp_code' => 'required|string|min:6|max:6',
        ];
        
        $messages = [
            'credential.required' => $loginType === LoginType::EMAIL ? 'El correo electrónico es requerido.' : 'El documento es requerido.',
            'credential.email' => 'El correo electrónico debe ser una dirección válida.',
            'credential.regex' => 'El documento solo debe contener números.',
            'otp_code.required' => 'El código OTP es requerido.',
            'otp_code.string' => 'El código OTP debe ser una cadena de texto.',
            'otp_code.min' => 'El código OTP debe tener exactamente 6 dígitos.',
            'otp_code.max' => 'El código OTP debe tener exactamente 6 dígitos.',
        ];
        
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $credential = $request->input('credential');
        $otpCode = $request->input('otp_code');
        
        // Determinar el email según el tipo de login
        if ($loginType === LoginType::EMAIL) {
            $email = $credential;
            $user = User::where('email', $email)
                ->where('activo', true)
                ->first();
        } else {
            // Login por documento
            $user = User::where('documento_identidad', $credential)
                ->where('activo', true)
                ->first();
            
            if ($user) {
                $email = $user->email;
            } else {
                throw ValidationException::withMessages([
                    'credential' => ['Usuario no encontrado o inactivo.'],
                ]);
            }
        }

        // Validar código OTP
        if (!$this->otpService->validateOTP($email, $otpCode)) {
            throw ValidationException::withMessages([
                'otp_code' => ['El código OTP es inválido o ha expirado.'],
            ]);
        }

        if (!$user) {
            throw ValidationException::withMessages([
                'credential' => ['Usuario no encontrado o inactivo.'],
            ]);
        }

        // Crear token Sanctum para la sesión
        $token = $user->createToken('auth_token')->plainTextToken;

        // Login del usuario
        Auth::login($user);

        // Limpiar datos temporales del OTP de la sesión
        $request->session()->forget(['censored_email', 'otp_credential']);
        
        // Regenerar sesión por seguridad
        $request->session()->regenerate();

        // Redirigir según tipo de usuario
        $redirectRoute = ($user->isAdmin() || $user->isSuperAdmin()) ? 'admin.dashboard' : 'dashboard';
        return redirect()->route($redirectRoute)->with('success', 'Autenticación exitosa.');
    }

    /**
     * Reenviar código OTP
     */
    public function resendOTP(Request $request): RedirectResponse
    {
        $loginType = GlobalSettingsService::getLoginType();
        
        // Validación según tipo de login
        $rules = [
            'credential' => $loginType === LoginType::EMAIL ? 'required|email' : 'required|string|regex:/^[0-9]+$/',
        ];
        
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $credential = $request->input('credential');
        
        // Buscar usuario según tipo de login
        if ($loginType === LoginType::EMAIL) {
            $user = User::where('email', $credential)
                ->where('activo', true)
                ->first();
            $email = $credential;
        } else {
            $user = User::where('documento_identidad', $credential)
                ->where('activo', true)
                ->first();
            
            if ($user) {
                $email = $user->email;
            }
        }

        if (!$user) {
            throw ValidationException::withMessages([
                'credential' => ['Usuario no encontrado o inactivo.'],
            ]);
        }

        // Obtener número de WhatsApp del usuario si existe
        $phone = $user->getWhatsAppNumber();
        
        // Determinar el canal que se usará
        $channel = $this->determineOTPChannel($email, $phone);
        
        // Generar nuevo código (esto invalidará el anterior y enviará email/WhatsApp)
        $codigo = $this->otpService->generateOTP($email, $phone);
        
        // Censurar el email para mostrar de forma segura
        $censoredEmail = $this->censorEmail($email);
        
        // Actualizar en sesión
        $request->session()->put('censored_email', $censoredEmail);
        $request->session()->put('otp_credential', $credential);
        $request->session()->put('otp_channel', $channel);
        
        // Mensaje de éxito según el canal
        $successMessage = 'Nuevo ' . $this->getSuccessMessage($channel);

        return back()->with('success', $successMessage);
    }

    /**
     * Obtener configuración de login
     * Endpoint público para el frontend
     */
    public function getLoginConfig(): JsonResponse
    {
        return response()->json([
            'config' => GlobalSettingsService::getAuthConfig(),
        ]);
    }

    /**
     * Cerrar sesión
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Revocar todos los tokens del usuario
        if ($user = Auth::user()) {
            $user->tokens()->delete();
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Sesión cerrada exitosamente.');
    }

    /**
     * Determinar el canal de OTP basado en configuración y disponibilidad
     */
    private function determineOTPChannel(string $email, ?string $phone): string
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
     * Obtener mensaje de éxito según el canal usado
     */
    private function getSuccessMessage(string $channel): string
    {
        switch ($channel) {
            case 'whatsapp':
                return 'Código OTP enviado a tu WhatsApp.';
            
            case 'both':
                return 'Código OTP enviado a tu correo electrónico y WhatsApp.';
            
            case 'email':
            default:
                return 'Código OTP enviado a tu correo electrónico.';
        }
    }
}