# Hoja de ruta y preparación para entrega

## Diagnóstico actual

El proyecto cuenta con un MVP funcional amplio: gestión de personal y
operaciones, finanzas, remuneraciones, licencias, finiquitos, documentos,
autorización por roles, pruebas automatizadas y guías de despliegue.

**Estado recomendado: listo para una demostración o piloto controlado después de
verificar las pruebas; todavía no debe tratarse como listo para producción ni
para emitir cálculos legales sin revisión.** Este repositorio no demuestra una
instalación productiva ni una aprobación formal de los cálculos chilenos. El
resultado de las pruebas ejecutadas en esta revisión está registrado abajo.

## Dirección del proyecto

El objetivo es avanzar en dos etapas: primero entregar una instalación sencilla
y confiable en un PC con Windows; después, preparar el mismo sistema para
usarlo por internet. La primera etapa se considera para **un PC y una base de
datos local**. Si desde el inicio varias personas necesitan usarlo a la vez
desde distintos equipos, hay que tratarlo como un servidor de red y no como una
instalación individual.

El lanzador actual (`windows\Iniciar-ERP.bat`) solo inicia el servidor local de
Laravel y abre Edge desde la carpeta del proyecto. También existe un paquete
portable de preview que incluye PHP y las dependencias, y desde 2026-11-04 un
instalador de escritorio (`ERP-ELEAM-Setup-0.2.0.exe`) que se instala por
usuario, con datos separados del programa, bandeja del sistema, respaldos
automáticos y actualizador con reversa. Ambos se probaron mediante una
extracción/instalación limpia en el mismo PC donde se construyeron; todavía no
están validados en otro PC Windows limpio ni deben considerarse versiones de
distribución. El servidor local no es multiusuario ni se debe exponer
directamente a internet.

### Etapa A — Instalable y confiable en un PC

1. **Cerrar el alcance del primer uso:** confirmar la versión de Windows, si
   será un único usuario/PC o si otros equipos deben conectarse, y quién será
   responsable de los datos y las copias de seguridad.
2. **Definir el paquete de instalación:** preparar una instalación guiada que
   compruebe los requisitos o incluya un runtime compatible, instale el ERP,
   cree la base SQLite en una ubicación persistente, ejecute las migraciones y
   permita crear el primer administrador con contraseña generada.
3. **Completar la operación diaria:** accesos directos para iniciar y detener,
   inicio claro ante errores, ubicación visible de los datos y logs, y
   documentación breve de acceso y cierre.
4. **Proteger los datos:** configurar copias automáticas en otra ubicación,
   indicar su retención y probar restaurar una copia. Las actualizaciones deben
   respaldar antes de migrar y conservar los datos; la desinstalación debe
   explicar qué pasará con la base y los archivos.
5. **Probar en un PC limpio:** instalar, crear el administrador, recorrer los
   módulos principales, reiniciar Windows, actualizar una versión y restaurar
   un respaldo. No dar por lista la entrega hasta completar el ensayo sin
   herramientas de desarrollo preinstaladas.

**Salida de la etapa:** una versión etiquetada, guía de instalación/uso,
procedimiento de actualización y recuperación probados, y aprobación del
responsable del ELEAM. Antes de usar remuneraciones en pagos reales, completar
la revisión laboral/contable indicada en P0.

### Etapa B — Preparar el uso web

1. **Revisar el piloto local:** corregir problemas reales de uso y confirmar
   roles, soporte, retención de datos y responsables de operación.
2. **Elegir y probar el destino web:** usar PostgreSQL y un despliegue mantenido
   (VPS con HTTPS o Docker en un servidor); las guías existentes son un punto
   de partida, no evidencia de una puesta en producción ya validada.
3. **Planificar el traslado:** ensayar en una copia cómo mover los datos desde
   el PC, incluidos adjuntos, usuarios y permisos. La migración SQLite a
   PostgreSQL existente debe probarse con los datos del piloto. No asumir que
   las dos instalaciones se sincronizan automáticamente.
4. **Cerrar controles de producción:** dominio y HTTPS, secretos únicos,
   acceso restringido, correo/alertas, respaldos externos y restauración
   completa probada, actualizaciones con reversa y monitoreo.
5. **Hacer un piloto web limitado:** probar concurrencia, acceso desde los
   dispositivos reales, permisos, rendimiento y recuperación antes de ampliar
   el uso.

**Salida de la etapa:** sistema accesible por HTTPS desde los dispositivos
autorizados, con PostgreSQL, operación y soporte asignados, respaldo externo
restaurable y aceptación documentada. La publicación web es una fase posterior,
no un requisito para terminar la instalación inicial en el PC.

## Prioridades

### P0 — Cerrar antes de entregar

- [x] Ejecutar la suite completa en SQLite en memoria y registrar el resultado:
  **128 pruebas aprobadas, 1 omitida y 487 aserciones** (verificación
  2026-11-04). La omitida es el ensayo de `pg_dump`, exclusivo de PostgreSQL.
  Incluye previsión por trabajador, SIS, IUSC, cierre/reapertura, permisos,
  dashboard, notificaciones, reportes, respaldo simulado y CSV.
- [x] Validar la suite contra **PostgreSQL 17**: 122 pruebas y 441 aserciones,
  sin omisiones. **Reverificado el 2026-11-04 contra PostgreSQL 18.3 nativo en
  Windows: 129 pruebas y 493 aserciones**; el ensayo real de `pg_dump` y
  restauración en una base temporal también pasa. La validación destapó y
  corrigió el tipo JSON de `notifications.data`.
- [ ] Confirmar con el usuario del negocio el alcance exacto de la entrega:
  demo, piloto interno o producción; qué módulos se usarán y quién los aprobará.
- [ ] Obtener revisión laboral/contable de remuneraciones, licencias, vacaciones,
  finiquitos, parámetros legales y del CSV. No presentar las fórmulas actuales
  como certificación normativa.
- [x] Implementar el enlace entre licencias/asistencias y remuneraciones:
  `RemuneracionService` calcula los días desde licencias no rechazadas, desde
  asistencias registradas o permite el ingreso manual.
- [ ] Validar con el responsable laboral/contable el tratamiento de licencias
  pendientes, ausencias y el prorrateo por asistencia antes de pagar nómina real.
- [x] Validar los descuentos previsionales básicos: `RemuneracionService` usa la
  tasa AFP y el sistema de salud del trabajador, aplica el plan Isapre con
  mínimo de 7 % y registra el SIS como aporte del empleador (no lo descuenta
  del líquido).
- [x] Implementar el impuesto único de segunda categoría con la tabla SII 2026
  y pruebas de cálculo, más descuentos adicionales (anticipo, sindicato y otros).
- [ ] Validar con asesoría laboral/contable el IUSC, la salud Isapre, los topes y
  la gratificación antes de usar nómina real para pagos.
- [x] Implementar el flujo de cierre y reapertura de liquidaciones: el cierre
  exige parámetros verificados y bloquea el recálculo; la reapertura es solo de
  administrador y registra usuario, fecha y motivo. El generador rechaza
  liquidaciones cerradas.
- [x] Automatizar la actualización de UF y UTM desde mindicador.cl, con origen,
  fecha y verificación humana registrados; el sueldo mínimo se toma de una
  tabla de vigencia configurable.
- [ ] Verificar los parámetros de cada período antes de cualquier cálculo real:
  los topes, el SIS y el sueldo mínimo siguen requiriendo contraste con la
  normativa vigente y la marca de verificación de un responsable.
- [x] Probar los cuatro roles con datos representativos: lectura, creación,
  edición, eliminación y acceso directo a PDF/rutas. Se agregaron pruebas de
  403 en parámetros y usuarios, y de acceso al PDF por rol.
- [x] Preparar un entorno de ensayo con el mismo motor de base de datos elegido
  para producción: base `eleam_staging` en PostgreSQL 18.3 con **27
  migraciones** aplicadas, alta de administrador, flujo de aceptación punta a
  punta (`AceptacionFlujoCompletoTest`) y ensayo del actualizador (respaldo,
  verificación, reemplazo solo de la carpeta de la aplicación, migraciones y
  reversa) en el PC de desarrollo. Detalle en
  [validación técnica 2026-11-04](VALIDACION_TECNICA_2026-11-04.md).
  Falta repetir el ensayo en un PC Windows limpio.
- [x] Preparar el paquete portable de preview para Windows, con runtime PHP
  incluido, SQLite, migraciones y alta del primer administrador. Se verificó
  instalación, inicio y parada mediante extracción limpia en el PC de
  desarrollo.
- [x] Compilar el instalador `ERP-ELEAM-Setup-0.2.0.exe` con Inno Setup
  (2026-11-04) y probarlo en el PC de desarrollo: smoke test del flujo
  instalado (servidor, `/up`, login, base en la carpeta de datos, respaldo y
  verificación), tarea programada y actualizador con reversa. Detalle en
  [validación técnica 2026-11-04](VALIDACION_TECNICA_2026-11-04.md).
- [ ] Antes de distribuirlo, probarlo en otro PC Windows limpio sin
  herramientas de desarrollo, incluyendo reinicio, copia/restauración y
  actualización siguiendo la Etapa A.
- [ ] Realizar y restaurar un respaldo de ensayo completo, incluyendo la base
  PostgreSQL y los archivos adjuntos, y copiarlo a un destino externo. Registrar
  dónde se guarda y quién puede recuperarlo. El ensayo automatizado de la base
  ya existe (`RespaldoPostgresTest` y `deploy/probar-restauracion.sh`); falta
  incluir adjuntos y la copia externa.
- [ ] Sustituir las contraseñas de ejemplo, configurar secretos únicos, HTTPS,
  `APP_DEBUG=false`, correo/registro operativo y acceso restringido. Nunca usar
  contraseñas publicadas en documentación.
- [ ] Corregir las guías de instalación para que cada comando aplique al sistema
  operativo indicado y validar que los pasos no oculten errores de inicialización.

### P1 — Completar para un piloto confiable

- [ ] Completar primero el piloto local en un PC según la Etapa A; separar de
  ese objetivo la puesta en marcha web futura.
- [ ] Ejecutar una prueba de aceptación de punta a punta: trabajador/contrato,
  turno y asistencia, vacaciones/licencia, remuneración, PDF y exportación.
- [ ] Acordar responsables y procedimientos de operación: alta/baja de
  usuarios, cierre de períodos, correcciones, auditoría y soporte.
- [ ] Confirmar legibilidad y uso móvil en los dispositivos reales del equipo.
- [ ] Documentar recuperación ante caída, rotación de claves, actualización y
  contacto responsable.
- [ ] Validar manejo de información personal: acceso mínimo necesario,
  conservación, exportación y eliminación conforme a las políticas aplicables
  al ELEAM.

### P2 — Próxima evolución técnica

- [x] Agregar integración continua para `composer install` y pruebas PHPUnit en
  cada cambio.
- [x] Mantener pruebas de migraciones y flujos en PostgreSQL además de SQLite,
  incluido el ensayo de respaldo y restauración.
- [ ] Añadir monitoreo y alertas operativas, y programar pruebas periódicas de
  restauración.
- [ ] Definir versiones etiquetadas, changelog y procedimiento de reversa de
  despliegue.
- [ ] Revisar deuda funcional priorizada por usuarios después del piloto, sin
  agregar módulos antes de validar los flujos actuales.

## Criterio mínimo para decidir si se entrega

**Demostración interna:** suite completa verde, credenciales de desarrollo
aisladas y datos ficticios.

**Piloto con usuarios:** además de lo anterior, aprobación de negocio de los
flujos, permisos probados, ambiente aislado, respaldo restaurable y revisión
humana de los cálculos antes de pagar o emitir documentación oficial.

**Producción:** todas las tareas P0 cerradas, despliegue reproducible probado,
HTTPS y secretos propios, respaldos/recuperación probados, responsables
operativos definidos y aceptación legal/contable documentada. No usar
`migrate:fresh` contra datos reales.

## Verificación de esta revisión

- Fecha de esta revisión: **2026-11-04**. El detalle completo (pruebas,
  PostgreSQL de ensayo, respaldo/restauración, instalador y pendientes) está en
  [VALIDACION_TECNICA_2026-11-04.md](VALIDACION_TECNICA_2026-11-04.md).
- Repositorio: los cambios de las revisiones de octubre/noviembre están **sin
  commit ni push** en el árbol de trabajo; la última línea base publicada es
  `v0.1.0` (`82db1fc`, `5eb47c5`, `178e2d9` y `249a7cd`, más la actualización
  de las actions del CI). No implica validación legal ni autorización de
  producción.
- Etiqueta publicada: **`v0.1.0`**, línea base probada antes de esta revisión.
- PHP local: 8.2.12; SQLite y `pdo_pgsql` habilitados. El CI prueba PHP 8.2 con
  SQLite y PHP 8.3 con PostgreSQL 17.
- Entorno local preparado en `http://127.0.0.1:8000/admin`, con SQLite y assets
  compilados.
- Suite completa SQLite: **128 pruebas aprobadas, 1 omitida y 487 aserciones**
  (2026-11-04).
- Suite completa PostgreSQL 18.3 nativo Windows: **129 pruebas y 493
  aserciones** (2026-11-04), sin omisiones, incluido el ensayo real de
  `pg_dump`/`pg_restore`.
- Migraciones: **27**, incluidas `notifications` (tipo JSON), los campos P0,
  el soft delete de liquidaciones y `parametros_usados`.
- El respaldo SQLite usa `VACUUM INTO` para obtener una copia consistente y no
  perder transacciones que aún están en el WAL. El respaldo PostgreSQL usa
  `pg_dump` en formato custom y `pg_restore` para recuperar.
- GitHub Actions ejecuta ambos motores en cada push o pull request. El job
  PostgreSQL instala el cliente 17 y ejecuta el ensayo de restauración.
- Migraciones locales aplicadas, incluidas `notifications` (tipo JSON), los
  campos P0, el soft delete de liquidaciones y `parametros_usados`.
- Pruebas nuevas relevantes:
  - `RemuneracionPrevisionalTest`: AFP propia, Isapre con plan y mínimo de 7 %,
    SIS fuera del líquido, contrato vigente y errores por datos faltantes.
  - `SeguridadTest`: 403 en parámetros/usuarios para no administradores; PDF
    anónimo redirige al login, usuario sin rol recibe 403 y solo lectura accede.
  - `ParametroLegalServiceTest`: actualización, verificación y conservación de
    topes.
  - `LibroRemuneracionesTest`: neutralización de fórmulas y separadores.
  - `DashboardWidgetsTest`: renderiza el dashboard y sus cuatro widgets con
    datos reales.
  - `NotificacionesDiariasTest`: detecta contratos, vacaciones y licencias, y
    respeta el modo `--dry-run`.
  - `ComandosMantenimientoTest`: comandos programados, limpieza de logs,
    optimización, reporte PDF, envío por correo y respaldo simulado.
  - `RespaldoPostgresTest`: genera un `pg_dump`, lo restaura en una base
    temporal y compara tablas y migraciones.
  - `LiquidacionP0Test`: IUSC, descuentos adicionales, origen de días,
    cierre/reapertura y borrado reversible de liquidaciones.
  - `EscenarioOperacionalDemoTest`, `PanelPaginasNuevasTest` y
    `PilotoRemuneraciones2026Test`: flujo operativo y regresión del panel.
  - `AceptacionFlujoCompletoTest`: flujo de aceptación punta a punta
    (trabajador, contrato, nómina, PDF y CSV).
  - `BackupLocalTest` y `VerificarBackup`: respaldo SQLite/PostgreSQL,
    manifiesto con hash, verificación y retención.
- QA visual headless previa: `/admin`, `/admin/parametro-legals`,
  `/admin/trabajadors`, `/admin/gastos` y `/admin/liquidacions` responden 200,
  sin errores de consola ni requests fallidos. Un solo botón "Salir" visible en
  escritorio y móvil (390 px), sin desborde horizontal. El dashboard nuevo está
  cubierto además por `DashboardWidgetsTest`.
- `vendor\bin\pint --dirty`: aplicado correctamente.
- Verificación de rutas: 41 rutas de administración registradas, incluidas las
  dos rutas PDF protegidas por autenticación y autorización.
- La cuenta temporal de QA visual fue eliminada. Para entrar localmente,
  restablece la clave de `admin@eleam.cl` con `php artisan eleam:admin-crear`.
- Se añadió y ejecutó `DemoOperacionalSeeder`, una carga optativa e idempotente
  de datos sintéticos. Usa un RUT de fixture y el nombre
  `DEMO / NO ES PERSONA REAL`; no contiene datos personales reales. Los montos y
  parámetros son demostrativos, no certificación legal.
- Riesgo vigente: la validación legal/contable (IUSC, salud Isapre, licencias,
  ausencias, topes y gratificación) sigue pendiente. El modo asistencia prorratea
  el sueldo mensual por días con marca; acordar esa regla antes de pagar. El
  respaldo a Google Drive ya cubre SQLite y PostgreSQL, pero falta ensayar la
  restauración completa con adjuntos y copia externa en un servidor/staging con
  datos de ensayo. La suite PostgreSQL y el ensayo de `pg_dump`/`pg_restore` ya
  pasan en local y CI.
- Se agregó un lanzador de Windows (`windows\Iniciar-ERP.bat`) para probar el
  sistema como app con Edge, sin reemplazar el servidor web de producción.
- Se generó `dist\ERP-ELEAM-portable-preview.zip`. La extracción de prueba
  quedó con `APP_ENV=production` y `APP_DEBUG=false`; se verificaron las
  migraciones SQLite, el alta del administrador, el enlace de archivos, las
  respuestas HTTP de `/up` y `/admin` y la detención del servidor. El ZIP no
  contiene `.env`, bases locales ni credenciales. Sigue pendiente probarlo en
  otro PC Windows limpio antes de distribuirlo.
- El instalador `dist\ERP-ELEAM-Setup-0.2.0.exe` (compilado el 2026-11-04 con
  Inno Setup 6.7.3) se probó en el PC de desarrollo: smoke test del flujo
  instalado, base en la carpeta de datos, respaldo y verificación, tarea
  programada y actualizador con reversa. **Pendiente: prueba en otro PC Windows
  limpio y firma digital del ejecutable.** Ver
  [validación técnica 2026-11-04](VALIDACION_TECNICA_2026-11-04.md).
- Docker usa PostgreSQL 17, incluye `pg_dump` y ejecuta el scheduler dentro del
  contenedor.

## Continuidad entre sesiones

Para traspasar el trabajo a otra sesión de desarrollo o IA, consulta
[CONTINUAR_CON_IA.md](CONTINUAR_CON_IA.md). Resume el estado del entorno local,
la integración publicada, las pruebas ejecutadas y los riesgos funcionales que
deben atenderse antes de nómina real.
