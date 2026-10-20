"""Orquestación LangGraph: un supervisor y un subagente por módulo."""

from __future__ import annotations

import json
from datetime import date
from typing import Any

from langchain_core.messages import AIMessage, ToolMessage

from agente.finance_access import FinanceAccess, reset_finance_access, set_finance_access
from agente.graph_app import SPECIALISTS, get_graph
from agente.pending_actions import conversation_context, reset_conversation_context, set_conversation_context


def _text(content: Any) -> str:
    if isinstance(content, str):
        return content.strip()
    if isinstance(content, list):
        parts = []
        for block in content:
            if isinstance(block, str):
                parts.append(block)
            elif isinstance(block, dict):
                parts.append(str(block.get("text") or ""))
        return "\n".join(p for p in parts if p).strip()
    return str(content or "").strip()


def _debug_from_messages(messages: list[Any]) -> list[dict[str, Any]]:
    debug: list[dict[str, Any]] = []
    for msg in messages:
        name = getattr(msg, "name", None)
        if isinstance(msg, AIMessage) and msg.tool_calls:
            debug.append({
                "agent": name,
                "assistant_tool_calls": [
                    {"name": tc.get("name"), "arguments": tc.get("args")}
                    for tc in msg.tool_calls
                ],
            })
        elif isinstance(msg, ToolMessage):
            debug.append({
                "tool": msg.name,
                "result_preview": _text(msg.content)[:500],
            })
    return debug


def run_agent_turn(
    user_message: str,
    history: list[dict[str, Any]] | None = None,
    *,
    include_debug: bool = False,
    finance: FinanceAccess | None = None,
    context: dict[str, Any] | None = None,
) -> tuple[str, list[dict[str, Any]]]:
    history = history or []
    access = finance or FinanceAccess()
    token = set_finance_access(access)
    ctx_token = set_conversation_context(context)
    today = date.today().isoformat()
    messages: list[dict[str, Any]] = [
        {
            "role": "system",
            "content": (
                f"Hoy es {today}. Si dicen un día y un mes sin año, el año es el de hoy. "
                "Últimas, recientes o agregadas no significan el día de hoy: no uses since_date. "
                "Propiedad o inmueble es catálogo, aunque digan agendadas. Visitas solo si dicen visita. "
                "No uses 2023 ni 2024. "
                + access.instruction()
                + (
                    " Esta conversación permite proponer teléfono y visita a un asesor."
                    if conversation_context()
                    else " No hay conversación asociada: no propongas acciones."
                )
            ),
        },
        *[
            {"role": h["role"], "content": h.get("content") or ""}
            for h in history
            if h.get("role") in ("user", "assistant")
        ],
        {"role": "user", "content": user_message.strip()},
    ]
    try:
        result = get_graph().invoke(
            {"messages": messages},
            config={"recursion_limit": 30},
        )
    finally:
        reset_finance_access(token)
        reset_conversation_context(ctx_token)
    out = result.get("messages") or []
    debug = _debug_from_messages(out) if include_debug else []
    reply = ""
    specialist_reply = ""
    for msg in out:
        if not isinstance(msg, AIMessage) or msg.tool_calls:
            continue
        text = _text(msg.content)
        if not text or text.startswith("Transferring back"):
            continue
        if (msg.name or "") in SPECIALISTS:
            specialist_reply = text
        else:
            reply = text
    return specialist_reply or reply or "(Sin respuesta de texto del modelo)", debug
