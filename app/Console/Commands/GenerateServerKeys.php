<?php

namespace App\Console\Commands;

use App\Services\CryptoService;
use Illuminate\Console\Command;

class GenerateServerKeys extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'votaciones:generate-keys {--force : Sobrescribir claves existentes}';

    /**
     * The console command description.
     */
    protected $description = 'Genera el par de claves RSA para el sistema de votaciones';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔐 Generando claves para el sistema de votaciones...');

        // Verificar si las claves ya existen
        if (CryptoService::keysExist() && !$this->option('force')) {
            $this->warn('⚠️  Las claves ya existen.');
            
            if (!$this->confirm('¿Quieres sobrescribirlas? Esto invalidará todos los tokens existentes.')) {
                $this->info('✅ Operación cancelada.');
                return self::SUCCESS;
            }
        }

        try {
            // Generar el par de claves
            $this->info('🔄 Generando par de claves RSA 2048-bit...');
            $keys = CryptoService::generateKeyPair();

            // Almacenar las claves
            $this->info('💾 Almacenando claves en storage/app/keys/...');
            CryptoService::storeKeys($keys['private_key'], $keys['public_key']);

            $this->newLine();
            $this->info('✅ ¡Claves generadas exitosamente!');
            $this->line('📁 Ubicación: storage/app/keys/');
            $this->line('🔒 Clave privada: storage/app/keys/private.pem (permisos 600)');
            $this->line('🔓 Clave pública: storage/app/keys/public.pem (permisos 644)');
            
            $this->newLine();
            $this->warn('⚠️  IMPORTANTE:');
            $this->warn('   • Mantén la clave privada segura y nunca la compartas');
            $this->warn('   • Haz backup de las claves antes de deployar a producción');
            $this->warn('   • Si pierdes las claves, todos los tokens existentes se invalidarán');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error al generar las claves: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}