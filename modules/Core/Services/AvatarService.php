<?php

namespace Modules\Core\Services;

use Modules\Core\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AvatarService
{
    /**
     * Tamaño máximo permitido para avatares (5MB)
     */
    const MAX_SIZE = 5 * 1024 * 1024;
    
    /**
     * Dimensiones del avatar
     */
    const AVATAR_SIZE = 256;
    
    /**
     * Tipos MIME permitidos
     */
    const ALLOWED_MIMES = [
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/webp'
    ];
    
    /**
     * Subir y procesar un avatar para un usuario
     *
     * @param User $user
     * @param UploadedFile $file
     * @return string Ruta del archivo guardado
     * @throws \Exception
     */
    public function uploadAvatar(User $user, UploadedFile $file): string
    {
        // Validar el archivo
        $this->validateAvatar($file);
        
        // Eliminar avatar anterior si existe
        if ($user->avatar) {
            $this->deleteAvatar($user);
        }
        
        // Generar nombre único para el archivo
        $filename = $this->generateFilename($file);
        
        // Definir la ruta de almacenamiento
        $path = "avatars/{$user->id}/{$filename}";
        
        // Crear instancia del ImageManager con driver GD
        $manager = new ImageManager(new Driver());
        
        // Procesar y optimizar la imagen
        $image = $manager->read($file->getRealPath());
        
        // Redimensionar y recortar al cuadrado
        $image->cover(self::AVATAR_SIZE, self::AVATAR_SIZE);
        
        // Guardar la imagen optimizada con calidad del 85%
        $imageContent = (string) $image->toJpeg(quality: 85);
        
        // Almacenar en el disco público
        Storage::disk('public')->put($path, $imageContent);
        
        // Actualizar el campo avatar del usuario
        $user->update(['avatar' => $path]);
        
        return $path;
    }
    
    /**
     * Eliminar el avatar de un usuario
     *
     * @param User $user
     * @return bool
     */
    public function deleteAvatar(User $user): bool
    {
        if (!$user->avatar) {
            return false;
        }
        
        // Eliminar el archivo del storage
        if (Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }
        
        // Limpiar el campo avatar del usuario
        $user->update(['avatar' => null]);
        
        return true;
    }
    
    /**
     * Obtener la URL del avatar de un usuario
     *
     * @param User $user
     * @return string
     */
    public function getAvatarUrl(User $user): string
    {
        // Si tiene avatar personalizado, devolver su URL
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            // Asegurar que la URL sea absoluta con el prefijo /storage/
            $url = Storage::url($user->avatar);
            
            // Si la URL no empieza con /storage/, agregarla
            if (!str_starts_with($url, '/storage/')) {
                $url = '/storage/' . $user->avatar;
            }
            
            // Asegurar que la URL empiece con / para que sea absoluta
            return str_starts_with($url, '/') ? $url : '/' . $url;
        }
        
        // Generar URL de UI Avatars como fallback
        return $this->generateUIAvatarUrl($user);
    }
    
    /**
     * Generar URL de UI Avatars
     *
     * @param User $user
     * @return string
     */
    protected function generateUIAvatarUrl(User $user): string
    {
        $name = urlencode($user->name ?? 'Usuario');
        // Usar email si existe, sino usar el name, y si tampoco existe usar un string por defecto
        $stringForColor = $user->email ?? $user->name ?? 'default-user-' . ($user->id ?? '0');
        $backgroundColor = $this->hashStringToColor($stringForColor);

        return "https://ui-avatars.com/api/?name={$name}&background={$backgroundColor}&color=fff&size=256&bold=true&format=svg";
    }
    
    /**
     * Generar color determinístico desde string
     *
     * @param string|null $str
     * @return string Color hexadecimal sin #
     */
    protected function hashStringToColor(?string $str): string
    {
        // Si el string es null o vacío, usar un valor por defecto
        if (empty($str)) {
            $str = 'default-color';
        }

        $hash = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            $hash = ord($str[$i]) + (($hash << 5) - $hash);
        }

        $hue = abs($hash % 360);
        $saturation = 70;
        $lightness = 45;

        return $this->hslToHex($hue, $saturation, $lightness);
    }
    
    /**
     * Convertir HSL a hexadecimal
     *
     * @param int $h Hue (0-360)
     * @param int $s Saturation (0-100)
     * @param int $l Lightness (0-100)
     * @return string
     */
    protected function hslToHex(int $h, int $s, int $l): string
    {
        $l /= 100;
        $a = $s * min($l, 1 - $l) / 100;
        
        $f = function($n) use ($h, $l, $a) {
            $k = ($n + $h / 30) % 12;
            $color = $l - $a * max(min($k - 3, 9 - $k, 1), -1);
            return str_pad(dechex(round(255 * $color)), 2, '0', STR_PAD_LEFT);
        };
        
        return $f(0) . $f(8) . $f(4);
    }
    
    /**
     * Validar archivo de avatar
     *
     * @param UploadedFile $file
     * @throws \Exception
     */
    protected function validateAvatar(UploadedFile $file): void
    {
        // Validar tamaño
        if ($file->getSize() > self::MAX_SIZE) {
            throw new \Exception('El archivo excede el tamaño máximo permitido de 5MB');
        }
        
        // Validar tipo MIME
        if (!in_array($file->getMimeType(), self::ALLOWED_MIMES)) {
            throw new \Exception('El formato del archivo no es válido. Solo se permiten: JPG, PNG, WEBP');
        }
        
        // Validar que sea una imagen real
        $imageInfo = getimagesize($file->getRealPath());
        if (!$imageInfo) {
            throw new \Exception('El archivo no es una imagen válida');
        }
    }
    
    /**
     * Generar nombre único para el archivo
     *
     * @param UploadedFile $file
     * @return string
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);
        
        return "avatar_{$timestamp}_{$random}.jpg";
    }
    
    /**
     * Obtener las iniciales del nombre
     *
     * @param string $name
     * @return string
     */
    public function getInitials(string $name): string
    {
        $words = explode(' ', $name);
        $initials = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
        }
        
        return substr($initials, 0, 2);
    }
}