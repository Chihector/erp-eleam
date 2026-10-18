<?php

namespace Tests\Feature;

use App\Filament\Resources\Trabajadors\Pages\ListTrabajadors;
use App\Models\Trabajador;
use App\Models\User;
use App\Support\Buscador;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Búsqueda tolerante a tildes y mayúsculas: escribir "hector" debe
 * encontrar "Héctor", "munoz" a "Muñoz", y el RUT con o sin formato.
 */
class BusquedaSinAcentosTest extends TestCase
{
    use RefreshDatabase;

    private Trabajador $hector;

    private Trabajador $maria;

    private Trabajador $rosa;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');

        $this->hector = $this->crearTrabajador('19486694-1', 'Héctor Andrés', 'Contreras Soto');
        $this->maria = $this->crearTrabajador('22222222-2', 'María Fernanda', 'González Pérez');
        $this->rosa = $this->crearTrabajador('11111111-1', 'Rosa Elena', 'Muñoz Silva');
    }

    private function crearTrabajador(string $rut, string $nombres, string $apellidos): Trabajador
    {
        return Trabajador::create([
            'rut' => $rut,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'cargo' => 'Cuidadora',
            'afp' => 'modelo',
            'sistema_salud' => 'fonasa',
            'fecha_ingreso' => '2024-01-15',
        ]);
    }

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

    public function test_normaliza_texto_quitando_tildes_y_mayusculas()
    {
        $this->assertEquals('hector andres', Buscador::normalizar('Héctor Andrés'));
        $this->assertEquals('munoz perez', Buscador::normalizar('MUÑOZ PÉREZ'));
        $this->assertEquals('dia', Buscador::normalizar('Día'));
        $this->assertEquals('gutierrez', Buscador::normalizar('Gutiérrez'));
    }

    public function test_normaliza_el_rut_quitando_puntos_y_guion()
    {
        $this->assertEquals('194866941', Buscador::normalizar('19.486.694-1'));
        $this->assertEquals('194866941', Buscador::normalizar('19486694-1'));
        $this->assertEquals('19486694', Buscador::normalizar('19.486.694'));
    }

    public function test_el_buscador_de_trabajadores_encuentra_sin_tildes()
    {
        // "hector" encuentra a "Héctor" (y también con tilde o mayúsculas).
        $this->assertArrayHasKey($this->hector->id, Buscador::trabajadores('hector'));
        $this->assertArrayHasKey($this->hector->id, Buscador::trabajadores('HÉCTOR'));
        $this->assertArrayHasKey($this->hector->id, Buscador::trabajadores('Héctor'));

        // "munoz" encuentra a "Muñoz" y "perez" a "Pérez".
        $this->assertArrayHasKey($this->rosa->id, Buscador::trabajadores('munoz'));
        $this->assertArrayHasKey($this->maria->id, Buscador::trabajadores('perez'));

        // Búsqueda sin coincidencias.
        $this->assertSame([], Buscador::trabajadores('zzzz'));
    }

    public function test_el_buscador_encuentra_por_rut_con_o_sin_formato()
    {
        $this->assertArrayHasKey($this->hector->id, Buscador::trabajadores('19486694'));
        $this->assertArrayHasKey($this->hector->id, Buscador::trabajadores('19.486.694-1'));
        $this->assertArrayHasKey($this->hector->id, Buscador::trabajadores('19486694-1'));
    }

    public function test_sin_termino_devuelve_todos_ordenados_por_apellido()
    {
        $resultados = Buscador::trabajadores();

        // Contreras (Héctor), González (María), Muñoz (Rosa).
        $this->assertSame(
            [$this->hector->id, $this->maria->id, $this->rosa->id],
            array_keys($resultados),
        );

        $this->assertEquals(
            '19486694-1 - Héctor Andrés Contreras Soto',
            Buscador::etiqueta($this->hector),
        );
    }

    public function test_la_expresion_sql_normalizada_encuentra_sin_tildes()
    {
        $porNombre = Trabajador::query()
            ->whereRaw(Buscador::sqlNormalizado('nombres').' LIKE ?', ['%hector%'])
            ->pluck('id');

        $this->assertTrue($porNombre->contains($this->hector->id));

        $porApellido = Trabajador::query()
            ->whereRaw(Buscador::sqlNormalizado('apellidos').' LIKE ?', ['%munoz%'])
            ->pluck('id');

        $this->assertTrue($porApellido->contains($this->rosa->id));
        $this->assertFalse($porApellido->contains($this->maria->id));

        $porRut = Trabajador::query()
            ->whereRaw(Buscador::sqlNormalizado('rut').' LIKE ?', ['%19486694%'])
            ->pluck('id');

        $this->assertTrue($porRut->contains($this->hector->id));
    }

    public function test_la_tabla_de_trabajadores_encuentra_sin_tildes()
    {
        $this->actingAs($this->crearAdministrador());

        Livewire::test(ListTrabajadors::class)
            ->searchTable('hector')
            ->assertCanSeeTableRecords([$this->hector])
            ->assertCanNotSeeTableRecords([$this->maria, $this->rosa]);

        Livewire::test(ListTrabajadors::class)
            ->searchTable('munoz')
            ->assertCanSeeTableRecords([$this->rosa])
            ->assertCanNotSeeTableRecords([$this->hector, $this->maria]);
    }
}
