"""Lectura de finanzas. Cada consulta exige el permiso del turno."""

from __future__ import annotations

import json
from datetime import date, datetime
from typing import Any

from langchain_core.tools import tool

from agente.db_tools import _conn, _norm
from agente.finance_access import get_finance_access


def _denied(message: str) -> str:
    return json.dumps({"error": "sin_acceso", "message": message}, ensure_ascii=False)


def _date_bound(value: Any, *, end: bool = False) -> str | None:
    raw = str(value or "").strip()
    if not raw:
        return None
    parsed = None
    for fmt in ("%Y-%m-%d", "%d-%m-%Y", "%d/%m/%Y"):
        try:
            parsed = datetime.strptime(raw[:10], fmt)
            break
        except ValueError:
            continue
    if parsed is None:
        return None
    clock = "23:59:59" if end else "00:00:00"
    return parsed.strftime("%Y-%m-%d") + " " + clock


def _limit(value: Any, default: int = 8) -> int:
    try:
        number = int(value)
    except (TypeError, ValueError):
        number = default
    return max(1, min(20, number))


def finance_summary(args: dict[str, Any] | None = None) -> str:
    access = get_finance_access()
    if not access.can_summarize():
        return _denied("Este usuario no puede ver el resumen de finanzas.")
    args = args or {}
    start = _date_bound(args.get("since_date")) or date.today().replace(day=1).isoformat() + " 00:00:00"
    end = _date_bound(args.get("until_date"), end=True) or date.today().isoformat() + " 23:59:59"
    sql = """
        SELECT c.type, COALESCE(SUM(ml.amount), 0) AS total, COUNT(*) AS movements
        FROM finance_movement_lines ml
        JOIN finance_movements m ON m.id = ml.movement_id
        JOIN finance_categories c ON c.id = ml.category_id
        WHERE m.status = 'posted'
          AND ml.line_number = 1
          AND m.occurred_on >= %s
          AND m.occurred_on <= %s
        GROUP BY c.type
    """
    with _conn() as conn:
        with conn.cursor() as cur:
            cur.execute(sql, (start, end))
            rows = {_norm(row["type"]): row for row in cur.fetchall()}
    income = rows.get("income") or {}
    expense = rows.get("expense") or {}
    income_total = float(_norm(income.get("total") or 0))
    expense_total = float(_norm(expense.get("total") or 0))
    return json.dumps({
        "since": start[:10],
        "until": end[:10],
        "income_total": income_total,
        "income_movements": int(income.get("movements") or 0),
        "expense_total": expense_total,
        "expense_movements": int(expense.get("movements") or 0),
        "net": round(income_total - expense_total, 2),
    }, ensure_ascii=False)


def _list_movements(kind: str, args: dict[str, Any] | None) -> str:
    access = get_finance_access()
    allowed = access.income if kind == "income" else access.expense
    if not allowed:
        label = "ingresos" if kind == "income" else "egresos"
        return _denied(f"Este usuario no puede ver {label}.")
    args = args or {}
    params: list[Any] = [kind]
    sql = """
        SELECT m.occurred_on, m.status, c.name AS category, c.movement_type, ml.amount
        FROM finance_movement_lines ml
        JOIN finance_movements m ON m.id = ml.movement_id
        JOIN finance_categories c ON c.id = ml.category_id
        WHERE ml.line_number = 1
          AND c.type = %s
    """
    since = _date_bound(args.get("since_date"))
    until = _date_bound(args.get("until_date"), end=True)
    if since:
        sql += " AND m.occurred_on >= %s"
        params.append(since)
    if until:
        sql += " AND m.occurred_on <= %s"
        params.append(until)
    sql += " ORDER BY m.occurred_on DESC, m.id DESC LIMIT %s"
    params.append(_limit(args.get("limit")))
    with _conn() as conn:
        with conn.cursor() as cur:
            cur.execute(sql, params)
            rows = []
            for row in cur.fetchall():
                item = {key: _norm(value) for key, value in row.items()}
                item["amount"] = float(item["amount"] or 0)
                rows.append(item)
    return json.dumps({kind: rows}, ensure_ascii=False)


def list_finance_income(args: dict[str, Any] | None = None) -> str:
    return _list_movements("income", args)


def list_finance_expenses(args: dict[str, Any] | None = None) -> str:
    return _list_movements("expense", args)


@tool("finance_summary")
def finance_summary_tool(since_date: str | None = None, until_date: str | None = None) -> str:
    """Resumen de ingresos y egresos publicados. Solo si el usuario puede ver el dashboard o los reportes."""
    payload = {}
    if since_date:
        payload["since_date"] = since_date
    if until_date:
        payload["until_date"] = until_date
    return finance_summary(payload)


@tool("list_finance_income")
def list_finance_income_tool(
    since_date: str | None = None,
    until_date: str | None = None,
    limit: int = 8,
) -> str:
    """Ingresos publicados: fecha, categoría y monto. Sin teléfonos."""
    return list_finance_income({
        "since_date": since_date,
        "until_date": until_date,
        "limit": limit,
    })


@tool("list_finance_expenses")
def list_finance_expenses_tool(
    since_date: str | None = None,
    until_date: str | None = None,
    limit: int = 8,
) -> str:
    """Egresos publicados: fecha, categoría y monto."""
    return list_finance_expenses({
        "since_date": since_date,
        "until_date": until_date,
        "limit": limit,
    })
