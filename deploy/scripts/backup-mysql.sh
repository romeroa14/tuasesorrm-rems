#!/usr/bin/env bash
# Dump MySQL de REMS (producción 158.69.220.222).
# Cron sugerido (usuario asesoresrm):
#   0 2 * * * /opt/docker/rems/tuasesorrm-rems/deploy/scripts/backup-mysql.sh >> $HOME/logs/rems-mysql-backup.log 2>&1
set -euo pipefail

REMS_ROOT="${REMS_ROOT:-/opt/docker/rems/tuasesorrm-rems}"
ENV_FILE="${REMS_ENV_FILE:-$REMS_ROOT/.env}"
MYSQL_CONTAINER="${REMS_MYSQL_CONTAINER:-rems-mysql}"
BACKUP_DIR="${REMS_BACKUP_DIR:-/home/asesoresrm/backups/mysql}"
RETAIN_DAYS="${REMS_BACKUP_RETAIN_DAYS:-14}"
RCLONE_REMOTE="${REMS_BACKUP_RCLONE_REMOTE:-}"

read_env() {
  local key="$1"
  local line val
  line="$(grep -E "^${key}\s*=" "$ENV_FILE" 2>/dev/null | head -1 || true)"
  if [[ -z "$line" ]]; then
    return 1
  fi
  val="${line#*=}"
  val="$(echo "$val" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')"
  val="${val%\"}"
  val="${val#\"}"
  val="${val%\'}"
  val="${val#\'}"
  printf '%s' "$val"
}

if [[ ! -f "$ENV_FILE" ]]; then
  echo "ERROR: no existe $ENV_FILE" >&2
  exit 1
fi

DB_NAME="$(read_env database.default.database || true)"
DB_USER="$(read_env database.default.username || true)"
DB_PASS="$(read_env database.default.password || true)"

if [[ -z "$DB_NAME" || -z "$DB_USER" ]]; then
  echo "ERROR: database.default.database / username no encontrados en .env" >&2
  exit 1
fi

mkdir -p "$BACKUP_DIR"
STAMP="$(date +%Y%m%d-%H%M%S)"
OUT_FILE="$BACKUP_DIR/${DB_NAME}-${STAMP}.sql.gz"
LATEST_LINK="$BACKUP_DIR/${DB_NAME}-latest.sql.gz"

echo "[$(date -Iseconds)] Iniciando dump -> $OUT_FILE"

docker exec "$MYSQL_CONTAINER" mysqldump \
  -u"$DB_USER" -p"$DB_PASS" \
  --single-transaction \
  --no-tablespaces \
  --routines \
  --triggers \
  --events \
  --default-character-set=utf8mb4 \
  "$DB_NAME" | gzip -c > "$OUT_FILE"

SIZE="$(du -h "$OUT_FILE" | awk '{print $1}')"
ln -sf "$(basename "$OUT_FILE")" "$LATEST_LINK"
echo "[$(date -Iseconds)] OK size=$SIZE"

if [[ -n "$RCLONE_REMOTE" ]]; then
  if command -v rclone >/dev/null 2>&1; then
    echo "[$(date -Iseconds)] Subiendo a rclone:$RCLONE_REMOTE"
    rclone copy "$OUT_FILE" "$RCLONE_REMOTE/" --retries 3 --low-level-retries 10
    echo "[$(date -Iseconds)] Upload OK"
  else
    echo "WARN: REMS_BACKUP_RCLONE_REMOTE definido pero rclone no está instalado" >&2
  fi
fi

find "$BACKUP_DIR" -name "${DB_NAME}-*.sql.gz" -type f -mtime +"$RETAIN_DAYS" -delete 2>/dev/null || true
echo "[$(date -Iseconds)] Limpieza: conservar ${RETAIN_DAYS} días en $BACKUP_DIR"
