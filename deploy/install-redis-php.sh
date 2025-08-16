#!/bin/bash

# ========================================
# Instalación de Extensión PHP Redis
# AlmaLinux / RHEL / CentOS
# ========================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}🚀 Instalando extensión PHP Redis${NC}"
echo "========================================="

# 1. Verificar si ya está instalada
if php -m | grep -q redis; then
    echo -e "${GREEN}✅ La extensión Redis ya está instalada${NC}"
    exit 0
fi

# 2. Detectar versión de PHP
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo -e "${YELLOW}📦 Detectada PHP versión: $PHP_VERSION${NC}"

# 3. Instalar dependencias
echo -e "${YELLOW}📦 Instalando dependencias...${NC}"
sudo dnf install -y php-devel php-pear make gcc

# 4. Instalar extensión Redis via PECL
echo -e "${YELLOW}📦 Instalando extensión Redis via PECL...${NC}"
sudo pecl install redis <<< ""

# 5. Habilitar extensión en PHP
echo -e "${YELLOW}⚙️ Habilitando extensión en PHP...${NC}"
PHP_INI_DIR=$(php -r "echo php_ini_loaded_file();")
PHP_INI_DIR=$(dirname "$PHP_INI_DIR")

# Crear archivo de configuración para Redis
echo "extension=redis.so" | sudo tee $PHP_INI_DIR/20-redis.ini > /dev/null

# 6. Reiniciar servicios PHP (si aplica)
echo -e "${YELLOW}🔄 Reiniciando servicios PHP...${NC}"

# Para PHP-FPM
if systemctl list-units --type=service | grep -q php-fpm; then
    sudo systemctl restart php-fpm
fi

# Para LiteSpeed
if systemctl list-units --type=service | grep -q lsws; then
    echo -e "${YELLOW}⚠️ LiteSpeed detectado - necesitarás reiniciar manualmente${NC}"
    echo "Ejecuta: sudo systemctl restart lsws"
fi

# 7. Verificar instalación
echo -e "${YELLOW}✅ Verificando instalación...${NC}"
if php -m | grep -q redis; then
    echo -e "${GREEN}✅ Extensión Redis instalada correctamente${NC}"
    
    # Mostrar información
    php -r "
        if (class_exists('Redis')) {
            \$redis = new Redis();
            echo 'Versión de extensión Redis: ' . phpversion('redis') . PHP_EOL;
        }
    "
else
    echo -e "${RED}❌ Error: La extensión Redis no se instaló correctamente${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}✅ Instalación completada${NC}"
echo "========================================="
echo ""
echo "Próximos pasos:"
echo "1. Ejecuta de nuevo: composer install"
echo "2. Configura Redis en .env:"
echo "   REDIS_HOST=tu_ip_redis"
echo "   REDIS_PORT=6379"
echo "   CACHE_DRIVER=redis"
echo "   SESSION_DRIVER=redis"
echo "   QUEUE_CONNECTION=redis"
echo ""