# Sistema de Rate Limiting para Campañas

## 📋 Resumen de Cambios Implementados

### ✅ Correcciones Aplicadas

1. **SendCampanaEmailJob.php**
   - Eliminado trait `WithRateLimiting` que no tenía efecto
   - Implementado `RateLimited::forCampanaEmail()` 
   - Garantiza máximo 2 emails por segundo

2. **SendCampanaWhatsAppJob.php**
   - Eliminado trait `WithRateLimiting` innecesario
   - Implementado `RateLimited::forCampanaWhatsApp()`
   - Garantiza máximo 5 mensajes WhatsApp por segundo

3. **RateLimited.php (Core)**
   - Agregados factory methods específicos:
     - `forCampanaEmail()`: Lee límite de `config('campanas.rate_limits.email')`
     - `forCampanaWhatsApp()`: Lee límite de `config('campanas.rate_limits.whatsapp')`
   - Claves únicas: `campana_email` y `campana_whatsapp` para evitar conflictos

## ⚙️ Configuración

### Variables de Entorno (.env)
```env
# Rate Limits para Campañas
CAMPANAS_EMAIL_RATE_LIMIT=2       # Máximo 2 emails por segundo
CAMPANAS_WHATSAPP_RATE_LIMIT=5    # Máximo 5 WhatsApp por segundo
```

### Archivos de Configuración
- `config/campanas.php`: Define los límites específicos para campañas
- `config/queue.php`: Define límites generales (OTP, etc.)

## 🔍 Funcionamiento

### En Desarrollo (Local)
- Si Redis NO está disponible: Jobs se ejecutan sin rate limiting
- El sistema detecta automáticamente y registra en logs
- Permite desarrollo sin necesidad de Redis

### En Producción
- Redis OBLIGATORIO para rate limiting
- Límites estrictamente aplicados:
  - **Emails**: Máximo 2 por segundo
  - **WhatsApp**: Máximo 5 por segundo
- Garantía multi-worker mediante Redis (atomicidad global)

## 🚀 Garantías del Sistema

1. **Atomicidad Global**: Todos los workers comparten el mismo contador Redis
2. **Sin importar número de workers**: El límite es global, no por worker
3. **Backoff Exponencial**: Reintento automático con delays incrementales
4. **Claves Separadas**: Campañas no interfieren con OTP u otros sistemas

## 📊 Monitoreo

### Comandos Redis (Producción)
```bash
# Ver claves de rate limiting de campañas
redis-cli KEYS "campana:*"

# Monitorear en tiempo real
redis-cli MONITOR | grep "campana"

# Ver información de throttle
redis-cli GET "campana:campana_email"
redis-cli GET "campana:campana_whatsapp"
```

### Logs Laravel
```bash
# Ver logs de rate limiting
tail -f storage/logs/laravel.log | grep "Rate limit"

# Ver jobs procesados
tail -f storage/logs/laravel.log | grep "campana"
```

## ⚠️ Notas Importantes

1. **NO modificar** los valores hardcodeados - usar siempre configuración
2. **NO reutilizar** claves 'email' o 'whatsapp' genéricas
3. **Siempre usar** factory methods para consistencia
4. **Redis requerido** en producción para funcionamiento correcto

## 🔧 Troubleshooting

### Problema: Jobs no respetan límite
**Solución**: Verificar que Redis esté funcionando y las claves sean únicas

### Problema: Conflicto con OTP
**Solución**: Usar claves con prefijo 'campana_' para separación

### Problema: Rate limit muy restrictivo
**Solución**: Ajustar valores en `.env` y reiniciar workers

## 📈 Métricas Clave

- **Throughput Email**: 2/segundo = 120/minuto = 7,200/hora
- **Throughput WhatsApp**: 5/segundo = 300/minuto = 18,000/hora
- **Con 50 usuarios por batch**: ~25 segundos para email, ~10 segundos para WhatsApp