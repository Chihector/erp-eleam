# Instalación y uso en Windows — ERP ELEAM

Esta guía describe la instalación de escritorio para **un PC con Windows 10/11
x64**. La aplicación incluye su propio runtime PHP; no hace falta instalar PHP,
Composer, Node ni un servidor web.

## 1. Antes de instalar

- Windows 10 u 11 de 64 bits.
- Espacio libre suficiente para el programa y los datos (el paquete ronda los
  500 MB descomprimido; los adjuntos y respaldos ocupan espacio adicional).
- Microsoft Edge para abrir la interfaz en modo aplicación. Si no está, se usa
  el navegador predeterminado.
- No se requieren permisos de administrador.
- Cierra cualquier instalación anterior de ERP ELEAM antes de actualizar.

> La instalación es para **un solo PC y una base local**. No está pensada para
> que varios equipos se conecten a la vez. Para uso multiusuario se necesita el
> despliegue web con PostgreSQL y HTTPS.

## 2. Instalar

1. Ejecuta `ERP-ELEAM-Setup-0.2.0.exe`.
2. Acepta el aviso de Windows si aparece. El ejecutable aún no tiene firma
   digital, así que SmartScreen puede mostrar “Windows protegió tu PC”. Solo
   continúa si el archivo vino por un canal confiable.
3. Elige si quieres un acceso directo en el escritorio.
4. Finaliza el asistente y deja marcada la opción **Abrir ERP ELEAM**.

El programa se instala en:

```text
%LOCALAPPDATA%\Programs\ERP-ELEAM
```

Los datos **no** se guardan dentro de esa carpeta. Se guardan en:

```text
%LOCALAPPDATA%\ERP-ELEAM\data
```

Esa separación permite actualizar o desinstalar el programa sin borrar la base,
los adjuntos ni los respaldos.

## 3. Primer inicio

En el primer arranque, ERP ELEAM:

1. Crea la carpeta de datos y el archivo `.env`.
2. Genera una clave de aplicación.
3. Crea la base SQLite y ejecuta las migraciones.
4. Pide los datos del administrador: correo, nombre, apellido y RUT.
5. Muestra una contraseña aleatoria **una sola vez**.
6. Abre la interfaz en `http://127.0.0.1:<puerto>/admin`.

Guarda la contraseña en un lugar seguro antes de cerrar el aviso. Si se pierde,
se puede restablecer desde una consola con:

```powershell
php artisan eleam:admin-crear admin@eleam.cl
```

Para usar ese comando en la instalación, ejecútalo desde la carpeta del programa
con el PHP incluido y las variables de datos:

```powershell
$app = "$env:LOCALAPPDATA\Programs\ERP-ELEAM\app"
$data = "$env:LOCALAPPDATA\ERP-ELEAM\data"
$env:ELEAM_DATA_DIR = $data
$env:DB_CONNECTION = "sqlite"
$env:DB_DATABASE = "$data\database\database.sqlite"
& "$app\runtime\php.exe" "$app\artisan" eleam:admin-crear admin@eleam.cl
```

## 4. Uso diario

Al abrir ERP ELEAM queda un icono en la bandeja del sistema (junto al reloj).
Haz clic derecho para:

- **Abrir ERP:** inicia el servidor si está detenido y abre la interfaz.
- **Respaldar ahora:** crea un respaldo inmediato.
- **Ver carpeta de respaldos:** abre el explorador en los respaldos.
- **Abrir carpeta de datos:** muestra la base, los adjuntos y los logs.
- **Ver registro de errores:** abre el log más reciente.
- **Actualizar ERP:** inicia el actualizador.
- **Detener servidor:** apaga el servidor sin cerrar la bandeja.
- **Salir:** detiene el servidor y cierra la aplicación.

El servidor escucha solo en `127.0.0.1`; no se expone a la red local ni a
internet.

## 5. Respaldos

Los respaldos se guardan en:

```text
%LOCALAPPDATA%\ERP-ELEAM\data\backups
```

Cada respaldo contiene:

- `database.sqlite` (o `database.dump` en PostgreSQL).
- `storage.zip` con los archivos adjuntos.
- `env-sanitized.txt` con la configuración sin secretos.
- `manifest.json` con fecha, motor, versión y hashes SHA-256.

La tarea programada de Windows ejecuta el scheduler cada minuto y el respaldo
local diario a las 02:30. Se conservan 30 respaldos por defecto.

### Verificar un respaldo

```powershell
$app = "$env:LOCALAPPDATA\Programs\ERP-ELEAM\app"
$data = "$env:LOCALAPPDATA\ERP-ELEAM\data"
$env:ELEAM_DATA_DIR = $data
$env:DB_CONNECTION = "sqlite"
$env:DB_DATABASE = "$data\database\database.sqlite"
& "$app\runtime\php.exe" "$app\artisan" eleam:verificar-backup "$data\backups\2026-11-04_02-30-00"
```

La verificación restaura la base en una ubicación temporal y comprueba los
hashes. No modifica la base en uso.

### Restaurar un respaldo

1. En la bandeja, elige **Salir** para detener el servidor.
2. Copia la carpeta de datos actual como resguardo:
   `%LOCALAPPDATA%\ERP-ELEAM\data` → por ejemplo `data-antes-de-restaurar`.
3. Restaura `database.sqlite` desde la carpeta del respaldo a
   `%LOCALAPPDATA%\ERP-ELEAM\data\database\database.sqlite`.
4. Descomprime `storage.zip` dentro de
   `%LOCALAPPDATA%\ERP-ELEAM\data\storage`.
5. Abre ERP ELEAM de nuevo y comprueba trabajadores, liquidaciones y adjuntos.
6. Verifica el respaldo restaurado con `eleam:verificar-backup` antes de seguir
   operando.

No restaures el archivo `env-sanitized.txt` como `.env`: es una copia de
referencia sin secretos. El `.env` operativo se conserva en la carpeta de datos.

Copia los respaldos a un disco externo o a la nube corporativa. Un respaldo en
el mismo equipo no protege contra robo, incendio o fallo del disco.

## 6. Actualizar

1. Descarga el paquete de actualización `.zip`.
2. Abre **Actualizar ERP** desde la bandeja o ejecuta
   `%LOCALAPPDATA%\Programs\ERP-ELEAM\Actualizar-ERP.cmd`.
3. Selecciona el ZIP y confirma.

El actualizador:

1. Detiene el servidor.
2. Crea un respaldo y lo verifica.
3. Extrae el paquete y comprueba que tenga `artisan`, `vendor`, `runtime` y
   `public`.
4. Reemplaza solo la carpeta `app`; los datos quedan intactos.
5. Ejecuta `optimize:clear`, migraciones, caché de vistas y optimización de
   Filament.
6. Prueba `/up` y el login.
7. Si algo falla, restaura la versión anterior y la vuelve a iniciar.

La versión anterior se conserva en
`%LOCALAPPDATA%\ERP-ELEAM\data\updates` para una reversa manual.

## 7. Desinstalar

Desde **Configuración → Aplicaciones → ERP ELEAM → Desinstalar**.

El desinstalador detiene el servidor y elimina la tarea programada. Al final
pregunta si quieres borrar también la base, los adjuntos y los respaldos:

- **No:** conserva `%LOCALAPPDATA%\ERP-ELEAM\data`.
- **Sí:** elimina esa carpeta completa. Esta acción no se puede deshacer.

## 8. Problemas frecuentes

| Síntoma | Qué revisar |
| --- | --- |
| El puerto está ocupado | ERP ELEAM elige otro puerto automáticamente; revisa `config.json` y los logs. |
| No abre la interfaz | Revisa `data\storage\logs\serve-windows.err.log` y `laravel.log`. |
| SmartScreen bloquea el instalador | El ejecutable no está firmado; verifica el origen y, si corresponde, usa “Más información → Ejecutar de todas formas”. |
| No se ejecuta la tarea programada | Ejecuta `TareaProgramada.ps1 -Accion Estado` y revisa el Programador de tareas. |
| El respaldo falla | Comprueba espacio libre y permisos de escritura en `data\backups`; revisa `storage\logs`. |
| Se perdió la contraseña | Restablécela con `eleam:admin-crear` usando el PHP incluido. |

## 9. Construir el instalador (desarrollo)

Requisitos: PHP 8.2+, Composer, Node.js/npm e Inno Setup 6.

```powershell
powershell -ExecutionPolicy Bypass -File windows\Instalador\Preparar-Instalador.ps1 `
    -Version 0.2.0
```

Si ya existe un paquete portable extraído y quieres evitar volver a generarlo:

```powershell
powershell -ExecutionPolicy Bypass -File windows\Instalador\Preparar-Instalador.ps1 `
    -Version 0.2.0 `
    -PayloadDir "C:\ruta\ERP-ELEAM"
```

El resultado queda en `dist\ERP-ELEAM-Setup-0.2.0.exe`.

Para generar solo el paquete de aplicación que usa el actualizador:

```powershell
powershell -ExecutionPolicy Bypass -File windows\Instalador\Preparar-Instalador.ps1 `
    -Version 0.2.0 -SoloPaquete
```

Prueba siempre el instalador en un PC Windows limpio antes de distribuirlo.

## 10. Límites y seguridad

- No expongas el puerto local a internet ni a la red corporativa.
- No uses la instalación de escritorio como servidor multiusuario.
- Mantén el equipo con antivirus, actualizaciones y acceso físico restringido.
- No copies bases con datos reales a equipos personales ni a repositorios.
- La firma digital del ejecutable y la revisión legal/contable siguen pendientes.
- Para producción web se requiere PostgreSQL, HTTPS, secretos propios, respaldos
  externos y monitoreo; consulta la hoja de ruta.
