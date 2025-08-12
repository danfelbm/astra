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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subdomain')->unique()->nullable();
            $table->json('settings')->nullable(); // Logo, colores, configuración OTP, etc.
            $table->boolean('active')->default(true);
            $table->string('subscription_plan')->default('basic');
            $table->json('limits')->nullable(); // Límites de usuarios, votaciones, etc.
            $table->timestamps();
            
            // Índices para optimización
            $table->index('subdomain');
            $table->index('active');
        });
        
        // Insertar tenant por defecto para datos existentes
        DB::table('tenants')->insert([
            'id' => 1,
            'name' => 'Organización Principal',
            'subdomain' => 'main',
            'active' => true,
            'subscription_plan' => 'enterprise',
            'settings' => json_encode([
                'logo' => null,
                'primary_color' => '#3B82F6',
                'otp_expiration' => 10,
                'timezone' => 'America/Bogota'
            ]),
            'limits' => json_encode([
                'max_users' => null,
                'max_votaciones' => null,
                'max_convocatorias' => null
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};