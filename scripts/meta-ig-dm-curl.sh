#!/usr/bin/env bash
# Ejemplos de curl contra Graph API para Instagram DM (no pegues tokens en git).
#
# Uso:
#   export META_GRAPH_ACCESS_TOKEN="tu_token"
#   export META_GRAPH_API_VERSION="v21.0"
#   ./scripts/meta-ig-dm-curl.sh
#
# Opcional: PAGE_ID de la cuenta IG que quieres (ej. @migliorerossana → 688678624318510):
#   export META_PAGE_ID="688678624318510"

set -euo pipefail

: "${META_GRAPH_ACCESS_TOKEN:?Define META_GRAPH_ACCESS_TOKEN}"

VERSION="${META_GRAPH_API_VERSION:-v21.0}"
BASE="https://graph.facebook.com/${VERSION}"

pretty() {
  if command -v jq >/dev/null 2>&1; then jq .
  elif command -v python3 >/dev/null 2>&1; then python3 -m json.tool
  else cat
  fi
}

echo "=== 1) Páginas + cuenta Instagram Business vinculada (IDs útiles vs webhook entry.id) ==="
curl -sS -G "${BASE}/me/accounts" \
  --data-urlencode "fields=id,name,instagram_business_account{id,username}" \
  --data-urlencode "access_token=${META_GRAPH_ACCESS_TOKEN}" | pretty

echo ""
echo "=== 2) Conversaciones DM por Page (necesitas META_PAGE_ID del paso 1) ==="

if [[ -z "${META_PAGE_ID:-}" ]]; then
  echo "Omitido: export META_PAGE_ID=<id_de_tu_page>"
  exit 0
fi

echo "GET ${BASE}/${META_PAGE_ID}/conversations ?platform=instagram&limit=1"
curl -sS -G "${BASE}/${META_PAGE_ID}/conversations" \
  --data-urlencode "platform=instagram" \
  --data-urlencode "limit=1" \
  --data-urlencode "fields=id,updated_time,senders{name,username}" \
  --data-urlencode "access_token=${META_GRAPH_ACCESS_TOKEN}" | pretty

echo ""
echo "=== 3) Mensajes de una conversación (opcional: META_CONVERSATION_ID del paso 2) ==="
if [[ -z "${META_CONVERSATION_ID:-}" ]]; then
  echo "Omitido: export META_CONVERSATION_ID=<t:id_de_conversacion>"
  exit 0
fi

curl -sS -G "${BASE}/${META_CONVERSATION_ID}" \
  --data-urlencode "fields=messages{id,message,created_time,from,to}" \
  --data-urlencode "access_token=${META_GRAPH_ACCESS_TOKEN}" | pretty
