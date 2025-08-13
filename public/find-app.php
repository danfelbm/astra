<?php
// Find the actual app.js file
header('Content-Type: text/plain');

$assetsDir = __DIR__ . '/build/assets';
$manifestPath = __DIR__ . '/build/manifest.json';

echo "=== FINDING APP.JS FILE ===\n\n";

// Read manifest
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    
    echo "Manifest entry for 'resources/js/app.ts':\n";
    if (isset($manifest['resources/js/app.ts'])) {
        echo json_encode($manifest['resources/js/app.ts'], JSON_PRETTY_PRINT) . "\n\n";
        $expectedFile = $manifest['resources/js/app.ts']['file'] ?? 'not found';
        echo "Expected file: $expectedFile\n";
        echo "File exists: " . (file_exists(__DIR__ . '/build/' . $expectedFile) ? 'YES' : 'NO') . "\n\n";
    } else {
        echo "NOT FOUND in manifest\n\n";
    }
}

// Find actual app files
echo "App files in /build/assets/:\n";
$files = scandir($assetsDir);
foreach ($files as $file) {
    if (strpos($file, 'app-') === 0 && strpos($file, '.js') !== false) {
        $fullPath = $assetsDir . '/' . $file;
        $size = filesize($fullPath);
        echo "  - $file (" . number_format($size) . " bytes)\n";
    }
}

// Check CSS files too
echo "\nApp CSS files:\n";
foreach ($files as $file) {
    if (strpos($file, 'app-') === 0 && strpos($file, '.css') !== false) {
        $fullPath = $assetsDir . '/' . $file;
        $size = filesize($fullPath);
        echo "  - $file (" . number_format($size) . " bytes)\n";
    }
}

// Show first 5 entries of manifest
echo "\n=== FIRST 5 MANIFEST ENTRIES ===\n";
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    $count = 0;
    foreach ($manifest as $key => $value) {
        if ($count++ >= 5) break;
        echo "$key => " . ($value['file'] ?? 'no file') . "\n";
    }
}