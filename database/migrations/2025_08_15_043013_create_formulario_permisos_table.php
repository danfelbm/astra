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
        Schema::create('formulario_permisos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            
            // Relaciones
            $table->foreignId('formulario_id')->constrained('formularios')->onDelete('cascade');
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('cascade');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->onDelete('cascade');
            
            // Tipo de permiso
            $table->enum('tipo_permiso', ['ver', 'llenar', 'editar_respuesta', 'ver_respuestas'])->default('llenar');
            
            // Configuración adicional
            $table->json('configuracion')->nullable(); // Para configuraciones específicas del permiso
            
            // Vigencia del permiso
            $table->datetime('valido_desde')->nullable();
            $table->datetime('valido_hasta')->nullable();
            
            // Estado
            $table->boolean('activo')->default(true);
            
            // Metadata
            $table->unsignedBigInteger('otorgado_por')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index('tenant_id');
            $table->index(['formulario_id', 'role_id']);
            $table->index(['formulario_id', 'usuario_id']);
            $table->index(['tipo_permiso', 'activo']);
            $table->unique(['formulario_id', 'role_id', 'tipo_permiso'], 'unique_form_role_perm');
            $table->unique(['formulario_id', 'usuario_id', 'tipo_permiso'], 'unique_form_user_perm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formulario_permisos');
    }
};
