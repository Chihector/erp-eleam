<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Asistencia;
use App\Models\Trabajador;

class AsistenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_asistencia()
    {
        $trabajador = Trabajador::create([
            'rut' => '12345678-5',
            'nombres' => 'Juan',
            'apellidos' => 'Perez',
            'cargo' => 'Enfermero',
            'fecha_ingreso' => '2020-01-01',
        ]);

        $asistencia = Asistencia::create([
            'trabajador_id' => $trabajador->id,
            'fecha_hora_entrada' => '2026-10-10 07:55:00',
            'observaciones' => 'Llegó a tiempo'
        ]);

        $this->assertDatabaseHas('asistencias', [
            'trabajador_id' => $trabajador->id,
            'observaciones' => 'Llegó a tiempo'
        ]);
    }

    public function test_can_register_entrada_and_salida_in_one_record()
    {
        $trabajador = Trabajador::create([
            'rut' => '12345678-5',
            'nombres' => 'Juan',
            'apellidos' => 'Perez',
            'cargo' => 'Enfermero',
            'fecha_ingreso' => '2020-01-01',
        ]);

        $asistencia = Asistencia::create([
            'trabajador_id' => $trabajador->id,
            'fecha_hora_entrada' => '2026-10-10 07:55:00',
            'fecha_hora_salida' => '2026-10-10 20:00:00',
            'observaciones' => 'Jornada completa'
        ]);

        $this->assertDatabaseHas('asistencias', [
            'id' => $asistencia->id,
            'fecha_hora_entrada' => '2026-10-10 07:55:00',
            'fecha_hora_salida' => '2026-10-10 20:00:00'
        ]);
    }

    public function test_can_delete_an_asistencia()
    {
        $trabajador = Trabajador::create([
            'rut' => '12345678-5',
            'nombres' => 'Juan',
            'apellidos' => 'Perez',
            'cargo' => 'Enfermero',
            'fecha_ingreso' => '2020-01-01',
        ]);

        $asistencia = Asistencia::create([
            'trabajador_id' => $trabajador->id,
            'fecha_hora_entrada' => '2026-10-10 07:55:00',
        ]);

        $asistencia->delete();

        $this->assertDatabaseMissing('asistencias', ['id' => $asistencia->id]);
    }
}
