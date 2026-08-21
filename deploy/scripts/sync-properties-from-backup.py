#!/usr/bin/env python3
"""
Compare properties in a hosting SQL backup vs production and import missing rows.

Usage:
  python3 deploy/scripts/sync-properties-from-backup.py --backup sql/tuaseso_db08062.sql --dry-run
  python3 deploy/scripts/sync-properties-from-backup.py --backup sql/tuaseso_db08062.sql --apply --mysql-cmd "docker exec -i rems-mysql mysql -urems -p'PASS' a0051406_rems"
"""

from __future__ import annotations

import argparse
import re
import subprocess
import sys
from pathlib import Path

PROD_COLUMNS = [
    'id_properties', 'agent', 'area_type', 'housing_type', 'business_model',
    'bedrooms', 'bathrooms', 'garages', 'meters_construction', 'meters_land',
    'environments', 'amenities', 'exterior', 'adjacencies', 'business_conditions',
    'advertising_status', 'market_type', 'state', 'municipality', 'city',
    'address', 'map_coordinates', 'price', 'price_additional', 'owner',
    'owner_mail', 'owner_phone', 'status', 'created_at', 'updated_at',
]

BACKUP_COLUMNS = PROD_COLUMNS + ['note']


def parse_insert_blocks(sql_text: str, table: str) -> list[str]:
    pattern = rf"INSERT INTO `{table}`[^;]+;"
    return re.findall(pattern, sql_text, re.S)


def parse_property_rows(sql_text: str) -> dict[int, list[str]]:
    rows: dict[int, list[str]] = {}
    for block in parse_insert_blocks(sql_text, 'properties'):
        header = re.search(r"INSERT INTO `properties` \(([^)]+)\)", block)
        if not header:
            continue
        cols = [c.strip().strip('`') for c in header.group(1).split(',')]
        values_part = block.split('VALUES', 1)[1].strip().rstrip(';').strip()
        for tuple_match in re.finditer(r"\((.*)\)(?:,|$)", values_part, re.S):
            raw = tuple_match.group(1)
            values = split_sql_values(raw)
            if len(values) != len(cols):
                continue
            row = dict(zip(cols, values))
            pid = int(row['id_properties'].strip("'"))
            rows[pid] = values
    return rows, cols if rows else BACKUP_COLUMNS


def split_sql_values(raw: str) -> list[str]:
    values: list[str] = []
    current: list[str] = []
    in_string = False
    escape = False
    for ch in raw:
        if escape:
            current.append(ch)
            escape = False
            continue
        if ch == '\\':
            current.append(ch)
            escape = True
            continue
        if ch == "'":
            in_string = not in_string
            current.append(ch)
            continue
        if ch == ',' and not in_string:
            values.append(''.join(current).strip())
            current = []
            continue
        current.append(ch)
    if current:
        values.append(''.join(current).strip())
    return values


def parse_image_rows(sql_text: str) -> list[tuple[int, int, str, str, str]]:
    images: list[tuple[int, int, str, str, str]] = []
    for block in parse_insert_blocks(sql_text, 'imageorderbyproperties'):
        for m in re.finditer(
            r"\((\d+),\s*(\d+),\s*('(?:\\'|[^'])*'),\s*('(?:\\'|[^'])*'),\s*('(?:\\'|[^'])*')\)",
            block,
        ):
            images.append((int(m.group(1)), int(m.group(2)), m.group(3), m.group(4), m.group(5)))
    return images


def fetch_prod_ids(mysql_cmd: str) -> set[int]:
    cmd = f"{mysql_cmd} -N -e \"SELECT id_properties FROM properties\""
    out = subprocess.check_output(cmd, shell=True, text=True)
    return {int(line.strip()) for line in out.splitlines() if line.strip().isdigit()}


def fetch_prod_image_ids(mysql_cmd: str) -> set[int]:
    cmd = f"{mysql_cmd} -N -e \"SELECT id FROM imageorderbyproperties\""
    out = subprocess.check_output(cmd, shell=True, text=True)
    return {int(line.strip()) for line in out.splitlines() if line.strip().isdigit()}


def fetch_prod_agents(mysql_cmd: str) -> set[int]:
    cmd = f"{mysql_cmd} -N -e \"SELECT id FROM users\""
    out = subprocess.check_output(cmd, shell=True, text=True)
    return {int(line.strip()) for line in out.splitlines() if line.strip().isdigit()}


def build_property_insert(cols: list[str], values: list[str]) -> str:
    idx_map = {c: i for i, c in enumerate(cols)}
    prod_values = [values[idx_map[c]] for c in PROD_COLUMNS]
    return (
        'INSERT INTO properties (' + ', '.join(PROD_COLUMNS) + ') VALUES ('
        + ', '.join(prod_values) + ');'
    )


def main() -> int:
    parser = argparse.ArgumentParser(description='Sync missing properties from hosting backup')
    parser.add_argument('--backup', required=True, help='Path to tuaseso_db08062.sql')
    parser.add_argument('--mysql-cmd', default='', help='mysql client prefix, e.g. docker exec -i rems-mysql mysql -urems -pPASS a0051406_rems')
    parser.add_argument('--prod-ids-file', help='Optional file with one id_properties per line (skip mysql query)')
    parser.add_argument('--dry-run', action='store_true', default=True)
    parser.add_argument('--apply', action='store_true', help='Execute SQL against production')
    parser.add_argument('--report', default='sql/sync-report.json')
    args = parser.parse_args()

    backup_path = Path(args.backup)
    if not backup_path.is_file():
        print(f'Backup not found: {backup_path}', file=sys.stderr)
        return 1

    sql_text = backup_path.read_text(errors='ignore')
    backup_rows, cols = parse_property_rows(sql_text)
    if not backup_rows:
        print('No properties found in backup', file=sys.stderr)
        return 1

    backup_ids = set(backup_rows.keys())
    images = parse_image_rows(sql_text)

    if args.prod_ids_file:
        prod_ids = {int(x.strip()) for x in Path(args.prod_ids_file).read_text().splitlines() if x.strip().isdigit()}
    elif args.mysql_cmd:
        prod_ids = fetch_prod_ids(args.mysql_cmd)
    else:
        print('Provide --mysql-cmd or --prod-ids-file', file=sys.stderr)
        return 1

    missing_ids = sorted(backup_ids - prod_ids)
    only_prod = sorted(prod_ids - backup_ids)

    print(f'Backup properties: {len(backup_ids)}')
    print(f'Production properties: {len(prod_ids)}')
    print(f'Missing in production (to import): {len(missing_ids)}')
    print(f'Only in production (kept): {len(only_prod)}')

    if not missing_ids:
        print('Nothing to import.')
        return 0

    agents_ok: set[int] = set()
    if args.mysql_cmd:
        agents_ok = fetch_prod_agents(args.mysql_cmd)

    property_sql: list[str] = []
    skipped_fk: list[int] = []
    for pid in missing_ids:
        values = backup_rows[pid]
        idx_map = {c: i for i, c in enumerate(cols)}
        agent = int(values[idx_map['agent']].strip("'"))
        if agents_ok and agent not in agents_ok:
            skipped_fk.append(pid)
            continue
        property_sql.append(build_property_insert(cols, values))

    missing_set = set(missing_ids) - set(skipped_fk)
    image_sql: list[str] = []
    prod_image_ids: set[int] = set()
    if args.mysql_cmd:
        prod_image_ids = fetch_prod_image_ids(args.mysql_cmd)

    for img_id, prop_id, image, created_at, updated_at in images:
        if prop_id not in missing_set:
            continue
        if img_id in prod_image_ids:
            continue
        image_sql.append(
            f"INSERT INTO imageorderbyproperties (id, property_id, image, created_at, updated_at) "
            f"VALUES ({img_id}, {prop_id}, {image}, {created_at}, {updated_at});"
        )

    report_path = Path(args.report)
    report_path.parent.mkdir(parents=True, exist_ok=True)
    import json
    report_path.write_text(json.dumps({
        'backup_count': len(backup_ids),
        'prod_count': len(prod_ids),
        'to_import': sorted(missing_set),
        'skipped_fk_agent': skipped_fk,
        'images_to_import': len(image_sql),
        'only_in_prod_sample': only_prod[:20],
    }, indent=2))
    print(f'Report: {report_path}')

    all_sql = [
        'SET FOREIGN_KEY_CHECKS=0;',
        'START TRANSACTION;',
        *property_sql,
        *image_sql,
        'COMMIT;',
        'SET FOREIGN_KEY_CHECKS=1;',
    ]
    sql_out = Path('sql/sync-import-properties.sql')
    sql_out.write_text('\n'.join(all_sql) + '\n')
    print(f'SQL file: {sql_out} ({len(property_sql)} properties, {len(image_sql)} images)')

    if skipped_fk:
        print(f'WARNING: skipped {len(skipped_fk)} properties due to missing agent user: {skipped_fk[:10]}...')

    if args.apply:
        if not args.mysql_cmd:
            print('--apply requires --mysql-cmd', file=sys.stderr)
            return 1
        proc = subprocess.run(
            f"{args.mysql_cmd}",
            input='\n'.join(all_sql),
            shell=True,
            text=True,
            capture_output=True,
        )
        if proc.returncode != 0:
            print(proc.stderr or proc.stdout, file=sys.stderr)
            return proc.returncode
        new_count = fetch_prod_ids(args.mysql_cmd)
        print(f'Production properties after import: {len(new_count)}')
    else:
        print('Dry run only. Use --apply to execute.')

    return 0


if __name__ == '__main__':
    raise SystemExit(main())
