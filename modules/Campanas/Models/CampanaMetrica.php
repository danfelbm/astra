<?php

namespace Modules\Campanas\Models;

use Modules\Core\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class CampanaMetrica extends Model
{
    use HasFactory, HasTenant;

    /**
     * Los atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'campana_id',
        // Métricas generales
        'total_destinatarios',
        'total_enviados',
        'total_pendientes',
        'total_fallidos',
        // Métricas de email
        'emails_enviados',
        'emails_abiertos',
        'emails_con_click',
        'emails_rebotados',
        'total_clicks',
        'tasa_apertura',
        'tasa_click',
        'tasa_rebote',
        // Métricas de WhatsApp
        'whatsapp_enviados',
        'whatsapp_fallidos',
        'whatsapp_entregados',
        // Tiempos
        'tiempo_promedio_apertura', // en minutos
        'tiempo_promedio_click', // en minutos
        // Metadata
        'ultima_actualizacion',
        'metadata',
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_destinatarios' => 'integer',
        'total_enviados' => 'integer',
        'total_pendientes' => 'integer',
        'total_fallidos' => 'integer',
        'emails_enviados' => 'integer',
        'emails_abiertos' => 'integer',
        'emails_con_click' => 'integer',
        'emails_rebotados' => 'integer',
        'total_clicks' => 'integer',
        'tasa_apertura' => 'float',
        'tasa_click' => 'float',
        'tasa_rebote' => 'float',
        'whatsapp_enviados' => 'integer',
        'whatsapp_fallidos' => 'integer',
        'whatsapp_entregados' => 'integer',
        'tiempo_promedio_apertura' => 'float',
        'tiempo_promedio_click' => 'float',
        'ultima_actualizacion' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Valores por defecto para atributos
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'total_destinatarios' => 0,
        'total_enviados' => 0,
        'total_pendientes' => 0,
        'total_fallidos' => 0,
        'emails_enviados' => 0,
        'emails_abiertos' => 0,
        'emails_con_click' => 0,
        'emails_rebotados' => 0,
        'total_clicks' => 0,
        'tasa_apertura' => 0,
        'tasa_click' => 0,
        'tasa_rebote' => 0,
        'whatsapp_enviados' => 0,
        'whatsapp_fallidos' => 0,
        'whatsapp_entregados' => 0,
        'tiempo_promedio_apertura' => 0,
        'tiempo_promedio_click' => 0,
        'metadata' => '{}',
    ];

    /**
     * Obtener la campaña
     */
    public function campana(): BelongsTo
    {
        return $this->belongsTo(Campana::class);
    }

    /**
     * Actualizar métricas desde los envíos
     *
     * @return void
     */
    public function actualizarMetricas(): void
    {
        $campana = $this->campana;
        
        if (!$campana) {
            return;
        }
        
        // Obtener datos agregados de los envíos
        $envios = $campana->envios();
        
        // Métricas generales
        $this->total_destinatarios = $campana->contarDestinatarios();
        $this->total_enviados = $envios->enviados()->count();
        $this->total_pendientes = $envios->pendientes()->count();
        $this->total_fallidos = $envios->fallidos()->count();
        
        // Métricas de email
        if ($campana->usaEmail()) {
            // Crear nuevas queries para cada métrica para evitar mutación
            $this->emails_enviados = $campana->envios()->tipoEmail()->enviados()->count();
            $this->emails_abiertos = $campana->envios()->tipoEmail()->abiertos()->count();
            $this->emails_con_click = $campana->envios()->tipoEmail()->conClicks()->count();
            $this->emails_rebotados = $campana->envios()->tipoEmail()->fallidos()->count();
            $this->total_clicks = $campana->envios()->tipoEmail()->sum('clicks_count');
            
            // Calcular tasas
            if ($this->emails_enviados > 0) {
                $this->tasa_apertura = round(($this->emails_abiertos / $this->emails_enviados) * 100, 2);
                $this->tasa_click = round(($this->emails_con_click / $this->emails_enviados) * 100, 2);
                $this->tasa_rebote = round(($this->emails_rebotados / $this->emails_enviados) * 100, 2);
            }
            
            // Calcular tiempos promedio
            $this->calcularTiemposPromedio();
        }
        
        // Métricas de WhatsApp
        if ($campana->usaWhatsApp()) {
            // Crear nuevas queries para cada métrica para evitar mutación
            $this->whatsapp_enviados = $campana->envios()->tipoWhatsApp()->enviados()->count();
            $this->whatsapp_fallidos = $campana->envios()->tipoWhatsApp()->fallidos()->count();
            $this->whatsapp_entregados = $this->whatsapp_enviados - $this->whatsapp_fallidos;
        }
        
        // Actualizar metadata
        $this->actualizarMetadata();
        
        // Marcar última actualización
        $this->ultima_actualizacion = now();
        
        $this->save();
    }

    /**
     * Calcular tiempos promedio de apertura y click
     *
     * @return void
     */
    protected function calcularTiemposPromedio(): void
    {
        $enviosConApertura = $this->campana->envios()
            ->tipoEmail()
            ->whereNotNull('fecha_abierto')
            ->whereNotNull('fecha_enviado')
            ->get();
        
        if ($enviosConApertura->count() > 0) {
            $tiemposTotales = 0;
            
            foreach ($enviosConApertura as $envio) {
                $tiemposTotales += $envio->getTiempoHastaApertura() ?? 0;
            }
            
            $this->tiempo_promedio_apertura = round($tiemposTotales / $enviosConApertura->count(), 2);
        }
        
        // Tiempo promedio hasta el primer click
        $enviosConClick = $this->campana->envios()
            ->tipoEmail()
            ->whereNotNull('fecha_primer_click')
            ->whereNotNull('fecha_enviado')
            ->get();
        
        if ($enviosConClick->count() > 0) {
            $tiemposTotales = 0;
            
            foreach ($enviosConClick as $envio) {
                $tiemposTotales += $envio->fecha_enviado->diffInMinutes($envio->fecha_primer_click);
            }
            
            $this->tiempo_promedio_click = round($tiemposTotales / $enviosConClick->count(), 2);
        }
    }

    /**
     * Actualizar metadata con información adicional
     *
     * @return void
     */
    protected function actualizarMetadata(): void
    {
        $metadata = $this->metadata ?? [];
        
        // URLs más clickeadas
        $urlsClickeadas = [];
        $enviosConClicks = $this->campana->envios()
            ->tipoEmail()
            ->conClicks()
            ->get();
        
        foreach ($enviosConClicks as $envio) {
            $clicks = $envio->metadata['clicks'] ?? [];
            
            foreach ($clicks as $click) {
                $url = $click['url'] ?? 'unknown';
                
                if (!isset($urlsClickeadas[$url])) {
                    $urlsClickeadas[$url] = 0;
                }
                
                $urlsClickeadas[$url]++;
            }
        }
        
        arsort($urlsClickeadas);
        $metadata['top_urls'] = array_slice($urlsClickeadas, 0, 10, true);
        
        // Distribución por hora del día (aperturas)
        $distribucionHoras = array_fill(0, 24, 0);
        $aperturas = $this->campana->envios()
            ->tipoEmail()
            ->whereNotNull('fecha_abierto')
            ->get();
        
        foreach ($aperturas as $envio) {
            $hora = $envio->fecha_abierto->hour;
            $distribucionHoras[$hora]++;
        }
        
        $metadata['distribucion_horas'] = $distribucionHoras;
        
        // Dispositivos/clientes de email
        $dispositivos = [];
        foreach ($this->campana->envios as $envio) {
            $device = $envio->getDispositivoInfo();
            
            if ($device) {
                $tipo = $device['type'] ?? 'unknown';
                
                if (!isset($dispositivos[$tipo])) {
                    $dispositivos[$tipo] = 0;
                }
                
                $dispositivos[$tipo]++;
            }
        }
        
        $metadata['dispositivos'] = $dispositivos;
        
        $this->metadata = $metadata;
    }

    /**
     * Obtener métricas con cache
     *
     * @param Campana $campana
     * @return self
     */
    public static function obtenerOActualizar(Campana $campana): self
    {
        $cacheKey = "campana_metricas_{$campana->id}";
        $cacheDuration = config('campanas.metrics.cache_duration', 300);
        
        return Cache::remember($cacheKey, $cacheDuration, function () use ($campana) {
            $metrica = $campana->metrica;
            
            if (!$metrica) {
                $metrica = self::create([
                    'campana_id' => $campana->id,
                    'tenant_id' => $campana->tenant_id,
                ]);
            }
            
            // Actualizar si es necesario
            $minutosDesdeActualizacion = $metrica->ultima_actualizacion 
                ? $metrica->ultima_actualizacion->diffInMinutes(now())
                : PHP_INT_MAX;
            
            if ($minutosDesdeActualizacion > 5) {
                $metrica->actualizarMetricas();
            }
            
            return $metrica;
        });
    }

    /**
     * Limpiar cache de métricas
     *
     * @return void
     */
    public function limpiarCache(): void
    {
        $cacheKey = "campana_metricas_{$this->campana_id}";
        Cache::forget($cacheKey);
    }

    /**
     * Obtener resumen de métricas
     *
     * @return array
     */
    public function getResumen(): array
    {
        return [
            'generales' => [
                'total_destinatarios' => $this->total_destinatarios,
                'total_enviados' => $this->total_enviados,
                'total_pendientes' => $this->total_pendientes,
                'total_fallidos' => $this->total_fallidos,
                'progreso' => $this->total_destinatarios > 0 
                    ? round(($this->total_enviados / $this->total_destinatarios) * 100, 2)
                    : 0,
            ],
            'email' => [
                'enviados' => $this->emails_enviados,
                'abiertos' => $this->emails_abiertos,
                'clicks' => $this->emails_con_click,
                'rebotados' => $this->emails_rebotados,
                'total_clicks' => $this->total_clicks,
                'tasa_apertura' => $this->tasa_apertura . '%',
                'tasa_click' => $this->tasa_click . '%',
                'tasa_rebote' => $this->tasa_rebote . '%',
                'tiempo_promedio_apertura' => $this->formatearTiempo($this->tiempo_promedio_apertura),
            ],
            'whatsapp' => [
                'enviados' => $this->whatsapp_enviados,
                'entregados' => $this->whatsapp_entregados,
                'fallidos' => $this->whatsapp_fallidos,
                'tasa_entrega' => $this->whatsapp_enviados > 0
                    ? round(($this->whatsapp_entregados / $this->whatsapp_enviados) * 100, 2) . '%'
                    : '0%',
            ],
            'ultima_actualizacion' => $this->ultima_actualizacion?->diffForHumans(),
        ];
    }

    /**
     * Formatear tiempo en minutos a formato legible
     *
     * @param float $minutos
     * @return string
     */
    protected function formatearTiempo(float $minutos): string
    {
        if ($minutos < 60) {
            return round($minutos) . ' min';
        }
        
        $horas = floor($minutos / 60);
        $minutosRestantes = round($minutos % 60);
        
        if ($minutosRestantes > 0) {
            return "{$horas}h {$minutosRestantes}min";
        }
        
        return "{$horas}h";
    }
}