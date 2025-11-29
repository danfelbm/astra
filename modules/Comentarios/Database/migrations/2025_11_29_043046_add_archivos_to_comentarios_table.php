<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega soporte para archivos adjuntos en comentarios.
     * Máximo 3 archivos por comentario.
     */
    public function up(): void
    {
        Schema::table('comentarios', function (Blueprint $table) {
            // Rutas de archivos en storage
            $table->json('archivos_paths')->nullable()->after('contenido_plain');
            // Nombres originales de archivos
            $table->json('archivos_nombres')->nullable()->after('archivos_paths');
            // Tipos MIME de archivos
            $table->json('archivos_tipos')->nullable()->after('archivos_nombres');
            // Contador de archivos para consultas rápidas
            $table->unsignedTinyInteger('total_archivos')->default(0)->after('archivos_tipos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comentarios', function (Blueprint $table) {
            $table->dropColumn([
                'archivos_paths',
                'archivos_nombres',
                'archivos_tipos',
                'total_archivos',
            ]);
        });
    }
};
