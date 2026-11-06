[CmdletBinding()]
param(
    [string]$Version = 'preview',
    [string]$Destino = ''
)

$ErrorActionPreference = 'Stop'

$raiz = Split-Path -Parent $PSScriptRoot
if (-not $Destino) {
    $Destino = Join-Path $raiz 'dist'
}

$phpComando = Get-Command php.exe -ErrorAction SilentlyContinue
if (-not $phpComando) {
    throw 'No se encontró php.exe. Instala PHP 8.2+ para preparar el paquete.'
}

$composerComando = Get-Command composer -ErrorAction SilentlyContinue
if (-not $composerComando) {
    throw 'No se encontró Composer. Es necesario solo para construir el paquete.'
}

$npmComando = Get-Command npm -ErrorAction SilentlyContinue
if (-not $npmComando) {
    throw 'No se encontró npm. Es necesario solo para compilar los recursos del paquete.'
}

$php = $phpComando.Source
$phpRaiz = Split-Path -Parent $php
$phpVersion = & $php -r 'echo PHP_VERSION;'
if ($LASTEXITCODE -ne 0 -or [version]($phpVersion -replace '-.*$', '') -lt [version]'8.2') {
    throw "Se requiere PHP 8.2 o superior. Versión encontrada: $phpVersion"
}

$modulos = @(& $php -m)
foreach ($modulo in @('curl', 'fileinfo', 'intl', 'mbstring', 'openssl', 'PDO', 'pdo_sqlite', 'zip')) {
    if ($modulos -notcontains $modulo) {
        throw "El PHP de construcción no tiene habilitada la extensión requerida: $modulo"
    }
}

if (-not (Test-Path (Join-Path $raiz 'composer.lock')) -or
    -not (Test-Path (Join-Path $raiz 'package-lock.json'))) {
    throw 'Faltan composer.lock o package-lock.json; no se puede generar un paquete reproducible.'
}

$versionArchivo = [regex]::Replace($Version, '[^A-Za-z0-9._-]', '-')
$destinoAbsoluto = [System.IO.Path]::GetFullPath($Destino)
$archivoZip = Join-Path $destinoAbsoluto "ERP-ELEAM-portable-$versionArchivo.zip"
$temporal = Join-Path $env:TEMP ("erp-" + [guid]::NewGuid().ToString('N').Substring(0, 8))
$paquete = Join-Path $temporal 'ERP-ELEAM'
$runtime = Join-Path $paquete 'runtime'

if (Test-Path $temporal) {
    throw "La carpeta temporal ya existe y no se modificará: $temporal"
}

New-Item -ItemType Directory -Force -Path $destinoAbsoluto, $paquete, $runtime | Out-Null

try {
    Push-Location $raiz
    try {
        & $npmComando.Source run build
        if ($LASTEXITCODE -ne 0) {
            throw 'Falló la compilación de los recursos web con npm run build.'
        }
    }
    finally {
        Pop-Location
    }

    $excluirDirectorios = @('.git', 'database', 'dist', 'node_modules', 'storage', 'tests', 'vendor')
    $excluirArchivos = @(
        '.env',
        '.env.production',
        '.phpunit.result.cache',
        '.aider.chat.history.md',
        'composer.phar',
        'google-drive-credentials.json',
        'check_login.php',
        'test_boot.php',
        'test_rut.php'
    )

    Get-ChildItem -LiteralPath $raiz -Force | ForEach-Object {
        if ($_.PSIsContainer) {
            if ($_.Name -eq 'database') {
                $databasePaquete = Join-Path $paquete 'database'
                New-Item -ItemType Directory -Force -Path $databasePaquete | Out-Null
                Get-ChildItem -LiteralPath $_.FullName -Force |
                    Where-Object { $_.Name -notmatch '\.(sqlite|db)(-|$)' } |
                    ForEach-Object {
                        Copy-Item -LiteralPath $_.FullName -Destination $databasePaquete -Recurse -Force
                    }
            }
            elseif ($_.Name -eq 'public') {
                $publicPaquete = Join-Path $paquete 'public'
                New-Item -ItemType Directory -Force -Path $publicPaquete | Out-Null
                Get-ChildItem -LiteralPath $_.FullName -Force |
                    Where-Object { $_.Name -notin @('storage', 'hot') } |
                    ForEach-Object {
                        Copy-Item -LiteralPath $_.FullName -Destination $publicPaquete -Recurse -Force
                    }
            }
            elseif ($excluirDirectorios -notcontains $_.Name) {
                Copy-Item -LiteralPath $_.FullName -Destination $paquete -Recurse -Force
            }
        }
        elseif ($excluirArchivos -notcontains $_.Name -and $_.Extension -ne '.log') {
            Copy-Item -LiteralPath $_.FullName -Destination $paquete -Force
        }
    }

    $cacheBootstrap = Join-Path $paquete 'bootstrap\cache'
    if (Test-Path $cacheBootstrap) {
        Get-ChildItem -LiteralPath $cacheBootstrap -File -Force |
            Where-Object { $_.Name -ne '.gitignore' } |
            Remove-Item -Force
    }

    $composerArchivo = $composerComando.Source
    $env:PATH = "$phpRaiz;$env:PATH"
    $composerManifestPath = Join-Path $paquete 'composer.json'
    $composerManifest = Get-Content -LiteralPath $composerManifestPath -Raw | ConvertFrom-Json
    $composerManifest.config.'optimize-autoloader' = $false
    $composerJson = $composerManifest | ConvertTo-Json -Depth 100
    $utf8SinBOM = New-Object System.Text.UTF8Encoding -ArgumentList $false
    [System.IO.File]::WriteAllText($composerManifestPath, $composerJson, $utf8SinBOM)

    & $composerArchivo install --no-dev --no-scripts --no-interaction --prefer-dist "--working-dir=$paquete"
    if ($LASTEXITCODE -ne 0) {
        throw 'Falló composer install --no-dev al preparar el paquete.'
    }

    Copy-Item -LiteralPath (Join-Path $raiz 'composer.json') -Destination $composerManifestPath -Force

    foreach ($directorio in @(
        'storage\app\private',
        'storage\app\public',
        'storage\framework\cache\data',
        'storage\framework\sessions',
        'storage\framework\views',
        'storage\logs',
        'bootstrap\cache'
    )) {
        New-Item -ItemType Directory -Force -Path (Join-Path $paquete $directorio) | Out-Null
    }

    $phpConstruccion = Join-Path $phpRaiz 'php.exe'
    & $phpConstruccion (Join-Path $paquete 'artisan') package:discover --ansi
    if ($LASTEXITCODE -ne 0) {
        throw 'Falló artisan package:discover al preparar el paquete.'
    }

    & $phpConstruccion (Join-Path $paquete 'artisan') filament:upgrade
    if ($LASTEXITCODE -ne 0) {
        throw 'Falló artisan filament:upgrade al preparar el paquete.'
    }

    $runtimeDlls = Get-ChildItem -LiteralPath $phpRaiz -Filter '*.dll' -File
    if (-not (Test-Path (Join-Path $phpRaiz 'php.exe')) -or
        -not (Test-Path (Join-Path $phpRaiz 'ext'))) {
        throw 'El runtime seleccionado debe contener php.exe y la carpeta ext.'
    }

    Copy-Item -LiteralPath (Join-Path $phpRaiz 'php.exe') -Destination $runtime -Force
    Copy-Item -LiteralPath (Join-Path $phpRaiz 'ext') -Destination $runtime -Recurse -Force
    Copy-Item -LiteralPath $runtimeDlls.FullName -Destination $runtime -Force
    Copy-Item -LiteralPath (Join-Path $phpRaiz 'license.txt') -Destination $runtime -Force

    @'
; Configuración mínima del runtime PHP incluido en ERP ELEAM.
extension_dir="ext"
date.timezone=America/Santiago
memory_limit=256M
upload_max_filesize=20M
post_max_size=25M
max_execution_time=120
extension=curl
extension=fileinfo
extension=intl
extension=mbstring
extension=openssl
extension=pdo_sqlite
extension=sqlite3
extension=zip
'@ | Set-Content -LiteralPath (Join-Path $runtime 'php.ini') -Encoding ASCII

    $env:PHPRC = $runtime
    Remove-Item Env:\PHP_INI_SCAN_DIR -ErrorAction SilentlyContinue

    $runtimeModulos = @(& (Join-Path $runtime 'php.exe') -m)
    if ($LASTEXITCODE -ne 0) {
        throw 'El PHP empaquetado no pudo iniciar; revisa la distribución de PHP usada para construir.'
    }
    foreach ($modulo in @('curl', 'fileinfo', 'intl', 'mbstring', 'openssl', 'PDO', 'pdo_sqlite', 'zip')) {
        if ($runtimeModulos -notcontains $modulo) {
            throw "El runtime empaquetado no cargó la extensión requerida: $modulo"
        }
    }

    & $composerArchivo check-platform-reqs --no-dev "--working-dir=$paquete"
    if ($LASTEXITCODE -ne 0) {
        throw 'El paquete no cumple los requisitos de plataforma PHP.'
    }

    if (Test-Path $archivoZip) {
        Remove-Item -LiteralPath $archivoZip -Force
    }

    # tar.exe produce un ZIP estandar (rutas con /) y es mucho mas rapido que
    # Compress-Archive o ZipFile con miles de archivos.
    $tar = Join-Path $env:SystemRoot 'System32\tar.exe'
    if (Test-Path $tar) {
        & $tar -a -c -f $archivoZip -C $paquete .
        if ($LASTEXITCODE -ne 0) {
            throw "tar no pudo crear el paquete portable (codigo $LASTEXITCODE)."
        }
    }
    else {
        Add-Type -AssemblyName System.IO.Compression.FileSystem
        [System.IO.Compression.ZipFile]::CreateFromDirectory(
            $paquete,
            $archivoZip,
            [System.IO.Compression.CompressionLevel]::Fastest,
            $false
        )
    }

    Write-Host "Paquete portable creado: $archivoZip" -ForegroundColor Green
}
finally {
    if (Test-Path $temporal) {
        Remove-Item -LiteralPath $temporal -Recurse -Force
    }
}
