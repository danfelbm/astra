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
ZOOM_WORKER_COUNT=4
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

# Copiar servicio de workers de Zoom
cp systemd/laravel-zoom-queue@.service /etc/systemd/system/
echo "  ✓ laravel-zoom-queue@.service copiado"

# Copiar scheduler service y timer
cp systemd/laravel-scheduler.service /etc/systemd/system/
cp systemd/laravel-scheduler.timer /etc/systemd/system/
echo "  ✓ laravel-scheduler service y timer copiados"

# 5. Recargar systemd
echo -e "${YELLOW}🔄 Recargando configuración de systemd...${NC}"
systemctl daemon-reload

# 6. Habilitar y arrancar workers OTP
echo -e "${YELLOW}⚙️  Habilitando $WORKER_COUNT workers OTP...${NC}"

for i in $(seq 1 $WORKER_COUNT); do
    systemctl enable laravel-queue@$i.service
    systemctl start laravel-queue@$i.service
    echo "  ✓ Worker OTP $i habilitado y arrancado"
done

# 6.1. Habilitar y arrancar workers de Zoom
echo -e "${YELLOW}🎥 Habilitando $ZOOM_WORKER_COUNT workers de Zoom...${NC}"

for i in $(seq 1 $ZOOM_WORKER_COUNT); do
    systemctl enable laravel-zoom-queue@$i.service
    systemctl start laravel-zoom-queue@$i.service
    echo "  ✓ Worker Zoom $i habilitado y arrancado"
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
echo ""
echo "🔧 Workers OTP:"
systemctl status laravel-queue@1.service --no-pager | head -n 3
echo "... (total: $WORKER_COUNT workers)"
echo ""
echo "🎥 Workers Zoom:"
systemctl status laravel-zoom-queue@1.service --no-pager | head -n 3
echo "... (total: $ZOOM_WORKER_COUNT workers)"
echo ""
echo "⏰ Scheduler:"
systemctl status laravel-scheduler.timer --no-pager | head -n 3

echo ""
echo -e "${YELLOW}📝 Comandos útiles:${NC}"
echo ""
echo "🔧 Workers OTP:"
echo "  Ver estado:    systemctl status 'laravel-queue@*'"
echo "  Reiniciar:     systemctl restart 'laravel-queue@*'"
echo "  Ver logs:      journalctl -u laravel-queue@1 -f"
echo "  Gestión:       ./deploy/manage-workers.sh [status|start|stop|restart]"
echo ""
echo "🎥 Workers Zoom:"
echo "  Ver estado:    systemctl status 'laravel-zoom-queue@*'"
echo "  Reiniciar:     systemctl restart 'laravel-zoom-queue@*'"
echo "  Ver logs:      journalctl -u laravel-zoom-queue@1 -f"
echo "  Gestión:       ./deploy/manage-zoom-workers.sh [status|start|stop|restart]"
echo ""
echo "⏰ Scheduler:"
echo "  Ver estado:    systemctl status laravel-scheduler.timer"
echo "  Ver logs:      journalctl -u laravel-scheduler -f"
echo ""
echo "📁 Logs de archivos:"
echo "  Workers OTP:   tail -f $LARAVEL_PATH/storage/logs/worker-*.log"
echo "  Workers Zoom:  tail -f $LARAVEL_PATH/storage/logs/zoom-worker-*.log"
echo ""
echo -e "${GREEN}🎉 Sistema listo para producción!${NC}"