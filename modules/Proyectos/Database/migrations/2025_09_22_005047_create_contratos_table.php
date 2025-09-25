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
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();

            // Relación con proyecto
            $table->unsignedBigInteger('proyecto_id');
            $table->foreign('proyecto_id')->references('id')->on('proyectos')->onDelete('cascade');

            // Información básica del contrato
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();

            // Estado y tipo
            $table->enum('estado', ['borrador', 'activo', 'finalizado', 'cancelado'])->default('borrador');
            $table->enum('tipo', ['servicio', 'obra', 'suministro', 'consultoria', 'otro'])->default('servicio');

            // Información financiera
            $table->decimal('monto_total', 15, 2)->nullable();
            $table->string('moneda', 3)->default('USD');

            // Responsables
            $table->unsignedBigInteger('responsable_id')->nullable();
            $table->foreign('responsable_id')->references('id')->on('users')->onDelete('set null');

            // Información de la contraparte
            $table->string('contraparte_nombre')->nullable();
            $table->string('contraparte_identificacion')->nullable();
            $table->string('contraparte_email')->nullable();
            $table->string('contraparte_telefono')->nullable();

            // Archivos y documentación
            $table->string('archivo_pdf')->nullable(); // Ruta del contrato escaneado
            $table->text('observaciones')->nullable();

            // Multi-tenancy
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            // Auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();

            // Índices para optimización
            $table->index('proyecto_id');
            $table->index('estado');
            $table->index('tipo');
            $table->index('fecha_inicio');
            $table->index('fecha_fin');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
