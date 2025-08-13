<?php
/**
 * Generador de SQL para importación masiva de usuarios
 * Lee wp_users.csv y genera INSERT statements con mapeo geográfico
 */

require __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Configuración de base de datos para conexión directa
$pdo = new PDO('mysql:host=localhost;dbname=votaciones;charset=utf8mb4', 'danielb', '159753456', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

echo "🚀 Iniciando generación de SQL desde wp_users.csv\n";

// Cargar departamentos en memoria
echo "📍 Cargando departamentos...\n";
$stmt = $pdo->query("SELECT id, nombre FROM departamentos WHERE activo = 1");
$departamentos = [];
while ($row = $stmt->fetch()) {
    $normalizado = normalizarNombre($row['nombre']);
    $departamentos[$normalizado] = $row['id'];
}
echo "   Cargados: " . count($departamentos) . " departamentos\n";

// Cargar municipios en memoria
echo "🏘️ Cargando municipios...\n";
$stmt = $pdo->query("
    SELECT m.id, m.nombre, d.nombre as departamento 
    FROM municipios m 
    JOIN departamentos d ON m.departamento_id = d.id 
    WHERE m.activo = 1 AND d.activo = 1
");
$municipios = [];
while ($row = $stmt->fetch()) {
    $deptoNormalizado = normalizarNombre($row['departamento']);
    $munNormalizado = normalizarNombre($row['nombre']);
    if (!isset($municipios[$deptoNormalizado])) {
        $municipios[$deptoNormalizado] = [];
    }
    $municipios[$deptoNormalizado][$munNormalizado] = $row['id'];
}
$totalMunicipios = array_sum(array_map('count', $municipios));
echo "   Cargados: {$totalMunicipios} municipios\n";

// Abrir archivo CSV
$csvPath = __DIR__ . '/../../public/wp_users.csv';
if (!file_exists($csvPath)) {
    echo "❌ No se encontró wp_users.csv\n";
    exit(1);
}

$handle = fopen($csvPath, 'r');
$header = fgetcsv($handle); // Leer header
echo "📋 Header: " . implode(', ', $header) . "\n";

// Generar SQL
$outputPath = __DIR__ . '/import_users_generated.sql';
$sqlFile = fopen($outputPath, 'w');

// Escribir header del archivo SQL
fwrite($sqlFile, "-- ====================================\n");
fwrite($sqlFile, "-- IMPORTACIÓN MASIVA DE USUARIOS - GENERADO AUTOMÁTICAMENTE\n");
fwrite($sqlFile, "-- Generado: " . date('Y-m-d H:i:s') . "\n");
fwrite($sqlFile, "-- ====================================\n\n");

// Crear función de normalización
fwrite($sqlFile, "-- Función de normalización\n");
fwrite($sqlFile, "DELIMITER //\n");
fwrite($sqlFile, "DROP FUNCTION IF EXISTS normalize_name //\n");
fwrite($sqlFile, "CREATE FUNCTION normalize_name(input_name VARCHAR(255))\n");
fwrite($sqlFile, "RETURNS VARCHAR(255)\n");
fwrite($sqlFile, "DETERMINISTIC\n");
fwrite($sqlFile, "READS SQL DATA\n");
fwrite($sqlFile, "BEGIN\n");
fwrite($sqlFile, "    DECLARE result VARCHAR(255);\n");
fwrite($sqlFile, "    IF input_name IS NULL THEN RETURN NULL; END IF;\n");
fwrite($sqlFile, "    SET result = LOWER(input_name);\n");
fwrite($sqlFile, "    SET result = REPLACE(result, 'á', 'a');\n");
fwrite($sqlFile, "    SET result = REPLACE(result, 'é', 'e');\n");
fwrite($sqlFile, "    SET result = REPLACE(result, 'í', 'i');\n");
fwrite($sqlFile, "    SET result = REPLACE(result, 'ó', 'o');\n");
fwrite($sqlFile, "    SET result = REPLACE(result, 'ú', 'u');\n");
fwrite($sqlFile, "    SET result = REPLACE(result, 'ñ', 'n');\n");
fwrite($sqlFile, "    SET result = REPLACE(result, 'ü', 'u');\n");
fwrite($sqlFile, "    SET result = REGEXP_REPLACE(result, '[^a-z0-9]', '');\n");
fwrite($sqlFile, "    RETURN result;\n");
fwrite($sqlFile, "END //\n");
fwrite($sqlFile, "DELIMITER ;\n\n");

// Insertar usuarios en lotes
fwrite($sqlFile, "-- Insertar usuarios nuevos (que no existen por email)\n");
fwrite($sqlFile, "INSERT IGNORE INTO users (\n");
fwrite($sqlFile, "    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,\n");
fwrite($sqlFile, "    password, activo, created_at, updated_at\n");
fwrite($sqlFile, ") VALUES\n");

$stats = [
    'total' => 0,
    'validos' => 0,
    'sin_email' => 0,
    'con_depto' => 0,
    'con_municipio' => 0
];

$values = [];
$batchSize = 100;

while (($row = fgetcsv($handle)) !== false) {
    $stats['total']++;
    
    if (count($row) !== count($header)) {
        echo "⚠️ Línea {$stats['total']}: Número de columnas incorrecto\n";
        continue;
    }
    
    $data = array_combine($header, $row);
    
    // Validar email
    if (empty($data['user_email']) || $data['user_email'] === 'NULL') {
        $stats['sin_email']++;
        continue;
    }
    
    // Limpiar datos
    $cedula = ($data['cedula'] === 'NULL' || $data['cedula'] === '') ? null : $data['cedula'];
    $departamento = ($data['departamento'] === 'NULL' || $data['departamento'] === '') ? null : $data['departamento'];
    $municipio = ($data['municipio'] === 'NULL' || $data['municipio'] === '') ? null : $data['municipio'];
    
    // Mapear geográficamente
    $departamentoId = buscarDepartamento($departamento, $departamentos);
    $municipioId = buscarMunicipio($municipio, $departamento, $municipios);
    
    if ($departamentoId) $stats['con_depto']++;
    if ($municipioId) $stats['con_municipio']++;
    
    // Preparar datos para SQL
    $name = !empty($data['user_login']) && $data['user_login'] !== 'NULL' 
        ? $data['user_login'] 
        : 'Usuario_' . $data['ID'];
    
    $values[] = sprintf(
        "(1, %s, %s, %s, %s, %s, '$2y\$12\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW())",
        $pdo->quote($name),
        $pdo->quote($data['user_email']),
        $cedula ? $pdo->quote($cedula) : 'NULL',
        $departamentoId ? $departamentoId : 'NULL',
        $municipioId ? $municipioId : 'NULL'
    );
    
    $stats['validos']++;
    
    // Escribir lote cuando llegue al tamaño
    if (count($values) >= $batchSize) {
        fwrite($sqlFile, implode(",\n", $values) . ";\n\n");
        $values = [];
        
        // Nueva declaración INSERT para el siguiente lote
        if ($stats['total'] % ($batchSize * 5) == 0) {
            echo "   Procesados: {$stats['total']} registros...\n";
        }
        
        // Escribir nueva declaración INSERT para el siguiente lote
        fwrite($sqlFile, "INSERT IGNORE INTO users (\n");
        fwrite($sqlFile, "    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,\n");
        fwrite($sqlFile, "    password, activo, created_at, updated_at\n");
        fwrite($sqlFile, ") VALUES\n");
    }
}

// Escribir último lote si hay valores pendientes
if (!empty($values)) {
    fwrite($sqlFile, implode(",\n", $values) . ";\n\n");
}

fclose($handle);

// Script de actualización para usuarios existentes
fwrite($sqlFile, "-- Actualizar usuarios existentes usando email como clave\n");
fwrite($sqlFile, "-- Este UPDATE manejará conflictos de cedula duplicada\n");

// Reabrir CSV para generar UPDATEs
$handle = fopen($csvPath, 'r');
fgetcsv($handle); // Skip header

$updateCount = 0;
while (($row = fgetcsv($handle)) !== false && count($row) === count($header)) {
    $data = array_combine($header, $row);
    
    if (empty($data['user_email']) || $data['user_email'] === 'NULL') continue;
    
    // Limpiar datos
    $cedula = ($data['cedula'] === 'NULL' || $data['cedula'] === '') ? null : $data['cedula'];
    $departamento = ($data['departamento'] === 'NULL' || $data['departamento'] === '') ? null : $data['departamento'];
    $municipio = ($data['municipio'] === 'NULL' || $data['municipio'] === '') ? null : $data['municipio'];
    
    // Mapear geográficamente
    $departamentoId = buscarDepartamento($departamento, $departamentos);
    $municipioId = buscarMunicipio($municipio, $departamento, $municipios);
    
    $name = !empty($data['user_login']) && $data['user_login'] !== 'NULL' 
        ? $data['user_login'] 
        : null;
    
    // Generar UPDATE individual para usuarios existentes
    $updateParts = [];
    if ($name) $updateParts[] = "name = " . $pdo->quote($name);
    if ($cedula) $updateParts[] = "documento_identidad = " . $pdo->quote($cedula);
    if ($departamentoId) $updateParts[] = "departamento_id = " . $departamentoId;
    if ($municipioId) $updateParts[] = "municipio_id = " . $municipioId;
    
    if (!empty($updateParts)) {
        fwrite($sqlFile, "UPDATE users SET " . implode(', ', $updateParts) . 
            ", updated_at = NOW() WHERE email = " . $pdo->quote($data['user_email']) . 
            " AND tenant_id = 1;\n");
        $updateCount++;
    }
}

fclose($handle);

// Estadísticas finales
fwrite($sqlFile, "\n-- Mostrar estadísticas\n");
fwrite($sqlFile, "SELECT 'ESTADÍSTICAS FINALES:' as info;\n");
fwrite($sqlFile, "SELECT \n");
fwrite($sqlFile, "    {$stats['total']} as registros_csv,\n");
fwrite($sqlFile, "    {$stats['validos']} as registros_validos,\n");
fwrite($sqlFile, "    {$stats['con_depto']} as con_departamento,\n");
fwrite($sqlFile, "    {$stats['con_municipio']} as con_municipio,\n");
fwrite($sqlFile, "    (SELECT COUNT(*) FROM users WHERE tenant_id = 1) as usuarios_total_tenant;\n\n");

fwrite($sqlFile, "DROP FUNCTION normalize_name;\n");
fwrite($sqlFile, "SELECT '✅ Importación completada exitosamente' as resultado;\n");

fclose($sqlFile);

echo "\n📊 === ESTADÍSTICAS DE GENERACIÓN ===\n";
echo "✅ Total procesados: {$stats['total']}\n";
echo "📧 Registros válidos: {$stats['validos']}\n";
echo "❌ Sin email: {$stats['sin_email']}\n";
echo "📍 Con departamento: {$stats['con_depto']}\n";
echo "🏘️ Con municipio: {$stats['con_municipio']}\n";
echo "📝 UPDATEs generados: {$updateCount}\n";
echo "\n🎉 SQL generado en: {$outputPath}\n";
echo "📋 Ejecuta: mysql -u danielb -p159753456 -D votaciones < {$outputPath}\n";

// Funciones auxiliares
function normalizarNombre($nombre) {
    if (!$nombre) return '';
    $nombre = strtolower($nombre);
    $nombre = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $nombre);
    return preg_replace('/[^a-z0-9]/', '', $nombre);
}

function buscarDepartamento($nombre, $departamentos) {
    if (!$nombre) return null;
    $normalizado = normalizarNombre($nombre);
    return $departamentos[$normalizado] ?? null;
}

function buscarMunicipio($municipio, $departamento, $municipios) {
    if (!$municipio || !$departamento) return null;
    $deptoNorm = normalizarNombre($departamento);
    $munNorm = normalizarNombre($municipio);
    return $municipios[$deptoNorm][$munNorm] ?? null;
}