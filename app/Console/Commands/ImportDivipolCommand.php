<?php

namespace App\Console\Commands;

use App\Models\Territorio;
use App\Models\Departamento;
use App\Models\Municipio;
use App\Models\Localidad;
use Illuminate\Console\Command;

class ImportDivipolCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'divipol:import {file? : Path to the CSV file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import geographic data from divipol CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file') ?: base_path('divipol.csv');
        
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Starting import from: {$filePath}");
        
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $this->error("Could not open file: {$filePath}");
            return Command::FAILURE;
        }

        // Skip header row
        fgetcsv($handle);
        
        $territorios = [];
        $departamentos = [];
        $municipios = [];
        $localidades = [];
        
        $rowCount = 0;
        $this->output->progressStart();

        while (($data = fgetcsv($handle)) !== false) {
            $rowCount++;
            
            if (count($data) < 4) {
                continue;
            }

            $pais = trim($data[0]);
            $departamento = trim($data[1]);
            $municipio = trim($data[2]);
            $localidad = trim($data[3]);

            // Skip if essential data is missing
            if (empty($pais) || empty($departamento) || empty($municipio)) {
                continue;
            }

            // Process Territory (País)
            if (!isset($territorios[$pais])) {
                $territorio = Territorio::firstOrCreate(
                    ['nombre' => $pais],
                    ['activo' => true]
                );
                $territorios[$pais] = $territorio->id;
            }
            $territorioId = $territorios[$pais];

            // Process Department
            $deptKey = $pais . '::' . $departamento;
            if (!isset($departamentos[$deptKey])) {
                $dept = Departamento::firstOrCreate(
                    [
                        'territorio_id' => $territorioId,
                        'nombre' => $departamento
                    ],
                    ['activo' => true]
                );
                $departamentos[$deptKey] = $dept->id;
            }
            $departamentoId = $departamentos[$deptKey];

            // Process Municipality
            $muniKey = $deptKey . '::' . $municipio;
            if (!isset($municipios[$muniKey])) {
                $muni = Municipio::firstOrCreate(
                    [
                        'departamento_id' => $departamentoId,
                        'nombre' => $municipio
                    ],
                    ['activo' => true]
                );
                $municipios[$muniKey] = $muni->id;
            }
            $municipioId = $municipios[$muniKey];

            // Process Locality (only if not empty and not "No Aplica")
            if (!empty($localidad) && $localidad !== 'No Aplica') {
                $localKey = $muniKey . '::' . $localidad;
                if (!isset($localidades[$localKey])) {
                    $local = Localidad::firstOrCreate(
                        [
                            'municipio_id' => $municipioId,
                            'nombre' => $localidad
                        ],
                        ['activo' => true]
                    );
                    $localidades[$localKey] = $local->id;
                }
            }

            if ($rowCount % 50 === 0) {
                $this->output->progressAdvance(50);
            }
        }

        $this->output->progressFinish();
        fclose($handle);

        $this->info("Import completed successfully!");
        $this->table(
            ['Type', 'Count'],
            [
                ['Territories', count($territorios)],
                ['Departments', count($departamentos)],
                ['Municipalities', count($municipios)],
                ['Localities', count($localidades)],
                ['Total Rows', $rowCount]
            ]
        );

        return Command::SUCCESS;
    }
}
