<?php

namespace Database\Seeders;

use App\Models\Cargo;
use App\Models\Convocatoria;
use App\Models\Departamento;
use App\Models\PeriodoElectoral;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ConvocatoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Iniciando seeder de Convocatorias para Cámara de Representantes...');

        // 1. Verificar dependencias
        $this->validarDependencias();

        // 2. Obtener referencias necesarias
        $cargo = Cargo::where('nombre', 'Cámara de Representantes')
            ->where('es_cargo', true)
            ->first();

        $periodo = PeriodoElectoral::where('nombre', 'Elecciones Legislativas 2026')
            ->where('activo', true)
            ->first();

        // 3. Configurar fechas
        $fechaApertura = Carbon::now();
        $fechaCierre = Carbon::now()->addDays(10);

        // 4. Definir formulario de postulación con solo campo de Perfil de Candidatura
        $formularioPostulacion = [
            [
                'id' => 'perfil_candidatura_vinculado',
                'type' => 'perfil_candidatura',
                'title' => 'Perfil de Candidatura',
                'description' => 'Seleccione su perfil de candidatura aprobado para vincular a esta postulación',
                'required' => true,
                'validations' => [
                    'candidatura_aprobada' => true,
                    'usuario_propietario' => true,
                ],
                'helpText' => 'Debe tener un perfil de candidatura previamente aprobado para poder postularse. Si no tiene uno, créelo y espere su aprobación antes de continuar.',
            ],
        ];

        // 5. Crear convocatorias para los 32 departamentos
        $convocatoriasCreadas = 0;
        $convocatoriasExistentes = 0;

        $this->command->info("🏛️ Creando convocatorias para Cámara de Representantes por departamento...");

        $departamentos = Departamento::activos()
            ->with('territorio')
            ->orderBy('nombre')
            ->get();

        if ($departamentos->isEmpty()) {
            $this->command->error('❌ No se encontraron departamentos en la base de datos.');
            $this->command->error('   Por favor ejecute primero: php artisan db:seed --class=DivipolSeeder');
            return;
        }

        foreach ($departamentos as $departamento) {
            $nombreConvocatoria = "Cámara de Representantes - {$departamento->nombre}";
            
            // Verificar si ya existe la convocatoria
            $convocatoriaExistente = Convocatoria::where('nombre', $nombreConvocatoria)
                ->where('cargo_id', $cargo->id)
                ->where('periodo_electoral_id', $periodo->id)
                ->first();

            if ($convocatoriaExistente) {
                $convocatoriasExistentes++;
                $this->command->info("   ├── Ya existe: {$nombreConvocatoria}");
                continue;
            }

            $convocatoria = Convocatoria::create([
                'nombre' => $nombreConvocatoria,
                'descripcion' => "Convocatoria para postulaciones al cargo de Representante a la Cámara por el departamento de {$departamento->nombre}. " .
                               "Los candidatos seleccionados representarán los intereses del departamento en el Congreso de la República.",
                'fecha_apertura' => $fechaApertura,
                'fecha_cierre' => $fechaCierre,
                'cargo_id' => $cargo->id,
                'periodo_electoral_id' => $periodo->id,
                'territorio_id' => $departamento->territorio_id,
                'departamento_id' => $departamento->id,
                'municipio_id' => null, // Nacional por departamento
                'localidad_id' => null,
                'formulario_postulacion' => $formularioPostulacion,
                'estado' => 'activa',
                'activo' => true,
            ]);

            $convocatoriasCreadas++;
            $this->command->info("   ├── ✅ Creada: {$nombreConvocatoria}");
        }

        // 6. Crear convocatoria para circunscripción internacional
        $nombreConvocatoriaInternacional = "Cámara de Representantes - Circunscripción Internacional";
        
        $convocatoriaInternacionalExistente = Convocatoria::where('nombre', $nombreConvocatoriaInternacional)
            ->where('cargo_id', $cargo->id)
            ->where('periodo_electoral_id', $periodo->id)
            ->first();

        if (!$convocatoriaInternacionalExistente) {
            $convocatoriaInternacional = Convocatoria::create([
                'nombre' => $nombreConvocatoriaInternacional,
                'descripcion' => "Convocatoria para postulaciones al cargo de Representante a la Cámara por la Circunscripción Internacional. " .
                               "Los candidatos seleccionados representarán los intereses de los colombianos residentes en el exterior.",
                'fecha_apertura' => $fechaApertura,
                'fecha_cierre' => $fechaCierre,
                'cargo_id' => $cargo->id,
                'periodo_electoral_id' => $periodo->id,
                'territorio_id' => null, // Internacional - no aplica territorio específico
                'departamento_id' => null,
                'municipio_id' => null,
                'localidad_id' => null,
                'formulario_postulacion' => $formularioPostulacion,
                'estado' => 'activa',
                'activo' => true,
            ]);

            $convocatoriasCreadas++;
            $this->command->info("   ├── ✅ Creada: {$nombreConvocatoriaInternacional}");
        } else {
            $convocatoriasExistentes++;
            $this->command->info("   ├── Ya existe: {$nombreConvocatoriaInternacional}");
        }

        // 7. Mostrar resumen
        $this->mostrarResumen($convocatoriasCreadas, $convocatoriasExistentes, $cargo, $periodo, $fechaApertura, $fechaCierre);
    }

    /**
     * Validar que existan las dependencias necesarias
     */
    private function validarDependencias(): void
    {
        // Verificar que existe el cargo
        $cargo = Cargo::where('nombre', 'Cámara de Representantes')
            ->where('es_cargo', true)
            ->first();

        if (!$cargo) {
            $this->command->error('❌ No se encontró el cargo "Cámara de Representantes".');
            $this->command->error('   Por favor ejecute primero: php artisan db:seed --class=CargoSeeder');
            exit(1);
        }

        // Verificar que existe el periodo electoral
        $periodo = PeriodoElectoral::where('nombre', 'Elecciones Legislativas 2026')
            ->where('activo', true)
            ->first();

        if (!$periodo) {
            $this->command->error('❌ No se encontró el periodo electoral "Elecciones Legislativas 2026".');
            $this->command->error('   Por favor ejecute primero: php artisan db:seed --class=PeriodoElectoralSeeder');
            exit(1);
        }

        $this->command->info('✅ Dependencias verificadas correctamente');
    }

    /**
     * Mostrar resumen de la operación
     */
    private function mostrarResumen(int $creadas, int $existentes, Cargo $cargo, PeriodoElectoral $periodo, Carbon $apertura, Carbon $cierre): void
    {
        $this->command->info("\n📊 Resumen de Convocatorias Creadas:");
        $this->command->info("   - Nuevas convocatorias creadas: {$creadas}");
        $this->command->info("   - Convocatorias ya existentes: {$existentes}");
        $this->command->info("   - Total esperado: 33 (32 departamentos + 1 internacional)");
        $this->command->info("   - Total en sistema: " . Convocatoria::where('cargo_id', $cargo->id)->count());
        
        $this->command->info("\n⚙️ Configuración de las Convocatorias:");
        $this->command->info("   - Cargo: {$cargo->nombre} (ID: {$cargo->id})");
        $this->command->info("   - Periodo: {$periodo->nombre} (ID: {$periodo->id})");
        $this->command->info("   - Fecha apertura: {$apertura->format('d/m/Y H:i')}");
        $this->command->info("   - Fecha cierre: {$cierre->format('d/m/Y H:i')}");
        $this->command->info("   - Estado: activa");
        $this->command->info("   - Formulario: Solo campo 'Perfil de Candidatura' (requerido)");

        $this->command->info("\n✅ Seeder de Convocatorias completado exitosamente!");
        $this->command->info("   Los usuarios ahora pueden postularse a las convocatorias creadas");
        $this->command->info("   siempre que tengan un perfil de candidatura previamente aprobado.");
    }
}