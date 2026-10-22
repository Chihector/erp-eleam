<?php

namespace Tests\Feature;

use App\Models\Contrato;
use App\Models\LicenciaMedica;
use App\Models\Trabajador;
use App\Models\User;
use App\Models\Vacacion;
use App\Notifications\ContratoPorVencerNotification;
use App\Notifications\LicenciaMedicaAlertaNotification;
use App\Notifications\VacacionesPendientesNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Verifica que el comando diario detecte contratos, vacaciones y licencias
 * y guarde las notificaciones sin errores de columnas o relaciones.
 */
class NotificacionesDiariasTest extends TestCase
{
    use RefreshDatabase;

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

        $trabajador = Trabajador::create([
            'rut' => '12345678-9',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'cargo' => 'TENS',
            'afp' => 'Modelo',
            'sistema_salud' => 'Fonasa',
            'fecha_ingreso' => Carbon::today()->subYears(2),
            'color_calendario' => '#3b82f6',
        ]);

        // Contrato que vence justo en el hito de 7 días.
        Contrato::create([
            'trabajador_id' => $trabajador->id,
            'tipo_contrato' => 'Plazo fijo',
            'sueldo_base' => 800000,
            'horas_semanales' => 44,
            'jornada_excepcional' => false,
            'fecha_inicio' => Carbon::today()->subMonths(6),
            'fecha_termino' => Carbon::today()->addDays(7),
        ]);

        // Licencia que vence en 3 días.
        LicenciaMedica::create([
            'trabajador_id' => $trabajador->id,
            'tipo' => 'Común',
            'entidad_emisora' => 'Fonasa',
            'fecha_inicio' => Carbon::today()->subDays(3),
            'fecha_fin' => Carbon::today()->addDays(3),
            'dias_reposo' => 7,
            'estado' => 'Validada',
        ]);

        Vacacion::create([
            'trabajador_id' => $trabajador->id,
            'fecha_inicio' => Carbon::today()->subDays(20),
            'fecha_fin' => Carbon::today()->subDays(16),
            'dias_habiles' => 5,
            'estado' => 'Aprobada',
        ]);
    }

    public function test_detecta_las_tres_categorias_y_guarda_notificaciones(): void
    {
        $this->artisan('notificaciones:diarias')
            ->expectsOutputToContain('Notificaciones procesadas: 3')
            ->assertSuccessful();

        $this->assertDatabaseCount('notifications', 3);

        $tipos = DB::table('notifications')->pluck('type')->all();

        $this->assertContains(ContratoPorVencerNotification::class, $tipos);
        $this->assertContains(VacacionesPendientesNotification::class, $tipos);
        $this->assertContains(LicenciaMedicaAlertaNotification::class, $tipos);
    }

    public function test_el_modo_dry_run_no_guarda_notificaciones(): void
    {
        $this->artisan('notificaciones:diarias', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseCount('notifications', 0);
    }
}
