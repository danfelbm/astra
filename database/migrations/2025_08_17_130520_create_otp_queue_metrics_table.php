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
        Schema::create('otp_queue_metrics', function (Blueprint $table) {
            $table->id();
            
            // Canal de envío
            $table->enum('channel', ['email', 'whatsapp']);
            $table->index('channel');
            
            // Estado del envío
            $table->enum('status', ['queued', 'processing', 'sent', 'failed', 'throttled']);
            $table->index('status');
            
            // Identificador del destinatario (email o teléfono)
            $table->string('identifier');
            $table->index('identifier');
            
            // Referencia al usuario si está autenticado
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            // Timestamps de procesamiento
            $table->timestamp('queued_at');
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            
            // Información de reintentos
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('retry_after')->nullable(); // Segundos hasta el próximo reintento
            
            // Mensaje de error si falla
            $table->text('error_message')->nullable();
            
            // Código de error específico (ej: 450 para rate limit)
            $table->string('error_code')->nullable();
            
            // Metadata adicional en JSON
            $table->json('metadata')->nullable();
            // Puede incluir: tipo de OTP, asamblea_id, ip_address, user_agent, etc.
            
            // Tiempo de procesamiento en milisegundos
            $table->unsignedInteger('processing_time_ms')->nullable();
            
            // Si fue throttled, cuántos segundos tuvo que esperar
            $table->unsignedInteger('throttle_delay_seconds')->nullable();
            
            // Job ID de Laravel si está disponible
            $table->string('job_id')->nullable();
            $table->index('job_id');
            
            // Timestamps
            $table->timestamps();
            
            // Índices compuestos para queries comunes
            $table->index(['channel', 'status', 'created_at']);
            $table->index(['channel', 'queued_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_queue_metrics');
    }
};