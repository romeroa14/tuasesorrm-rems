#!/usr/bin/env bash
# Monitor webhook dead letter queue. Exit 1 if dead > 0 (for cron/alerting).
# Example cron (every 5 min): */5 * * * * /opt/docker/rems/tuasesorrm-rems/deploy/scripts/crm-queue-health.sh >> /var/log/rems-queue-health.log 2>&1
set -euo pipefail
docker exec rems-app php /var/www/html/spark queue:dead --check
