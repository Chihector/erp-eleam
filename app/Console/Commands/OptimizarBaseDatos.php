<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class OptimizarBaseDatos extends Command
{
    protected $signature = 'db:optimize';

    protected $description = 'Optimiza la base de datos según el motor configurado.';

    public function handle(): int
    {
        $driver = DB::connection()->getDriverName();

        $this->info("Optimizando base de datos ({$driver})...");

        try {
            if ($driver === 'sqlite') {
                $this->optimizarSqlite();
            } elseif ($driver === 'pgsql') {
                $this->optimizarPostgres();
            } else {
                $this->warn("El motor '{$driver}' no tiene una rutina de optimización implementada.");

                return self::SUCCESS;
            }
        } catch (Throwable $e) {
            $this->error('No se pudo optimizar la base de datos: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Optimización completada.');

        return self::SUCCESS;
    }

    private function optimizarSqlite(): void
    {
        if (DB::transactionLevel() === 0) {
            DB::statement('VACUUM');
            $this->line('VACUUM ejecutado.');
        } else {
            $this->warn('VACUUM omitido porque hay una transacción activa.');
        }

        DB::statement('ANALYZE');
        $this->line('ANALYZE ejecutado.');
    }

    private function optimizarPostgres(): void
    {
        if (DB::transactionLevel() === 0) {
            DB::statement('VACUUM ANALYZE');
            $this->line('VACUUM ANALYZE ejecutado.');

            return;
        }

        DB::statement('ANALYZE');
        $this->line('ANALYZE ejecutado (VACUUM omitido por transacción activa).');
    }
}
