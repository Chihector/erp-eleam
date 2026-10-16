<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Gasto;

class GastoTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_a_gasto()
    {
        $gasto = Gasto::create([
            'fecha' => '2026-10-10',
            'categoria' => 'Luz',
            'monto' => 50000,
            'descripcion' => 'Boleta de luz de octubre'
        ]);

        $this->assertDatabaseHas('gastos', [
            'categoria' => 'Luz',
            'monto' => 50000,
            'descripcion' => 'Boleta de luz de octubre'
        ]);
    }

    public function test_requires_fields_in_database()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        // Esto fallará porque la base de datos requiere 'monto' y 'categoria'
        Gasto::create([
            'fecha' => '2026-10-10',
        ]);
    }

    public function test_can_update_a_gasto()
    {
        $gasto = Gasto::create([
            'fecha' => '2026-10-10',
            'categoria' => 'Luz',
            'monto' => 50000,
            'descripcion' => 'Boleta de luz de octubre'
        ]);

        $gasto->update([
            'monto' => 60000,
            'descripcion' => 'Boleta de luz corregida'
        ]);

        $this->assertDatabaseHas('gastos', [
            'id' => $gasto->id,
            'monto' => 60000,
            'descripcion' => 'Boleta de luz corregida'
        ]);
    }

    public function test_can_delete_a_gasto()
    {
        $gasto = Gasto::create([
            'fecha' => '2026-10-10',
            'categoria' => 'Luz',
            'monto' => 50000,
            'descripcion' => 'Boleta de luz de octubre'
        ]);

        $gasto->delete();

        $this->assertDatabaseMissing('gastos', ['id' => $gasto->id]);
    }
}
