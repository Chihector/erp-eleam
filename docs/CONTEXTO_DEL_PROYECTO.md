# Contexto del proyecto ERP ELEAM

## Propósito

ERP ELEAM es una aplicación web para apoyar la administración de personal y la
operación de un Establecimiento de Larga Estadía para Adultos Mayores (ELEAM) en
Chile. Incluye gestión de trabajadores, contratos, turnos, asistencia,
vacaciones, licencias médicas, finanzas, remuneraciones y finiquitos.

El sistema centraliza información y automatiza cálculos de apoyo; no reemplaza
la revisión profesional de remuneraciones, obligaciones laborales, previsionales
ni reportes oficiales.

## Estado conocido

- El commit `d1a8c94` incorpora licencias médicas, finiquitos, exportación CSV
  del libro de remuneraciones, búsqueda sin tildes y opciones de despliegue,
  además de la actualización de UF/UTM desde mindicador.cl, verificación humana
  de parámetros, AFP y salud por trabajador, plan Isapre en UF, SIS informativo
  del empleador, controles de acceso más estrictos y pruebas de CSV injection.
- La integración posterior agrega dashboard operativo, notificaciones diarias,
  reportes PDF, respaldo a Google Drive, tareas programadas, IUSC, origen de días
  desde licencias/asistencias y cierre/reapertura de liquidaciones.
- La rama `master` se publica en GitHub con la etiqueta `v0.1.0` y un workflow de
  GitHub Actions que ejecuta la suite en SQLite (PHP 8.2) y PostgreSQL 17
  (PHP 8.3) en cada push o pull request.
- La validación en PostgreSQL obligó a guardar `notifications.data` como JSON;
  las consultas `data->>` ya funcionan en ambos motores.
- El respaldo a Google Drive soporta SQLite (`VACUUM INTO`) y PostgreSQL
  (`pg_dump`/`pg_restore`). Las liquidaciones usan soft delete y se pueden
  restaurar desde el panel solo como administrador.
- `windows\Iniciar-ERP.bat` abre el sistema en Edge como app para pruebas
  locales; no reemplaza el servidor web de producción.
- Docker usa PostgreSQL 17, incluye el cliente `pg_dump` y ejecuta el scheduler
  dentro del contenedor.
- La aplicación y sus pruebas están implementadas en Laravel; los detalles de
  cobertura y el resultado verificable más reciente se registran en
  [HOJA_DE_RUTA.md](HOJA_DE_RUTA.md).
- El repositorio no contiene `vendor/` de forma predeterminada; antes de correr
  pruebas en una instalación limpia se debe ejecutar `composer install`.
- No se ha acreditado en este repositorio una puesta en producción, una
  restauración completa con datos y adjuntos reales ni la validación legal de
  los cálculos.

## Tecnologías y estructura

| Área | Implementación |
| --- | --- |
| Backend | PHP 8.2+ y Laravel 12 |
| Panel | Filament 5 (`filament/filament ^5.10`) |
| Persistencia | SQLite para desarrollo/pruebas; PostgreSQL para despliegues |
| Autorización | Policies de Laravel y roles de Spatie Permission |
| Reportes | DomPDF para documentos PDF; CSV para exportación de remuneraciones |
| Frontend | Blade/Filament, CSS y JavaScript compilados con Vite |
| Pruebas | PHPUnit con SQLite en memoria y PostgreSQL 17 (`phpunit.pgsql.xml`) |

Directorios que conviene conocer:

- `app/Models`: entidades y relaciones Eloquent.
- `app/Filament/Resources`: formularios, tablas y páginas del panel.
- `app/Policies`: permisos de consulta y gestión por rol.
- `app/Services`: cálculos y lógica reutilizable de remuneraciones, finiquitos,
  subsidios, finanzas y libro de remuneraciones.
- `app/Support`: lógica auxiliar, como normalización de búsquedas.
- `database/migrations` y `database/seeders`: esquema y datos iniciales.
- `routes/web.php` y `resources/views/pdf`: portada y descargas protegidas de
  PDF.
- `tests/Feature` y `tests/Unit`: pruebas automatizadas.
- `windows`: lanzador y detención del servidor local en Windows.
- `tests/e2e`: scripts heredados de QA visual; no forman parte de PHPUnit ni CI.
- `deploy`, `docker-compose.yml` y `docs/DESPLIEGUE_*`: opciones de instalación,
  operación, respaldo y ensayo de restauración.

## Módulos y permisos

El panel organiza los módulos en cuatro grupos: Operación, Personal, Finanzas
y Configuración. Las reglas descritas por el código y la guía de usuario son:

| Rol | Gestión principal |
| --- | --- |
| Administrador | Todos los módulos y configuración |
| Jefe de Turno | Turnos, asistencias y vacaciones |
| Contador | Gastos, ingresos y liquidaciones |
| Solo Lectura | Consulta, sin gestión |

**Parámetros Legales** y **Usuarios** están restringidos al rol Administrador.
Las Policies son la capa de autorización que debe proteger los recursos y las
rutas de documentos, no solo ocultar acciones en la interfaz. Al agregar un
módulo o una ruta nueva, agregar y ejecutar pruebas de autorización para cada rol.

## Ejecución local y pruebas

Requisitos: PHP 8.2+, Composer y extensiones PHP para SQLite, entre ellas
`pdo_sqlite`, `mbstring`, `xml`, `curl` y `intl`.

```sh
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan eleam:parametros-actualizar
php artisan eleam:admin-crear admin@eleam.cl
php artisan serve
```

En PowerShell, `copy` es un alias de copia de archivos. Si `.env` ya existe, no
lo sobrescribas ni generes otra clave sin revisar su configuración.
La suite PHPUnit define su propia clave de cifrado de prueba en `phpunit.xml`;
esa clave solo se usa en tests.

```sh
php artisan test
```

Para aislar un área, por ejemplo:

```sh
php artisan test --filter=AutorizacionTest
php artisan test --filter=SeguridadTest
php artisan test --filter=ParametroLegalServiceTest
php artisan test --filter=RemuneracionServiceTest
php artisan test --filter=RemuneracionPrevisionalTest
php artisan test --filter=LicenciaMedicaTest
php artisan test --filter=FiniquitoTest
php artisan test --filter=LibroRemuneracionesTest
```

La configuración PHPUnit usa SQLite en memoria, por lo que las pruebas no deben
modificar la base local de desarrollo.

## Cálculos y límites de uso

Los servicios de remuneraciones, licencias y finiquitos contienen reglas
simplificadas y dependen de parámetros legales almacenados en la aplicación.

En remuneraciones, la UF y la UTM se consultan desde mindicador.cl; el sueldo
mínimo sale de una tabla de vigencia configurable. Los topes y el SIS no se
descargan automáticamente. La AFP y la salud se toman de cada trabajador, con
tope de salud de 60 UF, y el SIS se informa como costo del empleador sin
descontarlo del líquido. **El impuesto único de segunda categoría se calcula**
con la tabla SII configurable en `config/legal.php`. Los días trabajados pueden
calcularse desde licencias médicas no rechazadas, desde asistencias registradas
o ingresarse manualmente. El cierre exige parámetros verificados y bloquea el
recálculo; la reapertura es exclusiva del administrador y deja usuario, fecha y
motivo registrados.

Persisten límites que requieren validación laboral/contable: la tabla del IUSC,
el tratamiento de la salud Isapre, el prorrateo por asistencia, las licencias
pendientes, los topes y la gratificación. Las pruebas automatizadas protegen el
comportamiento codificado, no certifican cumplimiento normativo.

Antes de usar los cálculos para pagar, firmar documentos o presentar
información oficial:

1. Confirmar la vigencia de los parámetros para el período que se está
   procesando y marcar la verificación humana en el panel.
2. Contrastar cada fórmula y causal con asesoría laboral/contable competente y
   con fuentes oficiales chilenas, incluido el impuesto único y los descuentos
   que falten.
3. Revisar resultados en casos reales anonimizados y obtener aprobación del
   responsable del ELEAM.

Las pruebas automatizadas protegen el comportamiento codificado; no certifican
por sí solas el cumplimiento normativo ni la exactitud legal de una fórmula.

## Decisiones operativas pendientes de confirmar

- Elegir un único destino de despliegue (PostgreSQL en VPS o Docker) y probarlo
  en un entorno de ensayo.
- Definir quién administra usuarios, revisa auditoría y aprueba cambios de
  parámetros legales.
- Definir una política de respaldos, retención y recuperación probada.
- Reemplazar credenciales de ejemplo y secretos antes de entregar o exponer el
  sistema a usuarios.
