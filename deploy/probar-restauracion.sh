#!/usr/bin/env bash
# =============================================================================
# ERP ELEAM — Ensayo de restauración de PostgreSQL
# =============================================================================
# Restaura el respaldo más reciente (o el indicado) en una base temporal,
# verifica que el esquema quedó completo y elimina la base temporal.
#
# Uso manual:
#   sudo bash /var/www/eleam/deploy/probar-restauracion.sh
#   sudo bash /var/www/eleam/deploy/probar-restauracion.sh /ruta/base.sql.gz
#
# No toca la base de producción: crea y elimina una base con prefijo
# eleam_restauracion_.
# =============================================================================
set -euo pipefail

DESTINO="/var/backups/eleam"
DB_TEMP="eleam_restauracion_$(date +%Y%m%d_%H%M%S)"
ARCHIVO="${1:-}"

if [ -z "$ARCHIVO" ]; then
    ARCHIVO="$(ls -1t "$DESTINO"/base_*.sql.gz 2>/dev/null | head -n1 || true)"
fi

if [ -z "$ARCHIVO" ] || [ ! -f "$ARCHIVO" ]; then
    echo "[ELEAM] No se encontró un respaldo en $DESTINO." >&2
    exit 1
fi

limpiar() {
    sudo -u postgres dropdb --if-exists "$DB_TEMP" >/dev/null 2>&1 || true
}
trap limpiar EXIT

echo "[ELEAM] Creando base temporal $DB_TEMP..."
sudo -u postgres createdb "$DB_TEMP"

echo "[ELEAM] Restaurando $ARCHIVO..."
gunzip -c "$ARCHIVO" | sudo -u postgres psql --quiet --set ON_ERROR_STOP=1 --dbname "$DB_TEMP" >/dev/null

TABLAS="$(sudo -u postgres psql -tAc "select count(*) from information_schema.tables where table_schema = 'public'" "$DB_TEMP" | tr -d '[:space:]')"

if [ "${TABLAS:-0}" -lt 10 ]; then
    echo "[ELEAM] ERROR: la restauración solo tiene ${TABLAS:-0} tablas." >&2
    exit 1
fi

echo "[ELEAM] Restauración correcta: $TABLAS tablas restauradas."
