<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Vacacion;
use App\Models\Trabajador;

class VacacionTest extends TestCase
{
    use RefreshDatabase;

    private function crearTrabajador(string $rut, string $fechaIngreso): Trabajador
    {
        return Trabajador::create([
            'rut' => $rut,
            'nombres' => 'Juan',
            'apellidos' => 'Perez',
            'cargo' => 'Enfermero',
            'fecha_ingreso' => $fechaIngreso,
        ]);
    }

    public function test_saldo_vacaciones_se_acumula_segun_meses_trabajados()
    {
        // Un trabajador ingresado hace exactamente 12 meses genera 12 * 1.25 = 15 días.
        $trabajador = $this->crearTrabajador(
            '12345678-5',
            now()->subMonthsNoOverflow(12)->format('Y-m-d')
        );

        // diffInMonths devuelve fracciones de mes, por eso se tolera el redondeo.
        $this->assertEqualsWithDelta(15.0, $trabajador->saldo_vacaciones, 0.1);
    }

    public function test_solo_cuentan_vacaciones_aprobadas()
    {
        $trabajador = $this->crearTrabajador(
            '12345678-5',
            now()->subMonthsNoOverflow(12)->format('Y-m-d')
        );

        // Una solicitud aprobada descuenta días del saldo.
        Vacacion::create([
            'trabajador_id' => $trabajador->id,
            'fecha_inicio' => '2026-01-05',
            'fecha_fin' => '2026-01-09',
            'dias_habiles' => 5,
            'estado' => 'Aprobada',
        ]);

        // Las solicitudes pendientes y rechazadas no descuentan el saldo.
        Vacacion::create([
            'trabajador_id' => $trabajador->id,
            'fecha_inicio' => '2026-02-01',
            'fecha_fin' => '2026-02-04',
            'dias_habiles' => 4,
            'estado' => 'Pendiente',
        ]);

        Vacacion::create([
            'trabajador_id' => $trabajador->id,
            'fecha_inicio' => '2026-03-01',
            'fecha_fin' => '2026-03-03',
            'dias_habiles' => 3,
            'estado' => 'Rechazada',
        ]);

        // 15 días ganados menos los 5 aprobados (15 - 5 = 10).
        $this->assertEqualsWithDelta(10.0, $trabajador->fresh()->saldo_vacaciones, 0.1);
    }

    public function test_puede_crear_solicitud_de_vacaciones()
    {
        $trabajador = $this->crearTrabajador(
            '12345678-5',
            now()->subMonthsNoOverflow(12)->format('Y-m-d')
        );

        $vacacion = Vacacion::create([
            'trabajador_id' => $trabajador->id,
            'fecha_inicio' => '2026-01-05',
            'fecha_fin' => '2026-01-09',
            'dias_habiles' => 5,
            'estado' => 'Pendiente',
        ]);

        $this->assertDatabaseHas('vacaciones', [
            'id' => $vacacion->id,
            'trabajador_id' => $trabajador->id,
            'dias_habiles' => 5,
        ]);
    }
}
