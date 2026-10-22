<?php

namespace Tests\Feature;

use App\Models\Contrato;
use App\Models\Liquidacion;
use App\Models\Trabajador;
use App\Services\LibroRemuneracionesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibroRemuneracionesTest extends TestCase
{
    use RefreshDatabase;

    private function crearTrabajador(string $rut, string $nombres, string $apellidos, string $ingreso = '2020-01-01'): Trabajador
    {
        $trabajador = Trabajador::create([
            'rut' => $rut,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'cargo' => $nombres === 'Ana' ? 'Enfermera' : 'Auxiliar',
            'afp' => 'Modelo',
            'sistema_salud' => 'Fonasa',
            'fecha_ingreso' => $ingreso,
        ]);

        Contrato::create([
            'trabajador_id' => $trabajador->id,
            'sueldo_base' => 600000,
            'tipo_contrato' => 'Indefinido',
            'horas_semanales' => 45,
            'fecha_inicio' => $ingreso,
        ]);

        return $trabajador;
    }

    private function crearLiquidacion(Trabajador $trabajador, int $mes, int $anio, int $sueldoLiquido): Liquidacion
    {
        return Liquidacion::create([
            'trabajador_id' => $trabajador->id,
            'contrato_id' => $trabajador->contratos()->first()->id,
            'mes' => $mes,
            'anio' => $anio,
            'dias_trabajados' => 30,
            'sueldo_base' => 600000,
            'gratificacion' => 150000,
            'total_imponible' => 750000,
            'monto_movilizacion' => 50000,
            'monto_colacion' => 50000,
            'cargas_familiares' => 0,
            'total_no_imponible' => 100000,
            'total_haberes' => 850000,
            'afp_nombre' => 'Modelo',
            'afp_porcentaje' => 10.4,
            'monto_afp' => 66000,
            'salud_nombre' => 'Fonasa',
            'salud_porcentaje' => 7.0,
            'monto_salud' => 42000,
            'monto_cesantia' => 3600,
            'total_descuentos_legales' => 111600,
            'otros_descuentos' => 0,
            'total_descuentos' => 111600,
            'sueldo_liquido' => $sueldoLiquido,
        ]);
    }

    public function test_el_csv_incluye_bom_utf8_y_encabezados()
    {
        $csv = app(LibroRemuneracionesService::class)->generarCsv(10, 2026);

        // BOM UTF-8 para que Excel reconozca los acentos.
        $this->assertTrue(str_starts_with($csv, "\xEF\xBB\xBF"));

        $primeraLinea = explode("\r\n", substr($csv, 3))[0];

        $this->assertEquals(implode(';', LibroRemuneracionesService::COLUMNAS), $primeraLinea);
    }

    public function test_genera_una_fila_por_liquidacion_ordenada_por_apellido()
    {
        $ana = $this->crearTrabajador('11111111-1', 'Ana', 'González');
        $bruno = $this->crearTrabajador('22222222-2', 'Bruno', 'Pérez');

        $this->crearLiquidacion($ana, 10, 2026, 738400);
        $this->crearLiquidacion($bruno, 10, 2026, 700000);

        $csv = app(LibroRemuneracionesService::class)->generarCsv(10, 2026);
        $lineas = array_values(array_filter(explode("\r\n", substr($csv, 3))));

        // Encabezado + 2 trabajadores.
        $this->assertCount(3, $lineas);

        // Orden alfabético por apellido: González antes que Pérez.
        $this->assertStringStartsWith('11111111-1;Ana;González;Enfermera;Indefinido;01/01/2020;30;', $lineas[1]);
        $this->assertStringStartsWith('22222222-2;Bruno;Pérez;Auxiliar;Indefinido;01/01/2020;30;', $lineas[2]);

        // La fila completa lleva todas las columnas de la liquidación.
        $this->assertEquals(
            '11111111-1;Ana;González;Enfermera;Indefinido;01/01/2020;30;600000;150000;750000;50000;50000;0;100000;850000;66000;42000;3600;111600;0;111600;738400',
            $lineas[1],
        );
    }

    public function test_solo_incluye_las_liquidaciones_del_periodo_solicitado()
    {
        $ana = $this->crearTrabajador('11111111-1', 'Ana', 'González');
        $bruno = $this->crearTrabajador('22222222-2', 'Bruno', 'Pérez');

        $this->crearLiquidacion($ana, 10, 2026, 738400);
        $this->crearLiquidacion($bruno, 9, 2026, 700000); // Mes anterior

        $servicio = app(LibroRemuneracionesService::class);

        $this->assertEquals(1, $servicio->cantidadDelPeriodo(10, 2026));

        $csv = $servicio->generarCsv(10, 2026);
        $lineas = array_values(array_filter(explode("\r\n", substr($csv, 3))));

        $this->assertCount(2, $lineas); // Encabezado + 1
        $this->assertStringContainsString('Ana;González', $lineas[1]);
        $this->assertStringNotContainsString('Pérez', $csv);
    }

    public function test_neutraliza_formulas_y_escapa_separadores_en_el_csv()
    {
        $trabajador = Trabajador::create([
            'rut' => '33333333-3',
            'nombres' => '=1+1',
            'apellidos' => '@SUM(A1:A2)',
            'cargo' => 'Auxiliar; Turno',
            'afp' => 'Modelo',
            'sistema_salud' => 'Fonasa',
            'fecha_ingreso' => '2020-01-01',
        ]);

        Contrato::create([
            'trabajador_id' => $trabajador->id,
            'sueldo_base' => 600000,
            'tipo_contrato' => 'Indefinido',
            'horas_semanales' => 45,
            'fecha_inicio' => '2020-01-01',
        ]);

        $this->crearLiquidacion($trabajador, 10, 2026, 738400);

        $csv = app(LibroRemuneracionesService::class)->generarCsv(10, 2026);

        // Las fórmulas se neutralizan con apóstrofo y el separador se entrecomilla.
        $this->assertStringContainsString(
            '33333333-3;\'=1+1;\'@SUM(A1:A2);"Auxiliar; Turno";',
            $csv,
        );
    }

    public function test_sin_liquidaciones_devuelve_solo_el_encabezado()
    {
        $servicio = app(LibroRemuneracionesService::class);

        $this->assertEquals(0, $servicio->cantidadDelPeriodo(12, 2026));

        $csv = $servicio->generarCsv(12, 2026);
        $lineas = array_values(array_filter(explode("\r\n", substr($csv, 3))));

        $this->assertCount(1, $lineas);
    }
}
