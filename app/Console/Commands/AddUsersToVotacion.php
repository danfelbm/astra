<?php

namespace App\Console\Commands;

use App\Models\Core\User;
use App\Models\Votaciones\Votacion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddUsersToVotacion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'votaciones:add-users 
                            {votacion_id : ID de la votación}
                            {user_start_id : ID inicial del rango de usuarios}
                            {user_end_id : ID final del rango de usuarios}
                            {--check-only : Solo mostrar qué usuarios se añadirían sin ejecutar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Añadir un rango de usuarios a una votación específica';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $votacionId = (int) $this->argument('votacion_id');
        $userStartId = (int) $this->argument('user_start_id');
        $userEndId = (int) $this->argument('user_end_id');
        $checkOnly = $this->option('check-only');
        
        // Validar parámetros
        if ($userStartId > $userEndId) {
            $this->error('El ID inicial debe ser menor o igual al ID final');
            return Command::FAILURE;
        }
        
        // Verificar que la votación existe
        $votacion = Votacion::find($votacionId);
        if (!$votacion) {
            $this->error("Votación con ID {$votacionId} no encontrada");
            return Command::FAILURE;
        }
        
        $this->info("📊 Votación: {$votacion->titulo}");
        $this->info("👥 Rango de usuarios: {$userStartId} - {$userEndId}");
        
        // Obtener usuarios existentes en el rango
        $usersInRange = User::whereBetween('id', [$userStartId, $userEndId])
            ->orderBy('id')
            ->get();
        
        if ($usersInRange->isEmpty()) {
            $this->error('No se encontraron usuarios en el rango especificado');
            return Command::FAILURE;
        }
        
        $this->info("✅ Usuarios encontrados en rango: {$usersInRange->count()}");
        
        // Verificar cuáles ya están asignados a la votación
        $alreadyAssigned = $votacion->votantes()
            ->whereBetween('users.id', [$userStartId, $userEndId])
            ->pluck('users.id')
            ->toArray();
        
        // Usuarios que se pueden añadir (no duplicados)
        $usersToAdd = $usersInRange->whereNotIn('id', $alreadyAssigned);
        
        $this->table(
            ['Estadística', 'Cantidad'],
            [
                ['Usuarios en rango', $usersInRange->count()],
                ['Ya asignados a la votación', count($alreadyAssigned)],
                ['Usuarios a añadir', $usersToAdd->count()],
            ]
        );
        
        if ($usersToAdd->isEmpty()) {
            $this->warn('Todos los usuarios del rango ya están asignados a esta votación');
            return Command::SUCCESS;
        }
        
        // Mostrar algunos usuarios que se añadirán
        if ($usersToAdd->count() <= 10) {
            $this->info('👤 Usuarios que se añadirán:');
            foreach ($usersToAdd as $user) {
                $this->line("  - ID {$user->id}: {$user->name} ({$user->email})");
            }
        } else {
            $this->info('👤 Primeros 5 usuarios que se añadirán:');
            foreach ($usersToAdd->take(5) as $user) {
                $this->line("  - ID {$user->id}: {$user->name} ({$user->email})");
            }
            $remainingCount = $usersToAdd->count() - 5;
            $this->line("  ... y {$remainingCount} más");
        }
        
        // Si es solo check, terminar aquí
        if ($checkOnly) {
            $this->info('🔍 Modo check-only activado. No se realizaron cambios.');
            return Command::SUCCESS;
        }
        
        // Confirmar acción
        if (!$this->confirm("¿Confirmas añadir {$usersToAdd->count()} usuarios a la votación '{$votacion->titulo}'?")) {
            $this->info('Operación cancelada');
            return Command::SUCCESS;
        }
        
        // Obtener tenant_id actual
        $tenantId = app(\App\Services\Core\TenantService::class)->getCurrentTenant()?->id ?? 1;
        
        // Preparar datos para inserción masiva
        $dataToInsert = [];
        $now = now();
        
        foreach ($usersToAdd as $user) {
            $dataToInsert[] = [
                'votacion_id' => $votacionId,
                'usuario_id' => $user->id,
                'tenant_id' => $tenantId,
                'model_type' => 'App\\Models\\Core\\User',
                'origen_id' => null, // Se puede ajustar según necesidades
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        // Inserción masiva
        $this->info('💾 Añadiendo usuarios a la votación...');
        
        try {
            DB::table('votacion_usuario')->insert($dataToInsert);
            
            $this->newLine();
            $this->info("✅ {$usersToAdd->count()} usuarios añadidos exitosamente a la votación");
            
            // Estadísticas finales
            $totalVotantesActuales = $votacion->votantes()->count();
            $this->info("📊 Total de votantes en la votación ahora: {$totalVotantesActuales}");
            
        } catch (\Exception $e) {
            $this->error('❌ Error al añadir usuarios: ' . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}
