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
}
