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
            $table->string('nombre')->after('name');
            $table->bigInteger('territorio_id')->nullable()->after('email');
            $table->bigInteger('departamento_id')->nullable()->after('territorio_id');
            $table->bigInteger('municipio_id')->nullable()->after('departamento_id');
            $table->boolean('activo')->default(true)->after('municipio_id');
            $table->boolean('es_admin')->default(false)->after('activo');
            
            $table->index('email', 'idx_email');
            $table->index('territorio_id', 'idx_territorio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_email');
            $table->dropIndex('idx_territorio');
            $table->dropColumn(['nombre', 'territorio_id', 'departamento_id', 'municipio_id', 'activo', 'es_admin']);
        });
    }
};
