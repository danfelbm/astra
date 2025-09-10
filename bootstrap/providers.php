<?php

return [
    Modules\Core\Providers\AppServiceProvider::class,
    Modules\Core\Providers\OptimizationServiceProvider::class,
    
    // Módulos
    Modules\Core\Providers\CoreServiceProvider::class,
    Modules\Asamblea\Providers\AsambleaServiceProvider::class,

    
    // Módulos migrados
    Modules\Tenant\Providers\TenantServiceProvider::class,
    Modules\Rbac\Providers\RbacServiceProvider::class,
    Modules\Formularios\Providers\FormulariosServiceProvider::class,
    Modules\Elecciones\Providers\EleccionesServiceProvider::class,
    Modules\Imports\Providers\ImportsServiceProvider::class,
    Modules\Users\Providers\UsersServiceProvider::class,
    Modules\Geografico\Providers\GeograficoServiceProvider::class,
    Modules\Votaciones\Providers\VotacionesServiceProvider::class,
    Modules\Configuration\Providers\ConfigurationServiceProvider::class,
    Modules\Campanas\Providers\CampanasServiceProvider::class,
];
