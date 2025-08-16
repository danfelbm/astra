#!/bin/bash

echo "🔧 ARREGLANDO REDIS PARA PHP 8.4"
echo "================================="
echo ""

# 1. Verificar el redis.so en el directorio de PHP 8.4
echo "1️⃣ Verificando redis.so en PHP 8.4:"
if [ -f "/usr/local/lsws/lsphp84/lib64/php/modules/redis.so" ]; then
    echo "✅ redis.so existe en el directorio correcto"
    file /usr/local/lsws/lsphp84/lib64/php/modules/redis.so
    ls -la /usr/local/lsws/lsphp84/lib64/php/modules/redis.so
else
    echo "❌ redis.so NO existe, copiándolo..."
    sudo cp /usr/lib64/php/modules/redis.so /usr/local/lsws/lsphp84/lib64/php/modules/
    sudo chown litespeed:litespeed /usr/local/lsws/lsphp84/lib64/php/modules/redis.so
    sudo chmod 755 /usr/local/lsws/lsphp84/lib64/php/modules/redis.so
fi
echo ""

# 2. Verificar compatibilidad
echo "2️⃣ Verificando compatibilidad del módulo:"
/usr/local/lsws/lsphp84/bin/php -d extension=/usr/local/lsws/lsphp84/lib64/php/modules/redis.so -r "
if (extension_loaded('redis')) {
    echo '✅ redis.so es compatible con PHP 8.4' . PHP_EOL;
} else {
    echo '❌ redis.so NO es compatible con PHP 8.4' . PHP_EOL;
}
" 2>&1 | head -20
echo ""

# 3. Si no es compatible, necesitamos compilar para PHP 8.4
if ! /usr/local/lsws/lsphp84/bin/php -d extension=/usr/local/lsws/lsphp84/lib64/php/modules/redis.so -m 2>/dev/null | grep -qi redis; then
    echo "❌ El módulo actual no es compatible. Necesitamos compilar Redis para PHP 8.4"
    echo ""
    echo "3️⃣ Compilando Redis para PHP 8.4..."
    
    # Instalar herramientas de compilación si no están
    sudo dnf install -y gcc make php-devel autoconf automake libtool 2>/dev/null || sudo yum install -y gcc make php-devel autoconf automake libtool
    
    # Descargar y compilar Redis
    cd /tmp
    
    # Usar PECL de PHP 8.4
    if [ -f "/usr/local/lsws/lsphp84/bin/pecl" ]; then
        echo "Usando PECL de PHP 8.4..."
        sudo /usr/local/lsws/lsphp84/bin/pecl channel-update pecl.php.net
        yes "" | sudo /usr/local/lsws/lsphp84/bin/pecl install -f redis
    else
        echo "PECL no disponible, compilando manualmente..."
        
        # Descargar código fuente
        wget https://pecl.php.net/get/redis-6.2.0.tgz
        tar xzf redis-6.2.0.tgz
        cd redis-6.2.0
        
        # Compilar con PHP 8.4
        /usr/local/lsws/lsphp84/bin/phpize
        ./configure --with-php-config=/usr/local/lsws/lsphp84/bin/php-config
        make
        sudo make install
        
        cd ..
        rm -rf redis-6.2.0*
    fi
else
    echo "✅ El módulo redis.so es compatible con PHP 8.4"
fi
echo ""

# 4. Verificar que el archivo de configuración está correcto
echo "4️⃣ Verificando configuración..."
CONFIG_FILE="/usr/local/lsws/lsphp84/etc/php.d/50-redis.ini"

# Asegurarse de que solo dice "extension=redis.so" sin ruta completa
sudo sed -i 's|^extension.*=.*redis\.so|extension=redis.so|g' $CONFIG_FILE

echo "Contenido actual de 50-redis.ini:"
grep "^extension" $CONFIG_FILE
echo ""

# 5. Test final
echo "5️⃣ TEST FINAL:"
echo "================================="
/usr/local/lsws/lsphp84/bin/php -r "
if (extension_loaded('redis')) {
    echo '✅ ÉXITO: Redis está cargado en PHP 8.4 CLI' . PHP_EOL;
    echo 'Versión de Redis: ' . phpversion('redis') . PHP_EOL;
    
    // Probar conexión
    try {
        \$redis = new Redis();
        echo 'Clase Redis disponible: SI' . PHP_EOL;
        
        // Intentar conectar
        \$redis->connect('10.101.8.72', 6379, 1);
        echo '✅ Conexión a servidor Redis exitosa' . PHP_EOL;
    } catch (Exception \$e) {
        echo '⚠️ Redis cargado pero no se pudo conectar al servidor: ' . \$e->getMessage() . PHP_EOL;
    }
} else {
    echo '❌ Redis aún no está cargado' . PHP_EOL;
    echo '' . PHP_EOL;
    echo 'Posibles causas:' . PHP_EOL;
    echo '1. El módulo redis.so no es compatible con PHP 8.4' . PHP_EOL;
    echo '2. Falta alguna dependencia del sistema' . PHP_EOL;
    echo '' . PHP_EOL;
    echo 'Intenta ejecutar:' . PHP_EOL;
    echo 'sudo /usr/local/lsws/lsphp84/bin/pecl install -f redis' . PHP_EOL;
}
"
echo ""
echo "================================="
echo ""

# 6. Si funciona, probar con artisan
if /usr/local/lsws/lsphp84/bin/php -m | grep -qi redis; then
    echo "✅ Probando con artisan..."
    cd /var/www/webroot/ROOT
    /usr/local/lsws/lsphp84/bin/php artisan --version
else
    echo "❌ Redis aún no funciona. Revisa los errores anteriores."
fi