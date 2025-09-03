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
        // En MySQL, para modificar un ENUM necesitamos usar SQL directo
        DB::statement("ALTER TABLE csv_imports MODIFY COLUMN import_type ENUM('votacion', 'users', 'general', 'asamblea') DEFAULT 'votacion'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir el cambio, quitando 'asamblea' del ENUM
        DB::statement("ALTER TABLE csv_imports MODIFY COLUMN import_type ENUM('votacion', 'users', 'general') DEFAULT 'votacion'");
    }
};
