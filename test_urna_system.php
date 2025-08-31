use App\Models\Votaciones\Voto;
use App\Models\Votaciones\UrnaSession;
use App\Services\Votaciones\TokenService;

// Verificar último voto insertado
$ultimoVoto = Voto::orderBy('id', 'desc')->first();

if ($ultimoVoto) {
    echo "=== ÚLTIMO VOTO ===\n";
    echo "ID: " . $ultimoVoto->id . "\n";
    echo "Created At: " . $ultimoVoto->created_at . "\n";
    echo "Urna Opened At: " . ($ultimoVoto->urna_opened_at ?: 'NULL') . "\n";
    echo "Token: " . substr($ultimoVoto->token_unico, 0, 50) . "...\n\n";
    
    // Verificar el token
    echo "=== VERIFICACIÓN DEL TOKEN ===\n";
    $tokenInfo = TokenService::verifyToken($ultimoVoto->token_unico);
    
    if ($tokenInfo['is_valid']) {
        echo "✅ Token válido\n";
        echo "Timestamps en el token:\n";
        if (isset($tokenInfo['timestamps'])) {
            echo "  - vote_created_at: " . ($tokenInfo['timestamps']['vote_created_at'] ?? 'No incluido') . "\n";
            echo "  - urna_opened_at: " . ($tokenInfo['timestamps']['urna_opened_at'] ?? 'No incluido') . "\n";
            if ($tokenInfo['timestamps']['tiempo_en_urna']) {
                echo "  - Tiempo en urna: " . $tokenInfo['timestamps']['tiempo_en_urna']['formato_legible'] . "\n";
            }
        }
    } else {
        echo "❌ Token inválido\n";
    }
} else {
    echo "No hay votos en la base de datos\n";
}

echo "\n=== SESIONES DE URNA ===\n";
$sesiones = UrnaSession::orderBy('id', 'desc')->limit(5)->get();
foreach ($sesiones as $sesion) {
    echo "ID: {$sesion->id} | Usuario: {$sesion->usuario_id} | Estado: {$sesion->status} | ";
    echo "Abierto: {$sesion->opened_at} | Cerrado: " . ($sesion->closed_at ?: 'NULL') . "\n";
}

echo "\n=== ESTADÍSTICAS ===\n";
echo "Total votos: " . Voto::count() . "\n";
echo "Votos con urna_opened_at: " . Voto::whereNotNull('urna_opened_at')->count() . "\n";
echo "Votos sin urna_opened_at: " . Voto::whereNull('urna_opened_at')->count() . "\n";
echo "Sesiones de urna totales: " . UrnaSession::count() . "\n";
echo "Sesiones activas: " . UrnaSession::where('status', 'active')->count() . "\n";
echo "Sesiones con voto: " . UrnaSession::where('status', 'voted')->count() . "\n";