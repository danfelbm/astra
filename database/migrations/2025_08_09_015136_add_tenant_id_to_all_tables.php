<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas que necesitan tenant_id
     * Excluimos: territorios, departamentos, municipios, localidades (datos compartidos)
     */
    private $tablesWithTenant = [
        'users',
        'categorias',
        'votaciones',
        'votacion_usuario',
        'votos',
        'otps',
        'csv_imports',
        'configuraciones',
        'cargos',
        'periodos_electorales',
        'convocatorias',
        'candidaturas',
        'candidatura_configs',
        'candidatura_historiales',
        'postulaciones',
        'postulacion_historiales',
    ];
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tablesWithTenant as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    // Para tablas pivote sin columna id, añadir después del primer campo
                    if ($tableName === 'votacion_usuario') {
                        $table->unsignedBigInteger('tenant_id')->default(1)->after('votacion_id');
                    } else {
                        // Para tablas normales, añadir después del id
                        $table->unsignedBigInteger('tenant_id')->default(1)->after('id');
                    }
                    
                    // Añadir foreign key
                    $table->foreign('tenant_id')
                          ->references('id')
                          ->on('tenants')
                          ->onDelete('cascade');
                    
                    // Añadir índice para optimización
                    $table->index('tenant_id');
                });
                
                // Asignar tenant_id = 1 a todos los registros existentes
                DB::table($tableName)->update(['tenant_id' => 1]);
            }
        }
        
        // Eliminar el valor por defecto después de la migración
        foreach ($this->tablesWithTenant as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('tenant_id')->default(null)->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tablesWithTenant as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    // Eliminar foreign key
                    $table->dropForeign(['tenant_id']);
                    
                    // Eliminar índice
                    $table->dropIndex(['tenant_id']);
                    
                    // Eliminar columna
                    $table->dropColumn('tenant_id');
                });
            }
        }
    }
};