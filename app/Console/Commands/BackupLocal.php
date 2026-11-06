<?php

namespace App\Console\Commands;

use App\Services\RespaldoBaseDatos;
use App\Support\EnvSanitizer;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackupLocal extends Command
{
    protected $signature = 'eleam:backup-local
                            {--destino= : Carpeta destino (por defecto storage/app/backups)}
                            {--retencion= : Días de retención; 0 conserva todos los respaldos}
                            {--sin-storage : No incluir storage/app}
                            {--sin-env : No incluir el .env sanitizado}
                            {--dry-run : Muestra el plan sin escribir archivos}';

    protected $description = 'Genera un respaldo local de la base de datos, storage/app y .env sanitizado';

    public function __construct(private readonly RespaldoBaseDatos $respaldo)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $destinoBase = $this->option('destino')
            ?: config('backup.local.path', storage_path('app/backups'));
        $destinoBase = $this->rutaAbsoluta((string) $destinoBase);
        $retencion = $this->option('retencion');
        $retencion = $retencion === null
            ? (int) config('backup.local.retention_days', 30)
            : (int) $retencion;

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $carpeta = $destinoBase.DIRECTORY_SEPARATOR.$timestamp;

        $this->info('🗂️  Respaldo local del ERP ELEAM'.($dryRun ? ' [MODO PRUEBA]' : ''));
        $this->line("   Destino: {$carpeta}");
        $this->line('   Motor de base de datos: '.$this->respaldo->driver());

        if ($dryRun) {
            $this->line('   [DRY-RUN] Se respaldarían: base de datos, '
                .($this->option('sin-storage') ? '' : 'storage/app, ')
                .($this->option('sin-env') ? '' : '.env sanitizado'));

            return Command::SUCCESS;
        }

        if (! class_exists(\ZipArchive::class)) {
            $this->error('La extensión PHP zip no está habilitada; no se puede respaldar storage/app.');

            return Command::FAILURE;
        }

        try {
            File::ensureDirectoryExists($carpeta);

            $archivos = [];

            // 1. Base de datos con snapshot consistente (SQLite o PostgreSQL).
            $extension = $this->respaldo->driver() === 'pgsql' ? 'dump' : 'sqlite';
            $rutaDb = $carpeta.DIRECTORY_SEPARATOR."database.{$extension}";
            $this->respaldo->crearRespaldo($rutaDb);
            $archivos['database'] = $this->describirArchivo($rutaDb);

            // 2. storage/app (adjuntos, reportes, logs), sin incluir el propio
            //    respaldo si la carpeta destino vive dentro de storage/app.
            if (! $this->option('sin-storage')) {
                $rutaStorage = $carpeta.DIRECTORY_SEPARATOR.'storage.zip';
                $this->crearZipStorage($rutaStorage, $destinoBase);
                $archivos['storage'] = $this->describirArchivo($rutaStorage);
            }

            // 3. .env sanitizado (sin claves, contraseñas ni tokens).
            if (! $this->option('sin-env')) {
                $envPath = base_path('.env');

                if (File::exists($envPath)) {
                    $rutaEnv = $carpeta.DIRECTORY_SEPARATOR.'env-sanitized.txt';
                    File::put($rutaEnv, EnvSanitizer::sanitizar(File::get($envPath)));
                    $archivos['env'] = $this->describirArchivo($rutaEnv);
                }
            }

            $manifest = [
                'generado_en' => Carbon::now()->toIso8601String(),
                'driver' => $this->respaldo->driver(),
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'archivos' => $archivos,
            ];

            File::put(
                $carpeta.DIRECTORY_SEPARATOR.'manifest.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            );

            $this->limpiarRespaldosAntiguos($destinoBase, $retencion);

            Log::channel('backup')->info('Respaldo local completado', [
                'carpeta' => $carpeta,
                'driver' => $this->respaldo->driver(),
                'archivos' => array_keys($archivos),
            ]);

            $this->newLine();
            $this->info("✅ Respaldo local creado en: {$carpeta}");
            $this->line('   Archivos: '.implode(', ', array_keys($archivos)));

            if ($retencion > 0) {
                $this->line("   Retención aplicada: {$retencion} días.");
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            // No dejar una carpeta a medias que parezca un respaldo válido.
            if (File::isDirectory($carpeta)) {
                File::deleteDirectory($carpeta);
            }

            Log::channel('backup')->error('Falló el respaldo local', [
                'carpeta' => $carpeta,
                'error' => $e->getMessage(),
            ]);

            $this->error('❌ No se pudo crear el respaldo: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function crearZipStorage(string $destino, string $destinoBase): void
    {
        $origen = storage_path('app');

        if (! File::isDirectory($origen)) {
            throw new \RuntimeException('No existe la carpeta storage/app.');
        }

        if (File::exists($destino)) {
            File::delete($destino);
        }

        $zip = new \ZipArchive;

        if ($zip->open($destino, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("No se pudo crear el ZIP: {$destino}");
        }

        $origenReal = realpath($origen) ?: $origen;
        $destinoReal = realpath($destinoBase) ?: $destinoBase;

        try {
            $archivos = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($origen, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY,
            );

            foreach ($archivos as $archivo) {
                if (! $archivo->isFile()) {
                    continue;
                }

                $ruta = $archivo->getRealPath();

                // Evitar que el respaldo se incluya a sí mismo cuando el
                // destino está dentro de storage/app.
                if (str_starts_with($ruta, $destinoReal.DIRECTORY_SEPARATOR)
                    || $ruta === $destinoReal) {
                    continue;
                }

                $relativa = str_replace('\\', '/', substr($ruta, strlen($origenReal) + 1));
                $zip->addFile($ruta, $relativa);
            }
        } finally {
            $zip->close();
        }

        $this->verificarArchivo($destino);
    }

    private function limpiarRespaldosAntiguos(string $destinoBase, int $retencionDias): void
    {
        if ($retencionDias <= 0 || ! File::isDirectory($destinoBase)) {
            return;
        }

        $limite = Carbon::now()->subDays($retencionDias);

        foreach (File::directories($destinoBase) as $carpeta) {
            $nombre = basename($carpeta);

            if (preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', $nombre) !== 1) {
                continue;
            }

            $manifestPath = $carpeta.DIRECTORY_SEPARATOR.'manifest.json';

            if (! File::exists($manifestPath)) {
                continue;
            }

            try {
                $manifest = json_decode(File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
                $generado = Carbon::parse($manifest['generado_en'] ?? $nombre);

                if ($generado->lt($limite)) {
                    File::deleteDirectory($carpeta);
                }
            } catch (\Throwable) {
                // Un respaldo con manifiesto ilegible no se borra automáticamente.
            }
        }
    }

    /**
     * @return array{nombre: string, bytes: int, sha256: string}
     */
    private function describirArchivo(string $ruta): array
    {
        $this->verificarArchivo($ruta);

        return [
            'nombre' => basename($ruta),
            'bytes' => File::size($ruta),
            'sha256' => hash_file('sha256', $ruta),
        ];
    }

    private function verificarArchivo(string $ruta): void
    {
        if (! File::exists($ruta) || File::size($ruta) === 0) {
            throw new \RuntimeException('El archivo de respaldo quedó vacío: '.$ruta);
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
