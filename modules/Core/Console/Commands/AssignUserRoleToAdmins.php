<?php

namespace Modules\Core\Console\Commands;

use Modules\Core\Models\User;
use Illuminate\Console\Command;

class AssignUserRoleToAdmins extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:assign-user-role-to-admins';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asigna el rol user a todos los administradores existentes para permitir navegación multi-contexto';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Buscando administradores sin rol de usuario...');
        
        // Obtener todos los usuarios con roles administrativos
        $admins = User::role(['admin', 'super_admin', 'manager'])->get();
        
        if ($admins->isEmpty()) {
            $this->warn('No se encontraron administradores en el sistema.');
            return Command::SUCCESS;
        }
        
        $count = 0;
        $this->info("Se encontraron {$admins->count()} administradores.");
        $this->newLine();
        
        foreach ($admins as $admin) {
            if (!$admin->hasRole('user')) {
                $admin->assignRole('user');
                $count++;
                $this->line("✓ Rol 'user' asignado a: {$admin->email} (ID: {$admin->id})");
            } else {
                $this->line("- {$admin->email} ya tiene rol 'user' (sin cambios)");
            }
        }
        
        $this->newLine();
        
        if ($count > 0) {
            $this->info("✅ Se asignó el rol 'user' a {$count} administrador(es).");
            $this->info("Ahora pueden navegar entre contextos admin y usuario.");
        } else {
            $this->info("Todos los administradores ya tienen el rol 'user'. No se realizaron cambios.");
        }
        
        return Command::SUCCESS;
    }
}