# Notas de lanzamiento — ERP ELEAM v0.2.0

**Fecha de publicación:** 2026-11-17
**Etiqueta:** `v0.2.0`
**Estado:** piloto técnico / demostración. **No aprobado para producción ni para pago de remuneraciones reales.**

## Resumen

v0.2.0 convierte al MVP en un paquete instalable para un PC con Windows,
añade respaldos locales verificables con manifiesto SHA-256 y refuerza el
cierre de liquidaciones con una copia de todos los parámetros legales usados
en el cálculo. Incluye la suite automatizada en verde en SQLite y PostgreSQL.

## Cambios principales

### Instalación y operación en Windows

- Instalador de escritorio `ERP-ELEAM-Setup-0.2.0.exe` (Inno Setup 6.7.3):
  programa y datos separados, bandeja del sistema, tarea programada,
  actualizador con respaldo previo y reversa.
- Lanzador `windows/Instalador/ERP-ELEAM.ps1`, desinstalador, actualizador
  `Actualizar-ERP.ps1`, programador de tareas y script de smoke test.
- `bootstrap/app.php` honra `ELEAM_DATA_DIR` para mover `.env` y `storage`
  fuera de la carpeta del programa sin mover `database/migrations`.

### Respaldo y recuperación

- Comandos `eleam:backup-local` y `eleam:verificar-backup`: copia consistente
  (`VACUUM INTO` en SQLite, `pg_dump` en PostgreSQL), `storage.zip`,
  `.env` sanitizado y `manifest.json` con hashes SHA-256.
- Retención configurable (30 respaldos por defecto) y verificación que detecta
  manifiestos manipulados.
- `app/Support/EnvSanitizer.php` redacta `KEY`, `SECRET`, `PASSWORD`, `TOKEN` y
  `PRIVATE`. Tener en cuenta: un restaurado en equipo nuevo requiere regenerar
  `APP_KEY` si hay datos cifrados.
- Respaldo a Google Drive para ambos motores y tareas programadas diarias.

### Remuneraciones

- Nuevas columnas `sueldo_minimo_usado`, `tope_afp_usado`, `tope_ips_usado` y
  `tope_cesantia_usado` en `liquidacions` (migración
  `2026_11_04_000001_add_parametros_usados_to_liquidacions_table.php`).
- `RemuneracionService::cerrar()` valida los parámetros **vigentes del período**
  y compara UF, UTM, sueldo mínimo, topes AFP/IPS/cesantía y SIS. Si difieren,
  bloquea el cierre y pide recalcular.
- Cierre/reapertura con trazabilidad de usuario, fecha y motivo; borrado
  reversible de liquidaciones.

## Validación ejecutada

| Área | Resultado | Evidencia |
| --- | --- | --- |
| Suite SQLite | 128 aprobadas, 1 omitida, 487 aserciones | `php artisan test` (repetido 2026-11-17) |
| Suite PostgreSQL | 129 aprobadas, 493 aserciones | `vendor/bin/phpunit -c phpunit.pgsql.xml`, 2026-11-04 en PG 18.3 |
| CI | Ambos jobs en verde | GitHub Actions run `42226018600`, PHP 8.2/SQLite y PHP 8.3/PG 17 |
| Migraciones | 27 aplicadas | SQLite y PostgreSQL |
| Flujo punta a punta | Verde | `AceptacionFlujoCompletoTest` |
| Respaldo/restauración | Verde | `BackupLocalTest`, `RespaldoPostgresTest` |

> La validación local documentada usa PostgreSQL 18.3 y CI usa PostgreSQL 17;
> ambas combinaciones pasan. No hay una certificación legal ni contable detrás
> de estos resultados.

## Código y publicación

- Commit funcional: `129358f` — *feat: backups locales, instalador Windows y mejoras de liquidaciones*.
- Commit de documentación de esta release: el apuntado por la etiqueta `v0.2.0`.
- Repositorios publicados (ambos sincronizados):
  - `https://github.com/Chihector/erp-eleam.git`
  - `https://github.com/chicohector953/Erp-eleam.git`
- Documentación de detalle: `docs/VALIDACION_TECNICA_2026-11-04.md`,
  `docs/HOJA_DE_RUTA.md`, `docs/CONTEXTO_DEL_PROYECTO.md` y
  `docs/CONTINUAR_CON_IA.md`.

## Artefactos distribuidos

**Advertencia de procedencia:** los binarios de `dist/` se construyeron el
2026-11-04 desde el árbol de trabajo cuando `HEAD` era `33bd5c8`. El
`version.json` incrustado en el ZIP portable declara `commit: 33bd5c8`, es
decir, **no apuntan a `129358f` ni a la etiqueta `v0.2.0`**. Deben
reconstruirse desde la etiqueta antes de distribuirlos. En este entorno no
está disponible Inno Setup 6.7.3, por lo que no fue posible recompilar el
`.exe` durante la preparación de esta release.

| Artefacto | Tamaño (bytes) | SHA-256 |
| --- | --- | --- |
| `dist/ERP-ELEAM-Setup-0.2.0.exe` | 24.668.676 | `4445b173f05e0dc204447d39b77bafce5747403fc6c5494e8ef1b096ae2e8894` |
| `dist/ERP-ELEAM-portable-0.2.0.zip` | 111.397.667 | `b21af7ec442fac20abe1139e53bba6be1e2564b45f05ef522c76638323bfc33a` |
| `dist/ERP-ELEAM-portable-preview.zip` | 114.971.979 | `273b2b2ac1fe29943d2819bb3d0bebd3a8e541bc4fcf3065e7058a561710cf18` |

Los hashes también están en `CHECKSUMS.sha256` en la raíz del repositorio.
`dist/` está excluido por `.gitignore`: los archivos existen en la máquina de
desarrollo, no en un checkout limpio.

```bash
sha256sum -c CHECKSUMS.sha256
```

## Límites conocidos (P0 pendiente)

1. Revisión laboral/contable de IUSC, Isapre, topes, gratificación, licencias
   pendientes, ausencias y prorrateo por asistencia, con aprobación firmada.
2. Definición de alcance (demo, piloto o producción) con el usuario de negocio.
3. Verificación humana de los parámetros legales de cada período.
4. Prueba del instalador en un PC Windows limpio, sin herramientas de desarrollo.
5. Firma digital del ejecutable o aviso documentado de SmartScreen.
6. Ensayo de restauración completa con adjuntos y copia del respaldo fuera del equipo.
7. Secretos propios, HTTPS, `APP_DEBUG=false` y correo operativo para servidor.
8. Reconstrucción de los artefactos `dist/` desde `v0.2.0` y actualización de los hashes.

La tabla de aprobaciones de `docs/VALIDACION_TECNICA_2026-11-04.md` permanece
vacía a propósito: completarla es una tarea humana previa a cualquier entrega formal.

## Para el portafolio

Puntos que conviene destacar al presentar el proyecto:

- Arquitectura Laravel 12 + Filament 5 con roles y políticas (4 perfiles),
  auditoría, reportes PDF/CSV y 128 pruebas automatizadas.
- Soporte dual de base de datos (SQLite y PostgreSQL) con CI en ambos motores.
- Empaquetado real para escritorio Windows con actualización con reversa.
- Respaldo verificable con manifiestos SHA-256 y restauración ensayada.
- Documentación honesta de límites: cálculos laborales de apoyo, pendientes de
  validación profesional.

Demo local: `composer install`, copiar `.env.example` a `.env`,
`php artisan key:generate`, tocar `database/database.sqlite`,
`php artisan migrate --seed`, `npm install && npm run build`,
`php artisan eleam:admin-crear admin@eleam.cl --nombre=Administrador --apellido=Local --rut=11111111-1`
y `php artisan serve`. Los datos sintéticos se cargan con
`php artisan db:seed --class=DemoOperacionalSeeder`.
