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
}
