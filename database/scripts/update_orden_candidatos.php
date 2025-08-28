<?php

/**
 * Script para actualizar votaciones existentes con el valor por defecto de ordenCandidatos
 * 
 * Ejecutar con: php database/scripts/update_orden_candidatos.php
 * o desde tinker: php artisan tinker < database/scripts/update_orden_candidatos.php
 */

use App\Models\Votaciones\Votacion;

// Cargar el autoloader de Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Actualizando votaciones existentes con orden de candidatos por defecto...\n";

$votacionesActualizadas = 0;
$votacionesTotal = Votacion::count();

Votacion::chunk(100, function ($votaciones) use (&$votacionesActualizadas) {
    foreach ($votaciones as $votacion) {
        $formularioConfig = $votacion->formulario_config;
        $actualizado = false;
        
        // Revisar cada campo del formulario
        foreach ($formularioConfig as &$campo) {
            // Solo actualizar campos de tipo convocatoria que no tengan ordenCandidatos definido
            if ($campo['type'] === 'convocatoria' && isset($campo['convocatoriaConfig'])) {
                if (!isset($campo['convocatoriaConfig']['ordenCandidatos'])) {
                    $campo['convocatoriaConfig']['ordenCandidatos'] = 'aleatorio';
                    $actualizado = true;
                }
            }
        }
        
        // Guardar solo si hubo cambios
        if ($actualizado) {
            $votacion->formulario_config = $formularioConfig;
            $votacion->save();
            $votacionesActualizadas++;
            echo "✓ Votación ID {$votacion->id}: '{$votacion->titulo}' actualizada\n";
        }
    }
});

echo "\n";
echo "========================================\n";
echo "Proceso completado:\n";
echo "- Total de votaciones: {$votacionesTotal}\n";
echo "- Votaciones actualizadas: {$votacionesActualizadas}\n";
echo "- Votaciones sin cambios: " . ($votacionesTotal - $votacionesActualizadas) . "\n";
echo "========================================\n";