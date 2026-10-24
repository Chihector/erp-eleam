# Handoff para continuar ERP ELEAM con otra IA

Fecha de actualización: 2026-10-24

## Resumen para retomar

ERP ELEAM es una aplicación Laravel 12 / Filament 5 para la operación de un
ELEAM en Chile. Tiene módulos de trabajadores, contratos, turnos, asistencia,
vacaciones, licencias, finanzas, remuneraciones y finiquitos.

El proyecto está **unificado en una sola rama publicada** (`master` en GitHub).
La copia de trabajo actual es
`Desktop\Nueva carpeta\repos-github\Erp-eleam`; evita mantener otra copia
divergente. El repositorio local quedó limpio y sincronizado.

## Estado de código y entorno local

- Repositorio remoto: `https://github.com/chicohector953/Erp-eleam.git`.
- Rama local y remota: `master`.
- PHP local: 8.2.12; cumple el requisito `^8.2`.
- Dependencias instaladas en `vendor/`, incluido `google/apiclient`.
- Frontend instalado y assets compilados en `public/build/`.
- Base local: SQLite; migraciones aplicadas, incluidas `notifications` y los
  campos P0 de liquidaciones.
- Suite completa: **120 pruebas aprobadas, 427 aserciones** (2026-10-24).
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
- Respaldo a Google Drive de SQLite, `storage/` y `.env` sanitizado.
- Scheduler con tareas diarias, semanales y mensuales.
- Comandos de mantenimiento `log:clear` y `db:optimize`.
- Pruebas de dashboard, notificaciones, reportes, respaldo simulado y comandos
  programados.
- El commit `38e6987` agregó IUSC, descuentos adicionales, origen de días desde
  licencias/asistencias, cierre/reapertura con trazabilidad y pruebas P0.
- El 2026-10-24 se actualizaron las dependencias, se aplicaron las migraciones
  pendientes, se corrigió la prueba de respaldo, se agregó CI y se programó el
  scheduler/respaldo en el instalador de servidor.

## Validaciones ejecutadas

- `php artisan test`: **120 pruebas aprobadas, 427 aserciones**.
- `php artisan migrate --force`: migraciones legales, de notificaciones y campos
  P0 aplicadas en la SQLite local.
- `php artisan schedule:list`: seis tareas registradas, todas con comandos
  existentes.
- `php artisan notificaciones:diarias --dry-run`: correcto.
- `php artisan backup:google-drive --database --dry-run`: correcto.
- `php artisan reporte:notificaciones --periodo=semanal --guardar`: PDF
  generado correctamente.
- `php artisan log:clear`: correcto.
- `php artisan db:optimize`: VACUUM y ANALYZE correctos en SQLite.
- `vendor\bin\pint` y `git diff --check` se ejecutan antes de publicar.

## Automatización y configuración

El scheduler **no se ejecuta solo** en local. En local:

```powershell
php artisan schedule:work
```

En producción, el instalador de servidor crea `/etc/cron.d/eleam` con
`schedule:run` cada minuto y el respaldo diario a las 3:00. Verifícalo con
`sudo -u www-data php artisan schedule:list`. Las notificaciones se envían en
forma inmediata y no requieren un worker de colas. Tareas registradas:

- 08:00: notificaciones diarias.
- 02:00: respaldo a Google Drive (solo SQLite y si está configurado).
- Domingo 03:00: limpieza de logs.
- Día 1, 04:00: optimización de la base de datos.
- Lunes 07:00: reporte semanal.
- Día 1, 06:00: reporte mensual.

Para el respaldo a Google Drive se necesitan
`GOOGLE_DRIVE_CREDENTIALS_PATH` y `GOOGLE_DRIVE_BACKUP_FOLDER_ID`. El archivo de
credenciales está en `.gitignore`; nunca debe subirse al repositorio. Este
respaldo solo funciona con SQLite; con PostgreSQL usa `deploy/respaldar.sh` o
`pg_dump` y copia el archivo fuera del servidor.

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
4. Ensayar PostgreSQL, respaldos/restauración y despliegue antes de un piloto
   externo.
5. Configurar correo real y probar el envío de notificaciones y reportes.
6. Configurar Google Drive en un entorno de ensayo y probar una restauración
   completa. El respaldo integrado solo cubre SQLite; con PostgreSQL usar
   `pg_dump`/`deploy/respaldar.sh` y copiar el respaldo fuera del servidor.

## Comandos útiles

```powershell
php artisan test
php artisan test --filter=ComandosMantenimientoTest
php artisan test --filter=LiquidacionP0Test
php artisan test --filter=DashboardWidgetsTest
php artisan test --filter=NotificacionesDiariasTest
php artisan eleam:parametros-actualizar --mes=10 --anio=2026
php artisan eleam:parametros-actualizar --mes=10 --anio=2026 --verificar --usuario=admin@eleam.cl
php artisan notificaciones:diarias --dry-run
php artisan reporte:notificaciones --periodo=semanal --guardar
php artisan backup:google-drive --all --dry-run
php artisan schedule:work
php artisan log:clear
php artisan db:optimize
php artisan eleam:admin-crear admin@eleam.cl
php artisan db:seed --class=DemoOperacionalSeeder
php artisan route:list --path=admin
php artisan serve --host=127.0.0.1 --port=8000
```

Lee también:

- [Contexto del proyecto](CONTEXTO_DEL_PROYECTO.md)
- [Hoja de ruta](HOJA_DE_RUTA.md)
- [Prueba local en Windows](PRUEBA_LOCAL.md)
