#!/bin/sh
# Actualiza tasas de cambio desde DolarAPI (ve.dolarapi.com).
# Uso en cron del host (log en home del usuario — /var/log requiere root):
#   0 6,12,18 * * * /opt/docker/rems/tuasesorrm-rems/deploy/scripts/fetch-exchange-rates.sh >> "$HOME/logs/rems-fetch-rates.log" 2>&1

set -e
LOG_DIR="${REMS_FETCH_RATES_LOG_DIR:-$HOME/logs}"
mkdir -p "$LOG_DIR"
docker exec rems-app php /var/www/html/spark finance:fetch-rates
