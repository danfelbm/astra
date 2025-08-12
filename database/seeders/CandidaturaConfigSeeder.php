<?php

namespace Database\Seeders;

use App\Models\CandidaturaConfig;
use App\Models\User;
use Illuminate\Database\Seeder;

class CandidaturaConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar un usuario admin existente o crear uno por defecto
        $admin = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->first();

        // Si no hay admin, usar el primer usuario o crear uno por defecto
        if (!$admin) {
            $admin = User::first();
            
            if (!$admin) {
                $admin = User::create([
                    'name' => 'Admin Sistema',
                    'email' => 'admin@votaciones.test',
                    'email_verified_at' => now(),
                ]);
            }
        }

        // Configuración completa del formulario de candidaturas basado en formulario físico
        $camposCompletos = [
            // ==== SECCIÓN A: INFORMACIÓN PERSONAL DEL ASPIRANTE ====
            [
                'id' => 'nombres_completos',
                'type' => 'text',
                'title' => 'Nombres Completos',
                'description' => 'Ingrese sus nombres completos tal como aparecen en su documento de identidad',
                'required' => true,
                'editable' => false,
            ],
            [
                'id' => 'documento_identidad',
                'type' => 'text',
                'title' => 'Documento de Identidad',
                'description' => 'Número de cédula de ciudadanía',
                'required' => true,
                'editable' => false,
            ],
            [
                'id' => 'fecha_nacimiento',
                'type' => 'datepicker',
                'title' => 'Fecha de Nacimiento',
                'description' => 'Seleccione su fecha de nacimiento',
                'required' => true,
                'editable' => false,
                'datepickerConfig' => [
                    'format' => 'DD/MM/YYYY',
                    'allowPastDates' => true,
                    'allowFutureDates' => false,
                    'maxDate' => now()->subYears(18)->format('Y-m-d'),
                ],
            ],
            [
                'id' => 'genero',
                'type' => 'select',
                'title' => 'Género',
                'description' => 'Seleccione su identidad de género',
                'required' => true,
                'editable' => true,
                'options' => [
                    'FEMENINO',
                    'MASCULINO', 
                    'NO BINARIO',
                    'OTRO',
                ],
            ],
            [
                'id' => 'reconocimiento_etnico',
                'type' => 'select',
                'title' => 'Se reconoce como',
                'description' => 'Seleccione el grupo étnico con el que se identifica',
                'required' => false,
                'editable' => true,
                'options' => [
                    'INDÍGENA',
                    'NEGRO',
                    'AFROCOLOMBIANO',
                    'RAIZAL',
                    'PALENQUERO',
                    'ROM',
                    'CAMPESINO',
                    'LGBTIQ+',
                    'NINGUNO',
                ],
            ],

            // ==== SECCIÓN B: DATOS DE CONTACTO DEL ASPIRANTE ====
            [
                'id' => 'pais_residencia',
                'type' => 'text',
                'title' => 'País de Residencia',
                'description' => 'País donde reside actualmente',
                'required' => true,
                'editable' => true,
            ],
            [
                'id' => 'departamento_residencia',
                'type' => 'text',
                'title' => 'Departamento',
                'description' => 'Departamento de residencia',
                'required' => true,
                'editable' => true,
            ],
            [
                'id' => 'municipio_residencia',
                'type' => 'text',
                'title' => 'Municipio',
                'description' => 'Municipio de residencia',
                'required' => true,
                'editable' => true,
            ],
            [
                'id' => 'direccion_residencia',
                'type' => 'text',
                'title' => 'Dirección',
                'description' => 'Dirección completa de residencia',
                'required' => true,
                'editable' => true,
            ],
            [
                'id' => 'tiempo_residencia_municipio',
                'type' => 'text',
                'title' => 'Tiempo de Residencia en el Municipio',
                'description' => 'Especifique el tiempo que lleva residiendo en el municipio',
                'required' => true,
                'editable' => true,
            ],
            [
                'id' => 'telefono_fijo',
                'type' => 'text',
                'title' => 'Número de Teléfono Fijo',
                'description' => 'Número de teléfono fijo (opcional)',
                'required' => false,
                'editable' => true,
            ],
            [
                'id' => 'telefono_celular',
                'type' => 'text',
                'title' => 'Número de Teléfono Celular',
                'description' => 'Número de teléfono celular',
                'required' => true,
                'editable' => true,
            ],
            [
                'id' => 'correo_electronico',
                'type' => 'email',
                'title' => 'Correo Electrónico',
                'description' => 'Dirección de correo electrónico activa',
                'required' => true,
                'editable' => true,
            ],
            
            // ==== SECCIÓN C: DATOS DE PERSONA DE CONTACTO ====
            [
                'id' => 'contacto_emergencia',
                'type' => 'repeater',
                'title' => 'Datos de una Persona de Contacto',
                'description' => 'Información de una persona de contacto en caso de emergencia',
                'required' => true,
                'editable' => true,
                'repeaterConfig' => [
                    'minItems' => 1,
                    'maxItems' => 1,
                    'itemName' => 'Contacto de Emergencia',
                    'addButtonText' => 'Agregar Contacto',
                    'removeButtonText' => 'Eliminar Contacto',
                    'fields' => [
                        [
                            'id' => 'nombre_completo_contacto',
                            'type' => 'text',
                            'title' => 'Nombre Completo',
                            'required' => true,
                        ],
                        [
                            'id' => 'telefono_contacto',
                            'type' => 'text',
                            'title' => 'Número de Teléfono',
                            'required' => true,
                        ],
                        [
                            'id' => 'parentesco_contacto',
                            'type' => 'text',
                            'title' => 'Parentesco',
                            'required' => true,
                        ],
                    ],
                ],
            ],

            // ==== SECCIÓN D: INFORMACIÓN ACADÉMICA DEL ASPIRANTE ====
            [
                'id' => 'formacion_academica',
                'type' => 'select',
                'title' => 'Formación Académica',
                'description' => 'Nivel de formación académica más alto alcanzado',
                'required' => true,
                'editable' => true,
                'options' => [
                    'PRIMARIA',
                    'SECUNDARIA',
                    'TÉCNICA',
                    'PROFESIONAL',
                    'POSTGRADO',
                    'OTRA',
                ],
            ],
            [
                'id' => 'educacion_basica',
                'type' => 'text',
                'title' => 'Educación Básica',
                'description' => 'Institución donde completó su educación básica',
                'required' => false,
                'editable' => true,
            ],
            [
                'id' => 'ultimo_grado_aprobado',
                'type' => 'text',
                'title' => 'Último Grado Aprobado',
                'description' => 'Especifique el último grado académico aprobado',
                'required' => true,
                'editable' => true,
            ],
            [
                'id' => 'titulo_obtenido',
                'type' => 'text',
                'title' => 'Título',
                'description' => 'Título obtenido en educación superior (si aplica)',
                'required' => false,
                'editable' => true,
            ],
            [
                'id' => 'ano_graduacion',
                'type' => 'number',
                'title' => 'Año',
                'description' => 'Año de graduación',
                'required' => false,
                'editable' => true,
                'numberConfig' => [
                    'min' => 1950,
                    'max' => date('Y'),
                    'step' => 1,
                ],
            ],
            [
                'id' => 'educacion_superior',
                'type' => 'repeater',
                'title' => 'Educación Superior',
                'description' => 'Detalle sus estudios de educación superior',
                'required' => false,
                'editable' => true,
                'repeaterConfig' => [
                    'minItems' => 0,
                    'maxItems' => 5,
                    'itemName' => 'Estudio',
                    'addButtonText' => 'Agregar Estudio',
                    'removeButtonText' => 'Eliminar',
                    'fields' => [
                        [
                            'id' => 'programa_estudio',
                            'type' => 'text',
                            'title' => 'Programa',
                            'required' => true,
                        ],
                        [
                            'id' => 'titulo_estudio',
                            'type' => 'text',
                            'title' => 'Título',
                            'required' => true,
                        ],
                        [
                            'id' => 'ano_estudio',
                            'type' => 'number',
                            'title' => 'Año',
                            'required' => true,
                        ],
                    ],
                ],
            ],
            
            // ==== DOCUMENTOS PERSONALES ====
            [
                'id' => 'hoja_vida',
                'type' => 'file',
                'title' => 'Hoja de Vida',
                'description' => 'Adjunte su hoja de vida actualizada en formato PDF (máximo 5MB)',
                'required' => true,
                'editable' => true,
                'fileConfig' => [
                    'multiple' => false,
                    'maxFiles' => 1,
                    'maxFileSize' => 5,
                    'accept' => '.pdf',
                ],
            ],
            
            // ==== SECCIÓN E: EXPERIENCIA LABORAL DEL ASPIRANTE ====
            [
                'id' => 'experiencia_laboral',
                'type' => 'repeater',
                'title' => 'Experiencia Laboral',
                'description' => 'Detalle su experiencia laboral en orden cronológico (más reciente primero)',
                'required' => false,
                'editable' => true,
                'repeaterConfig' => [
                    'minItems' => 0,
                    'maxItems' => 10,
                    'itemName' => 'Experiencia Laboral',
                    'addButtonText' => 'Agregar Experiencia',
                    'removeButtonText' => 'Eliminar',
                    'fields' => [
                        [
                            'id' => 'empresa_entidad',
                            'type' => 'text',
                            'title' => 'Empresa o Entidad',
                            'required' => true,
                        ],
                        [
                            'id' => 'cargo_desempenado',
                            'type' => 'text',
                            'title' => 'Cargo',
                            'required' => true,
                        ],
                        [
                            'id' => 'fecha_ingreso',
                            'type' => 'date',
                            'title' => 'Fecha Ingreso (DD/MM/AAAA)',
                            'required' => true,
                        ],
                        [
                            'id' => 'fecha_retiro',
                            'type' => 'date',
                            'title' => 'Fecha Retiro (DD/MM/AAAA)',
                            'required' => false,
                        ],
                    ],
                ],
            ],
            
            // ==== SECCIÓN F: TRAYECTORIA POLÍTICA DEL ASPIRANTE ====
            // Subsección 1: Cargos de elección popular (últimos 5 años)
            [
                'id' => 'cargos_eleccion_popular',
                'type' => 'repeater',
                'title' => 'Cargos de Elección Popular que ha ocupado o aspirado en los últimos cinco años',
                'description' => 'Del más reciente al más antiguo. Solo incluir cargos de los últimos 5 años.',
                'required' => false,
                'editable' => true,
                'repeaterConfig' => [
                    'minItems' => 0,
                    'maxItems' => 10,
                    'itemName' => 'Cargo de Elección Popular',
                    'addButtonText' => 'Agregar Cargo',
                    'removeButtonText' => 'Eliminar',
                    'fields' => [
                        [
                            'id' => 'cargo_aspirado',
                            'type' => 'text',
                            'title' => 'Cargo al que Aspiró',
                            'required' => true,
                        ],
                        [
                            'id' => 'partido_avalador',
                            'type' => 'text',
                            'title' => 'Partido por el cual fue avalado',
                            'required' => true,
                        ],
                        [
                            'id' => 'fecha_eleccion',
                            'type' => 'date',
                            'title' => 'Fecha de Elección',
                            'required' => true,
                        ],
                        [
                            'id' => 'resultado_eleccion',
                            'type' => 'select',
                            'title' => 'Elegido',
                            'options' => ['SI', 'NO'],
                            'required' => true,
                        ],
                    ],
                ],
            ],
            
            // Subsección 2: Cargos públicos por nombramiento
            [
                'id' => 'cargos_publicos_nombramiento',
                'type' => 'repeater',
                'title' => 'Cargos públicos que ha ejercido en los últimos cinco años por nombramiento, provisionalidad, contrato prestación de servicios u otro',
                'description' => 'Incluir todos los cargos públicos de los últimos 5 años',
                'required' => false,
                'editable' => true,
                'repeaterConfig' => [
                    'minItems' => 0,
                    'maxItems' => 15,
                    'itemName' => 'Cargo Público',
                    'addButtonText' => 'Agregar Cargo',
                    'removeButtonText' => 'Eliminar',
                    'fields' => [
                        [
                            'id' => 'entidad_publica',
                            'type' => 'text',
                            'title' => 'Entidad',
                            'required' => true,
                        ],
                        [
                            'id' => 'cargo_vinculacion',
                            'type' => 'text',
                            'title' => 'Cargo/Vinculación Contractual',
                            'required' => true,
                        ],
                        [
                            'id' => 'ciudad_municipio',
                            'type' => 'text',
                            'title' => 'Ciudad o Municipio',
                            'required' => true,
                        ],
                        [
                            'id' => 'fecha_inicio_cargo',
                            'type' => 'date',
                            'title' => 'Fecha Inicio (DD/MM/AAAA)',
                            'required' => true,
                        ],
                        [
                            'id' => 'fecha_fin_cargo',
                            'type' => 'date',
                            'title' => 'Fecha Fin (DD/MM/AAAA)',
                            'required' => false,
                        ],
                    ],
                ],
            ],
            
            // Subsección 3: Organismos de control
            [
                'id' => 'organismos_control',
                'type' => 'repeater',
                'title' => 'Organismos de Control o Dirección',
                'description' => 'Cargos en organismos de control o dirección que haya ocupado',
                'required' => false,
                'editable' => true,
                'repeaterConfig' => [
                    'minItems' => 0,
                    'maxItems' => 10,
                    'itemName' => 'Organismo de Control',
                    'addButtonText' => 'Agregar Organismo',
                    'removeButtonText' => 'Eliminar',
                    'fields' => [
                        [
                            'id' => 'organismo_direccion',
                            'type' => 'text',
                            'title' => 'Organismo de Control o Dirección',
                            'required' => true,
                        ],
                        [
                            'id' => 'cargo_organismo',
                            'type' => 'text',
                            'title' => 'Cargo',
                            'required' => true,
                        ],
                        [
                            'id' => 'fecha_inicio_organismo',
                            'type' => 'date',
                            'title' => 'Fecha Inicio (DD/MM/AAAA)',
                            'required' => true,
                        ],
                        [
                            'id' => 'fecha_fin_organismo',
                            'type' => 'date',
                            'title' => 'Fecha Fin (DD/MM/AAAA)',
                            'required' => false,
                        ],
                    ],
                ],
            ],
            
            // ==== SECCIÓN G: CONFLICTOS DE INTERESES ====
            // Subsección 1: Actividades económicas personales
            [
                'id' => 'actividades_economicas_personales',
                'type' => 'repeater',
                'title' => 'Actividades económicas y participación en sociedades',
                'description' => 'Empresas, sociedades, negocios y organizaciones sin ánimo de lucro de las cuales ha sido dueño, socio, representante legal, directivo o miembro por los últimos 5 años',
                'required' => false,
                'editable' => true,
                'repeaterConfig' => [
                    'minItems' => 0,
                    'maxItems' => 10,
                    'itemName' => 'Actividad Económica',
                    'addButtonText' => 'Agregar Actividad',
                    'removeButtonText' => 'Eliminar',
                    'fields' => [
                        [
                            'id' => 'entidad_empresa',
                            'type' => 'text',
                            'title' => 'Entidad o Empresa',
                            'required' => true,
                        ],
                        [
                            'id' => 'tipo_participacion',
                            'type' => 'text',
                            'title' => 'Tipo de Participación',
                            'required' => true,
                        ],
                        [
                            'id' => 'fecha_inicio_participacion',
                            'type' => 'date',
                            'title' => 'Fecha Inicio (DD/MM/AAAA)',
                            'required' => true,
                        ],
                        [
                            'id' => 'fecha_fin_participacion',
                            'type' => 'date',
                            'title' => 'Fecha Fin (DD/MM/AAAA)',
                            'required' => false,
                        ],
                    ],
                ],
            ],
            
            // Subsección 2: Actividades económicas familiares
            [
                'id' => 'actividades_economicas_familiares',
                'type' => 'repeater',
                'title' => 'Actividades económicas de familiares',
                'description' => 'Empresas, sociedades, negocios y organizaciones sin ánimo de lucro en las cuales participan en calidad de socios, gerentes, representantes legales, su cónyuge o compañero(a) permanente y las personas con las que tenga vínculo hasta por cuarto grado de consanguinidad, segundo de afinidad, o primero civil',
                'required' => false,
                'editable' => true,
                'repeaterConfig' => [
                    'minItems' => 0,
                    'maxItems' => 10,
                    'itemName' => 'Actividad Familiar',
                    'addButtonText' => 'Agregar Actividad Familiar',
                    'removeButtonText' => 'Eliminar',
                    'fields' => [
                        [
                            'id' => 'nombre_familiar',
                            'type' => 'text',
                            'title' => 'Nombre',
                            'required' => true,
                        ],
                        [
                            'id' => 'parentesco_familiar',
                            'type' => 'text',
                            'title' => 'Parentesco',
                            'required' => true,
                        ],
                        [
                            'id' => 'entidad_familiar',
                            'type' => 'text',
                            'title' => 'Entidad',
                            'required' => true,
                        ],
                        [
                            'id' => 'fecha_inicio_familiar',
                            'type' => 'date',
                            'title' => 'Fecha Inicio (DD/MM/AAAA)',
                            'required' => true,
                        ],
                        [
                            'id' => 'fecha_fin_familiar',
                            'type' => 'date',
                            'title' => 'Fecha Fin (DD/MM/AAAA)',
                            'required' => false,
                        ],
                    ],
                ],
            ],
            
            // Subsección 3: Contratación estatal
            [
                'id' => 'contratacion_estatal',
                'type' => 'repeater',
                'title' => 'Contratación estatal como persona natural o jurídica',
                'description' => 'Contratos suscritos como persona natural o jurídica con entidades estatales',
                'required' => false,
                'editable' => true,
                'repeaterConfig' => [
                    'minItems' => 0,
                    'maxItems' => 15,
                    'itemName' => 'Contrato Estatal',
                    'addButtonText' => 'Agregar Contrato',
                    'removeButtonText' => 'Eliminar',
                    'fields' => [
                        [
                            'id' => 'entidad_contratante',
                            'type' => 'text',
                            'title' => 'Entidad',
                            'required' => true,
                        ],
                        [
                            'id' => 'tipo_contrato',
                            'type' => 'text',
                            'title' => 'Tipo de Contrato',
                            'required' => true,
                        ],
                        [
                            'id' => 'fecha_inicio_contrato',
                            'type' => 'date',
                            'title' => 'Fecha Inicio (DD/MM/AAAA)',
                            'required' => true,
                        ],
                        [
                            'id' => 'fecha_fin_contrato',
                            'type' => 'date',
                            'title' => 'Fecha Fin (DD/MM/AAAA)',
                            'required' => false,
                        ],
                    ],
                ],
            ],

            // ==== DOCUMENTOS PERSONALES ====
            [
                'id' => 'certificados_estudios',
                'type' => 'file',
                'title' => 'Certificados de Estudios',
                'description' => 'Adjunte certificados de estudios (diplomas, actas de grado, certificaciones) en formato PDF. Puede cargar múltiples archivos.',
                'required' => true,
                'editable' => true,
                'fileConfig' => [
                    'multiple' => true,
                    'maxFiles' => 5,
                    'maxFileSize' => 5,
                    'accept' => '.pdf',
                ],
            ],
            
            // Experiencia Laboral
            [
                'id' => 'certificados_laborales',
                'type' => 'file',
                'title' => 'Certificados Laborales',
                'description' => 'Adjunte certificaciones laborales que acrediten su experiencia profesional. Puede cargar múltiples archivos en formato PDF.',
                'required' => false,
                'editable' => true,
                'fileConfig' => [
                    'multiple' => true,
                    'maxFiles' => 10,
                    'maxFileSize' => 5,
                    'accept' => '.pdf',
                ],
            ],
            
            // ==== SECCIÓN H: INHABILIDADES DEL ASPIRANTE ====
            // Campo condicional 1: Investigaciones fiscales, disciplinarias o penales
            [
                'id' => 'investigacion_fiscal_disciplinaria',
                'type' => 'radio',
                'title' => '¿Se encuentra incurso en alguna investigación de carácter fiscal, disciplinaria o penal que se pueda configurar como una inhabilidad para ejercer cargos de elección popular?',
                'description' => 'Indique bajo la gravedad de juramento',
                'required' => true,
                'editable' => true,
                'options' => ['SI', 'NO'],
            ],
            [
                'id' => 'explicacion_investigacion',
                'type' => 'textarea',
                'title' => 'Si su respuesta es afirmativa, por favor explique el carácter de la investigación',
                'description' => 'Detalle la naturaleza, estado y entidad que adelanta la investigación',
                'required' => false,
                'editable' => true,
                'conditionalConfig' => [
                    'enabled' => true,
                    'showWhen' => 'all',
                    'conditions' => [
                        [
                            'fieldId' => 'investigacion_fiscal_disciplinaria',
                            'operator' => 'equals',
                            'value' => 'SI',
                        ],
                    ],
                ],
            ],
            
            // Campo condicional 2: Empleado público
            [
                'id' => 'empleado_publico_doce_meses',
                'type' => 'radio',
                'title' => '¿Ha ejercido como empleado público, jurisdicción o autoridad política, civil, administrativa o militar, dentro de los doce meses anteriores a la fecha de la elección solicitada?',
                'description' => 'Marque la opción correspondiente',
                'required' => true,
                'editable' => true,
                'options' => ['SI', 'NO'],
            ],
            [
                'id' => 'explicacion_empleo_publico',
                'type' => 'textarea',
                'title' => 'Si su respuesta es afirmativa, por favor explique el empleo ocupado y reseñe la entidad',
                'description' => 'Especifique el cargo, entidad y periodo de ejercicio',
                'required' => false,
                'editable' => true,
                'conditionalConfig' => [
                    'enabled' => true,
                    'showWhen' => 'all',
                    'conditions' => [
                        [
                            'fieldId' => 'empleado_publico_doce_meses',
                            'operator' => 'equals',
                            'value' => 'SI',
                        ],
                    ],
                ],
            ],
            
            // Campo condicional 3: Gestión de negocios con entidades públicas
            [
                'id' => 'gestion_negocios_entidades_publicas',
                'type' => 'radio',
                'title' => '¿Ha intervenido en gestión de negocios ante entidades públicas, en la celebración de contratos con ellas a interés propio o en el de terceros? (dentro de los seis meses anteriores a la fecha de la elección solicitada)',
                'description' => 'Marque la opción correspondiente',
                'required' => true,
                'editable' => true,
                'options' => ['SI', 'NO'],
            ],
            [
                'id' => 'explicacion_gestion_negocios',
                'type' => 'textarea',
                'title' => 'Si su respuesta es afirmativa, por favor explique el tipo de negocio y reseñe la entidad',
                'description' => 'Detalle el tipo de gestión, contratos o negocios realizados',
                'required' => false,
                'editable' => true,
                'conditionalConfig' => [
                    'enabled' => true,
                    'showWhen' => 'all',
                    'conditions' => [
                        [
                            'fieldId' => 'gestion_negocios_entidades_publicas',
                            'operator' => 'equals',
                            'value' => 'SI',
                        ],
                    ],
                ],
            ],
            
            // Campo condicional 4: Vínculos matrimoniales o familiares
            [
                'id' => 'vinculos_familiares_funcionarios',
                'type' => 'radio',
                'title' => '¿Tiene usted vínculos por matrimonio, unión permanente, parentesco en tercer grado de consanguinidad, primero de afinidad, o único civil, con funcionarios que ejerzan autoridad civil o política?',
                'description' => 'Marque la opción correspondiente',
                'required' => true,
                'editable' => true,
                'options' => ['SI', 'NO'],
            ],
            [
                'id' => 'explicacion_vinculos_familiares',
                'type' => 'textarea',
                'title' => 'Si su respuesta es afirmativa, por favor explique el vínculo y la función que este ejerce',
                'description' => 'Detalle el parentesco y el cargo que ocupa el familiar',
                'required' => false,
                'editable' => true,
                'conditionalConfig' => [
                    'enabled' => true,
                    'showWhen' => 'all',
                    'conditions' => [
                        [
                            'fieldId' => 'vinculos_familiares_funcionarios',
                            'operator' => 'equals',
                            'value' => 'SI',
                        ],
                    ],
                ],
            ],
            
            // Campo condicional 5: Condenado en Colombia o exterior
            [
                'id' => 'condenado_colombia_exterior',
                'type' => 'radio',
                'title' => '¿Ha sido condenado en Colombia o en el exterior por cualquier delito?',
                'description' => 'Marque la opción correspondiente',
                'required' => true,
                'editable' => true,
                'options' => ['SI', 'NO'],
            ],
            [
                'id' => 'explicacion_condena',
                'type' => 'textarea',
                'title' => 'Si su respuesta es afirmativa, por favor explique el delito y la condena',
                'description' => 'Detalle el delito, la condena impuesta y el estado actual',
                'required' => false,
                'editable' => true,
                'conditionalConfig' => [
                    'enabled' => true,
                    'showWhen' => 'all',
                    'conditions' => [
                        [
                            'fieldId' => 'condenado_colombia_exterior',
                            'operator' => 'equals',
                            'value' => 'SI',
                        ],
                    ],
                ],
            ],
            
            // ==== SECCIÓN I: MOTIVACIÓN ====
            [
                'id' => 'motivacion_candidatura_pacto',
                'type' => 'textarea',
                'title' => 'Indique la motivación para postular su candidatura en el pacto histórico',
                'description' => 'Explique en detalle sus motivaciones para participar en este proceso electoral (mínimo 200 palabras)',
                'required' => true,
                'editable' => true,
            ],

            // ==== CERTIFICADOS DE ANTECEDENTES ====
            [
                'id' => 'certificado_contraloria',
                'type' => 'file',
                'title' => 'Certificado de Antecedentes Fiscales - Contraloría',
                'description' => 'Certificado de la Contraloría General de la República (no mayor a 30 días)',
                'required' => true,
                'editable' => true,
                'fileConfig' => [
                    'multiple' => false,
                    'maxFiles' => 1,
                    'maxFileSize' => 2,
                    'accept' => '.pdf',
                ],
            ],
            [
                'id' => 'certificado_procuraduria',
                'type' => 'file',
                'title' => 'Certificado de Antecedentes Disciplinarios - Procuraduría',
                'description' => 'Certificado de la Procuraduría General de la Nación (no mayor a 30 días)',
                'required' => true,
                'editable' => true,
                'fileConfig' => [
                    'multiple' => false,
                    'maxFiles' => 1,
                    'maxFileSize' => 2,
                    'accept' => '.pdf',
                ],
            ],
            [
                'id' => 'certificado_policia',
                'type' => 'file',
                'title' => 'Certificado de Antecedentes Judiciales - Policía Nacional',
                'description' => 'Certificado de Antecedentes Judiciales de la Policía Nacional (no mayor a 30 días)',
                'required' => true,
                'editable' => true,
                'fileConfig' => [
                    'multiple' => false,
                    'maxFiles' => 1,
                    'maxFileSize' => 2,
                    'accept' => '.pdf',
                ],
            ],
            [
                'id' => 'certificado_redam',
                'type' => 'file',
                'title' => 'Certificado REDAM',
                'description' => 'Certificado del Registro Nacional de Medidas Correctivas (no mayor a 30 días)',
                'required' => true,
                'editable' => true,
                'fileConfig' => [
                    'multiple' => false,
                    'maxFiles' => 1,
                    'maxFileSize' => 2,
                    'accept' => '.pdf',
                ],
            ],
            
            // Motivación y Experiencia Política
            [
                'id' => 'motivacion_postulacion',
                'type' => 'textarea',
                'title' => '¿Por qué desea postularse para este cargo de elección popular?',
                'description' => 'Explique sus motivaciones para participar en este proceso electoral (mínimo 200 palabras)',
                'required' => true,
                'editable' => true,
            ],
            [
                'id' => 'propuestas_principales',
                'type' => 'textarea',
                'title' => 'Propuestas Principales',
                'description' => 'Describa las tres propuestas principales que impulsaría si resulta elegido',
                'required' => true,
                'editable' => true,
            ],
            [
                'id' => 'experiencia_militancia',
                'type' => 'textarea',
                'title' => 'Experiencia de Militancia Política',
                'description' => 'Describa su experiencia en partidos políticos, movimientos sociales o activismo comunitario',
                'required' => false,
                'editable' => true,
            ],
            [
                'id' => 'cargos_publicos_anteriores',
                'type' => 'textarea',
                'title' => 'Cargos Públicos o de Elección Popular Anteriores',
                'description' => 'Si ha ocupado cargos públicos o de elección popular anteriormente, por favor descríbalos indicando período y logros principales',
                'required' => false,
                'editable' => true,
            ],
            
            // Información Adicional
            [
                'id' => 'experiencia_comunitaria',
                'type' => 'textarea',
                'title' => 'Experiencia en Trabajo Comunitario',
                'description' => 'Describa su experiencia en trabajo con comunidades, organizaciones sociales o proyectos de impacto social',
                'required' => false,
                'editable' => true,
            ],
            [
                'id' => 'formacion_politica',
                'type' => 'select',
                'title' => '¿Ha recibido formación política o en administración pública?',
                'description' => 'Indique si ha participado en cursos, diplomados o formación relacionada',
                'required' => true,
                'editable' => true,
                'options' => [
                    'No he recibido formación política formal',
                    'He participado en cursos o talleres básicos',
                    'Tengo diplomado(s) en temas políticos o de administración pública',
                    'Tengo pregrado relacionado con ciencias políticas o administración pública',
                    'Tengo posgrado(s) en áreas relacionadas',
                ],
            ],
            [
                'id' => 'disponibilidad_tiempo',
                'type' => 'radio',
                'title' => 'Disponibilidad de Tiempo',
                'description' => '¿Cuenta con disponibilidad de tiempo completo para ejercer el cargo en caso de ser elegido?',
                'required' => true,
                'editable' => true,
                'options' => [
                    'Sí, tengo disponibilidad de tiempo completo',
                    'No, pero puedo reorganizar mis actividades',
                    'Parcialmente, requiero mantener algunas actividades',
                ],
            ],
            [
                'id' => 'conflictos_interes',
                'type' => 'textarea',
                'title' => 'Declaración de Conflictos de Interés',
                'description' => 'Declare si tiene algún potencial conflicto de interés que deba ser conocido (vínculos empresariales, familiares en cargos públicos, etc.)',
                'required' => false,
                'editable' => true,
            ],
            
            // Consentimientos
            [
                'id' => 'autorizacion_verificacion',
                'type' => 'checkbox',
                'title' => 'Autorización para Verificación de Información',
                'description' => 'Autorizo la verificación de toda la información suministrada',
                'required' => true,
                'editable' => false,
                'options' => [
                    'Autorizo la verificación de la información y documentos presentados',
                ],
            ],
            [
                'id' => 'declaracion_veracidad',
                'type' => 'checkbox',
                'title' => 'Declaración de Veracidad',
                'description' => 'Declaro que toda la información suministrada es verídica',
                'required' => true,
                'editable' => false,
                'options' => [
                    'Declaro bajo la gravedad de juramento que toda la información suministrada es verídica y puede ser verificada',
                ],
            ],
            
            // ==== SECCIÓN J: ACTA DE COMPROMISO ====
            [
                'id' => 'acta_compromiso',
                'type' => 'disclaimer',
                'title' => 'Acta de Compromiso',
                'description' => 'Lea y acepte el acta de compromiso para continuar',
                'required' => true,
                'editable' => false,
                'disclaimerConfig' => [
                    'modalTitle' => 'Acta de Compromiso - Pacto Histórico',
                    'disclaimerText' => "Yo, _________________________, identificado(a) con C.C Nº _____________ de _____________, obrando como precandidato(a) aspirante por el PACTO HISTÓRICO, en pleno conocimiento de la normatividad y obligaciones electorales, tanto aquellas establecidas en la Ley 1475 de 2011 y la ley 136 de 1994 como las dispuestas en los estatutos del partido, como precandidato(a) a corporación pública, entre otras; me comprometo a:\n\n1. El cumplimiento de mis obligaciones constitucionales, legales y estatutarias como candidato(a).\n\n2. Leer y aplicar el instructivo de rendición de cuentas y los demás formatos de carácter obligatorio.\n\n3. Conocer, respetar y adherir plenamente la reglamentación de la consulta, especialmente en lo referente con el proceso de selección de candidaturas y ordenamiento de las listas.\n\n4. Aportar información veraz y real relacionada con mis datos de contacto a saber: domicilio y residencia, teléfono fijo y celular, correo electrónico, referencia personales, donde luego se me pueda ubicar en caso de alguna inconsistencia en la rendición de cuentas Si en el transcurso de la campaña o en el proceso de rendición de cuentas tengo modificación en mis datos de contacto lo informaré por escrito al PACTO HISTÓRICO.\n\n5. Atender los requerimientos que me haga el CONSEJO NACIONAL ELECTORAL y aportar la documentación que esta corporación me solicite como soporte de proceso alguno.\n\n6. Entregar oportunamente el informe individual de ingresos y gastos de campaña máximo diez (10) días calendario, luego de realizarse la elección Y Cumplir a cabalidad con los requerimientos realizados desde la Auditoría Interna.\n\n7. Abstenerme de participar en consultas de otros partidos políticos o incurrir en actos constitutivos de Doble Militancia.",
                    'acceptButtonText' => 'Acepto el Acta de Compromiso',
                    'declineButtonText' => 'No acepto',
                ],
            ],
            
            // ==== SECCIÓN K: DECLARACIÓN JURAMENTADA ====
            [
                'id' => 'nombre_declaracion_juramentada',
                'type' => 'text',
                'title' => 'Nombre Completo para Declaración Juramentada',
                'description' => 'En calidad de precandidato a _______________________________',
                'required' => true,
                'editable' => true,
            ],
            [
                'id' => 'cedula_declaracion_juramentada',
                'type' => 'text',
                'title' => 'Número de Cédula',
                'description' => 'Declaro bajo gravedad de juramento que; yo, __________________________ identificado con cédula de ciudadanía Nº ______________________',
                'required' => true,
                'editable' => true,
            ],
            [
                'id' => 'declaracion_juramentada',
                'type' => 'disclaimer',
                'title' => 'Declaración Juramentada',
                'description' => 'Lea y acepte la declaración juramentada',
                'required' => true,
                'editable' => false,
                'disclaimerConfig' => [
                    'modalTitle' => 'Declaración Juramentada',
                    'disclaimerText' => "1. Acepto plenamente los resultados de la consulta, especialmente en lo referente con el proceso de selección de candidaturas y ordenamiento de las listas acorde a la reglamentación.\n\n2. Me comprometo a cumplir cabalmente lo dispuesto en la Constitución Política, la ley, las disposiciones de la Organización Electoral y las normas establecidas por el Pacto Histórico para esta consulta\n\n3. Acepto la candidatura para la cual me inscribo y manifiesto que conozco la normatividad electoral aplicable.\n\n4. Me comprometo a respaldar pública, política y electoralmente a las candidaturas elegidas de conformidad con el reglamento de la consulta del Pacto Histórico.\n\n5. No me encuentro incurso(a) en ninguna causal de inhabilidad o incompatibilidad, ni cuento con antecedente penal alguno, ni estoy incurriendo en doble militancia para inscribir mi precandidatura.\n\n6. Cumplo y me comprometo a seguir cumpliendo con todos los requisitos establecidos en la reglamentación del Pacto Histórico para esta consulta\n\n7. Autorizo expresamente a las directivas y comités de ética y garantías electoralesdel Pacto Histórico para consultar mis antecedentes judiciales, disciplinarios, fiscales y de cualquier otra naturaleza ante las entidades competentes.\n\n8. Declaro haber leído y entendido las disposiciones contenidas en la Constitución Política, la Ley, los documentos radicados ante el CNE en el marco del reconocimiento de personería jurídica del Pacto Histórico tales como Programa del Pacto Histórico, los Estatutos, el Código de Ética y Régimen Disciplinario, el Reglamento de Bancada, las normas internas, las reglamentaciones vigentes y la presente Declaración Juramentada.",
                    'acceptButtonText' => 'Acepto la Declaración Juramentada',
                    'declineButtonText' => 'No acepto',
                ],
            ],
        ];

        // Limpiar tabla de configuración existente
        CandidaturaConfig::truncate();
        $this->command->info('Tabla candidatura_config limpiada.');
        
        // Crear la configuración completa
        CandidaturaConfig::create([
            'campos' => $camposCompletos,
            'activo' => true,
            'created_by' => $admin->id,
            'version' => 1,
        ]);
        
        $this->command->info('Configuración completa de candidaturas creada exitosamente con ' . count($camposCompletos) . ' campos.');
    }
}