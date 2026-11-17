# Validación técnica — ERP ELEAM

Fecha: 2026-11-04

## 1. Objetivo y alcance

Este informe registra las validaciones técnicas ejecutadas antes de entregar una
versión instalable de ERP ELEAM en Windows. El alcance es **técnico**: pruebas
automatizadas, PostgreSQL de ensayo, respaldo/restauración, flujo operativo de
punta a punta y empaquetado para Windows.

No sustituye la revisión laboral/contable ni la aceptación de los usuarios. Las
fórmulas, parámetros legales y documentos emitidos siguen requiriendo la
aprobación de una persona responsable antes de pagar remuneraciones o emitir
documentos oficiales.

## 2. Resumen ejecutivo

| Área | Estado | Evidencia |
| --- | --- | --- |
| Suite SQLite | Verde | 128 aprobadas, 1 omitida, 487 aserciones |
| Suite PostgreSQL | Verde | 129 aprobadas, 493 aserciones |
| PostgreSQL de ensayo | Verde | PostgreSQL 18.3, base `eleam_staging`, 27 migraciones |
| Respaldo y restauración | Verde | Respaldo local, manifiesto SHA-256 y verificación en SQLite/PostgreSQL |
| Flujo de aceptación | Verde | Trabajador, contrato, nómina, PDF y CSV |
| Cierre de liquidaciones | Verde | Comparación de UF, UTM, sueldo mínimo, topes y SIS |
| Instalador Windows | Compilado y probado en el PC de desarrollo | Ver sección 7 |
| PC Windows limpio | Pendiente | Requiere otra máquina o máquina virtual |
| Firma digital | Pendiente | No hay certificado de firma |
| Validación legal/contable | Pendiente | Requiere responsable humano |

**Conclusión:** la base técnica está lista para un piloto controlado en un PC,
siempre que se complete la prueba en un equipo limpio y la revisión
legal/contable. No debe presentarse como sistema certificado ni usarse para
pagos reales sin esas aprobaciones.

## 3. Entorno de validación

- Windows 10/11 x64, PHP 8.2.12.
- Laravel 12 y Filament 5, sin reescritura de la aplicación.
- SQLite para la instalación de escritorio.
- PostgreSQL 18.3 nativo en Windows para el ensayo de servidor.
- Node.js/npm para compilar assets; Composer para dependencias.
- Inno Setup 6.7.3 para generar el instalador.

## 4. Pruebas automatizadas

### SQLite

```powershell
php artisan test
```

Resultado: **128 pruebas aprobadas, 1 omitida y 487 aserciones**. La prueba
omitida es el ensayo de `pg_dump`, que solo aplica a PostgreSQL.

### PostgreSQL

```powershell
vendor/bin/phpunit -c phpunit.pgsql.xml
```

Resultado: **129 pruebas aprobadas y 493 aserciones**, sin omisiones. La suite
incluye el ensayo real de `pg_dump` y `pg_restore` en una base temporal.

### Corrección relevante en las pruebas de respaldo

`BackupLocalTest` usa `DatabaseTruncation` porque `VACUUM INTO` no puede
ejecutarse dentro de una transacción. La suite completa reutilizaba el estado
estático de migración con una base SQLite `:memory:` nueva, lo que producía un
fallo intermitente. La prueba ahora fuerza la migración de su propia conexión en
`setUp()` y valida el respaldo según el motor (`database.sqlite` o
`database.dump`).

## 5. PostgreSQL de ensayo

- Servidor: PostgreSQL 18.3 en `127.0.0.1:5432`.
- Base: `eleam_staging`.
- Migraciones aplicadas: **27**.
- Administrador de ensayo: `staging-admin@eleam.cl` (credencial fuera del
  repositorio).
- Parámetros de noviembre de 2026: UF 38.000, UTM 65.000 y sueldo mínimo
  500.000, marcados como **manuales y verificados** por el administrador de
  ensayo el 2026-11-04.

> Esos valores son de ensayo, no valores legales vigentes. No deben copiarse a
> una base real sin contraste con la normativa y verificación humana.

Comandos validados en esa base:

- `eleam:parametros-actualizar` y verificación de parámetros.
- `notificaciones:diarias --dry-run`.
- `reporte:notificaciones --periodo=mensual --guardar` (PDF).
- `db:optimize`.
- `log:clear`.
- Respaldo y restauración de PostgreSQL en una base temporal.

## 6. Respaldo y restauración

El comando `eleam:backup-local` genera una carpeta con:

- Copia consistente de la base: SQLite con `VACUUM INTO` o PostgreSQL con
  `pg_dump` en formato custom.
- `storage.zip` con los archivos adjuntos y documentos.
- `.env` sanitizado, sin secretos operativos.
- `manifest.json` con motor, fecha, versión y hash SHA-256 de los archivos.
- Retención configurable; por defecto se conservan 30 respaldos.

El comando `eleam:verificar-backup` restaura la base en una ubicación temporal,
comprueba los hashes y valida que los archivos estén presentes. Se probó con
SQLite y PostgreSQL. En la instalación Windows, el respaldo diario se ejecuta a
las 02:30 mediante la tarea programada; el menú de la bandeja permite crear uno
inmediato.

La prueba automatizada también confirma que un manifiesto manipulado se detecta
y que la retención elimina los respaldos antiguos.

## 7. Instalación Windows

La capa Windows separa el programa de los datos:

- Programa: `%LOCALAPPDATA%\Programs\ERP-ELEAM`.
- Datos: `%LOCALAPPDATA%\ERP-ELEAM\data`.
- Base: `%LOCALAPPDATA%\ERP-ELEAM\data\database\database.sqlite`.
- Respaldos: `%LOCALAPPDATA%\ERP-ELEAM\data\backups`.
- Configuración: `%LOCALAPPDATA%\ERP-ELEAM\data\config.json`.
- Logs: `%LOCALAPPDATA%\ERP-ELEAM\data\storage\logs`.

El lanzador `ERP-ELEAM.ps1` inicia el servidor local, abre Edge en modo
aplicación, crea el administrador en el primer inicio y deja un icono en la
bandeja del sistema. No requiere permisos de administrador.

Validaciones realizadas en el PC de desarrollo:

- Smoke test del flujo instalado: servidor, `/up`, login, base en la carpeta de
  datos, respaldo y verificación del respaldo.
- La base SQLite no se creó dentro de la carpeta del programa.
- La tarea programada se registró, consultó y eliminó correctamente.
- El actualizador crea un respaldo, verifica su integridad, reemplaza solo la
  carpeta de la aplicación, ejecuta migraciones y revierte si la nueva versión
  no responde.

El instalador `ERP-ELEAM-Setup-0.2.0.exe` se compiló con Inno Setup y se probó
en el PC de desarrollo. **Falta repetir la instalación en un PC Windows limpio**,
sin PHP, Composer ni Node instalados, incluyendo reinicio, actualización y
restauración de un respaldo.

## 8. Riesgos y pendientes

1. **Validación legal/contable:** revisar IUSC, salud Isapre, topes, SIS,
   gratificación, licencias, ausencias y formato del CSV antes de pagar nómina
   real.
2. **Parámetros del período:** la base de ensayo usa valores manuales. En
   producción, cada período debe actualizarse y verificarse por una persona
   responsable.
3. **PC limpio:** probar el instalador en otra máquina o máquina virtual.
4. **Firma digital:** el ejecutable no está firmado; Windows SmartScreen puede
   mostrar una advertencia. Se necesita un certificado de firma de código.
5. **Multiusuario:** la instalación de escritorio es para un PC. Varios equipos
   requieren el despliegue web con PostgreSQL, HTTPS y respaldos externos.
6. **Respaldo externo:** copiar los respaldos fuera del equipo y ensayar la
   restauración completa con adjuntos.
7. **Commit y etiqueta:** el trabajo funcional se publicó en `129358f` y la
   documentación de entrega en el commit apuntado por `v0.2.0`, con push a
   `Chihector/erp-eleam` y `chicohector953/Erp-eleam` y GitHub Release en
   ambos. Los artefactos de `dist/` son anteriores al commit (`HEAD=33bd5c8`
   al compilarse), por lo que deben reconstruirse desde la etiqueta; hashes y
   detalle en [RELEASE_NOTES_0.2.0.md](RELEASE_NOTES_0.2.0.md).

## 9. Evidencia reproducible

```powershell
php artisan test
vendor/bin/phpunit -c phpunit.pgsql.xml
php artisan eleam:backup-local --destino=C:\ruta\respaldos --retencion=30
php artisan eleam:verificar-backup C:\ruta\respaldos\2026-11-04_03-56-25
powershell -ExecutionPolicy Bypass -File windows\Instalador\ERP-ELEAM.ps1 `
    -DataDir "$env:TEMP\erp-smoke-test" -SmokeTest -KeepData
powershell -ExecutionPolicy Bypass -File windows\Instalador\TareaProgramada.ps1 `
    -Accion Estado
```

## 10. Aprobaciones

| Responsabilidad | Nombre | Fecha | Firma |
| --- | --- | --- | --- |
| Validación técnica |  |  |  |
| Validación laboral/contable |  |  |  |
| Aceptación del usuario |  |  |  |

Las casillas vacías deben completarse antes de considerar la entrega aprobada.
A la fecha de publicación de `v0.2.0` (2026-11-17) siguen vacías: la validación
técnica está documentada, pero la revisión laboral/contable y la aceptación del
usuario son tareas humanas pendientes.
