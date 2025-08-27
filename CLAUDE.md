# 🏗️ Plan de Refactorización: Arquitectura Modular y Separación de Espacios

## prompt inicial

Quiero que analices la posibilidad de separar entornos para los usuarios. Actualmente todo está combinado y a través de permisos se permite acceso o no a ciertos lugares; hay páginas públicas sin autenticación, otras que requieren autenticación, y otras administrativas que se acceden solo con permisos. Pero quiero pensar algo más como InvisionCommunity, WordPress, Joomla o Decidim. Existe un entorno público para guests, otro entorno autenticado para usuarios, y otro entorno administrativo.

Actualmente existe un login via OTP, que no creo que haga falta crear otra autenticación distinta (no creo, pero quiero saber tu opinión). Distintos layouts para cada entorno, ciertamente; el mejor ejemplo es Decidim, donde los usuarios y visitantes comparten visualmente un entorno, pero ciertamente los usuarios autenticados son los únicos que pueden interactuar con cosas del sistema. Quiero un análisis profundo y riguroso.

También ocurre algo en el file tree y es que como Laravel no es nativamente modular, los archivos de modelos vistas, controladores, etc, están todos combinados. Solo las vistas (pages) están más o menos compartimentadas bajo la lógica actual; pero los modelos y controladores parecen estar todos como combinados. Quisiera explorar la posibilidad de mejorar la estructuración de las carpetas para organizar mucho mejor el código y sentar unas bases sólidas y fuertes que le permitan a esta plataforma crecer con más desarrollos y módulos.

Ultrathink.

## 📋 Estrategia de Implementación

### ✅ **RECOMENDACIÓN PRINCIPAL: Subcarpetas en Estructura Nativa**

**NO crear** `/app/Modules/` nueva. En su lugar, usar **subcarpetas dentro de las carpetas existentes de Laravel**.

🔐 Controladores Base por Espacio

// app/Http/Controllers/Core/AdminController.php
namespace App\Http\Controllers\Core;

abstract class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:access-admin']);
    }

    protected function getLayout(): string
    {
        return 'AdminLayout';
    }

    protected function getNavigationItems(): array
    {
        // Navegación específica de admin
        return config('navigation.admin');
    }
}

// app/Http/Controllers/Core/UserController.php
namespace App\Http\Controllers\Core;

abstract class UserController extends Controller  
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    protected function getLayout(): string
    {
        return 'UserLayout';
    }

    protected function shouldShowAdminLink(): bool
    {
        return auth()->user()->hasRole(['admin', 'super_admin']);
    }
}

// app/Http/Controllers/Core/GuestController.php
namespace App\Http\Controllers\Core;

abstract class GuestController extends Controller
{
    protected function getLayout(): string
    {
        return 'GuestLayout';
    }

    protected function getPublicNavigation(): array
    {
        return [
            ['label' => 'Inicio', 'route' => 'welcome'],
            ['label' => 'Votaciones Públicas', 'route' => 'public.votaciones'],
            ['label' => 'Resultados', 'route' => 'public.resultados'],
        ];
    }
}

📂 Ejemplo Práctico: Módulo Assembly

// app/Http/Controllers/Assembly/Admin/AssemblyAdminController.php
namespace App\Http\Controllers\Assembly\Admin;

use App\Http\Controllers\Core\AdminController;
use App\Traits\HasAdvancedFilters;
use App\Models\Assembly\Assembly;

class AssemblyAdminController extends AdminController
{
    use HasAdvancedFilters;

    public function index()
    {
        return Inertia::render('Admin/Asambleas/Index', [
            'asambleas' => $this->applyFilters(Assembly::query()),
            'layout' => $this->getLayout(),
            'navigation' => $this->getNavigationItems(),
        ]);
    }
}

// app/Http/Controllers/Assembly/User/MyAssembliesController.php
namespace App\Http\Controllers\Assembly\User;

use App\Http\Controllers\Core\UserController;

class MyAssembliesController extends UserController
{
    public function index()
    {
        $user = auth()->user();

        return Inertia::render('Asambleas/MisAsambleas', [
            'asambleas' => $user->asambleas()->active()->get(),
            'layout' => $this->getLayout(),
            'canAccessAdmin' => $this->shouldShowAdminLink(),
        ]);
    }
}

// app/Http/Controllers/Assembly/Guest/PublicAssemblyController.php
namespace App\Http\Controllers\Assembly\Guest;

use App\Http\Controllers\Core\GuestController;
use App\Models\Assembly\Assembly;

class PublicAssemblyController extends GuestController
{
    public function index()
    {
        return Inertia::render('Public/Asambleas/Index', [
            'asambleas' => Assembly::public()->upcoming()->get(),
            'layout' => $this->getLayout(),
            'navigation' => $this->getPublicNavigation(),
        ]);
    }
}

🛣️ Rutas Organizadas por Espacio y Módulo

// routes/modules/assembly.php
use App\Http\Controllers\Assembly\Admin;
use App\Http\Controllers\Assembly\User;
use App\Http\Controllers\Assembly\Guest;

// Rutas públicas (Guest)
Route::prefix('asambleas')->name('public.asambleas.')->group(function () {
    Route::get('/', [Guest\PublicAssemblyController::class, 'index'])->name('index');
    Route::get('/{id}/resultados', [Guest\PublicAssemblyController::class,
'results'])->name('results');
});

// Rutas de usuario autenticado
Route::middleware(['auth'])->prefix('app/asambleas')->name('user.asambleas.')->group(function 
() {
    Route::get('/mis-asambleas', [User\MyAssembliesController::class,
'index'])->name('index');
    Route::post('/{id}/join', [User\JoinAssemblyController::class, 'join'])->name('join');
});

// Rutas administrativas
Route::middleware(['auth',
'can:access-admin'])->prefix('admin/asambleas')->name('admin.asambleas.')->group(function () {
    Route::get('/', [Admin\AssemblyAdminController::class, 'index'])->name('index');
    Route::post('/', [Admin\AssemblyAdminController::class, 'store'])->name('store');
    // etc...
});

📊 Ventajas de Esta Estructura

Para Core/Shared:

✅ Claro qué es compartido vs específico de módulo
✅ Reutilizable - los módulos extienden de Core
✅ No duplicación - FilterService, ImportService en un solo lugar
✅ Fácil de encontrar - todo lo universal en Core/

Para los Tres Entornos:

✅ Separación clara - Admin/, User/, Guest/ dentro de cada módulo
✅ Herencia lógica - cada espacio tiene su controlador base
✅ Middleware centralizado - definido en el controlador base
✅ Layout automático - cada espacio sabe su layout
✅ Navegación contextual - cada espacio tiene su menú

🚀 Implementación Gradual Sugerida

# 1. Crear estructura Core primero
mkdir -p app/Http/Controllers/Core
mkdir -p app/Models/Core
mkdir -p app/Services/Core

# 2. Mover elementos universales a Core
mv app/Models/User.php app/Models/Core/
mv app/Services/OTPService.php app/Services/Core/

# 3. Crear controladores base
touch app/Http/Controllers/Core/{AdminController,UserController,GuestController}.php

# 4. Migrar UN módulo como prueba (ej: Assembly)
mkdir -p app/Http/Controllers/Assembly/{Admin,User,Guest}
mkdir -p app/Models/Assembly
mkdir -p app/Services/Assembly

# 5. Mover y refactorizar controladores existentes
mv app/Http/Controllers/Admin/AsambleaController.php \
    app/Http/Controllers/Assembly/Admin/AssemblyAdminController.php

💡 Respuesta Directa

1. Core/Universal: Sí, crear carpeta Core/ en cada carpeta nativa para elementos compartidos
2. Tres Entornos: Subcarpetas Admin/, User/, Guest/ dentro de cada módulo
3. Herencia: Controladores base en Core que definen comportamiento por espacio
4. Traits: Se mantienen en /app/Traits (ya funcionan bien ahí)

Esta estructura te da:
- Claridad sobre qué es core vs módulo
- Separación clara de los tres espacios
- Flexibilidad para que cada módulo implemente solo los espacios que necesita
- Consistencia en toda la aplicación

## 📦 **1. Estructura Core/Universal**

```
app/
├── Http/Controllers/
│   ├── Core/                    # Funcionalidad compartida
│   │   ├── BaseController.php
│   │   ├── ImportController.php # CSV imports genérico
│   │   └── FilterableController.php
│   ├── Assembly/
│   │   └── AssemblyController.php extends FilterableController
│   └── Voting/
│       └── VotacionController.php extends FilterableController
│
├── Models/
│   ├── Core/                    # Modelos base/compartidos
│   │   ├── User.php
│   │   ├── Tenant.php
│   │   ├── Role.php
│   │   ├── Permission.php
│   │   └── CsvImport.php        # Usado por todos los módulos
│   ├── Assembly/
│   │   └── Assembly.php
│   └── Voting/
│       └── Votacion.php
│
├── Services/
│   ├── Core/                    # Servicios universales
│   │   ├── OTPService.php
│   │   ├── TenantService.php
│   │   ├── FilterService.php    # Para AdvancedFilters
│   │   ├── ImportService.php    # CSV imports
│   │   └── PermissionService.php
│   └── Assembly/
│       └── AssemblyService.php
│
├── Traits/                       # SE MANTIENE COMO ESTÁ
│   ├── HasAdvancedFilters.php   # Universal
│   ├── HasTenant.php
│   └── HasAuditLog.php
```

## 🎨 **2. Estructura por Espacio + Módulo**

```
app/Http/Controllers/
├── Core/
│   ├── AdminController.php      # Base para todos los admin
│   ├── UserController.php       # Base para todos los user
│   └── GuestController.php      # Base para todos los guest
│
├── Assembly/
│   ├── Admin/                   # Espacio Admin
│   │   ├── AssemblyAdminController.php
│   │   └── ParticipantManagementController.php
│   ├── User/                    # Espacio User
│   │   ├── MyAssembliesController.php
│   │   └── JoinAssemblyController.php
│   └── Guest/                   # Espacio Guest/Public
│       ├── PublicAssemblyController.php
│       └── AssemblyResultsController.php
│
├── Voting/
│   ├── Admin/
│   │   ├── VotacionAdminController.php
│   │   └── VotacionReportsController.php
│   ├── User/
│   │   ├── MyVotesController.php
│   │   └── VoteController.php
│   └── Guest/
│       └── PublicResultsController.php
│
└── Elections/
    ├── Admin/
    │   ├── CandidaturaAdminController.php
    │   └── ConvocatoriaAdminController.php
    ├── User/
    │   ├── MyCandidaturaController.php
    │   └── PostulacionController.php
    └── Guest/
        └── PublicConvocatoriasController.php
```

## 🚀 **3. Fases de Implementación**

### **Fase 1: Core y Controladores Base (1 día)**
1. Crear estructura Core en Controllers, Models, Services
2. Mover elementos universales a Core/
3. Crear controladores base AdminController, UserController, GuestController
4. Actualizar namespaces y autoloading

### **Fase 2: Migración por Módulos (1 semana)**
1. **Assembly** - Asambleas y Zoom (2-3 horas)
2. **Voting** - Votaciones y Votos (2 horas)
3. **Elections** - Candidaturas, Convocatorias, Postulaciones (4-5 horas)
4. **Forms** - Formularios dinámicos (2-3 horas)

### **Fase 3: Rutas y Middleware (2 días)**
1. Reorganizar rutas por módulo en `routes/modules/`
2. Actualizar RouteServiceProvider
3. Configurar middleware por espacio
4. Testing de rutas

### **Fase 4: Testing y Documentación (1 día)**
1. Actualizar tests para nuevos namespaces
2. Documentar nueva estructura
3. Crear guía de desarrollo para nuevos módulos

## 📊 **Estimación Total: 10-12 días**


⏺ ✅ Refactorización a Español COMPLETADA

  📊 Resultados Finales:

  - 90 archivos procesados exitosamente
  - 80 archivos movidos a carpetas en español
  - 10 archivos actualizados solo en contenido
  - 37 cambios en rutas
  - 14 directorios vacíos eliminados

  🔄 Traducciones Aplicadas (Minimalista):

  Solo se tradujeron los módulos de dominio:
  Assembly → Asamblea
  Elections → Elecciones
  Forms → Formularios
  Geographic → Geografico
  Voting → Votaciones

  ✅ Estructura Mantenida en Inglés:

  - Controllers, Models, Services, Jobs, Mail
  - Core, Auth, Settings, Configuration
  - Middleware, Providers, Traits

  🎯 Ejemplos de Cambios:

  // Antes
  use App\Models\Assembly\Asamblea;
  use App\Services\Voting\TokenService;

  // Después
  use App\Models\Asamblea\Asamblea;
  use App\Services\Votaciones\TokenService;

  ✅ Verificación Exitosa:

  - Todos los modelos funcionan: User (142,553), Asamblea (3), Candidatura (16)
  - Rutas correctamente actualizadas con namespaces en español
  - Cachés limpiados
  - PSR-4 autoloading funcionando

  La aplicación está lista con estructura híbrida: base Laravel en inglés + módulos de negocio 
  en español.

🏢 Análisis de Separación de Entornos

  📉 Estado Actual

  El sistema actualmente tiene una separación débil entre entornos:

  1. Mezcla de Rutas
    - Rutas admin y usuario en el mismo archivo web.php
    - Sin agrupamiento claro por tipo de usuario
    - Rutas públicas entremezcladas
  2. Un Solo Layout Base
    - AppLayout usado tanto para admin como usuarios
    - Sidebar siempre visible con condicionales
    - No hay distinción visual clara
  3. Middleware Inconsistente
    - Uso mixto de admin y permission
    - Sin middleware específico para usuarios regulares

  ✅ Propuesta de Arquitectura de 3 Capas

  1. Capa Guest (Público sin autenticación)

  Características:
  - Acceso sin login
  - Layout minimalista con navbar pública
  - Solo información general y formularios públicos

  Implementación:
  // routes/guest.php
  Route::name('public.')->group(function () {
      Route::get('/', [PublicController::class, 'home']);
      Route::get('/votaciones', [PublicVotacionController::class, 'index']);
      Route::get('/resultados/{token}', [TokenVerificationController::class, 'show']);
  });

  Layout: GuestLayout.vue con navbar simple, footer, sin sidebar

  2. Capa User (Usuarios autenticados no-admin)

  Características:
  - Requiere autenticación
  - Dashboard personalizado de usuario
  - Navbar con menú de usuario
  - Sin acceso a funciones administrativas

  Implementación:
  // routes/user.php
  Route::middleware(['auth', 'user'])->prefix('u')->name('user.')->group(function () {
      Route::get('/dashboard', [UserDashboardController::class, 'index']);
      Route::get('/mis-votaciones', [UserVotacionController::class, 'index']);
      Route::get('/mi-perfil', [UserProfileController::class, 'show']);
  });

  Layout: UserLayout.vue con navbar horizontal, menú de usuario, sin sidebar admin

  3. Capa Admin (Panel administrativo)

  Características:
  - Requiere rol administrativo
  - Sidebar con navegación completa
  - Herramientas de gestión
  - Acceso a reportes y configuración

  Implementación:
  // routes/admin.php
  Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
      Route::get('/dashboard', [AdminDashboardController::class, 'index']);
      // ... resto de rutas admin
  });

  Layout: AdminLayout.vue con sidebar, breadcrumbs, tema oscuro opcional