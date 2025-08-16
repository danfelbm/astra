#!/bin/bash

# Script para iniciar múltiples workers de cola para manejar carga masiva
# Ejecutar con: bash start-queue-workers.sh

echo "🚀 Iniciando workers de cola para procesamiento de OTP..."

# Número de workers a iniciar
WORKERS=5

# Matar workers existentes
echo "⚠️  Deteniendo workers existentes..."
pkill -f "artisan queue:work"

# Esperar un momento para asegurar que se detuvieron
sleep 2

# Iniciar nuevos workers
for i in $(seq 1 $WORKERS)
do
    echo "✅ Iniciando worker #$i..."
    nohup php artisan queue:work --queue=default --sleep=3 --tries=3 --timeout=30 > storage/logs/worker-$i.log 2>&1 &
done

echo "✅ $WORKERS workers iniciados exitosamente"
echo ""
echo "Para monitorear los workers:"
echo "  - Ver procesos: ps aux | grep 'queue:work'"
echo "  - Ver logs: tail -f storage/logs/worker-*.log"
echo "  - Ver estado de cola: php artisan queue:monitor"
echo ""
echo "Para detener los workers:"
echo "  pkill -f 'artisan queue:work'"