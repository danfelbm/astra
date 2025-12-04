<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Campanas\Models\WhatsAppGroup;
use Illuminate\Support\Facades\File;

/**
 * Comando one-time para importar grupos de WhatsApp desde JSON
 */
class ImportWhatsAppGroups extends Command
{
    protected $signature = 'whatsapp:import-groups
                            {file? : Ruta al archivo JSON (default: storage/app/public/grupos_wp.json)}
                            {--chunk=100 : Tamaño del chunk para procesar}
                            {--fresh : Eliminar todos los grupos existentes antes de importar}';

    protected $description = 'Importar grupos de WhatsApp desde archivo JSON (one-time)';

    public function handle(): int
    {
        $filePath = $this->argument('file') ?? storage_path('app/public/grupos_wp.json');
        $chunkSize = (int) $this->option('chunk');

        // Verificar que el archivo existe
        if (!File::exists($filePath)) {
            $this->error("Archivo no encontrado: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Leyendo archivo JSON: {$filePath}");

        // Leer y decodificar JSON
        $jsonContent = File::get($filePath);
        $grupos = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Error al decodificar JSON: ' . json_last_error_msg());
            return Command::FAILURE;
        }

        $totalGrupos = count($grupos);
        $this->info("Total de grupos a importar: {$totalGrupos}");

        // Opción fresh: eliminar existentes
        if ($this->option('fresh')) {
            if ($this->confirm('¿Eliminar todos los grupos existentes antes de importar?')) {
                WhatsAppGroup::truncate();
                $this->warn('Grupos existentes eliminados.');
            }
        }

        // Procesar en chunks
        $bar = $this->output->createProgressBar($totalGrupos);
        $bar->start();

        $importados = 0;
        $errores = 0;
        $chunks = array_chunk($grupos, $chunkSize);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $grupoData) {
                try {
                    // Usar el método existente del modelo
                    // Adaptar isCommunity al tipo
                    if (isset($grupoData['isCommunity']) && $grupoData['isCommunity']) {
                        // Forzar tipo comunidad si la API lo indica
                        $grupoData['_tipo_override'] = 'comunidad';
                    }

                    $this->upsertGrupo($grupoData);
                    $importados++;
                } catch (\Exception $e) {
                    $errores++;
                    $this->newLine();
                    $this->error("Error en grupo {$grupoData['id']}: {$e->getMessage()}");
                }

                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Importación completada:");
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total en archivo', $totalGrupos],
                ['Importados/Actualizados', $importados],
                ['Errores', $errores],
                ['Grupos en BD ahora', WhatsAppGroup::count()],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Insertar o actualizar grupo desde datos del JSON
     */
    private function upsertGrupo(array $data): WhatsAppGroup
    {
        // Determinar tipo: si la API dice isCommunity, es comunidad
        // Si no, usar el criterio de tamaño
        $tipo = 'grupo';
        if (!empty($data['isCommunity'])) {
            $tipo = 'comunidad';
        } elseif (($data['size'] ?? 0) > 256) {
            $tipo = 'comunidad';
        }

        return WhatsAppGroup::updateOrCreate(
            ['group_jid' => $data['id']],
            [
                'nombre' => $data['subject'] ?? 'Sin nombre',
                'descripcion' => $data['desc'] ?? null,
                'tipo' => $tipo,
                'avatar_url' => $data['pictureUrl'] ?? null,
                'participantes_count' => $data['size'] ?? 0,
                'owner_jid' => $data['owner'] ?? $data['subjectOwner'] ?? null,
                'is_announce' => $data['announce'] ?? false,
                'is_restrict' => $data['restrict'] ?? false,
                'metadata' => [
                    'creation' => $data['creation'] ?? null,
                    'subjectTime' => $data['subjectTime'] ?? null,
                    'descId' => $data['descId'] ?? null,
                    'isCommunity' => $data['isCommunity'] ?? false,
                    'isCommunityAnnounce' => $data['isCommunityAnnounce'] ?? false,
                ],
                'synced_at' => now(),
            ]
        );
    }
}
