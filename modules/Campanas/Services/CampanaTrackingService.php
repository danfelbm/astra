<?php

namespace Modules\Campanas\Services;

use Modules\Campanas\Models\CampanaEnvio;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class CampanaTrackingService
{
    /**
     * Procesar apertura de email (pixel tracking)
     */
    public function trackOpen(string $trackingId, Request $request): array
    {
        try {
            $envio = CampanaEnvio::where('tracking_id', $trackingId)
                ->where('tipo', 'email')
                ->first();
            
            if (!$envio) {
                Log::warning('Tracking ID no encontrado para apertura', [
                    'tracking_id' => $trackingId
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Tracking ID no válido'
                ];
            }
            
            // TODO en UNA SOLA operación atómica para aperturas
            $ahora = now();
            $metadata = $envio->metadata ?? [];
            
            // Limpiar datos para evitar problemas de UTF-8
            $cleanUserAgent = mb_convert_encoding($request->userAgent() ?? '', 'UTF-8', 'UTF-8');
            $cleanIp = $request->ip() ?? 'unknown';
            
            // Mantener info del dispositivo de la primera apertura
            if (!isset($metadata['device'])) {
                $metadata['device'] = [
                    'user_agent' => $cleanUserAgent,
                    'ip' => $cleanIp,
                    'opened_at' => $ahora->toIso8601String(),
                ];
            }
            
            // Guardar historial de todas las aperturas
            if (!isset($metadata['aperturas'])) {
                $metadata['aperturas'] = [];
            }
            
            $metadata['aperturas'][] = [
                'timestamp' => $ahora->toIso8601String(),
                'user_agent' => $cleanUserAgent,
                'ip' => $cleanIp,
            ];
            
            // Preparar TODOS los campos a actualizar
            $updates = [
                'aperturas_count' => $envio->aperturas_count + 1,
                'metadata' => $metadata
            ];
            
            // Si es la primera apertura
            if (!$envio->fecha_abierto) {
                $updates['fecha_abierto'] = $ahora;
                $updates['estado'] = 'abierto';
            }
            
            // Si ya hay clics, mantener estado como 'click'
            if ($envio->estado === 'click') {
                unset($updates['estado']);
            }
            
            // UN SOLO UPDATE ATÓMICO
            $envio->update($updates);
            
            // Actualizar métricas de la campaña
            $this->actualizarMetricasCampana($envio->campana_id);
            
            Log::info('Email abierto registrado', [
                'tracking_id' => $trackingId,
                'campana_id' => $envio->campana_id,
                'user_id' => $envio->user_id
            ]);
            
            return [
                'success' => true,
                'message' => 'Apertura registrada'
            ];
        } catch (\Exception $e) {
            Log::error('Error registrando apertura de email', [
                'error' => $e->getMessage(),
                'tracking_id' => $trackingId
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al registrar apertura'
            ];
        }
    }

    /**
     * Procesar click en enlace
     */
    public function trackClick(string $trackingId, string $encodedUrl, Request $request): array
    {
        try {
            $envio = CampanaEnvio::where('tracking_id', $trackingId)
                ->where('tipo', 'email')
                ->first();
            
            if (!$envio) {
                Log::warning('Tracking ID no encontrado para click', [
                    'tracking_id' => $trackingId
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Tracking ID no válido',
                    'url' => null
                ];
            }
            
            // Decodificar URL
            $url = base64_decode($encodedUrl);
            
            if (!$url) {
                return [
                    'success' => false,
                    'message' => 'URL no válida',
                    'url' => null
                ];
            }
            
            // TODO en UNA SOLA operación atómica
            $ahora = now();
            $metadata = $envio->metadata ?? [];
            
            // Limpiar datos para evitar problemas de UTF-8
            $cleanUrl = mb_convert_encoding($url, 'UTF-8', 'UTF-8');
            $cleanUserAgent = mb_convert_encoding($request->userAgent() ?? '', 'UTF-8', 'UTF-8');
            $cleanIp = $request->ip() ?? 'unknown';
            
            // Guardar en metadata.clicks (formato simple)
            if (!isset($metadata['clicks'])) {
                $metadata['clicks'] = [];
            }
            $metadata['clicks'][] = [
                'url' => $cleanUrl,
                'timestamp' => $ahora->toIso8601String(),
            ];
            
            // Guardar en metadata.clicks_detail (formato completo)
            if (!isset($metadata['clicks_detail'])) {
                $metadata['clicks_detail'] = [];
            }
            $metadata['clicks_detail'][] = [
                'url' => $cleanUrl,
                'clicked_at' => $ahora->toIso8601String(),
                'user_agent' => $cleanUserAgent,
                'ip' => $cleanIp,
            ];
            
            // Preparar TODOS los campos a actualizar
            $updates = [
                'estado' => 'click',
                'clicks_count' => $envio->clicks_count + 1,
                'fecha_ultimo_click' => $ahora,
                'metadata' => $metadata
            ];
            
            // Si es el primer click
            if (!$envio->fecha_primer_click) {
                $updates['fecha_primer_click'] = $ahora;
            }
            
            // Si nunca se marcó como abierto, marcarlo ahora
            if (!$envio->fecha_abierto) {
                $updates['fecha_abierto'] = $ahora;
            }
            
            // UN SOLO UPDATE ATÓMICO
            $envio->update($updates);
            
            // Actualizar métricas de la campaña
            $this->actualizarMetricasCampana($envio->campana_id);
            
            Log::info('Click registrado', [
                'tracking_id' => $trackingId,
                'campana_id' => $envio->campana_id,
                'user_id' => $envio->user_id,
                'url' => $url
            ]);
            
            return [
                'success' => true,
                'message' => 'Click registrado',
                'url' => $url
            ];
        } catch (\Exception $e) {
            Log::error('Error registrando click', [
                'error' => $e->getMessage(),
                'tracking_id' => $trackingId
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al registrar click',
                'url' => null
            ];
        }
    }

    /**
     * Obtener estadísticas de tracking de una campaña
     */
    public function getEstadisticas(int $campanaId): array
    {
        try {
            $envios = CampanaEnvio::where('campana_id', $campanaId)
                ->where('tipo', 'email')
                ->get();
            
            $total = $envios->count();
            $enviados = $envios->where('estado', '!=', 'pendiente')->count();
            $abiertos = $envios->whereIn('estado', ['abierto', 'click'])->count();
            $conClicks = $envios->where('estado', 'click')->count();
            $fallidos = $envios->where('estado', 'fallido')->count();
            
            // Calcular tasas
            $tasaApertura = $enviados > 0 ? round(($abiertos / $enviados) * 100, 2) : 0;
            $tasaClick = $enviados > 0 ? round(($conClicks / $enviados) * 100, 2) : 0;
            $tasaRebote = $enviados > 0 ? round(($fallidos / $enviados) * 100, 2) : 0;
            
            // Obtener URLs más clickeadas
            $urlsClickeadas = [];
            foreach ($envios->where('estado', 'click') as $envio) {
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
            $topUrls = array_slice($urlsClickeadas, 0, 10, true);
            
            // Distribución temporal de aperturas
            $distribucionHoras = array_fill(0, 24, 0);
            foreach ($envios->whereNotNull('fecha_abierto') as $envio) {
                if ($envio->fecha_abierto) {
                    $hora = $envio->fecha_abierto->hour;
                    $distribucionHoras[$hora]++;
                }
            }
            
            return [
                'totales' => [
                    'total' => $total,
                    'enviados' => $enviados,
                    'abiertos' => $abiertos,
                    'con_clicks' => $conClicks,
                    'fallidos' => $fallidos,
                ],
                'tasas' => [
                    'apertura' => $tasaApertura,
                    'click' => $tasaClick,
                    'rebote' => $tasaRebote,
                ],
                'top_urls' => $topUrls,
                'distribucion_horas' => $distribucionHoras,
            ];
        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas de tracking', [
                'error' => $e->getMessage(),
                'campana_id' => $campanaId
            ]);
            
            return [
                'totales' => [
                    'total' => 0,
                    'enviados' => 0,
                    'abiertos' => 0,
                    'con_clicks' => 0,
                    'fallidos' => 0,
                ],
                'tasas' => [
                    'apertura' => 0,
                    'click' => 0,
                    'rebote' => 0,
                ],
                'top_urls' => [],
                'distribucion_horas' => array_fill(0, 24, 0),
            ];
        }
    }

    /**
     * Actualizar métricas de la campaña
     */
    private function actualizarMetricasCampana(int $campanaId): void
    {
        try {
            $campana = \Modules\Campanas\Models\Campana::find($campanaId);
            
            if (!$campana) {
                return;
            }
            
            // Crear métricas si no existen
            if (!$campana->metrica) {
                \Modules\Campanas\Models\CampanaMetrica::create([
                    'campana_id' => $campana->id,
                    'tenant_id' => $campana->tenant_id,
                ]);
                
                // Recargar la relación
                $campana->load('metrica');
            }
            
            // Ahora actualizar las métricas
            if ($campana->metrica) {
                $campana->metrica->actualizarMetricas();
                $campana->metrica->limpiarCache();
            }
        } catch (\Exception $e) {
            Log::error('Error actualizando métricas de campaña', [
                'error' => $e->getMessage(),
                'campana_id' => $campanaId
            ]);
        }
    }

    /**
     * Generar pixel de tracking
     */
    public function generateTrackingPixel(): string
    {
        // Retornar imagen transparente de 1x1 pixel
        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        
        return $image;
    }

    /**
     * Limpiar datos de tracking antiguos
     */
    public function limpiarDatosAntiguos(int $diasAntiguedad = 90): int
    {
        try {
            $fecha = now()->subDays($diasAntiguedad);
            
            // Limpiar metadata de envíos antiguos
            $enviosAntiguos = CampanaEnvio::where('created_at', '<', $fecha)
                ->whereNotNull('metadata')
                ->get();
            
            $contador = 0;
            foreach ($enviosAntiguos as $envio) {
                $metadata = $envio->metadata ?? [];
                
                // Mantener solo información básica
                $metadataLimpia = [
                    'cleaned_at' => now()->toIso8601String(),
                    'original_device' => $metadata['device']['type'] ?? null,
                ];
                
                $envio->update(['metadata' => $metadataLimpia]);
                $contador++;
            }
            
            Log::info('Datos de tracking limpiados', [
                'envios_procesados' => $contador,
                'dias_antiguedad' => $diasAntiguedad
            ]);
            
            return $contador;
        } catch (\Exception $e) {
            Log::error('Error limpiando datos de tracking', [
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }

    /**
     * Exportar datos de tracking
     */
    public function exportarDatos(int $campanaId): array
    {
        try {
            $campana = \Modules\Campanas\Models\Campana::with(['envios.user'])->find($campanaId);
            
            if (!$campana) {
                return [
                    'success' => false,
                    'message' => 'Campaña no encontrada'
                ];
            }
            
            $datos = [];
            
            foreach ($campana->envios as $envio) {
                $datos[] = [
                    'usuario' => $envio->user->name ?? 'N/A',
                    'email' => $envio->user->email ?? 'N/A',
                    'tipo' => $envio->tipo,
                    'destinatario' => $envio->destinatario,
                    'estado' => $envio->estado,
                    'fecha_enviado' => $envio->fecha_enviado?->format('Y-m-d H:i:s'),
                    'fecha_abierto' => $envio->fecha_abierto?->format('Y-m-d H:i:s'),
                    'fecha_primer_click' => $envio->fecha_primer_click?->format('Y-m-d H:i:s'),
                    'clicks_totales' => $envio->clicks_count,
                    'error' => $envio->error_mensaje,
                ];
            }
            
            return [
                'success' => true,
                'data' => $datos,
                'campana' => [
                    'nombre' => $campana->nombre,
                    'tipo' => $campana->tipo,
                    'fecha_inicio' => $campana->fecha_inicio?->format('Y-m-d H:i:s'),
                    'fecha_fin' => $campana->fecha_fin?->format('Y-m-d H:i:s'),
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error exportando datos de tracking', [
                'error' => $e->getMessage(),
                'campana_id' => $campanaId
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al exportar datos: ' . $e->getMessage()
            ];
        }
    }
}