# 🚀 Blueprint de Refactorización Modular

## 📋 Resumen Ejecutivo
Migración de estructura monolítica Laravel a arquitectura **100% modular** sin librerías externas. Cada módulo será una mini-aplicación completa. La carpeta `/app` quedará prácticamente vacía.

## 🗂️ Estado Final de /app (Prácticamente Vacío)
```
app/
├── Exceptions/
│   └── Handler.php          # Solo si tiene lógica personalizada
└── Providers/
    └── AppServiceProvider.php  # Solo si no se puede mover
```
**Todo lo demás estará en `/modules`**

## 🎯 Módulos a Migrar
1. **Core** (PRIMERO - Base global del sistema)
   - User, Auth, OTP, Tenant
   - Traits compartidos (HasAdvancedFilters, HasTenant, etc.)
   - Middleware global
   - Controladores base (AdminController, UserController, GuestController)
   - Providers esenciales
2. **Geografico**
3. **Rbac** (Roles, Permisos, Segments)
4. **Asamblea** (Piloto para módulos de negocio)
5. **Votaciones**
6. **Elecciones** (Candidaturas, Convocatorias, Postulaciones, Cargos, Periodos)
7. **Formularios**

---

## 🔧 FASE 0: Preparación Inicial (ANTES de migrar cualquier módulo)

### 1. Configurar Vite para Módulos
```typescript
// vite.config.ts - AGREGAR estas líneas
import { glob } from 'glob';

// Encontrar todos los assets de módulos
const moduleAssets = glob.sync('modules/*/Resources/js/app.js');

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.ts',
                ...moduleAssets // Incluir assets de módulos
            ],
            refresh: true,
        }),
        // ... resto
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            '@modules': path.resolve(__dirname, './modules'), // Nuevo alias
        },
    },
});
```

### 2. Configurar app.ts para Resolver Componentes Modulares
```typescript
// resources/js/app.ts - MODIFICAR función resolve
resolve: (name) => {
    // Detectar si es un módulo: Modules/NombreModulo/Componente
    const modulePattern = /^Modules\/([^\/]+)\/(.+)$/;
    const match = name.match(modulePattern);
    
    if (match) {
        const [, module, component] = match;
        // Buscar en la carpeta del módulo
        return import(`../../modules/${module}/Resources/js/Pages/${component}.vue`);
    }
    
    // Si no es módulo, buscar en resources/js/pages normal
    return resolvePageComponent(`./pages/${name}.vue`, 
        import.meta.glob('./pages/**/*.vue'));
}
```

### 3. Actualizar composer.json para Autoload
```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Modules\\": "modules/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    }
}
// Ejecutar después: composer dump-autoload
```

### 4. Crear bootstrap/providers.php (si no existe)
```php
<?php
return [
    App\Providers\AppServiceProvider::class,
    // Los módulos se agregarán aquí automáticamente
];
```

---

## 📁 ESTRUCTURA DE CADA MÓDULO

### Estructura Estándar (Módulos de Negocio)
```
modules/
└── NombreModulo/
    ├── Config/
    │   └── config.php              # Configuración del módulo
    ├── Console/
    │   └── Commands/               # Comandos Artisan
    ├── Database/
    │   ├── migrations/             # Migraciones del módulo
    │   └── seeders/                # Seeders del módulo
    ├── Http/
    │   ├── Controllers/
    │   │   ├── Admin/              # Controladores admin
    │   │   ├── User/               # Controladores usuario
    │   │   └── Guest/              # Controladores público
    │   ├── Middleware/             # Middleware específico
    │   └── Requests/               # Form Requests
    ├── Jobs/                       # Jobs del módulo
    ├── Mail/                       # Mailable classes
    ├── Models/                     # Modelos Eloquent
    ├── Observers/                  # Model Observers
    ├── Providers/
    │   └── NombreModuloServiceProvider.php
    ├── Repositories/               # Repository pattern
    ├── Resources/
    │   ├── js/
    │   │   └── Pages/
    │   │       ├── Admin/          # Vistas Inertia admin
    │   │       ├── User/           # Vistas Inertia user
    │   │       └── Guest/          # Vistas Inertia public
    │   └── lang/                   # Traducciones
    ├── Routes/
    │   ├── admin.php               # Rutas admin
    │   ├── user.php                # Rutas user
    │   └── guest.php               # Rutas públicas
    ├── Scopes/                     # Query Scopes
    ├── Services/                   # Business Logic
    ├── Traits/                     # Traits del módulo
    └── Transformers/               # API Transformers
```

### Estructura Especial: Módulo Core (Global)
```
modules/
└── Core/
    ├── Config/
    │   └── core.php                # Configuración global
    ├── Console/
    │   └── Commands/               # Comandos globales
    ├── Http/
    │   ├── Controllers/
    │   │   ├── Base/               # Controladores base para herencia
    │   │   │   ├── AdminController.php
    │   │   │   ├── UserController.php
    │   │   │   └── GuestController.php
    │   │   └── Auth/               # Controladores de autenticación
    │   ├── Middleware/             # Middleware GLOBAL
    │   │   ├── AdminMiddleware.php
    │   │   ├── UserMiddleware.php
    │   │   ├── TenantMiddleware.php
    │   │   └── HandleInertiaRequests.php
    │   └── Requests/               # Form Requests globales
    ├── Models/
    │   ├── User.php                # Modelo User (global)
    │   └── Tenant.php              # Modelo Tenant (si aplica)
    ├── Providers/
    │   └── CoreServiceProvider.php # Provider principal
    ├── Services/
    │   ├── OTPService.php         # Servicio OTP (global)
    │   └── TenantService.php      # Servicio Tenant
    ├── Traits/                     # TRAITS GLOBALES COMPARTIDOS
    │   ├── HasAdvancedFilters.php
    │   ├── HasTenant.php
    │   ├── HasGeographicFilters.php
    │   └── HasAuditLog.php
    └── Routes/
        └── auth.php                # Rutas de autenticación
```

---

## 🔄 PROCESO DE MIGRACIÓN POR MÓDULO

### Paso 1: Crear Estructura Base
```bash
# Crear carpetas del módulo
mkdir -p modules/NombreModulo/{Config,Console/Commands,Database/{migrations,seeders}}
mkdir -p modules/NombreModulo/Http/{Controllers/{Admin,User,Guest},Middleware,Requests}
mkdir -p modules/NombreModulo/{Jobs,Mail,Models,Observers,Providers,Repositories}
mkdir -p modules/NombreModulo/Resources/js/Pages/{Admin,User,Guest}
mkdir -p modules/NombreModulo/{Routes,Scopes,Services,Traits,Transformers}
```

### Paso 2: Mover Archivos (preservando git history)
```bash
# Mover controladores
git mv app/Http/Controllers/NombreModulo/Admin/* modules/NombreModulo/Http/Controllers/Admin/
git mv app/Http/Controllers/NombreModulo/User/* modules/NombreModulo/Http/Controllers/User/
git mv app/Http/Controllers/NombreModulo/Guest/* modules/NombreModulo/Http/Controllers/Guest/

# Mover modelos
git mv app/Models/NombreModulo/* modules/NombreModulo/Models/

# Mover servicios
git mv app/Services/NombreModulo/* modules/NombreModulo/Services/

# Mover repositories
git mv app/Repositories/NombreModulo/* modules/NombreModulo/Repositories/

# Mover requests
git mv app/Http/Requests/NombreModulo/* modules/NombreModulo/Http/Requests/

# Mover jobs
git mv app/Jobs/NombreModulo/* modules/NombreModulo/Jobs/

# Mover mails
git mv app/Mail/NombreModulo/* modules/NombreModulo/Mail/

# Mover vistas Inertia
git mv resources/js/pages/Admin/NombreModulo/* modules/NombreModulo/Resources/js/Pages/Admin/
git mv resources/js/pages/User/NombreModulo/* modules/NombreModulo/Resources/js/Pages/User/
git mv resources/js/pages/Guest/NombreModulo/* modules/NombreModulo/Resources/js/Pages/Guest/
```

### Paso 3: Actualizar Namespaces
```php
// Cambiar en TODOS los archivos movidos:
namespace App\Http\Controllers\NombreModulo\Admin;
// A:
namespace Modules\NombreModulo\Http\Controllers\Admin;

// Cambiar imports de modelos:
use App\Models\NombreModulo\Modelo;
// A:
use Modules\NombreModulo\Models\Modelo;

// Cambiar imports de Core:
use App\Http\Controllers\Core\AdminController;
use App\Models\Core\User;
use App\Traits\HasAdvancedFilters;
// A:
use Modules\Core\Http\Controllers\Base\AdminController;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasAdvancedFilters;
```

### Paso 4: Crear Service Provider
```php
// modules/NombreModulo/Providers/NombreModuloServiceProvider.php
<?php
namespace Modules\NombreModulo\Providers;

use Illuminate\Support\ServiceProvider;

class NombreModuloServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Cargar rutas
        $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/user.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/guest.php');
        
        // Cargar migraciones
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
        
        // Cargar traducciones
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'nombremodulo');
        
        // Cargar configuración
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'nombremodulo');
    }
    
    public function register()
    {
        // Registrar servicios si es necesario
    }
}
```

### Paso 5: Crear Archivos de Rutas
```php
// modules/NombreModulo/Routes/admin.php
<?php
use Illuminate\Support\Facades\Route;
use Modules\NombreModulo\Http\Controllers\Admin;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin/nombremodulo')
    ->name('admin.nombremodulo.')
    ->group(function () {
        Route::get('/', [Admin\NombreModuloController::class, 'index'])->name('index');
        // ... más rutas
    });

// modules/NombreModulo/Routes/user.php
// modules/NombreModulo/Routes/guest.php (similar estructura)
```

### Paso 6: Actualizar Referencias en Controladores
```php
// En controladores, cambiar:
return Inertia::render('Admin/NombreModulo/Index', $data);
// A:
return Inertia::render('Modules/NombreModulo/Admin/Index', $data);
```

### Paso 7: Registrar Service Provider
```php
// bootstrap/providers.php - Agregar:
return [
    App\Providers\AppServiceProvider::class,
    Modules\NombreModulo\Providers\NombreModuloServiceProvider::class, // Nueva línea
];
```

### Paso 8: Limpiar Estructura Antigua
```bash
# Solo después de verificar que todo funciona:
rm -rf app/Http/Controllers/NombreModulo
rm -rf app/Models/NombreModulo
rm -rf app/Services/NombreModulo
# etc...
```

---

## ✅ CHECKLIST POR MÓDULO

### [ ] Asamblea
- [ ] Crear estructura de carpetas
- [ ] Mover Controllers (Admin: 4, User: 3, Guest: 2)
- [ ] Mover Models (Asamblea, AsambleaUsuario)
- [ ] Mover Services (AsambleaService, AsambleaParticipantService, etc.)
- [ ] Mover Repositories (AsambleaRepository)
- [ ] Mover Jobs (SyncParticipantsJob, NotifyParticipantsJob)
- [ ] Mover Mail (AsambleaInvitation, AsambleaReminder)
- [ ] Mover Vistas Inertia
- [ ] Actualizar namespaces
- [ ] Crear AsambleaServiceProvider
- [ ] Crear archivos de rutas
- [ ] Actualizar referencias Inertia::render()
- [ ] Registrar en bootstrap/providers.php
- [ ] Probar funcionalidad
- [ ] Limpiar estructura antigua

### [ ] Votaciones
- [ ] Crear estructura...
- [ ] (repetir pasos)

### [ ] Elecciones
- [ ] Crear estructura...
- [ ] (repetir pasos)

### [ ] Formularios
- [ ] Crear estructura...
- [ ] (repetir pasos)

### [ ] Geografico
- [ ] Crear estructura...
- [ ] (repetir pasos)

### [ ] Core
- [ ] Crear estructura...
- [ ] Mover User, OTP, Auth, Tenant
- [ ] (repetir pasos)

### [ ] Rbac
- [ ] Crear estructura...
- [ ] Mover Roles, Permissions, Segments
- [ ] (repetir pasos)

---

## 🔍 COMANDOS DE VERIFICACIÓN

```bash
# Verificar autoload
composer dump-autoload

# Verificar rutas
php artisan route:list | grep nombremodulo

# Verificar que Vite compila
npm run build

# Limpiar cachés
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Verificar namespaces
grep -r "App\\\\Http\\\\Controllers\\\\NombreModulo" modules/
# No debe encontrar nada (todos deben ser Modules\\NombreModulo)
```

---

## ⚠️ CONSIDERACIONES ESPECIALES

### Arquitectura 100% Modular
- **TODO** va en `/modules`, incluyendo Core
- La carpeta `/app` quedará prácticamente vacía, solo con:
  - `Providers/` (solo los esenciales de Laravel que no se pueden mover)
  - `Exceptions/Handler.php` (si es necesario)
  - Archivos de Laravel que no se pueden mover

### Módulo Core como Base Global
- **Traits Compartidos**: Van en `modules/Core/Traits/`
- **Middleware Global**: Va en `modules/Core/Http/Middleware/`
- **Controladores Base**: Van en `modules/Core/Http/Controllers/Base/`
- **Modelos Globales**: User, Tenant en `modules/Core/Models/`
- Todos los módulos importarán desde Core: `use Modules\Core\Traits\HasAdvancedFilters;`

### Dependencias entre Módulos
```
Core (sin dependencias)
├── Geografico (depende de Core)
├── Rbac (depende de Core)
├── Formularios (depende de Core)
├── Asamblea (depende de Core, Geografico, Rbac)
├── Votaciones (depende de Core, Asamblea, Rbac)
└── Elecciones (depende de Core, Geografico, Formularios, Rbac)
```

### Orden de Migración OBLIGATORIO
1. **Core** (PRIMERO - base de todo)
2. **Geografico** (datos geográficos)
3. **Rbac** (roles y permisos)
4. **Asamblea** (piloto de negocio)
5. **Votaciones**
6. **Formularios**
7. **Elecciones**

---

## 🚨 ROLLBACK
Si algo sale mal:
```bash
git checkout main
git branch -D refactor-modular
```
Todo vuelve al estado original.

---

## 📚 LECCIONES APRENDIDAS (IMPORTANTE!)

### ⚠️ Problemas Encontrados y Soluciones

#### 1. **Namespaces No Se Actualizan Automáticamente**
**Problema**: Al mover archivos, los namespaces siguen siendo `App\*`
**Solución**: 
- Crear script Python para actualizar namespaces masivamente
- Verificar SIEMPRE con `composer dump-autoload` después de cambios
- Los archivos movidos necesitan cambiar de `App\Models\Asamblea\*` a `Modules\Asamblea\Models\*`

#### 2. **Conflicto de Clases Duplicadas**
**Problema**: Al COPIAR Core, existen dos clases con el mismo nombre
**Solución**:
- Actualizar namespaces en módulo Core INMEDIATAMENTE después de copiar
- Core debe cambiar a `Modules\Core\*` mientras que `/app` mantiene `App\*`

#### 3. **Imports Dinámicos de Vite**
**Problema**: `import(\`../../modules/${module}/...\`)` falla en producción
**Solución**:
```typescript
// Pre-cargar TODOS los componentes de módulos
const modulePages = import.meta.glob<DefineComponent>('../../modules/*/Resources/js/Pages/**/*.vue');
const regularPages = import.meta.glob<DefineComponent>('./pages/**/*.vue');

// Luego usar los pre-cargados
if (modulePath in modulePages) {
    return modulePages[modulePath]();
}
```

#### 4. **Rutas Deben Actualizar Imports**
**Problema**: Las rutas siguen buscando `App\Http\Controllers\Asamblea\*`
**Solución**:
- Actualizar TODOS los archivos de rutas:
  - `routes/admin.php`
  - `routes/user.php` 
  - `routes/guest.php`
- Cambiar imports a `Modules\Asamblea\Http\Controllers\*`

#### 5. **Service Providers No Se Cargan**
**Problema**: Los módulos no funcionan aunque existan
**Solución**:
- Registrar en `bootstrap/providers.php`:
```php
return [
    // ...existentes
    Modules\Core\Providers\CoreServiceProvider::class,
    Modules\Asamblea\Providers\AsambleaServiceProvider::class,
];
```

#### 6. **Git MV Falla con Archivos Nuevos**
**Problema**: `git mv` falla si el archivo no está en git
**Solución**:
- Usar `mv` normal como fallback
- El script Python debe manejar ambos casos

#### 7. **Referencias Entre Módulos**
**Problema**: Asamblea usa modelos de Geografico y Votaciones
**Solución Temporal**:
- Mantener referencias a `App\Models\Geografico\*` hasta migrar ese módulo
- Actualizar referencias gradualmente conforme se migran módulos

### ✅ Scripts Python Creados

| Script | Propósito | Estado | Reutilizable |
|--------|-----------|--------|--------------|
| `modularize.py` | Crear estructura y mover archivos | ⚠️ Funcional con limitaciones | SÍ - Ajustar nombres de módulos |
| `update_namespaces.py` | Actualizar namespaces básicos | ❌ Incompleto | NO - Usar fix_all_namespaces.py |
| `fix_all_namespaces.py` | Corregir todas las referencias | ✅ Funcional | SÍ - Modificar módulo target |
| `fix_core_namespaces.py` | Específico para Core | ✅ Completo | NO - Ya no necesario |

### 📋 Checklist Post-Migración

Después de migrar CADA módulo:
- [ ] Ejecutar `python3 fix_all_namespaces.py` (adaptado al módulo)
- [ ] Actualizar imports en `routes/*.php`
- [ ] Registrar ServiceProvider en `bootstrap/providers.php`
- [ ] Ejecutar `composer dump-autoload`
- [ ] Ejecutar `npm run build`
- [ ] Limpiar cachés: `php artisan cache:clear && php artisan config:clear`
- [ ] Verificar rutas: `php artisan route:list | grep nombre-modulo`
- [ ] Probar en navegador

### 🔧 Configuración Crítica en app.ts

**OBLIGATORIO** para que funcionen los módulos:
```typescript
// Pre-cargar todos los componentes de módulos
const modulePages = import.meta.glob<DefineComponent>('../../modules/*/Resources/js/Pages/**/*.vue');
```
Sin esto, Vite NO puede resolver componentes de módulos en producción.