#!/bin/bash

# ========================================
# Gestión de Laravel Queue Workers
# ========================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

WORKER_COUNT=8
OTP_EMAIL_WORKERS=2
OTP_WHATSAPP_WORKERS=1
ZOOM_WORKERS=1

function show_status() {
    echo -e "${BLUE}📊 Estado de los Workers:${NC}"
    for i in $(seq 1 $WORKER_COUNT); do
        status=$(systemctl is-active laravel-queue@$i.service)
        if [ "$status" = "active" ]; then
            echo -e "  Worker $i: ${GREEN}✓ $status${NC}"
        else
            echo -e "  Worker $i: ${RED}✗ $status${NC}"
        fi
    done
    
    echo ""
    echo -e "${BLUE}📧 Estado de Workers OTP Email:${NC}"
    for i in $(seq 1 $OTP_EMAIL_WORKERS); do
        status=$(systemctl is-active laravel-otp-email-queue@$i.service)
        if [ "$status" = "active" ]; then
            echo -e "  OTP Email Worker $i: ${GREEN}✓ $status${NC}"
        else
            echo -e "  OTP Email Worker $i: ${RED}✗ $status${NC}"
        fi
    done
    
    echo ""
    echo -e "${BLUE}💬 Estado de Workers OTP WhatsApp:${NC}"
    for i in $(seq 1 $OTP_WHATSAPP_WORKERS); do
        status=$(systemctl is-active laravel-otp-whatsapp-queue@$i.service)
        if [ "$status" = "active" ]; then
            echo -e "  OTP WhatsApp Worker $i: ${GREEN}✓ $status${NC}"
        else
            echo -e "  OTP WhatsApp Worker $i: ${RED}✗ $status${NC}"
        fi
    done
    
    echo ""
    echo -e "${BLUE}🎥 Estado de Workers Zoom:${NC}"
    for i in $(seq 1 $ZOOM_WORKERS); do
        status=$(systemctl is-active laravel-zoom-queue@$i.service)
        if [ "$status" = "active" ]; then
            echo -e "  Zoom Worker $i: ${GREEN}✓ $status${NC}"
        else
            echo -e "  Zoom Worker $i: ${RED}✗ $status${NC}"
        fi
    done
    
    echo ""
    echo -e "${BLUE}⏰ Estado del Scheduler:${NC}"
    scheduler_status=$(systemctl is-active laravel-scheduler.timer)
    if [ "$scheduler_status" = "active" ]; then
        echo -e "  Scheduler: ${GREEN}✓ $scheduler_status${NC}"
    else
        echo -e "  Scheduler: ${RED}✗ $scheduler_status${NC}"
    fi
}

function start_workers() {
    echo -e "${YELLOW}▶️  Iniciando workers...${NC}"
    for i in $(seq 1 $WORKER_COUNT); do
        sudo systemctl start laravel-queue@$i.service
        echo "  Worker $i iniciado"
    done
    
    # Iniciar workers OTP Email
    for i in $(seq 1 $OTP_EMAIL_WORKERS); do
        sudo systemctl start laravel-otp-email-queue@$i.service
        echo "  OTP Email Worker $i iniciado"
    done
    
    # Iniciar workers OTP WhatsApp
    for i in $(seq 1 $OTP_WHATSAPP_WORKERS); do
        sudo systemctl start laravel-otp-whatsapp-queue@$i.service
        echo "  OTP WhatsApp Worker $i iniciado"
    done
    
    # Iniciar workers Zoom
    for i in $(seq 1 $ZOOM_WORKERS); do
        sudo systemctl start laravel-zoom-queue@$i.service
        echo "  Zoom Worker $i iniciado"
    done
    
    # Iniciar scheduler también
    sudo systemctl start laravel-scheduler.timer
    echo "  Scheduler iniciado"
    echo -e "${GREEN}✅ Todos los workers iniciados${NC}"
}

function stop_workers() {
    echo -e "${YELLOW}⏸️  Deteniendo workers...${NC}"
    for i in $(seq 1 $WORKER_COUNT); do
        sudo systemctl stop laravel-queue@$i.service
        echo "  Worker $i detenido"
    done
    
    # Detener workers OTP Email
    for i in $(seq 1 $OTP_EMAIL_WORKERS); do
        sudo systemctl stop laravel-otp-email-queue@$i.service
        echo "  OTP Email Worker $i detenido"
    done
    
    # Detener workers OTP WhatsApp
    for i in $(seq 1 $OTP_WHATSAPP_WORKERS); do
        sudo systemctl stop laravel-otp-whatsapp-queue@$i.service
        echo "  OTP WhatsApp Worker $i detenido"
    done
    
    # Detener workers Zoom
    for i in $(seq 1 $ZOOM_WORKERS); do
        sudo systemctl stop laravel-zoom-queue@$i.service
        echo "  Zoom Worker $i detenido"
    done
    
    # Detener scheduler también
    sudo systemctl stop laravel-scheduler.timer
    echo "  Scheduler detenido"
    echo -e "${GREEN}✅ Todos los workers detenidos${NC}"
}

function restart_workers() {
    echo -e "${YELLOW}🔄 Reiniciando workers...${NC}"
    for i in $(seq 1 $WORKER_COUNT); do
        sudo systemctl restart laravel-queue@$i.service
        echo "  Worker $i reiniciado"
    done
    
    # Reiniciar workers OTP Email
    for i in $(seq 1 $OTP_EMAIL_WORKERS); do
        sudo systemctl restart laravel-otp-email-queue@$i.service
        echo "  OTP Email Worker $i reiniciado"
    done
    
    # Reiniciar workers OTP WhatsApp
    for i in $(seq 1 $OTP_WHATSAPP_WORKERS); do
        sudo systemctl restart laravel-otp-whatsapp-queue@$i.service
        echo "  OTP WhatsApp Worker $i reiniciado"
    done
    
    # Reiniciar workers Zoom
    for i in $(seq 1 $ZOOM_WORKERS); do
        sudo systemctl restart laravel-zoom-queue@$i.service
        echo "  Zoom Worker $i reiniciado"
    done
    
    # Reiniciar scheduler también
    sudo systemctl restart laravel-scheduler.timer
    echo "  Scheduler reiniciado"
    echo -e "${GREEN}✅ Todos los workers reiniciados${NC}"
}

function show_logs() {
    echo -e "${BLUE}📜 Mostrando logs (Ctrl+C para salir)...${NC}"
    echo ""
    journalctl -u "laravel-queue@*" -u "laravel-otp-email-queue@*" -u "laravel-otp-whatsapp-queue@*" -u "laravel-zoom-queue@*" -f
}

function show_help() {
    echo "Uso: $0 {status|start|stop|restart|logs|help}"
    echo ""
    echo "Comandos:"
    echo "  status   - Mostrar estado de todos los workers"
    echo "  start    - Iniciar todos los workers"
    echo "  stop     - Detener todos los workers"
    echo "  restart  - Reiniciar todos los workers"
    echo "  logs     - Ver logs en tiempo real"
    echo "  help     - Mostrar esta ayuda"
}

# Verificar permisos sudo si no es root
if [[ $EUID -ne 0 ]]; then
   # Verificar que puede usar sudo
   if ! sudo -n true 2>/dev/null; then 
      echo -e "${RED}❌ Este script requiere permisos de sudo${NC}"
      echo "Ejecuta: sudo $0 $@"
      exit 1
   fi
fi

# Procesar comando
case "$1" in
    status)
        show_status
        ;;
    start)
        start_workers
        ;;
    stop)
        stop_workers
        ;;
    restart)
        restart_workers
        ;;
    logs)
        show_logs
        ;;
    help)
        show_help
        ;;
    *)
        echo -e "${RED}Comando no válido${NC}"
        show_help
        exit 1
        ;;
esac