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

## Prioridades

### P0 — Cerrar antes de entregar

- [x] Ejecutar la suite completa en SQLite en memoria y registrar el resultado:
  resultado actualizado en esta revisión: **103 pruebas y 318 aserciones
  aprobadas**. Incluye previsión por trabajador, SIS, permisos, dashboard,
  notificaciones, reportes, respaldo simulado y CSV.
- [ ] Confirmar con el usuario del negocio el alcance exacto de la entrega:
  demo, piloto interno o producción; qué módulos se usarán y quién los aprobará.
- [ ] Obtener revisión laboral/contable de remuneraciones, licencias, vacaciones,
  finiquitos, parámetros legales y del CSV. No presentar las fórmulas actuales
  como certificación normativa.
- [ ] Definir y probar el enlace entre licencias/asistencias y remuneraciones:
  el cálculo actual usa los días trabajados que se ingresan manualmente y no
  los descuenta automáticamente a partir de licencias o marcas de asistencia.
- [x] Validar los descuentos previsionales básicos: `RemuneracionService` usa la
  tasa AFP y el sistema de salud del trabajador, aplica el plan Isapre con
  mínimo de 7 % y registra el SIS como aporte del empleador (no lo descuenta
  del líquido).
- [ ] Implementar y validar el impuesto único de segunda categoría y otros
  descuentos (anticipos, sindicatos, cargas familiares) antes de usar nómina
  real para pagos.
- [ ] Definir el flujo de cierre y reapertura de liquidaciones: hoy una
  liquidación marcada como `cerrada` puede volver a recalcularse con el
  generador mensual.
- [x] Automatizar la actualización de UF y UTM desde mindicador.cl, con origen,
  fecha y verificación humana registrados; el sueldo mínimo se toma de una
  tabla de vigencia configurable.
- [ ] Verificar los parámetros de cada período antes de cualquier cálculo real:
  los topes, el SIS y el sueldo mínimo siguen requiriendo contraste con la
  normativa vigente y la marca de verificación de un responsable.
- [x] Probar los cuatro roles con datos representativos: lectura, creación,
  edición, eliminación y acceso directo a PDF/rutas. Se agregaron pruebas de
  403 en parámetros y usuarios, y de acceso al PDF por rol.
- [ ] Preparar un entorno de ensayo con el mismo motor de base de datos elegido
  para producción. Ejecutar migraciones, alta de administrador, flujos completos
  y una actualización desde una versión anterior.
- [ ] Realizar y restaurar un respaldo de ensayo, incluyendo la base de datos y
  archivos adjuntos. Registrar dónde se guardan y quién puede recuperarlos.
- [ ] Sustituir las contraseñas de ejemplo, configurar secretos únicos, HTTPS,
  `APP_DEBUG=false`, correo/registro operativo y acceso restringido. Nunca usar
  contraseñas publicadas en documentación.
- [ ] Corregir las guías de instalación para que cada comando aplique al sistema
  operativo indicado y validar que los pasos no oculten errores de inicialización.

### P1 — Completar para un piloto confiable

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

- [ ] Agregar integración continua para `composer install` y pruebas PHPUnit en
  cada cambio.
- [ ] Mantener pruebas de migraciones y flujos en PostgreSQL además de SQLite.
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

- Repositorio: **limpio y unificado**. La copia local es `Erp eleam` y la
  rama publicada es `master`; los worktrees auxiliares fueron eliminados.
- Últimos commits: `d1a8c94` (parámetros legales, previsión y seguridad) y el
  commit de integración de dashboard, notificaciones, reportes y respaldos.
- PHP disponible: 8.2.12; SQLite habilitado.
- Entorno local preparado en `http://127.0.0.1:8000/admin`, con SQLite y assets
  compilados.
- Suite completa: **103 aprobadas, 318 aserciones**.
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
- QA visual headless previa: `/admin`, `/admin/parametro-legals`,
  `/admin/trabajadors`, `/admin/gastos` y `/admin/liquidacions` responden 200,
  sin errores de consola ni requests fallidos. Un solo botón "Salir" visible en
  escritorio y móvil (390 px), sin desborde horizontal. El dashboard nuevo está
  cubierto además por `DashboardWidgetsTest`.
- `vendor\bin\pint --dirty`: aplicado correctamente.
- Migraciones legales y de notificaciones ejecutadas en la SQLite local.
- Verificación de rutas: 41 rutas de administración registradas, incluidas las
  dos rutas PDF protegidas por autenticación y autorización.
- La cuenta temporal de QA visual fue eliminada. Para entrar localmente,
  restablece la clave de `admin@eleam.cl` con `php artisan eleam:admin-crear`.
- Se añadió y ejecutó `DemoOperacionalSeeder`, una carga optativa e idempotente
  de datos sintéticos. Usa un RUT de fixture y el nombre
  `DEMO / NO ES PERSONA REAL`; no contiene datos personales reales. Los montos y
  parámetros son demostrativos, no certificación legal.
- Riesgo vigente: el generador de liquidaciones recibe 30 días por defecto y no
  deriva ausencias desde marcas ni licencias. Tampoco calcula impuesto único de
  segunda categoría. Acordar ambos flujos con el área laboral antes de procesar
  remuneraciones reales.

## Continuidad entre sesiones

Para traspasar el trabajo a otra sesión de desarrollo o IA, consulta
[CONTINUAR_CON_IA.md](CONTINUAR_CON_IA.md). Resume el estado del entorno local,
la integración publicada, las pruebas ejecutadas y los riesgos funcionales que
deben atenderse antes de nómina real.
