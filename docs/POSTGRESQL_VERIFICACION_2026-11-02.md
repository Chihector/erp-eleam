# Verificación PostgreSQL nativo en Windows — 2026-11-02

> Nota histórica. El estado vigente está en
> [VALIDACION_TECNICA_2026-11-04.md](VALIDACION_TECNICA_2026-11-04.md), con
> números actualizados al 2026-11-04 (129 pruebas y 493 aserciones, 27
> migraciones). Este documento se conserva como registro del ensayo original.

## Resumen

Se completó la verificación opcional con PostgreSQL nativo en Windows:
instalación de PostgreSQL 18.3, migraciones, suite completa y comandos
artisan, en la máquina de desarrollo.

## Entorno de prueba

- **PostgreSQL**: 18.3 nativo en Windows
- **Ubicación**: `C:\Program Files\PostgreSQL\18\`
- **Servicio**: `postgresql-x64-18`
- **PHP**: 8.2.12 con `pdo_pgsql` y `pgsql` habilitados
- **Base de datos de pruebas**: `eleam_test`

## Resultados de ese ensayo (2026-11-02)

### SQLite (por defecto)

- 121 aprobados, 1 omitido · 435 aserciones
- Estado: verde

### PostgreSQL 18 nativo

- 122 aprobados, 0 omitidos · 441 aserciones
- Estado: verde · duración ~21.8 segundos
- El test que se omitía con SQLite (`RespaldoPostgresTest`) corre completo con
  PostgreSQL real, probando `pg_dump` y `pg_restore`.

### Migraciones

- 24 migraciones aplicadas sin errores (eran las vigentes en esa fecha; hoy
  son 27).
- Tipos de datos correctos (JSON para `notifications.data`).

### Comandos artisan validados

- `eleam:admin-crear`
- `eleam:parametros-actualizar --mes=11 --anio=2026 --uf=38000 --utm=65000 --sueldo-minimo=500000`
  (valores de ensayo, no legales)
- `notificaciones:diarias --dry-run`
- `reporte:notificaciones --periodo=mensual --guardar`
- `db:optimize` (VACUUM ANALYZE)
- `log:clear`

## Configuración de conexión (local, no para producción)

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=eleam_test
DB_USERNAME=postgres
DB_PASSWORD=<clave local de postgres; no se publica>
```

## Seguridad configurada

- Autenticación `scram-sha-256` configurada en `pg_hba.conf`.
- La clave del usuario `postgres` es local de la máquina de desarrollo y no se
  registra en el repositorio.

## Estado posterior

- La suite se reverificó el 2026-11-04: **129 pruebas y 493 aserciones**,
  **27 migraciones** y respaldo/verificación con `pg_dump`/`pg_restore`.
- Pendientes: prueba en un PC Windows limpio, firma digital y validación
  legal/contable. Ver [HOJA_DE_RUTA.md](HOJA_DE_RUTA.md).
