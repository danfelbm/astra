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
        Schema::table('user_update_requests', function (Blueprint $table) {
            // Campos para la nueva ubicación solicitada
            $table->foreignId('new_territorio_id')->nullable()->after('new_telefono')
                ->constrained('territorios')->onDelete('set null');
            $table->foreignId('new_departamento_id')->nullable()->after('new_territorio_id')
                ->constrained('departamentos')->onDelete('set null');
            $table->foreignId('new_municipio_id')->nullable()->after('new_departamento_id')
                ->constrained('municipios')->onDelete('set null');
            $table->foreignId('new_localidad_id')->nullable()->after('new_municipio_id')
                ->constrained('localidades')->onDelete('set null');
            
            // Campos para la ubicación actual (snapshot al momento de la solicitud)
            $table->unsignedBigInteger('current_territorio_id')->nullable()->after('current_telefono');
            $table->unsignedBigInteger('current_departamento_id')->nullable()->after('current_territorio_id');
            $table->unsignedBigInteger('current_municipio_id')->nullable()->after('current_departamento_id');
            $table->unsignedBigInteger('current_localidad_id')->nullable()->after('current_municipio_id');
            
            // Índices para mejorar las consultas
            $table->index('new_territorio_id');
            $table->index('new_departamento_id');
            $table->index('new_municipio_id');
            $table->index('new_localidad_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_update_requests', function (Blueprint $table) {
            // Eliminar índices
            $table->dropIndex(['new_territorio_id']);
            $table->dropIndex(['new_departamento_id']);
            $table->dropIndex(['new_municipio_id']);
            $table->dropIndex(['new_localidad_id']);
            
            // Eliminar foreign keys
            $table->dropForeign(['new_territorio_id']);
            $table->dropForeign(['new_departamento_id']);
            $table->dropForeign(['new_municipio_id']);
            $table->dropForeign(['new_localidad_id']);
            
            // Eliminar columnas
            $table->dropColumn([
                'new_territorio_id',
                'new_departamento_id',
                'new_municipio_id',
                'new_localidad_id',
                'current_territorio_id',
                'current_departamento_id',
                'current_municipio_id',
                'current_localidad_id',
            ]);
        });
    }
};