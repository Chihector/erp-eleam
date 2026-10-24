# 🐳 Guía de Despliegue con Docker + PostgreSQL

Esta guía explica paso a paso cómo pasar el sistema **ERP ELEAM** de la base local
SQLite a una instalación profesional con **Docker** y **PostgreSQL**, incluyendo
la migración automática de los datos existentes.

> **¿Cuándo usar esta guía?** Cuando quieras que el sistema funcione de forma
> estable para varias personas conectadas al mismo tiempo (oficina + celulares),
> con respaldos fáciles y sin depender de la PC de desarrollo.

---

## 📋 Requisitos previos

1. **Docker Desktop** instalado (Windows/Mac) o **Docker Engine** (Linux).
   - Descarga: https://www.docker.com/products/docker-desktop/
   - En Windows, acepta la opción WSL2 cuando lo solicite.
2. Que Docker esté funcionando: abre una terminal y ejecuta:
   ```bash
   docker --version
   docker compose version
   ```
   Deben responder con un número de versión. Si dan error, reinstala Docker Desktop.

---

## 🚀 Puesta en marcha (5 pasos)

### Paso 1 — Crear el archivo de configuración de producción

En la carpeta del proyecto:

```bash
cp .env.production.example .env.production
```

> En Windows/PowerShell usa `copy .env.production.example .env.production`.

Abre `.env.production` con un editor y completa **dos** valores:

| Variable | Qué poner |
| :--- | :--- |
| `APP_KEY` | Genera una con: `php artisan key:generate --show` y pega el resultado. |
| `DB_PASSWORD` | Una contraseña segura nueva (ej: inventa una larga y única). |

> ⚠️ **Importante:** `APP_KEY` cifra las sesiones. Si ya tenías una clave en tu
> `.env` local, puedes reutilizarla para no invalidar sesiones abiertas.

### Paso 2 — Construir y levantar el sistema

```bash
docker compose --env-file .env.production up -d --build
```

La primera vez tarda unos minutos (compila assets, instala PHP y crea la base).
Al terminar, la aplicación queda corriendo en:

👉 **http://localhost:8000/admin**

Desde un teléfono en la misma red Wi-Fi: `http://IP-DE-TU-PC:8000/admin`

> 💡 **Nota:** siempre usa `--env-file .env.production` en los comandos de Docker
> Compose; así se leen la contraseña de la base de datos y el puerto.

### Paso 3 — Migrar los datos antiguos de SQLite (solo la primera vez)

Si quieres conservar los datos que ya tenías (usuarios, trabajadores, parámetros
legales, etc.):

```bash
docker compose --env-file .env.production exec app php artisan eleam:migrate-sqlite
```

Salida esperada:

```
  ✔ users                    2 registros copiados
  ✔ trabajadores             ... registros copiados
  ✔ roles                    4 registros copiados
  ...
  Migración completada: N registros copiados en total.
```

> ℹ️ Si empiezas de cero (sin datos antiguos), puedes omitir este paso.
> El sistema aplicó las migraciones automáticamente al arrancar.

### Paso 4 — Verificar que todo funciona

1. Abre **http://localhost:8000/admin** e inicia sesión con tu usuario.
2. Revisa el menú de la izquierda: debe mostrar solo las secciones
   **Operación · Personal · Finanzas · Configuración** (según tu rol).
3. Entra a **Liquidaciones** y confirma que tus datos están ahí.

### Paso 5 — Cargar datos iniciales si empezaste de cero

Si la base está vacía (instalación nueva), crea los datos mínimos:

```bash
docker compose --env-file .env.production exec app php artisan db:seed --force
```

Y luego crea tu usuario administrador con el comando seguro, que genera una
contraseña aleatoria y la muestra una sola vez:

```bash
docker compose --env-file .env.production exec app \
    php artisan eleam:admin-crear admin@eleam.cl \
    --nombre=Administrador --apellido=Sistema --rut=11111111-1
```

> 🔔 **Tareas programadas:** el contenedor `app` ejecuta `schedule:work` mediante
> supervisord, así que notificaciones, reportes y respaldos funcionan sin un
> servicio aparte. El respaldo a Google Drive soporta SQLite y PostgreSQL; en
> Docker usa el `pg_dump` incluido en la imagen.

---

## 🛠️ Comandos del día a día

| Acción | Comando |
| :--- | :--- |
| Ver que todo esté corriendo | `docker compose --env-file .env.production ps` |
| Ver registros (logs) en vivo | `docker compose --env-file .env.production logs -f app` |
| Detener el sistema | `docker compose --env-file .env.production down` |
| Volver a levantar | `docker compose --env-file .env.production up -d` |
| Aplicar cambios nuevos de código | `docker compose --env-file .env.production up -d --build` |
| Ejecutar cualquier comando Artisan | `docker compose --env-file .env.production exec app php artisan ...` |

---

## 💾 Respaldos (¡muy importante!)

### Respaldar la base de datos

```bash
docker compose --env-file .env.production exec postgres \
    pg_dump --no-owner --clean --if-exists -U eleam eleam > respaldo_2026-10-18.sql
```

### Restaurar un respaldo

```bash
type respaldo_2026-10-18.sql | docker compose --env-file .env.production exec -T postgres \
    psql -U eleam eleam
```

### Recomendación
El contenedor ejecuta el respaldo automático a Google Drive según el scheduler.
Además, programa un respaldo local una vez al día (Programador de tareas de
Windows o cron en Linux) y guarda los archivos en una carpeta segura o en la
nube. Prueba la restauración en una base temporal antes de confiar en el
respaldo. Si usas el respaldo plano de `deploy/respaldar.sh` en un servidor
Linux, puedes ensayarlo con `deploy/probar-restauracion.sh`.

---

## ❓ Preguntas frecuentes

**¿Se pierden los datos si hago `docker compose down`?**
No. Los datos viven en volúmenes (`pgdata` y `storage`). Solo se borran si usas
`down -v` (opción de borrado de volúmenes).

**¿Cómo cambio el puerto?**
Edita `APP_PORT` en `.env.production` (por defecto 8000) y reinicia con
`up -d --build`.

**¿Cómo actualizo el sistema con una versión nueva del código?**
```bash
docker compose --env-file .env.production up -d --build
```
Las migraciones nuevas se aplican solas al arrancar (`RUN_MIGRATIONS=true`).

**¿Funciona en un servidor Linux sin Windows?**
Sí, exactamente igual. Solo cambia `copy` por `cp` al crear `.env.production`.

**¿Qué pasa con el archivo `database/database.sqlite`?**
Se monta como solo-lectura únicamente para la migración inicial. Puedes
conservarlo como respaldo histórico o quitarlo del `docker-compose.yml`
después de migrar.
