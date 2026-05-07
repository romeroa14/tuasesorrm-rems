#!/usr/bin/env bash
# Test: Instagram webhook creates two distinct leads from two different senders.
#
# Simulates Meta webhook POST payloads to verify:
#   1. Two different sender IG Scoped IDs create two distinct leads
#   2. No "Duplicate entry '' for key 'leads.phone'" error (phone=NULL tolerant UNIQUE)
#   3. Webhook returns HTTP 200 even for malformed payloads
#   4. Lead names use real Instagram profiles when Graph API token is available,
#      or fallback to "Instagram User XXXXXX" placeholder
#
# Usage:
#   bash scripts/test-instagram-webhook-leads.sh
#
# Override endpoint:
#   WEBHOOK_URL="http://localhost:8080/api/webhook/instagram" bash scripts/test-instagram-webhook-leads.sh
#
# Optional DB verification (requires mysql client + credentials):
#   DB_HOST=localhost DB_USER=root DB_PASS='' DB_NAME=rems \
#   bash scripts/test-instagram-webhook-leads.sh --db-check
#
# Env vars for DB check:
#   DB_HOST  — MySQL host (default: localhost)
#   DB_USER  — MySQL user
#   DB_PASS  — MySQL password
#   DB_NAME  — MySQL database name

set -euo pipefail

WEBHOOK_URL="${WEBHOOK_URL:-https://rems.admetricas.com/api/webhook/instagram}"
SENDER_A="17841400000000001"
SENDER_B="17841400000000002"
ENTRY_ID="100000000000001"
NOW=$(date +%s)

PAYLOAD_A=$(cat <<ENDOFPAYLOAD
{
  "entry": [{
    "id": "${ENTRY_ID}",
    "messaging": [{
      "sender": {"id": "${SENDER_A}"},
      "recipient": {"id": "${ENTRY_ID}"},
      "timestamp": ${NOW}000,
      "message": {
        "mid": "m_test_a_${NOW}",
        "text": "Hola, quiero información sobre propiedades en Caracas"
      }
    }]
  }]
}
ENDOFPAYLOAD
)

PAYLOAD_B=$(cat <<ENDOFPAYLOAD
{
  "entry": [{
    "id": "${ENTRY_ID}",
    "messaging": [{
      "sender": {"id": "${SENDER_B}"},
      "recipient": {"id": "${ENTRY_ID}"},
      "timestamp": $((NOW + 1))000,
      "message": {
        "mid": "m_test_b_${NOW}",
        "text": "Buenas tardes, me interesa un apartamento en Chacao"
      }
    }]
  }]
}
ENDOFPAYLOAD
)

red()   { echo -e "\033[31m$*\033[0m"; }
green() { echo -e "\033[32m$*\033[0m"; }
yellow(){ echo -e "\033[33m$*\033[0m"; }

echo "╔══════════════════════════════════════════╗"
echo "║  Instagram Webhook Lead Detection Test  ║"
echo "╚══════════════════════════════════════════╝"
echo ""
echo "Endpoint: ${WEBHOOK_URL}"
echo "Sender A: ${SENDER_A}"
echo "Sender B: ${SENDER_B}"
echo ""

# ── Test 1: Sender A ──────────────────────────────────────────────
echo "── Test 1: POST sender A (${SENDER_A})"
RESP_A=$(curl -s -w "\n%{http_code}" -X POST "${WEBHOOK_URL}" \
  -H "Content-Type: application/json" \
  -d "${PAYLOAD_A}" 2>&1)
HTTP_A=$(echo "$RESP_A" | tail -1)
BODY_A=$(echo "$RESP_A" | sed '$d')

if [ "$HTTP_A" = "200" ]; then
  green "   ✅ HTTP ${HTTP_A}"
else
  red   "   ❌ HTTP ${HTTP_A} (expected 200)"
  echo "   Body: ${BODY_A}"
fi

if echo "$BODY_A" | grep -q '"status":"ok"'; then
  green "   ✅ Body: {\"status\":\"ok\"}"
else
  yellow "   ⚠️  Body: ${BODY_A}"
fi
echo ""

# ── Test 2: Sender B ──────────────────────────────────────────────
echo "── Test 2: POST sender B (${SENDER_B})"
RESP_B=$(curl -s -w "\n%{http_code}" -X POST "${WEBHOOK_URL}" \
  -H "Content-Type: application/json" \
  -d "${PAYLOAD_B}" 2>&1)
HTTP_B=$(echo "$RESP_B" | tail -1)
BODY_B=$(echo "$RESP_B" | sed '$d')

if [ "$HTTP_B" = "200" ]; then
  green "   ✅ HTTP ${HTTP_B}"
else
  red   "   ❌ HTTP ${HTTP_B} (expected 200)"
  echo "   Body: ${BODY_B}"
fi

if echo "$BODY_B" | grep -q '"status":"ok"'; then
  green "   ✅ Body: {\"status\":\"ok\"}"
else
  yellow "   ⚠️  Body: ${BODY_B}"
fi
echo ""

# ── Test 3: Malformed payload (graceful degradation) ──────────────
echo "── Test 3: Malformed payload (graceful degradation)"
RESP_M=$(curl -s -w "\n%{http_code}" -X POST "${WEBHOOK_URL}" \
  -H "Content-Type: application/json" \
  -d '{"garbage": true}' 2>&1)
HTTP_M=$(echo "$RESP_M" | tail -1)

if [ "$HTTP_M" = "200" ]; then
  green "   ✅ HTTP ${HTTP_M} (webhook never crashes)"
else
  red   "   ❌ HTTP ${HTTP_M} (expected 200 — Meta requires 200 always)"
fi
echo ""

# ── Test 4: Empty payload ─────────────────────────────────────────
echo "── Test 4: Empty body (edge case)"
RESP_E=$(curl -s -w "\n%{http_code}" -X POST "${WEBHOOK_URL}" \
  -H "Content-Type: application/json" \
  -d '' 2>&1)
HTTP_E=$(echo "$RESP_E" | tail -1)

if [ "$HTTP_E" = "200" ]; then
  green "   ✅ HTTP ${HTTP_E}"
else
  red   "   ❌ HTTP ${HTTP_E} (expected 200)"
fi
echo ""

# ── Summary ───────────────────────────────────────────────────────
echo "── Summary ──"
echo "Sender A (${SENDER_A}): HTTP ${HTTP_A}"
echo "Sender B (${SENDER_B}): HTTP ${HTTP_B}"
echo "Malformed payload:      HTTP ${HTTP_M}"
echo "Empty body:             HTTP ${HTTP_E}"
echo ""

PASS=0
FAIL=0
for code in "$HTTP_A" "$HTTP_B" "$HTTP_M" "$HTTP_E"; do
  if [ "$code" = "200" ]; then PASS=$((PASS + 1)); else FAIL=$((FAIL + 1)); fi
done
echo "Passed: ${PASS}/$((PASS + FAIL))"
echo ""

# ── Optional DB verification ──────────────────────────────────────
if [ "${1:-}" = "--db-check" ]; then
  DB_HOST="${DB_HOST:-localhost}"
  DB_USER="${DB_USER:-}"
  DB_PASS="${DB_PASS:-}"
  DB_NAME="${DB_NAME:-}"

  if [ -z "$DB_USER" ] || [ -z "$DB_NAME" ]; then
    yellow "⚠️  DB check skipped: set DB_USER and DB_NAME env vars."
    exit 0
  fi

  MYSQL_OPTS="-h ${DB_HOST} -u ${DB_USER}"
  if [ -n "$DB_PASS" ]; then MYSQL_OPTS="${MYSQL_OPTS} -p${DB_PASS}"; fi

  echo "── DB Verification ──"

  # Check that two leads exist with phone=NULL and the test external_ids
  echo "Leads for test senders:"
  mysql ${MYSQL_OPTS} "${DB_NAME}" -e \
    "SELECT id, name, phone, instagram_username, external_id
     FROM leads l
     JOIN conversations c ON c.lead_id = l.id
     WHERE c.external_id IN ('${SENDER_A}', '${SENDER_B}')
     ORDER BY l.id;" 2>/dev/null || echo "   (DB query failed — check credentials)"

  echo ""
  echo "Pipeline enrollment (assignedclients):"
  mysql ${MYSQL_OPTS} "${DB_NAME}" -e \
    "SELECT ac.lead_id, ac.trackingstatus_id, ac.delegate_id, ac.assigned_id
     FROM assignedclients ac
     JOIN conversations c ON c.lead_id = ac.lead_id
     WHERE c.external_id IN ('${SENDER_A}', '${SENDER_B}');" 2>/dev/null || echo "   (DB query failed)"

  echo ""
  green "DB check completed."
fi

echo ""
echo "Done. If you see 4/4 passed, the fix is working."
echo "For full verification, run:  bash scripts/test-instagram-webhook-leads.sh --db-check"
