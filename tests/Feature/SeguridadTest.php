<?php

namespace Tests\Feature;

use App\Models\Contrato;
use App\Models\Liquidacion;
use App\Models\ParametroLegal;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Verifica que los datos sensibles (parámetros legales, usuarios y PDF de
 * liquidaciones) no queden expuestos a roles sin permiso.
 */
class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    private int $contadorUsuarios = 1;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['administrador', 'jefe de turno', 'contador', 'solo lectura'] as $rol) {
            Role::firstOrCreate(['name' => $rol]);
        }
    }

    private function crearUsuario(?string $rol = null): User
    {
        $usuario = User::create([
            'email' => 'usuario'.$this->contadorUsuarios.'@eleam.cl',
            'password' => 'password',
            'nombre' => 'Nombre',
            'apellido' => 'Apellido',
            'rut' => sprintf('19%07d-%d', $this->contadorUsuarios, random_int(0, 9)),
        ]);
        $this->contadorUsuarios++;

        if ($rol !== null) {
            $usuario->assignRole($rol);
        }

        return $usuario;
    }

    private function crearLiquidacion(): Liquidacion
    {
        $trabajador = Trabajador::create([
            'rut' => '12345678-9',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cargo' => 'Enfermero',
            'afp' => 'Modelo',
            'sistema_salud' => 'Fonasa',
            'fecha_ingreso' => '2025-01-01',
        ]);

        $contrato = Contrato::create([
            'trabajador_id' => $trabajador->id,
            'sueldo_base' => 500000,
            'tipo_contrato' => 'Indefinido',
            'horas_semanales' => 45,
            'fecha_inicio' => '2025-01-01',
        ]);

        return Liquidacion::create([
            'trabajador_id' => $trabajador->id,
            'contrato_id' => $contrato->id,
            'mes' => 10,
            'anio' => 2026,
            'dias_trabajados' => 30,
            'sueldo_base' => 500000,
            'gratificacion' => 125000,
            'total_imponible' => 625000,
            'monto_colacion' => 0,
            'monto_movilizacion' => 0,
            'cargas_familiares' => 0,
            'total_no_imponible' => 0,
            'total_haberes' => 625000,
            'afp_nombre' => 'Modelo',
            'afp_porcentaje' => 10.58,
            'monto_afp' => 66125,
            'salud_nombre' => 'Fonasa',
            'salud_porcentaje' => 7.0,
            'monto_salud' => 43750,
            'monto_cesantia' => 3750,
            'sis_porcentaje' => 1.49,
            'monto_sis' => 9313,
            'total_descuentos_legales' => 113625,
            'otros_descuentos' => 0,
            'total_descuentos' => 113625,
            'sueldo_liquido' => 511375,
        ]);
    }

    public function test_solo_el_administrador_puede_ver_los_parametros_legales(): void
    {
        foreach (['contador', 'jefe de turno', 'solo lectura'] as $rol) {
            // La sesión guarda el hash de contraseña del usuario anterior;
            // se limpia para probar cada rol de forma independiente.
            $this->flushSession();

            $this->actingAs($this->crearUsuario($rol))
                ->get('/admin/parametro-legals')
                ->assertForbidden();
        }

        $this->flushSession();

        $this->actingAs($this->crearUsuario('administrador'))
            ->get('/admin/parametro-legals')
            ->assertOk();
    }

    public function test_solo_el_administrador_puede_ver_la_gestion_de_usuarios(): void
    {
        foreach (['jefe de turno', 'contador', 'solo lectura'] as $rol) {
            $this->flushSession();

            $this->actingAs($this->crearUsuario($rol))
                ->get('/admin/users')
                ->assertForbidden();
        }

        $this->flushSession();

        $this->actingAs($this->crearUsuario('administrador'))
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_la_politica_de_parametros_legales_niega_la_gestion_a_no_administradores(): void
    {
        $contador = $this->crearUsuario('contador');
        $administrador = $this->crearUsuario('administrador');

        $this->assertFalse($contador->can('viewAny', ParametroLegal::class));
        $this->assertFalse($contador->can('create', ParametroLegal::class));
        $this->assertTrue($administrador->can('viewAny', ParametroLegal::class));
        $this->assertTrue($administrador->can('create', ParametroLegal::class));
    }

    public function test_el_pdf_de_liquidacion_requiere_autenticacion(): void
    {
        $liquidacion = $this->crearLiquidacion();

        $this->get("/admin/liquidaciones/{$liquidacion->id}/pdf")
            ->assertRedirectContains('/admin/login');
    }

    public function test_el_pdf_de_liquidacion_niega_a_usuarios_sin_rol(): void
    {
        $liquidacion = $this->crearLiquidacion();

        $this->actingAs($this->crearUsuario())
            ->get("/admin/liquidaciones/{$liquidacion->id}/pdf")
            ->assertForbidden();
    }

    public function test_el_pdf_de_liquidacion_es_visible_para_solo_lectura(): void
    {
        $liquidacion = $this->crearLiquidacion();

        $respuesta = $this->actingAs($this->crearUsuario('solo lectura'))
            ->get("/admin/liquidaciones/{$liquidacion->id}/pdf");

        $respuesta->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $respuesta->headers->get('content-type'),
        );
    }
}
