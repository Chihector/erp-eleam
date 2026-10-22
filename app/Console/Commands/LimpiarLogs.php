<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LimpiarLogs extends Command
{
    protected $signature = 'log:clear
                            {--days=14 : Días de retención de los archivos de log}
                            {--path= : Directorio de logs (por defecto storage/logs)}';

    protected $description = 'Elimina los archivos de log más antiguos que el período de retención.';

    public function handle(): int
    {
        $dias = max(0, (int) $this->option('days'));
        $directorio = (string) ($this->option('path') ?: storage_path('logs'));

        if (! File::isDirectory($directorio)) {
            $this->warn("El directorio de logs no existe: {$directorio}");

            return self::SUCCESS;
        }

        $limite = Carbon::now()->subDays($dias)->getTimestamp();
        $eliminados = 0;

        foreach (File::glob($directorio.'/*.log') as $archivo) {
            if (File::lastModified($archivo) >= $limite) {
                continue;
            }

            if (File::delete($archivo)) {
                $eliminados++;
                $this->line('Eliminado: '.basename($archivo));
            }
        }

        $this->info("Logs eliminados: {$eliminados}");

        return self::SUCCESS;
    }
}
