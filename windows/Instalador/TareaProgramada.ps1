# =============================================================================
# ERP ELEAM - Registrar o quitar la tarea programada de Windows
# =============================================================================
# La tarea ejecuta `php artisan schedule:run` cada minuto. Es necesaria para
# notificaciones, reportes, limpieza de logs y el respaldo local diario.
# =============================================================================

[CmdletBinding()]
param(
    [ValidateSet('Instalar', 'Quitar', 'Estado')]
    [string]$Accion = 'Instalar',

    [string]$AppDir = '',
    [string]$DataDir = ''
)

$ErrorActionPreference = 'Stop'

$nombreTarea = 'ERP ELEAM - Tareas programadas'

if (-not $AppDir) {
    $AppDir = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
}

if (-not $DataDir) {
    if ($env:ELEAM_DATA_DIR) {
        $DataDir = $env:ELEAM_DATA_DIR
    }
    else {
        $DataDir = Join-Path $env:LOCALAPPDATA 'ERP-ELEAM\data'
    }
}

$AppDir = [System.IO.Path]::GetFullPath($AppDir)
$DataDir = [System.IO.Path]::GetFullPath($DataDir)

try {
    if ($Accion -eq 'Quitar') {
        $existente = Get-ScheduledTask -TaskName $nombreTarea -ErrorAction SilentlyContinue
        if ($existente) {
            Unregister-ScheduledTask -TaskName $nombreTarea -Confirm:$false
        }
        Write-Host "Tarea eliminada: $nombreTarea"
        exit 0
    }

    if ($Accion -eq 'Estado') {
        $tarea = Get-ScheduledTask -TaskName $nombreTarea -ErrorAction SilentlyContinue
        if (-not $tarea) {
            Write-Host 'La tarea no esta registrada.'
            exit 1
        }
        $info = Get-ScheduledTaskInfo -TaskName $nombreTarea
        Write-Host "Tarea: $($tarea.TaskName)"
        Write-Host "Estado: $($tarea.State)"
        Write-Host "Ultima ejecucion: $($info.LastRunTime)"
        Write-Host "Ultimo resultado: $($info.LastTaskResult)"
        exit 0
    }

    $scheduler = Join-Path $AppDir 'windows\Instalador\Scheduler-ERP.ps1'

    if (-not (Test-Path $scheduler)) {
        throw "No se encontro el ejecutor del scheduler: $scheduler"
    }

    $argumentos = "-NoProfile -NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$scheduler`" -AppDir `"$AppDir`" -DataDir `"$DataDir`""

    $tareaAccion = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument $argumentos
    $disparador = New-ScheduledTaskTrigger -Once -At (Get-Date) `
        -RepetitionInterval (New-TimeSpan -Minutes 1) `
        -RepetitionDuration (New-TimeSpan -Days 3650)
    $ajustes = New-ScheduledTaskSettingsSet `
        -AllowStartIfOnBatteries `
        -DontStopIfGoingOnBatteries `
        -StartWhenAvailable `
        -MultipleInstances IgnoreNew `
        -ExecutionTimeLimit (New-TimeSpan -Minutes 10)

    Register-ScheduledTask `
        -TaskName $nombreTarea `
        -Action $tareaAccion `
        -Trigger $disparador `
        -Settings $ajustes `
        -Description 'Ejecuta las tareas programadas de ERP ELEAM (notificaciones, reportes y respaldos).' `
        -Force | Out-Null

    Write-Host "Tarea registrada: $nombreTarea" -ForegroundColor Green
    exit 0
}
catch {
    Write-Error "No se pudo administrar la tarea programada: $($_.Exception.Message)"
    exit 1
}
