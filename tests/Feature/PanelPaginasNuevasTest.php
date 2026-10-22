<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Verifica que las páginas de los nuevos módulos (Licencias Médicas y
 * Finiquitos) se rendericen sin errores en el panel de administración.
 */
class PanelPaginasNuevasTest extends TestCase
{
    use RefreshDatabase;

    private function crearAdministrador(): User
    {
        Role::firstOrCreate(['name' => 'administrador']);

        $usuario = User::create([
            'email' => 'admin@eleam.cl',
            'password' => 'password',
            'nombre' => 'Administrador',
            'apellido' => 'Sistema',
            'rut' => '11111111-1',
        ]);

        $usuario->assignRole('administrador');

        return $usuario;
    }

    public function test_las_paginas_de_licencias_medicas_se_renderizan()
    {
        $this->actingAs($this->crearAdministrador());

        $this->get('/admin/licencia-medicas')->assertOk();
        $this->get('/admin/licencia-medicas/create')->assertOk();
    }

    public function test_las_paginas_de_finiquitos_se_renderizan()
    {
        $this->actingAs($this->crearAdministrador());

        $this->get('/admin/finiquitos')->assertOk();
        $this->get('/admin/finiquitos/create')->assertOk();
    }

    public function test_las_paginas_de_liquidaciones_se_renderizan()
    {
        $this->actingAs($this->crearAdministrador());

        $this->get('/admin/liquidacions')->assertOk();
        $this->get('/admin/liquidacions/create')->assertOk();
    }

    public function test_la_edicion_de_una_liquidacion_cerrada_queda_bloqueada()
    {
        $administrador = $this->crearAdministrador();
        $this->actingAs($administrador);

        $trabajador = \App\Models\Trabajador::create([
            'rut' => '12345678-9',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'cargo' => 'Enfermera',
            'afp' => 'Modelo',
            'sistema_salud' => 'Fonasa',
            'fecha_ingreso' => '2024-01-01',
        ]);

        $contrato = \App\Models\Contrato::create([
            'trabajador_id' => $trabajador->id,
            'sueldo_base' => 500000,
            'tipo_contrato' => 'Indefinido',
            'horas_semanales' => 45,
            'fecha_inicio' => '2024-01-01',
        ]);

        $liquidacion = \App\Models\Liquidacion::create([
            'trabajador_id' => $trabajador->id,
            'contrato_id' => $contrato->id,
            'mes' => 10,
            'anio' => 2026,
            'dias_trabajados' => 30,
            'sueldo_base' => 500000,
            'gratificacion' => 125000,
            'total_imponible' => 625000,
            'total_haberes' => 625000,
            'afp_nombre' => 'Modelo',
            'afp_porcentaje' => 10.58,
            'monto_afp' => 66125,
            'salud_nombre' => 'Fonasa',
            'salud_porcentaje' => 7,
            'monto_salud' => 43750,
            'total_descuentos_legales' => 113625,
            'total_descuentos' => 113625,
            'sueldo_liquido' => 511375,
            'cerrada' => true,
        ]);

        $this->get("/admin/liquidacions/{$liquidacion->id}/edit")->assertForbidden();
    }
}
