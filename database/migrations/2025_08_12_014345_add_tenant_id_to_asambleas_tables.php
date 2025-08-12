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
        // Añadir tenant_id a la tabla asambleas si existe
        if (Schema::hasTable('asambleas') && !Schema::hasColumn('asambleas', 'tenant_id')) {
            Schema::table('asambleas', function (Blueprint $table) {
                // Eliminar el campo nullable temporal y añadirlo correctamente
                $table->dropColumn('tenant_id');
            });
            
            Schema::table('asambleas', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->default(1)->after('id');
                
                // Añadir foreign key
                $table->foreign('tenant_id')
                      ->references('id')
                      ->on('tenants')
                      ->onDelete('cascade');
                
                // Añadir índice para optimización
                $table->index('tenant_id');
            });
            
            // Asignar tenant_id = 1 a todos los registros existentes
            DB::table('asambleas')->update(['tenant_id' => 1]);
        }
        
        // Añadir tenant_id a la tabla pivote asamblea_usuario si existe
        if (Schema::hasTable('asamblea_usuario') && !Schema::hasColumn('asamblea_usuario', 'tenant_id')) {
            Schema::table('asamblea_usuario', function (Blueprint $table) {
                // Eliminar el campo nullable temporal y añadirlo correctamente
                $table->dropColumn('tenant_id');
            });
            
            Schema::table('asamblea_usuario', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->default(1)->after('asamblea_id');
                
                // Añadir foreign key
                $table->foreign('tenant_id')
                      ->references('id')
                      ->on('tenants')
                      ->onDelete('cascade');
                
                // Añadir índice para optimización
                $table->index('tenant_id');
            });
            
            // Asignar tenant_id = 1 a todos los registros existentes
            DB::table('asamblea_usuario')->update(['tenant_id' => 1]);
        }
        
        // Eliminar el valor por defecto después de la migración
        if (Schema::hasTable('asambleas')) {
            Schema::table('asambleas', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->default(null)->change();
            });
        }
        
        if (Schema::hasTable('asamblea_usuario')) {
            Schema::table('asamblea_usuario', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->default(null)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar foreign key y columna de asambleas
        if (Schema::hasTable('asambleas') && Schema::hasColumn('asambleas', 'tenant_id')) {
            Schema::table('asambleas', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
        
        // Eliminar foreign key y columna de asamblea_usuario
        if (Schema::hasTable('asamblea_usuario') && Schema::hasColumn('asamblea_usuario', 'tenant_id')) {
            Schema::table('asamblea_usuario', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};
