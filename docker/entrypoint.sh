#!/bin/sh
# =============================================================================
# ERP ELEAM — Entrypoint de producción
# =============================================================================
# Ejecuta las tareas previas al arranque (esperar la base de datos, preparar
# storage, cachear configuración y migrar) y luego inicia supervisor.
# =============================================================================
set -e

cd /var/www/html

echo "[eleam] Preparando estructura de storage..."
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# ── Esperar a que PostgreSQL acepte conexiones ───────────────────────────────
if [ "$DB_CONNECTION" = "pgsql" ] && [ -n "$DB_HOST" ]; then
    echo "[eleam] Esperando a PostgreSQL en ${DB_HOST}:${DB_PORT:-5432}..."
    intento=1
    while [ "$intento" -le 30 ]; do
        if php -r '
            $host = getenv("DB_HOST"); $port = getenv("DB_PORT") ?: 5432;
            $conn = @fsockopen($host, (int) $port, $errno, $errstr, 2);
            exit($conn ? 0 : 1);
        '; then
            echo "[eleam] PostgreSQL disponible."
            break
        fi
        echo "[eleam] Intento ${intento}/30 — PostgreSQL aún no responde, esperando 2s..."
        intento=$((intento + 1))
        sleep 2
    done
fi

# ── Migraciones (idempotente: solo aplica las pendientes) ────────────────────
if [ "$RUN_MIGRATIONS" = "true" ] && [ -n "$DB_CONNECTION" ]; then
    echo "[eleam] Aplicando migraciones de base de datos..."
    php artisan migrate --force --no-interaction
fi

# ── Cachés de producción (acelera cada petición) ─────────────────────────────
echo "[eleam] Generando cachés de configuración, rutas, vistas y Filament..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize

echo "[eleam] Iniciando servicios (nginx + php-fpm)..."
exec "$@"
