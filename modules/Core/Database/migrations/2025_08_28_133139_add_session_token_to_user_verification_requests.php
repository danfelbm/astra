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
        Schema::table('user_verification_requests', function (Blueprint $table) {
            $table->string('session_token', 64)->nullable()->after('user_id');
            $table->index('session_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_verification_requests', function (Blueprint $table) {
            $table->dropIndex(['session_token']);
            $table->dropColumn('session_token');
        });
    }
};