<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Genera respaldos consistentes de la base de datos activa.
 *
 * SQLite se copia con VACUUM INTO para incluir las transacciones del WAL.
 * PostgreSQL se vuelca con pg_dump en formato custom, restaurable con
 * pg_restore. El binario se puede definir con PG_DUMP_PATH.
 */
class RespaldoBaseDatos
{
    public function driver(): string
    {
        return DB::connection()->getDriverName();
    }

    public function crearRespaldo(string $destino): void
    {
        match ($this->driver()) {
            'sqlite' => $this->crearSnapshotSqlite($destino),
            'pgsql' => $this->volcarPostgres($destino),
            default => throw new \RuntimeException(
                'Motor de base de datos no soportado para respaldo: '.$this->driver()
            ),
        };
    }

    /**
     * Copia consistente de SQLite. Copiar el archivo principal directamente
     * puede omitir transacciones que todavía están en el WAL.
     */
    public function crearSnapshotSqlite(string $destino): void
    {
        $origen = $this->rutaSqlite();

        if ($origen === null) {
            throw new \RuntimeException('No hay una base SQLite configurada para respaldar.');
        }

        // VACUUM INTO también funciona sobre una base SQLite en memoria, que
        // es la que usa la suite de pruebas.
        if ($origen !== ':memory:' && ! File::exists($origen)) {
            throw new \RuntimeException('Base de datos SQLite no encontrada: '.$origen);
        }

        if (File::exists($destino)) {
            File::delete($destino);
        }

        $ruta = str_replace("'", "''", $destino);
        DB::connection()->getPdo()->exec("VACUUM INTO '{$ruta}'");

        $this->verificarArchivo($destino);
    }

    /**
     * Vuelca PostgreSQL en formato custom. La contraseña viaja por entorno,
     * nunca como argumento visible en la lista de procesos.
     */
    public function volcarPostgres(string $destino): void
    {
        $pgDump = $this->rutaPgDump();

        if ($pgDump === null) {
            throw new \RuntimeException(
                'No se encontró pg_dump. Instala el cliente de PostgreSQL o define PG_DUMP_PATH.'
            );
        }

        if (File::exists($destino)) {
            File::delete($destino);
        }

        $conexion = $this->conexionPostgres();
        $comando = [
            $pgDump,
            '--format=custom',
            '--no-owner',
            '--no-privileges',
            '--no-password',
            '--file='.$destino,
        ];

        foreach ([
            'host' => '--host=',
            'port' => '--port=',
            'username' => '--username=',
            'database' => '--dbname=',
        ] as $clave => $opcion) {
            if (! empty($conexion[$clave])) {
                $comando[] = $opcion.$conexion[$clave];
            }
        }

        $resultado = Process::env($this->entornoPostgres($conexion))
            ->timeout((int) config('backup.pgsql.timeout', 300))
            ->run($comando);

        if ($resultado->failed()) {
            File::delete($destino);

            $detalle = trim($resultado->errorOutput()) ?: trim($resultado->output());

            throw new \RuntimeException(
                'pg_dump falló'.($detalle !== '' ? ': '.$detalle : '.')
            );
        }

        $this->verificarArchivo($destino);
    }

    public function rutaPgDump(): ?string
    {
        return $this->buscarBinario('pg_dump');
    }

    public function rutaPgRestore(): ?string
    {
        return $this->buscarBinario('pg_restore');
    }

    public function rutaPsql(): ?string
    {
        return $this->buscarBinario('psql');
    }

    /**
     * @return array<string, mixed>
     */
    public function conexionPostgres(): array
    {
        $nombre = config('database.default');

        return config("database.connections.{$nombre}", []);
    }

    /**
     * @param  array<string, mixed>  $conexion
     * @return array<string, string>
     */
    public function entornoPostgres(array $conexion): array
    {
        $entorno = ['PGCONNECT_TIMEOUT' => '10'];

        if (! empty($conexion['password'])) {
            $entorno['PGPASSWORD'] = (string) $conexion['password'];
        }

        if (! empty($conexion['sslmode'])) {
            $entorno['PGSSLMODE'] = (string) $conexion['sslmode'];
        }

        return $entorno;
    }

    private function buscarBinario(string $nombre): ?string
    {
        $configurado = config("backup.pgsql.{$nombre}_path");

        if (is_string($configurado) && $configurado !== '') {
            return $configurado;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $candidatos = glob("C:\\Program Files\\PostgreSQL\\*\\bin\\{$nombre}.exe") ?: [];
            usort($candidatos, fn (string $a, string $b): int => strnatcmp($b, $a));

            if ($candidatos !== []) {
                return $candidatos[0];
            }
        }

        // En Linux/macOS se resuelve desde PATH. Se comprueba antes para poder
        // avisar con un mensaje claro en vez de fallar a mitad del respaldo.
        try {
            if (Process::timeout(5)->run([$nombre, '--version'])->successful()) {
                return $nombre;
            }
        } catch (\Throwable) {
            // El binario no existe o no es ejecutable.
        }

        return null;
    }

    private function rutaSqlite(): ?string
    {
        $conexion = config('database.connections.'.config('database.default'), []);
        $ruta = $conexion['database'] ?? null;

        if (! is_string($ruta) || trim($ruta) === '') {
            return null;
        }

        if ($ruta === ':memory:') {
            return ':memory:';
        }

        $esAbsoluta = str_starts_with($ruta, '/')
            || str_starts_with($ruta, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $ruta) === 1;

        return $esAbsoluta ? $ruta : base_path($ruta);
    }

    private function verificarArchivo(string $destino): void
    {
        if (! File::exists($destino) || File::size($destino) === 0) {
            File::delete($destino);

            throw new \RuntimeException('El respaldo quedó vacío: '.$destino);
        }
    }
}
