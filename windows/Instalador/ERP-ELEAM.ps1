# =============================================================================
# ERP ELEAM - Aplicacion de escritorio con bandeja del sistema
# =============================================================================
# Inicia el servidor PHP local, abre Edge en modo aplicacion y deja un icono
# en la bandeja para abrir, respaldar, revisar logs o detener el ERP.
#
# Uso instalado: acceso directo "ERP ELEAM".
# Prueba desde el repositorio:
#   powershell -ExecutionPolicy Bypass -File windows\Instalador\ERP-ELEAM.ps1 `
#       -DataDir "$env:TEMP\erp-eleam-datos" -SmokeTest
# =============================================================================

[CmdletBinding()]
param(
    [string]$DataDir = '',
    [int]$Port = 0,
    [switch]$NoBrowser,
    [switch]$SmokeTest,
    [switch]$KeepData,
    [string]$AdminEmail = '',
    [string]$AdminNombre = '',
    [string]$AdminApellido = '',
    [string]$AdminRut = ''
)

$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing
[System.Windows.Forms.Application]::EnableVisualStyles()

# -----------------------------------------------------------------------------
# Rutas: el lanzador puede ejecutarse desde el repositorio o desde la raiz
# instalada. En la instalacion, el codigo Laravel queda en <raiz>\app.
# -----------------------------------------------------------------------------
if (Test-Path (Join-Path $PSScriptRoot 'app\artisan')) {
    $script:AppDir = Join-Path $PSScriptRoot 'app'
    $script:RaizInstalada = $PSScriptRoot
}
else {
    $script:AppDir = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
    $script:RaizInstalada = Split-Path -Parent $script:AppDir
}

$script:AppDir = [System.IO.Path]::GetFullPath($script:AppDir)
$script:RaizInstalada = [System.IO.Path]::GetFullPath($script:RaizInstalada)

if (-not (Test-Path (Join-Path $script:AppDir 'artisan'))) {
    throw "No se encontro artisan en $($script:AppDir). El paquete esta incompleto."
}

if (-not $DataDir) {
    if ($env:ELEAM_DATA_DIR) {
        $DataDir = $env:ELEAM_DATA_DIR
    }
    else {
        $DataDir = Join-Path $env:LOCALAPPDATA 'ERP-ELEAM\data'
    }
}

$script:DataDir = [System.IO.Path]::GetFullPath($DataDir)
$script:Storage = Join-Path $script:DataDir 'storage'
$script:Backups = Join-Path $script:DataDir 'backups'
$script:Updates = Join-Path $script:DataDir 'updates'
$script:ConfigFile = Join-Path $script:DataDir 'config.json'
$script:PidFile = Join-Path $script:Storage 'app\erp-server.json'
$script:Log = Join-Path $script:Storage 'logs\serve-windows.log'
$script:ErrLog = Join-Path $script:Storage 'logs\serve-windows.err.log'
$script:Marker = Join-Path $script:Storage 'app\instalacion-completa'
$script:VersionFile = Join-Path $script:AppDir 'version.json'
$script:AdminEmail = $AdminEmail
$script:AdminNombre = $AdminNombre
$script:AdminApellido = $AdminApellido
$script:AdminRut = $AdminRut

if ($SmokeTest) {
    if (-not $script:AdminEmail) { $script:AdminEmail = 'smoke@eleam.local' }
    if (-not $script:AdminNombre) { $script:AdminNombre = 'Prueba' }
    if (-not $script:AdminApellido) { $script:AdminApellido = 'Automatica' }
    if (-not $script:AdminRut) { $script:AdminRut = '11111111-1' }
}

function Obtener-Version {
    if (Test-Path $script:VersionFile) {
        try {
            $info = Get-Content -LiteralPath $script:VersionFile -Raw | ConvertFrom-Json
            if ($info.version) { return [string]$info.version }
        }
        catch {
            # Un version.json ilegible no impide iniciar.
        }
    }

    return 'dev'
}

# -----------------------------------------------------------------------------
# PHP incluido (o el del sistema, solo para desarrollo)
# -----------------------------------------------------------------------------
$script:Php = Join-Path $script:AppDir 'runtime\php.exe'

if (Test-Path $script:Php) {
    $env:PHPRC = Join-Path $script:AppDir 'runtime'
    Remove-Item Env:\PHP_INI_SCAN_DIR -ErrorAction SilentlyContinue
    $env:PATH = "$(Join-Path $script:AppDir 'runtime');$env:PATH"
}
else {
    $comando = Get-Command php.exe -ErrorAction SilentlyContinue
    if (-not $comando) {
        throw 'No se encontro PHP. El paquete instalado debe incluir runtime\php.exe.'
    }
    $script:Php = $comando.Source
}

$env:ELEAM_DATA_DIR = $script:DataDir
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = Join-Path $script:DataDir 'database\database.sqlite'

function New-Directorios {
    foreach ($directorio in @(
        (Join-Path $script:DataDir 'database'),
        (Join-Path $script:Storage 'app\private'),
        (Join-Path $script:Storage 'app\public'),
        (Join-Path $script:Storage 'framework\cache\data'),
        (Join-Path $script:Storage 'framework\sessions'),
        (Join-Path $script:Storage 'framework\views'),
        (Join-Path $script:Storage 'logs'),
        $script:Backups,
        $script:Updates
    )) {
        New-Item -ItemType Directory -Force -Path $directorio | Out-Null
    }
}

function Invoke-Artisan {
    param([string[]]$Argumentos)

    $salida = & $script:Php (Join-Path $script:AppDir 'artisan') @Argumentos 2>&1

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

function Test-PuertoLibre {
    param([int]$Puerto)

    $listener = $null

    try {
        $listener = [System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Loopback, $Puerto)
        $listener.Start()
        return $true
    }
    catch {
        return $false
    }
    finally {
        if ($listener) {
            try { $listener.Stop() } catch { }
        }
    }
}

function Buscar-PuertoLibre {
    param([int]$Desde)

    for ($puerto = $Desde; $puerto -lt ($Desde + 50); $puerto++) {
        if (Test-PuertoLibre $puerto) { return $puerto }
    }

    throw "No se encontro un puerto libre entre $Desde y $($Desde + 49)."
}

function Guardar-Configuracion {
    param([int]$Puerto)

    $config = @{
        port = $Puerto
        version = (Obtener-Version)
        actualizado_en = (Get-Date).ToString('o')
    }

    $config | ConvertTo-Json | Set-Content -LiteralPath $script:ConfigFile -Encoding UTF8
}

function Leer-Servidor {
    if (-not (Test-Path $script:PidFile)) { return $null }

    try {
        $info = Get-Content -LiteralPath $script:PidFile -Raw | ConvertFrom-Json
        $proceso = Get-Process -Id ([int]$info.pid) -ErrorAction SilentlyContinue

        if ($proceso -and $proceso.ProcessName -eq 'php') {
            return [pscustomobject]@{
                pid = [int]$info.pid
                port = [int]$info.port
            }
        }
    }
    catch {
        # Un archivo de PID ilegible se reemplaza al iniciar de nuevo.
    }

    return $null
}

function Resolver-Puerto {
    $servidor = Leer-Servidor

    if ($servidor -and (Test-Erp $servidor.port)) {
        return $servidor.port
    }

    $deseado = $Port

    if ($deseado -le 0 -and (Test-Path $script:ConfigFile)) {
        try {
            $config = Get-Content -LiteralPath $script:ConfigFile -Raw | ConvertFrom-Json
            if ($config.port) { $deseado = [int]$config.port }
        }
        catch {
            # Se usa el puerto por defecto.
        }
    }

    if ($deseado -le 0) { $deseado = 8000 }

    if (Test-PuertoLibre $deseado) {
        Guardar-Configuracion $deseado
        return $deseado
    }

    $libre = Buscar-PuertoLibre $deseado
    Guardar-Configuracion $libre
    return $libre
}

function Iniciar-Servidor {
    param([int]$Puerto)

    $env:APP_URL = "http://127.0.0.1:$Puerto"

    $servidor = Leer-Servidor
    if ($servidor -and (Test-Erp $servidor.port)) {
        return
    }

    if (-not (Test-PuertoLibre $Puerto)) {
        throw "El puerto $Puerto esta ocupado por otro programa. Cierra ese programa o reinicia ERP ELEAM para elegir otro puerto."
    }

    $router = Join-Path $script:AppDir 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'

    if (-not (Test-Path $router)) {
        throw 'Faltan las dependencias de Composer. Reinstala ERP ELEAM.'
    }

    foreach ($archivo in @($script:Log, $script:ErrLog)) {
        if ((Test-Path $archivo) -and (Get-Item $archivo).Length -gt 5MB) {
            Remove-Item -LiteralPath $archivo -Force
        }
    }

    $proceso = Start-Process -FilePath $script:Php `
        -ArgumentList @('-S', "127.0.0.1:$Puerto", '..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php') `
        -WorkingDirectory (Join-Path $script:AppDir 'public') `
        -WindowStyle Hidden `
        -RedirectStandardOutput $script:Log `
        -RedirectStandardError $script:ErrLog `
        -PassThru

    @{
        pid = $proceso.Id
        port = $Puerto
        iniciado_en = (Get-Date).ToString('o')
    } | ConvertTo-Json | Set-Content -LiteralPath $script:PidFile -Encoding UTF8

    $limite = (Get-Date).AddSeconds(60)

    while ((Get-Date) -lt $limite -and -not (Test-Erp $Puerto)) {
        Start-Sleep -Milliseconds 500
    }

    if (-not (Test-Erp $Puerto)) {
        throw "El servidor no respondio en 60 segundos. Revisa $($script:ErrLog)."
    }
}

function Detener-Servidor {
    $servidor = Leer-Servidor

    if ($servidor) {
        Stop-Process -Id $servidor.pid -Force -ErrorAction SilentlyContinue
    }

    Remove-Item -LiteralPath $script:PidFile -Force -ErrorAction SilentlyContinue
}

function Abrir-Navegador {
    param([int]$Puerto)

    $url = "http://127.0.0.1:$Puerto/admin"
    $candidatos = @()

    $comando = Get-Command msedge.exe -ErrorAction SilentlyContinue
    if ($comando) { $candidatos += $comando.Source }

    if (${env:ProgramFiles(x86)}) {
        $candidatos += "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe"
    }

    if ($env:ProgramFiles) {
        $candidatos += "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe"
    }

    $edge = $candidatos | Where-Object { Test-Path $_ } | Select-Object -First 1

    if ($edge) {
        $perfil = Join-Path $script:Storage 'app\edge-profile'
        New-Item -ItemType Directory -Force -Path $perfil | Out-Null

        Start-Process -FilePath $edge -ArgumentList @(
            "--app=$url",
            "--user-data-dir=$perfil",
            '--no-first-run',
            '--no-default-browser-check',
            '--window-size=1440,900'
        )
    }
    else {
        Start-Process $url
    }
}

function Crear-Env {
    param([int]$Puerto)

    $origen = Join-Path $script:AppDir '.env.example'
    $contenido = Get-Content -LiteralPath $origen -Raw

    $clave = (& $script:Php -r "echo 'base64:'.base64_encode(random_bytes(32));").Trim()
    $backupPath = (Join-Path $script:DataDir 'backups').Replace('\', '/')

    $valores = [ordered]@{
        APP_NAME = '"ERP ELEAM"'
        APP_ENV = 'production'
        APP_DEBUG = 'false'
        APP_URL = "http://127.0.0.1:$Puerto"
        APP_KEY = $clave
        LOG_LEVEL = 'warning'
        DB_CONNECTION = 'sqlite'
        BACKUP_LOCAL_ENABLED = 'true'
        BACKUP_LOCAL_PATH = '"' + $backupPath + '"'
        BACKUP_LOCAL_RETENTION = '30'
    }

    foreach ($variable in $valores.Keys) {
        $patron = "(?m)^$([regex]::Escape($variable))=.*$"
        $linea = "$variable=$($valores[$variable])"

        if ($contenido -match $patron) {
            $contenido = [regex]::Replace($contenido, $patron, $linea)
        }
        else {
            $contenido += "`r`n$linea"
        }
    }

    $utf8SinBom = New-Object System.Text.UTF8Encoding -ArgumentList $false
    [System.IO.File]::WriteAllText((Join-Path $script:DataDir '.env'), $contenido, $utf8SinBom)
}

function Asegurar-StorageLink {
    $publicStorage = Join-Path $script:AppDir 'public\storage'
    $dataPublic = Join-Path $script:Storage 'app\public'

    if (Test-Path $publicStorage) { return }

    try {
        New-Item -ItemType Junction -Path $publicStorage -Target $dataPublic | Out-Null
    }
    catch {
        try {
            New-Item -ItemType SymbolicLink -Path $publicStorage -Target $dataPublic | Out-Null
        }
        catch {
            Write-Warning "No se pudo enlazar public\storage con los datos: $($_.Exception.Message)"
        }
    }
}

function New-ClaveSegura {
    return (& $script:Php -r "echo bin2hex(random_bytes(12));").Trim()
}

function Crear-Administrador {
    $email = $script:AdminEmail
    $nombre = $script:AdminNombre
    $apellido = $script:AdminApellido
    $rut = $script:AdminRut

    if (-not $email) {
        $form = New-Object System.Windows.Forms.Form
        $form.Text = 'ERP ELEAM - Primer inicio'
        $form.Size = New-Object System.Drawing.Size(470, 330)
        $form.StartPosition = 'CenterScreen'
        $form.FormBorderStyle = 'FixedDialog'
        $form.MaximizeBox = $false
        $form.MinimizeBox = $false
        $form.TopMost = $true

        $titulo = New-Object System.Windows.Forms.Label
        $titulo.Text = 'Crea la cuenta de administrador'
        $titulo.Font = New-Object System.Drawing.Font('Segoe UI', 13, [System.Drawing.FontStyle]::Bold)
        $titulo.AutoSize = $true
        $titulo.Location = New-Object System.Drawing.Point(20, 15)
        $form.Controls.Add($titulo)

        $ayuda = New-Object System.Windows.Forms.Label
        $ayuda.Text = 'Estos datos quedan solo en este computador. La contrasena se muestra una vez.'
        $ayuda.AutoSize = $false
        $ayuda.Size = New-Object System.Drawing.Size(410, 34)
        $ayuda.Location = New-Object System.Drawing.Point(20, 48)
        $form.Controls.Add($ayuda)

        $campos = @(
            @{ Etiqueta = 'Correo'; Clave = 'Email'; Top = 92 },
            @{ Etiqueta = 'Nombre'; Clave = 'Nombre'; Top = 124 },
            @{ Etiqueta = 'Apellido'; Clave = 'Apellido'; Top = 156 },
            @{ Etiqueta = 'RUT (12345678-5)'; Clave = 'Rut'; Top = 188 }
        )

        $script:FormAdmin = @{}

        foreach ($campo in $campos) {
            $label = New-Object System.Windows.Forms.Label
            $label.Text = $campo.Etiqueta
            $label.AutoSize = $true
            $label.Location = New-Object System.Drawing.Point(20, ($campo.Top + 3))
            $form.Controls.Add($label)

            $texto = New-Object System.Windows.Forms.TextBox
            $texto.Location = New-Object System.Drawing.Point(150, $campo.Top)
            $texto.Size = New-Object System.Drawing.Size(290, 22)
            $form.Controls.Add($texto)

            $script:FormAdmin[$campo.Clave] = $texto
        }

        $boton = New-Object System.Windows.Forms.Button
        $boton.Text = 'Crear administrador'
        $boton.Size = New-Object System.Drawing.Size(150, 30)
        $boton.Location = New-Object System.Drawing.Point(290, 235)
        $boton.Add_Click({
            $correo = $script:FormAdmin['Email'].Text.Trim()
            $nom = $script:FormAdmin['Nombre'].Text.Trim()
            $ape = $script:FormAdmin['Apellido'].Text.Trim()
            $documento = $script:FormAdmin['Rut'].Text.Trim()

            if ($correo -notmatch '^[^@\s]+@[^@\s]+\.[^@\s]+$') {
                [System.Windows.Forms.MessageBox]::Show('Escribe un correo valido.', 'ERP ELEAM')
                return
            }

            if (-not $nom -or -not $ape) {
                [System.Windows.Forms.MessageBox]::Show('Completa nombre y apellido.', 'ERP ELEAM')
                return
            }

            if ($documento -notmatch '^\d{7,8}-[\dkK]$') {
                [System.Windows.Forms.MessageBox]::Show('El RUT debe tener el formato 12345678-5.', 'ERP ELEAM')
                return
            }

            $script:AdminEmail = $correo
            $script:AdminNombre = $nom
            $script:AdminApellido = $ape
            $script:AdminRut = $documento
            $form.DialogResult = [System.Windows.Forms.DialogResult]::OK
            $form.Close()
        })
        $form.Controls.Add($boton)

        $cancelar = New-Object System.Windows.Forms.Button
        $cancelar.Text = 'Cancelar'
        $cancelar.Size = New-Object System.Drawing.Size(100, 30)
        $cancelar.Location = New-Object System.Drawing.Point(175, 235)
        $cancelar.Add_Click({
            $form.DialogResult = [System.Windows.Forms.DialogResult]::Cancel
            $form.Close()
        })
        $form.Controls.Add($cancelar)

        if ($form.ShowDialog() -ne [System.Windows.Forms.DialogResult]::OK) {
            throw 'El primer inicio se cancelo antes de crear el administrador.'
        }
    }

    $clave = New-ClaveSegura
    $resultado = Invoke-Artisan @(
        'eleam:admin-crear',
        $script:AdminEmail,
        "--clave=$clave",
        "--nombre=$($script:AdminNombre)",
        "--apellido=$($script:AdminApellido)",
        "--rut=$($script:AdminRut)"
    )

    if ($resultado.Codigo -ne 0) {
        throw "No se pudo crear el administrador.`r`n$($resultado.Salida)"
    }

    $credenciales = "Usuario: $($script:AdminEmail)`r`nClave: $clave"

    if (-not $SmokeTest) {
        $form = New-Object System.Windows.Forms.Form
        $form.Text = 'ERP ELEAM - Credenciales del administrador'
        $form.Size = New-Object System.Drawing.Size(520, 260)
        $form.StartPosition = 'CenterScreen'
        $form.FormBorderStyle = 'FixedDialog'
        $form.MaximizeBox = $false
        $form.MinimizeBox = $false
        $form.TopMost = $true

        $aviso = New-Object System.Windows.Forms.Label
        $aviso.Text = 'Guarda esta contrasena. No se volvera a mostrar.'
        $aviso.Font = New-Object System.Drawing.Font('Segoe UI', 10, [System.Drawing.FontStyle]::Bold)
        $aviso.AutoSize = $true
        $aviso.Location = New-Object System.Drawing.Point(20, 15)
        $form.Controls.Add($aviso)

        $caja = New-Object System.Windows.Forms.TextBox
        $caja.Multiline = $true
        $caja.ReadOnly = $true
        $caja.Font = New-Object System.Drawing.Font('Consolas', 11)
        $caja.Text = $credenciales
        $caja.Location = New-Object System.Drawing.Point(20, 45)
        $caja.Size = New-Object System.Drawing.Size(460, 80)
        $form.Controls.Add($caja)

        $copiar = New-Object System.Windows.Forms.Button
        $copiar.Text = 'Copiar'
        $copiar.Size = New-Object System.Drawing.Size(100, 30)
        $copiar.Location = New-Object System.Drawing.Point(380, 140)
        $copiar.Add_Click({
            [System.Windows.Forms.Clipboard]::SetText($credenciales)
            [System.Windows.Forms.MessageBox]::Show('Credenciales copiadas al portapapeles.', 'ERP ELEAM')
        })
        $form.Controls.Add($copiar)

        $cerrar = New-Object System.Windows.Forms.Button
        $cerrar.Text = 'Ya las guarde'
        $cerrar.Size = New-Object System.Drawing.Size(120, 30)
        $cerrar.Location = New-Object System.Drawing.Point(250, 140)
        $cerrar.Add_Click({ $form.Close() })
        $form.Controls.Add($cerrar)

        $form.ShowDialog() | Out-Null
    }
    else {
        Write-Host $credenciales
    }

    Set-Content -LiteralPath $script:Marker -Value (Get-Date).ToString('o') -Encoding ASCII
}

function Inicializar-Datos {
    param([int]$Puerto)

    New-Directorios

    $envArchivo = Join-Path $script:DataDir '.env'
    $nuevo = -not (Test-Path $envArchivo)

    if ($nuevo) {
        Crear-Env $Puerto
    }
    else {
        $contenido = Get-Content -LiteralPath $envArchivo -Raw
        if ($contenido -notmatch '(?m)^APP_KEY=base64:.+$') {
            $resultado = Invoke-Artisan @('key:generate', '--force')
            if ($resultado.Codigo -ne 0) {
                throw "No se pudo generar APP_KEY.`r`n$($resultado.Salida)"
            }
        }
    }

    $baseDatos = Join-Path $script:DataDir 'database\database.sqlite'
    if (-not (Test-Path $baseDatos)) {
        New-Item -ItemType File -Path $baseDatos -Force | Out-Null
    }

    $argumentos = @('migrate', '--force')
    if ($nuevo) { $argumentos += '--seed' }

    $migracion = Invoke-Artisan $argumentos
    if ($migracion.Codigo -ne 0) {
        throw "No se pudo preparar la base de datos.`r`n$($migracion.Salida)"
    }

    Asegurar-StorageLink

    if (-not (Test-Path $script:Marker)) {
        Crear-Administrador
    }

    if (-not $SmokeTest) {
        $scriptTarea = Join-Path $script:AppDir 'windows\Instalador\TareaProgramada.ps1'
        if (Test-Path $scriptTarea) {
            try {
                & $scriptTarea -Accion Instalar -AppDir $script:AppDir -DataDir $script:DataDir | Out-Null
            }
            catch {
                Write-Warning "No se pudo programar la tarea automatica: $($_.Exception.Message)"
            }
        }
    }
}

# -----------------------------------------------------------------------------
# Bandeja del sistema
# -----------------------------------------------------------------------------
$script:Notify = $null
$script:PuertoActivo = 0

function Avisar {
    param([string]$Titulo, [string]$Texto, [string]$Tipo = 'Info')

    if ($script:Notify) {
        $script:Notify.BalloonTipTitle = $Titulo
        $script:Notify.BalloonTipText = $Texto
        $script:Notify.BalloonTipIcon = [System.Windows.Forms.ToolTipIcon]::$Tipo
        $script:Notify.ShowBalloonTip(4000)
    }
}

function Abrir-Erp {
    try {
        Iniciar-Servidor $script:PuertoActivo
        Abrir-Navegador $script:PuertoActivo
    }
    catch {
        [System.Windows.Forms.MessageBox]::Show($_.Exception.Message, 'ERP ELEAM', 'OK', 'Error')
    }
}

function Respaldar-Ahora {
    try {
        $resultado = Invoke-Artisan @(
            'eleam:backup-local',
            "--destino=$($script:Backups)",
            '--retencion=30'
        )

        if ($resultado.Codigo -eq 0) {
            Avisar 'Respaldo completado' 'La base, los archivos y la configuracion quedaron respaldados.'
        }
        else {
            [System.Windows.Forms.MessageBox]::Show($resultado.Salida, 'Error al respaldar', 'OK', 'Error')
        }
    }
    catch {
        [System.Windows.Forms.MessageBox]::Show($_.Exception.Message, 'Error al respaldar', 'OK', 'Error')
    }
}

function Crear-Bandeja {
    param([int]$Puerto)

    $script:Notify = New-Object System.Windows.Forms.NotifyIcon
    $icono = Join-Path $script:RaizInstalada 'ERP-ELEAM.ico'
    if (-not (Test-Path $icono)) { $icono = Join-Path $script:AppDir 'public\favicon.ico' }

    if (Test-Path $icono) {
        $script:Notify.Icon = New-Object System.Drawing.Icon($icono)
    }
    else {
        $script:Notify.Icon = [System.Drawing.SystemIcons]::Application
    }

    $script:Notify.Text = "ERP ELEAM $(Obtener-Version) - puerto $Puerto"
    $script:Notify.Visible = $true

    $menu = New-Object System.Windows.Forms.ContextMenuStrip

    $abrir = New-Object System.Windows.Forms.ToolStripMenuItem
    $abrir.Text = 'Abrir ERP'
    $abrir.Font = New-Object System.Drawing.Font($menu.Font, [System.Drawing.FontStyle]::Bold)
    $abrir.Add_Click({ Abrir-Erp })
    $menu.Items.Add($abrir) | Out-Null

    $menu.Items.Add((New-Object System.Windows.Forms.ToolStripSeparator)) | Out-Null

    $respaldar = New-Object System.Windows.Forms.ToolStripMenuItem
    $respaldar.Text = 'Respaldar ahora'
    $respaldar.Add_Click({ Respaldar-Ahora })
    $menu.Items.Add($respaldar) | Out-Null

    $verRespaldos = New-Object System.Windows.Forms.ToolStripMenuItem
    $verRespaldos.Text = 'Ver carpeta de respaldos'
    $verRespaldos.Add_Click({ Start-Process explorer.exe $script:Backups })
    $menu.Items.Add($verRespaldos) | Out-Null

    $verDatos = New-Object System.Windows.Forms.ToolStripMenuItem
    $verDatos.Text = 'Abrir carpeta de datos'
    $verDatos.Add_Click({ Start-Process explorer.exe $script:DataDir })
    $menu.Items.Add($verDatos) | Out-Null

    $verLogs = New-Object System.Windows.Forms.ToolStripMenuItem
    $verLogs.Text = 'Ver registro de errores'
    $verLogs.Add_Click({
        $archivo = $script:ErrLog
        if (-not (Test-Path $archivo) -or (Get-Item $archivo).Length -eq 0) {
            $archivo = Join-Path $script:Storage 'logs\laravel.log'
        }
        if (Test-Path $archivo) { Start-Process notepad.exe $archivo }
    })
    $menu.Items.Add($verLogs) | Out-Null

    $actualizar = New-Object System.Windows.Forms.ToolStripMenuItem
    $actualizar.Text = 'Actualizar ERP...'
    $actualizar.Add_Click({
        $updater = Join-Path $script:RaizInstalada 'Actualizar-ERP.ps1'
        if (-not (Test-Path $updater)) {
            $updater = Join-Path $script:AppDir 'windows\Instalador\Actualizar-ERP.ps1'
        }
        if (Test-Path $updater) {
            Start-Process -FilePath 'powershell.exe' -ArgumentList @(
                '-NoProfile',
                '-ExecutionPolicy', 'Bypass',
                '-File', $updater,
                '-AppDir', $script:AppDir,
                '-DataDir', $script:DataDir
            )
        }
        else {
            [System.Windows.Forms.MessageBox]::Show('No se encontro el actualizador.', 'ERP ELEAM', 'OK', 'Warning')
        }
    })
    $menu.Items.Add($actualizar) | Out-Null

    $menu.Items.Add((New-Object System.Windows.Forms.ToolStripSeparator)) | Out-Null

    $detener = New-Object System.Windows.Forms.ToolStripMenuItem
    $detener.Text = 'Detener servidor'
    $detener.Add_Click({
        Detener-Servidor
        Avisar 'Servidor detenido' 'El ERP sigue en la bandeja. Usa Abrir ERP para iniciarlo de nuevo.'
    })
    $menu.Items.Add($detener) | Out-Null

    $salir = New-Object System.Windows.Forms.ToolStripMenuItem
    $salir.Text = 'Salir'
    $salir.Add_Click({
        $script:Notify.Visible = $false
        Detener-Servidor
        [System.Windows.Forms.Application]::ExitThread()
    })
    $menu.Items.Add($salir) | Out-Null

    $script:Notify.ContextMenuStrip = $menu
    $script:Notify.Add_DoubleClick({ Abrir-Erp })
}

# -----------------------------------------------------------------------------
# Prueba automatica del paquete (sin interfaz)
# -----------------------------------------------------------------------------
function Ejecutar-SmokeTest {
    param([int]$Puerto)

    Write-Host "ERP ELEAM: prueba de humo sobre $($script:AppDir)"
    Write-Host "Datos temporales: $($script:DataDir)"

    try {
        Iniciar-Servidor $Puerto

        if (-not (Test-Erp $Puerto)) {
            throw 'El servidor no respondio en /up.'
        }

        $respuesta = Invoke-WebRequest -Uri "http://127.0.0.1:$Puerto/admin/login" -UseBasicParsing -TimeoutSec 10
        if ($respuesta.StatusCode -ne 200) {
            throw "El login respondio $($respuesta.StatusCode)."
        }

        $respaldo = Invoke-Artisan @(
            'eleam:backup-local',
            "--destino=$($script:Backups)",
            '--retencion=0'
        )

        if ($respaldo.Codigo -ne 0) {
            throw "Fallo el respaldo: $($respaldo.Salida)"
        }

        $carpeta = Get-ChildItem -LiteralPath $script:Backups -Directory |
            Sort-Object LastWriteTime -Descending |
            Select-Object -First 1

        if (-not $carpeta) { throw 'No se creo la carpeta de respaldo.' }

        $verificacion = Invoke-Artisan @('eleam:verificar-backup', $carpeta.FullName)

        if ($verificacion.Codigo -ne 0) {
            throw "Fallo la verificacion del respaldo: $($verificacion.Salida)"
        }

        $envPrueba = Join-Path $script:DataDir '.env'
        if (-not (Test-Path $envPrueba)) { throw 'No se creo el .env en la carpeta de datos.' }
        if (-not (Test-Path (Join-Path $script:DataDir 'database\database.sqlite'))) {
            throw 'La base SQLite no quedo en la carpeta de datos.'
        }

        Write-Host 'SMOKE OK: servidor, login, base en data, respaldo y verificacion correctos.' -ForegroundColor Green
        return $true
    }
    catch {
        Write-Host "SMOKE FALLO: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
    finally {
        Detener-Servidor
    }
}

# -----------------------------------------------------------------------------
# Arranque
# -----------------------------------------------------------------------------
New-Directorios
$puerto = Resolver-Puerto

try {
    Inicializar-Datos $puerto
}
catch {
    if ($SmokeTest) {
        Write-Host "SMOKE FALLO: $($_.Exception.Message)" -ForegroundColor Red
    }
    else {
        [System.Windows.Forms.MessageBox]::Show($_.Exception.Message, 'ERP ELEAM - Error de inicio', 'OK', 'Error')
    }
    exit 1
}

if ($SmokeTest) {
    $ok = Ejecutar-SmokeTest $puerto

    if (-not $KeepData) {
        Remove-Item -LiteralPath $script:DataDir -Recurse -Force -ErrorAction SilentlyContinue
    }

    if ($ok) { exit 0 } else { exit 1 }
}

$script:PuertoActivo = $puerto
$env:APP_URL = "http://127.0.0.1:$puerto"

try {
    Iniciar-Servidor $puerto
}
catch {
    [System.Windows.Forms.MessageBox]::Show($_.Exception.Message, 'ERP ELEAM - Error de inicio', 'OK', 'Error')
    exit 1
}

if (-not $NoBrowser) {
    Abrir-Navegador $puerto
}

Crear-Bandeja $puerto
Avisar 'ERP ELEAM' "Listo en http://127.0.0.1:$puerto/admin. Usa el icono de la bandeja para abrirlo o respaldar."

try {
    [System.Windows.Forms.Application]::Run()
}
finally {
    Detener-Servidor
}
