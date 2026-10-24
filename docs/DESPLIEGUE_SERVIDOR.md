# 🌐 Guía de Despliegue como App Web (sin Docker)

Esta guía explica cómo publicar el **ERP ELEAM** en internet con tu propio
dominio (ej: `erp.tu-eleam.cl`), de modo que tú y tu equipo entren desde
cualquier computador o teléfono, sin depender de ninguna PC encendida.

> **¿Docker?** No es necesario. Esta guía usa un servidor Ubuntu estándar.
> Si más adelante prefieres Docker, existe la guía alternativa
> **[DESPLIEGUE_DOCKER.md](DESPLIEGUE_DOCKER.md)**.

---

## 📦 Lo que necesitas contratar

| Elemento | Para qué | Costo referencial |
| :--- | :--- | :--- |
| **Dominio** | Tu dirección web (ej: `erp.tu-eleam.cl`) | ~$10 USD / año |
| **Servidor VPS** (Ubuntu 24.04) | Donde vivirá el sistema | ~$5–7 USD / mes |

**Proveedores de VPS recomendados** (todos funcionan igual):

- [Hetzner](https://www.hetzner.com/cloud) — CX22 (~€4/mes), el más económico.
- [DigitalOcean](https://www.digitalocean.com) — Droplet básico ($6/mes).
- [Vultr](https://www.vultr.com) — desde $6/mes.
- Proveedores chilenos (HostDime, SolucionHost) — precios similares, soporte local.

**Registradores de dominio:** NIC Chile (para `.cl`), Namecheap, Cloudflare.

> 💡 **Alternativa sin servidor propio:** Railway, Render o Laravel Cloud
> permiten subir el sistema desde GitHub sin administrar un servidor, pero
> tienen costos mensuales mayores y menos control. Para un ELEAM,
> el VPS es la opción más económica y estable.

---

## 🚀 Puesta en marcha (30 minutos)

### Paso 1 — Contratar el VPS

1. Crea la cuenta en tu proveedor y crea un servidor con:
   - **Sistema operativo:** Ubuntu 24.04 LTS
   - **Plan:** el más básico (1–2 GB de RAM es suficiente)
2. Anota la **dirección IP** del servidor (ej: `203.0.113.50`).
3. Anota la **contraseña de root** (o configura una llave SSH).

### Paso 2 — Apuntar el dominio al servidor

En el panel de tu dominio (NIC Chile, Cloudflare, etc.), crea un **registro DNS tipo A**:

| Tipo | Nombre | Valor |
| :--- | :--- | :--- |
| A | `erp` (o el subdominio que quieras) | `203.0.113.50` (la IP del VPS) |

Espera de 5 a 30 minutos a que propague. Verifica en el navegador que
`http://erp.tu-eleam.cl` al menos intenta conectar.

### Paso 3 — Conectarse al servidor

**Opción A — Desde Windows (PowerShell):**
```powershell
ssh root@203.0.113.50
```

**Opción B — Consola web del proveedor:**
Todos los proveedores tienen una consola en el navegador (botón "Console" / "Terminal").
Úsala si el SSH te da problemas.

### Paso 4 — Ejecutar el instalador automático

Conéctate por SSH y ejecuta **un solo comando** (reemplaza el dominio, el repo
y tu correo):

```bash
sudo bash -c "$(curl -fsSL https://raw.githubusercontent.com/chicohector953/Erp-eleam/master/deploy/instalar-servidor.sh)" \
    erp.tu-eleam.cl \
    https://github.com/chicohector953/Erp-eleam.git \
    tu-correo@tu-eleam.cl
```

> ℹ️ Si el comando anterior falla porque el repositorio es privado, descarga el
> proyecto manualmente (opción alternativa del Paso 4B al final de esta guía).

El instalador hace **todo automáticamente**:

1. Instala nginx, PHP, PostgreSQL, Composer.
2. Crea la base de datos segura con contraseña aleatoria.
3. Descarga el código desde GitHub.
4. Genera el archivo de configuración de producción con clave propia.
5. Aplica las migraciones y datos iniciales.
6. Configura nginx para servir el sistema.
7. **Activa HTTPS** con Let's Encrypt (si diste el correo).
8. Crea tu usuario administrador **con contraseña segura** y la muestra en pantalla.

**Anota la contraseña que aparece al final** — es la de tu cuenta `admin@eleam.cl`.

### Paso 5 — Entrar al sistema

Abre en el navegador:

👉 **https://erp.tu-eleam.cl/admin**

Inicia sesión con:
- **Usuario:** `admin@eleam.cl`
- **Clave:** la que mostró el instalador (anótala en un lugar seguro).

---

## 🔄 Tareas del día a día

### Actualizar el sistema con una versión nueva

Cada vez que subas cambios a GitHub:

```bash
sudo bash /var/www/eleam/deploy/actualizar.sh
```

El script activa el modo mantenimiento, descarga el código, aplica migraciones
nuevas y reactiva todo. **No se pierde ningún dato.**

### Respaldar (¡muy importante!)

```bash
sudo bash /var/www/eleam/deploy/respaldar.sh
```

Guarda la base de datos y los archivos subidos en `/var/backups/eleam/`.

**Respaldo automático diario:** el instalador ya crea `/etc/cron.d/eleam`
con el scheduler de Laravel y este respaldo a las 3:00 AM. Si instalaste con una
versión anterior, agrégalo manualmente:

```bash
sudo crontab -e
```

Agrega esta línea al final y guarda:

```
0 3 * * * bash /var/www/eleam/deploy/respaldar.sh >> /var/log/eleam-respaldo.log 2>&1
```

Esto respalda todos los días a las 3:00 AM y borra respaldos de más de 30 días.
El respaldo usa `pg_dump`; el comando `backup:google-drive` solo respalda SQLite.
Copia `/var/backups/eleam/` a un destino externo y prueba una restauración.

Verifica que el scheduler quede registrado:

```bash
sudo -u www-data php artisan schedule:list
```

### Crear más usuarios administradores

```bash
cd /var/www/eleam
sudo -u www-data php artisan eleam:admin-crear otro-admin@eleam.cl --rut=12345678-9
```

---

## ❓ Preguntas frecuentes

**¿Debo subir mis cambios a GitHub primero?**
Sí. El servidor descarga el código desde GitHub. Desde tu PC:
```bash
git add .
git commit -m "Descripción de los cambios"
git push
```

**¿Qué pasa si el instalador dice que certbot falló?**
Generalmente es porque el DNS aún no propaga. Espera unos minutos y ejecuta:
```bash
sudo certbot --nginx -d erp.tu-eleam.cl
```

**¿Cómo veo los errores del sistema?**
```bash
tail -f /var/www/eleam/storage/logs/laravel-$(date +%Y-%m-%d).log
```

**¿Cómo reinicio el sistema si algo se cuelga?**
```bash
sudo systemctl restart nginx php8.3-fpm postgresql
```

**¿Cuántos usuarios aguanta este servidor?**
Un VPS básico (1–2 GB RAM) soporta cómodamente decenas de usuarios
simultáneos navegando, más que suficiente para uno o varios ELEAM.

**¿Y si mi repositorio es privado?**
El servidor no podrá descargarlo solo. Opciones:
1. Hacer el repositorio público (más simple).
2. Subir el código manualmente (Paso 4B).

---

## 📎 Paso 4B — Alternativa: subir el código manualmente

Si prefieres no usar GitHub en el servidor:

**Desde tu PC (PowerShell), en la carpeta del proyecto:**

```powershell
# Crear un archivo comprimido del proyecto (sin dependencias ni datos locales)
# Nota: crea un .zip con todo el proyecto excepto vendor, node_modules y .env
Compress-Archive -Path * -DestinationPath eleam.zip -Force
```

Luego, **desde el servidor**:

```bash
mkdir -p /var/www/eleam
cd /var/www/eleam
# (sube el archivo con la consola del proveedor o scp, luego:)
unzip eleam.zip
# y ejecuta el instalador indicando la carpeta actual como origen
sudo bash deploy/instalar-servidor.sh erp.tu-eleam.cl
```

> En este caso el paso de `git clone` se omite automáticamente porque
> la carpeta ya contiene el código.

---

## 🧭 Resumen visual

```
   Tú y tu equipo                    Internet                 Servidor VPS
  ┌──────────────┐               ┌─────────────┐           ┌────────────────┐
  │  PC oficina  │──┐            │             │           │  nginx + PHP   │
  ├──────────────┤  │  HTTPS     │   Dominio   │           │  ├─ ERP ELEAM  │
  │  Teléfonos   │──┼───────────▶│ erp.tu-     │──────────▶│  └─ PostgreSQL │
  ├──────────────┤  │            │ eleam.cl    │           │                │
  │  Tablet      │──┘            │             │           │  Respaldos     │
  └──────────────┘               └─────────────┘           │  automáticos   │
                                                           └────────────────┘
```
