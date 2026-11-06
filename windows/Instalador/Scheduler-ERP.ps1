# =============================================================================
# ERP ELEAM - Ejecutor silencioso del scheduler de Laravel
# =============================================================================
# Lo llama la tarea programada de Windows cada minuto. No abrir manualmente.
# =============================================================================

[CmdletBinding()]
param(
    [Parameter(Mandatory)]
    [string]$AppDir,

    [Parameter(Mandatory)]
    [string]$DataDir
)

$ErrorActionPreference = 'Stop'

$env:ELEAM_DATA_DIR = $DataDir
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = Join-Path $DataDir 'database\database.sqlite'

$runtime = Join-Path $AppDir 'runtime'
$php = Join-Path $runtime 'php.exe'

if (Test-Path $php) {
    $env:PHPRC = $runtime
    Remove-Item Env:\PHP_INI_SCAN_DIR -ErrorAction SilentlyContinue
    $env:PATH = "$runtime;$env:PATH"
}
else {
    $comando = Get-Command php.exe -ErrorAction SilentlyContinue
    if (-not $comando) {
        throw 'No se encontro PHP para ejecutar el scheduler.'
    }
    $php = $comando.Source
}

$logs = Join-Path $DataDir 'storage\logs'
New-Item -ItemType Directory -Force -Path $logs | Out-Null
$log = Join-Path $logs 'scheduler.log'

if ((Test-Path $log) -and (Get-Item $log).Length -gt 5MB) {
    Remove-Item -LiteralPath $log -Force
}

$salida = & $php (Join-Path $AppDir 'artisan') schedule:run 2>&1
$codigo = $LASTEXITCODE

if ($salida) {
    $salida | Out-File -LiteralPath $log -Append -Encoding utf8
}

exit $codigo
