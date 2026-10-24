<?php

namespace Tests\Feature;

use App\Services\RespaldoBaseDatos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Ensayo de respaldo/restauración de PostgreSQL: genera un pg_dump real, lo
 * restaura en una base temporal y verifica que el esquema quedó completo.
 */
class RespaldoPostgresTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_respaldo_pg_dump_se_restaura_en_una_base_temporal(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('El ensayo de pg_dump solo aplica a PostgreSQL.');
        }

        $servicio = app(RespaldoBaseDatos::class);
        $pgDump = $servicio->rutaPgDump();
        $pgRestore = $servicio->rutaPgRestore();

        if ($pgDump === null || $pgRestore === null) {
            $this->markTestSkipped('Faltan pg_dump y pg_restore en el sistema.');
        }

        $conexion = $servicio->conexionPostgres();
        $entorno = $servicio->entornoPostgres($conexion);
        $archivo = tempnam(sys_get_temp_dir(), 'eleam_dump_');
        $baseTemporal = 'eleam_restore_'.Str::lower(Str::random(8));

        config([
            'database.connections.pgsql_admin' => array_merge($conexion, ['database' => 'postgres']),
            'database.connections.pgsql_restore' => array_merge($conexion, ['database' => $baseTemporal]),
        ]);

        DB::purge('pgsql_admin');
        DB::purge('pgsql_restore');

        try {
            $servicio->volcarPostgres($archivo);

            $this->assertFileExists($archivo);
            $this->assertGreaterThan(0, File::size($archivo));

            DB::connection('pgsql_admin')->statement('CREATE DATABASE "'.$baseTemporal.'"');

            $resultado = Process::env($entorno)
                ->timeout(120)
                ->run([
                    $pgRestore,
                    '--clean',
                    '--if-exists',
                    '--no-owner',
                    '--no-privileges',
                    '--no-password',
                    '--host='.$conexion['host'],
                    '--port='.$conexion['port'],
                    '--username='.$conexion['username'],
                    '--dbname='.$baseTemporal,
                    $archivo,
                ]);

            $this->assertTrue(
                $resultado->successful(),
                'pg_restore falló: '.trim($resultado->errorOutput())
            );

            $tablasOrigen = (int) DB::connection('pgsql')
                ->selectOne("select count(*) as total from information_schema.tables where table_schema = 'public'")
                ->total;
            $tablasRestauradas = (int) DB::connection('pgsql_restore')
                ->selectOne("select count(*) as total from information_schema.tables where table_schema = 'public'")
                ->total;

            $this->assertGreaterThan(10, $tablasRestauradas);
            $this->assertSame($tablasOrigen, $tablasRestauradas);
            $this->assertSame(
                DB::connection('pgsql')->table('migrations')->count(),
                DB::connection('pgsql_restore')->table('migrations')->count(),
            );
        } finally {
            DB::purge('pgsql_restore');
            DB::purge('pgsql_admin');

            try {
                DB::connection('pgsql_admin')->statement('DROP DATABASE IF EXISTS "'.$baseTemporal.'"');
            } catch (\Throwable) {
                // La limpieza no debe ocultar el resultado del ensayo.
            }

            DB::purge('pgsql_admin');
            File::delete($archivo);
        }
    }
}
