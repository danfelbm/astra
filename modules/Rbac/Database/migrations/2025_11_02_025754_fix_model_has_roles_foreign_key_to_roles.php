<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Corrige TODAS las foreign keys para que apunten a 'roles'
     * en lugar de 'roles_old' (tabla legacy de migración a Spatie)
     */
    public function up(): void
    {
        // 1. model_has_roles
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropForeign('model_has_roles_role_id_foreign');
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');
        });

        // 2. formulario_permisos
        Schema::table('formulario_permisos', function (Blueprint $table) {
            $table->dropForeign('formulario_permisos_role_id_foreign');
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');
        });

        // 3. role_has_permissions
        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->dropForeign('role_has_permissions_role_id_foreign');
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');
        });

        // 4. role_segments
        Schema::table('role_segments', function (Blueprint $table) {
            $table->dropForeign('role_segments_role_id_foreign');
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');
        });

        // 5. role_user_backup (si existe)
        if (Schema::hasTable('role_user_backup')) {
            Schema::table('role_user_backup', function (Blueprint $table) {
                $table->dropForeign('role_user_role_id_foreign');
                $table->foreign('role_id')
                    ->references('id')
                    ->on('roles')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_has_roles', function (Blueprint $table) {
            // Eliminar FK que apunta a roles
            $table->dropForeign('model_has_roles_role_id_foreign');

            // Restaurar FK que apunte a roles_old
            $table->foreign('role_id')
                ->references('id')
                ->on('roles_old')
                ->onDelete('cascade');
        });
    }
};
