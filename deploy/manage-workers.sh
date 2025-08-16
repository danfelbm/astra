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
        systemctl start laravel-queue@$i.service
        echo "  Worker $i iniciado"
    done
    echo -e "${GREEN}✅ Todos los workers iniciados${NC}"
}

function stop_workers() {
    echo -e "${YELLOW}⏸️  Deteniendo workers...${NC}"
    for i in $(seq 1 $WORKER_COUNT); do
        systemctl stop laravel-queue@$i.service
        echo "  Worker $i detenido"
    done
    echo -e "${GREEN}✅ Todos los workers detenidos${NC}"
}

function restart_workers() {
    echo -e "${YELLOW}🔄 Reiniciando workers...${NC}"
    for i in $(seq 1 $WORKER_COUNT); do
        systemctl restart laravel-queue@$i.service
        echo "  Worker $i reiniciado"
    done
    echo -e "${GREEN}✅ Todos los workers reiniciados${NC}"
}

function show_logs() {
    echo -e "${BLUE}📜 Mostrando logs (Ctrl+C para salir)...${NC}"
    echo ""
    journalctl -u "laravel-queue@*" -f
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

# Verificar que se ejecuta como root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}❌ Este script debe ejecutarse como root (sudo)${NC}"
   exit 1
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