#!/usr/bin/env bash
# Simula POST /api/webhook/instagram (misma forma que Meta).
# Requiere PHP CLI (usa scripts/crm-ai-webhook-json.php, sin jq).
#
#   export WEBHOOK_BASE_URL="https://tu-dominio.com"
#   export IG_ENTRY_ID="17841407185102827"
#   export SENDER_PSID="1234567890"
#   bash scripts/crm-ai-webhook-demo.sh "Busco casa 3 dormitorios"

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASE="${WEBHOOK_BASE_URL:-http://127.0.0.1:8080}"
TEXT="${1:-Hola, busco información de una propiedad}"

JSON="$(php "$ROOT/scripts/crm-ai-webhook-json.php" "$TEXT")"

curl -sS -X POST "${BASE%/}/api/webhook/instagram" \
  -H "Content-Type: application/json" \
  -d "$JSON"

echo
