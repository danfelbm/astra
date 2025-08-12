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
        // Migrar usuarios existentes a roles
        $users = DB::table('users')->get();
        
        foreach ($users as $user) {
            if ($user->es_admin) {
                // Si el email es admin@votaciones.test, asignar super_admin, sino admin
                $roleId = ($user->email === 'admin@votaciones.test') ? 1 : 2;
            } else {
                // Usuario regular
                $roleId = 4; // Role 'user'
            }
            
            // Insertar en la tabla role_user
            DB::table('role_user')->insert([
                'role_id' => $roleId,
                'user_id' => $user->id,
                'assigned_by' => null, // Migración automática
                'assigned_at' => now()
            ]);
        }
        
        // Después de migrar los datos, eliminar el campo es_admin
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('es_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar el campo es_admin
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('es_admin')->default(false)->after('activo');
        });
        
        // Restaurar valores de es_admin basándose en los roles
        $roleUsers = DB::table('role_user')
            ->whereIn('role_id', [1, 2]) // super_admin y admin
            ->pluck('user_id');
        
        DB::table('users')
            ->whereIn('id', $roleUsers)
            ->update(['es_admin' => true]);
        
        // Limpiar la tabla role_user
        DB::table('role_user')->truncate();
    }
};