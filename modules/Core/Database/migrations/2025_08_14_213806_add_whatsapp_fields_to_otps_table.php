<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            // Canal por el cual se envió el OTP
            $table->enum('canal_enviado', ['email', 'whatsapp', 'both'])
                  ->default('email')
                  ->after('usado')
                  ->comment('Canal usado para enviar el código OTP');
            
            // Timestamp de envío por WhatsApp
            $table->timestamp('whatsapp_sent_at')
                  ->nullable()
                  ->after('canal_enviado')
                  ->comment('Fecha y hora de envío por WhatsApp');
            
            // Número de teléfono al que se envió
            $table->string('telefono_destino', 20)
                  ->nullable()
                  ->after('whatsapp_sent_at')
                  ->comment('Número de WhatsApp al que se envió el código');
            
            // Índices para mejorar performance
            $table->index('canal_enviado', 'idx_canal_enviado');
            $table->index('telefono_destino', 'idx_telefono_destino');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            // Eliminar índices
            $table->dropIndex('idx_canal_enviado');
            $table->dropIndex('idx_telefono_destino');
            
            // Eliminar columnas
            $table->dropColumn([
                'canal_enviado',
                'whatsapp_sent_at',
                'telefono_destino'
            ]);
        });
    }
};
