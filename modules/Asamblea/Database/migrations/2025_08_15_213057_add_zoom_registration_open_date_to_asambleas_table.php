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
        Schema::table('asambleas', function (Blueprint $table) {
            $table->datetime('zoom_registration_open_date')->nullable()->after('zoom_prefix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asambleas', function (Blueprint $table) {
            $table->dropColumn('zoom_registration_open_date');
        });
    }
};
