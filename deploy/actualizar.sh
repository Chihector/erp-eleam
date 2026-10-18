#!/usr/bin/env bash
# =============================================================================
# ERP ELEAM — Actualizador del servidor (sin Docker)
# =============================================================================
# Trae la última versión del código, actualiza dependencias, ejecuta
# migraciones nuevas y regenera las cachés. No pierde ningún dato.
#
# Uso en el servidor:
#   sudo bash /var/www/eleam/deploy/actualizar.sh
# =============================================================================
set -euo pipefail

APP_DIR="/var/www/eleam"
PHP_VER="${PHP_VER:-8.3}"

azul()  { echo -e "\n\033[1;34m[ELEAM]\033[0m $*"; }
ok()    { echo -e "\033[1;32m  ✔\033[0m $*"; }
error() { echo -e "\033[1;31m[ERROR]\033[0m $*" >&2; }

if [[ $EUID -ne 0 ]]; then
    error "Ejecuta con sudo: sudo bash $APP_DIR/deploy/actualizar.sh"
    exit 1
fi

cd "$APP_DIR"

azul "1/6 Mantenimiento activado (los usuarios verán 'En mantenimiento')..."
sudo -u www-data php artisan down --retry=15 >/dev/null 2>&1 || true

azul "2/6 Descargando la última versión..."
git pull --ff-only
ok "Código actualizado."

azul "3/6 Actualizando dependencias..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --quiet
ok "Dependencias actualizadas."

azul "4/6 Aplicando migraciones nuevas..."
sudo -u www-data php artisan migrate --force --ansi
ok "Base de datos al día."

azul "5/6 Regenerando cachés..."
sudo -u www-data php artisan optimize:clear >/dev/null
sudo -u www-data php artisan config:cache >/dev/null
sudo -u www-data php artisan route:cache >/dev/null
sudo -u www-data php artisan view:cache >/dev/null
sudo -u www-data php artisan filament:optimize >/dev/null 2>&1 || true
chown -R www-data:www-data storage bootstrap/cache
ok "Cachés regeneradas."

azul "6/6 Reactivando el sistema..."
sudo -u www-data php artisan up >/dev/null
systemctl reload "php$PHP_VER-fpm" 2>/dev/null || true
systemctl reload nginx 2>/dev/null || true
ok "Actualización completada. El sistema está en línea."
