<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero, hacer backup de los datos existentes
        $existingData = DB::table('zoom_registrant_accesses')->get();
        
        // Crear tabla temporal con nueva estructura
        Schema::create('zoom_registrant_accesses_new', function (Blueprint $table) {
            $table->id();
            
            // Relación con zoom_registrants
            $table->foreignId('zoom_registrant_id')->constrained('zoom_registrants')->onDelete('cascade');
            
            // Campos de tracking individual para cada acceso
            $table->timestamp('accessed_at');
            $table->text('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable(); // IPv6 compatible
            $table->text('masked_url')->nullable();
            $table->text('referer')->nullable(); // Nuevo campo para el referer
            $table->string('device_type', 50)->nullable(); // mobile/desktop/tablet
            $table->string('browser_name', 50)->nullable(); // chrome/firefox/safari/edge
            
            $table->timestamps();
            
            // Índices para optimización (con nombres cortos)
            $table->index('zoom_registrant_id', 'idx_zoom_reg_id');
            $table->index('accessed_at', 'idx_accessed_at');
            $table->index(['zoom_registrant_id', 'accessed_at'], 'idx_reg_accessed');
            $table->index('device_type', 'idx_device');
            $table->index('browser_name', 'idx_browser');
        });
        
        // Crear índice parcial para masked_url
        DB::statement('CREATE INDEX zoom_registrant_accesses_new_masked_url_partial ON zoom_registrant_accesses_new (masked_url(255))');
        
        // Migrar datos existentes
        foreach ($existingData as $oldRecord) {
            if ($oldRecord->access_count > 0) {
                // Para cada registro con accesos, crear registros individuales
                $accessCount = $oldRecord->access_count;
                $firstAccessed = $oldRecord->first_accessed_at ? new DateTime($oldRecord->first_accessed_at) : null;
                $lastAccessed = $oldRecord->last_accessed_at ? new DateTime($oldRecord->last_accessed_at) : null;
                
                // Si solo hay un acceso, crear un registro con los datos exactos
                if ($accessCount == 1) {
                    DB::table('zoom_registrant_accesses_new')->insert([
                        'zoom_registrant_id' => $oldRecord->zoom_registrant_id,
                        'accessed_at' => $lastAccessed ?: now(),
                        'user_agent' => $oldRecord->user_agent,
                        'ip_address' => $oldRecord->ip_address,
                        'masked_url' => $oldRecord->masked_url,
                        'referer' => null,
                        'device_type' => $this->detectDeviceType($oldRecord->user_agent),
                        'browser_name' => $this->detectBrowser($oldRecord->user_agent),
                        'created_at' => $oldRecord->created_at,
                        'updated_at' => $oldRecord->updated_at,
                    ]);
                } else {
                    // Para múltiples accesos, distribuir en el tiempo
                    // Crear al menos el primer y último acceso con los timestamps conocidos
                    
                    // Primer acceso
                    if ($firstAccessed) {
                        DB::table('zoom_registrant_accesses_new')->insert([
                            'zoom_registrant_id' => $oldRecord->zoom_registrant_id,
                            'accessed_at' => $firstAccessed,
                            'user_agent' => $oldRecord->user_agent, // Usamos el último conocido
                            'ip_address' => $oldRecord->ip_address,
                            'masked_url' => $oldRecord->masked_url,
                            'referer' => null,
                            'device_type' => $this->detectDeviceType($oldRecord->user_agent),
                            'browser_name' => $this->detectBrowser($oldRecord->user_agent),
                            'created_at' => $oldRecord->created_at,
                            'updated_at' => $oldRecord->created_at,
                        ]);
                    }
                    
                    // Último acceso (si es diferente del primero)
                    if ($lastAccessed && $firstAccessed && $firstAccessed != $lastAccessed) {
                        DB::table('zoom_registrant_accesses_new')->insert([
                            'zoom_registrant_id' => $oldRecord->zoom_registrant_id,
                            'accessed_at' => $lastAccessed,
                            'user_agent' => $oldRecord->user_agent,
                            'ip_address' => $oldRecord->ip_address,
                            'masked_url' => $oldRecord->masked_url,
                            'referer' => null,
                            'device_type' => $this->detectDeviceType($oldRecord->user_agent),
                            'browser_name' => $this->detectBrowser($oldRecord->user_agent),
                            'created_at' => $lastAccessed,
                            'updated_at' => $oldRecord->updated_at,
                        ]);
                        
                        // Si hay más de 2 accesos, crear algunos registros intermedios
                        $remainingAccesses = $accessCount - 2;
                        if ($remainingAccesses > 0 && $firstAccessed && $lastAccessed) {
                            // Distribuir los accesos restantes uniformemente
                            $interval = $firstAccessed->diff($lastAccessed);
                            $totalSeconds = ($interval->days * 86400) + ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
                            
                            for ($i = 0; $i < min($remainingAccesses, 5); $i++) { // Limitar a 5 registros intermedios máximo
                                $offsetSeconds = ($totalSeconds / ($remainingAccesses + 1)) * ($i + 1);
                                $accessTime = clone $firstAccessed;
                                $accessTime->add(new DateInterval('PT' . (int)$offsetSeconds . 'S'));
                                
                                DB::table('zoom_registrant_accesses_new')->insert([
                                    'zoom_registrant_id' => $oldRecord->zoom_registrant_id,
                                    'accessed_at' => $accessTime,
                                    'user_agent' => $oldRecord->user_agent,
                                    'ip_address' => $oldRecord->ip_address,
                                    'masked_url' => $oldRecord->masked_url,
                                    'referer' => null,
                                    'device_type' => $this->detectDeviceType($oldRecord->user_agent),
                                    'browser_name' => $this->detectBrowser($oldRecord->user_agent),
                                    'created_at' => $accessTime,
                                    'updated_at' => $accessTime,
                                ]);
                            }
                        }
                    } elseif ($accessCount > 1 && !$firstAccessed) {
                        // Si no tenemos timestamps, crear registros con el timestamp actual
                        for ($i = 0; $i < min($accessCount, 5); $i++) {
                            DB::table('zoom_registrant_accesses_new')->insert([
                                'zoom_registrant_id' => $oldRecord->zoom_registrant_id,
                                'accessed_at' => now()->subMinutes($i * 5), // Espaciar 5 minutos
                                'user_agent' => $oldRecord->user_agent,
                                'ip_address' => $oldRecord->ip_address,
                                'masked_url' => $oldRecord->masked_url,
                                'referer' => null,
                                'device_type' => $this->detectDeviceType($oldRecord->user_agent),
                                'browser_name' => $this->detectBrowser($oldRecord->user_agent),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        }
        
        // Eliminar tabla antigua y renombrar la nueva
        Schema::dropIfExists('zoom_registrant_accesses');
        Schema::rename('zoom_registrant_accesses_new', 'zoom_registrant_accesses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hacer backup de los datos actuales
        $currentData = DB::table('zoom_registrant_accesses')
            ->select('zoom_registrant_id', DB::raw('COUNT(*) as access_count'), 
                     DB::raw('MIN(accessed_at) as first_accessed_at'),
                     DB::raw('MAX(accessed_at) as last_accessed_at'))
            ->groupBy('zoom_registrant_id')
            ->get();
        
        // Obtener el último acceso de cada zoom_registrant para preservar user_agent, ip, etc
        $lastAccesses = DB::table('zoom_registrant_accesses')
            ->select('zoom_registrant_id', 'user_agent', 'ip_address', 'masked_url')
            ->whereIn('id', function($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('zoom_registrant_accesses')
                    ->groupBy('zoom_registrant_id');
            })
            ->get()
            ->keyBy('zoom_registrant_id');
        
        // Crear tabla con estructura antigua
        Schema::create('zoom_registrant_accesses_old', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zoom_registrant_id')->constrained('zoom_registrants')->onDelete('cascade');
            $table->unsignedInteger('access_count')->default(0);
            $table->timestamp('first_accessed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('masked_url')->nullable();
            $table->timestamps();
            
            $table->index('zoom_registrant_id', 'idx_old_zoom_reg_id');
            $table->index('access_count', 'idx_old_access_count');
            $table->index(['zoom_registrant_id', 'access_count'], 'idx_old_reg_count');
        });
        
        // Restaurar índice de masked_url
        DB::statement('CREATE INDEX zoom_registrant_accesses_old_masked_url_partial ON zoom_registrant_accesses_old (masked_url(255))');
        
        // Migrar datos de vuelta
        foreach ($currentData as $data) {
            $lastAccess = $lastAccesses[$data->zoom_registrant_id] ?? null;
            
            DB::table('zoom_registrant_accesses_old')->insert([
                'zoom_registrant_id' => $data->zoom_registrant_id,
                'access_count' => $data->access_count,
                'first_accessed_at' => $data->first_accessed_at,
                'last_accessed_at' => $data->last_accessed_at,
                'user_agent' => $lastAccess ? $lastAccess->user_agent : null,
                'ip_address' => $lastAccess ? $lastAccess->ip_address : null,
                'masked_url' => $lastAccess ? $lastAccess->masked_url : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Eliminar tabla nueva y renombrar la antigua
        Schema::dropIfExists('zoom_registrant_accesses');
        Schema::rename('zoom_registrant_accesses_old', 'zoom_registrant_accesses');
    }
    
    /**
     * Detectar tipo de dispositivo desde user agent
     */
    private function detectDeviceType(?string $userAgent): ?string
    {
        if (!$userAgent) return null;
        
        $userAgent = strtolower($userAgent);
        
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $userAgent)) {
            return 'tablet';
        }
        
        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $userAgent)) {
            return 'mobile';
        }
        
        if (preg_match('/(mobile|phone|ipod)/i', $userAgent)) {
            return 'mobile';
        }
        
        return 'desktop';
    }
    
    /**
     * Detectar navegador desde user agent
     */
    private function detectBrowser(?string $userAgent): ?string
    {
        if (!$userAgent) return null;
        
        $userAgent = strtolower($userAgent);
        
        if (strpos($userAgent, 'edg') !== false || strpos($userAgent, 'edge') !== false) {
            return 'edge';
        }
        
        if (strpos($userAgent, 'opr') !== false || strpos($userAgent, 'opera') !== false) {
            return 'opera';
        }
        
        if (strpos($userAgent, 'chrome') !== false) {
            return 'chrome';
        }
        
        if (strpos($userAgent, 'safari') !== false && strpos($userAgent, 'chrome') === false) {
            return 'safari';
        }
        
        if (strpos($userAgent, 'firefox') !== false) {
            return 'firefox';
        }
        
        if (strpos($userAgent, 'msie') !== false || strpos($userAgent, 'trident') !== false) {
            return 'ie';
        }
        
        return 'other';
    }
};