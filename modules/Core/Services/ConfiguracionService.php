<?php

namespace Modules\Core\Services;

use Modules\Core\Models\Configuracion;
use Illuminate\Support\Facades\Storage;

class ConfiguracionService
{
    /**
     * Obtener valor de configuración
     */
    public static function obtener(string $clave, $default = null)
    {
        return Configuracion::obtener($clave, $default);
    }

    /**
     * Establecer configuración simple
     */
    public static function establecer(string $clave, $valor, string $tipo = 'string', array $opciones = [])
    {
        return Configuracion::establecer($clave, $valor, $tipo, $opciones);
    }

    /**
     * Configurar múltiples valores de una vez
     */
    public static function configurarVarios(array $configuraciones): bool
    {
        foreach ($configuraciones as $config) {
            if (!isset($config['clave'], $config['valor'])) {
                continue;
            }

            self::establecer(
                $config['clave'],
                $config['valor'],
                $config['tipo'] ?? 'string',
                $config['opciones'] ?? []
            );
        }

        return true;
    }

    /**
     * Obtener configuraciones de logo específicamente
     */
    public static function obtenerConfiguracionLogo(): array
    {
        return [
            'logo_display' => self::obtener('app.logo_display', 'logo_text'),
            'logo_text' => self::obtener('app.logo_text', 'Laravel Starter Kit'),
            'logo_file' => self::obtener('app.logo_file', null),
        ];
    }

    /**
     * Establecer configuración de logo
     */
    public static function configurarLogo(array $datos): bool
    {
        $configuraciones = [
            [
                'clave' => 'app.logo_display',
                'valor' => $datos['logo_display'],
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'logo',
                    'publico' => true,
                    'descripcion' => 'Tipo de visualización del logo en el sidebar',
                    'validacion' => ['in:logo_text,logo_only']
                ]
            ],
            [
                'clave' => 'app.logo_text',
                'valor' => $datos['logo_text'],
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'logo',
                    'publico' => true,
                    'descripcion' => 'Texto que aparece junto al logo',
                    'validacion' => ['required', 'string', 'max:50']
                ]
            ]
        ];

        // Manejar archivo de logo
        if (isset($datos['logo_file'])) {
            $configuraciones[] = [
                'clave' => 'app.logo_file',
                'valor' => $datos['logo_file'],
                'tipo' => 'file',
                'opciones' => [
                    'categoria' => 'logo',
                    'publico' => true,
                    'descripcion' => 'Archivo de logo personalizado',
                    'validacion' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg', 'max:2048']
                ]
            ];
        }

        return self::configurarVarios($configuraciones);
    }

    /**
     * Manejar upload de archivo de logo
     */
    public static function manejarUploadLogo($archivo): ?string
    {
        if (!$archivo) {
            return null;
        }

        // Eliminar logo anterior si existe
        $logoAnterior = self::obtener('app.logo_file');
        if ($logoAnterior && Storage::disk('public')->exists($logoAnterior)) {
            Storage::disk('public')->delete($logoAnterior);
        }

        // Guardar nuevo logo
        $path = $archivo->store('logos', 'public');
        
        return $path;
    }

    /**
     * Eliminar logo anterior
     */
    public static function eliminarLogoAnterior(?string $logoPath): void
    {
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            Storage::disk('public')->delete($logoPath);
        }
    }

    /**
     * Obtener todas las configuraciones públicas para el frontend
     */
    public static function obtenerConfiguracionesPublicas(): array
    {
        return Configuracion::obtenerPublicas();
    }

    /**
     * Obtener configuraciones por categoría
     */
    public static function obtenerPorCategoria(string $categoria): array
    {
        return Configuracion::obtenerPorCategoria($categoria);
    }

    /**
     * Inicializar configuraciones por defecto del sistema
     */
    public static function inicializarConfiguracionesPorDefecto(): void
    {
        $configuracionesPorDefecto = [
            [
                'clave' => 'app.logo_display',
                'valor' => 'logo_text',
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'logo',
                    'publico' => true,
                    'descripcion' => 'Tipo de visualización del logo en el sidebar',
                ]
            ],
            [
                'clave' => 'app.logo_text',
                'valor' => 'Laravel Starter Kit',
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'logo',
                    'publico' => true,
                    'descripcion' => 'Texto que aparece junto al logo',
                ]
            ],
            [
                'clave' => 'app.nombre',
                'valor' => 'Sistema de Votaciones',
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'general',
                    'publico' => true,
                    'descripcion' => 'Nombre del sistema',
                ]
            ],
            [
                'clave' => 'votaciones.tiempo_expiracion_otp',
                'valor' => '10',
                'tipo' => 'integer',
                'opciones' => [
                    'categoria' => 'votaciones',
                    'publico' => false,
                    'descripcion' => 'Tiempo de expiración de códigos OTP en minutos',
                ]
            ],
            [
                'clave' => 'email.driver',
                'valor' => 'smtp',
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'email',
                    'publico' => false,
                    'descripcion' => 'Driver de email a utilizar',
                ]
            ],
            [
                'clave' => 'auth.mensaje_login',
                'valor' => 'Si necesitas ayuda escribe a <b>soporte@colombiahumana.co</b> para soporte sobre la plataforma. Para inquietudes jurídicas a <b>juridicoelectoral@colombiahumana.co</b>',
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'auth',
                    'publico' => true,
                    'descripcion' => 'Mensaje HTML de ayuda que se muestra en la página de login',
                ]
            ],
            [
                'clave' => 'dashboard.user.hero_html',
                'valor' => '<h1 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold mb-2 sm:mb-3 md:mb-4 text-gray-900 dark:text-white">Bienvenido a la plataforma de participación en línea</h1><p class="text-sm sm:text-base md:text-lg text-gray-600 dark:text-gray-300 max-w-2xl">Explora las herramientas disponibles para gestionar candidaturas, participar en convocatorias y asambleas del movimiento.</p>',
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'dashboard',
                    'publico' => true,
                    'descripcion' => 'HTML del hero del dashboard de usuarios',
                ]
            ],
            [
                'clave' => 'dashboard.user.cards',
                'valor' => json_encode([
                    [
                        'id' => '1',
                        'enabled' => true,
                        'order' => 0,
                        'color' => 'blue',
                        'icon' => 'Vote',
                        'title' => 'Participar en Votaciones',
                        'description' => 'Consulta las votaciones activas y participa en los procesos democráticos del movimiento.',
                        'buttonText' => 'Ver Votaciones',
                        'buttonLink' => '/miembro/votaciones'
                    ],
                    [
                        'id' => '2',
                        'enabled' => true,
                        'order' => 1,
                        'color' => 'green',
                        'icon' => 'Users',
                        'title' => 'Participar en Asambleas',
                        'description' => 'Accede a las asambleas del movimiento, consulta información y participa en las sesiones.',
                        'buttonText' => 'Ver Asambleas',
                        'buttonLink' => '/miembro/asambleas'
                    ]
                ]),
                'tipo' => 'json',
                'opciones' => [
                    'categoria' => 'dashboard',
                    'publico' => true,
                    'descripcion' => 'Configuración de cards del dashboard de usuarios',
                ]
            ],
            // Configuraciones de Welcome Page
            [
                'clave' => 'welcome.header.logo_url',
                'valor' => '/logo.png',
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'welcome',
                    'publico' => true,
                    'descripcion' => 'URL del logo en header de la página principal',
                ]
            ],
            [
                'clave' => 'welcome.header.logo_text',
                'valor' => 'Colombia Humana',
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'welcome',
                    'publico' => true,
                    'descripcion' => 'Texto del logo en header de la página principal',
                ]
            ],
            [
                'clave' => 'welcome.hero.title_html',
                'valor' => "<h1 class='font-bold uppercase text-[72px] lg:text-[144px] leading-[0.8] mb-10 text-white'>Ágora</h1>",
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'welcome',
                    'publico' => true,
                    'descripcion' => 'HTML del título principal de la página principal',
                ]
            ],
            [
                'clave' => 'welcome.hero.description_html',
                'valor' => '<span>Sistema de Votaciones Digital para gestionar asambleas, votaciones y procesos electorales de forma segura y transparente.</span>',
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'welcome',
                    'publico' => true,
                    'descripcion' => 'HTML de la descripción de la página principal',
                ]
            ],
            [
                'clave' => 'welcome.background_url',
                'valor' => '/2.png',
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'welcome',
                    'publico' => true,
                    'descripcion' => 'URL del background de la página principal',
                ]
            ],
            [
                'clave' => 'welcome.links',
                'valor' => json_encode([
                    [
                        'id' => '1',
                        'enabled' => true,
                        'order' => 0,
                        'text' => 'Ver Asambleas',
                        'url' => '/miembro/asambleas',
                        'visibility' => 'always',
                        'is_primary' => true
                    ],
                    [
                        'id' => '2',
                        'enabled' => true,
                        'order' => 1,
                        'text' => 'Consulta Participantes',
                        'url' => '/consulta-participantes',
                        'visibility' => 'always',
                        'is_primary' => false
                    ],
                    [
                        'id' => '3',
                        'enabled' => true,
                        'order' => 2,
                        'text' => 'Consultar Inscripción',
                        'url' => '/confirmar-registro',
                        'visibility' => 'always',
                        'is_primary' => false
                    ],
                    [
                        'id' => '4',
                        'enabled' => true,
                        'order' => 3,
                        'text' => 'Lista Precandidatos',
                        'url' => '/postulaciones-aceptadas',
                        'visibility' => 'always',
                        'is_primary' => false
                    ],
                    [
                        'id' => '5',
                        'enabled' => true,
                        'order' => 4,
                        'text' => 'Iniciar Sesión',
                        'url' => '/login',
                        'visibility' => 'logged_out',
                        'is_primary' => false
                    ],
                    [
                        'id' => '6',
                        'enabled' => true,
                        'order' => 4,
                        'text' => 'Dashboard',
                        'url' => '/miembro/dashboard',
                        'visibility' => 'logged_in',
                        'is_primary' => false
                    ]
                ]),
                'tipo' => 'json',
                'opciones' => [
                    'categoria' => 'welcome',
                    'publico' => true,
                    'descripcion' => 'Enlaces laterales de la página principal',
                ]
            ]
        ];

        foreach ($configuracionesPorDefecto as $config) {
            // Solo crear si no existe
            if (!Configuracion::where('clave', $config['clave'])->exists()) {
                self::establecer(
                    $config['clave'],
                    $config['valor'],
                    $config['tipo'],
                    $config['opciones']
                );
            }
        }
    }

    /**
     * Limpiar cache de configuraciones
     */
    public static function limpiarCache(): void
    {
        Configuracion::limpiarCache();
    }

    /**
     * Obtener configuración de control de candidaturas
     */
    public static function obtenerConfiguracionCandidaturas(): array
    {
        return [
            'bloqueo_activo' => self::obtener('candidaturas.bloqueo_activo', false),
            'bloqueo_titulo' => self::obtener('candidaturas.bloqueo_titulo', 'Sistema de Candidaturas Temporalmente Cerrado'),
            'bloqueo_mensaje' => self::obtener('candidaturas.bloqueo_mensaje', 'En este momento no es posible crear o editar candidaturas. Por favor, intente más tarde o contacte al administrador para más información.'),
        ];
    }

    /**
     * Establecer configuración de control de candidaturas
     */
    public static function configurarControlCandidaturas(array $datos): bool
    {
        $configuraciones = [
            [
                'clave' => 'candidaturas.bloqueo_activo',
                'valor' => $datos['bloqueo_activo'] ?? false,
                'tipo' => 'boolean',
                'opciones' => [
                    'categoria' => 'candidaturas',
                    'publico' => false,
                    'descripcion' => 'Bloquear creación y edición de candidaturas en estado borrador',
                ]
            ],
            [
                'clave' => 'candidaturas.bloqueo_titulo',
                'valor' => $datos['bloqueo_titulo'] ?? 'Sistema de Candidaturas Temporalmente Cerrado',
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'candidaturas',
                    'publico' => false,
                    'descripcion' => 'Título del mensaje de bloqueo de candidaturas',
                    'validacion' => ['required', 'string', 'max:255']
                ]
            ],
            [
                'clave' => 'candidaturas.bloqueo_mensaje',
                'valor' => $datos['bloqueo_mensaje'] ?? 'En este momento no es posible crear o editar candidaturas.',
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'candidaturas',
                    'publico' => false,
                    'descripcion' => 'Mensaje detallado del bloqueo de candidaturas',
                    'validacion' => ['required', 'string', 'max:1000']
                ]
            ]
        ];

        return self::configurarVarios($configuraciones);
    }

    /**
     * Verificar si el bloqueo de candidaturas está activo
     */
    public static function bloqueoCandidaturasActivo(): bool
    {
        return (bool) self::obtener('candidaturas.bloqueo_activo', false);
    }

    /**
     * Obtener configuración del mensaje de login
     */
    public static function obtenerConfiguracionLogin(): array
    {
        return [
            'mensaje_html' => self::obtener(
                'auth.mensaje_login',
                'Si necesitas ayuda escribe a <b>soporte@colombiahumana.co</b> para soporte sobre la plataforma. Para inquietudes jurídicas a <b>juridicoelectoral@colombiahumana.co</b>'
            ),
        ];
    }

    /**
     * Establecer configuración del mensaje de login
     */
    public static function configurarMensajeLogin(array $datos): bool
    {
        $configuraciones = [
            [
                'clave' => 'auth.mensaje_login',
                'valor' => $datos['mensaje_html'],
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'auth',
                    'publico' => true,
                    'descripcion' => 'Mensaje HTML de ayuda que se muestra en la página de login',
                    'validacion' => ['required', 'string', 'max:2000']
                ]
            ]
        ];

        return self::configurarVarios($configuraciones);
    }

    /**
     * Obtener configuración del dashboard de usuarios
     */
    public static function obtenerConfiguracionDashboardUser(): array
    {
        $heroDefault = '<h1 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold mb-2 sm:mb-3 md:mb-4 text-gray-900 dark:text-white">Bienvenido a la plataforma de participación en línea</h1><p class="text-sm sm:text-base md:text-lg text-gray-600 dark:text-gray-300 max-w-2xl">Explora las herramientas disponibles para gestionar candidaturas, participar en convocatorias y asambleas del movimiento.</p>';

        $cardsDefault = json_encode([
            [
                'id' => '1',
                'enabled' => true,
                'order' => 0,
                'color' => 'blue',
                'icon' => 'Vote',
                'title' => 'Participar en Votaciones',
                'description' => 'Consulta las votaciones activas y participa en los procesos democráticos del movimiento.',
                'buttonText' => 'Ver Votaciones',
                'buttonLink' => '/miembro/votaciones'
            ],
            [
                'id' => '2',
                'enabled' => true,
                'order' => 1,
                'color' => 'green',
                'icon' => 'Users',
                'title' => 'Participar en Asambleas',
                'description' => 'Accede a las asambleas del movimiento, consulta información y participa en las sesiones.',
                'buttonText' => 'Ver Asambleas',
                'buttonLink' => '/miembro/asambleas'
            ]
        ]);

        return [
            'hero_html' => self::obtener('dashboard.user.hero_html', $heroDefault),
            'cards' => json_decode(self::obtener('dashboard.user.cards', $cardsDefault), true)
        ];
    }

    /**
     * Establecer configuración del dashboard de usuarios
     */
    public static function configurarDashboardUser(array $datos): bool
    {
        $configuraciones = [
            [
                'clave' => 'dashboard.user.hero_html',
                'valor' => $datos['hero_html'],
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'dashboard',
                    'publico' => true,
                    'descripcion' => 'HTML del hero del dashboard de usuarios',
                    'validacion' => ['required', 'string', 'max:5000']
                ]
            ],
            [
                'clave' => 'dashboard.user.cards',
                'valor' => json_encode($datos['cards']),
                'tipo' => 'json',
                'opciones' => [
                    'categoria' => 'dashboard',
                    'publico' => true,
                    'descripcion' => 'Configuración de cards del dashboard de usuarios',
                    'validacion' => ['required', 'array']
                ]
            ]
        ];

        return self::configurarVarios($configuraciones);
    }

    /**
     * Obtener configuración de la página principal (Welcome)
     */
    public static function obtenerConfiguracionWelcome(): array
    {
        $headerDefaults = [
            'logo_url' => '/logo.png',
            'logo_text' => 'Colombia Humana'
        ];

        $heroDefaults = [
            'title_html' => "<h1 class='font-bold uppercase text-[72px] lg:text-[144px] leading-[0.8] mb-10 text-white'>Ágora</h1>",
            'description_html' => '<span>Sistema de Votaciones Digital para gestionar asambleas, votaciones y procesos electorales de forma segura y transparente.</span>'
        ];

        $linksDefault = json_encode([
            [
                'id' => '1',
                'enabled' => true,
                'order' => 0,
                'text' => 'Ver Asambleas',
                'url' => '/miembro/asambleas',
                'visibility' => 'always',
                'is_primary' => true
            ],
            [
                'id' => '2',
                'enabled' => true,
                'order' => 1,
                'text' => 'Consulta Participantes',
                'url' => '/consulta-participantes',
                'visibility' => 'always',
                'is_primary' => false
            ],
            [
                'id' => '3',
                'enabled' => true,
                'order' => 2,
                'text' => 'Consultar Inscripción',
                'url' => '/confirmar-registro',
                'visibility' => 'always',
                'is_primary' => false
            ],
            [
                'id' => '4',
                'enabled' => true,
                'order' => 3,
                'text' => 'Lista Precandidatos',
                'url' => '/postulaciones-aceptadas',
                'visibility' => 'always',
                'is_primary' => false
            ],
            [
                'id' => '5',
                'enabled' => true,
                'order' => 4,
                'text' => 'Iniciar Sesión',
                'url' => '/login',
                'visibility' => 'logged_out',
                'is_primary' => false
            ],
            [
                'id' => '6',
                'enabled' => true,
                'order' => 4,
                'text' => 'Dashboard',
                'url' => '/miembro/dashboard',
                'visibility' => 'logged_in',
                'is_primary' => false
            ]
        ]);

        $links = self::obtener('welcome.links', $linksDefault);

        // Si es string, decodificar JSON
        if (is_string($links)) {
            $links = json_decode($links, true);
        }

        // Convertir valores a tipos correctos
        $links = array_map(function ($link) {
            // Convertir enabled a boolean (manejar cualquier formato)
            $link['enabled'] = is_bool($link['enabled'])
                ? $link['enabled']
                : (bool) filter_var($link['enabled'], FILTER_VALIDATE_BOOLEAN);

            // Convertir is_primary a boolean
            $link['is_primary'] = is_bool($link['is_primary'])
                ? $link['is_primary']
                : (bool) filter_var($link['is_primary'], FILTER_VALIDATE_BOOLEAN);

            $link['order'] = (int) $link['order'];
            return $link;
        }, $links);

        return [
            'header' => [
                'logo_url' => self::obtener('welcome.header.logo_url', $headerDefaults['logo_url']),
                'logo_text' => self::obtener('welcome.header.logo_text', $headerDefaults['logo_text'])
            ],
            'hero' => [
                'title_html' => self::obtener('welcome.hero.title_html', $heroDefaults['title_html']),
                'description_html' => self::obtener('welcome.hero.description_html', $heroDefaults['description_html'])
            ],
            'links' => $links,
            'background_url' => self::obtener('welcome.background_url', '/2.png')
        ];
    }

    /**
     * Manejar upload de logo de Welcome
     */
    public static function manejarUploadLogoWelcome($archivo): ?string
    {
        if (!$archivo) {
            return null;
        }

        // Eliminar logo anterior si existe
        $logoAnterior = self::obtener('welcome.header.logo_url');
        if ($logoAnterior && $logoAnterior !== '/logo.png' && str_starts_with($logoAnterior, '/storage/')) {
            $relativePath = str_replace('/storage/', '', $logoAnterior);
            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
        }

        // Guardar nuevo logo
        $path = $archivo->store('logos/welcome', 'public');

        return '/storage/' . $path;
    }

    /**
     * Manejar upload de background de Welcome
     */
    public static function manejarUploadBackgroundWelcome($archivo): ?string
    {
        if (!$archivo) {
            return null;
        }

        // Eliminar background anterior si existe
        $backgroundAnterior = self::obtener('welcome.background_url');
        if ($backgroundAnterior && $backgroundAnterior !== '/2.png' && str_starts_with($backgroundAnterior, '/storage/')) {
            $relativePath = str_replace('/storage/', '', $backgroundAnterior);
            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
        }

        // Guardar nuevo background
        $path = $archivo->store('backgrounds/welcome', 'public');

        return '/storage/' . $path;
    }

    /**
     * Establecer configuración de la página principal (Welcome)
     */
    public static function configurarWelcome(array $datos): bool
    {
        $configuraciones = [
            [
                'clave' => 'welcome.header.logo_url',
                'valor' => $datos['header']['logo_url'] ?? '/logo.png',
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'welcome',
                    'publico' => true,
                    'descripcion' => 'URL del logo en header de la página principal',
                    'validacion' => ['nullable', 'string', 'max:500']
                ]
            ],
            [
                'clave' => 'welcome.header.logo_text',
                'valor' => $datos['header']['logo_text'],
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'welcome',
                    'publico' => true,
                    'descripcion' => 'Texto del logo en header de la página principal',
                    'validacion' => ['required', 'string', 'max:100']
                ]
            ],
            [
                'clave' => 'welcome.hero.title_html',
                'valor' => $datos['hero']['title_html'],
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'welcome',
                    'publico' => true,
                    'descripcion' => 'HTML del título principal de la página principal',
                    'validacion' => ['required', 'string', 'max:1000']
                ]
            ],
            [
                'clave' => 'welcome.hero.description_html',
                'valor' => $datos['hero']['description_html'],
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'welcome',
                    'publico' => true,
                    'descripcion' => 'HTML de la descripción de la página principal',
                    'validacion' => ['required', 'string', 'max:2000']
                ]
            ],
            [
                'clave' => 'welcome.background_url',
                'valor' => $datos['background_url'] ?? '/2.png',
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'welcome',
                    'publico' => true,
                    'descripcion' => 'URL del background de la página principal',
                    'validacion' => ['nullable', 'string', 'max:500']
                ]
            ],
            [
                'clave' => 'welcome.links',
                'valor' => $datos['links'], // No hacer json_encode aquí, el modelo lo hará
                'tipo' => 'json',
                'opciones' => [
                    'categoria' => 'welcome',
                    'publico' => true,
                    'descripcion' => 'Enlaces laterales de la página principal',
                    'validacion' => ['required', 'array']
                ]
            ]
        ];

        return self::configurarVarios($configuraciones);
    }
}