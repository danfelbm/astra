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
        // Primero modificar el enum para agregar la opción 'message'
        DB::statement("ALTER TABLE asambleas MODIFY COLUMN zoom_integration_type ENUM('sdk', 'api', 'message') DEFAULT 'sdk'");
        
        // Agregar campo para el mensaje estático
        Schema::table('asambleas', function (Blueprint $table) {
            $table->text('zoom_static_message')
                  ->nullable()
                  ->after('zoom_occurrence_ids')
                  ->comment('Mensaje estático a mostrar en lugar de videoconferencia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar el campo de mensaje estático
        Schema::table('asambleas', function (Blueprint $table) {
            $table->dropColumn('zoom_static_message');
        });
        
        // Restaurar el enum original
        DB::statement("ALTER TABLE asambleas MODIFY COLUMN zoom_integration_type ENUM('sdk', 'api') DEFAULT 'sdk'");
    }
};