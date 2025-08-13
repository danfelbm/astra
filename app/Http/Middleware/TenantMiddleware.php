<?php

namespace App\Http\Middleware;

use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    protected TenantService $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Intentar identificar el tenant por subdomain
        $host = $request->getHost();
        $subdomain = $this->extractSubdomain($host);
        
        if ($subdomain && $subdomain !== 'www') {
            $tenant = $this->tenantService->getTenantBySubdomain($subdomain);
            
            if ($tenant && $tenant->isActive()) {
                $this->tenantService->setCurrentTenant($tenant);
            } else {
                // Subdomain no válido o tenant inactivo
                abort(404, 'Organización no encontrada o inactiva.');
            }
        } else {
            // Si no hay subdomain, intentar obtener el tenant del usuario autenticado
            // o usar el tenant por defecto
            $tenant = $this->tenantService->getCurrentTenant();
            
            if (!$tenant) {
                // Si no hay tenant y es una ruta protegida, redirigir
                if (!$request->is('login') && !$request->is('api/*')) {
                    return redirect()->route('login')
                                   ->with('error', 'Debe seleccionar una organización.');
                }
            }
        }

        return $next($request);
    }

    /**
     * Extraer el subdomain del host
     */
    protected function extractSubdomain(string $host): ?string
    {
        // Ignorar dominios de Laravel Cloud
        if (str_contains($host, '.laravel.cloud')) {
            return null;
        }
        
        // Para desarrollo local con .test
        if (str_ends_with($host, '.test')) {
            $parts = explode('.', $host);
            if (count($parts) > 2) {
                return $parts[0];
            }
        }
        
        // Para producción con dominio real
        $parts = explode('.', $host);
        if (count($parts) > 2) {
            // Asumiendo formato: subdomain.domain.com
            return $parts[0];
        }
        
        return null;
    }
}