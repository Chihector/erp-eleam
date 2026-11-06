# =============================================================================
# ERP ELEAM - Actualizador con respaldo y reversa automatica
# =============================================================================
# Uso instalado:
#   powershell -ExecutionPolicy Bypass -File Actualizar-ERP.ps1
#   powershell -ExecutionPolicy Bypass -File Actualizar-ERP.ps1 -Zip C:\ruta\ERP-ELEAM.zip
#
# El actualizador:
#   1. detiene el servidor;
#   2. crea y verifica un respaldo local;
#   3. reemplaza solo la carpeta app (los datos quedan intactos);
#   4. ejecuta migraciones y caches;
#   5. prueba /up y, si falla, restaura la version anterior.
# =============================================================================

[CmdletBinding()]
param(
    [string]$Zip = '',
    [string]$AppDir = '',
    [string]$DataDir = '',
    [int]$Port = 0,
    [switch]$Silencioso
)

$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.Windows.Forms

if (-not $AppDir) {
    if (Test-Path (Join-Path $PSScriptRoot 'app\artisan')) {
        $AppDir = Join-Path $PSScriptRoot 'app'
    }
    else {
        $AppDir = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
    }
}

$AppDir = [System.IO.Path]::GetFullPath($AppDir)

if (-not $DataDir) {
    if ($env:ELEAM_DATA_DIR) {
        $DataDir = $env:ELEAM_DATA_DIR
    }
    else {
        $DataDir = Join-Path $env:LOCALAPPDATA 'ERP-ELEAM\data'
    }
}

$DataDir = [System.IO.Path]::GetFullPath($DataDir)
$Storage = Join-Path $DataDir 'storage'
$Backups = Join-Path $DataDir 'backups'
$Updates = Join-Path $DataDir 'updates'
$PidFile = Join-Path $Storage 'app\erp-server.json'
$ConfigFile = Join-Path $DataDir 'config.json'

if (Test-Path (Join-Path $AppDir '.git')) {
    throw 'Este actualizador es para la version instalada. No reemplaza el repositorio de desarrollo.'
}

function Obtener-Php {
    param([string]$Raiz)

    $incluido = Join-Path $Raiz 'runtime\php.exe'
    if (Test-Path $incluido) {
        $env:PHPRC = Join-Path $Raiz 'runtime'
        Remove-Item Env:\PHP_INI_SCAN_DIR -ErrorAction SilentlyContinue
        $env:PATH = "$(Join-Path $Raiz 'runtime');$env:PATH"
        return $incluido
    }

    $comando = Get-Command php.exe -ErrorAction SilentlyContinue
    if ($comando) { return $comando.Source }

    throw 'No se encontro PHP para ejecutar la actualizacion.'
}

$env:ELEAM_DATA_DIR = $DataDir
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = Join-Path $DataDir 'database\database.sqlite'

function Invoke-Artisan {
    param(
        [string]$Php,
        [string[]]$Argumentos
    )

    $salida = & $Php (Join-Path $AppDir 'artisan') @Argumentos 2>&1

    return [pscustomobject]@{
        Codigo = $LASTEXITCODE
        Salida = ($salida -join "`r`n")
    }
}

function Test-Erp {
    param([int]$Puerto)

    try {
        $respuesta = Invoke-WebRequest -Uri "http://127.0.0.1:$Puerto/up" -UseBasicParsing -TimeoutSec 2
        return $respuesta.StatusCode -eq 200
    }
    catch {
        return $false
    }
}

function Leer-Servidor {
    if (-not (Test-Path $PidFile)) { return $null }

    try {
        $info = Get-Content -LiteralPath $PidFile -Raw | ConvertFrom-Json
        $proceso = Get-Process -Id ([int]$info.pid) -ErrorAction SilentlyContinue
        if ($proceso -and $proceso.ProcessName -eq 'php') { return $info }
    }
    catch { }

    return $null
}

function Detener-Servidor {
    $servidor = Leer-Servidor
    if ($servidor) {
        Stop-Process -Id ([int]$servidor.pid) -Force -ErrorAction SilentlyContinue
    }
    Remove-Item -LiteralPath $PidFile -Force -ErrorAction SilentlyContinue
}

function Expand-Paquete {
    param(
        [string]$Archivo,
        [string]$Destino
    )

    New-Item -ItemType Directory -Force -Path $Destino | Out-Null

    # tar.exe de Windows 10/11 es mucho mas rapido que Expand-Archive con
    # decenas de miles de archivos. ZipFile queda como respaldo.
    $tar = Join-Path $env:SystemRoot 'System32\tar.exe'
    if (Test-Path $tar) {
        & $tar -xf $Archivo -C $Destino
        if ($LASTEXITCODE -eq 0) { return }
        throw "tar no pudo extraer el paquete (codigo $LASTEXITCODE)."
    }

    Add-Type -AssemblyName System.IO.Compression.FileSystem
    [System.IO.Compression.ZipFile]::ExtractToDirectory($Archivo, $Destino)
}

function Obtener-Puerto {
    if ($Port -gt 0) { return $Port }

    if (Test-Path $ConfigFile) {
        try {
            $config = Get-Content -LiteralPath $ConfigFile -Raw | ConvertFrom-Json
            if ($config.port) { return [int]$config.port }
        }
        catch { }
    }

    return 8000
}

function Iniciar-Servidor {
    param(
        [string]$Php,
        [int]$Puerto
    )

    $env:APP_URL = "http://127.0.0.1:$Puerto"
    $router = Join-Path $AppDir 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'

    $log = Join-Path $Storage 'logs\serve-windows.log'
    $err = Join-Path $Storage 'logs\serve-windows.err.log'
    New-Item -ItemType Directory -Force -Path (Split-Path $log) | Out-Null

    $proceso = Start-Process -FilePath $Php `
        -ArgumentList @('-S', "127.0.0.1:$Puerto", '..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php') `
        -WorkingDirectory (Join-Path $AppDir 'public') `
        -WindowStyle Hidden `
        -RedirectStandardOutput $log `
        -RedirectStandardError $err `
        -PassThru

    @{
        pid = $proceso.Id
        port = $Puerto
        iniciado_en = (Get-Date).ToString('o')
    } | ConvertTo-Json | Set-Content -LiteralPath $PidFile -Encoding UTF8

    $limite = (Get-Date).AddSeconds(60)
    while ((Get-Date) -lt $limite -and -not (Test-Erp $Puerto)) {
        Start-Sleep -Milliseconds 500
    }

    if (-not (Test-Erp $Puerto)) {
        throw "El servidor actualizado no respondio. Revisa $err."
    }
}

function Restaurar-Anterior {
    param(
        [string]$Anterior,
        [string]$Fallida
    )

    Detener-Servidor

    if (Test-Path $AppDir) {
        Move-Item -LiteralPath $AppDir -Destination $Fallida -Force
    }

    Move-Item -LiteralPath $Anterior -Destination $AppDir -Force
}

function Mostrar-Resultado {
    param(
        [string]$Mensaje,
        [string]$Tipo = 'Info'
    )

    if ($Silencioso) {
        Write-Host $Mensaje
        return
    }

    $icono = [System.Windows.Forms.MessageBoxIcon]::Information
    if ($Tipo -eq 'Error') { $icono = [System.Windows.Forms.MessageBoxIcon]::Error }

    [System.Windows.Forms.MessageBox]::Show($Mensaje, 'ERP ELEAM - Actualizacion', 'OK', $icono) | Out-Null
}

New-Item -ItemType Directory -Force -Path $Backups, $Updates | Out-Null

if (-not $Zip) {
    if ($Silencioso) {
        Write-Host 'Falta -Zip para actualizar en modo silencioso.'
        exit 1
    }

    $dialogo = New-Object System.Windows.Forms.OpenFileDialog
    $dialogo.Title = 'Selecciona el paquete de actualizacion de ERP ELEAM'
    $dialogo.Filter = 'Paquete ERP ELEAM (*.zip)|*.zip|Todos los archivos (*.*)|*.*'
    $dialogo.CheckFileExists = $true

    if ($dialogo.ShowDialog() -ne [System.Windows.Forms.DialogResult]::OK) {
        exit 0
    }

    $Zip = $dialogo.FileName
}

if (-not (Test-Path $Zip)) {
    Mostrar-Resultado "No existe el paquete indicado:`r`n$Zip" 'Error'
    exit 1
}

if (-not $Silencioso) {
    $confirmacion = [System.Windows.Forms.MessageBox]::Show(
        "Se detendra el ERP, se creara un respaldo y se reemplazara el programa.`r`n`r`nLos datos y los adjuntos no se borran.`r`n`r`n¿Continuar?",
        'ERP ELEAM - Actualizar',
        'YesNo',
        'Question'
    )

    if ($confirmacion -ne [System.Windows.Forms.DialogResult]::Yes) {
        exit 0
    }
}

$puerto = Obtener-Puerto
$estabaActivo = Test-Erp $puerto
$phpActual = Obtener-Php $AppDir
$marca = Get-Date -Format 'yyyyMMdd-HHmmss'
$anterior = Join-Path $Updates "app-anterior-$marca"
$temporal = Join-Path $Updates "paquete-$marca"
$fallida = Join-Path $Updates "app-fallida-$marca"

try {
    Detener-Servidor

    Write-Host 'Creando respaldo antes de actualizar...'
    $respaldo = Invoke-Artisan $phpActual @(
        'eleam:backup-local',
        "--destino=$Backups",
        '--retencion=30'
    )

    if ($respaldo.Codigo -ne 0) {
        throw "No se pudo crear el respaldo; se cancela la actualizacion.`r`n$($respaldo.Salida)"
    }

    $carpetaRespaldo = Get-ChildItem -LiteralPath $Backups -Directory |
        Sort-Object LastWriteTime -Descending |
        Select-Object -First 1

    if ($carpetaRespaldo) {
        Write-Host 'Verificando el respaldo...'
        $verificacion = Invoke-Artisan $phpActual @('eleam:verificar-backup', $carpetaRespaldo.FullName)

        if ($verificacion.Codigo -ne 0) {
            throw "El respaldo no paso la verificacion; se cancela la actualizacion.`r`n$($verificacion.Salida)"
        }
    }

    Write-Host 'Extrayendo el paquete...'
    Expand-Paquete -Archivo $Zip -Destino $temporal

    $nuevo = $temporal
    if (Test-Path (Join-Path $temporal 'ERP-ELEAM\artisan')) {
        $nuevo = Join-Path $temporal 'ERP-ELEAM'
    }

    foreach ($requisito in @('artisan', 'vendor\autoload.php', 'runtime\php.exe', 'public\index.php')) {
        if (-not (Test-Path (Join-Path $nuevo $requisito))) {
            throw "El paquete no contiene $requisito. No parece una actualizacion completa de ERP ELEAM."
        }
    }

    $phpNuevo = Obtener-Php $nuevo
    $versionNueva = 'desconocida'
    if (Test-Path (Join-Path $nuevo 'version.json')) {
        try {
            $versionNueva = (Get-Content -LiteralPath (Join-Path $nuevo 'version.json') -Raw | ConvertFrom-Json).version
        }
        catch { }
    }

    Write-Host 'Reemplazando el programa (los datos quedan en su carpeta)...'
    Move-Item -LiteralPath $AppDir -Destination $anterior -Force
    Move-Item -LiteralPath $nuevo -Destination $AppDir -Force

    # A partir de aqui se usa el PHP y el artisan nuevos.
    $phpActual = Obtener-Php $AppDir
    $env:ELEAM_DATA_DIR = $DataDir
    $env:DB_CONNECTION = 'sqlite'
    $env:DB_DATABASE = Join-Path $DataDir 'database\database.sqlite'

    Write-Host 'Limpiando caches y ejecutando migraciones...'
    $limpieza = Invoke-Artisan $phpActual @('optimize:clear')
    if ($limpieza.Codigo -ne 0) {
        throw "Fallo optimize:clear.`r`n$($limpieza.Salida)"
    }

    $migracion = Invoke-Artisan $phpActual @('migrate', '--force')
    if ($migracion.Codigo -ne 0) {
        throw "Fallo la migracion de la base.`r`n$($migracion.Salida)"
    }

    $cacheVistas = Invoke-Artisan $phpActual @('view:cache')
    if ($cacheVistas.Codigo -ne 0) {
        Write-Warning "No se pudieron precompilar las vistas: $($cacheVistas.Salida)"
    }

    $filament = Invoke-Artisan $phpActual @('filament:optimize')
    if ($filament.Codigo -ne 0) {
        Write-Warning "No se pudieron optimizar los componentes de Filament: $($filament.Salida)"
    }

    if ($estabaActivo) {
        Write-Host 'Iniciando y probando la nueva version...'
        Iniciar-Servidor $phpActual $puerto

        $login = Invoke-WebRequest -Uri "http://127.0.0.1:$puerto/admin/login" -UseBasicParsing -TimeoutSec 15
        if ($login.StatusCode -ne 200) {
            throw "El login respondio $($login.StatusCode)."
        }
    }

    # Conservar solo la version anterior mas reciente.
    Get-ChildItem -LiteralPath $Updates -Directory -Filter 'app-anterior-*' |
        Sort-Object LastWriteTime -Descending |
        Select-Object -Skip 2 |
        ForEach-Object { Remove-Item -LiteralPath $_.FullName -Recurse -Force -ErrorAction SilentlyContinue }

    Remove-Item -LiteralPath $temporal -Recurse -Force -ErrorAction SilentlyContinue

    Mostrar-Resultado "ERP ELEAM se actualizo a la version $versionNueva.`r`n`r`nRespaldo: $($carpetaRespaldo.FullName)`r`nVersion anterior conservada en: $anterior"

    if ($estabaActivo -and -not $Silencioso) {
        Start-Process "http://127.0.0.1:$puerto/admin"
    }

    exit 0
}
catch {
    $mensaje = $_.Exception.Message
    Write-Host "FALLO: $mensaje" -ForegroundColor Red

    try {
        if (Test-Path $anterior) {
            Restaurar-Anterior -Anterior $anterior -Fallida $fallida

            if ($estabaActivo) {
                $phpViejo = Obtener-Php $AppDir
                Iniciar-Servidor $phpViejo $puerto
            }
        }
        elseif ($estabaActivo -and (Test-Path (Join-Path $AppDir 'artisan'))) {
            # El fallo ocurrio antes de reemplazar el programa: se vuelve a
            # iniciar la version que ya estaba instalada.
            $phpViejo = Obtener-Php $AppDir
            Iniciar-Servidor $phpViejo $puerto
        }
    }
    catch {
        $mensaje += "`r`n`r`nAdemas fallo la reversa automatica: $($_.Exception.Message)"
    }

    Mostrar-Resultado "No se pudo actualizar ERP ELEAM.`r`n`r`n$mensaje`r`n`r`nLos respaldos estan en: $Backups" 'Error'
    exit 1
}
