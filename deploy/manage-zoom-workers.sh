#!/bin/bash

# ========================================
# Gestión de Laravel Queue Workers - ZOOM
# ========================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuración específica para Zoom
ZOOM_WORKER_COUNT=4
SERVICE_PREFIX="laravel-zoom-queue"

function show_status() {
    echo -e "${BLUE}📊 Estado de los Workers de Zoom:${NC}"
    for i in $(seq 1 $ZOOM_WORKER_COUNT); do
        status=$(systemctl is-active ${SERVICE_PREFIX}@$i.service)
        if [ "$status" = "active" ]; then
            echo -e "  Zoom Worker $i: ${GREEN}✓ $status${NC}"
        else
            echo -e "  Zoom Worker $i: ${RED}✗ $status${NC}"
        fi
    done
}

function start_workers() {
    echo -e "${YELLOW}▶️  Iniciando workers de Zoom...${NC}"
    for i in $(seq 1 $ZOOM_WORKER_COUNT); do
        sudo systemctl start ${SERVICE_PREFIX}@$i.service
        echo "  Zoom Worker $i iniciado"
    done
    echo -e "${GREEN}✅ Todos los workers de Zoom iniciados${NC}"
}

function stop_workers() {
    echo -e "${YELLOW}⏸️  Deteniendo workers de Zoom...${NC}"
    for i in $(seq 1 $ZOOM_WORKER_COUNT); do
        sudo systemctl stop ${SERVICE_PREFIX}@$i.service
        echo "  Zoom Worker $i detenido"
    done
    echo -e "${GREEN}✅ Todos los workers de Zoom detenidos${NC}"
}

function restart_workers() {
    echo -e "${YELLOW}🔄 Reiniciando workers de Zoom...${NC}"
    for i in $(seq 1 $ZOOM_WORKER_COUNT); do
        sudo systemctl restart ${SERVICE_PREFIX}@$i.service
        echo "  Zoom Worker $i reiniciado"
    done
    echo -e "${GREEN}✅ Todos los workers de Zoom reiniciados${NC}"
}

function show_logs() {
    echo -e "${BLUE}📜 Mostrando logs de Zoom (Ctrl+C para salir)...${NC}"
    echo ""
    journalctl -u "${SERVICE_PREFIX}@*" -f
}

function show_queue_status() {
    echo -e "${BLUE}🎥 Estado de la cola de Zoom:${NC}"
    # Esto requiere que tengas acceso a la BD desde el servidor
    echo "Jobs pendientes en zoom-registrations:"
    # mysql -u user -p database -e "SELECT COUNT(*) as pending_jobs FROM jobs WHERE queue='zoom-registrations';"
}

function show_help() {
    echo "Uso: $0 {status|start|stop|restart|logs|queue|help}"
    echo ""
    echo "Comandos:"
    echo "  status   - Mostrar estado de workers de Zoom"
    echo "  start    - Iniciar workers de Zoom"
    echo "  stop     - Detener workers de Zoom" 
    echo "  restart  - Reiniciar workers de Zoom"
    echo "  logs     - Ver logs de Zoom en tiempo real"
    echo "  queue    - Ver estado de cola zoom-registrations"
    echo "  help     - Mostrar esta ayuda"
}

# Verificar permisos sudo si no es root
if [[ $EUID -ne 0 ]]; then
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
    queue)
        show_queue_status
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