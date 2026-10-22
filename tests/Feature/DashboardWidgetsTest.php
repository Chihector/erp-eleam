<?php

namespace Tests\Feature;

use App\Filament\Widgets\ActividadRecienteWidget;
use App\Filament\Widgets\EstadisticasGeneralesWidget;
use App\Filament\Widgets\FinanzasChartWidget;
use App\Filament\Widgets\ProximosTurnosWidget;
use App\Models\Contrato;
use App\Models\Finiquito;
use App\Models\Gasto;
use App\Models\Ingreso;
use App\Models\LicenciaMedica;
use App\Models\Trabajador;
use App\Models\Turno;
use App\Models\User;
use App\Models\Vacacion;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Renderiza cada widget del dashboard con datos reales para detectar
 * consultas SQL inválidas, columnas inexistentes o iconos faltantes.
 */
class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    private Trabajador $trabajador;

    private Contrato $contrato;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'administrador']);

        $admin = User::create([
            'email' => 'admin@eleam.cl',
            'password' => 'password',
            'nombre' => 'Administrador',
            'apellido' => 'Sistema',
            'rut' => '11111111-1',
        ]);
        $admin->assignRole('administrador');

        $this->actingAs($admin);

        $this->trabajador = Trabajador::create([
            'rut' => '12345678-9',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'cargo' => 'TENS',
            'afp' => 'Modelo',
            'sistema_salud' => 'Fonasa',
            'fecha_ingreso' => Carbon::today()->subYears(2),
            'color_calendario' => '#3b82f6',
        ]);

        $this->contrato = Contrato::create([
            'trabajador_id' => $this->trabajador->id,
            'tipo_contrato' => 'Indefinido',
            'sueldo_base' => 800000,
            'horas_semanales' => 44,
            'jornada_excepcional' => false,
            'fecha_inicio' => Carbon::today()->subYears(2),
            'fecha_termino' => Carbon::today()->addDays(10),
        ]);

        Turno::create([
            'trabajador_id' => $this->trabajador->id,
            'fecha_inicio' => Carbon::tomorrow()->setTime(8, 0),
            'fecha_fin' => Carbon::tomorrow()->setTime(20, 0),
            'tipo_turno' => 'Dia',
            'estado' => 'Programado',
        ]);

        LicenciaMedica::create([
            'trabajador_id' => $this->trabajador->id,
            'tipo' => 'Común',
            'entidad_emisora' => 'Fonasa',
            'fecha_inicio' => Carbon::today()->subDays(2),
            'fecha_fin' => Carbon::today()->addDays(3),
            'dias_reposo' => 6,
            'estado' => 'Validada',
        ]);

        Finiquito::create([
            'trabajador_id' => $this->trabajador->id,
            'fecha_termino' => Carbon::today(),
            'causal' => 'Renuncia Voluntaria',
            'estado' => 'Borrador',
            'total_haberes' => 500000,
        ]);

        Vacacion::create([
            'trabajador_id' => $this->trabajador->id,
            'fecha_inicio' => Carbon::today()->subDays(10),
            'fecha_fin' => Carbon::today()->subDays(6),
            'dias_habiles' => 5,
            'estado' => 'Aprobada',
        ]);

        Gasto::create([
            'fecha' => Carbon::today(),
            'categoria' => 'Insumos',
            'descripcion' => 'Guantes de examen',
            'monto' => 25000,
        ]);

        Ingreso::create([
            'fecha' => Carbon::today(),
            'categoria' => 'Mensualidad',
            'descripcion' => 'Pago residente',
            'monto' => 450000,
        ]);

        DB::table('liquidacions')->insert([
            'trabajador_id' => $this->trabajador->id,
            'contrato_id' => $this->contrato->id,
            'mes' => Carbon::today()->month,
            'anio' => Carbon::today()->year,
            'dias_trabajados' => 30,
            'sueldo_base' => 800000,
            'gratificacion' => 0,
            'total_imponible' => 800000,
            'total_haberes' => 800000,
            'afp_nombre' => 'Modelo',
            'afp_porcentaje' => 10,
            'monto_afp' => 80000,
            'salud_nombre' => 'Fonasa',
            'salud_porcentaje' => 7,
            'monto_salud' => 56000,
            'total_descuentos_legales' => 136000,
            'total_descuentos' => 136000,
            'sueldo_liquido' => 664000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_el_dashboard_carga_para_un_administrador(): void
    {
        $this->get('/admin')->assertOk();
    }

    public function test_el_widget_de_estadisticas_se_renderiza(): void
    {
        Livewire::test(EstadisticasGeneralesWidget::class)
            ->assertSee('Total Trabajadores')
            ->assertSee('Contratos por vencer')
            ->assertSee('vacaciones');
    }

    public function test_el_widget_de_finanzas_se_renderiza(): void
    {
        Livewire::test(FinanzasChartWidget::class)
            ->assertSee('Gastos vs Ingresos');
    }

    public function test_el_widget_de_actividad_reciente_se_renderiza(): void
    {
        Livewire::test(ActividadRecienteWidget::class)
            ->assertSee('Actividad Reciente')
            ->assertSee('Gasto: Guantes de examen')
            ->assertSee('Vacación')
            ->assertSee('Ana Pérez');
    }

    public function test_el_widget_de_proximos_turnos_se_renderiza(): void
    {
        Livewire::test(ProximosTurnosWidget::class)
            ->assertSee('Próximos Turnos')
            ->assertSee('Ana')
            ->assertSee('Dia');
    }
}
