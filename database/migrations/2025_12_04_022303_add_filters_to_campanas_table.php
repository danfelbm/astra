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
        Schema::table('campanas', function (Blueprint $table) {
            // Modo de audiencia: 'segment' (usa segment_id) o 'manual' (usa filters)
            $table->string('audience_mode', 20)->default('segment')->after('segment_id');
            // Filtros manuales en formato JSON (estructura compatible con AdvancedFilters)
            $table->json('filters')->nullable()->after('audience_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campanas', function (Blueprint $table) {
            $table->dropColumn(['audience_mode', 'filters']);
        });
    }
};
