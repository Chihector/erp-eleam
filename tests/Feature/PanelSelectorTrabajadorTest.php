<?php

namespace Tests\Feature;

use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Verifica que las páginas que usan el selector de trabajador con búsqueda
 * sin tildes se rendericen sin errores y con datos cargados.
 */
class PanelSelectorTrabajadorTest extends TestCase
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

    public function test_las_paginas_con_selector_de_trabajador_se_renderizan()
    {
        $this->actingAs($this->crearAdministrador());

        Trabajador::create([
            'rut' => '19486694-1',
            'nombres' => 'Héctor Andrés',
            'apellidos' => 'Contreras Soto',
            'cargo' => 'Cuidador',
            'afp' => 'modelo',
            'sistema_salud' => 'fonasa',
            'fecha_ingreso' => '2024-01-15',
        ]);

        $paginas = [
            '/admin/turnos/create',
            '/admin/asistencias/create',
            '/admin/contratos/create',
            '/admin/vacacions/create',
            '/admin/liquidacions/create',
            '/admin/licencia-medicas/create',
            '/admin/finiquitos/create',
        ];

        foreach ($paginas as $pagina) {
            $this->get($pagina)->assertOk();
        }
    }
}
