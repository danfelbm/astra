# 🔒 ACTUALIZACIÓN CRÍTICA: Sistema de Rate Limiting
**Fecha**: 2025-09-10  
**Prioridad**: CRÍTICA  
**Módulos Afectados**: Core, Campañas

## 🚨 PROBLEMAS CORREGIDOS

### 1. OTP Sin Rate Limiting (CRÍTICO)
**Problema**: Los Jobs de OTP (`SendOTPWhatsAppJob` y `SendOTPEmailJob`) NO estaban aplicando rate limiting debido a namespaces incorrectos en el trait `WithRateLimiting`.

**Solución Aplicada**:
- Agregado `Modules\Core\Jobs\SendOTPWhatsAppJob` al switch
- Agregado `Modules\Core\Jobs\SendOTPEmailJob` al switch
- Mantenidos los namespaces antiguos por compatibilidad

### 2. Límite WhatsApp Muy Alto (CRÍTICO)
**Problema**: El límite de 5 mensajes/segundo ponía en riesgo de bloqueo con Evolution API.

**Solución Aplicada**:
- Reducido límite de WhatsApp de 5 a 1 mensaje por segundo
- Aplicado en todas las configuraciones y factory methods

## ✅ CAMBIOS IMPLEMENTADOS

### Archivo: `modules/Core/Jobs/Middleware/WithRateLimiting.php`
```php
// ANTES (no funcionaba):
case 'App\Jobs\Core\SendOTPWhatsAppJob':
case 'App\Jobs\Core\SendOTPEmailJob':

// DESPUÉS (funciona):
case 'Modules\Core\Jobs\SendOTPWhatsAppJob':  // Namespace correcto
case 'Modules\Core\Jobs\SendOTPEmailJob':      // Namespace correcto
```

### Archivo: `config/queue.php`
```php
'rate_limits' => [
    'resend' => env('RESEND_RATE_LIMIT', 2),     // Sin cambios
    'whatsapp' => env('WHATSAPP_RATE_LIMIT', 1), // Cambiado de 5 a 1
],
```

### Archivo: `modules/Campanas/Config/campanas.php`
```php
'rate_limits' => [
    'email' => env('CAMPANAS_EMAIL_RATE_LIMIT', 2),     // Sin cambios
    'whatsapp' => env('CAMPANAS_WHATSAPP_RATE_LIMIT', 1), // Cambiado de 5 a 1
],
```

### Archivo: `modules/Core/Jobs/Middleware/RateLimited.php`
```php
// Factory methods actualizados con nuevo límite por defecto:
public static function forWhatsApp(): static
{
    $limit = config('queue.rate_limits.whatsapp', 1);  // Valor por defecto: 1
    return new static('otp_whatsapp', $limit, 1, 'whatsapp');
}

public static function forCampanaWhatsApp(): static
{
    $limit = config('campanas.rate_limits.whatsapp', 1);  // Valor por defecto: 1
    return new static('campana_whatsapp', $limit, 1, 'campana');
}
```

## 📊 ESTADO ACTUAL DEL SISTEMA

| Sistema | Rate Limit | Estado | Clave Redis |
|---------|------------|--------|-------------|
| **OTP WhatsApp** | 1/segundo | ✅ Funcionando | `whatsapp:otp_whatsapp` |
| **OTP Email** | 2/segundo | ✅ Funcionando | `resend:otp_email` |
| **Campañas WhatsApp** | 1/segundo | ✅ Funcionando | `campana:campana_whatsapp` |
| **Campañas Email** | 2/segundo | ✅ Funcionando | `campana:campana_email` |

## 🎯 GARANTÍAS DEL SISTEMA

1. **OTP Protegido**: Ahora todos los envíos de OTP tienen rate limiting activo
2. **WhatsApp Seguro**: Máximo 1 mensaje/segundo evita bloqueos con Evolution API
3. **Atomicidad Global**: Redis garantiza límites compartidos entre todos los workers
4. **Sin Conflictos**: Claves separadas para OTP y Campañas

## ⚙️ CONFIGURACIÓN RECOMENDADA (.env)

```env
# Rate Limits Seguros para WhatsApp
WHATSAPP_RATE_LIMIT=1              # OTP WhatsApp - Max 1/seg
CAMPANAS_WHATSAPP_RATE_LIMIT=1     # Campañas WhatsApp - Max 1/seg

# Rate Limits para Email (sin cambios)
RESEND_RATE_LIMIT=2                # OTP Email - Max 2/seg
CAMPANAS_EMAIL_RATE_LIMIT=2        # Campañas Email - Max 2/seg
```

## 🔍 VERIFICACIÓN

### Comando de Verificación
```bash
php artisan tinker --execute="
use Modules\Core\Jobs\SendOTPWhatsAppJob;
\$job = new SendOTPWhatsAppJob('+573001234567', '123456', 'Test');
\$middlewares = \$job->middleware();
echo 'Middlewares: ' . count(\$middlewares) . PHP_EOL;
echo 'WhatsApp limit: ' . config('queue.rate_limits.whatsapp') . '/seg' . PHP_EOL;
"
```

### Resultado Esperado
```
Middlewares: 1
WhatsApp limit: 1/seg
```

## ⚠️ NOTAS IMPORTANTES

1. **Evolution API**: El límite de 1/segundo es conservador pero seguro
2. **Desarrollo Local**: Si Redis no está disponible, el sistema permite ejecución sin límites
3. **Producción**: Redis es OBLIGATORIO para que funcione el rate limiting
4. **Monitoreo**: Revisar logs de Evolution API para detectar posibles errores 429

## 📈 IMPACTO EN THROUGHPUT

### WhatsApp (Reducción del 80%)
- **Antes**: 5/seg = 300/min = 18,000/hora
- **Ahora**: 1/seg = 60/min = 3,600/hora
- **Razón**: Seguridad sobre velocidad para evitar bloqueos

### Email (Sin cambios)
- **Mantiene**: 2/seg = 120/min = 7,200/hora

## 🚀 SIGUIENTES PASOS

1. **Monitorear** respuestas de Evolution API durante 48 horas
2. **Ajustar** si Evolution permite más throughput sin riesgo
3. **Implementar** circuit breaker para protección adicional
4. **Considerar** rate limiting dinámico basado en respuestas

---

**IMPORTANTE**: Estos cambios son críticos para la seguridad del sistema. NO revertir sin análisis previo.