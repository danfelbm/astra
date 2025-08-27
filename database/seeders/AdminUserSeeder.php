<?php

namespace Database\Seeders;

use App\Models\Core\User;
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

        // Asignar roles super_admin y user usando Spatie
        if (!$user->hasRole('super_admin')) {
            $user->assignRole('super_admin');
        }
        if (!$user->hasRole('user')) {
            $user->assignRole('user');
        }

        $this->command->info('Usuario administrador creado: admin@votaciones.test con roles super_admin y user');
    }
}
