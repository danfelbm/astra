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
        Schema::table('votaciones', function (Blueprint $table) {
            $table->json('territorios_ids')->nullable()->after('timezone')->comment('Array de IDs de territorios asociados');
            $table->json('departamentos_ids')->nullable()->after('territorios_ids')->comment('Array de IDs de departamentos asociados');
            $table->json('municipios_ids')->nullable()->after('departamentos_ids')->comment('Array de IDs de municipios asociados');
            $table->json('localidades_ids')->nullable()->after('municipios_ids')->comment('Array de IDs de localidades asociadas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votaciones', function (Blueprint $table) {
            $table->dropColumn(['territorios_ids', 'departamentos_ids', 'municipios_ids', 'localidades_ids']);
        });
    }
};
