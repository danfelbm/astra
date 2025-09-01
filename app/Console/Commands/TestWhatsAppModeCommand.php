<?php

namespace App\Console\Commands;

use App\Services\Core\WhatsAppService;
use Illuminate\Console\Command;

class TestWhatsAppModeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:whatsapp-mode';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar el modo LOG de WhatsApp sin generar costos reales';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $whatsappService = new WhatsAppService();

        $this->info('🚀 Probando sistema de WhatsApp...');
        
        // Mostrar estadísticas del servicio
        $stats = $whatsappService->getStats();
        $this->table(
            ['Propiedad', 'Valor'],
            [
                ['Habilitado', $stats['enabled'] ? 'Sí' : 'No'],
                ['Modo', $stats['mode']],
                ['Modo LOG', $stats['is_log_mode'] ? 'Sí' : 'No'],
                ['Instancia', $stats['instance']],
                ['URL Base', $stats['base_url']],
                ['Test Conexión', $stats['connection_test'] ? 'OK' : 'Fallo'],
            ]
        );

        // Probar envío de diferentes tipos de mensajes
        $phoneNumber = '573123456789';
        
        $this->info("\n📱 Probando envío de mensajes...");
        
        // 1. Mensaje OTP
        $this->line('1. Probando OTP...');
        $otpTemplate = $whatsappService->getOTPMessageTemplate('123456', 'Usuario Test', 10);
        $result1 = $whatsappService->sendMessage($phoneNumber, $otpTemplate);
        $this->line($result1 ? '✅ OTP enviado' : '❌ Error en OTP');

        // 2. Mensaje de confirmación de voto
        $this->line('2. Probando confirmación de voto...');
        $voteTemplate = $whatsappService->getVoteConfirmationTemplate(
            'Usuario Test', 
            'Elección de Presidente', 
            'ABC123-DEF456-GHI789', 
            now(), 
            'America/Bogota'
        );
        $result2 = $whatsappService->sendMessage($phoneNumber, $voteTemplate);
        $this->line($result2 ? '✅ Confirmación de voto enviada' : '❌ Error en confirmación');

        // 3. Mensaje genérico
        $this->line('3. Probando mensaje genérico...');
        $genericMessage = "Hola Usuario Test,\n\nEste es un mensaje de prueba del sistema.\n\nGracias por tu participación.";
        $result3 = $whatsappService->sendMessage($phoneNumber, $genericMessage);
        $this->line($result3 ? '✅ Mensaje genérico enviado' : '❌ Error en mensaje genérico');

        // Resultados finales
        $this->newLine();
        if ($stats['is_log_mode']) {
            $this->info('🎯 Pruebas completadas en MODO LOG');
            $this->line('💰 Sin costos generados - Revisa storage/logs/laravel.log para ver los mensajes registrados');
            $this->line('📝 Busca líneas con "[WHATSAPP MODE LOG]" en el log');
        } else {
            $this->warn('⚠️  CUIDADO: Ejecutado en MODO PRODUCTION');
            $this->line('💸 Los mensajes fueron enviados REALMENTE y pueden generar costos');
        }

        return Command::SUCCESS;
    }
}
