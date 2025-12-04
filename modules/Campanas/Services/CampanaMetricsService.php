<?php

namespace Modules\Campanas\Services;

use Modules\Campanas\Models\Campana;
use Modules\Campanas\Models\CampanaMetrica;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CampanaMetricsService
{
    /**
     * Obtener métricas actualizadas de una campaña
     */
    public function getMetricas(Campana $campana): array
    {
        try {
            $metrica = $campana->metrica;
            
            // Si no hay métricas, crear valores por defecto para campañas nuevas
            if (!$metrica) {
                return [
                    'success' => true,
                    'metricas' => $this->getMetricasDefecto(),
                    'raw' => []
                ];
            }
            
            // Convertir estructura anidada a estructura plana para el frontend
            $resumen = $metrica->getResumen();

            // Calcular métricas separadas de WhatsApp (individual vs grupos)
            $waIndividualEnviados = $campana->envios()
                ->where('tipo', 'whatsapp')
                ->whereIn('estado', ['enviado', 'abierto', 'click'])
                ->count();
            $waIndividualFallidos = $campana->envios()
                ->where('tipo', 'whatsapp')
                ->where('estado', 'fallido')
                ->count();
            $waIndividualTotal = $campana->envios()
                ->where('tipo', 'whatsapp')
                ->count();

            $waGruposEnviados = $campana->envios()
                ->where('tipo', 'whatsapp_group')
                ->whereIn('estado', ['enviado', 'abierto', 'click'])
                ->count();
            $waGruposFallidos = $campana->envios()
                ->where('tipo', 'whatsapp_group')
                ->where('estado', 'fallido')
                ->count();
            $waGruposTotal = $campana->envios()
                ->where('tipo', 'whatsapp_group')
                ->count();

            return [
                'success' => true,
                'metricas' => [
                    // Métricas generales (planas)
                    'total_destinatarios' => $resumen['generales']['total_destinatarios'] ?? 0,
                    'total_enviados' => $resumen['generales']['total_enviados'] ?? 0,
                    'total_pendientes' => $resumen['generales']['total_pendientes'] ?? 0,
                    'total_fallidos' => $resumen['generales']['total_fallidos'] ?? 0,
                    'progreso' => $resumen['generales']['progreso'] ?? 0,

                    // Métricas de email (planas)
                    'emails_enviados' => $resumen['email']['enviados'] ?? 0,
                    'emails_abiertos' => $resumen['email']['abiertos'] ?? 0,
                    'emails_con_click' => $resumen['email']['clicks'] ?? 0,
                    'emails_rebotados' => $resumen['email']['rebotados'] ?? 0,
                    'total_clicks' => $resumen['email']['total_clicks'] ?? 0,
                    'tasa_apertura' => floatval(str_replace('%', '', $resumen['email']['tasa_apertura'] ?? '0')),
                    'tasa_click' => floatval(str_replace('%', '', $resumen['email']['tasa_click'] ?? '0')),
                    'tasa_rebote' => floatval(str_replace('%', '', $resumen['email']['tasa_rebote'] ?? '0')),
                    'tiempo_promedio_apertura' => intval(str_replace(' min', '', $resumen['email']['tiempo_promedio_apertura'] ?? '0')),
                    'tiempo_promedio_click' => 0,

                    // Métricas de WhatsApp (totales)
                    'whatsapp_enviados' => $resumen['whatsapp']['enviados'] ?? 0,
                    'whatsapp_entregados' => $resumen['whatsapp']['entregados'] ?? 0,
                    'whatsapp_fallidos' => $resumen['whatsapp']['fallidos'] ?? 0,
                    'whatsapp_tasa_entrega' => floatval(str_replace('%', '', $resumen['whatsapp']['tasa_entrega'] ?? '0')),

                    // Métricas de WhatsApp Individual (separadas)
                    'whatsapp_individual_enviados' => $waIndividualEnviados,
                    'whatsapp_individual_fallidos' => $waIndividualFallidos,
                    'whatsapp_individual_total' => $waIndividualTotal,

                    // Métricas de WhatsApp Grupos (separadas)
                    'whatsapp_grupos_enviados' => $waGruposEnviados,
                    'whatsapp_grupos_fallidos' => $waGruposFallidos,
                    'whatsapp_grupos_total' => $waGruposTotal,

                    // Metadata
                    'ultima_actualizacion' => $resumen['ultima_actualizacion'] ?? null,
                ],
                'raw' => $metrica->toArray()
            ];
        } catch (\Exception $e) {
            Log::error('Error obteniendo métricas', [
                'error' => $e->getMessage(),
                'campana_id' => $campana->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al obtener métricas: ' . $e->getMessage(),
                'metricas' => $this->getMetricasDefecto()
            ];
        }
    }

    /**
     * Obtener métricas por defecto para campañas nuevas
     */
    private function getMetricasDefecto(): array
    {
        return [
            'total_destinatarios' => 0,
            'total_enviados' => 0,
            'total_pendientes' => 0,
            'total_fallidos' => 0,
            'progreso' => 0,
            'emails_enviados' => 0,
            'emails_abiertos' => 0,
            'emails_con_click' => 0,
            'emails_rebotados' => 0,
            'total_clicks' => 0,
            'tasa_apertura' => 0,
            'tasa_click' => 0,
            'tasa_rebote' => 0,
            'tiempo_promedio_apertura' => 0,
            'tiempo_promedio_click' => 0,
            // WhatsApp totales
            'whatsapp_enviados' => 0,
            'whatsapp_entregados' => 0,
            'whatsapp_fallidos' => 0,
            'whatsapp_tasa_entrega' => 0,
            // WhatsApp Individual
            'whatsapp_individual_enviados' => 0,
            'whatsapp_individual_fallidos' => 0,
            'whatsapp_individual_total' => 0,
            // WhatsApp Grupos
            'whatsapp_grupos_enviados' => 0,
            'whatsapp_grupos_fallidos' => 0,
            'whatsapp_grupos_total' => 0,
            'ultima_actualizacion' => null,
        ];
    }

    /**
     * Actualizar métricas de una campaña
     */
    public function actualizarMetricas(Campana $campana): array
    {
        try {
            $metrica = $campana->metrica;
            
            if (!$metrica) {
                $metrica = CampanaMetrica::create([
                    'campana_id' => $campana->id,
                    'tenant_id' => $campana->tenant_id,
                ]);
            }
            
            $metrica->actualizarMetricas();
            $metrica->limpiarCache();
            
            return [
                'success' => true,
                'message' => 'Métricas actualizadas exitosamente',
                'metricas' => $metrica->getResumen()
            ];
        } catch (\Exception $e) {
            Log::error('Error actualizando métricas', [
                'error' => $e->getMessage(),
                'campana_id' => $campana->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al actualizar métricas: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener métricas en tiempo real
     */
    public function getMetricasRealtime(Campana $campana): array
    {
        try {
            // No usar cache para métricas en tiempo real
            $metrica = $campana->metrica;
            
            if (!$metrica) {
                $metrica = CampanaMetrica::create([
                    'campana_id' => $campana->id,
                    'tenant_id' => $campana->tenant_id,
                ]);
            }
            
            // Actualizar métricas antes de retornar
            $metrica->actualizarMetricas();
            
            // Agregar información adicional de tiempo real
            $enviosUltimaHora = $campana->envios()
                ->where('fecha_enviado', '>=', now()->subHour())
                ->count();
            
            $aperturaUltimaHora = $campana->envios()
                ->where('fecha_abierto', '>=', now()->subHour())
                ->count();
            
            $clicksUltimaHora = $campana->envios()
                ->where('fecha_ultimo_click', '>=', now()->subHour())
                ->sum('clicks_count');
            
            $resumen = $metrica->getResumen();
            $resumen['realtime'] = [
                'envios_ultima_hora' => $enviosUltimaHora,
                'aperturas_ultima_hora' => $aperturaUltimaHora,
                'clicks_ultima_hora' => $clicksUltimaHora,
                'velocidad_envio' => $this->calcularVelocidadEnvio($campana),
                'tiempo_estimado' => $this->calcularTiempoEstimado($campana),
            ];
            
            return [
                'success' => true,
                'metricas' => $resumen
            ];
        } catch (\Exception $e) {
            Log::error('Error obteniendo métricas en tiempo real', [
                'error' => $e->getMessage(),
                'campana_id' => $campana->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al obtener métricas en tiempo real: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener comparación con campañas anteriores
     */
    public function getComparacion(Campana $campana): array
    {
        try {
            // Obtener campañas anteriores del mismo tipo
            $campanasAnteriores = Campana::where('tipo', $campana->tipo)
                ->where('estado', 'completada')
                ->where('id', '!=', $campana->id)
                ->with('metrica')
                ->latest()
                ->limit(5)
                ->get();
            
            if ($campanasAnteriores->isEmpty()) {
                return [
                    'success' => true,
                    'comparacion' => null,
                    'message' => 'No hay campañas anteriores para comparar'
                ];
            }
            
            // Calcular promedios
            $promedios = [
                'tasa_apertura' => 0,
                'tasa_click' => 0,
                'tasa_rebote' => 0,
                'tiempo_promedio_apertura' => 0,
            ];
            
            $contador = 0;
            foreach ($campanasAnteriores as $campaniaAnterior) {
                if ($campaniaAnterior->metrica) {
                    $promedios['tasa_apertura'] += $campaniaAnterior->metrica->tasa_apertura;
                    $promedios['tasa_click'] += $campaniaAnterior->metrica->tasa_click;
                    $promedios['tasa_rebote'] += $campaniaAnterior->metrica->tasa_rebote;
                    $promedios['tiempo_promedio_apertura'] += $campaniaAnterior->metrica->tiempo_promedio_apertura;
                    $contador++;
                }
            }
            
            if ($contador > 0) {
                foreach ($promedios as $key => $value) {
                    $promedios[$key] = round($value / $contador, 2);
                }
            }
            
            // Comparar con la campaña actual
            $metricaActual = $campana->metrica;
            
            if (!$metricaActual) {
                return [
                    'success' => true,
                    'comparacion' => [
                        'promedios' => $promedios,
                        'actual' => null,
                        'diferencias' => null,
                    ]
                ];
            }
            
            $diferencias = [
                'tasa_apertura' => round($metricaActual->tasa_apertura - $promedios['tasa_apertura'], 2),
                'tasa_click' => round($metricaActual->tasa_click - $promedios['tasa_click'], 2),
                'tasa_rebote' => round($metricaActual->tasa_rebote - $promedios['tasa_rebote'], 2),
                'tiempo_promedio_apertura' => round($metricaActual->tiempo_promedio_apertura - $promedios['tiempo_promedio_apertura'], 2),
            ];
            
            return [
                'success' => true,
                'comparacion' => [
                    'promedios' => $promedios,
                    'actual' => [
                        'tasa_apertura' => $metricaActual->tasa_apertura,
                        'tasa_click' => $metricaActual->tasa_click,
                        'tasa_rebote' => $metricaActual->tasa_rebote,
                        'tiempo_promedio_apertura' => $metricaActual->tiempo_promedio_apertura,
                    ],
                    'diferencias' => $diferencias,
                    'campanas_comparadas' => $contador,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error obteniendo comparación', [
                'error' => $e->getMessage(),
                'campana_id' => $campana->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al obtener comparación: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calcular velocidad de envío actual
     */
    private function calcularVelocidadEnvio(Campana $campana): float
    {
        // Obtener envíos de los últimos 5 minutos
        $enviosRecientes = $campana->envios()
            ->where('fecha_enviado', '>=', now()->subMinutes(5))
            ->count();
        
        // Calcular envíos por minuto
        return round($enviosRecientes / 5, 2);
    }

    /**
     * Calcular tiempo estimado de finalización
     */
    private function calcularTiempoEstimado(Campana $campana): ?string
    {
        $pendientes = $campana->envios()->pendientes()->count();
        
        if ($pendientes === 0) {
            return 'Completado';
        }
        
        $velocidad = $this->calcularVelocidadEnvio($campana);
        
        if ($velocidad === 0.0) {
            return 'Calculando...';
        }
        
        $minutosRestantes = round($pendientes / $velocidad);
        
        if ($minutosRestantes < 60) {
            return "{$minutosRestantes} minutos";
        }
        
        $horas = floor($minutosRestantes / 60);
        $minutos = $minutosRestantes % 60;
        
        return "{$horas}h {$minutos}min";
    }

    /**
     * Obtener dashboard de métricas
     */
    public function getDashboard(): array
    {
        try {
            $campanasActivas = Campana::whereIn('estado', ['enviando', 'programada'])->count();
            $campanasCompletadas = Campana::where('estado', 'completada')->count();
            
            // Métricas del último mes
            $campanasUltimoMes = Campana::where('created_at', '>=', now()->subMonth())
                ->with('metrica')
                ->get();
            
            $totalEnviados = 0;
            $totalAbiertos = 0;
            $totalClicks = 0;
            
            foreach ($campanasUltimoMes as $campana) {
                if ($campana->metrica) {
                    $totalEnviados += $campana->metrica->total_enviados;
                    $totalAbiertos += $campana->metrica->emails_abiertos;
                    $totalClicks += $campana->metrica->total_clicks;
                }
            }
            
            // Mejores campañas
            $mejoresCampanas = Campana::where('estado', 'completada')
                ->with('metrica')
                ->get()
                ->filter(function ($campana) {
                    return $campana->metrica && $campana->metrica->tasa_apertura > 0;
                })
                ->sortByDesc(function ($campana) {
                    return $campana->metrica->tasa_apertura;
                })
                ->take(5)
                ->values();
            
            return [
                'success' => true,
                'dashboard' => [
                    'campanas_activas' => $campanasActivas,
                    'campanas_completadas' => $campanasCompletadas,
                    'ultimo_mes' => [
                        'total_campanas' => $campanasUltimoMes->count(),
                        'total_enviados' => $totalEnviados,
                        'total_abiertos' => $totalAbiertos,
                        'total_clicks' => $totalClicks,
                        'tasa_apertura_promedio' => $totalEnviados > 0 
                            ? round(($totalAbiertos / $totalEnviados) * 100, 2)
                            : 0,
                    ],
                    'mejores_campanas' => $mejoresCampanas->map(function ($campana) {
                        return [
                            'id' => $campana->id,
                            'nombre' => $campana->nombre,
                            'tasa_apertura' => $campana->metrica->tasa_apertura,
                            'tasa_click' => $campana->metrica->tasa_click,
                        ];
                    }),
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error obteniendo dashboard de métricas', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al obtener dashboard: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener tendencias de métricas de la campaña
     */
    public function getTrends(Campana $campana): array
    {
        try {
            // Por ahora retornar estructura básica - se puede expandir después
            return [
                'success' => true,
                'tendencias' => [
                    'aperturas_por_hora' => [],
                    'clicks_por_hora' => [],
                    'envios_por_hora' => [],
                    'tendencia_general' => 'estable'
                ],
                'message' => 'Tendencias calculadas (funcionalidad básica)'
            ];
        } catch (\Exception $e) {
            Log::error('Error obteniendo tendencias', [
                'error' => $e->getMessage(),
                'campana_id' => $campana->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al obtener tendencias: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Limpiar cache de todas las métricas
     */
    public function limpiarCacheTotal(): void
    {
        try {
            $campanas = Campana::with('metrica')->get();
            
            foreach ($campanas as $campana) {
                if ($campana->metrica) {
                    $campana->metrica->limpiarCache();
                }
            }
            
            Log::info('Cache de métricas limpiado completamente');
        } catch (\Exception $e) {
            Log::error('Error limpiando cache de métricas', [
                'error' => $e->getMessage()
            ]);
        }
    }
}