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
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('codigo', 6);
            $table->timestamp('expira_en');
            $table->boolean('usado')->default(false);
            $table->timestamps();
            
            $table->index(['email', 'codigo'], 'idx_email_codigo');
            $table->index('expira_en', 'idx_expiracion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
