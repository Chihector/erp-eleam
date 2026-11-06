# Handoff para continuar ERP ELEAM con otra IA

Fecha de actualización: 2026-11-04

## Resumen para retomar

ERP ELEAM es una aplicación Laravel 12 / Filament 5 para la operación de un
ELEAM en Chile. Tiene módulos de trabajadores, contratos, turnos, asistencia,
vacaciones, licencias, finanzas, remuneraciones y finiquitos.

El proyecto está **unificado en una sola rama** (`master` en GitHub).
La copia de trabajo actual está en
`C:\Users\hecto\OneDrive\Escritorio\Proyectos\Erp eleam`. Los cambios de las
revisiones de octubre/noviembre **están sin commit ni push** en el árbol de
trabajo; la última línea base publicada es la etiqueta `v0.1.0`.

## Estado de código y entorno local

- Repositorio remoto: `https://github.com/chicohector953/Erp-eleam.git`.
- Rama: `master` (la publicación en `origin/master` está **atrasada**: hay
  cambios sin commit ni push de esta revisión).
- Etiqueta publicada: `v0.1.0`.
- PHP local: 8.2.12; cumple el requisito `^8.2` y tiene `pdo_pgsql`.
- Dependencias instaladas en `vendor/`, incluido `google/apiclient`.
- Frontend instalado y assets compilados en `public/build/`.
- Base local: SQLite; migraciones aplicadas, incluidas `notifications` (JSON),
  los campos P0 y el soft delete de liquidaciones.
- Suite SQLite: **128 pruebas aprobadas, 1 omitida y 487 aserciones**
  (2026-11-04). La omitida es el ensayo de `pg_dump`, exclusivo de PostgreSQL.
- Suite PostgreSQL 18.3 nativo en Windows: **129 pruebas y 493 aserciones**
  (2026-11-04), sin omisiones. Configuración: `phpunit.pgsql.xml`.
- PostgreSQL de ensayo: base `eleam_staging` en PostgreSQL 18.3, **27
  migraciones** aplicadas (2026-11-04). Parámetros de noviembre de 2026 son de
  ensayo, no valores legales vigentes.
- `.env` local y `database/database.sqlite` son datos locales ignorados por Git.
- Administrador local: `admin@eleam.cl`. No hay contraseña fija en el código.
  Si no la conoces, restablécela con:
  `php artisan eleam:admin-crear admin@eleam.cl`
- No hay cuentas de prueba con clave conocida en el repositorio.
- `MAIL_MAILER=log` en local: los correos se escriben en el log, no se envían.

## Trabajo integrado en esta sesión

El commit `d1a8c94` publicó los cambios de parámetros legales, previsión y
seguridad. Sobre esa base se integró y publicó el trabajo que antes estaba sin
commit:

- Dashboard operativo con estadísticas, gráfico de finanzas, actividad reciente
  y próximos turnos.
- Cálculo eficiente del saldo de vacaciones en `Trabajador` (sin N+1).
- Notificaciones diarias de contratos, vacaciones y licencias médicas.
- Reportes PDF semanales/mensuales de notificaciones y envío opcional por correo
  a administradores.
- Respaldo a Google Drive de SQLite, `storage/` y `.env` sanitizado. La base se
  copia con `VACUUM INTO` para incluir las transacciones del WAL.
- Scheduler con tareas diarias, semanales y mensuales.
- Comandos de mantenimiento `log:clear` y `db:optimize`.
- Pruebas de dashboard, notificaciones, reportes, respaldo simulado y comandos
  programados.
- El commit `38e6987` agregó IUSC, descuentos adicionales, origen de días desde
  licencias/asistencias, cierre/reapertura con trazabilidad y pruebas P0.
- El 2026-10-24 se actualizaron las dependencias, se aplicaron las migraciones
  pendientes, se corrigió la prueba de respaldo, se agregó CI y se programó el
  scheduler/respaldo en el instalador de servidor.
- Se validó la suite contra PostgreSQL 17. Eso destapó que `notifications.data`
  era `text`; ahora es JSON y las consultas `data->>` funcionan en ambos motores.
- El respaldo SQLite ya no copia el archivo vivo: genera un snapshot consistente
  con `VACUUM INTO`.
- La publicación de esa revisión quedó en `82db1fc`, `5eb47c5`, `178e2d9` y
  `249a7cd`, más la etiqueta `v0.1.0`. Después se actualizaron las actions del
  CI (`actions/checkout@v7`, `actions/setup-node@v7`).
- El respaldo a Google Drive ahora soporta PostgreSQL: usa `pg_dump` en formato
  custom y `pg_restore` para recuperar. La lógica vive en
  `App\Services\RespaldoBaseDatos`.
- Las liquidaciones usan soft delete. El panel permite ver las archivadas y
  restaurarlas solo como administrador; no se borran en duro.
- Se agregó un lanzador de Windows (`windows\Iniciar-ERP.bat`) que abre Edge en
  modo app con perfil dedicado y espera `/up`.
- Docker usa PostgreSQL 17, incluye `pg_dump` en la imagen y ejecuta el
  scheduler dentro del contenedor.
- `deploy/respaldar.sh` usa `pg_dump` con `--no-owner --clean --if-exists` y se
  agregó `deploy/probar-restauracion.sh` para ensayar una restauración real.
- El 2026-11-04 se compiló `dist\ERP-ELEAM-Setup-0.2.0.exe` (114 MB) con Inno
  Setup y se probó en el PC de desarrollo: smoke test del flujo instalado
  (servidor, `/up`, login, base fuera de la carpeta del programa, respaldo y
  verificación), tarea programada y actualizador con reversa. Detalle en
  [VALIDACION_TECNICA_2026-11-04.md](VALIDACION_TECNICA_2026-11-04.md).
- Se agregaron `BackupLocal.php`, `VerificarBackup.php` y
  `app/Support/EnvSanitizer.php`: respaldo local SQLite/PostgreSQL con
  manifiesto SHA-256, verificación de restauración y `.env` sanitizado.
- Se agregaron las pruebas `AceptacionFlujoCompletoTest` (flujo punta a punta:
  trabajador, contrato, nómina, PDF y CSV) y `BackupLocalTest` (respaldo,
  manifiesto manipulado y retención).

## Validaciones ejecutadas

- `php artisan test`: **128 pruebas aprobadas, 1 omitida y 487 aserciones** en
  SQLite (2026-11-04).
- `vendor/bin/phpunit -c phpunit.pgsql.xml`: **129 pruebas y 493 aserciones**
  contra PostgreSQL 18.3 nativo en Windows, incluido el ensayo real de
  `pg_dump` + `pg_restore` (2026-11-04).
- `php artisan migrate:fresh --force` contra PostgreSQL: correcto, incluida
  la conversión de `notifications.data` a JSON.
- `php artisan migrate --force`: migraciones legales, de notificaciones, campos
  P0, soft delete de liquidaciones y `parametros_usados` aplicadas en la SQLite
  local y en `eleam_staging`.
- `backup:google-drive --database --dry-run` con PostgreSQL: genera un `.dump`
  real, lo muestra y lo elimina al terminar.
- `windows\Iniciar-ERP.ps1 -SinNavegador` y `windows\Detener-ERP.ps1`: el
  servidor responde `/up` y el proceso PHP se detiene sin tocar otros procesos.
- `php artisan schedule:list`: seis tareas registradas, todas con comandos
  existentes.
- `php artisan notificaciones:diarias --dry-run`: correcto.
- `php artisan backup:google-drive --database --dry-run`: correcto, con snapshot
  consistente de SQLite.
- `php artisan reporte:notificaciones --periodo=semanal --guardar`: PDF
  generado correctamente.
- `php artisan log:clear`: correcto.
- `php artisan db:optimize`: VACUUM y ANALYZE correctos en SQLite.
- `vendor\bin\pint` y `git diff --check` se ejecutan antes de publicar.
- GitHub Actions ejecuta dos jobs: **PHP 8.2 · SQLite** y
  **PHP 8.3 · PostgreSQL 17**. Ambos incluyen la suite completa; el job
  PostgreSQL instala el cliente 17 para probar `pg_dump` y `pg_restore`.

## Automatización y configuración

El scheduler **no se ejecuta solo** en local. En local:

```powershell
php artisan schedule:work
```

En producción, el instalador de servidor crea `/etc/cron.d/eleam` con
`schedule:run` cada minuto y el respaldo diario a las 3:00. Docker ejecuta
`schedule:work` dentro del contenedor mediante supervisord. Verifícalo con
`sudo -u www-data php artisan schedule:list`. Las notificaciones se envían en
forma inmediata y no requieren un worker de colas. Tareas registradas:

- 08:00: notificaciones diarias.
- 02:00: respaldo a Google Drive (SQLite o PostgreSQL, si está configurado).
- Domingo 03:00: limpieza de logs.
- Día 1, 04:00: optimización de la base de datos.
- Lunes 07:00: reporte semanal.
- Día 1, 06:00: reporte mensual.

Para el respaldo a Google Drive se necesitan
`GOOGLE_DRIVE_CREDENTIALS_PATH` y `GOOGLE_DRIVE_BACKUP_FOLDER_ID`. El archivo de
credenciales está en `.gitignore`; nunca debe subirse al repositorio. En
PostgreSQL el comando usa `pg_dump` en formato custom; si el binario no está en
PATH, define `PG_DUMP_PATH`. Ese `.dump` se restaura con
`pg_restore --clean --if-exists --no-owner -d base_destino archivo.dump`. El
respaldo plano `.sql.gz` de `deploy/respaldar.sh` se ensaya con
`deploy/probar-restauracion.sh`.

## Hallazgos funcionales importantes: no procesar nómina real todavía

Los valores y cálculos siguen siendo de apoyo; ninguna prueba automatizada
acredita cumplimiento legal. Antes de pagar remuneraciones:

1. La UF y la UTM se obtienen de mindicador.cl, una fuente externa de
   referencia. Los topes, el SIS y el sueldo mínimo deben contrastarse con la
   normativa vigente y marcarse como verificados por una persona responsable.
2. El IUSC ya se calcula con la tabla SII 2026, pero hay que validar con un
   contador la base imponible y si la salud deducible debe ser el 7 % legal o el
   plan Isapre completo. También revisar topes y gratificación.
3. Los días trabajados pueden venir de licencias no rechazadas, asistencias o
   ingreso manual. Las licencias **Pendientes** también descuentan. El modo
   asistencia prorratea el sueldo mensual por días con marca; esa regla debe
   acordarse antes de usarla para pagos.
4. No afirmar que el CSV sea certificación oficial de la Dirección del Trabajo
   sin validar formato y reglas actuales.
5. Los cálculos de licencias y finiquitos son simplificados; solicitar revisión
   laboral/contable y contrastar con fuentes oficiales antes de pagos, firma o
   reportes.
6. El cierre de liquidaciones ya bloquea el recálculo y la reapertura deja
   trazabilidad. El cierre compara la UTM usada, pero no la UF, el sueldo mínimo
   ni todos los topes; revisar ese control si los parámetros cambian.

## Próximos pasos sugeridos

1. Obtener revisión laboral/contable de la nómina, incluido el impuesto único de
   segunda categoría y el tratamiento de licencias/ausencias.
2. Definir quién verifica los parámetros legales cada mes y dejar registro de
   esa revisión en el panel.
3. Validar con el responsable laboral el tratamiento de licencias pendientes,
   ausencias y el prorrateo por asistencia antes de automatizar el pago.
4. Probar el instalador `ERP-ELEAM-Setup-0.2.0.exe` en otro PC Windows limpio
   (reinicio, actualización y restauración de un respaldo). La suite ya pasa
   en PostgreSQL local y en CI; falta el equipo limpio y el servidor real.
5. Obtener firma digital para el ejecutable o documentar el aviso de
   SmartScreen para los usuarios.
6. Configurar correo real y probar el envío de notificaciones y reportes.
7. Configurar Google Drive en un entorno de ensayo y probar una restauración
   completa con `deploy/probar-restauracion.sh` o `pg_restore`. El respaldo
   integrado ya cubre SQLite y PostgreSQL; igualmente hay que copiar el
   respaldo fuera del servidor y verificar la restauración periódicamente.

## Comandos útiles

```powershell
php artisan test
vendor/bin/phpunit -c phpunit.pgsql.xml
vendor/bin/phpunit -c phpunit.pgsql.xml --filter=RespaldoPostgresTest
php artisan test --filter=ComandosMantenimientoTest
php artisan test --filter=LiquidacionP0Test
php artisan test --filter=DashboardWidgetsTest
php artisan test --filter=NotificacionesDiariasTest
php artisan eleam:parametros-actualizar --mes=10 --anio=2026
php artisan eleam:parametros-actualizar --mes=10 --anio=2026 --verificar --usuario=admin@eleam.cl
php artisan notificaciones:diarias --dry-run
php artisan reporte:notificaciones --periodo=semanal --guardar
php artisan backup:google-drive --all --dry-run
php artisan eleam:backup-local --destino=C:\ruta\respaldos --retencion=30
php artisan eleam:verificar-backup C:\ruta\respaldos\2026-11-04_02-30-00
php artisan schedule:work
php artisan log:clear
php artisan db:optimize
php artisan eleam:admin-crear admin@eleam.cl
php artisan db:seed --class=DemoOperacionalSeeder
php artisan route:list --path=admin
php artisan serve --host=127.0.0.1 --port=8000
```

`phpunit.pgsql.xml` espera PostgreSQL en `127.0.0.1:5432`, base `eleam_test`,
usuario `eleam` y clave `eleam_ci`. Puedes cambiar host, puerto, base y
credenciales con las variables `DB_HOST`, `DB_PORT`, `DB_DATABASE`,
`DB_USERNAME` y `DB_PASSWORD`.

Lee también:

- [Contexto del proyecto](CONTEXTO_DEL_PROYECTO.md)
- [Hoja de ruta](HOJA_DE_RUTA.md)
- [Prueba local en Windows](PRUEBA_LOCAL.md)
