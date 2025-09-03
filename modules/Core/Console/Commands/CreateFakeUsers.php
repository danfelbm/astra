<?php

namespace Modules\Core\Console\Commands;

use Modules\Core\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class CreateFakeUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:create-fake {count=100 : Número de usuarios a crear}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear usuarios falsos para testing con datos realistas y roles asignados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->argument('count');
        
        $this->info("Creando {$count} usuarios falsos...");
        
        // Verificar que existen roles
        $userRole = Role::where('name', 'user')->first();
        $adminRole = Role::where('name', 'admin')->first();
        
        if (!$userRole) {
            $this->error('Rol "user" no encontrado. Ejecuta los seeders primero.');
            return Command::FAILURE;
        }
        
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();
        
        for ($i = 0; $i < $count; $i++) {
            // Crear usuario con factory
            $user = User::factory()->create();
            
            // Asignar rol - 90% users, 10% admins si existe rol admin
            if ($adminRole && fake()->boolean(10)) {
                $user->assignRole($adminRole);
            } else {
                $user->assignRole($userRole);
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        
        $this->newLine(2);
        $this->info("✅ {$count} usuarios falsos creados exitosamente!");
        
        // Mostrar estadísticas
        $totalUsers = User::count();
        $usersWithUserRole = User::role('user')->count();
        $usersWithAdminRole = $adminRole ? User::role('admin')->count() : 0;
        
        $this->table(
            ['Estadística', 'Cantidad'],
            [
                ['Total usuarios en BD', $totalUsers],
                ['Usuarios con rol "user"', $usersWithUserRole],
                ['Usuarios con rol "admin"', $usersWithAdminRole],
                ['Usuarios con email @example.com', User::where('email', 'LIKE', '%@example.com')->count()],
            ]
        );
        
        return Command::SUCCESS;
    }
}
