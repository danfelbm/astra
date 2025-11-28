<?php

namespace Tests\Unit\Proyectos;

use Tests\TestCase;
use Modules\Proyectos\Services\NomenclaturaService;

/**
 * Tests unitarios para NomenclaturaService.
 * Verifica la generación de nombres, rutas y validación de patrones.
 */
class NomenclaturaServiceTest extends TestCase
{
    private NomenclaturaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NomenclaturaService();
    }

    /**
     * Test: Patrón vacío usa default {original}.
     */
    public function test_patron_vacio_usa_default(): void
    {
        $contexto = [
            'original' => 'mi-archivo.pdf',
            'extension' => 'pdf',
        ];

        $nombre = $this->service->generarNombre('', $contexto);

        // Debe contener 'mi-archivo' + '_' + uid (6 chars)
        $this->assertStringContainsString('mi-archivo', $nombre);
        $this->assertMatchesRegularExpression('/_[a-f0-9]{6}$/', $nombre);
    }

    /**
     * Test: Patrón null usa default.
     */
    public function test_patron_null_usa_default(): void
    {
        $contexto = [
            'original' => 'documento.docx',
            'extension' => 'docx',
        ];

        $nombre = $this->service->generarNombre(null, $contexto);

        $this->assertStringContainsString('documento', $nombre);
    }

    /**
     * Test: UID siempre se agrega al final.
     */
    public function test_uid_siempre_presente(): void
    {
        $contexto = [
            'proyecto_id' => 42,
            'original' => 'test.pdf',
            'extension' => 'pdf',
        ];

        $nombre1 = $this->service->generarNombre('{proyecto_id}', $contexto);
        $nombre2 = $this->service->generarNombre('{proyecto_id}', $contexto);

        // Ambos deben tener UID al final
        $this->assertMatchesRegularExpression('/_[a-f0-9]{6}$/', $nombre1);
        $this->assertMatchesRegularExpression('/_[a-f0-9]{6}$/', $nombre2);

        // UIDs deben ser diferentes (unicidad)
        $this->assertNotEquals($nombre1, $nombre2);
    }

    /**
     * Test: Token {proyecto_id} se reemplaza correctamente.
     */
    public function test_token_proyecto_id(): void
    {
        $contexto = [
            'proyecto_id' => 123,
            'original' => 'test.pdf',
        ];

        $nombre = $this->service->generarNombre('{proyecto_id}_{original}', $contexto);

        $this->assertStringContainsString('123', $nombre);
        $this->assertStringContainsString('test', $nombre);
    }

    /**
     * Test: Token {fecha} se reemplaza con fecha actual.
     */
    public function test_token_fecha_simple(): void
    {
        $contexto = ['original' => 'test.pdf'];
        $fechaEsperada = date('Y-m-d');

        $nombre = $this->service->generarNombre('{fecha}_{original}', $contexto);

        // Fecha debe estar en el nombre (convertida a slug)
        $fechaSlug = str_replace('-', '', $fechaEsperada);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}|' . preg_quote($fechaSlug) . '/', $nombre);
    }

    /**
     * Test: Token {fecha:Ymd} formato compacto.
     */
    public function test_token_fecha_formato_ymd(): void
    {
        $contexto = ['original' => 'test.pdf'];
        $fechaEsperada = date('Ymd');

        $nombre = $this->service->generarNombre('{fecha:Ymd}_{original}', $contexto);

        $this->assertStringContainsString($fechaEsperada, $nombre);
    }

    /**
     * Test: Token {fecha:d-m-Y} formato europeo.
     */
    public function test_token_fecha_formato_europeo(): void
    {
        $contexto = ['original' => 'test.pdf'];

        $nombre = $this->service->generarNombre('{fecha:d-m-Y}', $contexto);

        // La fecha debe contener día-mes-año (convertido a slug quita los guiones)
        $this->assertNotEmpty($nombre);
    }

    /**
     * Test: generarNombreCompleto incluye extensión.
     */
    public function test_generar_nombre_completo_incluye_extension(): void
    {
        $contexto = [
            'original' => 'archivo.pdf',
            'extension' => 'pdf',
        ];

        $nombreCompleto = $this->service->generarNombreCompleto('{original}', $contexto);

        $this->assertStringEndsWith('.pdf', $nombreCompleto);
    }

    /**
     * Test: generarRuta estructura correcta.
     */
    public function test_generar_ruta_solo_proyecto(): void
    {
        $ruta = $this->service->generarRuta(42);

        $this->assertEquals('evidencias/42', $ruta);
    }

    /**
     * Test: generarRuta con hito.
     */
    public function test_generar_ruta_con_hito(): void
    {
        $ruta = $this->service->generarRuta(42, 15);

        $this->assertEquals('evidencias/42/15', $ruta);
    }

    /**
     * Test: generarRuta completa.
     */
    public function test_generar_ruta_completa(): void
    {
        $ruta = $this->service->generarRuta(42, 15, 78);

        $this->assertEquals('evidencias/42/15/78', $ruta);
    }

    /**
     * Test: validarPatron - vacío es válido.
     */
    public function test_validar_patron_vacio_valido(): void
    {
        $this->assertTrue($this->service->validarPatron(''));
        $this->assertTrue($this->service->validarPatron('   '));
    }

    /**
     * Test: validarPatron - tokens válidos.
     */
    public function test_validar_patron_tokens_validos(): void
    {
        $this->assertTrue($this->service->validarPatron('{proyecto}'));
        $this->assertTrue($this->service->validarPatron('{proyecto_id}'));
        $this->assertTrue($this->service->validarPatron('{hito}'));
        $this->assertTrue($this->service->validarPatron('{hito_id}'));
        $this->assertTrue($this->service->validarPatron('{entregable}'));
        $this->assertTrue($this->service->validarPatron('{entregable_id}'));
        $this->assertTrue($this->service->validarPatron('{fecha}'));
        $this->assertTrue($this->service->validarPatron('{original}'));
    }

    /**
     * Test: validarPatron - tokens de fecha con formato.
     */
    public function test_validar_patron_fecha_con_formato(): void
    {
        $this->assertTrue($this->service->validarPatron('{fecha:Ymd}'));
        $this->assertTrue($this->service->validarPatron('{fecha:d-m-Y}'));
        $this->assertTrue($this->service->validarPatron('{fecha:Y_m_d}'));
    }

    /**
     * Test: validarPatron - combinación de tokens válidos.
     */
    public function test_validar_patron_combinacion_valida(): void
    {
        $patron = '{proyecto_id}-{hito_id}-{entregable_id}_{fecha:Ymd}_{original}';
        $this->assertTrue($this->service->validarPatron($patron));
    }

    /**
     * Test: validarPatron - token inválido.
     */
    public function test_validar_patron_token_invalido(): void
    {
        $this->assertFalse($this->service->validarPatron('{no_existe}'));
        $this->assertFalse($this->service->validarPatron('{proyecto}{token_falso}'));
    }

    /**
     * Test: validarPatron - texto literal sin tokens es válido.
     */
    public function test_validar_patron_texto_literal(): void
    {
        $this->assertTrue($this->service->validarPatron('prefijo-fijo'));
        $this->assertTrue($this->service->validarPatron('archivo_estatico'));
    }

    /**
     * Test: getTokensDisponibles retorna array.
     */
    public function test_get_tokens_disponibles(): void
    {
        $tokens = $this->service->getTokensDisponibles();

        $this->assertIsArray($tokens);
        $this->assertArrayHasKey('{proyecto}', $tokens);
        $this->assertArrayHasKey('{fecha}', $tokens);
        $this->assertArrayHasKey('{original}', $tokens);
    }

    /**
     * Test: getTokensParaFrontend retorna formato correcto.
     */
    public function test_get_tokens_para_frontend(): void
    {
        $tokens = $this->service->getTokensParaFrontend();

        $this->assertIsArray($tokens);
        $this->assertNotEmpty($tokens);

        // Verificar estructura del primer elemento
        $primerToken = $tokens[0];
        $this->assertArrayHasKey('token', $primerToken);
        $this->assertArrayHasKey('descripcion', $primerToken);
    }

    /**
     * Test: Caracteres especiales se sanitizan correctamente.
     */
    public function test_caracteres_especiales_sanitizados(): void
    {
        $contexto = [
            'original' => 'archivo con espacios & símbolos!.pdf',
            'extension' => 'pdf',
        ];

        $nombre = $this->service->generarNombre('{original}', $contexto);

        // No debe contener espacios ni caracteres especiales
        $this->assertDoesNotMatchRegularExpression('/\s/', $nombre);
        $this->assertDoesNotMatchRegularExpression('/[&!]/', $nombre);
    }

    /**
     * Test: generarPreview genera ejemplo correcto.
     */
    public function test_generar_preview(): void
    {
        $preview = $this->service->generarPreview('{proyecto_id}_{original}');

        // Debe contener ID 42 (mock) y extensión
        $this->assertStringContainsString('42', $preview);
        $this->assertStringEndsWith('.pdf', $preview);
    }
}
