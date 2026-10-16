<?php

namespace Tests\Feature;

use App\Models\Contrato;
use App\Models\ParametroLegal;
use App\Models\Trabajador;
use App\Services\RemuneracionService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemuneracionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcular_liquidacion_basica_con_30_dias()
    {
        $trabajador = Trabajador::create([
            'rut' => '12345678-9',
            'nombres' => 'Juan',
            'apellidos' => 'Perez',
            'cargo' => 'Enfermero',
            'afp' => 'Modelo',
            'sistema_salud' => 'Fonasa',
            'fecha_ingreso' => '2025-01-01'
        ]);
        $contrato = Contrato::create([
            'trabajador_id' => $trabajador->id,
            'sueldo_base' => 500000,
            'tipo_contrato' => 'Indefinido',
            'horas_semanales' => 45,
            'fecha_inicio' => '2025-01-01'
        ]);

        ParametroLegal::create([
            'mes' => 10,
            'anio' => 2026,
            'valor_uf' => 37000.50,
            'valor_utm' => 66000,
            'sueldo_minimo' => 500000,
            'tope_imponible_afp' => 84.3,
            'tope_imponible_ips' => 60.0,
            'tope_imponible_cesantia' => 126.6,
        ]);

        $service = new RemuneracionService();
        $liquidacion = $service->calcularLiquidacion($trabajador, 10, 2026, 30);

        $this->assertNotNull($liquidacion);
        $this->assertEquals(500000, $liquidacion->sueldo_base);
        $this->assertEquals(30, $liquidacion->dias_trabajados);

        // Gratificación: 25% de 500000 = 125000
        $this->assertEquals(125000, $liquidacion->gratificacion);
        
        $this->assertEquals(625000, $liquidacion->total_imponible);
        $this->assertEquals((int) round(625000 * 0.1058), $liquidacion->monto_afp);
        $this->assertEquals((int) round(625000 * 0.07), $liquidacion->monto_salud);
        $this->assertEquals((int) round(625000 * 0.006), $liquidacion->monto_cesantia);
    }

    public function test_calcular_liquidacion_lanza_error_si_no_hay_parametros()
    {
        $trabajador = Trabajador::create([
            'rut' => '12345678-9',
            'nombres' => 'Juan',
            'apellidos' => 'Perez',
            'cargo' => 'Enfermero',
            'afp' => 'Modelo',
            'sistema_salud' => 'Fonasa',
            'fecha_ingreso' => '2025-01-01'
        ]);
        $service = new RemuneracionService();
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No existen parámetros legales configurados');
        
        $service->calcularLiquidacion($trabajador, 10, 2026);
    }

    public function test_calcular_liquidacion_proporcional_a_dias_trabajados()
    {
        $trabajador = Trabajador::create([
            'rut' => '12345678-9',
            'nombres' => 'Juan',
            'apellidos' => 'Perez',
            'cargo' => 'Enfermero',
            'afp' => 'Modelo',
            'sistema_salud' => 'Fonasa',
            'fecha_ingreso' => '2025-01-01'
        ]);
        $contrato = Contrato::create([
            'trabajador_id' => $trabajador->id,
            'sueldo_base' => 600000,
            'tipo_contrato' => 'Plazo Fijo',
            'horas_semanales' => 45,
            'fecha_inicio' => '2025-01-01'
        ]);

        ParametroLegal::create([
            'mes' => 10,
            'anio' => 2026,
            'valor_uf' => 37000.50,
            'valor_utm' => 66000,
            'sueldo_minimo' => 500000,
            'tope_imponible_afp' => 84.3,
            'tope_imponible_ips' => 60.0,
            'tope_imponible_cesantia' => 126.6,
        ]);

        $service = new RemuneracionService();
        $liquidacion = $service->calcularLiquidacion($trabajador, 10, 2026, 15);

        $this->assertEquals(300000, $liquidacion->sueldo_base);
        $this->assertEquals(75000, $liquidacion->gratificacion);
        $this->assertEquals(375000, $liquidacion->total_imponible);
        
        // Plazo fijo -> 0% cesantía para el trabajador
        $this->assertEquals(0, $liquidacion->monto_cesantia);
    }
}
