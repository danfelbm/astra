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
        Schema::table('users', function (Blueprint $table) {
            // Drop the old foreign key constraint
            $table->dropForeign(['localidad_id']);
            
            // Add the new foreign key constraint with correct table name
            $table->foreign('localidad_id')->references('id')->on('localidades')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['localidad_id']);
            
            // Restore the old foreign key constraint
            $table->foreign('localidad_id')->references('id')->on('localidads')->onDelete('set null');
        });
    }
};
