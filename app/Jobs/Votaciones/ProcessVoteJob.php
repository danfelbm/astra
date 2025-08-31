<?php

namespace App\Jobs\Votaciones;

use App\Jobs\Middleware\WithRateLimiting;
use App\Models\Core\User;
use App\Models\Votaciones\Votacion;
use App\Models\Votaciones\Voto;
use App\Services\Votaciones\TokenService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessVoteJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * El número de veces que el job puede ser intentado.
     */
    public $tries = 3;

    /**
     * El número de segundos para esperar antes de reintentar el job.
     */
    public $backoff = [1, 3, 5]; // Backoff exponencial: 1s, 3s, 5s

    /**
     * El tiempo máximo que el job puede ejecutarse antes de timeout
     */
    public $timeout = 30;

    /**
     * El tiempo en segundos que el job debe ser único.
     * Esto evita que el mismo usuario vote múltiples veces en la misma votación.
     */
    public $uniqueFor = 60;

    protected Votacion $votacion;
    protected User $user;
    protected array $respuestas;
    protected string $ipAddress;
    protected string $userAgent;
    protected string $cacheKey;
    protected ?string $urnaOpenedAt;

    /**
     * Create a new job instance.
     */
    public function __construct(
        Votacion $votacion,
        User $user,
        array $respuestas,
        string $ipAddress,
        string $userAgent,
        ?string $urnaOpenedAt = null
    ) {
        $this->votacion = $votacion;
        $this->user = $user;
        $this->respuestas = $respuestas;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->urnaOpenedAt = $urnaOpenedAt;
        
        // Clave para actualizar el estado del voto en cache
        $this->cacheKey = "vote_status_{$votacion->id}_{$user->id}";
        
        // No usar cola especial, ir a la cola default para procesamiento rápido
        // Los 12 workers principales procesarán estos jobs
        $this->onQueue('default');
    }

    /**
     * El ID único del job para evitar duplicados.
     */
    public function uniqueId(): string
    {
        return "vote-{$this->votacion->id}-{$this->user->id}";
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Marcar en cache que el voto está siendo procesado
            Cache::put($this->cacheKey, 'processing', 120);

            // Verificar una vez más que el usuario no haya votado
            // (por si hubo una condición de carrera)
            if ($this->votacion->votos()->where('usuario_id', $this->user->id)->exists()) {
                Cache::put($this->cacheKey, 'duplicate', 60);
                Log::warning('Intento de voto duplicado detectado en job', [
                    'user_id' => $this->user->id,
                    'votacion_id' => $this->votacion->id
                ]);
                return;
            }

            // Usar transacción para garantizar atomicidad
            DB::transaction(function () {
                // Momento actual del voto
                $voteTimestamp = now();
                
                // Convertir string de urna_opened_at a DateTime si existe
                $urnaOpenedDateTime = $this->urnaOpenedAt ? Carbon::parse($this->urnaOpenedAt) : null;
                
                // Log de debugging
                Log::info('ProcessVoteJob: Procesando voto', [
                    'user_id' => $this->user->id,
                    'votacion_id' => $this->votacion->id,
                    'urnaOpenedAt_raw' => $this->urnaOpenedAt,
                    'urnaOpenedDateTime' => $urnaOpenedDateTime ? $urnaOpenedDateTime->toISOString() : 'null',
                    'voteTimestamp' => $voteTimestamp->toISOString()
                ]);
                
                // Generar token firmado con las respuestas y timestamps de urna
                // Incluir tanto created_at como urna_opened_at en el token
                $token = TokenService::generateSignedToken(
                    $this->votacion->id,
                    $this->respuestas,
                    $voteTimestamp->toISOString(),  // created_at del voto
                    $urnaOpenedDateTime ? $urnaOpenedDateTime->toISOString() : null  // urna_opened_at
                );

                // Crear el voto con retry automático para manejar posibles locks
                $voto = retry(3, function () use ($token, $voteTimestamp, $urnaOpenedDateTime) {
                    Log::info('ProcessVoteJob: Creando voto', [
                        'urnaOpenedDateTime_before_save' => $urnaOpenedDateTime ? $urnaOpenedDateTime->toISOString() : 'null'
                    ]);
                    
                    return Voto::create([
                        'votacion_id' => $this->votacion->id,
                        'usuario_id' => $this->user->id,
                        'token_unico' => $token,
                        'urna_opened_at' => $urnaOpenedDateTime,  // Guardar timestamp de apertura como DateTime
                        'respuestas' => $this->respuestas,
                        'ip_address' => $this->ipAddress,
                        'user_agent' => $this->userAgent,
                    ]);
                }, 100); // Reintentar después de 100ms si hay lock
                
                // Log para verificar qué se guardó realmente
                Log::info('ProcessVoteJob: Voto creado', [
                    'voto_id' => $voto->id,
                    'urna_opened_at_saved' => $voto->urna_opened_at ? $voto->urna_opened_at->toISOString() : 'null',
                    'created_at' => $voto->created_at->toISOString(),
                    'token_first_100' => substr($voto->token_unico, 0, 100)
                ]);

                // Cargar la relación de categoría para las notificaciones
                $this->votacion->load('categoria');

                // Despachar jobs de notificación
                if (!empty($this->user->email)) {
                    SendVoteConfirmationEmailJob::dispatch(
                        $this->user,
                        $this->votacion,
                        $voto
                    );
                }

                if (!empty($this->user->telefono)) {
                    SendVoteConfirmationWhatsAppJob::dispatch(
                        $this->user,
                        $this->votacion,
                        $voto
                    );
                }

                // Marcar en cache que el voto fue procesado exitosamente
                // Este cache durará 5 minutos para que el frontend pueda verificar
                Cache::put($this->cacheKey, 'completed', 300);
                
                // También guardar el ID del voto para referencia rápida
                Cache::put("vote_id_{$this->votacion->id}_{$this->user->id}", $voto->id, 3600);

                Log::info('Voto procesado exitosamente', [
                    'user_id' => $this->user->id,
                    'votacion_id' => $this->votacion->id,
                    'voto_id' => $voto->id,
                    'token' => substr($token, 0, 8) . '...' // Solo log parcial del token
                ]);
            });

        } catch (\Illuminate\Database\QueryException $e) {
            // Si es un error de constraint único, marcar como duplicado
            if ($e->getCode() === '23000') {
                Cache::put($this->cacheKey, 'duplicate', 60);
                Log::warning('Constraint único violado - voto duplicado', [
                    'user_id' => $this->user->id,
                    'votacion_id' => $this->votacion->id,
                    'error' => $e->getMessage()
                ]);
                return; // No reintentar
            }

            // Para otros errores de BD, marcar como fallido y reintentar
            Cache::put($this->cacheKey, 'error', 60);
            Log::error('Error de base de datos procesando voto', [
                'user_id' => $this->user->id,
                'votacion_id' => $this->votacion->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e; // Relanzar para que Laravel maneje el reintento
            
        } catch (\Exception $e) {
            // Error general - marcar como fallido
            Cache::put($this->cacheKey, 'error', 60);
            
            Log::error('Error procesando voto', [
                'user_id' => $this->user->id,
                'votacion_id' => $this->votacion->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e; // Relanzar para reintentos
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Marcar definitivamente como fallido después de todos los reintentos
        Cache::put($this->cacheKey, 'failed', 300);
        
        Log::error('Job de procesamiento de voto falló definitivamente', [
            'user_id' => $this->user->id,
            'votacion_id' => $this->votacion->id,
            'error' => $exception->getMessage()
        ]);

        // Aquí podrías enviar una notificación al admin o al usuario
        // sobre el fallo en el procesamiento del voto
    }
}