<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Ingreso;

class IngresoTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_ingreso()
    {
        $ingreso = Ingreso::create([
            'fecha' => '2026-10-15',
            'categoria' => 'Subsidio',
            'monto' => 1500000,
            'descripcion' => 'Aporte SENAMA'
        ]);

        $this->assertDatabaseHas('ingresos', [
            'categoria' => 'Subsidio',
            'monto' => 1500000
        ]);
    }

    public function test_can_update_an_ingreso()
    {
        $ingreso = Ingreso::create([
            'fecha' => '2026-10-15',
            'categoria' => 'Subsidio',
            'monto' => 1500000,
            'descripcion' => 'Aporte SENAMA'
        ]);

        $ingreso->update([
            'monto' => 1200000,
            'descripcion' => 'Aporte SENAMA corregido'
        ]);

        $this->assertDatabaseHas('ingresos', [
            'id' => $ingreso->id,
            'monto' => 1200000,
            'descripcion' => 'Aporte SENAMA corregido'
        ]);
    }

    public function test_can_delete_an_ingreso()
    {
        $ingreso = Ingreso::create([
            'fecha' => '2026-10-15',
            'categoria' => 'Subsidio',
            'monto' => 1500000,
            'descripcion' => 'Aporte SENAMA'
        ]);

        $ingreso->delete();

        $this->assertDatabaseMissing('ingresos', ['id' => $ingreso->id]);
    }
}
