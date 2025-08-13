<?php
// Check build directory structure
header('Content-Type: text/plain');

echo "=== BUILD DIRECTORY CHECK ===\n\n";

$buildDir = __DIR__ . '/build';
echo "Build directory exists: " . (is_dir($buildDir) ? 'YES' : 'NO') . "\n";

if (is_dir($buildDir)) {
    echo "\nContents of /build:\n";
    $buildContents = scandir($buildDir);
    foreach ($buildContents as $item) {
        if ($item != '.' && $item != '..') {
            $path = $buildDir . '/' . $item;
            $type = is_dir($path) ? 'DIR' : 'FILE';
            $size = is_file($path) ? filesize($path) : '';
            echo "  - $item ($type) $size\n";
        }
    }
    
    $assetsDir = $buildDir . '/assets';
    echo "\n/build/assets directory exists: " . (is_dir($assetsDir) ? 'YES' : 'NO') . "\n";
    
    if (is_dir($assetsDir)) {
        echo "\nFirst 20 files in /build/assets:\n";
        $files = scandir($assetsDir);
        $count = 0;
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && $count < 20) {
                $size = filesize($assetsDir . '/' . $file);
                echo "  - $file (" . number_format($size) . " bytes)\n";
                $count++;
            }
        }
        
        echo "\nTotal files in /build/assets: " . (count($files) - 2) . "\n";
        
        // Check for specific file
        $targetFile = $assetsDir . '/app-CirNizQj.js';
        echo "\nChecking for app-CirNizQj.js:\n";
        echo "  File exists: " . (file_exists($targetFile) ? 'YES' : 'NO') . "\n";
        if (file_exists($targetFile)) {
            echo "  File size: " . number_format(filesize($targetFile)) . " bytes\n";
            echo "  Readable: " . (is_readable($targetFile) ? 'YES' : 'NO') . "\n";
        }
    }
}

// Check git
echo "\n=== GIT STATUS ===\n";
exec('cd /var/www/html && git status --short public/build/ 2>&1', $gitOutput);
if (empty($gitOutput)) {
    echo "All build files are committed\n";
} else {
    echo implode("\n", $gitOutput) . "\n";
}

// Check last git log
echo "\n=== LAST GIT COMMITS ===\n";
exec('cd /var/www/html && git log --oneline -n 5 2>&1', $gitLog);
echo implode("\n", $gitLog) . "\n";