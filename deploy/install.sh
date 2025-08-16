#!/bin/bash

# ========================================
# Laravel Queue Workers - Instalación con Systemd
# Agnóstico entre distribuciones Linux
# ========================================

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Variables
LARAVEL_PATH="/var/www/webroot/ROOT"
WORKER_COUNT=8
SERVICE_USER="litespeed"
SERVICE_GROUP="litespeed"

echo -e "${GREEN}🚀 Instalando Laravel Queue Workers con Systemd${NC}"
echo "=========================================="

# 1. Verificar que estamos ejecutando como root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}❌ Este script debe ejecutarse como root (sudo)${NC}"
   exit 1
fi

# 2. Verificar que el directorio de Laravel existe
if [ ! -d "$LARAVEL_PATH" ]; then
    echo -e "${RED}❌ El directorio $LARAVEL_PATH no existe${NC}"
    echo "Por favor, ajusta la variable LARAVEL_PATH en este script"
    exit 1
fi

# 3. Verificar PHP
if ! command -v php &> /dev/null; then
    echo -e "${RED}❌ PHP no está instalado${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Verificaciones iniciales completadas${NC}"

# 4. Copiar archivos de servicio
echo -e "${YELLOW}📝 Copiando archivos de servicio...${NC}"

# Copiar servicio de workers
cp systemd/laravel-queue@.service /etc/systemd/system/
echo "  ✓ laravel-queue@.service copiado"

# Copiar scheduler service y timer
cp systemd/laravel-scheduler.service /etc/systemd/system/
cp systemd/laravel-scheduler.timer /etc/systemd/system/
echo "  ✓ laravel-scheduler service y timer copiados"

# 5. Recargar systemd
echo -e "${YELLOW}🔄 Recargando configuración de systemd...${NC}"
systemctl daemon-reload

# 6. Habilitar y arrancar workers
echo -e "${YELLOW}⚙️  Habilitando $WORKER_COUNT workers...${NC}"

for i in $(seq 1 $WORKER_COUNT); do
    systemctl enable laravel-queue@$i.service
    systemctl start laravel-queue@$i.service
    echo "  ✓ Worker $i habilitado y arrancado"
done

# 7. Habilitar y arrancar scheduler
echo -e "${YELLOW}⏰ Habilitando scheduler...${NC}"
systemctl enable laravel-scheduler.timer
systemctl start laravel-scheduler.timer
echo "  ✓ Scheduler habilitado y arrancado"

# 8. Crear directorios de logs si no existen
echo -e "${YELLOW}📁 Verificando directorios de logs...${NC}"
mkdir -p $LARAVEL_PATH/storage/logs
chown -R $SERVICE_USER:$SERVICE_GROUP $LARAVEL_PATH/storage
chmod -R 775 $LARAVEL_PATH/storage
echo "  ✓ Directorios de logs configurados"

# 9. Mostrar estado
echo ""
echo -e "${GREEN}✅ Instalación completada exitosamente!${NC}"
echo "=========================================="
echo ""
echo -e "${YELLOW}📊 Estado de los servicios:${NC}"
systemctl status laravel-queue@1.service --no-pager | head -n 3
echo "..."
systemctl status laravel-scheduler.timer --no-pager | head -n 3

echo ""
echo -e "${YELLOW}📝 Comandos útiles:${NC}"
echo ""
echo "  Ver estado de todos los workers:"
echo "    systemctl status 'laravel-queue@*'"
echo ""
echo "  Reiniciar todos los workers:"
echo "    systemctl restart 'laravel-queue@*'"
echo ""
echo "  Ver logs de un worker específico:"
echo "    journalctl -u laravel-queue@1 -f"
echo ""
echo "  Ver logs del scheduler:"
echo "    journalctl -u laravel-scheduler -f"
echo ""
echo "  Detener todos los workers:"
echo "    systemctl stop 'laravel-queue@*'"
echo ""
echo "  Ver logs de Laravel:"
echo "    tail -f $LARAVEL_PATH/storage/logs/worker-*.log"
echo ""
echo -e "${GREEN}🎉 Sistema listo para producción!${NC}"