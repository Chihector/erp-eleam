<?php

namespace Tests\Feature;

use App\Models\Contrato;
use App\Models\ParametroLegal;
use App\Models\Trabajador;
use App\Services\RemuneracionService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre el cálculo previsional con los datos propios de cada trabajador:
 * tasa AFP, plan de Isapre, SIS del empleador y contrato vigente.
 */
class RemuneracionPrevisionalTest extends TestCase
{
    use RefreshDatabase;

    private function crearParametro(array $cambios = []): ParametroLegal
    {
        return ParametroLegal::create(array_merge([
            'mes' => 10,
            'anio' => 2026,
            'valor_uf' => 40000,
            'valor_utm' => 72000,
            'sueldo_minimo' => 500000,
            'tope_imponible_afp' => 84.3,
            'tope_imponible_ips' => 60.0,
            'tope_imponible_cesantia' => 126.6,
            'porcentaje_sis' => 1.49,
        ], $cambios));
    }

    private function crearTrabajador(array $cambios = []): Trabajador
    {
        return Trabajador::create(array_merge([
            'rut' => '12345678-9',
            'nombres' => 'Juan',
            'apellidos' => 'Perez',
            'cargo' => 'Enfermero',
            'afp' => 'Modelo',
            'sistema_salud' => 'Fonasa',
            'fecha_ingreso' => '2025-01-01',
        ], $cambios));
    }

    private function crearContrato(Trabajador $trabajador, array $cambios = []): Contrato
    {
        return Contrato::create(array_merge([
            'trabajador_id' => $trabajador->id,
            'sueldo_base' => 500000,
            'tipo_contrato' => 'Indefinido',
            'horas_semanales' => 45,
            'fecha_inicio' => '2025-01-01',
        ], $cambios));
    }

    public function test_usa_la_tasa_afp_registrada_en_el_trabajador(): void
    {
        $this->crearParametro();

        $trabajador = $this->crearTrabajador([
            'afp' => 'Capital',
            'afp_tasa' => 12.5,
        ]);
        $this->crearContrato($trabajador);

        $liquidacion = app(RemuneracionService::class)
            ->calcularLiquidacion($trabajador, 10, 2026, 30);

        // Sueldo 500.000 + gratificación 125.000 = 625.000 imponible.
        $this->assertSame('Capital', $liquidacion->afp_nombre);
        $this->assertEquals(12.5, (float) $liquidacion->afp_porcentaje);
        $this->assertSame((int) round(625000 * 0.125), $liquidacion->monto_afp);
    }

    public function test_calcula_isapre_con_el_plan_en_uf_del_trabajador(): void
    {
        $this->crearParametro();

        $trabajador = $this->crearTrabajador([
            'sistema_salud' => 'Isapre',
            'salud_plan_uf' => 2.5,
        ]);
        $this->crearContrato($trabajador);

        $liquidacion = app(RemuneracionService::class)
            ->calcularLiquidacion($trabajador, 10, 2026, 30);

        // Plan 2,5 UF x 40.000 = 100.000, superior al mínimo legal de 7%.
        $this->assertSame('Isapre', $liquidacion->salud_nombre);
        $this->assertSame(100000, $liquidacion->monto_salud);
        $this->assertEquals(16.0, (float) $liquidacion->salud_porcentaje);
    }

    public function test_isapre_respeta_el_minimo_legal_del_7_por_ciento(): void
    {
        $this->crearParametro();

        $trabajador = $this->crearTrabajador([
            'sistema_salud' => 'Isapre',
            'salud_plan_uf' => 1.0, // 40.000, menor que el 7% de 625.000.
        ]);
        $this->crearContrato($trabajador);

        $liquidacion = app(RemuneracionService::class)
            ->calcularLiquidacion($trabajador, 10, 2026, 30);

        $this->assertSame((int) round(625000 * 0.07), $liquidacion->monto_salud);
        $this->assertEquals(7.0, (float) $liquidacion->salud_porcentaje);
    }

    public function test_aplica_el_tope_imponible_de_salud_de_60_uf(): void
    {
        $this->crearParametro();

        $trabajador = $this->crearTrabajador();
        $this->crearContrato($trabajador, ['sueldo_base' => 3200000]);

        $liquidacion = app(RemuneracionService::class)
            ->calcularLiquidacion($trabajador, 10, 2026, 30);

        // Imponible 3.397.917; el 7 % de salud se aplica sobre 60 UF = 2.400.000.
        $this->assertSame(2400000, (int) round(60 * 40000));
        $this->assertSame(168000, $liquidacion->monto_salud);
    }

    public function test_el_sis_es_aporte_del_empleador_y_no_se_descuenta_del_liquido(): void
    {
        $this->crearParametro();

        $trabajador = $this->crearTrabajador([
            'afp_tasa' => 12.5,
        ]);
        $this->crearContrato($trabajador);

        $liquidacion = app(RemuneracionService::class)
            ->calcularLiquidacion($trabajador, 10, 2026, 30);

        $afp = (int) round(625000 * 0.125);
        $salud = (int) round(625000 * 0.07);
        $cesantia = (int) round(625000 * 0.006);
        $sis = (int) round(625000 * 0.0149);

        $this->assertEquals(1.49, (float) $liquidacion->sis_porcentaje);
        $this->assertSame($sis, $liquidacion->monto_sis);

        // El SIS informa el costo del empleador, pero no integra los descuentos.
        $this->assertSame($afp + $salud + $cesantia, $liquidacion->total_descuentos_legales);
        $this->assertSame($afp + $salud + $cesantia, $liquidacion->total_descuentos);
        $this->assertSame(625000 - $afp - $salud - $cesantia, $liquidacion->sueldo_liquido);
    }

    public function test_selecciona_el_contrato_vigente_del_periodo(): void
    {
        $this->crearParametro();

        $trabajador = $this->crearTrabajador();

        $antiguo = $this->crearContrato($trabajador, [
            'sueldo_base' => 900000,
            'fecha_inicio' => '2024-01-01',
            'fecha_termino' => '2026-09-30',
        ]);
        $vigente = $this->crearContrato($trabajador, [
            'sueldo_base' => 700000,
            'fecha_inicio' => '2026-10-01',
        ]);

        $liquidacion = app(RemuneracionService::class)
            ->calcularLiquidacion($trabajador, 10, 2026, 30);

        $this->assertSame($vigente->id, $liquidacion->contrato_id);
        $this->assertNotSame($antiguo->id, $liquidacion->contrato_id);
        $this->assertSame(700000, $liquidacion->sueldo_base);
    }

    public function test_falla_si_el_trabajador_no_tiene_afp(): void
    {
        $this->crearParametro();

        $trabajador = $this->crearTrabajador(['afp' => null]);
        $this->crearContrato($trabajador);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('no tiene AFP registrada');

        app(RemuneracionService::class)->calcularLiquidacion($trabajador, 10, 2026);
    }

    public function test_falla_si_la_afp_no_tiene_tasa_configurada(): void
    {
        $this->crearParametro();

        $trabajador = $this->crearTrabajador(['afp' => 'AFP Nueva']);
        $this->crearContrato($trabajador);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No hay una tasa configurada');

        app(RemuneracionService::class)->calcularLiquidacion($trabajador, 10, 2026);
    }

    public function test_falla_si_el_trabajador_no_tiene_salud(): void
    {
        $this->crearParametro();

        $trabajador = $this->crearTrabajador(['sistema_salud' => null]);
        $this->crearContrato($trabajador);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('no tiene sistema de salud registrado');

        app(RemuneracionService::class)->calcularLiquidacion($trabajador, 10, 2026);
    }

    public function test_falla_si_esta_en_isapre_sin_plan_de_uf(): void
    {
        $this->crearParametro();

        $trabajador = $this->crearTrabajador([
            'sistema_salud' => 'Isapre',
            'salud_plan_uf' => null,
        ]);
        $this->crearContrato($trabajador);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('no tiene registrado el plan en UF');

        app(RemuneracionService::class)->calcularLiquidacion($trabajador, 10, 2026);
    }

    public function test_falla_si_no_hay_contrato_vigente_en_el_periodo(): void
    {
        $this->crearParametro();

        $trabajador = $this->crearTrabajador();
        $this->crearContrato($trabajador, [
            'fecha_inicio' => '2024-01-01',
            'fecha_termino' => '2026-08-31',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('no tiene un contrato vigente');

        app(RemuneracionService::class)->calcularLiquidacion($trabajador, 10, 2026);
    }
}
