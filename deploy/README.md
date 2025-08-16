# 🚀 Guía de Despliegue en Producción

## 📋 Requisitos Previos

- PHP 8.2+
- MariaDB/MySQL 8.0+
- Redis 7.2.4 (ya instalado en Node 194355)
- Systemd (disponible en todas las distribuciones modernas)
- Usuario: `litespeed` (o ajustar en los archivos .service)

## 🗂️ Estructura del Proyecto

```
/var/www/webroot/ROOT/          # Directorio de Laravel
├── artisan
├── bootstrap/
├── config/
├── database/
├── public/                     # Document root del servidor web
├── resources/
├── routes/
├── storage/                    # Debe tener permisos 775
│   └── logs/                   # Logs de workers
└── vendor/
```

## ⚡ Instalación Rápida

### 1️⃣ Subir Código al Servidor

```bash
# Desde tu máquina local
rsync -avz --exclude='vendor' --exclude='node_modules' --exclude='.env' \
  ./ usuario@servidor:/var/www/webroot/ROOT/
```

### 2️⃣ Configurar Laravel en el Servidor

```bash
cd /var/www/webroot/ROOT

# Instalar dependencias
composer install --optimize-autoloader --no-dev

# Copiar y configurar .env
cp deploy/.env.production .env
nano .env  # Ajustar credenciales

# Generar key de aplicación
php artisan key:generate

# Ejecutar migraciones
php artisan migrate --force

# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permisos
chown -R litespeed:litespeed storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### 3️⃣ Instalar Workers con Systemd

```bash
cd /var/www/webroot/ROOT/deploy

# Ejecutar script de instalación
sudo bash install.sh

# Verificar que todo esté funcionando
sudo systemctl status 'laravel-queue@*'
```

## 🔧 Gestión de Workers

### Comandos Básicos

```bash
# Ver estado
sudo systemctl status 'laravel-queue@*'

# Reiniciar todos los workers
sudo systemctl restart 'laravel-queue@*'

# Detener todos
sudo systemctl stop 'laravel-queue@*'

# Iniciar todos
sudo systemctl start 'laravel-queue@*'

# Ver logs en tiempo real
sudo journalctl -u 'laravel-queue@*' -f
```

### Script de Gestión

```bash
# Usar el script helper
sudo bash deploy/manage-workers.sh status
sudo bash deploy/manage-workers.sh restart
sudo bash deploy/manage-workers.sh logs
```

## 📊 Monitoreo

### Comando de Monitoreo OTP

```bash
# Ver métricas una vez
php artisan otp:monitor

# Monitoreo en tiempo real
php artisan otp:monitor --live
```

### Verificar Redis

```bash
redis-cli ping
# Debe responder: PONG

redis-cli info stats
# Ver estadísticas
```

### Verificar Cola

```bash
php artisan queue:monitor
```

## 🔄 Actualización del Código

Cuando necesites actualizar el código:

```bash
cd /var/www/webroot/ROOT

# 1. Poner en mantenimiento
php artisan down

# 2. Actualizar código
git pull origin main
# O usar rsync desde local

# 3. Actualizar dependencias
composer install --optimize-autoloader --no-dev

# 4. Ejecutar migraciones
php artisan migrate --force

# 5. Limpiar y reconstruir cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Reiniciar workers
sudo systemctl restart 'laravel-queue@*'

# 7. Quitar mantenimiento
php artisan up
```

## 🛡️ Seguridad

### Checklist de Seguridad

- [ ] `APP_DEBUG=false` en .env
- [ ] `APP_ENV=production` en .env
- [ ] Redis protegido (si es necesario, configurar password)
- [ ] Base de datos con password fuerte
- [ ] Firewall configurado (solo puertos necesarios)
- [ ] HTTPS habilitado en el servidor web
- [ ] Logs rotados automáticamente
- [ ] Backups configurados

### Configuración de Firewall

```bash
# Solo permitir puertos necesarios
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

## 📈 Optimización para Alta Carga

### Configuración de Workers

- **Application Server 1**: 8 workers
- **Application Server 2**: 8 workers
- **Total**: 16 workers concurrentes

### Redis como Cache Central

Todos los servidores apuntan al mismo Redis (Node 194355):
- Cache compartido
- Sesiones compartidas
- Cola centralizada

### Capacidad Esperada

Con esta configuración:
- ✅ 1000+ usuarios por minuto
- ✅ 2000+ usuarios concurrentes
- ✅ Tiempo de respuesta < 500ms
- ✅ Alta disponibilidad

## 🐛 Troubleshooting

### Workers no arrancan

```bash
# Verificar logs
sudo journalctl -u laravel-queue@1 -n 50

# Verificar permisos
ls -la /var/www/webroot/ROOT/storage/logs/

# Verificar PHP
which php
php -v
```

### Redis no conecta

```bash
# Verificar conexión
redis-cli -h IP_REDIS ping

# En Laravel
php artisan tinker
>>> Redis::ping();
```

### OTPs no se envían

```bash
# Verificar que esté en modo asíncrono
grep OTP_SEND_IMMEDIATELY .env
# Debe ser: OTP_SEND_IMMEDIATELY=false

# Ver jobs en cola
php artisan queue:monitor

# Ver jobs fallidos
php artisan queue:failed
```

## 📞 Soporte

Si encuentras problemas:

1. Revisa los logs: `/var/www/webroot/ROOT/storage/logs/`
2. Ejecuta: `php artisan otp:monitor`
3. Verifica workers: `sudo systemctl status 'laravel-queue@*'`

## ✅ Checklist Final

- [ ] Laravel instalado en `/var/www/webroot/ROOT`
- [ ] `.env` configurado correctamente
- [ ] Redis funcionando (Node 194355)
- [ ] Workers ejecutándose con systemd
- [ ] Scheduler configurado con systemd timer
- [ ] Permisos correctos en `storage/`
- [ ] `OTP_SEND_IMMEDIATELY=false`
- [ ] Monitoreo funcionando

¡Sistema listo para producción! 🎉