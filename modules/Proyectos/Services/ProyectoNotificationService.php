<?php

namespace Modules\Proyectos\Services;

use Modules\Proyectos\Models\Proyecto;
use Modules\Core\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ProyectoNotificationService
{
    /**
     * Notifica la asignación de un proyecto a un responsable.
     */
    public function notificarAsignacion(Proyecto $proyecto): void
    {
        if (!config('proyectos.notificaciones.asignacion_responsable')) {
            return;
        }

        try {
            $responsable = $proyecto->responsable;

            if (!$responsable || !$responsable->email) {
                return;
            }

            // TODO: Implementar envío de email usando Mail facade o servicio de notificaciones
            // Por ahora solo log
            Log::info("Proyecto '{$proyecto->nombre}' asignado a {$responsable->name}");

            // Ejemplo de estructura de email:
            // Mail::to($responsable->email)->queue(new ProyectoAsignadoMail($proyecto));

        } catch (\Exception $e) {
            Log::error('Error al notificar asignación de proyecto: ' . $e->getMessage());
        }
    }

    /**
     * Notifica el cambio de estado de un proyecto.
     */
    public function notificarCambioEstado(Proyecto $proyecto): void
    {
        if (!config('proyectos.notificaciones.cambio_estado')) {
            return;
        }

        try {
            $responsable = $proyecto->responsable;
            $creador = $proyecto->creador;

            $usuarios = collect();

            if ($responsable && $responsable->email) {
                $usuarios->push($responsable);
            }

            if ($creador && $creador->email && ($creador->id !== $responsable?->id)) {
                $usuarios->push($creador);
            }

            foreach ($usuarios as $usuario) {
                // TODO: Implementar envío de email
                Log::info("Estado del proyecto '{$proyecto->nombre}' cambiado a {$proyecto->estado} - Notificando a {$usuario->name}");
            }

        } catch (\Exception $e) {
            Log::error('Error al notificar cambio de estado: ' . $e->getMessage());
        }
    }

    /**
     * Notifica sobre proyectos próximos a vencer.
     */
    public function notificarProximosVencimientos(): void
    {
        if (!config('proyectos.notificaciones.proximo_vencimiento')) {
            return;
        }

        try {
            $diasAntes = config('proyectos.notificaciones.dias_antes_vencimiento', 3);

            $proyectos = Proyecto::where('activo', true)
                ->whereIn('estado', ['planificacion', 'en_progreso'])
                ->whereNotNull('fecha_fin')
                ->whereDate('fecha_fin', '>=', now())
                ->whereDate('fecha_fin', '<=', now()->addDays($diasAntes))
                ->with(['responsable', 'creador'])
                ->get();

            foreach ($proyectos as $proyecto) {
                $this->notificarVencimientoProximo($proyecto);
            }

        } catch (\Exception $e) {
            Log::error('Error al notificar próximos vencimientos: ' . $e->getMessage());
        }
    }

    /**
     * Notifica el vencimiento próximo de un proyecto específico.
     */
    private function notificarVencimientoProximo(Proyecto $proyecto): void
    {
        try {
            $responsable = $proyecto->responsable;

            if (!$responsable || !$responsable->email) {
                return;
            }

            $diasRestantes = now()->diffInDays($proyecto->fecha_fin, false);

            // TODO: Implementar envío de email
            Log::info("Proyecto '{$proyecto->nombre}' vence en {$diasRestantes} días - Notificando a {$responsable->name}");

        } catch (\Exception $e) {
            Log::error('Error al notificar vencimiento próximo: ' . $e->getMessage());
        }
    }

    /**
     * Notifica cuando un proyecto ha sido completado.
     */
    public function notificarProyectoCompletado(Proyecto $proyecto): void
    {
        try {
            // Obtener todos los interesados
            $usuarios = collect();

            if ($proyecto->responsable) {
                $usuarios->push($proyecto->responsable);
            }

            if ($proyecto->creador && $proyecto->creador->id !== $proyecto->responsable_id) {
                $usuarios->push($proyecto->creador);
            }

            // Notificar a administradores con permiso de proyectos
            $administradores = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin')
                      ->orWhere('name', 'super_admin');
            })->get();

            $usuarios = $usuarios->merge($administradores)->unique('id');

            foreach ($usuarios as $usuario) {
                if ($usuario->email) {
                    // TODO: Implementar envío de email
                    Log::info("Proyecto '{$proyecto->nombre}' completado - Notificando a {$usuario->name}");
                }
            }

        } catch (\Exception $e) {
            Log::error('Error al notificar proyecto completado: ' . $e->getMessage());
        }
    }

    /**
     * Envía un resumen diario de proyectos.
     */
    public function enviarResumenDiario(): void
    {
        try {
            // Obtener usuarios con permisos de proyectos
            $usuarios = User::whereHas('permissions', function ($query) {
                $query->where('name', 'like', 'proyectos.%');
            })->orWhereHas('roles', function ($query) {
                $query->where('name', 'admin')
                      ->orWhere('name', 'super_admin');
            })->get();

            foreach ($usuarios as $usuario) {
                $proyectosUsuario = Proyecto::where(function ($query) use ($usuario) {
                    $query->where('responsable_id', $usuario->id)
                          ->orWhere('created_by', $usuario->id);
                })->where('activo', true)
                  ->whereIn('estado', ['planificacion', 'en_progreso'])
                  ->get();

                if ($proyectosUsuario->isNotEmpty()) {
                    // TODO: Implementar envío de resumen
                    Log::info("Resumen diario para {$usuario->name}: {$proyectosUsuario->count()} proyectos activos");
                }
            }

        } catch (\Exception $e) {
            Log::error('Error al enviar resumen diario: ' . $e->getMessage());
        }
    }
}