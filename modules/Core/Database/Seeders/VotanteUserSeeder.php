<?php

namespace Modules\Core\Database\Seeders;

use Modules\Core\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class VotanteUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario votante si no existe
        $user = User::firstOrCreate(
            ['email' => 'votante@votaciones.test'],
            [
                'name' => 'Juan Pérez Votante',
                'email' => 'votante@votaciones.test',
                'password' => Hash::make('votante123'), // Password temporal, será reemplazado por OTP
                'territorio_id' => 1,
                'departamento_id' => 1,
                'municipio_id' => 1,
                'activo' => true,
                'email_verified_at' => now(),
            ]
        );

        // Asignar rol user usando Spatie
        if (!$user->hasRole('user')) {
            $user->assignRole('user');
        }

        $this->command->info('Usuario votante creado: votante@votaciones.test');
    }
}