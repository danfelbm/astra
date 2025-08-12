<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador si no existe
        $user = User::firstOrCreate(
            ['email' => 'admin@votaciones.test'],
            [
                'name' => 'Administrador Sistema',
                'email' => 'admin@votaciones.test',
                'password' => Hash::make('admin123'), // Password temporal, será reemplazado por OTP
                'territorio_id' => null,
                'departamento_id' => null,
                'municipio_id' => null,
                'activo' => true,
                'email_verified_at' => now(),
            ]
        );

        // Asignar rol super_admin si no lo tiene
        if (!\DB::table('role_user')->where('user_id', $user->id)->where('role_id', 1)->exists()) {
            \DB::table('role_user')->insert([
                'user_id' => $user->id,
                'role_id' => 1, // ID del rol super_admin
                'assigned_at' => now()
            ]);
        }

        $this->command->info('Usuario administrador creado: admin@votaciones.test');
    }
}
