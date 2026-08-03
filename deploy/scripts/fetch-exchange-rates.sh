#!/bin/sh
# Actualiza tasas de cambio desde DolarAPI (ve.dolarapi.com).
# Uso en cron del host:
#   0 6,12,18 * * * /opt/docker/rems/tuasesorrm-rems/deploy/scripts/fetch-exchange-rates.sh >> /var/log/rems-fetch-rates.log 2>&1

set -e
docker exec rems-app php /var/www/html/spark finance:fetch-rates
