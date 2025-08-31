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
     * Reducido a 2 para minimizar problemas de duplicados
     */
    public $tries = 2;

    /**
     * El número de segundos para esperar antes de reintentar el job.
     * Aumentado para dar tiempo a que se complete el primero
     */
    public $backoff = [5, 10]; // Backoff: 5s, 10s

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
    protected ?int $urnaSessionId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        Votacion $votacion,
        User $user,
        array $respuestas,
        string $ipAddress,
        string $userAgent,
        ?string $urnaOpenedAt = null,
        ?int $urnaSessionId = null
    ) {
        $this->votacion = $votacion;
        $this->user = $user;
        $this->respuestas = $respuestas;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->urnaOpenedAt = $urnaOpenedAt;
        $this->urnaSessionId = $urnaSessionId;
        
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
            $votoExistente = $this->votacion->votos()->where('usuario_id', $this->user->id)->first();
            
            if ($votoExistente) {
                // Si ya existe el voto, cerrar la sesión de urna si está pendiente
                if ($this->urnaSessionId) {
                    $urnaSession = \App\Models\Votaciones\UrnaSession::find($this->urnaSessionId);
                    if ($urnaSession && $urnaSession->status === 'active') {
                        $urnaSession->closeByVote();
                        Log::info('Sesión de urna cerrada en reintento de job', [
                            'urna_session_id' => $this->urnaSessionId,
                            'voto_id' => $votoExistente->id
                        ]);
                    }
                }
                
                Cache::put($this->cacheKey, 'completed', 300);
                Cache::put("vote_id_{$this->votacion->id}_{$this->user->id}", $votoExistente->id, 3600);
                
                Log::info('Voto ya existe, job terminando exitosamente', [
                    'user_id' => $this->user->id,
                    'votacion_id' => $this->votacion->id,
                    'voto_id' => $votoExistente->id
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

                // Crear el voto con retry automático para manejar posibles locks
                // CRÍTICO: Token se genera DENTRO del retry para evitar duplicados
                $voto = retry(3, function () use ($voteTimestamp, $urnaOpenedDateTime) {
                    // VERIFICACIÓN DE SEGURIDAD: ¿Ya existe el voto?
                    $votoExistente = Voto::where('votacion_id', $this->votacion->id)
                        ->where('usuario_id', $this->user->id)
                        ->first();
                    
                    if ($votoExistente) {
                        Log::info('ProcessVoteJob: Voto ya existe en retry, retornando existente', [
                            'voto_id' => $votoExistente->id,
                            'user_id' => $this->user->id,
                            'votacion_id' => $this->votacion->id
                        ]);
                        return $votoExistente; // Retornar el existente, NO crear duplicado
                    }
                    
                    // GENERAR TOKEN ÚNICO para este intento específico
                    // El salt aleatorio garantiza un token diferente cada vez
                    $token = TokenService::generateSignedToken(
                        $this->votacion->id,
                        $this->respuestas,
                        $voteTimestamp->toISOString(),  // created_at del voto
                        $urnaOpenedDateTime ? $urnaOpenedDateTime->toISOString() : null  // urna_opened_at
                    );
                    
                    Log::info('ProcessVoteJob: Creando voto con token único', [
                        'token_first_50' => substr($token, 0, 50),
                        'user_id' => $this->user->id
                    ]);
                    
                    return Voto::create([
                        'votacion_id' => $this->votacion->id,
                        'usuario_id' => $this->user->id,
                        'token_unico' => $token,
                        'urna_opened_at' => $urnaOpenedDateTime,
                        'respuestas' => $this->respuestas,
                        'ip_address' => $this->ipAddress,
                        'user_agent' => $this->userAgent,
                    ]);
                }, 100); // Reintentar después de 100ms si hay lock
                
                // Log final del voto procesado
                Log::info('ProcessVoteJob: Voto procesado exitosamente', [
                    'voto_id' => $voto->id,
                    'urna_opened_at_saved' => $voto->urna_opened_at ? $voto->urna_opened_at->toISOString() : 'null',
                    'created_at' => $voto->created_at->toISOString(),
                    'token_first_50' => substr($voto->token_unico, 0, 50)
                ]);

                // Cerrar la sesión de urna SOLO después de guardar el voto exitosamente
                if ($this->urnaSessionId) {
                    $urnaSession = \App\Models\Votaciones\UrnaSession::find($this->urnaSessionId);
                    if ($urnaSession && $urnaSession->status === 'active') {
                        $urnaSession->closeByVote();
                        Log::info('Sesión de urna cerrada exitosamente después de guardar voto', [
                            'urna_session_id' => $this->urnaSessionId,
                            'voto_id' => $voto->id
                        ]);
                    }
                }

                // Cargar la relación de categoría para las notificaciones
                $this->votacion->load('categoria');

                // Despachar jobs de notificación EN JOBS SEPARADOS
                // NO especificar cola - los jobs ya tienen configurada su cola correcta
                // (otp-emails y otp-whatsapp) en WithRateLimiting trait
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
            });

        } catch (\Illuminate\Database\QueryException $e) {
            // Si es un error de constraint único, verificar si el voto ya existe
            if ($e->getCode() === '23000') {
                // Verificar si el voto ya fue guardado (por un intento anterior)
                $votoExistente = $this->votacion->votos()->where('usuario_id', $this->user->id)->first();
                
                if ($votoExistente) {
                    // El voto SÍ existe, marcar como exitoso y cerrar sesión
                    if ($this->urnaSessionId) {
                        $urnaSession = \App\Models\Votaciones\UrnaSession::find($this->urnaSessionId);
                        if ($urnaSession && $urnaSession->status === 'active') {
                            $urnaSession->closeByVote();
                        }
                    }
                    
                    Cache::put($this->cacheKey, 'completed', 300);
                    Cache::put("vote_id_{$this->votacion->id}_{$this->user->id}", $votoExistente->id, 3600);
                    
                    Log::info('Constraint único pero voto existe - marcando como exitoso', [
                        'user_id' => $this->user->id,
                        'votacion_id' => $this->votacion->id,
                        'voto_id' => $votoExistente->id
                    ]);
                    return; // No reintentar, el voto ya está guardado
                }
                
                // Si no existe el voto, entonces sí es un error real
                Cache::put($this->cacheKey, 'error', 60);
                Log::error('Constraint único sin voto existente - error real', [
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