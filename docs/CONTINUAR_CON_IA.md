# Handoff para continuar ERP ELEAM con otra IA

Fecha de actualización: 2026-10-22

## Resumen para retomar

ERP ELEAM es una aplicación Laravel 12 / Filament 5 para la operación de un
ELEAM en Chile. Tiene módulos de trabajadores, contratos, turnos, asistencia,
vacaciones, licencias, finanzas, remuneraciones y finiquitos.

El trabajo local actual incluye cambios **sin commit**. Antes de seguir, ejecuta
`git status --short` y conserva las modificaciones existentes. No las descartes
ni las sobrescribas.

## Estado de código y entorno local

- PHP local: 8.2.12; cumple el requisito `^8.2` de `composer.json`.
- Dependencias instaladas en `vendor/`; frontend instalado localmente y assets
  compilados en `public/build/`.
- Se creó `.env` local, `database/database.sqlite` y una clave de aplicación.
  Son datos locales ignorados por Git: no compartir ni reemplazar sin pedirlo.
- Servidor de desarrollo iniciado en `127.0.0.1:8000`. La portada, login y
  assets respondían; el panel redirige al login sin sesión.
- Administrador local: `admin@eleam.cl`. No hay una contraseña fija en el
  código ni en esta documentación. Si no la conoces, restablécela con
  `php artisan eleam:admin-crear admin@eleam.cl`; el comando genera una clave
  aleatoria y la muestra una sola vez. No la escribas en documentación ni la
  subas al repositorio.
- La cuenta temporal de QA visual (`qa-visual@eleam.cl`) fue eliminada al
  terminar las pruebas. No dejamos cuentas de prueba con clave conocida.
- No cambiar globalmente PHP/XAMPP: esta versión ya satisface el requisito.

## Cambios sin commit de esta sesión

Todos los cambios siguen **sin commit**. No ejecutes `git checkout`, `git reset`
ni descartes archivos sin revisar antes el diff.

- `config/legal.php`: URL de mindicador, tabla de sueldo mínimo por vigencia,
  topes en UF, porcentaje SIS y tasas AFP de referencia.
- `app/Services/ParametroLegalService.php` y
  `app/Console/Commands/ActualizarParametrosLegales.php`: actualizan UF y UTM
  desde `https://mindicador.cl/api`, toman el sueldo mínimo de la tabla de
  vigencia y registran origen, fecha y verificación humana. Comando:
  `php artisan eleam:parametros-actualizar`.
- Tres migraciones nuevas: trazabilidad de parámetros legales, tasa AFP/plan
  Isapre por trabajador y SIS en liquidaciones.
- `app/Services/RemuneracionService.php`: usa la tasa AFP y el sistema de salud
  de cada trabajador; para Isapre usa el plan en UF con mínimo legal de 7%; el
  SIS queda como aporte informativo del empleador. Ya no fija AFP Modelo ni
  Fonasa para todos.
- Acciones Filament para actualizar/verificar parámetros, editar tasa AFP y plan
  Isapre, y descargar comprobantes de gastos desde almacenamiento privado.
- Policies de parámetros legales y usuarios restringidas a administrador; los
  PDF siguen protegidos por autenticación y `can('view', ...)`.
- `bootstrap/app.php`: los invitados que abren un PDF son redirigidos al login
  de Filament (antes se producía un error 500 por una ruta `login` inexistente).
- `LibroRemuneracionesService`: neutraliza fórmulas de Excel y escapa
  separadores, comillas y saltos de línea en el CSV.
- `RoleSeeder`: en local genera contraseñas aleatorias y las muestra una sola
  vez; fuera de local no crea usuarios demo.
- `phpunit.xml`, `DemoOperacionalSeeder` y `EscenarioOperacionalDemoTest` se
  mantienen como estaban: escenario sintético optativo e idempotente.
- Pruebas nuevas: `RemuneracionPrevisionalTest`, `SeguridadTest`,
  `ParametroLegalServiceTest` y un caso de CSV injection en
  `LibroRemuneracionesTest`.

El seeder demo crea un trabajador etiquetado `DEMO / NO ES PERSONA REAL`, un
contrato indefinido de 42 horas y $750.000, turno diurno/nocturno, asistencia,
vacaciones, licencia, ingreso, gasto, liquidación y finiquito borrador:

```powershell
php artisan db:seed --class=DemoOperacionalSeeder
```

No utiliza RUT de una persona real. El `12345678-5` es un identificador
sintético de fixture; no debe asignarse a una persona.

## Validaciones ejecutadas

- `php artisan test`: **89 pruebas aprobadas, 284 aserciones**.
- `vendor\bin\pint --dirty`: aplicado a los archivos PHP modificados.
- QA visual con navegador headless: `/admin`, `/admin/parametro-legals`,
  `/admin/trabajadors`, `/admin/gastos` y `/admin/liquidacions` responden 200,
  sin errores de consola ni requests fallidos.
- La tarjeta de cuenta muestra **un solo botón "Salir"** en escritorio y en
  móvil (390 px); no hay desborde horizontal en móvil.
- `php artisan route:list --path=admin`: 41 rutas de administración, incluidas
  las dos rutas PDF.
- La cuenta temporal `qa-visual@eleam.cl` fue eliminada al terminar la QA.

## Hallazgos funcionales importantes: no procesar nómina real todavía

Los valores y cálculos siguen siendo de apoyo; ninguna prueba automatizada
acredita cumplimiento legal. Antes de pagar remuneraciones:

1. La UF y la UTM se obtienen de mindicador.cl, una fuente externa de
   referencia. Los topes, el SIS y el sueldo mínimo deben contrastarse con la
   normativa vigente y marcarse como verificados por una persona responsable.
2. El cálculo ya usa la AFP y la salud del trabajador, pero **no calcula el
   impuesto único de segunda categoría**. También faltan otros descuentos
   posibles (anticipos, sindicatos, cargas familiares) y la validación del plan
   Isapre contra el contrato real.
3. El cálculo recibe los días trabajados manualmente (30 por defecto). No
   descuenta automáticamente licencias, ausencias ni marcas de asistencia.
4. No afirmar que el CSV sea certificación oficial de la Dirección del Trabajo
   sin validar formato y reglas actuales.
5. Los cálculos de licencias y finiquitos son simplificados; solicitar revisión
   laboral/contable y contrastar con fuentes oficiales antes de pagos, firma o
   reportes.
6. Una liquidación marcada como `cerrada` todavía puede recalcularse con el
   generador. Falta un flujo de cierre/reapertura con permisos y registro antes
   de usar el módulo como libro oficial.

## Próximos pasos sugeridos

1. Revisar `git status` y el diff completo; conservar los cambios sin commit.
2. Obtener revisión laboral/contable de la nómina, incluido el impuesto único de
   segunda categoría y el tratamiento de licencias/ausencias.
3. Definir quién verifica los parámetros legales cada mes y dejar registro de
   esa revisión en el panel.
4. Integrar asistencias/licencias con los días trabajados antes de automatizar
   el pago.
5. Ensayar PostgreSQL, respaldos/restauración y despliegue antes de un piloto
   externo.

## Comandos útiles

```powershell
php artisan test
php artisan test --filter=RemuneracionPrevisionalTest
php artisan test --filter=SeguridadTest
php artisan eleam:parametros-actualizar --mes=10 --anio=2026
php artisan eleam:parametros-actualizar --mes=10 --anio=2026 --verificar --usuario=admin@eleam.cl
php artisan eleam:admin-crear admin@eleam.cl
php artisan db:seed --class=DemoOperacionalSeeder
php artisan route:list --path=admin
php artisan serve --host=127.0.0.1 --port=8000
```

Lee también:

- [Contexto del proyecto](CONTEXTO_DEL_PROYECTO.md)
- [Hoja de ruta](HOJA_DE_RUTA.md)
- [Prueba local en Windows](PRUEBA_LOCAL.md)
