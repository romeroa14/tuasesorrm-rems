#!/usr/bin/env python3
"""Generate SQL to import properties missing from production."""

from __future__ import annotations

import argparse
import json
import re
import sys
from pathlib import Path

PROD_COLS = [
    'id_properties', 'agent', 'area_type', 'housing_type', 'business_model',
    'bedrooms', 'bathrooms', 'garages', 'meters_construction', 'meters_land',
    'environments', 'amenities', 'exterior', 'adjacencies', 'business_conditions',
    'advertising_status', 'market_type', 'state', 'municipality', 'city',
    'address', 'map_coordinates', 'price', 'price_additional', 'owner',
    'owner_mail', 'owner_phone', 'status', 'created_at', 'updated_at',
]


def split_sql_values(raw: str) -> list[str]:
    values, current, in_string, escape = [], [], False, False
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


def split_rows(values_part: str) -> list[str]:
    rows, depth, in_str, esc, start = [], 0, False, False, 0
    for ch in values_part:
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
                start = 1 if not rows else 0  # noqa: unused simplification
            depth += 1
        elif ch == ')':
            depth -= 1
    # Re-parse with index for correctness
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


def parse_properties(sql_text: str) -> dict[int, dict[str, str]]:
    rows: dict[int, dict[str, str]] = {}
    for block in re.findall(r"INSERT INTO `properties`[^;]+;", sql_text, re.S):
        header = re.search(r"\(([^)]+)\)\s*VALUES", block)
        if not header:
            continue
        cols = [c.strip().strip('`') for c in header.group(1).split(',')]
        values_part = block.split('VALUES', 1)[1].strip().rstrip(';')
        for row in split_rows(values_part):
            vals = split_sql_values(row)
            rowd = dict(zip(cols, vals))
            rows[int(rowd['id_properties'])] = rowd
    return rows


def parse_images(sql_text: str) -> list[tuple[int, int, str, str, str]]:
    images: list[tuple[int, int, str, str, str]] = []
    for block in re.findall(r"INSERT INTO `imageorderbyproperties`[^;]+;", sql_text, re.S):
        for m in re.finditer(
            r"\((\d+),\s*(\d+),\s*('(?:\\'|[^'])*'),\s*('(?:\\'|[^'])*'),\s*('(?:\\'|[^'])*')\)",
            block,
        ):
            images.append((int(m.group(1)), int(m.group(2)), m.group(3), m.group(4), m.group(5)))
    return images


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument('--backup', required=True)
    parser.add_argument('--prod-ids-file', required=True)
    parser.add_argument('--output', required=True)
    parser.add_argument('--report', required=True)
    args = parser.parse_args()

    sql_text = Path(args.backup).read_text(errors='ignore')
    prod_ids = {
        int(x.strip())
        for x in Path(args.prod_ids_file).read_text().splitlines()
        if x.strip().isdigit()
    }
    backup_rows = parse_properties(sql_text)
    missing = sorted(set(backup_rows) - prod_ids)
    missing_set = set(missing)

    lines = ['SET FOREIGN_KEY_CHECKS=0;', 'START TRANSACTION;']
    for pid in missing:
        r = backup_rows[pid]
        vals = [r[c] for c in PROD_COLS]
        lines.append(
            'INSERT INTO properties (' + ', '.join(PROD_COLS) + ') VALUES ('
            + ', '.join(vals) + ');'
        )

    img_count = 0
    for img_id, prop_id, image, ca, ua in parse_images(sql_text):
        if prop_id not in missing_set:
            continue
        lines.append(
            f'INSERT IGNORE INTO imageorderbyproperties (id, property_id, image, created_at, updated_at) '
            f'VALUES ({img_id}, {prop_id}, {image}, {ca}, {ua});'
        )
        img_count += 1

    lines += ['COMMIT;', 'SET FOREIGN_KEY_CHECKS=1;']
    Path(args.output).write_text('\n'.join(lines) + '\n')

    report = {
        'backup_count': len(backup_rows),
        'prod_count_before': len(prod_ids),
        'missing_in_prod': missing,
        'missing_count': len(missing),
        'images_to_import': img_count,
    }
    Path(args.report).write_text(json.dumps(report, indent=2))
    print(json.dumps({'missing_count': len(missing), 'images': img_count}))
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
