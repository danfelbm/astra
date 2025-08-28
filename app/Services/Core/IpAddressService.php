<?php

namespace App\Services\Core;

use Illuminate\Http\Request;

class IpAddressService
{
    /**
     * Obtener la dirección IP real del cliente, considerando proxies y load balancers
     * 
     * @param Request|null $request
     * @return string
     */
    public static function getRealIp(?Request $request = null): string
    {
        $request = $request ?: request();
        
        // Si no hay request disponible, retornar IP por defecto
        if (!$request) {
            return '127.0.0.1';
        }
        
        // Laravel ya maneja la lógica de trusted proxies con el middleware configurado
        // Esto funcionará correctamente si TrustProxies está configurado
        $ip = $request->ip();
        
        // Validación adicional para IPs privadas del load balancer
        // Si la IP es privada, intentar obtener desde headers específicos
        if (static::isPrivateIp($ip)) {
            // Prioridad de headers a verificar
            $headers = [
                'HTTP_CF_CONNECTING_IP',     // Cloudflare
                'HTTP_X_REAL_IP',             // Nginx proxy
                'HTTP_X_FORWARDED_FOR',       // Estándar de proxy
                'HTTP_X_FORWARDED',           // Variante
                'HTTP_X_CLUSTER_CLIENT_IP',   // Rackspace LB, Riverbed Stingray
                'HTTP_FORWARDED_FOR',         // Variante
                'HTTP_FORWARDED',             // RFC 7239
                'HTTP_CLIENT_IP',             // Algunos proxies
            ];
            
            foreach ($headers as $header) {
                if (!empty($_SERVER[$header])) {
                    // X-Forwarded-For puede contener múltiples IPs separadas por comas
                    // La primera es la IP original del cliente
                    if (str_contains($_SERVER[$header], ',')) {
                        $ips = explode(',', $_SERVER[$header]);
                        $clientIp = trim($ips[0]);
                    } else {
                        $clientIp = trim($_SERVER[$header]);
                    }
                    
                    // Validar que es una IP válida
                    if (filter_var($clientIp, FILTER_VALIDATE_IP)) {
                        // Si encontramos una IP pública, usarla
                        if (!static::isPrivateIp($clientIp)) {
                            return $clientIp;
                        }
                    }
                }
            }
        }
        
        return $ip;
    }
    
    /**
     * Verificar si una IP es privada o reservada
     * 
     * @param string $ip
     * @return bool
     */
    private static function isPrivateIp(string $ip): bool
    {
        // Rangos de IPs privadas según RFC 1918 y otras reservadas
        $privateRanges = [
            '10.0.0.0|10.255.255.255',      // Clase A privada
            '172.16.0.0|172.31.255.255',    // Clase B privada
            '192.168.0.0|192.168.255.255',  // Clase C privada
            '127.0.0.0|127.255.255.255',    // Loopback
            '169.254.0.0|169.254.255.255',  // Link-local
            '::1|::1',                       // IPv6 loopback
            'fc00::|fdff:ffff:ffff:ffff:ffff:ffff:ffff:ffff', // IPv6 privada
        ];
        
        $longIp = ip2long($ip);
        
        // Si es IPv6, usar filter_var para validación
        if ($longIp === false) {
            return !filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
        }
        
        // Verificar IPv4 contra rangos privados
        foreach ($privateRanges as $range) {
            list($rangeStart, $rangeEnd) = explode('|', $range);
            $rangeStart = ip2long($rangeStart);
            $rangeEnd = ip2long($rangeEnd);
            
            if ($rangeStart !== false && $rangeEnd !== false) {
                if ($longIp >= $rangeStart && $longIp <= $rangeEnd) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Obtener información adicional sobre la conexión
     * 
     * @param Request|null $request
     * @return array
     */
    public static function getConnectionInfo(?Request $request = null): array
    {
        $request = $request ?: request();
        
        return [
            'ip' => static::getRealIp($request),
            'user_agent' => $request ? $request->userAgent() : null,
            'referrer' => $request ? $request->header('referer') : null,
            'forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            'real_ip_header' => $_SERVER['HTTP_X_REAL_IP'] ?? null,
            'cf_ip' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
            'is_secure' => $request ? $request->secure() : false,
            'protocol' => $request ? $request->getScheme() : 'http',
        ];
    }
}