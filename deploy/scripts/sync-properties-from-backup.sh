#!/usr/bin/env bash
# Sync missing properties from hosting backup into production REMS database.
set -euo pipefail

BACKUP_SQL="${1:-sql/tuaseso_db08062.sql}"
MYSQL="docker exec -i rems-mysql mysql -urems -p${REMS_MYSQL_PASSWORD:-Rems20\$}"
PROD_DB="${REMS_PROD_DB:-a0051406_rems}"
REPORT_DIR="${REPORT_DIR:-/tmp/rems-sync}"

mkdir -p "$REPORT_DIR"
TS="$(date +%F-%H%M%S)"

echo "=== REMS property sync $TS ==="
echo "Backup: $BACKUP_SQL"
echo "Production DB: $PROD_DB"

if [[ ! -f "$BACKUP_SQL" ]]; then
  echo "ERROR: backup file not found: $BACKUP_SQL" >&2
  exit 1
fi

echo "=== 1) Backup production properties ==="
BEFORE_COUNT=$($MYSQL "$PROD_DB" -N -e "SELECT COUNT(*) FROM properties")
echo "Current count: $BEFORE_COUNT"
docker exec rems-mysql mysqldump -urems -p"${REMS_MYSQL_PASSWORD:-Rems20\$}" "$PROD_DB" \
  properties imageorderbyproperties > "$REPORT_DIR/pre-sync-properties-$TS.sql" 2>/dev/null || true
ls -lh "$REPORT_DIR/pre-sync-properties-$TS.sql"

echo "=== 2) Compare IDs ==="
$MYSQL "$PROD_DB" -N -e "SELECT id_properties FROM properties ORDER BY 1" > "$REPORT_DIR/prod-ids.txt"

python3 - "$BACKUP_SQL" "$REPORT_DIR/prod-ids.txt" "$REPORT_DIR/sync-report-$TS.json" <<'PY'
import json, re, sys
from pathlib import Path

backup_path, prod_ids_path, report_path = sys.argv[1:4]
text = Path(backup_path).read_text(errors='ignore')
prod_ids = {int(x.strip()) for x in Path(prod_ids_path).read_text().splitlines() if x.strip().isdigit()}

def split_rows(values_part):
    rows, depth, in_str, esc, start = [], 0, False, False, 0
    for i, ch in enumerate(values_part):
        if esc:
            esc = False
            continue
        if ch == '\\':
            esc = True
            continue
        if ch == "'":
            in_str = not in_str
            continue
        if in_str:
            continue
        if ch == '(':
            if depth == 0:
                start = i + 1
            depth += 1
        elif ch == ')':
            depth -= 1
            if depth == 0:
                rows.append(values_part[start:i])
    return rows

backup_ids = set()
for block in re.findall(r"INSERT INTO `properties`[^;]+;", text, re.S):
    values_part = block.split('VALUES', 1)[1].strip().rstrip(';')
    for row in split_rows(values_part):
        backup_ids.add(int(row.split(',')[0].strip()))

missing = sorted(backup_ids - prod_ids)
report = {
    'backup_count': len(backup_ids),
    'prod_count_before': len(prod_ids),
    'missing_in_prod': missing,
    'missing_count': len(missing),
    'only_in_prod_count': len(prod_ids - backup_ids),
}
Path(report_path).write_text(json.dumps(report, indent=2))
print(json.dumps({'missing_count': len(missing), 'ids': missing}, indent=2))
PY

MISSING_COUNT=$(python3 -c "import json; print(json.load(open('$REPORT_DIR/sync-report-$TS.json'))['missing_count'])")
if [[ "$MISSING_COUNT" == "0" ]]; then
  echo "Nothing to import."
  exit 0
fi

echo "=== 3) Generate import SQL (no staging DB required) ==="
GEN_SCRIPT="$(dirname "$0")/generate-sync-import-sql.py"
if [[ ! -f "$GEN_SCRIPT" ]]; then
  echo "ERROR: missing $GEN_SCRIPT — run from repo with deploy/scripts/" >&2
  exit 1
fi
python3 "$GEN_SCRIPT" \
  --backup "$BACKUP_SQL" \
  --prod-ids-file "$REPORT_DIR/prod-ids.txt" \
  --output "$REPORT_DIR/sync-import-$TS.sql" \
  --report "$REPORT_DIR/sync-report-$TS.json"

echo "=== 4) Apply import SQL ==="
$MYSQL "$PROD_DB" < "$REPORT_DIR/sync-import-$TS.sql"

echo "=== 5) Verify ==="
AFTER_COUNT=$($MYSQL "$PROD_DB" -N -e "SELECT COUNT(*) FROM properties")
IMPORTED=$((AFTER_COUNT - BEFORE_COUNT))
echo "Before: $BEFORE_COUNT | After: $AFTER_COUNT | Imported: $IMPORTED"
$MYSQL "$PROD_DB" -e "
SELECT COUNT(*) AS in_new_range FROM properties WHERE id_properties BETWEEN 1775 AND 1887;
SELECT id_properties, address, price, created_at FROM properties ORDER BY id_properties DESC LIMIT 5;
"

echo "Done. Report: $REPORT_DIR/sync-report-$TS.json"
