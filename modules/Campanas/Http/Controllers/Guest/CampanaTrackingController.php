<?php

namespace Modules\Campanas\Http\Controllers\Guest;

use Modules\Core\Http\Controllers\Base\GuestController;
use Modules\Campanas\Services\CampanaTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;

class CampanaTrackingController extends GuestController
{
    public function __construct(
        private CampanaTrackingService $trackingService
    ) {}

    /**
     * Registrar apertura de email mediante pixel de tracking
     */
    public function pixel(string $trackingId): Response
    {
        // Registrar la apertura
        $this->trackingService->trackOpen($trackingId, request());

        // Devolver un pixel transparente 1x1
        $pixel = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        
        return response($pixel, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Registrar click en enlace y redirigir
     */
    public function click(string $trackingId, string $encodedUrl): RedirectResponse
    {
        // Decodificar la URL
        $url = base64_decode($encodedUrl);
        
        // Validar que sea una URL válida
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            abort(404);
        }

        // Registrar el click
        $this->trackingService->trackClick($trackingId, $url, request());

        // Redirigir a la URL original
        return redirect()->away($url);
    }

    /**
     * Registrar descarga de archivo adjunto
     */
    public function download(string $trackingId, string $fileId): Response
    {
        // Registrar el evento de descarga
        $result = $this->trackingService->trackDownload($trackingId, $fileId, request());

        if (!$result['success']) {
            abort(404);
        }

        // Obtener el archivo y retornarlo
        $file = $result['file'];
        
        return response()->download(
            $file['path'],
            $file['name'],
            [
                'Content-Type' => $file['mime_type'],
            ]
        );
    }

    /**
     * Registrar desuscripción
     */
    public function unsubscribe(string $trackingId): Response
    {
        // Registrar la desuscripción
        $result = $this->trackingService->trackUnsubscribe($trackingId, request());

        if (!$result['success']) {
            return response()->view('errors.404', [], 404);
        }

        // Mostrar página de confirmación de desuscripción
        return response()->view('modules.campanas.unsubscribe', [
            'success' => true,
            'message' => 'Has sido desuscrito exitosamente de nuestras comunicaciones.',
        ]);
    }

    /**
     * Webhook para recibir eventos de proveedores de email
     */
    public function webhook(Request $request, string $provider): Response
    {
        // Validar el proveedor
        if (!in_array($provider, ['resend', 'sendgrid', 'mailgun'])) {
            abort(404);
        }

        // Verificar la firma del webhook según el proveedor
        $verified = $this->verifyWebhookSignature($request, $provider);
        
        if (!$verified) {
            return response('Unauthorized', 401);
        }

        // Procesar los eventos del webhook
        $result = $this->trackingService->processWebhookEvents($provider, $request->all());

        return response()->json([
            'success' => $result['success'],
            'processed' => $result['processed'] ?? 0,
        ]);
    }

    /**
     * Verificar firma del webhook según el proveedor
     */
    private function verifyWebhookSignature(Request $request, string $provider): bool
    {
        switch ($provider) {
            case 'resend':
                return $this->verifyResendSignature($request);
            case 'sendgrid':
                return $this->verifySendgridSignature($request);
            case 'mailgun':
                return $this->verifyMailgunSignature($request);
            default:
                return false;
        }
    }

    /**
     * Verificar firma de Resend
     */
    private function verifyResendSignature(Request $request): bool
    {
        $signature = $request->header('svix-signature');
        $timestamp = $request->header('svix-timestamp');
        $webhookSecret = config('services.resend.webhook_secret');

        if (!$signature || !$timestamp || !$webhookSecret) {
            return false;
        }

        // Construir el payload para verificar
        $payload = $timestamp . '.' . $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verificar firma de SendGrid
     */
    private function verifySendgridSignature(Request $request): bool
    {
        $signature = $request->header('X-Twilio-Email-Event-Webhook-Signature');
        $timestamp = $request->header('X-Twilio-Email-Event-Webhook-Timestamp');
        $webhookKey = config('services.sendgrid.webhook_key');

        if (!$signature || !$timestamp || !$webhookKey) {
            return false;
        }

        // Construir el payload para verificar
        $payload = $webhookKey . $timestamp . $request->getContent();
        $expectedSignature = base64_encode(hash('sha256', $payload, true));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verificar firma de Mailgun
     */
    private function verifyMailgunSignature(Request $request): bool
    {
        $signature = $request->input('signature.signature');
        $timestamp = $request->input('signature.timestamp');
        $token = $request->input('signature.token');
        $apiKey = config('services.mailgun.webhook_key');

        if (!$signature || !$timestamp || !$token || !$apiKey) {
            return false;
        }

        // Verificar que el timestamp no sea muy viejo (15 minutos)
        if (abs(time() - $timestamp) > 900) {
            return false;
        }

        // Construir el payload para verificar
        $payload = $timestamp . $token;
        $expectedSignature = hash_hmac('sha256', $payload, $apiKey);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Vista web del email para navegador
     */
    public function webView(string $trackingId): Response
    {
        $result = $this->trackingService->getWebViewContent($trackingId);

        if (!$result['success']) {
            abort(404);
        }

        // Registrar que se vio la versión web
        $this->trackingService->trackWebView($trackingId, request());

        return response($result['content'], 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}