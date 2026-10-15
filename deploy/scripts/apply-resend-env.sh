#!/usr/bin/env bash
# Configura Resend SMTP en el .env de REMS (producción).
# Uso (en el VPS, como asesoresrm):
#   RESEND_API_KEY='re_xxxx' FROM_EMAIL='cobranza@tuasesorrm.com.ve' ./apply-resend-env.sh
#
# FROM_EMAIL debe usar un dominio verificado en https://resend.com/domains

set -euo pipefail

ENV_FILE="${ENV_FILE:-/opt/docker/rems/tuasesorrm-rems/.env}"
API_KEY="${RESEND_API_KEY:-}"
FROM_EMAIL="${FROM_EMAIL:-}"
FROM_NAME="${FROM_NAME:-Asesores RM}"

if [[ -z "$API_KEY" ]]; then
  echo "Falta RESEND_API_KEY (re_...)" >&2
  exit 1
fi
if [[ -z "$FROM_EMAIL" ]]; then
  echo "Falta FROM_EMAIL (ej. cobranza@tuasesorrm.com.ve)" >&2
  exit 1
fi

if [[ ! -f "$ENV_FILE" ]]; then
  echo "No existe $ENV_FILE" >&2
  exit 1
fi

cp "$ENV_FILE" "${ENV_FILE}.bak.$(date +%Y%m%d%H%M%S)"

python3 << PY
from pathlib import Path
import re

path = Path("$ENV_FILE")
lines = path.read_text().splitlines()
keys = {
    "RESEND_API_KEY": "$API_KEY",
    "SMTP_HOST": "smtp.resend.com",
    "SMTP_PORT": "587",
    "SMTP_USER": "resend",
    "SMTP_PASS": "$API_KEY",
    "SMTP_CRYPTO": "tls",
    "SMTP_FROM_EMAIL": "$FROM_EMAIL",
    "SMTP_FROM_NAME": "$FROM_NAME",
    "FINANCE_EMAIL_ENABLED": "true",
    "FINANCE_EMAIL_RECEIPT_ON_PAYMENT": "true",
}
seen = set()
out = []
for line in lines:
    m = re.match(r"^([A-Z0-9_]+)=", line)
    if m and m.group(1) in keys:
        k = m.group(1)
        out.append(f"{k}={keys[k]}")
        seen.add(k)
    else:
        out.append(line)
for k, v in keys.items():
    if k not in seen:
        out.append(f"{k}={v}")
path.write_text("\n".join(out) + "\n")
print("Actualizado:", path)
PY

docker exec rems-app php spark cache:clear
echo "Prueba: docker exec rems-app php spark finance:test-email --to=tu-correo@ejemplo.com"
