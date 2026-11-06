<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupLocalTest extends TestCase
{
    // VACUUM INTO no puede ejecutarse dentro de una transacción. La
    // truncación deja la base limpia entre pruebas sin envolver el test.
    use DatabaseTruncation;

    private string $destino;

    protected function setUp(): void
    {
        // Cada prueba crea una aplicación nueva y, con SQLite :memory:, una
        // base nueva. DatabaseTruncation reutiliza el estado estático de otras
        // clases y dejaría esta conexión vacía; forzamos su migración.
        RefreshDatabaseState::$migrated = false;

        parent::setUp();

        $this->destino = storage_path('framework/testing/backups-'.uniqid());
        File::ensureDirectoryExists($this->destino);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->destino);

        parent::tearDown();
    }

    public function test_genera_un_respaldo_local_y_lo_verifica(): void
    {
        $exit = Artisan::call('eleam:backup-local', [
            '--destino' => $this->destino,
            '--retencion' => 0,
        ]);

        $this->assertSame(0, $exit, Artisan::output());

        $carpetas = File::directories($this->destino);
        $this->assertCount(1, $carpetas);

        $carpeta = $carpetas[0];
        $driver = DB::connection()->getDriverName();
        $extension = $driver === 'pgsql' ? 'dump' : 'sqlite';

        $this->assertFileExists($carpeta.DIRECTORY_SEPARATOR.'manifest.json');
        $this->assertFileExists($carpeta.DIRECTORY_SEPARATOR."database.{$extension}");
        $this->assertFileExists($carpeta.DIRECTORY_SEPARATOR.'storage.zip');

        $manifest = json_decode(File::get($carpeta.DIRECTORY_SEPARATOR.'manifest.json'), true);

        $this->assertSame($driver, $manifest['driver']);
        $this->assertArrayHasKey('database', $manifest['archivos']);
        $this->assertArrayHasKey('storage', $manifest['archivos']);
        $this->assertNotEmpty($manifest['archivos']['database']['sha256']);

        $exitVerificacion = Artisan::call('eleam:verificar-backup', ['archivo' => $carpeta]);

        $this->assertSame(0, $exitVerificacion, Artisan::output());
    }

    public function test_dry_run_no_escribe_ningun_archivo(): void
    {
        $this->artisan('eleam:backup-local', [
            '--destino' => $this->destino,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertSame([], File::directories($this->destino));
    }

    public function test_la_verificacion_detecta_un_hash_manipulado(): void
    {
        $this->artisan('eleam:backup-local', [
            '--destino' => $this->destino,
            '--retencion' => 0,
            '--sin-storage' => true,
            '--sin-env' => true,
        ])->assertSuccessful();

        $carpeta = File::directories($this->destino)[0];
        $manifestPath = $carpeta.DIRECTORY_SEPARATOR.'manifest.json';
        $manifest = json_decode(File::get($manifestPath), true);
        $manifest['archivos']['database']['sha256'] = str_repeat('0', 64);
        File::put($manifestPath, json_encode($manifest));

        $this->artisan('eleam:verificar-backup', ['archivo' => $carpeta])
            ->assertFailed();
    }

    public function test_aplica_la_retencion_a_respaldos_antiguos(): void
    {
        $vieja = $this->destino.DIRECTORY_SEPARATOR.'2020-01-01_00-00-00';
        File::ensureDirectoryExists($vieja);
        File::put(
            $vieja.DIRECTORY_SEPARATOR.'manifest.json',
            json_encode(['generado_en' => '2020-01-01T00:00:00+00:00']),
        );

        $this->artisan('eleam:backup-local', [
            '--destino' => $this->destino,
            '--retencion' => 1,
            '--sin-storage' => true,
            '--sin-env' => true,
        ])->assertSuccessful();

        $this->assertDirectoryDoesNotExist($vieja);
    }
}
