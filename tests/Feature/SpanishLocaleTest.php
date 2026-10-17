<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SpanishLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_and_validation_use_spanish(): void
    {
        $this->assertSame('es', app()->getLocale());

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Correo Electrónico o RUT')
            ->assertSee('Contraseña')
            ->assertSee('Entrar al Sistema');

        $this->assertSame(
            'El campo correo electrónico es obligatorio.',
            validator(['email' => ''], ['email' => 'required'])->errors()->first('email'),
        );

        $this->get('/')
            ->assertOk()
            ->assertSee('Bienvenido a ERP ELEAM')
            ->assertSee('Iniciar sesión')
            ->assertDontSee("Let's get started");
    }

    public function test_panel_and_welcome_page_show_spanish_content(): void
    {
        $role = Role::create(['name' => 'administrador']);
        $user = User::create([
            'email' => 'admin@eleam.cl',
            'password' => 'password',
            'nombre' => 'Administración',
            'apellido' => 'ERP ELEAM',
            'rut' => '11111111-1',
        ]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Escritorio')
            ->assertSee('Trabajadores')
            ->assertSee('Salir');
    }
}
