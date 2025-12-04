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
    protected $signature = 'users:create-fake
                            {count=100 : Número de usuarios a crear}
                            {--bogota : Crear usuarios solo en Bogotá con localidades variadas}
                            {--municipio= : ID del municipio específico}';

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
        $bogota = $this->option('bogota');
        $municipioId = $this->option('municipio');

        // Determinar ubicación
        $ubicacion = 'aleatorio';
        if ($bogota) {
            $ubicacion = 'Bogotá';
        } elseif ($municipioId) {
            $municipio = \DB::table('municipios')->find($municipioId);
            $ubicacion = $municipio ? $municipio->nombre : "Municipio ID: {$municipioId}";
        }

        $this->info("Creando {$count} usuarios falsos en {$ubicacion}...");

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
            // Crear usuario con factory según opciones
            $factory = User::factory();

            if ($bogota) {
                $factory = $factory->bogota();
            } elseif ($municipioId) {
                $factory = $factory->enMunicipio((int) $municipioId);
            }

            $user = $factory->create();

            // Asignar rol - solo usuarios normales para datos de prueba
            $user->assignRole($userRole);

            $progressBar->advance();
        }

        $progressBar->finish();

        $this->newLine(2);
        $this->info("✅ {$count} usuarios falsos creados exitosamente en {$ubicacion}!");

        // Mostrar estadísticas
        $totalUsers = User::count();
        $usersWithUserRole = User::role('user')->count();
        $usersWithAdminRole = $adminRole ? User::role('admin')->count() : 0;

        // Estadísticas adicionales si es Bogotá
        $stats = [
            ['Total usuarios en BD', $totalUsers],
            ['Usuarios con rol "user"', $usersWithUserRole],
            ['Usuarios con rol "admin"', $usersWithAdminRole],
            ['Usuarios con email @example.com', User::where('email', 'LIKE', '%@example.com')->count()],
        ];

        if ($bogota || $municipioId == 549) {
            $stats[] = ['Usuarios en Bogotá', User::where('municipio_id', 549)->count()];
            $stats[] = ['Con localidad asignada', User::where('municipio_id', 549)->whereNotNull('localidad_id')->count()];
        }

        $this->table(['Estadística', 'Cantidad'], $stats);

        // Mostrar distribución por localidad si es Bogotá
        if ($bogota) {
            $this->newLine();
            $this->info('Distribución por localidad en Bogotá:');

            $porLocalidad = User::where('municipio_id', 549)
                ->whereNotNull('localidad_id')
                ->selectRaw('localidad_id, COUNT(*) as total')
                ->groupBy('localidad_id')
                ->get()
                ->map(function ($item) {
                    $localidad = \DB::table('localidades')->find($item->localidad_id);
                    return [
                        'Localidad' => $localidad?->nombre ?? 'Desconocida',
                        'Usuarios' => $item->total,
                    ];
                });

            if ($porLocalidad->isNotEmpty()) {
                $this->table(['Localidad', 'Usuarios'], $porLocalidad->toArray());
            }
        }

        return Command::SUCCESS;
    }
}
