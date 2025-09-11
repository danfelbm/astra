<?php

namespace Modules\Imports\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ReflectionClass;

class VerifyLargeCsvSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:verify-setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica que el sistema esté configurado para procesar archivos CSV grandes (140k+ filas)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 VERIFICACIÓN DEL SISTEMA CSV PARA ARCHIVOS GRANDES');
        $this->info('=====================================================');
        $this->newLine();

        $checks = [];
        $allGood = true;
        $warnings = 0;
        $errors = 0;

        // 1. Verificar configuraciones Laravel
        $this->info('📋 Verificando configuraciones Laravel...');

        $batchSize = config('app.csv_import_batch_size', 30);
        $timeout = config('app.csv_import_timeout', 300);

        $checks['batch_size'] = $batchSize >= 300;
        $checks['timeout'] = $timeout >= 1800;

        $this->line("   Batch Size: {$batchSize} " . ($batchSize >= 300 ? '✅' : '❌ (debería ser >= 300)'));
        $this->line("   Timeout: {$timeout}s " . ($timeout >= 1800 ? '✅' : '❌ (debería ser >= 1800s)'));

        // 2. Verificar límites PHP
        $this->newLine();
        $this->info('🐘 Verificando límites PHP...');

        $memoryLimit = ini_get('memory_limit');
        $maxExecution = ini_get('max_execution_time');
        $uploadMax = ini_get('upload_max_filesize');
        $postMax = ini_get('post_max_size');

        $memoryBytes = $this->parseBytes($memoryLimit);
        $uploadBytes = $this->parseBytes($uploadMax);
        $postBytes = $this->parseBytes($postMax);

        $memoryOk = $memoryBytes >= $this->parseBytes('256M');
        $uploadOk = $uploadBytes >= $this->parseBytes('50M');
        $postOk = $postBytes >= $this->parseBytes('50M');

        $this->line("   Memory Limit: {$memoryLimit} " . ($memoryOk ? '✅' : '⚠️ (recomendado >= 256M)'));
        $this->line("   Upload Max: {$uploadMax} " . ($uploadOk ? '✅' : '⚠️ (recomendado >= 50M)'));
        $this->line("   Post Max: {$postMax} " . ($postOk ? '✅' : '⚠️ (recomendado >= 50M)'));

        if (!$memoryOk) $warnings++;
        if (!$uploadOk) $warnings++;
        if (!$postOk) $warnings++;

        // 3. Verificar sistema de colas
        $this->newLine();
        $this->info('📦 Verificando sistema de colas...');

        $queueConnection = env('QUEUE_CONNECTION', 'sync');
        $queueOk = $queueConnection !== 'sync';
        
        $this->line("   Queue Connection: {$queueConnection} " . ($queueOk ? '✅' : '❌ (no debería ser sync)'));
        
        if (!$queueOk) {
            $errors++;
            $allGood = false;
        }

        // 4. Verificar Job ProcessUsersCsvImport
        $this->newLine();
        $this->info('⚙️ Verificando Job optimizado...');

        try {
            $reflection = new ReflectionClass('Modules\Imports\Jobs\ProcessUsersCsvImport');
            $timeoutProperty = $reflection->getProperty('timeout');
            $jobTimeout = $timeoutProperty->getDefaultValue();
            
            $jobTimeoutOk = $jobTimeout >= 1800;
            $this->line("   Job Timeout: {$jobTimeout}s " . ($jobTimeoutOk ? '✅' : '❌ (debería ser >= 1800s)'));
            
            if (!$jobTimeoutOk) {
                $errors++;
                $allGood = false;
            }
        } catch (\Exception $e) {
            $this->line("   Job ProcessUsersCsvImport: ❌ No encontrado");
            $errors++;
            $allGood = false;
        }

        // 5. Verificar optimizaciones de base de datos
        $this->newLine();
        $this->info('🗄️ Verificando optimizaciones de base de datos...');

        try {
            $indexes = DB::select("SHOW INDEX FROM csv_imports WHERE Key_name = 'idx_csv_imports_status_date'");
            $indexesOk = !empty($indexes);
            $this->line("   Índices de DB: " . ($indexesOk ? '✅ Aplicados' : '❌ Faltantes'));
            
            if (!$indexesOk) {
                $errors++;
                $allGood = false;
            }
        } catch (\Exception $e) {
            $this->line("   Índices de DB: ❌ Error verificando");
            $errors++;
            $allGood = false;
        }

        // 6. Verificar espacio en disco
        $this->newLine();
        $this->info('💾 Verificando espacio en disco...');

        $storageDir = storage_path('app/imports');
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $freeBytes = disk_free_space($storageDir);
        $freeGB = round($freeBytes / 1024 / 1024 / 1024, 2);
        $spaceOk = $freeGB >= 2;

        $this->line("   Espacio libre: {$freeGB}GB " . ($spaceOk ? '✅' : '⚠️ (recomendado >= 2GB)'));
        
        if (!$spaceOk) $warnings++;

        // Mostrar resultados
        $this->newLine();
        $this->info('📊 RESUMEN DE VERIFICACIÓN');
        $this->info('========================');
        $this->newLine();

        if ($allGood && $warnings === 0) {
            $this->info('🎉 ¡SISTEMA COMPLETAMENTE OPTIMIZADO!');
            $this->info('✅ El sistema está listo para procesar archivos de 140k+ filas');
        } elseif ($errors === 0) {
            $this->warn('⚠️  SISTEMA FUNCIONAL CON ADVERTENCIAS');
            $this->info('✅ El sistema funcionará, pero considera aplicar las recomendaciones');
            $this->warn("⚠️  Advertencias: {$warnings}");
        } else {
            $this->error('❌ SISTEMA REQUIERE CORRECCIONES');
            $this->error("❌ Errores críticos: {$errors}");
            $this->warn("⚠️  Advertencias: {$warnings}");
            $this->newLine();
            $this->error('Por favor corrige los errores antes de procesar archivos grandes.');
        }

        $this->newLine();
        $this->info('📖 INSTRUCCIONES PARA ARCHIVOS GRANDES:');
        $this->info('=====================================');
        $this->line('1. Sube archivos hasta 50MB (140k+ filas)');
        $this->line('2. El análisis usa streaming (no carga todo a memoria)');
        $this->line("3. Procesamiento en batches de {$batchSize} registros");
        $this->line("4. Timeout de {$timeout} segundos (30 minutos)");
        $this->line('5. Progreso en tiempo real con resolución de conflictos');
        $this->line('6. Logs detallados para debugging');
        $this->newLine();

        $this->info('🚀 ¡Listo para probar con tu archivo de 140k filas!');
        $this->newLine();

        return $allGood ? 0 : 1;
    }

    /**
     * Parse bytes from PHP ini values
     */
    private function parseBytes($val): int
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int) $val;
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    }
}
