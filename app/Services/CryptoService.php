<?php

namespace App\Services;

use Exception;

class CryptoService
{
    private static $publicKeyPath = 'keys/public.pem';
    private static $privateKeyPath = 'keys/private.pem';

    /**
     * Genera un par de claves RSA para el sistema
     */
    public static function generateKeyPair(): array
    {
        $config = [
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        // Generar par de claves
        $resource = openssl_pkey_new($config);
        
        if (!$resource) {
            throw new Exception('Error al generar el par de claves: ' . openssl_error_string());
        }

        // Extraer clave privada
        openssl_pkey_export($resource, $privateKey);

        // Extraer clave pública
        $publicKeyDetails = openssl_pkey_get_details($resource);
        $publicKey = $publicKeyDetails["key"];

        return [
            'private_key' => $privateKey,
            'public_key' => $publicKey
        ];
    }

    /**
     * Almacena las claves en el storage
     */
    public static function storeKeys(string $privateKey, string $publicKey): void
    {
        $keysDir = storage_path('app/keys');
        
        if (!file_exists($keysDir)) {
            mkdir($keysDir, 0755, true);
        }

        // Almacenar claves
        file_put_contents(storage_path('app/' . self::$privateKeyPath), $privateKey);
        file_put_contents(storage_path('app/' . self::$publicKeyPath), $publicKey);

        // Asegurar permisos restrictivos para la clave privada
        chmod(storage_path('app/' . self::$privateKeyPath), 0600);
        chmod(storage_path('app/' . self::$publicKeyPath), 0644);
    }

    /**
     * Obtiene la clave privada
     */
    public static function getPrivateKey(): string
    {
        $path = storage_path('app/' . self::$privateKeyPath);
        
        if (!file_exists($path)) {
            throw new Exception('Clave privada no encontrada. Ejecuta php artisan votaciones:generate-keys');
        }

        return file_get_contents($path);
    }

    /**
     * Obtiene la clave pública
     */
    public static function getPublicKey(): string
    {
        $path = storage_path('app/' . self::$publicKeyPath);
        
        if (!file_exists($path)) {
            throw new Exception('Clave pública no encontrada. Ejecuta php artisan votaciones:generate-keys');
        }

        return file_get_contents($path);
    }

    /**
     * Firma datos con la clave privada del servidor
     */
    public static function signData(array $data): string
    {
        $dataString = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        $privateKey = self::getPrivateKey();
        $privateKeyResource = openssl_pkey_get_private($privateKey);
        
        if (!$privateKeyResource) {
            $error = openssl_error_string();
            throw new Exception('Error al cargar la clave privada: ' . $error);
        }

        $signature = '';
        $success = openssl_sign($dataString, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);

        if (!$success) {
            $error = openssl_error_string();
            throw new Exception('Error al firmar los datos: ' . $error);
        }

        return base64_encode($signature);
    }

    /**
     * Verifica la firma de los datos con la clave pública
     */
    public static function verifySignature(array $data, string $signature): bool
    {
        try {
            $dataString = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $publicKey = self::getPublicKey();

            $publicKeyResource = openssl_pkey_get_public($publicKey);
            if (!$publicKeyResource) {
                return false;
            }

            $binarySignature = base64_decode($signature);
            if ($binarySignature === false) {
                return false;
            }

            $result = openssl_verify($dataString, $binarySignature, $publicKeyResource, OPENSSL_ALGO_SHA256);
            
            return $result === 1;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Verifica si las claves existen
     */
    public static function keysExist(): bool
    {
        return file_exists(storage_path('app/' . self::$privateKeyPath)) && 
               file_exists(storage_path('app/' . self::$publicKeyPath));
    }

    /**
     * Genera hash SHA256 de los datos
     */
    public static function hashData(array $data): string
    {
        $dataString = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return hash('sha256', $dataString);
    }
}