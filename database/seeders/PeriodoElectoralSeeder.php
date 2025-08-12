<?php

namespace Database\Seeders;

use App\Models\PeriodoElectoral;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PeriodoElectoralSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Iniciando seeder de Periodos Electorales...');

        // Verificar si ya existe un periodo electoral similar para evitar duplicados
        $periodoExistente = PeriodoElectoral::where('nombre', 'Elecciones Legislativas 2026')->first();
        
        if ($periodoExistente) {
            $this->command->warn('Ya existe un periodo electoral "Elecciones Legislativas 2026". Omitiendo creación.');
            return;
        }

        // Crear periodo electoral activo
        $fechaInicio = Carbon::now();
        $fechaFin = Carbon::now()->addMonths(6);

        $periodo = PeriodoElectoral::create([
            'nombre' => 'Elecciones Legislativas 2026',
            'descripcion' => 'Periodo electoral para elecciones de Congreso de la República 2026 - Proceso de postulaciones y selección de candidatos para las corporaciones de elección popular',
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'activo' => true,
        ]);

        $this->command->info("✅ Periodo electoral creado exitosamente:");
        $this->command->info("   - ID: {$periodo->id}");
        $this->command->info("   - Nombre: {$periodo->nombre}");
        $this->command->info("   - Vigencia: {$periodo->getFechaInicioFormateada()} - {$periodo->getFechaFinFormateada()}");
        $this->command->info("   - Estado: {$periodo->getEstadoLabel()}");
        $this->command->info("   - Duración: {$periodo->getDuracion()}");
    }
}