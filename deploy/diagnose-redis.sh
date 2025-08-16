#!/bin/bash

echo "🔍 DIAGNÓSTICO COMPLETO DE REDIS"
echo "================================="
echo ""

# 1. Ver qué PHP está ejecutando artisan
echo "1️⃣ PHP que ejecuta artisan:"
head -1 /var/www/webroot/ROOT/artisan
echo ""

# 2. Ver configuración del CLI
echo "2️⃣ Archivos .ini que carga el CLI:"
/usr/local/lsws/lsphp84/bin/php --ini
echo ""

# 3. Verificar si redis.so existe físicamente
echo "3️⃣ Ubicación de redis.so:"
find /usr -name "redis.so" 2>/dev/null
echo ""

# 4. Ver el contenido del archivo de configuración
echo "4️⃣ Contenido de 50-redis.ini:"
cat /usr/local/lsws/lsphp84/etc/php.d/50-redis.ini
echo ""

# 5. Verificar módulos cargados por CLI
echo "5️⃣ Módulos PHP cargados en CLI:"
/usr/local/lsws/lsphp84/bin/php -m | grep -i redis
echo ""

# 6. Verificar si el CLI está leyendo php.d
echo "6️⃣ Verificar scan dir de PHP:"
/usr/local/lsws/lsphp84/bin/php -i | grep "Scan this dir"
echo ""

# 7. Test directo de la clase Redis
echo "7️⃣ Test directo de clase Redis:"
/usr/local/lsws/lsphp84/bin/php -r "
if (class_exists('Redis')) {
    echo '✅ Clase Redis existe' . PHP_EOL;
} else {
    echo '❌ Clase Redis NO existe' . PHP_EOL;
    echo 'extension_loaded(redis): ' . (extension_loaded('redis') ? 'true' : 'false') . PHP_EOL;
}
"
echo ""

# 8. Ver la configuración completa de PHP CLI
echo "8️⃣ Configuración php.ini principal:"
/usr/local/lsws/lsphp84/bin/php -i | grep -E "(Loaded Configuration File|Scan this dir|Additional .ini)"
echo ""

# 9. Verificar permisos del archivo redis.so
echo "9️⃣ Permisos de redis.so:"
ls -la /usr/lib64/php/modules/redis.so 2>/dev/null || echo "No encontrado en /usr/lib64/php/modules/"
echo ""

# 10. Verificar versión de PHP y arquitectura
echo "🔟 Versión de PHP y arquitectura:"
/usr/local/lsws/lsphp84/bin/php -v
echo ""
uname -m
echo ""

# 11. Ver si hay errores al cargar el módulo
echo "1️⃣1️⃣ Intentar cargar Redis manualmente:"
/usr/local/lsws/lsphp84/bin/php -d extension=redis.so -r "
if (extension_loaded('redis')) {
    echo '✅ Redis cargado con -d extension=redis.so' . PHP_EOL;
    echo 'Versión: ' . phpversion('redis') . PHP_EOL;
} else {
    echo '❌ No se pudo cargar Redis ni con -d' . PHP_EOL;
}
"
echo ""

# 12. Verificar si hay múltiples versiones de PHP
echo "1️⃣2️⃣ Versiones de PHP instaladas:"
ls -la /usr/local/lsws/ | grep lsphp
echo ""

# 13. Ver el extension_dir configurado
echo "1️⃣3️⃣ Extension directory configurado:"
/usr/local/lsws/lsphp84/bin/php -i | grep "extension_dir"
echo ""

# 14. Listar todas las extensiones en el extension_dir
echo "1️⃣4️⃣ Extensiones disponibles en extension_dir:"
EXTENSION_DIR=$(/usr/local/lsws/lsphp84/bin/php -i | grep "^extension_dir" | cut -d' ' -f3)
if [ -d "$EXTENSION_DIR" ]; then
    ls -la "$EXTENSION_DIR" | grep -E "\.so$" | head -10
else
    echo "Extension dir no encontrado o no es un directorio"
fi
echo ""

# 15. Verificar compatibilidad del módulo
echo "1️⃣5️⃣ Verificar compatibilidad del módulo redis.so:"
file /usr/lib64/php/modules/redis.so 2>/dev/null || echo "redis.so no encontrado"
echo ""

echo "================================="
echo "📊 ANÁLISIS AUTOMÁTICO:"
echo ""

# Análisis automático
if /usr/local/lsws/lsphp84/bin/php -m | grep -qi redis; then
    echo "✅ Redis está cargado en CLI"
else
    echo "❌ Redis NO está cargado en CLI"
    
    if [ -f "/usr/lib64/php/modules/redis.so" ]; then
        echo "  • redis.so existe en /usr/lib64/php/modules/"
    else
        echo "  • redis.so NO existe en /usr/lib64/php/modules/"
    fi
    
    if [ -f "/usr/local/lsws/lsphp84/etc/php.d/50-redis.ini" ]; then
        echo "  • Archivo de configuración existe"
    else
        echo "  • Archivo de configuración NO existe"
    fi
    
    SCAN_DIR=$(/usr/local/lsws/lsphp84/bin/php -i | grep "Scan this dir" | cut -d' ' -f6)
    if [ -n "$SCAN_DIR" ]; then
        echo "  • PHP escanea: $SCAN_DIR"
    else
        echo "  • PHP NO tiene configurado scan directory"
    fi
fi

echo ""
echo "================================="
echo "🔧 POSIBLES SOLUCIONES:"
echo ""
echo "1. Si redis.so no está en el extension_dir correcto:"
echo "   sudo cp /usr/lib64/php/modules/redis.so \$EXTENSION_DIR/"
echo ""
echo "2. Si el scan directory no está configurado:"
echo "   Agregar a php.ini: --with-config-file-scan-dir=/usr/local/lsws/lsphp84/etc/php.d"
echo ""
echo "3. Si el módulo no es compatible:"
echo "   Recompilar redis.so para PHP 8.4"
echo ""