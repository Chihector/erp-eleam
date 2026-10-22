<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Contrato;
use App\Models\LicenciaMedica;
use App\Models\ParametroLegal;
use App\Models\Trabajador;
use App\Services\LibroRemuneracionesService;
use App\Services\RemuneracionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Piloto de remuneraciones con cuatro perfiles realistas y los valores
 * oficiales de octubre de 2026 (UF al 30/09/2026 y UTM del SII).
 *
 * Los RUT y nombres son ficticios: no se usan datos personales reales.
 */
class PilotoRemuneraciones2026Test extends TestCase
{
    use RefreshDatabase;

    private ParametroLegal $parametro;

    private RemuneracionService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parametro = ParametroLegal::create([
            'mes' => 10,
            'anio' => 2026,
            'valor_uf' => 41057.20,
            'valor_utm' => 72151,
            'sueldo_minimo' => 553553,
            'tope_imponible_afp' => 84.3,
            'tope_imponible_ips' => 60.0,
            'tope_imponible_cesantia' => 126.6,
            'porcentaje_sis' => 1.49,
            'origen' => 'piloto',
            'verificado_en' => now(),
        ]);

        $this->servicio = app(RemuneracionService::class);
    }

    private function crearTrabajador(
        string $rut,
        string $nombres,
        string $apellidos,
        string $cargo,
        int $sueldo,
        string $salud = 'Fonasa',
        ?float $planUf = null,
    ): Trabajador {
        $trabajador = Trabajador::create([
            'rut' => $rut,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'cargo' => $cargo,
            'afp' => 'Modelo',
            'afp_tasa' => 10.58,
            'sistema_salud' => $salud,
            'salud_plan_uf' => $planUf,
            'fecha_ingreso' => '2024-01-01',
        ]);

        Contrato::create([
            'trabajador_id' => $trabajador->id,
            'sueldo_base' => $sueldo,
            'tipo_contrato' => 'Indefinido',
            'horas_semanales' => 45,
            'fecha_inicio' => '2024-01-01',
        ]);

        return $trabajador;
    }

    public function test_piloto_calcula_cuatro_perfiles_con_licencia_isapre_alto_sueldo_y_asistencia(): void
    {
        $fonasa = $this->crearTrabajador('90000001-1', 'Piloto', 'Fonasa Licencia', 'TENS', 700000);
        $isapre = $this->crearTrabajador('90000002-2', 'Piloto', 'Isapre', 'Enfermera', 1200000, 'Isapre', 2.5);
        $alto = $this->crearTrabajador('90000003-3', 'Piloto', 'Alto Sueldo', 'Director Técnico', 3000000, 'Isapre', 4.0);
        $asistencia = $this->crearTrabajador('90000004-4', 'Piloto', 'Asistencia', 'Auxiliar', 500000);

        LicenciaMedica::create([
            'trabajador_id' => $fonasa->id,
            'tipo' => 'Común',
            'entidad_emisora' => 'Fonasa',
            'fecha_inicio' => '2026-10-05',
            'fecha_fin' => '2026-10-09',
            'estado' => 'Validada',
        ]);

        for ($dia = 1; $dia <= 12; $dia++) {
            Asistencia::create([
                'trabajador_id' => $asistencia->id,
                'fecha_hora_entrada' => sprintf('2026-10-%02d 08:00:00', $dia),
                'fecha_hora_salida' => sprintf('2026-10-%02d 20:00:00', $dia),
            ]);
        }

        $lFonasa = $this->servicio->calcularLiquidacion($fonasa, 10, 2026, null, [
            'origen_dias' => RemuneracionService::ORIGEN_DIAS_LICENCIAS,
        ]);
        $lIsapre = $this->servicio->calcularLiquidacion($isapre, 10, 2026, 30, [
            'colacion' => 50000,
            'movilizacion' => 30000,
        ]);
        $lAlto = $this->servicio->calcularLiquidacion($alto, 10, 2026, 30, [
            'anticipo' => 200000,
            'descuento_sindicato' => 10000,
            'colacion' => 50000,
            'movilizacion' => 30000,
            'cargas_familiares' => 20000,
        ]);
        $lAsistencia = $this->servicio->calcularLiquidacion($asistencia, 10, 2026, null, [
            'origen_dias' => RemuneracionService::ORIGEN_DIAS_ASISTENCIA,
        ]);

        // Perfil 1: 25 días por licencia, sin IUSC.
        $this->assertSame(25, $lFonasa->dias_trabajados);
        $this->assertSame('licencias', $lFonasa->origen_dias);
        $this->assertSame(729166, $lFonasa->total_imponible);
        $this->assertSame(596603, $lFonasa->renta_liquida_imponible);
        $this->assertSame(0, $lFonasa->impuesto_segunda_categoria);
        $this->assertSame(596603, $lFonasa->sueldo_liquido);

        // Perfil 2: Isapre con plan de 2,5 UF y 4% de IUSC.
        $this->assertSame(1419115, $lIsapre->total_imponible);
        $this->assertSame(1157815, $lIsapre->renta_liquida_imponible);
        $this->assertSame(7351, $lIsapre->impuesto_segunda_categoria);
        $this->assertSame(102643, $lIsapre->monto_salud);
        $this->assertSame(1230464, $lIsapre->sueldo_liquido);

        // Perfil 3: alto sueldo, tope de salud de 60 UF, anticipo y sindicato.
        $this->assertSame(3219115, $lAlto->total_imponible);
        $this->assertSame(172440, $lAlto->monto_salud);
        $this->assertSame(2686778, $lAlto->renta_liquida_imponible);
        $this->assertSame(89400, $lAlto->impuesto_segunda_categoria);
        $this->assertSame(210000, $lAlto->otros_descuentos);
        $this->assertSame(2487378, $lAlto->sueldo_liquido);

        // Perfil 4: 12 días según asistencias, sin IUSC.
        $this->assertSame(12, $lAsistencia->dias_trabajados);
        $this->assertSame('asistencia', $lAsistencia->origen_dias);
        $this->assertSame(250000, $lAsistencia->total_imponible);
        $this->assertSame(204550, $lAsistencia->sueldo_liquido);

        // La suma de descuentos siempre cuadra con el líquido.
        foreach ([$lFonasa, $lIsapre, $lAlto, $lAsistencia] as $liquidacion) {
            $this->assertSame(
                $liquidacion->total_haberes - $liquidacion->total_descuentos,
                $liquidacion->sueldo_liquido,
            );
            $this->assertSame(
                $liquidacion->monto_afp
                    + $liquidacion->monto_salud
                    + $liquidacion->monto_cesantia
                    + $liquidacion->impuesto_segunda_categoria,
                $liquidacion->total_descuentos_legales,
            );
        }
    }

    public function test_piloto_genera_pdf_y_csv_con_impuesto_y_origen_de_dias(): void
    {
        $trabajador = $this->crearTrabajador(
            '90000005-5',
            'Piloto',
            'Documentos',
            'Enfermero',
            3000000,
            'Isapre',
            4.0,
        );

        $liquidacion = $this->servicio->calcularLiquidacion($trabajador, 10, 2026, 30, [
            'anticipo' => 150000,
            'descuento_sindicato' => 10000,
            'colacion' => 50000,
        ]);

        $html = view('pdf.liquidacion', [
            'liquidacion' => $liquidacion,
            'turnos' => collect(),
        ])->render();

        $this->assertStringContainsString('Impuesto Único de Segunda Categoría', $html);
        $this->assertStringContainsString('$89.400', $html);
        $this->assertStringContainsString('Anticipo', $html);
        $this->assertStringContainsString('BORRADOR', $html);
        $this->assertStringContainsString('30 (manual)', $html);

        $pdf = Pdf::loadView('pdf.liquidacion', [
            'liquidacion' => $liquidacion,
            'turnos' => collect(),
        ])->output();

        $this->assertStringStartsWith('%PDF', $pdf);

        $csv = app(LibroRemuneracionesService::class)->generarCsv(10, 2026);

        $this->assertStringContainsString('Origen Días', $csv);
        $this->assertStringContainsString('Impuesto Único 2ª Categoría', $csv);
        $this->assertStringContainsString('90000005-5', $csv);
        $this->assertStringContainsString(';30;manual;3000000;219115;3219115;2686778;', $csv);
        $this->assertStringContainsString(';89400;621737;150000;10000;160000;781737;', $csv);
    }
}
