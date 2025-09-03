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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable(); // Null para roles globales
            $table->string('name')->unique(); // super_admin, admin, manager, user, end_customer
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->json('permissions')->nullable(); // Array de permisos
            $table->json('allowed_modules')->nullable(); // Módulos permitidos
            $table->timestamps();
            
            // Foreign key
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->onDelete('cascade');
            
            // Índices
            $table->index('tenant_id');
            $table->index('name');
        });
        
        // Insertar roles por defecto
        DB::table('roles')->insert([
            [
                'id' => 1,
                'tenant_id' => null, // Rol global
                'name' => 'super_admin',
                'display_name' => 'Super Admin',
                'description' => 'Administración completa de la plataforma',
                'permissions' => json_encode(['*']),
                'allowed_modules' => json_encode(['*']),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 2,
                'tenant_id' => 1,
                'name' => 'admin',
                'display_name' => 'Admin',
                'description' => 'Gestión de usuarios y configuraciones generales',
                'permissions' => json_encode([
                    'users.view', 'users.create', 'users.edit', 'users.delete',
                    'votaciones.view', 'votaciones.create', 'votaciones.edit', 'votaciones.delete',
                    'convocatorias.view', 'convocatorias.create', 'convocatorias.edit', 'convocatorias.delete',
                    'postulaciones.view', 'postulaciones.review',
                    'candidaturas.view', 'candidaturas.approve',
                    'reports.view', 'reports.export',
                    'settings.view', 'settings.edit'
                ]),
                'allowed_modules' => null,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 3,
                'tenant_id' => 1,
                'name' => 'manager',
                'display_name' => 'Manager',
                'description' => 'Supervisión de múltiples cuentas de usuarios',
                'permissions' => json_encode([
                    'users.view', 'users.edit',
                    'votaciones.view', 'votaciones.create', 'votaciones.edit',
                    'convocatorias.view', 'convocatorias.create',
                    'postulaciones.view',
                    'candidaturas.view',
                    'reports.view'
                ]),
                'allowed_modules' => null,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 4,
                'tenant_id' => 1,
                'name' => 'user',
                'display_name' => 'User',
                'description' => 'Usuario que paga por el servicio, gestiona su propia cuenta',
                'permissions' => json_encode([
                    'dashboard.view',
                    'votaciones.view',
                    'votaciones.vote',
                    'convocatorias.view',
                    'postulaciones.create',
                    'candidaturas.create',
                    'profile.edit'
                ]),
                'allowed_modules' => null,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 5,
                'tenant_id' => 1,
                'name' => 'end_customer',
                'display_name' => 'End Customer',
                'description' => 'Cliente final del User, consume contenido/servicio específico del User',
                'permissions' => json_encode([
                    'votaciones.view',
                    'votaciones.vote',
                    'convocatorias.view',
                    'profile.edit'
                ]),
                'allowed_modules' => null,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};