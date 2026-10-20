"""Write tools that only PROPOSE actions. An advisor approves them in the CRM before anything runs."""

from __future__ import annotations

import json
import re
from contextvars import ContextVar, Token
from datetime import datetime
from typing import Any

from agente.db_tools import _conn


_CONVERSATION: ContextVar[dict[str, Any] | None] = ContextVar("conversation_context", default=None)


def set_conversation_context(ctx: dict[str, Any] | None) -> Token:
    return _CONVERSATION.set(ctx)


def reset_conversation_context(token: Token) -> None:
    _CONVERSATION.reset(token)


def conversation_context() -> dict[str, Any] | None:
    ctx = _CONVERSATION.get()
    return ctx if ctx and ctx.get("conversation_id") and ctx.get("lead_id") else None


def _insert(row: dict[str, Any]) -> int:
    conn = _conn()
    try:
        with conn.cursor() as cur:
            cur.execute(
                "INSERT INTO ai_pending_actions (conversation_id, lead_id, action, payload, status, created_at) "
                "VALUES (%s, %s, %s, %s, 'pending', NOW())",
                (row["conversation_id"], row["lead_id"], row["action"], json.dumps(row["payload"], ensure_ascii=False)),
            )
            conn.commit()
            return int(cur.lastrowid)
    finally:
        conn.close()


def _result(status: str, **extra: Any) -> str:
    return json.dumps({"status": status, **extra}, ensure_ascii=False)


def _parse_dt(raw: str) -> datetime | None:
    for fmt in ("%Y-%m-%d %H:%M", "%Y-%m-%d %H:%M:%S", "%Y-%m-%dT%H:%M", "%Y-%m-%dT%H:%M:%S"):
        try:
            return datetime.strptime(raw.strip(), fmt)
        except ValueError:
            continue
    return None


def propose_register_phone(args: dict[str, Any], ctx: dict[str, Any]) -> str:
    phone = str(args.get("phone") or "").strip()
    if len(re.sub(r"\D", "", phone)) < 10:
        return _result("invalid", message="El teléfono debe tener al menos 10 dígitos.")

    _insert({"conversation_id": ctx["conversation_id"], "lead_id": ctx["lead_id"], "action": "registrar_telefono", "payload": {"phone": phone}})
    return _result("proposed", message="Propuesta enviada al asesor para su aprobación. No está registrado aún.")


def propose_create_visit(args: dict[str, Any], ctx: dict[str, Any]) -> str:
    when = _parse_dt(str(args.get("date_at") or ""))
    if when is None:
        return _result("invalid", message="Usa el formato YYYY-MM-DD HH:MM.")
    if when <= datetime.now():
        return _result("invalid", message="La fecha de la visita debe ser futura.")

    payload = {"date_at": when.strftime("%Y-%m-%d %H:%M")}
    for key in ("property_label", "notes"):
        value = str(args.get(key) or "").strip()
        if value:
            payload[key] = value

    _insert({"conversation_id": ctx["conversation_id"], "lead_id": ctx["lead_id"], "action": "crear_visita", "payload": payload})
    return _result("proposed", message="Propuesta enviada al asesor para su aprobación. La visita aún no está agendada.")


WRITE_TOOLS: dict[str, Any] = {
    "proponer_registrar_telefono": propose_register_phone,
    "proponer_crear_visita": propose_create_visit,
}

WRITE_TOOL_SCHEMAS: list[dict[str, Any]] = [
    {
        "type": "function",
        "function": {
            "name": "proponer_registrar_telefono",
            "description": (
                "Propone guardar el teléfono que el cliente dio. NO lo guarda: un asesor debe aprobarlo. "
                "Úsalo solo cuando el cliente escribió su número."
            ),
            "parameters": {
                "type": "object",
                "properties": {"phone": {"type": "string", "description": "Teléfono tal como lo escribió el cliente"}},
                "required": ["phone"],
            },
        },
    },
    {
        "type": "function",
        "function": {
            "name": "proponer_crear_visita",
            "description": (
                "Propone agendar una visita acordada con el cliente. NO la agenda: un asesor debe aprobarla. "
                "Úsalo solo si el cliente confirmó día y hora."
            ),
            "parameters": {
                "type": "object",
                "properties": {
                    "date_at": {"type": "string", "description": "Fecha y hora futura, formato YYYY-MM-DD HH:MM"},
                    "property_label": {"type": "string", "description": "Inmueble de interés (opcional)"},
                    "notes": {"type": "string", "description": "Notas para el asesor (opcional)"},
                },
                "required": ["date_at"],
            },
        },
    },
]
