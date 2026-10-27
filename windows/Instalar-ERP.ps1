[CmdletBinding()]
param(
    [string]$AdminEmail = '',
    [string]$AdminNombre = '',
    [string]$AdminApellido = '',
    [string]$AdminRut = ''
)

$ErrorActionPreference = 'Stop'

$raiz = Split-Path -Parent $PSScriptRoot
Set-Location $raiz

$runtime = Join-Path $raiz 'runtime'
$phpIncluido = Join-Path $runtime 'php.exe'

if (Test-Path $phpIncluido) {
    $php = $phpIncluido
    $env:PHPRC = $runtime
    Remove-Item Env:\PHP_INI_SCAN_DIR -ErrorAction SilentlyContinue
    $env:PATH = "$runtime;$env:PATH"
}
else {
    $phpComando = Get-Command php.exe -ErrorAction SilentlyContinue
    if (-not $phpComando -and (Test-Path 'C:\xampp\php\php.exe')) {
        $php = 'C:\xampp\php\php.exe'
    }
    elseif ($phpComando) {
        $php = $phpComando.Source
    }
    else {
        throw 'No se encontró PHP. Usa el paquete portable o instala PHP 8.2 o superior.'
    }
}

$artisan = Join-Path $raiz 'artisan'
$envArchivo = Join-Path $raiz '.env'
$baseDatos = Join-Path $raiz 'database\database.sqlite'
$marca = Join-Path $raiz 'storage\app\portable-installada'

function Ejecutar-Artisan {
    param([string[]]$Argumentos)

    & $php $artisan @Argumentos
    if ($LASTEXITCODE -ne 0) {
        throw "Falló php artisan $($Argumentos -join ' '). Revisa el mensaje anterior."
    }
}

if (-not (Test-Path $envArchivo)) {
    Copy-Item -LiteralPath (Join-Path $raiz '.env.example') -Destination $envArchivo

    $contenidoEnv = Get-Content -LiteralPath $envArchivo -Raw
    $contenidoEnv = [regex]::Replace($contenidoEnv, '(?m)^APP_ENV=.*$', 'APP_ENV=production')
    $contenidoEnv = [regex]::Replace($contenidoEnv, '(?m)^APP_DEBUG=.*$', 'APP_DEBUG=false')
    $utf8SinBOM = New-Object System.Text.UTF8Encoding -ArgumentList $false
    [System.IO.File]::WriteAllText($envArchivo, $contenidoEnv, $utf8SinBOM)
}

foreach ($directorio in @(
    'database',
    'storage\app',
    'storage\app\private',
    'storage\app\public',
    'storage\framework\cache\data',
    'storage\framework\sessions',
    'storage\framework\views',
    'storage\logs',
    'bootstrap\cache'
)) {
    New-Item -ItemType Directory -Force -Path (Join-Path $raiz $directorio) | Out-Null
}
if (-not (Test-Path $baseDatos)) {
    New-Item -ItemType File -Path $baseDatos | Out-Null
}

$contenidoEnv = Get-Content -LiteralPath $envArchivo -Raw
if ($contenidoEnv -notmatch '(?m)^APP_KEY=.+$') {
    Ejecutar-Artisan -Argumentos @('key:generate', '--force')
}

Ejecutar-Artisan -Argumentos @('migrate', '--seed', '--force')

if (-not (Test-Path $marca)) {
    if (-not $AdminEmail) {
        $AdminEmail = Read-Host 'Correo del administrador'
    }
    if (-not $AdminNombre) {
        $AdminNombre = Read-Host 'Nombre del administrador'
    }
    if (-not $AdminApellido) {
        $AdminApellido = Read-Host 'Apellido del administrador'
    }
    if (-not $AdminRut) {
        $AdminRut = Read-Host 'RUT del administrador (formato 12345678-5)'
    }

    if ([string]::IsNullOrWhiteSpace($AdminEmail) -or
        [string]::IsNullOrWhiteSpace($AdminNombre) -or
        [string]::IsNullOrWhiteSpace($AdminApellido) -or
        [string]::IsNullOrWhiteSpace($AdminRut)) {
        throw 'Debes indicar correo, nombre, apellido y RUT para crear el administrador.'
    }

    Ejecutar-Artisan -Argumentos @(
        'eleam:admin-crear',
        $AdminEmail,
        "--nombre=$AdminNombre",
        "--apellido=$AdminApellido",
        "--rut=$AdminRut"
    )

}

$publicStorage = Join-Path $raiz 'public\storage'
$privateStorage = Join-Path $raiz 'storage\app\public'
if (-not (Test-Path $publicStorage)) {
    New-Item -ItemType Junction -Path $publicStorage -Target $privateStorage | Out-Null
}

if (-not (Test-Path $marca)) {
    Set-Content -LiteralPath $marca -Value (Get-Date -Format 'o') -Encoding ASCII
}

Write-Host ''
Write-Host 'Instalacion local del ERP completada.' -ForegroundColor Green
Write-Host 'Para iniciar, ejecuta windows\Iniciar-ERP.bat.' -ForegroundColor DarkGray
