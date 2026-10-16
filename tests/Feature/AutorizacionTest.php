<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use App\Models\Gasto;
use App\Models\Turno;
use App\Models\User;

class AutorizacionTest extends TestCase
{
    use RefreshDatabase;

    private int $contadorUsuario = 1;

    protected function setUp(): void
    {
        parent::setUp();

        // Los roles se crean igual que en RoleSeeder.
        foreach (['administrador', 'jefe de turno', 'contador', 'solo lectura'] as $rol) {
            Role::firstOrCreate(['name' => $rol]);
        }
    }

    private function crearUsuario(string $rol): User
    {
        $usuario = User::create([
            'email' => 'usuario' . $this->contadorUsuario . '@eleam.cl',
            'password' => 'password',
            'nombre' => 'Nombre',
            'apellido' => 'Apellido',
            'rut' => sprintf('19%07d-%d', $this->contadorUsuario, random_int(0, 9)),
        ]);
        $this->contadorUsuario++;

        $usuario->assignRole($rol);

        return $usuario;
    }

    public function test_usuario_sin_rol_no_puede_acceder_al_panel()
    {
        $usuario = User::create([
            'email' => 'sinrol@eleam.cl',
            'password' => 'password',
            'nombre' => 'Sin',
            'apellido' => 'Rol',
            'rut' => '19888888-8',
        ]);

        $this->actingAs($usuario);

        $this->get('/admin')->assertForbidden();
    }

    public function test_usuario_con_rol_puede_acceder_al_panel()
    {
        $usuario = $this->crearUsuario('jefe de turno');

        $this->actingAs($usuario);

        $this->get('/admin')->assertOk();
    }

    public function test_el_jefe_de_turno_puede_gestionar_turnos_pero_no_gastos()
    {
        $jefe = $this->crearUsuario('jefe de turno');

        $this->assertTrue($jefe->can('viewAny', Turno::class));
        $this->assertTrue($jefe->can('create', Turno::class));

        $this->assertFalse($jefe->can('create', Gasto::class));
    }

    public function test_el_contador_puede_gestionar_gastos_pero_no_turnos()
    {
        $contador = $this->crearUsuario('contador');

        $this->assertTrue($contador->can('viewAny', Gasto::class));
        $this->assertTrue($contador->can('create', Gasto::class));

        $this->assertFalse($contador->can('create', Turno::class));
    }

    public function test_el_usuario_de_solo_lectura_solo_puede_ver()
    {
        $soloLectura = $this->crearUsuario('solo lectura');

        $this->assertTrue($soloLectura->can('viewAny', Turno::class));
        $this->assertFalse($soloLectura->can('create', Turno::class));
        $this->assertFalse($soloLectura->can('create', Gasto::class));
    }

    public function test_el_administrador_puede_gestionar_todo()
    {
        $admin = $this->crearUsuario('administrador');

        $this->assertTrue($admin->can('viewAny', Turno::class));
        $this->assertTrue($admin->can('create', Turno::class));

        $this->assertTrue($admin->can('create', Gasto::class));
    }
}
