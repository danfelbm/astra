<?php

namespace App\Console\Commands;

use App\Models\Elecciones\Candidatura;
use App\Models\Core\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class TestAuditLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:test {--candidatura-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba el sistema de auditoría con el módulo de Candidaturas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando prueba del sistema de auditoría...');
        $this->newLine();

        // Obtener una candidatura para probar
        $candidaturaId = $this->option('candidatura-id');
        $candidatura = $candidaturaId 
            ? Candidatura::find($candidaturaId)
            : Candidatura::first();

        if (!$candidatura) {
            $this->error('No se encontró ninguna candidatura para probar.');
            $this->info('Creando una candidatura de prueba...');
            
            // Crear usuario de prueba si no existe
            $user = User::firstOrCreate(
                ['email' => 'test@example.com'],
                [
                    'name' => 'Usuario de Prueba',
                    'password' => bcrypt('password'),
                ]
            );

            // Crear candidatura de prueba
            $candidatura = Candidatura::create([
                'user_id' => $user->id,
                'formulario_data' => ['campo1' => 'valor1', 'campo2' => 'valor2'],
                'estado' => 'borrador',
            ]);
            
            $this->info('✅ Candidatura de prueba creada con ID: ' . $candidatura->id);
        }

        $this->info('📋 Usando candidatura ID: ' . $candidatura->id);
        $this->info('👤 Usuario: ' . $candidatura->user->name);
        $this->info('📊 Estado actual: ' . $candidatura->estado);
        $this->newLine();

        // Simular un usuario administrador
        $admin = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['admin', 'super_admin']);
        })->first();

        if (!$admin) {
            $admin = User::first();
            $this->warn('⚠️ No se encontró un administrador, usando el primer usuario disponible.');
        }

        Auth::login($admin);
        $this->info('🔐 Simulando autenticación como: ' . $admin->name);
        $this->newLine();

        // Prueba 1: Registrar acceso
        $this->info('📝 Prueba 1: Registrando acceso a candidatura...');
        $candidatura->logAccess('Acceso desde comando de prueba');
        $this->info('✅ Acceso registrado');
        $this->newLine();

        // Prueba 2: Agregar comentario
        $this->info('📝 Prueba 2: Agregando comentario...');
        $candidatura->agregarComentario('Comentario de prueba desde comando', 'general', false);
        $this->info('✅ Comentario agregado');
        $this->newLine();

        // Prueba 3: Cambiar estado si es borrador
        if ($candidatura->estado === 'borrador') {
            $this->info('📝 Prueba 3: Cambiando estado a pendiente...');
            $estadoAnterior = $candidatura->estado;
            $candidatura->update(['estado' => 'pendiente']);
            $candidatura->logStateChange('estado', $estadoAnterior, 'pendiente', 'Cambio de prueba');
            $this->info('✅ Estado cambiado y registrado');
        } else {
            $this->info('⏭️ Prueba 3: Saltada (candidatura no está en borrador)');
        }
        $this->newLine();

        // Prueba 4: Registrar acción personalizada
        $this->info('📝 Prueba 4: Registrando acción personalizada...');
        $candidatura->logAction('probó auditoría', 'Ejecución del comando de prueba', [
            'comando' => 'audit:test',
            'ejecutado_por' => $admin->name,
            'timestamp' => now()->toISOString(),
        ]);
        $this->info('✅ Acción personalizada registrada');
        $this->newLine();

        // Mostrar logs de actividad generados
        $this->info('📊 Logs de actividad generados para esta candidatura:');
        $this->newLine();

        $activities = Activity::where('subject_type', Candidatura::class)
            ->where('subject_id', $candidatura->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($activities->isEmpty()) {
            $this->warn('No se encontraron logs de actividad.');
        } else {
            $this->table(
                ['ID', 'Log Name', 'Descripción', 'Evento', 'Usuario', 'Fecha'],
                $activities->map(function ($activity) {
                    return [
                        $activity->id,
                        $activity->log_name,
                        Str::limit($activity->description, 50),
                        $activity->event ?? 'N/A',
                        $activity->causer ? $activity->causer->name : 'Sistema',
                        $activity->created_at->format('Y-m-d H:i:s'),
                    ];
                })
            );

            $this->newLine();
            
            // Mostrar detalles del último log
            $lastActivity = $activities->first();
            $this->info('📋 Detalles del último log:');
            $this->info('   ID: ' . $lastActivity->id);
            $this->info('   Log Name: ' . $lastActivity->log_name);
            $this->info('   Descripción: ' . $lastActivity->description);
            $this->info('   Evento: ' . ($lastActivity->event ?? 'N/A'));
            $this->info('   Usuario: ' . ($lastActivity->causer ? $lastActivity->causer->name : 'Sistema'));
            $this->info('   Fecha: ' . $lastActivity->created_at->format('Y-m-d H:i:s'));
            
            if ($lastActivity->properties->count() > 0) {
                $this->info('   Propiedades:');
                foreach ($lastActivity->properties as $key => $value) {
                    if (is_array($value)) {
                        $this->info('      ' . $key . ': ' . json_encode($value));
                    } else {
                        $this->info('      ' . $key . ': ' . $value);
                    }
                }
            }
        }

        $this->newLine();
        $this->info('✅ Prueba completada exitosamente!');
        $this->info('📊 Total de logs para esta candidatura: ' . $candidatura->getActivityCount());
        
        // Limpiar autenticación
        Auth::logout();

        return Command::SUCCESS;
    }
}