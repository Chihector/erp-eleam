# =============================================================================
# ERP ELEAM — Abrir el sistema como aplicación en Windows
# =============================================================================
# Levanta el servidor local de Laravel (si no está corriendo), espera a que
# responda /up y abre Edge en modo aplicación con un perfil dedicado.
#
# Uso normal: doble clic en windows\Iniciar-ERP.bat
# Uso manual: powershell -ExecutionPolicy Bypass -File windows\Iniciar-ERP.ps1
# =============================================================================

[CmdletBinding()]
param(
    [int]$Port = 8000,
    [switch]$SinNavegador
)

$ErrorActionPreference = 'Stop'

$raiz = Split-Path -Parent $PSScriptRoot
Set-Location $raiz

function Buscar-Php {
    $comando = Get-Command php.exe -ErrorAction SilentlyContinue
    if ($comando) {
        return $comando.Source
    }

    $xampp = 'C:\xampp\php\php.exe'
    if (Test-Path $xampp) {
        return $xampp
    }

    throw 'No se encontró PHP. Instala PHP 8.2 o superior, o agrega php.exe al PATH.'
}

function Probar-Erp {
    try {
        $respuesta = Invoke-WebRequest -Uri "http://127.0.0.1:$Port/up" -UseBasicParsing -TimeoutSec 2
        return $respuesta.StatusCode -eq 200
    }
    catch {
        return $false
    }
}

$php = Buscar-Php
$storage = Join-Path $raiz 'storage'
$pidFile = Join-Path $storage 'app\erp-server.pid'
$log = Join-Path $storage 'logs\serve-windows.log'
$err = Join-Path $storage 'logs\serve-windows.err.log'

New-Item -ItemType Directory -Force -Path (Split-Path $pidFile), (Split-Path $log) | Out-Null

if (-not (Probar-Erp)) {
    $ocupado = Get-NetTCPConnection -State Listen -LocalPort $Port -ErrorAction SilentlyContinue
    if ($ocupado) {
        throw "El puerto $Port ya está ocupado por otro proceso. Ciérralo o usa -Port con otro valor."
    }

    $router = Join-Path $raiz 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'

    if (-not (Test-Path $router)) {
        throw 'Faltan las dependencias de Composer. Ejecuta: composer install'
    }

    # Se inicia php -S directamente (sin artisan serve) para que el proceso
    # guardado sea el servidor real y se pueda detener sin dejar hijos vivos.
    $proceso = Start-Process -FilePath $php `
        -ArgumentList @('-S', "127.0.0.1:$Port", '..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php') `
        -WorkingDirectory (Join-Path $raiz 'public') `
        -WindowStyle Hidden `
        -RedirectStandardOutput $log `
        -RedirectStandardError $err `
        -PassThru

    Set-Content -Path $pidFile -Value $proceso.Id -Encoding ASCII

    $limite = (Get-Date).AddSeconds(60)
    while ((Get-Date) -lt $limite -and -not (Probar-Erp)) {
        Start-Sleep -Milliseconds 500
    }

    if (-not (Probar-Erp)) {
        throw "El servidor no respondió en 60 segundos. Revisa $log y $err."
    }
}

if (-not $SinNavegador) {
    $candidatosEdge = @()

    $comandoEdge = Get-Command msedge.exe -ErrorAction SilentlyContinue
    if ($comandoEdge) {
        $candidatosEdge += $comandoEdge.Source
    }

    if (${env:ProgramFiles(x86)}) {
        $candidatosEdge += "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe"
    }

    if ($env:ProgramFiles) {
        $candidatosEdge += "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe"
    }

    $edge = $candidatosEdge | Where-Object { Test-Path $_ } | Select-Object -First 1
    $url = "http://127.0.0.1:$Port/admin"

    if ($edge) {
        $perfil = Join-Path $storage 'app\edge-profile'
        New-Item -ItemType Directory -Force -Path $perfil | Out-Null

        $argumentos = "--app=$url --user-data-dir=`"$perfil`" --no-first-run --no-default-browser-check --window-size=1440,900"
        Start-Process -FilePath $edge -ArgumentList $argumentos
    }
    else {
        Write-Warning 'No se encontró Microsoft Edge. Se abrirá el navegador predeterminado.'
        Start-Process $url
    }
}

Write-Host ''
Write-Host "ERP ELEAM disponible en http://127.0.0.1:$Port/admin" -ForegroundColor Green
Write-Host 'Para detener el servidor ejecuta windows\Detener-ERP.bat.' -ForegroundColor DarkGray
