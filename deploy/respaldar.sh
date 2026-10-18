#!/usr/bin/env bash
# =============================================================================
# ERP ELEAM — Respaldo automático (base de datos + archivos)
# =============================================================================
# Genera un respaldo de PostgreSQL y de los archivos subidos (storage/app),
# y borra automáticamente los respaldos de más de 30 días.
#
# Uso manual:
#   sudo bash /var/www/eleam/deploy/respaldar.sh
#
# Programación automática diaria (recomendado) — ejecutar una vez:
#   sudo crontab -e
#   y agregar la línea:
#   0 3 * * * bash /var/www/eleam/deploy/respaldar.sh >> /var/log/eleam-respaldo.log 2>&1
# =============================================================================
set -euo pipefail

APP_DIR="/var/www/eleam"
DB_NOMBRE="eleam"
DB_USUARIO="eleam"
DESTINO="/var/backups/eleam"
RETENCION_DIAS=30
FECHA="$(date +%Y-%m-%d_%H-%M)"

mkdir -p "$DESTINO"

echo "[ELEAM] Respaldando base de datos..."
sudo -u postgres pg_dump -U "$DB_USUARIO" "$DB_NOMBRE" | gzip > "$DESTINO/base_$FECHA.sql.gz"

echo "[ELEAM] Respaldando archivos subidos..."
tar -czf "$DESTINO/archivos_$FECHA.tar.gz" -C "$APP_DIR/storage" app 2>/dev/null || true

echo "[ELEAM] Eliminando respaldos con más de $RETENCION_DIAS días..."
find "$DESTINO" -name "*.gz" -mtime +"$RETENCION_DIAS" -delete

echo "[ELEAM] Respaldo completado:"
ls -lh "$DESTINO" | tail -n +2
