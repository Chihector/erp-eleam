<?php

namespace Tests\Feature;

use App\Models\Gasto;
use App\Models\Ingreso;
use App\Services\FinanzasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanzasServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcula_totales_y_saldo_disponible()
    {
        Gasto::create([
            'fecha' => '2026-10-10',
            'categoria' => 'Luz',
            'monto' => 50000,
            'descripcion' => 'Boleta de luz',
        ]);

        Gasto::create([
            'fecha' => '2026-10-11',
            'categoria' => 'Agua',
            'monto' => 20000,
            'descripcion' => 'Factura de agua',
        ]);

        Ingreso::create([
            'fecha' => '2026-10-12',
            'categoria' => 'Subsidio',
            'monto' => 1500000,
            'descripcion' => 'Aporte SENAMA',
        ]);

        $finanzas = new FinanzasService();

        $this->assertEquals(70000, $finanzas->totalGastos());
        $this->assertEquals(1500000, $finanzas->totalIngresos());
        $this->assertEquals(1430000, $finanzas->saldoDisponible());
    }

    public function test_calcula_totales_del_mes_en_curso()
    {
        // Registros del mes actual (octubre 2026)
        Gasto::create([
            'fecha' => '2026-10-10',
            'categoria' => 'Luz',
            'monto' => 10000,
        ]);

        Ingreso::create([
            'fecha' => '2026-10-12',
            'categoria' => 'Subsidio',
            'monto' => 500000,
        ]);

        // Registros de otros meses no deben contar para el mes actual
        Gasto::create([
            'fecha' => '2026-09-10',
            'categoria' => 'Gas',
            'monto' => 90000,
        ]);

        Ingreso::create([
            'fecha' => '2026-09-12',
            'categoria' => 'Donación',
            'monto' => 100000,
        ]);

        $finanzas = new FinanzasService();

        $totales = $finanzas->totalDelMes(2026, 10);

        $this->assertEquals(10000, $totales['gastos']);
        $this->assertEquals(500000, $totales['ingresos']);
    }
}
