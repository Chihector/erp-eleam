<?php

namespace Tests\Feature;

use App\Mail\ReporteNotificacionesMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ComandosMantenimientoTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_comandos_programados_existen(): void
    {
        $comandos = array_keys(Artisan::all());

        foreach ([
            'notificaciones:diarias',
            'backup:google-drive',
            'log:clear',
            'db:optimize',
            'reporte:notificaciones',
        ] as $comando) {
            $this->assertContains($comando, $comandos, "Falta el comando {$comando}");
        }
    }

    public function test_log_clear_elimina_solo_los_logs_antiguos(): void
    {
        $directorio = storage_path('framework/testing/logs-'.uniqid());
        File::ensureDirectoryExists($directorio);

        $viejo = $directorio.'/viejo.log';
        $nuevo = $directorio.'/nuevo.log';

        File::put($viejo, 'antiguo');
        File::put($nuevo, 'reciente');
        touch($viejo, now()->subDays(30)->getTimestamp());

        try {
            $this->artisan('log:clear', ['--days' => 14, '--path' => $directorio])
                ->assertSuccessful();

            $this->assertFileDoesNotExist($viejo);
            $this->assertFileExists($nuevo);
        } finally {
            File::deleteDirectory($directorio);
        }
    }

    public function test_db_optimize_se_ejecuta_sin_errores(): void
    {
        $this->artisan('db:optimize')->assertSuccessful();
    }

    public function test_backup_google_drive_permite_simular_sin_credenciales(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->markTestSkipped('El respaldo a Google Drive solo aplica a SQLite.');
        }

        $this->artisan('backup:google-drive', [
            '--database' => true,
            '--dry-run' => true,
        ])->assertSuccessful();
    }

    public function test_backup_completo_falla_si_la_base_no_es_sqlite(): void
    {
        $conexionOriginal = config('database.default');
        config(['database.default' => 'pgsql']);

        try {
            $this->artisan('backup:google-drive', [
                '--all' => true,
                '--dry-run' => true,
            ])->assertFailed();
        } finally {
            config(['database.default' => $conexionOriginal]);
        }
    }

    public function test_reporte_de_notificaciones_se_genera(): void
    {
        $this->artisan('reporte:notificaciones', ['--periodo' => 'semanal'])
            ->assertSuccessful();
    }

    public function test_reporte_de_notificaciones_envia_email_a_administradores(): void
    {
        Mail::fake();

        Role::firstOrCreate(['name' => 'administrador']);

        $admin = User::create([
            'email' => 'admin@eleam.cl',
            'password' => 'password',
            'nombre' => 'Administrador',
            'apellido' => 'Sistema',
            'rut' => '11111111-1',
        ]);
        $admin->assignRole('administrador');

        $this->artisan('reporte:notificaciones', [
            '--periodo' => 'semanal',
            '--email' => true,
        ])->assertSuccessful();

        Mail::assertSent(ReporteNotificacionesMail::class, function (ReporteNotificacionesMail $mail) use ($admin): bool {
            return $mail->hasTo($admin->email);
        });
    }

    public function test_reporte_de_notificaciones_rechaza_un_periodo_invalido(): void
    {
        $this->artisan('reporte:notificaciones', ['--periodo' => 'diario'])
            ->assertFailed();
    }
}
