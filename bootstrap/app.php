<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Las rutas protegidas fuera del panel (por ejemplo, los PDF) deben
        // enviar al login de Filament, no a una ruta `login` inexistente.
        $middleware->redirectGuestsTo(fn (): string => route('filament.admin.auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

/*
|--------------------------------------------------------------------------
| Instalación de escritorio: código y datos separados
|--------------------------------------------------------------------------
|
| El instalador de Windows deja el programa en una carpeta estable y los
| datos (base SQLite, storage y .env) en otra que se conserva al actualizar.
| El lanzador define ELEAM_DATA_DIR antes de iniciar PHP. En desarrollo y en
| las pruebas la variable no existe, así que todo sigue usando la raíz del
| proyecto.
|
*/

$dataDir = getenv('ELEAM_DATA_DIR') ?: ($_SERVER['ELEAM_DATA_DIR'] ?? null);

if (is_string($dataDir) && trim($dataDir) !== '') {
    $dataDir = rtrim($dataDir, '\\/');

    $app->useEnvironmentPath($dataDir);
    $app->useStoragePath($dataDir.DIRECTORY_SEPARATOR.'storage');

    // La base SQLite se define con DB_DATABASE (el lanzador la apunta a
    // data\database\database.sqlite). No se usa useDatabasePath() porque
    // también movería database/migrations y el paquete quedaría sin
    // migraciones.
}

return $app;
