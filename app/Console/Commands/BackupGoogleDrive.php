<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackupGoogleDrive extends Command
{
    protected $signature = 'backup:google-drive
                            {--database : Solo respaldar la base de datos SQLite}
                            {--storage : Solo respaldar storage/ (logs, uploads, etc.)}
                            {--all : Respaldar todo (BD + storage + .env)}
                            {--dry-run : Simular sin subir a Drive}
                            {--folder-id= : ID de la carpeta en Google Drive (se configura en .env: GOOGLE_DRIVE_BACKUP_FOLDER_ID)}';

    protected $description = 'Realiza backup diario a Google Drive (base de datos SQLite, storage, .env)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $soloDb = $this->option('database');
        $soloStorage = $this->option('storage');
        $todo = $this->option('all');
        $folderId = $this->option('folder-id') ?: config('backup.google_drive.folder_id');

        if (! $folderId && ! $dryRun) {
            $this->error('❌ Falta GOOGLE_DRIVE_BACKUP_FOLDER_ID en .env o config/backup.php');
            $this->line('   Configura la variable de entorno o usa --folder-id=ID_CARPETA');

            return Command::FAILURE;
        }

        if ($dryRun && ! $folderId) {
            $folderId = 'DRY-RUN-FOLDER-ID';
        }

        $this->info('🗂️  Iniciando backup a Google Drive...'.($dryRun ? ' [MODO PRUEBA]' : ''));
        $this->info("📁 Carpeta destino: {$folderId}");

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $archivosASubir = [];

        // 1. Base de datos SQLite
        if ($todo || $soloDb || (! $soloDb && ! $soloStorage)) {
            $dbPath = database_path('database.sqlite');
            if (File::exists($dbPath)) {
                $destino = "backup_{$timestamp}_database.sqlite";
                $archivosASubir[] = [
                    'origen' => $dbPath,
                    'destino' => $destino,
                    'tipo' => 'database',
                    'descripcion' => 'Base de datos SQLite completa',
                ];
            } else {
                $this->warn("Base de datos no encontrada en: {$dbPath}");
            }
        }

        // 2. Carpeta storage (logs, uploads, etc.)
        if ($todo || $soloStorage) {
            $storagePath = storage_path('app');
            if (File::isDirectory($storagePath)) {
                // El ZIP se crea fuera de storage/app para no incluirse a sí mismo.
                $zipPath = storage_path("framework/backup_{$timestamp}_storage.zip");
                $this->crearZip($storagePath, $zipPath);
                $archivosASubir[] = [
                    'origen' => $zipPath,
                    'destino' => "backup_{$timestamp}_storage.zip",
                    'tipo' => 'storage',
                    'descripcion' => 'Carpeta storage/app (logs, uploads, backups locales)',
                ];
            }
        }

        // 3. Archivo .env (solo si --all)
        if ($todo) {
            $envPath = base_path('.env');
            if (File::exists($envPath)) {
                // Copiar y sanitizar (quitar secrets)
                $envSanitizado = storage_path("app/backup_{$timestamp}_env.txt");
                $contenido = File::get($envPath);
                $contenidoSanitizado = $this->sanitizarEnv($contenido);
                File::put($envSanitizado, $contenidoSanitizado);

                $archivosASubir[] = [
                    'origen' => $envSanitizado,
                    'destino' => "backup_{$timestamp}_env.txt",
                    'tipo' => 'config',
                    'descripcion' => 'Configuración .env (sin secrets)',
                ];
            }
        }

        if (empty($archivosASubir)) {
            $this->warn('No hay archivos para respaldar.');

            return Command::SUCCESS;
        }

        // Subir a Google Drive
        $subidos = 0;
        foreach ($archivosASubir as $archivo) {
            if ($this->subirAGoogleDrive($archivo['origen'], $archivo['destino'], $folderId, $dryRun)) {
                $subidos++;
                $this->line("  ✅ {$archivo['descripcion']} → {$archivo['destino']}");
            } else {
                $this->error("  ❌ Error subiendo {$archivo['destino']}");
            }
        }

        // Aplicar retención de backups antiguos en Drive.
        if (! $dryRun && $subidos > 0) {
            $this->limpiarBackupsAntiguos($folderId);
        }

        // Limpiar temporales
        foreach ($archivosASubir as $archivo) {
            if (str_contains($archivo['origen'], 'backup_') && File::exists($archivo['origen'])) {
                File::delete($archivo['origen']);
            }
        }

        // Log
        Log::channel('backup')->info('Backup Google Drive completado', [
            'archivos' => $subidos,
            'total' => count($archivosASubir),
            'timestamp' => $timestamp,
            'dry_run' => $dryRun,
        ]);

        $this->info("🎉 Backup completado: {$subidos}/".count($archivosASubir).' archivos subidos.');

        return $subidos === count($archivosASubir) ? Command::SUCCESS : Command::FAILURE;
    }

    private function crearZip(string $origen, string $destino): void
    {
        $zip = new \ZipArchive;
        if ($zip->open($destino, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("No se pudo crear ZIP: {$destino}");
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($origen),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (! $file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($origen) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
    }

    private function sanitizarEnv(string $contenido): string
    {
        $lineas = explode("\n", $contenido);
        $palabrasClave = ['KEY', 'SECRET', 'PASSWORD', 'TOKEN', 'PRIVATE'];

        foreach ($lineas as $i => $linea) {
            if (str_starts_with(trim($linea), '#') || ! str_contains($linea, '=')) {
                continue;
            }
            [$clave] = explode('=', $linea, 2);
            $clave = strtoupper(trim($clave));
            foreach ($palabrasClave as $palabra) {
                if (str_contains($clave, $palabra)) {
                    $lineas[$i] = "{$clave}=***REDACTADO***";
                    break;
                }
            }
        }

        return implode("\n", $lineas);
    }

    private function subirAGoogleDrive(string $archivoLocal, string $nombreRemoto, string $folderId, bool $dryRun): bool
    {
        if ($dryRun) {
            $this->line("  [DRY-RUN] Subiría: {$archivoLocal} → {$nombreRemoto}");

            return true;
        }

        // Crear cliente autenticado (valida SDK y credenciales).
        $client = $this->crearClienteDrive();

        if (! $client) {
            return false;
        }

        try {
            $service = new Drive($client);
            $fileMetadata = new DriveFile([
                'name' => $nombreRemoto,
                'parents' => [$folderId],
            ]);

            $content = File::get($archivoLocal);
            $file = $service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $this->obtenerMimeType($archivoLocal),
                'uploadType' => 'multipart',
            ]);

            $this->line("  📤 Subido a Drive: {$file->id} ({$nombreRemoto})");

            return true;

        } catch (\Throwable $e) {
            $this->error("  ❌ Error Google Drive: {$e->getMessage()}");
            Log::error('Error subiendo backup a Google Drive', [
                'archivo' => $archivoLocal,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Crea un cliente de Google autenticado con la cuenta de servicio.
     *
     * Devuelve null si falta el SDK o el archivo de credenciales, para que el
     * respaldo falle de forma controlada sin lanzar una excepción.
     */
    private function crearClienteDrive(): ?Client
    {
        if (! class_exists(Client::class)) {
            $this->error('  ❌ Google Client SDK no instalado. Ejecuta: composer require google/apiclient:^2.0');

            return null;
        }

        $credentialsPath = config('backup.google_drive.credentials_path', base_path('google-drive-credentials.json'));

        if (! File::exists($credentialsPath)) {
            $this->error("  ❌ Falta el archivo de credenciales: {$credentialsPath}");
            $this->line('     Descárgalo de Google Cloud Console > Credenciales > Cuenta de servicio');

            return null;
        }

        $client = new Client;
        $client->setApplicationName('ERP ELEAM Backup');
        $client->setScopes([Drive::DRIVE_FILE]);
        $client->setAccessType('offline');

        try {
            $client->setAuthConfig($credentialsPath);
        } catch (\Throwable $e) {
            $this->error("  ❌ Credenciales de Google inválidas: {$e->getMessage()}");

            return null;
        }

        return $client;
    }

    /**
     * Elimina de la carpeta de Drive los backups más antiguos que la retención.
     *
     * Solo considera archivos creados por este comando (`backup_`) y nunca
     * falla el respaldo completo si la limpieza no se puede realizar.
     */
    private function limpiarBackupsAntiguos(string $folderId): void
    {
        $dias = (int) config('backup.google_drive.retention_days', 0);

        if ($dias <= 0) {
            return;
        }

        try {
            $client = $this->crearClienteDrive();

            if (! $client) {
                return;
            }

            $service = new Drive($client);
            $limite = Carbon::now()->subDays($dias);

            $resultado = $service->files->listFiles([
                'q' => "'{$folderId}' in parents and trashed = false and name contains 'backup_'",
                'fields' => 'files(id,name,createdTime)',
                'pageSize' => 1000,
            ]);

            foreach ($resultado->getFiles() as $archivo) {
                $creado = Carbon::parse($archivo->getCreatedTime());

                if ($creado->lt($limite)) {
                    $service->files->delete($archivo->getId());
                    $this->line("  🗑️  Backup antiguo eliminado: {$archivo->getName()}");
                }
            }
        } catch (\Throwable $e) {
            $this->warn('No se pudieron limpiar backups antiguos: '.$e->getMessage());
            Log::channel('backup')->warning('Error limpiando backups antiguos en Google Drive', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function obtenerMimeType(string $archivo): string
    {
        $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));

        return match ($ext) {
            'sqlite' => 'application/x-sqlite3',
            'zip' => 'application/zip',
            'txt' => 'text/plain',
            default => 'application/octet-stream',
        };
    }
}
