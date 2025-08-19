# Tests de Rate Limiting para OTP

Esta carpeta contiene todos los tests relacionados con el sistema de rate limiting implementado para OTPs (One-Time Passwords) de email y WhatsApp.

## Estructura de Tests

```
tests/
├── Unit/RateLimit/
│   ├── QueueRateLimiterServiceTest.php    # Tests del servicio principal
│   ├── RateLimitedMiddlewareTest.php      # Tests del middleware de rate limiting
│   └── WithRateLimitingTraitTest.php      # Tests del trait aplicado a jobs
├── Feature/RateLimit/
│   ├── OTPRateLimitingTest.php            # Tests end-to-end del flujo OTP
│   ├── QueueWorkerIntegrationTest.php     # Tests de integración con workers
│   └── FallbackBehaviorTest.php           # Tests de comportamiento sin Redis
└── RateLimit/
    └── README.md                           # Este archivo
```

## Configuración del Entorno de Testing

### 1. Configuración de Base de Datos

Asegúrate de tener configurada la base de datos de testing en `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

O configura una base de datos MySQL separada para tests:

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="votaciones_testing"/>
```

### 2. Configuración de Redis para Tests

Los tests están diseñados para funcionar con y sin Redis. Configuración recomendada en `phpunit.xml`:

```xml
<!-- Para tests con Redis -->
<env name="REDIS_HOST" value="127.0.0.1"/>
<env name="REDIS_PASSWORD" value="null"/>
<env name="REDIS_PORT" value="6379"/>

<!-- Para simular entorno sin Redis -->
<env name="REDIS_HOST" value="invalid-host"/>
```

### 3. Configuración de Colas

```xml
<env name="QUEUE_CONNECTION" value="database"/>
<env name="OTP_EMAIL_QUEUE" value="otp-emails"/>
<env name="OTP_WHATSAPP_QUEUE" value="otp-whatsapp"/>
<env name="RESEND_RATE_LIMIT" value="2"/>
<env name="WHATSAPP_RATE_LIMIT" value="5"/>
```

### 4. Configuración de Email para Tests

```xml
<env name="MAIL_MAILER" value="log"/>
<env name="MAIL_HOST" value="localhost"/>
<env name="MAIL_PORT" value="2525"/>
```

## Ejecución de Tests

### Tests Individuales

```bash
# Tests unitarios específicos
php artisan test tests/Unit/RateLimit/QueueRateLimiterServiceTest.php
php artisan test tests/Unit/RateLimit/RateLimitedMiddlewareTest.php
php artisan test tests/Unit/RateLimit/WithRateLimitingTraitTest.php

# Tests de feature específicos
php artisan test tests/Feature/RateLimit/OTPRateLimitingTest.php
php artisan test tests/Feature/RateLimit/QueueWorkerIntegrationTest.php
php artisan test tests/Feature/RateLimit/FallbackBehaviorTest.php
```

### Tests por Categoría

```bash
# Todos los tests de rate limiting
php artisan test --group=rate-limiting

# Solo tests unitarios de rate limiting
php artisan test tests/Unit/RateLimit/

# Solo tests de feature de rate limiting
php artisan test tests/Feature/RateLimit/
```

### Tests con Cobertura

```bash
# Generar reporte de cobertura
php artisan test --coverage --min=80 tests/Unit/RateLimit/ tests/Feature/RateLimit/
```

## Test de Carga y Rendimiento

### Comando de Stress Testing

Se incluye un comando Artisan especial para tests de carga:

```bash
# Test básico de email (20 jobs a 10/seg)
php artisan test:rate-limiting --type=email --count=20 --rate=10

# Test de WhatsApp con monitor en vivo
php artisan test:rate-limiting --type=whatsapp --count=50 --rate=15 --monitor

# Test mixto (email + WhatsApp)
php artisan test:rate-limiting --type=both --count=100 --rate=20

# Test por duración (30 segundos a 5 jobs/seg)
php artisan test:rate-limiting --type=email --duration=30 --rate=5 --monitor
```

### Opciones del Comando

- `--type`: Tipo de jobs (email, whatsapp, both)
- `--count`: Número total de jobs a generar
- `--rate`: Jobs por segundo a generar
- `--duration`: Duración en segundos (alternativa a --count)
- `--monitor`: Ejecutar monitor de colas durante el test

## Monitoreo Durante Tests

### Monitor de Colas en Tiempo Real

```bash
# Monitor básico
php artisan otp:monitor

# Monitor con actualización rápida
php artisan otp:monitor --interval=1

# Ver métricas históricas
php artisan otp:monitor --metrics
```

### Workers para Tests Manuales

```bash
# Iniciar workers específicos para tests
php artisan queue:work --queue=otp-emails --tries=1 --timeout=60 &
php artisan queue:work --queue=otp-whatsapp --tries=1 --timeout=60 &

# Worker combinado
php artisan queue:work --queue=otp-emails,otp-whatsapp --tries=1 &
```

## Escenarios de Test Recomendados

### 1. Test de Verificación Básica
```bash
# Verificar que el sistema funciona
php artisan test tests/Unit/RateLimit/
php artisan test tests/Feature/RateLimit/OTPRateLimitingTest.php::test_otp_jobs_go_to_correct_queues
```

### 2. Test de Rate Limiting
```bash
# Verificar límites de 2 emails/seg y 5 WhatsApp/seg
php artisan test tests/Feature/RateLimit/QueueWorkerIntegrationTest.php::test_email_rate_limiting_respects_2_per_second
php artisan test tests/Feature/RateLimit/QueueWorkerIntegrationTest.php::test_whatsapp_rate_limiting_respects_5_per_second
```

### 3. Test de Fallback sin Redis
```bash
# Simular entorno sin Redis
php artisan test tests/Feature/RateLimit/FallbackBehaviorTest.php
```

### 4. Test de Carga
```bash
# Stress test moderado
php artisan test:rate-limiting --type=both --count=50 --rate=10 --monitor

# Stress test intenso (simular 2000 usuarios simultáneos)
php artisan test:rate-limiting --type=email --duration=60 --rate=100
```

## Interpretación de Resultados

### Métricas Clave

1. **Rate Limiting Efectivo**: Los jobs deben procesarse a máximo 2/seg (email) y 5/seg (WhatsApp)
2. **Encolado Correcto**: Jobs deben ir a las colas dedicadas (otp-emails, otp-whatsapp)
3. **Fallback Funcional**: Sistema debe funcionar sin Redis en desarrollo
4. **Sin Pérdida de Jobs**: Todos los jobs generados deben procesarse eventualmente

### Indicadores de Éxito

- ✅ Tests unitarios: 100% pass
- ✅ Rate limiting: Respeta límites configurados (2/seg, 5/seg)
- ✅ Fallback: Funciona sin Redis en local
- ✅ Carga: Maneja 1000+ jobs sin perder ninguno
- ✅ Monitor: Muestra métricas en tiempo real correctamente

## Troubleshooting

### Problemas Comunes

1. **Redis Connection Failed**: 
   - Verificar que Redis esté corriendo: `redis-cli ping`
   - O usar configuración sin Redis para desarrollo

2. **Tests de Queue Fallan**:
   - Limpiar colas: `php artisan queue:clear`
   - Verificar configuración de DB: `php artisan migrate:fresh --env=testing`

3. **Rate Limiting No Funciona**:
   - Verificar variables de entorno: `RESEND_RATE_LIMIT=2`, `WHATSAPP_RATE_LIMIT=5`
   - Verificar que jobs usen el trait WithRateLimiting

4. **Monitor No Muestra Datos**:
   - Verificar que hay jobs en las colas: `php artisan queue:monitor`
   - Verificar configuración de colas en config/queue.php

## Contribuir a los Tests

### Agregar Nuevos Tests

1. Seguir la estructura existente (Unit/ vs Feature/)
2. Usar el group `@group rate-limiting` en docblocks
3. Incluir setup y teardown apropiados
4. Documentar casos edge específicos

### Convenciones

- Nombres descriptivos: `test_email_rate_limiting_respects_2_per_second`
- Arrange-Act-Assert pattern
- Mocks para servicios externos (Redis, Email)
- Cleanup en tearDown()