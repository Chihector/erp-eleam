# Probar ERP ELEAM localmente en Windows

## Requisitos

- PHP 8.2 o superior, con `pdo_sqlite`, `mbstring`, `xml`, `curl` e `intl`.
- Composer, Node.js y npm.

Este proyecto admite PHP `^8.2`. La revisión se ejecutó con PHP 8.2.12; no hace
falta actualizar la instalación global de PHP para probar esta versión del ERP.

## Preparación inicial

Abre PowerShell en la carpeta del proyecto y ejecuta:

```powershell
composer install
if (!(Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
if (!(Test-Path database\database.sqlite)) {
    New-Item -ItemType File database\database.sqlite | Out-Null
}
php artisan migrate --seed
npm install
npm run build
php artisan eleam:admin-crear admin@eleam.cl --nombre=Administrador --apellido=Local --rut=11111111-1
```

El último comando genera una contraseña aleatoria y la muestra una sola vez;
guárdala. Ejecutarlo de nuevo restablece la contraseña de ese administrador.
No publiques la contraseña ni uses cuentas de ejemplo en un servidor accesible
por otras personas.

Después de sembrar la base, actualiza los parámetros legales del período:

```powershell
php artisan eleam:parametros-actualizar
```

El comando consulta UF y UTM en mindicador.cl y toma el sueldo mínimo de la
tabla de vigencia. Los topes y el SIS se conservan de la configuración. Revisa
el resultado en **Configuración > Parámetros Legales** y marca el período como
verificado solo después de contrastarlo con fuentes oficiales:

```powershell
php artisan eleam:parametros-actualizar --mes=10 --anio=2026 --verificar --usuario=admin@eleam.cl
```

## Cargar escenario ficticio para recorrer los módulos

Para poner datos de demostración en la base local (sin borrar otros datos),
ejecuta:

```powershell
php artisan db:seed --class=DemoOperacionalSeeder
```

Es idempotente para el escenario demo y crea un trabajador identificado como
`DEMO / NO ES PERSONA REAL`, con RUT de fixture `12345678-5`, contrato
indefinido, sueldo de ejemplo de $750.000 y jornada de 42 horas semanales,
turnos, marcación, vacaciones,
licencia, ingreso, gasto, liquidación y finiquito en borrador. Los parámetros
legales existentes se reutilizan; si falta el del período actual, usa los
valores de referencia del seeder de parámetros, que quedan **sin verificar** y
deben actualizarse con `eleam:parametros-actualizar`. Los montos permiten probar
pantallas y cálculos, pero no representan datos de un ELEAM ni se deben usar
para pagos reales. No uses este RUT de prueba como identificador de una persona.

## Iniciar y entrar

Inicia el servidor local:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Abre en el mismo computador:

- Aplicación: <http://127.0.0.1:8000/>
- Panel / inicio de sesión: <http://127.0.0.1:8000/admin>

Entra con el correo y la contraseña que generó el comando anterior. Esta
configuración solo escucha en el propio computador; no es accesible desde el
teléfono ni desde otros equipos de la red.
Para detenerlo, vuelve a la ventana de PowerShell del servidor y presiona
`Ctrl+C`.

### Abrir como aplicación en Windows

Si no quieres escribir comandos cada vez, usa el lanzador incluido:

1. Doble clic en `windows\Iniciar-ERP.bat`.
2. El script busca PHP, levanta el servidor, espera a que `/up` responda y abre
   Edge en modo aplicación con un perfil dedicado en
   `storage\app\edge-profile`.
3. Para cerrar el servidor, doble clic en `windows\Detener-ERP.bat`. Esto no
   cierra las ventanas de Edge; solo detiene el proceso PHP.

El lanzador no instala nada ni reemplaza el servidor web de producción. Si
prefieres el navegador normal, abre <http://127.0.0.1:8000/admin> después de
iniciar el servidor.

## Verificaciones

```powershell
php artisan test
php artisan route:list --path=admin
```

Las pruebas usan SQLite en memoria. La suite completa debe quedar en verde; el
resultado vigente se registra en [HOJA_DE_RUTA.md](HOJA_DE_RUTA.md). La base
SQLite local conserva los datos de prueba manual entre reinicios. No ejecutes
`php artisan migrate:fresh` si quieres conservarlos: ese comando elimina todas
las tablas y sus datos.

### Probar con PostgreSQL

Si tienes PostgreSQL disponible, crea una base **solo para pruebas** y ejecuta:

```powershell
$env:DB_CONNECTION="pgsql"
$env:DB_HOST="127.0.0.1"
$env:DB_PORT="5432"
$env:DB_DATABASE="eleam_test"
$env:DB_USERNAME="eleam"
$env:DB_PASSWORD="tu-clave"

php artisan migrate:fresh --force
vendor/bin/phpunit -c phpunit.pgsql.xml
```

`phpunit.pgsql.xml` ya trae valores por defecto para CI. No apuntes
`DB_DATABASE` a la base de producción: `migrate:fresh` borra todas las tablas.

La suite PostgreSQL incluye un ensayo real de respaldo: genera un `pg_dump`,
lo restaura en una base temporal y compara tablas y migraciones. Necesita
`pg_dump` y `pg_restore` en el PATH; si están instalados en otra ruta, define
`PG_DUMP_PATH` y `PG_RESTORE_PATH`. El comando `backup:google-drive` también
usa `pg_dump` cuando `DB_CONNECTION=pgsql`.

La QA visual local puede repetirse iniciando sesión y recorriendo el panel en el
navegador; las pruebas automatizadas de `SeguridadTest` ya verifican los 403 y
el acceso al PDF por rol.
