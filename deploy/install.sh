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
OTP_EMAIL_WORKERS=2
OTP_WHATSAPP_WORKERS=1
ZOOM_WORKERS=1
SERVICE_USER="nginx"
SERVICE_GROUP="nginx"

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

# Copiar servicio de workers default
cp systemd/laravel-queue@.service /etc/systemd/system/
echo "  ✓ laravel-queue@.service copiado"

# Copiar servicios de workers OTP con rate limiting
cp systemd/laravel-otp-email-queue@.service /etc/systemd/system/
echo "  ✓ laravel-otp-email-queue@.service copiado"

cp systemd/laravel-otp-whatsapp-queue@.service /etc/systemd/system/
echo "  ✓ laravel-otp-whatsapp-queue@.service copiado"

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

# 6. Habilitar y arrancar workers default
echo -e "${YELLOW}⚙️  Habilitando $WORKER_COUNT workers default (CSV, limpieza)...${NC}"

for i in $(seq 1 $WORKER_COUNT); do
    systemctl enable laravel-queue@$i.service
    systemctl start laravel-queue@$i.service
    echo "  ✓ Worker default $i habilitado y arrancado"
done

# 6.1. Habilitar y arrancar workers OTP Email
echo -e "${YELLOW}📧 Habilitando $OTP_EMAIL_WORKERS workers OTP Email (2/seg)...${NC}"

for i in $(seq 1 $OTP_EMAIL_WORKERS); do
    systemctl enable laravel-otp-email-queue@$i.service
    systemctl start laravel-otp-email-queue@$i.service
    echo "  ✓ Worker OTP Email $i habilitado y arrancado"
done

# 6.2. Habilitar y arrancar workers OTP WhatsApp
echo -e "${YELLOW}💬 Habilitando $OTP_WHATSAPP_WORKERS workers OTP WhatsApp (5/seg)...${NC}"

for i in $(seq 1 $OTP_WHATSAPP_WORKERS); do
    systemctl enable laravel-otp-whatsapp-queue@$i.service
    systemctl start laravel-otp-whatsapp-queue@$i.service
    echo "  ✓ Worker OTP WhatsApp $i habilitado y arrancado"
done

# 6.3. Habilitar y arrancar workers de Zoom
echo -e "${YELLOW}🎥 Habilitando $ZOOM_WORKERS workers de Zoom...${NC}"

for i in $(seq 1 $ZOOM_WORKERS); do
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
echo "🔧 Workers Default:"
systemctl status laravel-queue@1.service --no-pager | head -n 3
echo "... (total: $WORKER_COUNT workers)"
echo ""
echo "📧 Workers OTP Email:"
systemctl status laravel-otp-email-queue@1.service --no-pager | head -n 3
echo "... (total: $OTP_EMAIL_WORKERS workers)"
echo ""
echo "💬 Workers OTP WhatsApp:"
systemctl status laravel-otp-whatsapp-queue@1.service --no-pager | head -n 3
echo "... (total: $OTP_WHATSAPP_WORKERS workers)"
echo ""
echo "🎥 Workers Zoom:"
systemctl status laravel-zoom-queue@1.service --no-pager | head -n 3
echo "... (total: $ZOOM_WORKERS workers)"
echo ""
echo "⏰ Scheduler:"
systemctl status laravel-scheduler.timer --no-pager | head -n 3

echo ""
echo -e "${YELLOW}📝 Comandos útiles:${NC}"
echo ""
echo "🎯 Gestión centralizada:"
echo "  Todos los workers: ./deploy/manage-workers.sh [status|start|stop|restart|logs]"
echo ""
echo "🔧 Workers Default (CSV, limpieza):"
echo "  Ver estado:    systemctl status 'laravel-queue@*'"
echo "  Reiniciar:     systemctl restart 'laravel-queue@*'"
echo "  Ver logs:      journalctl -u laravel-queue@1 -f"
echo ""
echo "📧 Workers OTP Email (2/seg):"
echo "  Ver estado:    systemctl status 'laravel-otp-email-queue@*'"
echo "  Reiniciar:     systemctl restart 'laravel-otp-email-queue@*'"
echo "  Ver logs:      journalctl -u laravel-otp-email-queue@1 -f"
echo ""
echo "💬 Workers OTP WhatsApp (5/seg):"
echo "  Ver estado:    systemctl status 'laravel-otp-whatsapp-queue@*'"
echo "  Reiniciar:     systemctl restart 'laravel-otp-whatsapp-queue@*'"
echo "  Ver logs:      journalctl -u laravel-otp-whatsapp-queue@1 -f"
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
echo "  Workers default:   tail -f $LARAVEL_PATH/storage/logs/worker-*.log"
echo "  Workers OTP:       tail -f $LARAVEL_PATH/storage/logs/otp-*-worker-*.log"
echo "  Workers Zoom:      tail -f $LARAVEL_PATH/storage/logs/zoom-worker-*.log"
echo ""
echo -e "${GREEN}🎉 Sistema listo para producción!${NC}"