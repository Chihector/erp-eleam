# =============================================================================
# ERP ELEAM - Detener el servidor y quitar la tarea programada al desinstalar
# =============================================================================

[CmdletBinding()]
param(
    [string]$AppDir = '',
    [string]$DataDir = ''
)

$ErrorActionPreference = 'SilentlyContinue'

if (-not $AppDir) {
    $AppDir = Join-Path $PSScriptRoot 'app'
}

if (-not $DataDir) {
    if ($env:ELEAM_DATA_DIR) {
        $DataDir = $env:ELEAM_DATA_DIR
    }
    else {
        $DataDir = Join-Path $env:LOCALAPPDATA 'ERP-ELEAM\data'
    }
}

$pidFile = Join-Path $DataDir 'storage\app\erp-server.json'

if (Test-Path $pidFile) {
    try {
        $info = Get-Content -LiteralPath $pidFile -Raw | ConvertFrom-Json
        $proceso = Get-Process -Id ([int]$info.pid) -ErrorAction SilentlyContinue
        if ($proceso -and $proceso.ProcessName -eq 'php') {
            Stop-Process -Id ([int]$info.pid) -Force
        }
    }
    catch {
        # La desinstalacion continua aunque no se pueda detener el proceso.
    }

    Remove-Item -LiteralPath $pidFile -Force
}

$tarea = Join-Path $AppDir 'windows\Instalador\TareaProgramada.ps1'

if (Test-Path $tarea) {
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $tarea `
        -Accion Quitar `
        -AppDir $AppDir `
        -DataDir $DataDir
}
