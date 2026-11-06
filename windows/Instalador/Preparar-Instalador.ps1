# =============================================================================
# ERP ELEAM - Construir el instalador de Windows
# =============================================================================
# Requisitos en el PC de desarrollo: PHP 8.2+, Composer, Node.js/npm e
# Inno Setup 6 (o usa -InstalarInno para instalarlo con winget).
#
# Ejemplo:
#   powershell -ExecutionPolicy Bypass -File windows\Instalador\Preparar-Instalador.ps1 -Version 0.2.0
# =============================================================================

[CmdletBinding()]
param(
    [string]$Version = '0.2.0',
    [string]$Destino = '',
    [string]$ZipPortable = '',
    [string]$PayloadDir = '',
    [switch]$InstalarInno,
    [switch]$SoloPaquete,
    [switch]$ConservarTemporal
)

$ErrorActionPreference = 'Stop'

$raiz = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)

if (-not $Destino) {
    $Destino = Join-Path $raiz 'dist'
}

$destinoAbsoluto = [System.IO.Path]::GetFullPath($Destino)
New-Item -ItemType Directory -Force -Path $destinoAbsoluto | Out-Null

if ($Version -notmatch '^\d+\.\d+\.\d+$') {
    throw 'La version debe tener el formato numerico 1.2.3 para el instalador.'
}

$temporal = Join-Path $env:TEMP ("erp-instalador-" + [guid]::NewGuid().ToString('N').Substring(0, 8))
$payload = Join-Path $temporal 'app'
$icono = Join-Path $PSScriptRoot 'ERP-ELEAM.ico'

function New-Icono {
    param([string]$Ruta)

    Add-Type -AssemblyName System.Drawing

    $bitmap = New-Object System.Drawing.Bitmap 256, 256
    $grafico = [System.Drawing.Graphics]::FromImage($bitmap)
    $grafico.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $grafico.Clear([System.Drawing.Color]::FromArgb(15, 23, 42))

    $pincel = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(45, 212, 191))
    $fuente = New-Object System.Drawing.Font('Segoe UI', 72, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $grafico.DrawString('ERP', $fuente, $pincel, 18, 82)
    $grafico.Dispose()

    $iconoWindows = [System.Drawing.Icon]::FromHandle($bitmap.GetHicon())
    $archivo = [System.IO.File]::Create($Ruta)
    $iconoWindows.Save($archivo)
    $archivo.Close()
    $iconoWindows.Dispose()
    $bitmap.Dispose()
}

function Expand-Paquete {
    param(
        [string]$Archivo,
        [string]$Destino
    )

    New-Item -ItemType Directory -Force -Path $Destino | Out-Null

    # Expand-Archive de PowerShell 5 tarda decenas de minutos con miles de
    # archivos. tar.exe (incluido en Windows 10/11) extrae el mismo ZIP en
    # segundos; ZipFile queda como respaldo para equipos sin tar.
    $tar = Join-Path $env:SystemRoot 'System32\tar.exe'
    if (Test-Path $tar) {
        & $tar -xf $Archivo -C $Destino
        if ($LASTEXITCODE -eq 0) { return }
        throw "tar no pudo extraer el paquete (codigo $LASTEXITCODE)."
    }

    Add-Type -AssemblyName System.IO.Compression.FileSystem
    [System.IO.Compression.ZipFile]::ExtractToDirectory($Archivo, $Destino)
}

function Buscar-Iscc {
    $candidatos = @()

    if (${env:ProgramFiles(x86)}) {
        $candidatos += (Join-Path ${env:ProgramFiles(x86)} 'Inno Setup 6\ISCC.exe')
    }

    if ($env:ProgramFiles) {
        $candidatos += (Join-Path $env:ProgramFiles 'Inno Setup 6\ISCC.exe')
    }

    if ($env:LOCALAPPDATA) {
        $candidatos += (Join-Path $env:LOCALAPPDATA 'Programs\Inno Setup 6\ISCC.exe')
    }

    foreach ($candidato in $candidatos) {
        if (Test-Path $candidato) { return $candidato }
    }

    $comando = Get-Command iscc.exe -ErrorAction SilentlyContinue
    if ($comando) { return $comando.Source }

    return $null
}

function Instalar-InnoSetup {
    $winget = Get-Command winget.exe -ErrorAction SilentlyContinue

    if ($winget) {
        Write-Host 'Instalando Inno Setup con winget...'
        & $winget.Source install --id JRSoftware.InnoSetup --exact --silent `
            --accept-package-agreements --accept-source-agreements --disable-interactivity

        if ($LASTEXITCODE -eq 0) { return }
    }

    Write-Host 'Descargando Inno Setup desde jrsoftware.org...'
    $instalador = Join-Path $temporal 'innosetup.exe'
    Invoke-WebRequest -Uri 'https://jrsoftware.org/download.php/is.exe' -OutFile $instalador -UseBasicParsing

    & $instalador /VERYSILENT /SUPPRESSMSGBOXES /NORESTART /SP-
    if ($LASTEXITCODE -ne 0) {
        throw 'No se pudo instalar Inno Setup automaticamente. Instalalo desde https://jrsoftware.org/isdl.php'
    }
}

if (-not (Test-Path $icono)) {
    New-Icono $icono
}

try {
    if (Test-Path $temporal) {
        throw "La carpeta temporal ya existe: $temporal"
    }

    New-Item -ItemType Directory -Force -Path $temporal, $payload | Out-Null

    if ($PayloadDir) {
        $origen = [System.IO.Path]::GetFullPath($PayloadDir)

        if (-not (Test-Path (Join-Path $origen 'artisan'))) {
            throw "El paquete indicado en -PayloadDir no contiene artisan: $origen"
        }

        Write-Host "Usando el paquete ya extraido: $origen"
        $payload = $origen
    }
    else {
        if ($ZipPortable) {
            $zip = [System.IO.Path]::GetFullPath($ZipPortable)
            if (-not (Test-Path $zip)) { throw "No existe el paquete portable indicado: $zip" }
        }
        else {
            Write-Host 'Generando el paquete de aplicacion (npm, Composer, PHP)...'
            & (Join-Path $raiz 'windows\Preparar-ERP-Portatil.ps1') -Version $Version -Destino $temporal

            $zip = Join-Path $temporal "ERP-ELEAM-portable-$Version.zip"
            if (-not (Test-Path $zip)) {
                throw "No se genero el paquete portable esperado: $zip"
            }
        }

        Write-Host 'Extrayendo el paquete de aplicacion...'
        Expand-Paquete -Archivo $zip -Destino $payload
    }

    if (-not (Test-Path (Join-Path $payload 'artisan'))) {
        throw 'El paquete no contiene artisan en la raiz.'
    }

    $php = (Get-Command php.exe -ErrorAction SilentlyContinue).Source
    $phpVersion = 'desconocida'
    if ($php) { $phpVersion = (& $php -r 'echo PHP_VERSION;').Trim() }

    $commit = ''
    try {
        $commit = (& git -C $raiz rev-parse --short HEAD 2>$null).Trim()
    }
    catch { }

    @{
        version = $Version
        construido_en = (Get-Date).ToString('o')
        php = $phpVersion
        commit = $commit
    } | ConvertTo-Json | Set-Content -LiteralPath (Join-Path $payload 'version.json') -Encoding UTF8

    if ($SoloPaquete) {
        $paqueteFinal = Join-Path $destinoAbsoluto "ERP-ELEAM-app-$Version.zip"
        if (Test-Path $paqueteFinal) { Remove-Item -LiteralPath $paqueteFinal -Force }

        $tar = Join-Path $env:SystemRoot 'System32\tar.exe'
        if (Test-Path $tar) {
            & $tar -a -c -f $paqueteFinal -C $payload .
            if ($LASTEXITCODE -ne 0) {
                throw "tar no pudo crear el paquete de aplicacion (codigo $LASTEXITCODE)."
            }
        }
        else {
            Compress-Archive -Path (Join-Path $payload '*') -DestinationPath $paqueteFinal -CompressionLevel Optimal
        }

        Write-Host "Paquete de aplicacion creado: $paqueteFinal" -ForegroundColor Green
        exit 0
    }

    $iscc = Buscar-Iscc

    if (-not $iscc) {
        if ($InstalarInno) {
            Instalar-InnoSetup
            $iscc = Buscar-Iscc
        }
    }

    if (-not $iscc) {
        throw 'No se encontro Inno Setup 6. Instalalo desde https://jrsoftware.org/isdl.php o ejecuta este script con -InstalarInno.'
    }

    Write-Host "Compilando el instalador con $iscc ..."
    $iss = Join-Path $PSScriptRoot 'ERP-ELEAM.iss'

    & $iscc `
        "/DAppVersion=$Version" `
        "/DSourceDir=$payload" `
        "/DOutputDir=$destinoAbsoluto" `
        "/DIconFile=$icono" `
        $iss

    if ($LASTEXITCODE -ne 0) {
        throw "Inno Setup fallo con codigo $LASTEXITCODE."
    }

    $instaladorFinal = Join-Path $destinoAbsoluto "ERP-ELEAM-Setup-$Version.exe"
    if (-not (Test-Path $instaladorFinal)) {
        throw "No se encontro el instalador generado: $instaladorFinal"
    }

    Write-Host ''
    Write-Host "Instalador creado: $instaladorFinal" -ForegroundColor Green
    Write-Host 'Pruebalo en un PC Windows limpio antes de distribuirlo.' -ForegroundColor DarkGray
}
finally {
    if (-not $ConservarTemporal -and (Test-Path $temporal)) {
        Remove-Item -LiteralPath $temporal -Recurse -Force -ErrorAction SilentlyContinue
    }
    elseif ($ConservarTemporal) {
        Write-Host "Temporal conservado en: $temporal"
    }
}
