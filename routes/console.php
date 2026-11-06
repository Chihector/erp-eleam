<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tareas programadas
|--------------------------------------------------------------------------
|
| En local se ejecutan con: php artisan schedule:work
| En producción deben quedar conectadas al cron del servidor.
|
*/

// Notificaciones diarias: cada día a las 08:00.
Schedule::command('notificaciones:diarias')
    ->dailyAt('08:00')
    ->timezone('America/Santiago')
    ->withoutOverlapping()
    ->description('Envía notificaciones: contratos por vencer, vacaciones pendientes y licencias médicas')
    ->onSuccess(function () {
        Log::channel('scheduler')->info('Notificaciones diarias enviadas correctamente');
    })
    ->onFailure(function () {
        Log::channel('scheduler')->error('Error enviando notificaciones diarias');
    });

// Backup a Google Drive: cada día a las 02:00, solo si está configurado.
// Funciona tanto con SQLite como con PostgreSQL (pg_dump).
Schedule::command('backup:google-drive --all')
    ->dailyAt('02:00')
    ->timezone('America/Santiago')
    ->withoutOverlapping()
    ->when(fn (): bool => in_array(config('database.default'), ['sqlite', 'pgsql'], true)
        && filled(config('backup.google_drive.folder_id'))
        && is_file((string) config('backup.google_drive.credentials_path')))
    ->description('Backup completo a Google Drive (base de datos, storage y .env sanitizado)')
    ->onSuccess(function () {
        Log::channel('scheduler')->info('Backup Google Drive completado');
    })
    ->onFailure(function () {
        Log::channel('scheduler')->error('Error en backup Google Drive');
    });

// Respaldo local: cada día a las 02:30. Sirve para la instalación de
// escritorio y para servidores sin Google Drive configurado.
Schedule::command('eleam:backup-local')
    ->dailyAt('02:30')
    ->timezone('America/Santiago')
    ->withoutOverlapping()
    ->when(fn (): bool => (bool) config('backup.local.enabled'))
    ->description('Respaldo local de base de datos, storage/app y .env sanitizado')
    ->onSuccess(function () {
        Log::channel('scheduler')->info('Respaldo local completado');
    })
    ->onFailure(function () {
        Log::channel('scheduler')->error('Error en respaldo local');
    });

// Limpieza de logs antiguos: cada domingo a las 03:00.
Schedule::command('log:clear')
    ->weeklyOn(0, '03:00')
    ->timezone('America/Santiago')
    ->withoutOverlapping()
    ->description('Elimina logs antiguos (14 días por defecto)');

// Optimización de la base de datos: el día 1 de cada mes a las 04:00.
Schedule::command('db:optimize')
    ->monthlyOn(1, '04:00')
    ->timezone('America/Santiago')
    ->withoutOverlapping()
    ->description('Optimiza la base de datos (VACUUM/ANALYZE)');

// Reporte semanal de notificaciones: cada lunes a las 07:00.
Schedule::command('reporte:notificaciones --periodo=semanal --guardar')
    ->weeklyOn(1, '07:00')
    ->timezone('America/Santiago')
    ->withoutOverlapping()
    ->description('Genera y guarda el reporte PDF semanal de notificaciones')
    ->onSuccess(function () {
        Log::channel('scheduler')->info('Reporte semanal de notificaciones generado');
    })
    ->onFailure(function () {
        Log::channel('scheduler')->error('Error generando reporte semanal');
    });

// Reporte mensual de notificaciones: el día 1 a las 06:00.
Schedule::command('reporte:notificaciones --periodo=mensual --guardar')
    ->monthlyOn(1, '06:00')
    ->timezone('America/Santiago')
    ->withoutOverlapping()
    ->description('Genera y guarda el reporte PDF mensual de notificaciones')
    ->onSuccess(function () {
        Log::channel('scheduler')->info('Reporte mensual de notificaciones generado');
    })
    ->onFailure(function () {
        Log::channel('scheduler')->error('Error generando reporte mensual');
    });
