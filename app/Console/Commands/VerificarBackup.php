<?php

namespace App\Console\Commands;

use App\Services\RespaldoBaseDatos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class VerificarBackup extends Command
{
    protected $signature = 'eleam:verificar-backup
                            {archivo : Carpeta de respaldo o archivo .sqlite/.dump}
                            {--storage= : ZIP de storage/app si no está junto al respaldo}';

    protected $description = 'Verifica un respaldo restaurándolo en una base temporal y revisando sus archivos';

    public function __construct(private readonly RespaldoBaseDatos $respaldo)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $ruta = $this->rutaAbsoluta((string) $this->argument('archivo'));

        if (! File::exists($ruta)) {
            $this->error('No existe el respaldo indicado: '.$ruta);

            return Command::FAILURE;
        }

        $carpeta = File::isDirectory($ruta) ? $ruta : dirname($ruta);
        $manifest = $this->leerManifest($carpeta);

        try {
            $archivoDb = $this->resolverBase($ruta, $manifest);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $archivoStorage = $this->option('storage')
            ? $this->rutaAbsoluta((string) $this->option('storage'))
            : $this->resolverStorage($carpeta, $manifest);

        $this->info('🔎 Verificando respaldo...');
        $this->line("   Base de datos: {$archivoDb}");

        $errores = $this->verificarHashes($carpeta, $manifest);

        if (str_ends_with(strtolower($archivoDb), '.dump')) {
            $errores = array_merge($errores, $this->verificarPostgres($archivoDb));
        } else {
            $errores = array_merge($errores, $this->verificarSqlite($archivoDb));
        }

        if ($archivoStorage !== null) {
            $this->line("   Storage: {$archivoStorage}");
            $errores = array_merge($errores, $this->verificarZip($archivoStorage));
        } else {
            $this->warn('   No se encontró un ZIP de storage; solo se verificó la base.');
        }

        $this->newLine();

        if ($errores !== []) {
            foreach ($errores as $error) {
                $this->error('❌ '.$error);
            }

            return Command::FAILURE;
        }

        $this->info('✅ Respaldo verificado correctamente.');

        return Command::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function verificarHashes(string $carpeta, ?array $manifest): array
    {
        if ($manifest === null || ! isset($manifest['archivos'])) {
            return [];
        }

        $errores = [];

        foreach ($manifest['archivos'] as $tipo => $datos) {
            $ruta = $carpeta.DIRECTORY_SEPARATOR.($datos['nombre'] ?? '');

            if (! File::exists($ruta)) {
                $errores[] = "Falta el archivo de {$tipo}: {$ruta}";

                continue;
            }

            if (isset($datos['sha256'])) {
                $hash = hash_file('sha256', $ruta);

                if (! hash_equals((string) $datos['sha256'], (string) $hash)) {
                    $errores[] = "El hash de {$tipo} no coincide; el archivo pudo corromperse.";
                }
            }
        }

        return $errores;
    }

    /**
     * @return list<string>
     */
    private function verificarSqlite(string $archivo): array
    {
        $errores = [];

        try {
            $pdo = new \PDO('sqlite:'.$archivo, null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);

            $integridad = $pdo->query('PRAGMA integrity_check')->fetchColumn();

            if ($integridad !== 'ok') {
                $errores[] = 'SQLite reportó un problema de integridad: '.$integridad;

                return $errores;
            }

            $tablas = (int) $pdo->query(
                "select count(*) from sqlite_master where type = 'table' and name not like 'sqlite_%'"
            )->fetchColumn();

            $migraciones = $this->contarMigraciones($pdo);

            $this->line("   Tablas: {$tablas}; migraciones: {$migraciones}");

            if ($tablas < 10) {
                $errores[] = "El respaldo contiene muy pocas tablas ({$tablas}); parece incompleto.";
            }

            $origen = config('database.connections.'.config('database.default'), []);

            if (($origen['driver'] ?? null) === 'sqlite' && ($origen['database'] ?? null) !== ':memory:') {
                $this->compararConBaseActual($tablas, $migraciones, $errores);
            }
        } catch (\Throwable $e) {
            $errores[] = 'No se pudo abrir el respaldo SQLite: '.$e->getMessage();
        }

        return $errores;
    }

    /**
     * Restaura el dump en una base temporal, compara el esquema y la elimina.
     *
     * @return list<string>
     */
    private function verificarPostgres(string $archivo): array
    {
        $errores = [];
        $pgRestore = $this->respaldo->rutaPgRestore();

        if ($pgRestore === null) {
            return ['No se encontró pg_restore para verificar el respaldo PostgreSQL.'];
        }

        $conexion = $this->respaldo->conexionPostgres();
        $baseTemporal = 'eleam_verifica_'.Str::lower(Str::random(8));

        config([
            'database.connections.pgsql_verify_admin' => $conexion,
            'database.connections.pgsql_verify' => array_merge($conexion, ['database' => $baseTemporal]),
        ]);

        DB::purge('pgsql_verify_admin');
        DB::purge('pgsql_verify');

        try {
            DB::connection('pgsql_verify_admin')->statement('CREATE DATABASE "'.$baseTemporal.'"');

            $resultado = Process::env($this->respaldo->entornoPostgres($conexion))
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

            if ($resultado->failed()) {
                return ['pg_restore falló: '.trim($resultado->errorOutput())];
            }

            $tablas = (int) DB::connection('pgsql_verify')
                ->selectOne("select count(*) as total from information_schema.tables where table_schema = 'public'")
                ->total;
            $migraciones = (int) DB::connection('pgsql_verify')->table('migrations')->count();

            $this->line("   Restaurado en base temporal: {$tablas} tablas; {$migraciones} migraciones.");

            if ($tablas < 10) {
                $errores[] = "El respaldo PostgreSQL contiene muy pocas tablas ({$tablas}).";
            }

            $this->compararConBaseActual($tablas, $migraciones, $errores);
        } catch (\Throwable $e) {
            $errores[] = 'No se pudo verificar el respaldo PostgreSQL: '.$e->getMessage();
        } finally {
            DB::purge('pgsql_verify');

            try {
                DB::connection('pgsql_verify_admin')->statement('DROP DATABASE IF EXISTS "'.$baseTemporal.'"');
            } catch (\Throwable) {
                $errores[] = "No se pudo eliminar la base temporal {$baseTemporal}.";
            }

            DB::purge('pgsql_verify_admin');
        }

        return $errores;
    }

    /**
     * @param  list<string>  $errores
     */
    private function compararConBaseActual(int $tablas, int $migraciones, array &$errores): void
    {
        try {
            $tablasActuales = (int) DB::selectOne(
                "select count(*) as total from information_schema.tables where table_schema = 'public'"
            )->total ?? 0;
        } catch (\Throwable) {
            // SQLite no usa information_schema; el conteo se hace aparte.
            $tablasActuales = (int) DB::selectOne(
                "select count(*) as total from sqlite_master where type = 'table' and name not like 'sqlite_%'"
            )->total ?? 0;
        }

        try {
            $migracionesActuales = (int) DB::table('migrations')->count();
        } catch (\Throwable) {
            return;
        }

        if ($tablas > 0 && $tablasActuales > 0 && $tablas !== $tablasActuales) {
            $errores[] = "El respaldo tiene {$tablas} tablas y la base actual {$tablasActuales}.";
        }

        if ($migraciones !== $migracionesActuales) {
            $errores[] = "El respaldo tiene {$migraciones} migraciones y la base actual {$migracionesActuales}.";
        }
    }

    private function contarMigraciones(\PDO $pdo): int
    {
        try {
            return (int) $pdo->query('select count(*) from migrations')->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return list<string>
     */
    private function verificarZip(string $archivo): array
    {
        if (! File::exists($archivo)) {
            return ['No existe el ZIP de storage: '.$archivo];
        }

        if (! class_exists(\ZipArchive::class)) {
            return ['La extensión PHP zip no está habilitada.'];
        }

        $zip = new \ZipArchive;

        if ($zip->open($archivo) !== true) {
            return ['No se pudo abrir el ZIP de storage.'];
        }

        $total = $zip->numFiles;

        // Leer el primer archivo fuerza la comprobación de CRC del ZIP.
        if ($total > 0 && $zip->getFromIndex(0) === false) {
            $zip->close();

            return ['El ZIP de storage está corrupto.'];
        }

        $zip->close();

        $this->line("   Archivos en storage.zip: {$total}");

        if ($total === 0) {
            return ['El ZIP de storage está vacío.'];
        }

        return [];
    }

    private function resolverBase(string $ruta, ?array $manifest): string
    {
        if (! File::isDirectory($ruta)) {
            return $ruta;
        }

        $nombre = $manifest['archivos']['database']['nombre'] ?? null;

        if (is_string($nombre) && File::exists($ruta.DIRECTORY_SEPARATOR.$nombre)) {
            return $ruta.DIRECTORY_SEPARATOR.$nombre;
        }

        foreach (['database.sqlite', 'database.dump'] as $candidato) {
            if (File::exists($ruta.DIRECTORY_SEPARATOR.$candidato)) {
                return $ruta.DIRECTORY_SEPARATOR.$candidato;
            }
        }

        throw new \RuntimeException('No se encontró la base de datos dentro del respaldo: '.$ruta);
    }

    private function resolverStorage(string $carpeta, ?array $manifest): ?string
    {
        $nombre = $manifest['archivos']['storage']['nombre'] ?? 'storage.zip';
        $ruta = $carpeta.DIRECTORY_SEPARATOR.$nombre;

        return File::exists($ruta) ? $ruta : null;
    }

    private function leerManifest(string $carpeta): ?array
    {
        $ruta = $carpeta.DIRECTORY_SEPARATOR.'manifest.json';

        if (! File::exists($ruta)) {
            return null;
        }

        try {
            return json_decode(File::get($ruta), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }
    }

    private function rutaAbsoluta(string $ruta): string
    {
        $esAbsoluta = str_starts_with($ruta, '/')
            || str_starts_with($ruta, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $ruta) === 1;

        return $esAbsoluta ? $ruta : base_path($ruta);
    }
}
