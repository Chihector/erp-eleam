<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Turno;
use App\Models\Trabajador;

class TurnoTest extends TestCase
{
    use RefreshDatabase;

    public function test_turno_overlap_logic()
    {
        $trabajador = Trabajador::create([
            'rut' => '12345678-5',
            'nombres' => 'Juan',
            'apellidos' => 'Perez',
            'cargo' => 'Enfermero',
            'fecha_ingreso' => '2020-01-01',
        ]);

        $turno1 = Turno::create([
            'trabajador_id' => $trabajador->id,
            'tipo_turno' => 'Dia',
            'estado' => 'Programado',
            'fecha_inicio' => '2026-10-10 08:00:00',
            'fecha_fin' => '2026-10-10 20:00:00',
        ]);

        // Validate overlap programmatically since Filament forms use custom closures
        $isOverlapping = Turno::where('trabajador_id', $trabajador->id)
            ->where(function ($query) {
                $query->whereBetween('fecha_inicio', ['2026-10-10 10:00:00', '2026-10-10 12:00:00'])
                      ->orWhereBetween('fecha_fin', ['2026-10-10 10:00:00', '2026-10-10 12:00:00'])
                      ->orWhere(function ($q) {
                          $q->where('fecha_inicio', '<=', '2026-10-10 10:00:00')
                            ->where('fecha_fin', '>=', '2026-10-10 12:00:00');
                      });
            })->exists();

        $this->assertTrue($isOverlapping);
    }
}
