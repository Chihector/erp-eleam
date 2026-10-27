# =============================================================================
# ERP ELEAM — Detener el servidor local de Windows
# =============================================================================
# Detiene únicamente el proceso PHP que inició Iniciar-ERP.ps1. No cierra Edge
# ni toca otros procesos del sistema.
#
# Uso normal: doble clic en windows\Detener-ERP.bat
# =============================================================================

[CmdletBinding()]
param(
    [int]$Port = 8000
)

$ErrorActionPreference = 'Stop'

$raiz = Split-Path -Parent $PSScriptRoot
$pidFile = Join-Path $raiz 'storage\app\erp-server.pid'

function Detener-Php([int]$IdProceso) {
    $proceso = Get-Process -Id $IdProceso -ErrorAction SilentlyContinue

    if (-not $proceso) {
        return $false
    }

    if ($proceso.ProcessName -ne 'php') {
        Write-Warning "El proceso $IdProceso no es PHP ($($proceso.ProcessName)); no se detiene."
        return $false
    }

    Stop-Process -Id $IdProceso -Force -ErrorAction Stop
    return $true
}

$detenido = $false

if (Test-Path $pidFile) {
    $idGuardado = Get-Content $pidFile -ErrorAction SilentlyContinue | Select-Object -First 1

    if ($idGuardado -match '^\d+$' -and (Detener-Php ([int]$idGuardado))) {
        $detenido = $true
    }

    Remove-Item $pidFile -Force -ErrorAction SilentlyContinue
}

# Respaldo: si el PID no estaba o el proceso padre ya terminó, se busca el
# proceso que escucha el puerto y se detiene solo si es PHP.
$conexion = Get-NetTCPConnection -State Listen -LocalPort $Port -ErrorAction SilentlyContinue |
    Select-Object -First 1

if ($conexion -and (Detener-Php $conexion.OwningProcess)) {
    $detenido = $true
}

if ($detenido) {
    Write-Host 'Servidor del ERP detenido.' -ForegroundColor Green
}
else {
    Write-Host 'No había un servidor del ERP en ejecución.' -ForegroundColor Yellow
}
