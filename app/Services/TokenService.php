<?php

namespace App\Services;

use Exception;

class TokenService
{
    /**
     * Genera un token firmado digitalmente que incluye las respuestas del formulario
     */
    public static function generateSignedToken(int $votacionId, array $respuestas, string $timestamp = null): string
    {
        $timestamp = $timestamp ?: now()->toISOString();
        
        // Datos del voto
        $voteData = [
            'votacion_id' => $votacionId,
            'respuestas' => $respuestas,
            'timestamp' => $timestamp,
            'salt' => bin2hex(random_bytes(16))
        ];

        // Generar hash de los datos
        $voteData['vote_hash'] = CryptoService::hashData($voteData);

        // Crear estructura del token
        $signature = CryptoService::signData($voteData);
        
        $tokenStructure = [
            'vote_data' => $voteData,
            'server_signature' => $signature
        ];

        // Codificar en base64url para URLs seguras
        return self::base64urlEncode(json_encode($tokenStructure));
    }

    /**
     * Decodifica y verifica un token firmado
     */
    public static function verifyToken(string $token): array
    {
        try {
            // Decodificar el token
            $decodedJson = self::base64urlDecode($token);
            $tokenData = json_decode($decodedJson, true);

            if (!$tokenData || !isset($tokenData['vote_data'], $tokenData['server_signature'])) {
                throw new Exception('Formato de token inválido');
            }

            $voteData = $tokenData['vote_data'];
            $signature = $tokenData['server_signature'];

            // Verificar la firma
            $isValid = CryptoService::verifySignature($voteData, $signature);

            // Verificar que el hash coincida
            // Primero, necesitamos crear una copia de voteData sin el vote_hash para calcular el hash esperado
            $voteDataForHash = $voteData;
            unset($voteDataForHash['vote_hash']); // Remover el hash antes de calcular
            
            $expectedHash = CryptoService::hashData($voteDataForHash);
            $storedHash = $voteData['vote_hash'] ?? '';
            $hashMatches = hash_equals($expectedHash, $storedHash);

            return [
                'is_valid' => $isValid && $hashMatches,
                'vote_data' => $voteData,
                'signature_valid' => $isValid,
                'hash_valid' => $hashMatches,
                'error' => null
            ];

        } catch (Exception $e) {
            return [
                'is_valid' => false,
                'vote_data' => null,
                'signature_valid' => false,
                'hash_valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verifica si un token tiene el formato básico correcto
     */
    public static function isValidTokenFormat(string $token): bool
    {
        // Los nuevos tokens son base64url, los antiguos son hex de 64 caracteres
        return self::isBase64Url($token) || preg_match('/^[a-f0-9]{64}$/', $token) === 1;
    }

    /**
     * Verifica si un string es base64url válido
     */
    private static function isBase64Url(string $string): bool
    {
        return preg_match('/^[A-Za-z0-9_-]+$/', $string) === 1;
    }

    /**
     * Codifica en base64url (URL-safe)
     */
    private static function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodifica de base64url
     */
    private static function base64urlDecode(string $data): string
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    /**
     * Genera un token único simple (backward compatibility)
     * @deprecated Usar generateSignedToken() en su lugar
     */
    public static function generateUniqueToken(): string
    {
        $timestamp = microtime(true);
        $randomString = \Illuminate\Support\Str::random(32);
        
        $tokenData = $timestamp . $randomString . config('app.key');
        
        return hash('sha256', $tokenData);
    }
}