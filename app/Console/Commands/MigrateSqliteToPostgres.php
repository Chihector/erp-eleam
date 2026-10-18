<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Migra los datos existentes desde la base SQLite antigua hacia PostgreSQL.
 *
 * Uso dentro del contenedor de producción (una sola vez):
 *   docker compose exec app php artisan eleam:migrate-sqlite
 *
 * Opciones:
 *   --source=ruta   Ruta del archivo SQLite (por defecto database/database.sqlite)
 *   --force         Ejecuta aunque PostgreSQL ya contenga datos
 */
class MigrateSqliteToPostgres extends Command
{
    protected $signature = 'eleam:migrate-sqlite
                            {--source= : Ruta al archivo SQLite antiguo}
                            {--force : Forzar aunque el destino ya tenga datos}';

    protected $description = 'Copia los datos desde la base SQLite antigua hacia PostgreSQL (uso único al migrar).';

    /**
     * Tablas en orden de dependencias (padres antes que hijos).
     *
     * @var list<string>
     */
    private const TABLAS = [
        'users',
        'trabajadores',
        'contratos',
        'turnos',
        'vacaciones',
        'asistencias',
        'gastos',
        'ingresos',
        'liquidacions',
        'parametro_legals',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'audits',
    ];

    /**
     * Columnas que en SQLite se guardan como 0/1 y en PostgreSQL son booleanas.
     *
     * @var array<string, list<string>>
     */
    private const COLUMNAS_BOOLEANAS = [
        'contratos' => ['jornada_excepcional'],
        'liquidacions' => ['cerrada'],
    ];

    /**
     * Tablas que no se copian: runtime, caché o control de migraciones.
     *
     * @var list<string>
     */
    private const TABLAS_OMITIDAS = [
        'migrations',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'sessions',
        'password_reset_tokens',
    ];

    public function handle(): int
    {
        $destino = DB::connection()->getDriverName();

        if ($destino !== 'pgsql') {
            $this->error("La conexión por defecto es '{$destino}', pero se esperaba 'pgsql'.");
            $this->line('Configure DB_CONNECTION=pgsql en .env.production antes de migrar.');

            return self::FAILURE;
        }

        $rutaSqlite = $this->option('source') ?: database_path('database.sqlite');

        if (! is_file($rutaSqlite)) {
            $this->error("No se encontró el archivo SQLite en: {$rutaSqlite}");
            $this->line('Use --source=ruta/al/archivo.sqlite para indicar otra ubicación.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║  Migración de datos: SQLite → PostgreSQL (ERP ELEAM)      ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->line("  Origen : {$rutaSqlite}");
        $this->line('  Destino: '.$destino.' ('.DB::connection()->getDatabaseName().')');
        $this->newLine();

        // Evita copias accidentales sobre datos ya existentes.
        if (! $this->option('force') && $this->destinoTieneDatos()) {
            $this->error('PostgreSQL ya contiene datos (tabla users con registros).');
            $this->line('Use --force solo si desea copiar de todas formas (los IDs duplicados se omiten).');

            return self::FAILURE;
        }

        $this->registrarConexionSqlite($rutaSqlite);

        $totales = 0;

        foreach (self::TABLAS as $tabla) {
            $copiadas = $this->copiarTabla($tabla);
            $totales += $copiadas;

            if ($copiadas > 0) {
                $this->line(sprintf('  ✔ %-22s %5d registros copiados', $tabla, $copiadas));
            } else {
                $this->line(sprintf('  · %-22s sin registros nuevos', $tabla));
            }
        }

        $this->sincronizarSecuencias();

        $this->newLine();
        $this->info("Migración completada: {$totales} registros copiados en total.");
        $this->line('Verifique con: docker compose exec app php artisan tinker');

        return self::SUCCESS;
    }

    private function copiarTabla(string $tabla): int
    {
        // La conexión antigua no tiene la tabla (versión previa del sistema)
        if (! $this->existeTabla($tabla, 'sqlite_import')) {
            return 0;
        }

        // El destino tampoco (¿migraciones pendientes?)
        if (! $this->existeTabla($tabla, 'pgsql')) {
            $this->warn("  ⚠ La tabla '{$tabla}' no existe en PostgreSQL; ¿faltan migraciones?");

            return 0;
        }

        $columnas = Schema::connection('pgsql')->getColumnListing($tabla);
        $booleanas = self::COLUMNAS_BOOLEANAS[$tabla] ?? [];
        $copiadas = 0;

        $filas = DB::connection('sqlite_import')->table($tabla)->get();

        foreach ($filas as $fila) {
            $datos = [];

            foreach ($columnas as $columna) {
                $valor = data_get($fila, $columna, null);

                if (in_array($columna, $booleanas, true) && $valor !== null) {
                    $valor = (bool) $valor;
                }

                $datos[$columna] = $valor;
            }

            try {
                $insertadas = DB::connection('pgsql')
                    ->table($tabla)
                    ->insertOrIgnore([$datos]);

                $copiadas += $insertadas;
            } catch (Throwable $e) {
                $this->warn("  ⚠ Error copiando en '{$tabla}' (ID {$fila->id}): {$e->getMessage()}");
            }
        }

        return $copiadas;
    }

    /**
     * Ajusta las secuencias de PostgreSQL para que los nuevos registros
     * continúen después de los IDs migrados.
     */
    private function sincronizarSecuencias(): void
    {
        foreach (self::TABLAS as $tabla) {
            if (! Schema::connection('pgsql')->hasTable($tabla)) {
                continue;
            }

            try {
                DB::connection('pgsql')->statement(
                    "SELECT setval(pg_get_serial_sequence('{$tabla}', 'id'),
                                   COALESCE((SELECT MAX(id) FROM {$tabla}), 1),
                                   (SELECT COUNT(*) FROM {$tabla}) > 0)"
                );
            } catch (Throwable $e) {
                $this->warn("  ⚠ No se pudo sincronizar la secuencia de '{$tabla}': {$e->getMessage()}");
            }
        }
    }

    private function destinoTieneDatos(): bool
    {
        try {
            return Schema::connection('pgsql')->hasTable('users')
                && DB::connection('pgsql')->table('users')->exists();
        } catch (Throwable) {
            return false;
        }
    }

    private function existeTabla(string $tabla, string $conexion): bool
    {
        try {
            return Schema::connection($conexion)->hasTable($tabla);
        } catch (Throwable) {
            return false;
        }
    }

    private function registrarConexionSqlite(string $ruta): void
    {
        config()->set('database.connections.sqlite_import', [
            'driver' => 'sqlite',
            'database' => $ruta,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        // Limpia cualquier conexión previa para forzar la relectura del archivo.
        DB::purge('sqlite_import');
    }
}
