<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Contrato;
use App\Models\Finiquito;
use App\Models\Gasto;
use App\Models\Ingreso;
use App\Models\LicenciaMedica;
use App\Models\Liquidacion;
use App\Models\ParametroLegal;
use App\Models\Trabajador;
use App\Models\Turno;
use App\Models\Vacacion;
use App\Services\FinanzasService;
use App\Services\FiniquitoService;
use App\Services\RemuneracionService;
use Carbon\Carbon;
use Database\Seeders\DemoOperacionalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EscenarioOperacionalDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_es_idempotente(): void
    {
        $seeder = app(DemoOperacionalSeeder::class);
        $seeder->run();
        $seeder->run();

        $trabajador = Trabajador::where('rut', '12345678-5')->firstOrFail();

        $this->assertSame(1, Trabajador::count());
        $contrato = Contrato::where('trabajador_id', $trabajador->id)->firstOrFail();
        $this->assertSame(1, Contrato::where('trabajador_id', $trabajador->id)->count());
        $this->assertSame(42, $contrato->horas_semanales);
        $this->assertSame(2, Turno::where('trabajador_id', $trabajador->id)->count());
        $this->assertSame(1, Asistencia::where('trabajador_id', $trabajador->id)->count());
        $this->assertSame(1, Vacacion::where('trabajador_id', $trabajador->id)->count());
        $this->assertSame('2026-10-14', Vacacion::where('trabajador_id', $trabajador->id)->firstOrFail()->fecha_inicio->toDateString());
        $this->assertSame(1, LicenciaMedica::where('trabajador_id', $trabajador->id)->count());
        $this->assertSame(1, Liquidacion::where('trabajador_id', $trabajador->id)->count());
        $this->assertSame(1, Finiquito::where('trabajador_id', $trabajador->id)->count());
        $this->assertSame(1, Ingreso::count());
        $this->assertSame(1, Gasto::count());
    }

    public function test_escenario_operacional_completo_con_datos_sinteticos(): void
    {
        $hoy = Carbon::parse('2026-10-22 12:00:00');
        Carbon::setTestNow($hoy);

        try {
            $parametro = ParametroLegal::create([
                'mes' => $hoy->month,
                'anio' => $hoy->year,
                'valor_uf' => 38000,
                'valor_utm' => 67000,
                'sueldo_minimo' => 500000,
                'tope_imponible_afp' => 84.3,
                'tope_imponible_ips' => 60,
                'tope_imponible_cesantia' => 126.6,
                'porcentaje_sis' => 1.49,
            ]);

            $trabajador = Trabajador::create([
                'rut' => '12345678-5',
                'nombres' => 'DEMO',
                'apellidos' => 'NO ES PERSONA REAL',
                'cargo' => 'Cuidador/a (datos de prueba)',
                'afp' => 'Modelo',
                'sistema_salud' => 'Fonasa',
                'fecha_ingreso' => '2024-10-01',
            ]);

            $contrato = Contrato::create([
                'trabajador_id' => $trabajador->id,
                'tipo_contrato' => 'Indefinido',
                'sueldo_base' => 750000,
                'horas_semanales' => 42,
                'jornada_excepcional' => false,
                'fecha_inicio' => '2024-10-01',
            ]);

            $turno = Turno::create([
                'trabajador_id' => $trabajador->id,
                'fecha_inicio' => '2026-10-20 08:00:00',
                'fecha_fin' => '2026-10-20 20:00:00',
                'tipo_turno' => 'Día',
                'estado' => 'Completado',
                'observaciones' => 'Turno sintético para prueba.',
            ]);

            $asistencia = Asistencia::create([
                'trabajador_id' => $trabajador->id,
                'turno_id' => $turno->id,
                'fecha_hora_entrada' => '2026-10-20 07:55:00',
                'fecha_hora_salida' => '2026-10-20 20:02:00',
                'observaciones' => 'Marcaciones sintéticas.',
            ]);

            $turnoNoche = Turno::create([
                'trabajador_id' => $trabajador->id,
                'fecha_inicio' => '2026-10-23 20:00:00',
                'fecha_fin' => '2026-10-24 08:00:00',
                'tipo_turno' => 'Noche',
                'estado' => 'Programado',
            ]);

            $vacacion = Vacacion::create([
                'trabajador_id' => $trabajador->id,
                'fecha_inicio' => '2026-10-14',
                'fecha_fin' => '2026-10-16',
                'dias_habiles' => 3,
                'estado' => 'Aprobada',
                'observaciones' => 'Solicitud sintética.',
            ]);

            $licencia = LicenciaMedica::create([
                'trabajador_id' => $trabajador->id,
                'folio' => 'TEST-202610',
                'tipo' => 'Común',
                'entidad_emisora' => 'Fonasa',
                'fecha_inicio' => '2026-10-05',
                'fecha_fin' => '2026-10-08',
                'dias_reposo' => 4,
                'estado' => 'Pendiente',
                'observaciones' => 'Licencia completamente sintética.',
            ]);

            Ingreso::create([
                'fecha' => '2026-10-01',
                'categoria' => 'Demo',
                'descripcion' => 'Ingreso sintético',
                'monto' => 850000,
            ]);

            Gasto::create([
                'fecha' => '2026-10-02',
                'categoria' => 'Demo',
                'descripcion' => 'Gasto sintético',
                'monto' => 175000,
            ]);

            $liquidacion = app(RemuneracionService::class)
                ->calcularLiquidacion($trabajador, $hoy->month, $hoy->year, 30);
            $finanzas = app(FinanzasService::class);
            $finiquito = app(FiniquitoService::class)
                ->calcular($trabajador, 'Necesidades de la empresa Art. 161', $hoy);

            $this->assertSame('DEMO', $trabajador->nombres);
            $this->assertSame('NO ES PERSONA REAL', $trabajador->apellidos);
            $this->assertSame(750000, $contrato->sueldo_base);
            $this->assertSame(42, $contrato->horas_semanales);
            $this->assertSame(38000.0, (float) $parametro->valor_uf);
            $this->assertSame(67000, (int) $parametro->valor_utm);
            $this->assertSame(500000, $parametro->sueldo_minimo);
            $this->assertSame(84.3, (float) $parametro->tope_imponible_afp);
            $this->assertSame(60.0, (float) $parametro->tope_imponible_ips);
            $this->assertSame(126.6, (float) $parametro->tope_imponible_cesantia);
            $this->assertSame(1.49, (float) $parametro->porcentaje_sis);
            $this->assertSame(1, $trabajador->turnos()->where('estado', 'Completado')->count());
            $this->assertSame(1, $trabajador->turnos()->where('estado', 'Programado')->count());
            $this->assertSame('2026-10-23 20:00:00', $turnoNoche->fecha_inicio->format('Y-m-d H:i:s'));
            $this->assertSame('2026-10-24 08:00:00', $turnoNoche->fecha_fin->format('Y-m-d H:i:s'));
            $this->assertSame($turno->id, $asistencia->turno_id);
            $this->assertSame(3, $vacacion->dias_habiles);
            $this->assertSame(4, $licencia->dias_reposo);
            $this->assertSame(1, $licencia->dias_pagados);
            $this->assertSame(18750, $licencia->monto_subsidio);
            $this->assertSame(850000.0, $finanzas->totalIngresos());
            $this->assertSame(175000.0, $finanzas->totalGastos());
            $this->assertSame(675000.0, $finanzas->saldoDisponible());
            $this->assertSame(750000, $liquidacion->sueldo_base);
            $this->assertSame(187500, $liquidacion->gratificacion);
            $this->assertSame(937500, $liquidacion->total_haberes);
            $this->assertSame(767062, $liquidacion->sueldo_liquido);
            $this->assertSame($trabajador->id, $liquidacion->trabajador_id);
            $this->assertSame($contrato->id, $liquidacion->contrato_id);
            $this->assertGreaterThan(0, $finiquito['indemnizacion_anios']);
            $this->assertGreaterThan(0, $finiquito['liquido_pagar']);
        } finally {
            Carbon::setTestNow();
        }
    }
}
