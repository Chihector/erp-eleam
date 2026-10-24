#!/usr/bin/env bash
# =============================================================================
# ERP ELEAM — Instalador de servidor web de producción (sin Docker)
# =============================================================================
# Instala y configura TODO en un servidor Ubuntu/Debian limpio:
#   nginx + PHP-FPM + PostgreSQL + Composer + HTTPS (Let's Encrypt)
#
# Uso (en el servidor, como root o con sudo):
#   sudo bash deploy/instalar-servidor.sh erp.midominio.cl \
#        https://github.com/chicohector953/Erp-eleam.git \
#        correo@midominio.cl
#
# El tercer argumento (correo) es opcional: si se indica, se activa HTTPS
# automáticamente con Let's Encrypt.
#
# IMPORTANTE: antes de ejecutarlo, apunta el dominio (registro DNS tipo A)
# a la IP de este servidor y espera unos minutos a que propague.
# =============================================================================
set -euo pipefail

DOMINIO="${1:-}"
REPO="${2:-https://github.com/chicohector953/Erp-eleam.git}"
CORREO_HTTPS="${3:-}"

APP_DIR="/var/www/eleam"
DB_NOMBRE="eleam"
DB_USUARIO="eleam"
PHP_VER="${PHP_VER:-8.3}"

DB_CLAVE="$(openssl rand -base64 32 | tr -dc 'A-Za-z0-9' | cut -c1-28)"
MARKER="$APP_DIR/storage/.instalado"

azul()  { echo -e "\n\033[1;34m[ELEAM]\033[0m $*"; }
ok()    { echo -e "\033[1;32m  ✔\033[0m $*"; }
aviso() { echo -e "\033[1;33m  ⚠\033[0m $*"; }
error() { echo -e "\033[1;31m[ERROR]\033[0m $*" >&2; }

# ── Validaciones previas ─────────────────────────────────────────────────────
if [[ $EUID -ne 0 ]]; then
    error "Ejecuta este script con sudo: sudo bash deploy/instalar-servidor.sh <dominio>"
    exit 1
fi

if [[ -z "$DOMINIO" ]]; then
    error "Falta el dominio."
    echo "  Uso: sudo bash deploy/instalar-servidor.sh erp.midominio.cl [url-repo] [correo-https]"
    exit 1
fi

azul "Instalando ERP ELEAM en https://$DOMINIO"
echo "  Dominio : $DOMINIO"
echo "  Repo    : $REPO"
echo "  Carpeta : $APP_DIR"

# ── 1. Paquetes del sistema ──────────────────────────────────────────────────
azul "1/9 Instalando nginx, PHP $PHP_VER, PostgreSQL y Composer..."
export DEBIAN_FRONTEND=noninteractive

apt-get update -qq
apt-get install -y -qq \
    nginx \
    postgresql \
    "php$PHP_VER-fpm" \
    "php$PHP_VER-cli" \
    "php$PHP_VER-pgsql" \
    "php$PHP_VER-mbstring" \
    "php$PHP_VER-xml" \
    "php$PHP_VER-curl" \
    "php$PHP_VER-zip" \
    "php$PHP_VER-bcmath" \
    "php$PHP_VER-intl" \
    "php$PHP_VER-sqlite3" \
    "php$PHP_VER-opcache" \
    git unzip curl openssl >/dev/null

if ! command -v composer >/dev/null 2>&1; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --quiet
fi
ok "Paquetes instalados."

# ── 2. PostgreSQL: crear usuario y base de datos ─────────────────────────────
azul "2/9 Configurando PostgreSQL..."
systemctl enable --now postgresql >/dev/null 2>&1

if ! sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname='$DB_USUARIO'" | grep -q 1; then
    sudo -u postgres psql -c "CREATE USER $DB_USUARIO WITH PASSWORD '$DB_CLAVE';" >/dev/null
    ok "Usuario de base de datos creado."
else
    sudo -u postgres psql -c "ALTER USER $DB_USUARIO WITH PASSWORD '$DB_CLAVE';" >/dev/null
    ok "Usuario de base de datos actualizado."
fi

if ! sudo -u postgres psql -tAc "SELECT 1 FROM pg_database WHERE datname='$DB_NOMBRE'" | grep -q 1; then
    sudo -u postgres createdb -O "$DB_USUARIO" "$DB_NOMBRE"
    ok "Base de datos '$DB_NOMBRE' creada."
else
    ok "Base de datos '$DB_NOMBRE' ya existe (se conserva)."
fi

# ── 3. Código de la aplicación ───────────────────────────────────────────────
azul "3/9 Descargando el código..."
if [[ -d "$APP_DIR/.git" ]]; then
    git -C "$APP_DIR" pull --ff-only
    ok "Código actualizado (git pull)."
else
    mkdir -p "$(dirname "$APP_DIR")"
    git clone --depth 1 "$REPO" "$APP_DIR"
    ok "Código descargado."
fi

cd "$APP_DIR"

# ── 4. Archivo .env de producción ────────────────────────────────────────────
azul "4/9 Configurando variables de entorno (.env)..."
if [[ ! -f .env ]] || [[ ! -f "$MARKER" ]]; then
    cat > .env <<ENVFILE
APP_NAME="ERP ELEAM"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://$DOMINIO

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_ES

APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=$DB_NOMBRE
DB_USERNAME=$DB_USUARIO
DB_PASSWORD=$DB_CLAVE

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MAIL_MAILER=log
MAIL_FROM_ADDRESS="erp@$DOMINIO"
MAIL_FROM_NAME="\${APP_NAME}"
ENVFILE
    php artisan key:generate --force --ansi >/dev/null
    ok "Archivo .env creado con clave propia."
else
    aviso ".env ya existía; se conserva la configuración anterior."
fi

# ── 5. Dependencias PHP y base de datos ──────────────────────────────────────
azul "5/9 Instalando dependencias y preparando la base de datos..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --quiet
ok "Dependencias de Composer instaladas."

mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/logs bootstrap/cache

php artisan migrate --force --ansi
php artisan db:seed --force --ansi >/dev/null 2>&1 || true
ok "Base de datos migrada y datos iniciales cargados."

# ── 6. Permisos ──────────────────────────────────────────────────────────────
azul "6/9 Ajustando permisos..."
chown -R www-data:www-data "$APP_DIR"
chmod -R ug+rwx storage bootstrap/cache
ok "Permisos correctos (www-data)."

# ── 6b. Tareas programadas (scheduler y respaldo diario) ─────────────────────
# Laravel necesita schedule:run cada minuto. Sin esto no se envían
# notificaciones, reportes ni respaldos automáticos.
cat > /etc/cron.d/eleam <<CRON
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
* * * * * www-data cd $APP_DIR && /usr/bin/php$PHP_VER artisan schedule:run >> $APP_DIR/storage/logs/scheduler-cron.log 2>&1
0 3 * * * root bash $APP_DIR/deploy/respaldar.sh >> /var/log/eleam-respaldo.log 2>&1
CRON
chmod 644 /etc/cron.d/eleam
ok "Scheduler de Laravel y respaldo diario programados (cron)."

# ── 7. nginx ─────────────────────────────────────────────────────────────────
azul "7/9 Configurando nginx..."
cat > /etc/nginx/sites-available/eleam <<NGINXCONF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMINIO;

    root $APP_DIR/public;
    index index.php;

    charset utf-8;
    client_max_body_size 25M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~* \.(css|js|mjs|woff2?|ttf|eot|svg|png|jpe?g|gif|ico|webp)\$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files \$uri =404;
    }

    location ~ \.php\$ {
        fastcgi_pass unix:/run/php/php$PHP_VER-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_read_timeout 120s;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINXCONF

ln -sf /etc/nginx/sites-available/eleam /etc/nginx/sites-enabled/eleam
rm -f /etc/nginx/sites-enabled/default
nginx -t >/dev/null 2>&1
systemctl reload nginx
systemctl enable --now "php$PHP_VER-fpm" >/dev/null 2>&1
ok "nginx sirviendo $DOMINIO en el puerto 80."

# ── 8. HTTPS con Let's Encrypt ───────────────────────────────────────────────
azul "8/9 Configurando HTTPS..."
if [[ -n "$CORREO_HTTPS" ]]; then
    apt-get install -y -qq certbot python3-certbot-nginx >/dev/null
    if certbot --nginx -d "$DOMINIO" -m "$CORREO_HTTPS" --agree-tos --redirect -n >/dev/null 2>&1; then
        ok "HTTPS activado con renovación automática."
    else
        aviso "No se pudo activar HTTPS todavía. Revisa que el dominio apunte a este servidor"
        aviso "y luego ejecuta: sudo certbot --nginx -d $DOMINIO"
    fi
else
    aviso "Sin correo HTTPS: se omitió Let's Encrypt por ahora."
    aviso "Para activarlo después: sudo certbot --nginx -d $DOMINIO"
fi

# ── 9. Optimización y usuario administrador ──────────────────────────────────
azul "9/9 Optimizando y creando tu cuenta de administrador..."
php artisan config:cache >/dev/null
php artisan route:cache >/dev/null
php artisan view:cache >/dev/null
php artisan filament:optimize >/dev/null 2>&1 || true
ok "Cachés de producción generadas."

if [[ ! -f "$MARKER" ]]; then
    php artisan eleam:admin-crear admin@eleam.cl --nombre=Administrador --apellido=Sistema --rut=11111111-1 --ansi

    # Elimina la cuenta de ejemplo del seeder si conserva la clave por defecto
    sudo -u www-data php artisan tinker --execute="
        \$u = \App\Models\User::where('email', 'hector@eleam.cl')->first();
        if (\$u && \Illuminate\Support\Facades\Hash::check('password', \$u->password)) { \$u->delete(); echo \"cuenta-ejemplo-eliminada\"; }
    " >/dev/null 2>&1 || true

    touch "$MARKER"
    chown www-data:www-data "$MARKER"
fi

# ── Resumen final ────────────────────────────────────────────────────────────
azul "Instalación completada"
echo "  ┌──────────────────────────────────────────────────────────────┐"
if [[ -n "$CORREO_HTTPS" ]]; then
echo "  │  Entra a:  https://$DOMINIO/admin"
else
echo "  │  Entra a:  http://$DOMINIO/admin"
echo "  │  (activa HTTPS con: sudo certbot --nginx -d $DOMINIO)"
fi
echo "  ├──────────────────────────────────────────────────────────────┤"
echo "  │  Usuario :  admin@eleam.cl                                   │"
echo "  │  Clave   :  (la que se mostró arriba al crear la cuenta)     │"
echo "  ├──────────────────────────────────────────────────────────────┤"
echo "  │  Respaldar :  sudo bash $APP_DIR/deploy/respaldar.sh"
echo "  │  Actualizar:  sudo bash $APP_DIR/deploy/actualizar.sh"
echo "  └──────────────────────────────────────────────────────────────┘"
