<?php

namespace App\Console\Commands;

use App\Models\Core\User;
use App\Models\Votaciones\Votacion;
use App\Models\Votaciones\Voto;
use App\Models\Votaciones\UrnaSession;
use App\Jobs\Votaciones\ProcessVoteJob;
use App\Services\Core\IpAddressService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StressTestVoting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voting:stress-test
                            {votacion_id : ID de la votación a probar}
                            {--batch=5 : Número de usuarios votando simultáneamente}
                            {--delay=2 : Segundos de delay entre batches}
                            {--dry-run : Ejecutar sin crear votos reales}
                            {--verbose-log : Log detallado de cada acción}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta pruebas de estrés simulando votaciones simultáneas';

    // Métricas de rendimiento
    private array $metrics = [
        'total_users' => 0,
        'successful_votes' => 0,
        'failed_votes' => 0,
        'duplicate_attempts' => 0,
        'urna_sessions_created' => 0,
        'urna_sessions_expired' => 0,
        'jobs_dispatched' => 0,
        'total_time' => 0,
        'batch_times' => [],
        'errors' => [],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $votacionId = $this->argument('votacion_id');
        $batchSize = (int) $this->option('batch');
        $delayBetweenBatches = (int) $this->option('delay');
        $dryRun = $this->option('dry-run');
        $verboseLog = $this->option('verbose-log');

        $this->info('🧪 INICIANDO PRUEBA DE ESTRÉS DE VOTACIONES');
        $this->info('=========================================');
        
        // Validar votación
        $votacion = Votacion::find($votacionId);
        if (!$votacion) {
            $this->error("❌ Votación ID {$votacionId} no encontrada");
            return Command::FAILURE;
        }

        $this->info("📊 Votación: {$votacion->titulo}");
        $this->info("🗳️ Estado: {$votacion->estado}");
        
        // Verificar que la votación esté activa
        if ($votacion->estado !== 'activa' && !$dryRun) {
            $this->warn("⚠️ La votación no está activa. Usa --dry-run para simular de todos modos.");
            if (!$this->confirm('¿Deseas continuar de todas formas?')) {
                return Command::FAILURE;
            }
        }

        // Obtener votantes asignados
        $votantes = $votacion->votantes()
            ->whereNotExists(function ($query) use ($votacionId) {
                $query->select(DB::raw(1))
                      ->from('votos')
                      ->whereColumn('votos.usuario_id', 'users.id')
                      ->where('votos.votacion_id', $votacionId);
            })
            ->get();

        $totalVotantes = $votantes->count();
        $this->metrics['total_users'] = $totalVotantes;

        if ($totalVotantes === 0) {
            $this->warn("⚠️ No hay votantes disponibles (todos ya votaron o no hay asignados)");
            return Command::SUCCESS;
        }

        $this->info("👥 Votantes disponibles: {$totalVotantes}");
        $this->info("📦 Tamaño de batch: {$batchSize} usuarios simultáneos");
        $this->info("⏱️ Delay entre batches: {$delayBetweenBatches} segundos");
        
        if ($dryRun) {
            $this->warn("🔸 MODO DRY RUN - No se crearán votos reales");
        }

        $this->newLine();
        
        // Confirmar ejecución
        if (!$dryRun && !$this->confirm('¿Deseas ejecutar la prueba de estrés?')) {
            return Command::SUCCESS;
        }

        // Iniciar prueba
        $startTime = microtime(true);
        $this->info('🚀 Iniciando simulación...');
        $this->newLine();

        // Dividir votantes en batches
        $batches = $votantes->chunk($batchSize);
        $batchNumber = 0;

        foreach ($batches as $batch) {
            $batchNumber++;
            $batchStartTime = microtime(true);
            
            $this->info("📦 Batch {$batchNumber}/{$batches->count()} - {$batch->count()} usuarios");
            
            // Ejecutar votaciones en paralelo para este batch
            $this->processBatch($batch, $votacion, $dryRun, $verboseLog);
            
            $batchTime = microtime(true) - $batchStartTime;
            $this->metrics['batch_times'][] = $batchTime;
            
            $this->info(sprintf("  ⏱️ Tiempo del batch: %.2f segundos", $batchTime));
            
            // Mostrar progreso
            $this->showProgress();
            
            // Delay entre batches (excepto en el último)
            if ($batchNumber < $batches->count() && $delayBetweenBatches > 0) {
                $this->info("  ⏸️ Esperando {$delayBetweenBatches} segundos antes del siguiente batch...");
                sleep($delayBetweenBatches);
            }
            
            $this->newLine();
        }

        // Calcular tiempo total
        $this->metrics['total_time'] = microtime(true) - $startTime;

        // Esperar a que se procesen los jobs
        if (!$dryRun) {
            $this->info('⏳ Esperando a que se procesen todos los jobs...');
            $this->waitForJobsToComplete($votacion, $votantes);
        }

        // Mostrar resultados finales
        $this->showFinalResults($votacion, $dryRun);

        return Command::SUCCESS;
    }

    /**
     * Procesar un batch de usuarios votando simultáneamente
     */
    private function processBatch($users, Votacion $votacion, bool $dryRun, bool $verboseLog): void
    {
        // Array para almacenar promesas/threads simulados
        $processes = [];
        
        foreach ($users as $user) {
            if ($verboseLog) {
                $this->line("  👤 Procesando usuario {$user->id} - {$user->name}");
            }
            
            try {
                // Simular proceso de votación
                if ($dryRun) {
                    $this->simulateVote($user, $votacion, $verboseLog);
                } else {
                    $this->executeVote($user, $votacion, $verboseLog);
                }
                
            } catch (\Exception $e) {
                $this->metrics['failed_votes']++;
                $this->metrics['errors'][] = [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'time' => now()->toDateTimeString()
                ];
                
                if ($verboseLog) {
                    $this->error("    ❌ Error: {$e->getMessage()}");
                }
            }
        }
    }

    /**
     * Simular un voto (dry run)
     */
    private function simulateVote(User $user, Votacion $votacion, bool $verboseLog): void
    {
        // Simular apertura de urna
        if ($verboseLog) {
            $this->line("    🗳️ [SIMULADO] Abriendo urna para usuario {$user->id}");
        }
        $this->metrics['urna_sessions_created']++;
        
        // Simular delay aleatorio (entre 1-3 segundos para simular llenado de formulario)
        usleep(rand(1000000, 3000000)); // 1-3 segundos en microsegundos
        
        // Simular envío de voto
        if ($verboseLog) {
            $this->line("    ✉️ [SIMULADO] Enviando voto de usuario {$user->id}");
        }
        $this->metrics['jobs_dispatched']++;
        
        // Simular éxito con 95% de probabilidad
        if (rand(1, 100) <= 95) {
            $this->metrics['successful_votes']++;
            if ($verboseLog) {
                $this->line("    ✅ [SIMULADO] Voto exitoso para usuario {$user->id}");
            }
        } else {
            $this->metrics['failed_votes']++;
            if ($verboseLog) {
                $this->line("    ❌ [SIMULADO] Voto fallido para usuario {$user->id}");
            }
        }
    }

    /**
     * Ejecutar un voto real
     */
    private function executeVote(User $user, Votacion $votacion, bool $verboseLog): void
    {
        $now = Carbon::now();
        
        // 1. Crear sesión de urna
        $urnaSession = UrnaSession::create([
            'votacion_id' => $votacion->id,
            'usuario_id' => $user->id,
            'opened_at' => $now,
            'status' => 'active',
            'ip_address' => '127.0.0.' . rand(1, 255), // IP simulada
            'user_agent' => 'StressTest/1.0',
            'expires_at' => $now->copy()->addMinutes(5),
        ]);
        
        $this->metrics['urna_sessions_created']++;
        
        if ($verboseLog) {
            $this->line("    🗳️ Urna abierta (ID: {$urnaSession->id})");
        }
        
        // 2. Generar respuestas aleatorias basadas en el formulario
        $respuestas = $this->generateRandomResponses($votacion->formulario_config);
        
        // 3. Simular delay de llenado de formulario (1-3 segundos)
        $fillTime = rand(1, 3);
        if ($verboseLog) {
            $this->line("    ⏱️ Simulando llenado de formulario ({$fillTime}s)");
        }
        sleep($fillTime);
        
        // 4. Marcar voto como pendiente en cache
        $cacheKey = "vote_status_{$votacion->id}_{$user->id}";
        Cache::put($cacheKey, 'pending', 120);
        
        // 5. Despachar job de procesamiento
        ProcessVoteJob::dispatch(
            $votacion,
            $user,
            $respuestas,
            $urnaSession->ip_address,
            $urnaSession->user_agent,
            $urnaSession->opened_at->toDateTimeString(),
            $urnaSession->id
        );
        
        $this->metrics['jobs_dispatched']++;
        
        if ($verboseLog) {
            $this->line("    📤 Job despachado para usuario {$user->id}");
        }
    }

    /**
     * Generar respuestas aleatorias para el formulario
     */
    private function generateRandomResponses(array $formConfig): array
    {
        $respuestas = [];
        
        foreach ($formConfig as $field) {
            $fieldId = $field['id'];
            
            // Generar respuesta según tipo de campo
            switch ($field['type']) {
                case 'text':
                    $respuestas[$fieldId] = 'Respuesta de prueba ' . Str::random(10);
                    break;
                    
                case 'textarea':
                    $respuestas[$fieldId] = 'Texto largo de prueba. ' . Str::random(50);
                    break;
                    
                case 'radio':
                case 'select':
                    if (!empty($field['options'])) {
                        $respuestas[$fieldId] = $field['options'][array_rand($field['options'])];
                    }
                    break;
                    
                case 'checkbox':
                    if (!empty($field['options'])) {
                        $numSelections = rand(1, min(3, count($field['options'])));
                        $selections = array_rand($field['options'], $numSelections);
                        if (!is_array($selections)) {
                            $selections = [$selections];
                        }
                        $respuestas[$fieldId] = array_map(
                            fn($i) => $field['options'][$i], 
                            $selections
                        );
                    }
                    break;
                    
                case 'convocatoria':
                    // Simular voto en blanco 20% del tiempo
                    if (rand(1, 100) <= 20) {
                        $respuestas[$fieldId] = null;
                    } else {
                        $respuestas[$fieldId] = 'Candidato-' . rand(1, 10);
                    }
                    break;
                    
                default:
                    $respuestas[$fieldId] = 'Valor de prueba';
            }
        }
        
        return $respuestas;
    }

    /**
     * Esperar a que se completen los jobs
     */
    private function waitForJobsToComplete(Votacion $votacion, $users): void
    {
        $maxWaitTime = 60; // Máximo 60 segundos
        $checkInterval = 2; // Revisar cada 2 segundos
        $elapsed = 0;
        
        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();
        
        while ($elapsed < $maxWaitTime) {
            // Contar votos procesados
            $votosCount = Voto::where('votacion_id', $votacion->id)
                ->whereIn('usuario_id', $users->pluck('id'))
                ->count();
            
            $progressBar->setProgress($votosCount);
            
            // Si todos los votos están procesados, salir
            if ($votosCount >= $users->count()) {
                break;
            }
            
            sleep($checkInterval);
            $elapsed += $checkInterval;
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        if ($elapsed >= $maxWaitTime) {
            $this->warn("⚠️ Timeout esperando jobs. Algunos votos podrían no haberse procesado.");
        }
    }

    /**
     * Mostrar progreso actual
     */
    private function showProgress(): void
    {
        $processed = $this->metrics['successful_votes'] + $this->metrics['failed_votes'];
        $percentage = $this->metrics['total_users'] > 0 
            ? round(($processed / $this->metrics['total_users']) * 100, 1)
            : 0;
        
        $this->info(sprintf(
            "  📊 Progreso: %d/%d (%.1f%%) - ✅ %d exitosos, ❌ %d fallidos",
            $processed,
            $this->metrics['total_users'],
            $percentage,
            $this->metrics['successful_votes'],
            $this->metrics['failed_votes']
        ));
    }

    /**
     * Mostrar resultados finales
     */
    private function showFinalResults(Votacion $votacion, bool $dryRun): void
    {
        $this->newLine();
        $this->info('📊 RESULTADOS DE LA PRUEBA DE ESTRÉS');
        $this->info('=====================================');
        
        $table = [];
        
        // Métricas generales
        $table[] = ['Métrica', 'Valor'];
        $table[] = ['---', '---'];
        $table[] = ['Total de usuarios', $this->metrics['total_users']];
        $table[] = ['Votos exitosos', $this->metrics['successful_votes']];
        $table[] = ['Votos fallidos', $this->metrics['failed_votes']];
        $table[] = ['Intentos duplicados', $this->metrics['duplicate_attempts']];
        $table[] = ['Sesiones de urna creadas', $this->metrics['urna_sessions_created']];
        $table[] = ['Jobs despachados', $this->metrics['jobs_dispatched']];
        $table[] = ['Tiempo total', sprintf('%.2f segundos', $this->metrics['total_time'])];
        
        // Tiempos promedio
        if (!empty($this->metrics['batch_times'])) {
            $avgBatchTime = array_sum($this->metrics['batch_times']) / count($this->metrics['batch_times']);
            $table[] = ['Tiempo promedio por batch', sprintf('%.2f segundos', $avgBatchTime)];
        }
        
        // Tasa de éxito
        if ($this->metrics['total_users'] > 0) {
            $successRate = ($this->metrics['successful_votes'] / $this->metrics['total_users']) * 100;
            $table[] = ['Tasa de éxito', sprintf('%.1f%%', $successRate)];
        }
        
        // TPS (Transacciones por segundo)
        if ($this->metrics['total_time'] > 0) {
            $tps = $this->metrics['jobs_dispatched'] / $this->metrics['total_time'];
            $table[] = ['Transacciones por segundo', sprintf('%.2f', $tps)];
        }
        
        $this->table(['Métrica', 'Valor'], array_slice($table, 2));
        
        // Verificación de integridad (solo si no es dry run)
        if (!$dryRun) {
            $this->newLine();
            $this->info('🔍 VERIFICACIÓN DE INTEGRIDAD');
            $this->info('==============================');
            
            $this->verifyDataIntegrity($votacion);
        }
        
        // Mostrar errores si hay
        if (!empty($this->metrics['errors'])) {
            $this->newLine();
            $this->warn('⚠️ ERRORES ENCONTRADOS');
            $this->warn('======================');
            
            foreach ($this->metrics['errors'] as $error) {
                $this->error(sprintf(
                    "Usuario %d: %s (a las %s)",
                    $error['user_id'],
                    $error['error'],
                    $error['time']
                ));
            }
        }
    }

    /**
     * Verificar integridad de los datos
     */
    private function verifyDataIntegrity(Votacion $votacion): void
    {
        // 1. Verificar votos duplicados
        $duplicates = DB::table('votos')
            ->select('usuario_id', DB::raw('COUNT(*) as count'))
            ->where('votacion_id', $votacion->id)
            ->groupBy('usuario_id')
            ->having('count', '>', 1)
            ->get();
        
        if ($duplicates->count() > 0) {
            $this->error("❌ Se encontraron {$duplicates->count()} usuarios con votos duplicados!");
            foreach ($duplicates as $dup) {
                $this->error("  - Usuario {$dup->usuario_id}: {$dup->count} votos");
            }
        } else {
            $this->info("✅ No hay votos duplicados");
        }
        
        // 2. Verificar sesiones de urna
        $activeSessions = UrnaSession::where('votacion_id', $votacion->id)
            ->where('status', 'active')
            ->where('expires_at', '<=', now())
            ->count();
        
        if ($activeSessions > 0) {
            $this->warn("⚠️ Hay {$activeSessions} sesiones activas que deberían haber expirado");
        } else {
            $this->info("✅ Todas las sesiones expiradas están marcadas correctamente");
        }
        
        // 3. Verificar correspondencia entre sesiones votadas y votos
        $votedSessions = UrnaSession::where('votacion_id', $votacion->id)
            ->where('status', 'voted')
            ->count();
        
        $actualVotes = Voto::where('votacion_id', $votacion->id)->count();
        
        if ($votedSessions !== $actualVotes) {
            $this->warn("⚠️ Discrepancia: {$votedSessions} sesiones marcadas como votadas, pero hay {$actualVotes} votos");
        } else {
            $this->info("✅ Correspondencia perfecta entre sesiones votadas y votos: {$actualVotes}");
        }
        
        // 4. Verificar tokens únicos
        $tokens = Voto::where('votacion_id', $votacion->id)
            ->pluck('token_unico')
            ->toArray();
        
        $uniqueTokens = array_unique($tokens);
        
        if (count($tokens) !== count($uniqueTokens)) {
            $diff = count($tokens) - count($uniqueTokens);
            $this->error("❌ Se encontraron {$diff} tokens duplicados!");
        } else {
            $this->info("✅ Todos los tokens son únicos");
        }
        
        // 5. Verificar que todos los votos tienen timestamps correctos
        $votosConProblemas = Voto::where('votacion_id', $votacion->id)
            ->where(function ($query) {
                $query->whereNull('created_at')
                      ->orWhereNull('urna_opened_at');
            })
            ->count();
        
        if ($votosConProblemas > 0) {
            $this->warn("⚠️ Hay {$votosConProblemas} votos con timestamps faltantes");
        } else {
            $this->info("✅ Todos los votos tienen timestamps completos");
        }
    }
}