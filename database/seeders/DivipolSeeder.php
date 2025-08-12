<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DivipolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ruta del archivo CSV
        $csvFile = base_path('divipol.csv');
        
        if (!file_exists($csvFile)) {
            $this->command->error("El archivo divipol.csv no existe en la raíz del proyecto.");
            return;
        }

        // Leer el archivo CSV
        $file = fopen($csvFile, 'r');
        
        // Saltar la primera línea (headers)
        fgetcsv($file);
        
        // Contadores para información
        $territoriosCreados = 0;
        $departamentosCreados = 0;
        $municipiosCreados = 0;
        $localidadesCreadas = 0;
        
        // Cache para evitar consultas repetidas
        $territoriosCache = [];
        $departamentosCache = [];
        $municipiosCache = [];
        
        $this->command->info("Iniciando importación de datos DIVIPOL...");
        
        // Iniciar transacción para mejor rendimiento
        DB::beginTransaction();
        
        try {
            while (($row = fgetcsv($file)) !== FALSE) {
                // Estructura del CSV:
                // 0: País
                // 1: Departamento
                // 2: Ciudad o Municipio
                // 3: Localidad
                // 4: Departamento con código
                // 5: Municipio con código
                
                $pais = trim($row[0] ?? '');
                $departamentoNombre = trim($row[1] ?? '');
                $municipioNombre = trim($row[2] ?? '');
                $localidadNombre = trim($row[3] ?? '');
                $departamentoCodigo = $this->extractCodigo($row[4] ?? '');
                $municipioCodigo = $this->extractCodigo($row[5] ?? '');
                
                if (empty($pais) || empty($departamentoNombre) || empty($municipioNombre)) {
                    continue;
                }
                
                // 1. Crear o encontrar territorio (País)
                if (!isset($territoriosCache[$pais])) {
                    $territorio = DB::table('territorios')
                        ->where('nombre', $pais)
                        ->first();
                    
                    if (!$territorio) {
                        $territorioId = DB::table('territorios')->insertGetId([
                            'nombre' => $pais,
                            'codigo' => 'CO', // Colombia
                            'activo' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $territoriosCreados++;
                        $territoriosCache[$pais] = $territorioId;
                    } else {
                        $territoriosCache[$pais] = $territorio->id;
                    }
                }
                
                $territorioId = $territoriosCache[$pais];
                
                // 2. Crear o encontrar departamento
                $departamentoKey = $territorioId . '_' . $departamentoNombre;
                if (!isset($departamentosCache[$departamentoKey])) {
                    $departamento = DB::table('departamentos')
                        ->where('territorio_id', $territorioId)
                        ->where('nombre', $departamentoNombre)
                        ->first();
                    
                    if (!$departamento) {
                        $departamentoId = DB::table('departamentos')->insertGetId([
                            'territorio_id' => $territorioId,
                            'nombre' => $departamentoNombre,
                            'codigo' => $departamentoCodigo,
                            'activo' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $departamentosCreados++;
                        $departamentosCache[$departamentoKey] = $departamentoId;
                    } else {
                        $departamentosCache[$departamentoKey] = $departamento->id;
                    }
                }
                
                $departamentoId = $departamentosCache[$departamentoKey];
                
                // 3. Crear o encontrar municipio
                $municipioKey = $departamentoId . '_' . $municipioNombre;
                if (!isset($municipiosCache[$municipioKey])) {
                    $municipio = DB::table('municipios')
                        ->where('departamento_id', $departamentoId)
                        ->where('nombre', $municipioNombre)
                        ->first();
                    
                    if (!$municipio) {
                        $municipioId = DB::table('municipios')->insertGetId([
                            'departamento_id' => $departamentoId,
                            'nombre' => $municipioNombre,
                            'codigo' => $municipioCodigo,
                            'activo' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $municipiosCreados++;
                        $municipiosCache[$municipioKey] = $municipioId;
                    } else {
                        $municipiosCache[$municipioKey] = $municipio->id;
                    }
                }
                
                $municipioId = $municipiosCache[$municipioKey];
                
                // 4. Crear localidad si no es "No Aplica"
                if (!empty($localidadNombre) && strtolower($localidadNombre) !== 'no aplica') {
                    $localidad = DB::table('localidades')
                        ->where('municipio_id', $municipioId)
                        ->where('nombre', $localidadNombre)
                        ->first();
                    
                    if (!$localidad) {
                        DB::table('localidades')->insert([
                            'municipio_id' => $municipioId,
                            'nombre' => $localidadNombre,
                            'codigo' => null, // No tenemos código específico para localidades
                            'activo' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $localidadesCreadas++;
                    }
                }
            }
            
            fclose($file);
            
            // Confirmar transacción
            DB::commit();
            
            // Mostrar resumen
            $this->command->info("Importación completada exitosamente:");
            $this->command->info("  - Territorios creados: {$territoriosCreados}");
            $this->command->info("  - Departamentos creados: {$departamentosCreados}");
            $this->command->info("  - Municipios creados: {$municipiosCreados}");
            $this->command->info("  - Localidades creadas: {$localidadesCreadas}");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Error durante la importación: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Extrae el código numérico de una cadena como "ANTIOQUIA - 1"
     */
    private function extractCodigo($string): ?string
    {
        if (preg_match('/-\s*(\d+)$/', $string, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
