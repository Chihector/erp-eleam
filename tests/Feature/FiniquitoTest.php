<?php

namespace Tests\Feature;

use App\Models\Contrato;
use App\Models\Finiquito;
use App\Models\ParametroLegal;
use App\Models\Trabajador;
use App\Models\User;
use App\Services\FiniquitoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FiniquitoTest extends TestCase
{
    use RefreshDatabase;

    private function crearParametrosLegales(): void
    {
        ParametroLegal::create([
            'mes' => 10,
            'anio' => 2026,
            'valor_uf' => 37000.00,
            'valor_utm' => 66000,
            'sueldo_minimo' => 500000,
            'tope_imponible_afp' => 84.3,
            'tope_imponible_ips' => 60.0,
            'tope_imponible_cesantia' => 126.6,
        ]);
    }

    private function crearTrabajadorConContrato(string $fechaIngreso = '2024-10-15', int $sueldoBase = 600000): Trabajador
    {
        $trabajador = Trabajador::create([
            'rut' => '12345678-9',
            'nombres' => 'Juana',
            'apellidos' => 'Pérez',
            'cargo' => 'Cuidadora',
            'afp' => 'Modelo',
            'sistema_salud' => 'Fonasa',
            'fecha_ingreso' => $fechaIngreso,
        ]);

        Contrato::create([
            'trabajador_id' => $trabajador->id,
            'sueldo_base' => $sueldoBase,
            'tipo_contrato' => 'Indefinido',
            'horas_semanales' => 45,
            'fecha_inicio' => $fechaIngreso,
        ]);

        return $trabajador;
    }

    private function calcular(Trabajador $trabajador, string $causal): array
    {
        return app(FiniquitoService::class)->calcular(
            $trabajador,
            $causal,
            Carbon::parse('2026-10-15'),
        );
    }

    public function test_mutuo_acuerdo_no_paga_indemnizaciones_pero_si_vacaciones_y_gratificacion()
    {
        // Con 2 años exactos de antigüedad: 24 meses * 1.25 = 30 días de vacaciones al 15/10/2026.
        $this->travelTo(Carbon::parse('2026-10-15 00:00'));
        $this->crearParametrosLegales();

        $trabajador = $this->crearTrabajadorConContrato('2024-10-15', 600000);

        $calculo = $this->calcular($trabajador, 'Mutuo Acuerdo (Art. 159 N°1)');

        // Sin indemnización ni aviso previo.
        $this->assertEquals(0, $calculo['indemnizacion_anios']);
        $this->assertEquals(0, $calculo['indemnizacion_aviso_previo']);

        // Antigüedad: 2 años, 0 meses.
        $this->assertEquals(2, $calculo['anios_servicio']);
        $this->assertEquals(0, $calculo['meses_servicio']);

        // Vacaciones: 30 días * $20.000 = $600.000.
        $this->assertEquals(600000, $calculo['vacaciones_pendientes']);

        // Gratificación: min($150.000; tope $197.917) * 10 meses = $1.500.000.
        $this->assertEquals(1500000, $calculo['gratificacion_pendiente']);

        // Total: 600.000 + 1.500.000.
        $this->assertEquals(2100000, $calculo['total_haberes']);
        $this->assertEquals(2100000, $calculo['liquido_pagar']);
    }

    public function test_necesidades_de_la_empresa_paga_indemnizacion_por_anos_y_aviso_previo()
    {
        $this->travelTo(Carbon::parse('2026-10-15 00:00'));
        $this->crearParametrosLegales();

        $trabajador = $this->crearTrabajadorConContrato('2024-10-15', 600000);

        $calculo = $this->calcular($trabajador, 'Necesidades de la Empresa (Art. 161)');

        // 2 años * $600.000 = $1.200.000 de indemnización.
        $this->assertEquals(1200000, $calculo['indemnizacion_anios']);

        // Aviso previo: 1 sueldo mensual.
        $this->assertEquals(600000, $calculo['indemnizacion_aviso_previo']);

        // Total: 1.200.000 + 600.000 + 600.000 (vacaciones) + 1.500.000 (gratificación).
        $this->assertEquals(3900000, $calculo['total_haberes']);
    }

    public function test_indemnizacion_tiene_tope_de_11_anos()
    {
        $this->travelTo(Carbon::parse('2026-10-15 00:00'));
        $this->crearParametrosLegales();

        // 16 años de antigüedad: solo se indemnizan 11.
        $trabajador = $this->crearTrabajadorConContrato('2010-01-01', 600000);

        $calculo = $this->calcular($trabajador, 'Necesidades de la Empresa (Art. 161)');

        $this->assertEquals(16, $calculo['anios_servicio']);
        $this->assertEquals(6600000, $calculo['indemnizacion_anios']); // 11 * 600.000
    }

    public function test_sueldo_de_calculo_tiene_tope_de_90_uf()
    {
        $this->travelTo(Carbon::parse('2026-10-15 00:00'));
        $this->crearParametrosLegales();

        // Sueldo de $5.000.000, sobre el tope de 90 UF ($3.330.000 con UF $37.000).
        $trabajador = $this->crearTrabajadorConContrato('2024-10-15', 5000000);

        $calculo = $this->calcular($trabajador, 'Necesidades de la Empresa (Art. 161)');

        $this->assertEquals(3330000, $calculo['sueldo_base_calculo']);
        $this->assertEquals(6660000, $calculo['indemnizacion_anios']); // 2 * 3.330.000
        $this->assertEquals(3330000, $calculo['indemnizacion_aviso_previo']);
    }

    public function test_renuncia_voluntaria_solo_paga_vacaciones_y_gratificacion()
    {
        $this->travelTo(Carbon::parse('2026-10-15 00:00'));
        $this->crearParametrosLegales();

        $trabajador = $this->crearTrabajadorConContrato('2024-10-15', 600000);

        $calculo = $this->calcular($trabajador, 'Renuncia Voluntaria');

        $this->assertEquals(0, $calculo['indemnizacion_anios']);
        $this->assertEquals(0, $calculo['indemnizacion_aviso_previo']);
        $this->assertEquals(2100000, $calculo['total_haberes']);
    }

    public function test_sin_contrato_lanza_excepcion()
    {
        $this->travelTo(Carbon::parse('2026-10-15 00:00'));
        $this->crearParametrosLegales();

        $trabajador = Trabajador::create([
            'rut' => '12345678-9',
            'nombres' => 'Sin',
            'apellidos' => 'Contrato',
            'cargo' => 'Auxiliar',
            'afp' => 'Modelo',
            'sistema_salud' => 'Fonasa',
            'fecha_ingreso' => '2024-10-15',
        ]);

        $this->expectException(\Exception::class);

        $this->calcular($trabajador, 'Mutuo Acuerdo (Art. 159 N°1)');
    }

    public function test_sin_parametros_legales_lanza_excepcion()
    {
        $this->travelTo(Carbon::parse('2026-10-15 00:00'));

        $trabajador = $this->crearTrabajadorConContrato('2024-10-15', 600000);

        $this->expectException(\Exception::class);

        $this->calcular($trabajador, 'Necesidades de la Empresa (Art. 161)');
    }

    public function test_el_liquido_se_recalcula_descontando_otros_descuentos()
    {
        $trabajador = $this->crearTrabajadorConContrato();

        $finiquito = Finiquito::create([
            'trabajador_id' => $trabajador->id,
            'fecha_termino' => '2026-10-15',
            'causal' => 'Mutuo Acuerdo (Art. 159 N°1)',
            'total_haberes' => 1000000,
            'otros_descuentos' => 250000,
        ]);

        $this->assertEquals(750000, $finiquito->fresh()->liquido_pagar);
    }

    public function test_pdf_del_finiquito_se_renderiza_para_administrador()
    {
        $trabajador = $this->crearTrabajadorConContrato();

        $finiquito = Finiquito::create([
            'trabajador_id' => $trabajador->id,
            'fecha_termino' => '2026-10-15',
            'causal' => 'Necesidades de la Empresa (Art. 161)',
            'anios_servicio' => 2,
            'sueldo_base_calculo' => 600000,
            'indemnizacion_anios' => 1200000,
            'indemnizacion_aviso_previo' => 600000,
            'vacaciones_pendientes' => 600000,
            'gratificacion_pendiente' => 1500000,
            'total_haberes' => 3900000,
        ]);

        $admin = User::create([
            'nombre' => 'Admin',
            'apellido' => 'Test',
            'rut' => '11111111-1',
            'email' => 'admin@test.cl',
            'password' => 'secret123',
        ]);

        $admin->assignRole(Role::firstOrCreate(['name' => 'administrador']));

        $respuesta = $this->actingAs($admin)->get(route('finiquitos.pdf', $finiquito));

        $respuesta->assertOk();
    }
}
