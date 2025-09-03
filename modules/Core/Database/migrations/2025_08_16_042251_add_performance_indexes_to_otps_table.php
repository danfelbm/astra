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
            // Índice para búsquedas por estado de uso
            $table->index('usado', 'idx_usado');
            
            // Índice compuesto para validación completa de OTP
            // Optimiza la consulta más frecuente: WHERE email = ? AND usado = false AND expira_en > ?
            $table->index(['email', 'usado', 'expira_en'], 'idx_email_validation');
            
            // Índice para limpieza de OTPs expirados
            $table->index(['expira_en', 'usado'], 'idx_cleanup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->dropIndex('idx_usado');
            $table->dropIndex('idx_email_validation');
            $table->dropIndex('idx_cleanup');
        });
    }
};