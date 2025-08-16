#!/bin/bash

# ========================================
# FIX: Habilitar Redis en CLI de LiteSpeed PHP 8.4
# ========================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔧 ARREGLANDO REDIS PARA CLI${NC}"
echo "========================================="
echo ""

# 1. Verificar configuración actual
echo -e "${YELLOW}📍 Verificando configuración actual...${NC}"
echo ""

# Encontrar php.ini de CLI
echo "PHP CLI config:"
/usr/local/lsws/lsphp84/bin/php --ini

echo ""
echo -e "${YELLOW}📝 Verificando extensión Redis...${NC}"

# Ver si redis.so existe
if [ -f "/usr/lib64/php/modules/redis.so" ]; then
    echo -e "${GREEN}✅ redis.so encontrado en: /usr/lib64/php/modules/redis.so${NC}"
else
    echo -e "${RED}❌ redis.so NO encontrado en /usr/lib64/php/modules/${NC}"
    echo "Buscando en otras ubicaciones..."
    find /usr -name "redis.so" 2>/dev/null | head -5
fi

echo ""
echo -e "${YELLOW}🔍 Verificando configuración de LiteSpeed PHP...${NC}"

# Buscar el php.ini correcto para CLI
LSPHP_CLI_INI=$(/usr/local/lsws/lsphp84/bin/php -i | grep "Loaded Configuration File" | cut -d' ' -f5)
echo "PHP CLI ini: $LSPHP_CLI_INI"

# Si no hay php.ini para CLI, crearlo
if [ "$LSPHP_CLI_INI" == "(none)" ] || [ -z "$LSPHP_CLI_INI" ]; then
    echo -e "${YELLOW}⚠️ No hay php.ini para CLI, creando uno...${NC}"
    
    # Copiar del web si existe
    if [ -f "/usr/local/lsws/lsphp84/etc/php.ini" ]; then
        sudo cp /usr/local/lsws/lsphp84/etc/php.ini /usr/local/lsws/lsphp84/etc/php-cli.ini
        LSPHP_CLI_INI="/usr/local/lsws/lsphp84/etc/php-cli.ini"
    else
        # Crear uno básico
        LSPHP_CLI_INI="/usr/local/lsws/lsphp84/etc/php.ini"
        echo "; PHP CLI Configuration" | sudo tee $LSPHP_CLI_INI > /dev/null
    fi
fi

echo ""
echo -e "${YELLOW}📝 Agregando Redis a la configuración CLI...${NC}"

# Verificar si ya existe la configuración de Redis
if grep -q "extension=redis" "$LSPHP_CLI_INI" 2>/dev/null; then
    echo -e "${GREEN}✅ Redis ya está en php.ini${NC}"
else
    # Agregar extensión Redis
    echo "" | sudo tee -a $LSPHP_CLI_INI > /dev/null
    echo "; Redis Extension" | sudo tee -a $LSPHP_CLI_INI > /dev/null
    echo "extension=redis.so" | sudo tee -a $LSPHP_CLI_INI > /dev/null
    echo -e "${GREEN}✅ Redis agregado a php.ini${NC}"
fi

# También verificar en php.d
LSPHP_DIR="/usr/local/lsws/lsphp84/etc/php.d"
if [ -d "$LSPHP_DIR" ]; then
    echo ""
    echo -e "${YELLOW}📁 Verificando directorio php.d...${NC}"
    
    # Buscar archivo de Redis
    REDIS_INI=$(find $LSPHP_DIR -name "*redis*" 2>/dev/null | head -1)
    
    if [ -z "$REDIS_INI" ]; then
        # Crear archivo para Redis
        REDIS_INI="$LSPHP_DIR/50-redis.ini"
        echo "; Enable Redis extension" | sudo tee $REDIS_INI > /dev/null
        echo "extension=redis.so" | sudo tee -a $REDIS_INI > /dev/null
        echo -e "${GREEN}✅ Creado: $REDIS_INI${NC}"
    else
        echo -e "${GREEN}✅ Archivo Redis encontrado: $REDIS_INI${NC}"
        
        # Verificar que esté habilitado
        if ! grep -q "^extension=redis" "$REDIS_INI"; then
            echo "extension=redis.so" | sudo tee -a $REDIS_INI > /dev/null
            echo "  ✓ Habilitado en $REDIS_INI"
        fi
    fi
fi

echo ""
echo -e "${BLUE}🔍 VERIFICACIÓN FINAL${NC}"
echo "========================================="

# Test directo
echo -e "${YELLOW}Probando extensión Redis en CLI...${NC}"
/usr/local/lsws/lsphp84/bin/php -r "
    if (extension_loaded('redis')) {
        echo '✅ Extensión Redis CARGADA en CLI' . PHP_EOL;
        echo 'Versión: ' . phpversion('redis') . PHP_EOL;
        
        // Probar conexión
        try {
            \$redis = new Redis();
            \$redis->connect('10.101.8.72', 6379, 1);
            echo '✅ Conexión a Redis exitosa' . PHP_EOL;
            \$redis->close();
        } catch (Exception \$e) {
            echo '⚠️ No se pudo conectar a Redis: ' . \$e->getMessage() . PHP_EOL;
        }
    } else {
        echo '❌ Extensión Redis NO está cargada' . PHP_EOL;
        echo 'Extensiones cargadas:' . PHP_EOL;
        print_r(get_loaded_extensions());
    }
"

echo ""
echo -e "${BLUE}📋 SIGUIENTE PASO${NC}"
echo "========================================="
echo ""
echo "Si Redis aún no funciona en CLI, ejecuta:"
echo ""
echo "1. Verificar que el módulo existe:"
echo "   ls -la /usr/lib64/php/modules/redis.so"
echo ""
echo "2. Si no existe, buscar dónde está:"
echo "   find / -name redis.so 2>/dev/null"
echo ""
echo "3. Crear enlace simbólico si es necesario:"
echo "   sudo ln -s /ruta/donde/esta/redis.so /usr/lib64/php/modules/redis.so"
echo ""
echo "4. Luego probar:"
echo "   /usr/local/lsws/lsphp84/bin/php artisan cache:clear"
echo ""