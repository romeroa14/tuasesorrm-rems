"""Bucle agente → DeepSeek con tools (HTTP OpenAI-compatible)."""

from __future__ import annotations

import json
from typing import Any

import httpx

from agente.config import get_settings
from agente.db_tools import OPENAI_TOOLS, execute_tool
from agente.prompts import SYSTEM_PROMPT


def _chat_completions(payload: dict[str, Any]) -> dict[str, Any]:
    s = get_settings()
    if not s.deepseek_api_key.strip():
        raise RuntimeError("Define DEEPSEEK_API_KEY en agente/.env")

    url = s.deepseek_base_url.rstrip("/") + "/chat/completions"
    headers = {
        "Authorization": f"Bearer {s.deepseek_api_key.strip()}",
        "Content-Type": "application/json",
    }

    with httpx.Client(timeout=120.0) as client:
        r = client.post(url, headers=headers, json=payload)
        body = r.json() if r.content else {}

    if r.status_code != 200:
        raise RuntimeError(
            f"DeepSeek HTTP {r.status_code}: {json.dumps(body, ensure_ascii=False)[:800]}"
        )

    return body


def run_agent_turn(
    user_message: str,
    history: list[dict[str, Any]] | None = None,
    *,
    include_debug: bool = False,
) -> tuple[str, list[dict[str, Any]]]:
    """
    Ejecuta un turno completo (posibles múltiples tool_calls).
    history: mensajes previos con roles user|assistant (sin system).
    """
    s = get_settings()
    history = history or []

    messages: list[dict[str, Any]] = [
        {"role": "system", "content": SYSTEM_PROMPT},
        *[h for h in history if h.get("role") in ("user", "assistant") and h.get("content") is not None],
        {"role": "user", "content": user_message.strip()},
    ]

    debug_log: list[dict[str, Any]] = []
    last_text = ""

    for _ in range(10):
        resp = _chat_completions(
            {
                "model": s.deepseek_model,
                "messages": messages,
                "tools": OPENAI_TOOLS,
                "tool_choice": "auto",
            }
        )

        choice = (resp.get("choices") or [{}])[0]
        msg = choice.get("message") or {}

        tool_calls = msg.get("tool_calls")
        if tool_calls:
            messages.append(msg)
            if include_debug:
                debug_log.append({"assistant_tool_calls": tool_calls})

            for tc in tool_calls:
                fn = (tc.get("function") or {})
                name = fn.get("name") or ""
                args = fn.get("arguments") or "{}"
                tid = tc.get("id") or ""
                result = execute_tool(name, args if isinstance(args, str) else json.dumps(args))
                if include_debug:
                    debug_log.append({"tool": name, "arguments": args, "result_preview": result[:500]})
                messages.append({"role": "tool", "tool_call_id": tid, "content": result})

            continue

        last_text = (msg.get("content") or "").strip()
        break

    return last_text or "(Sin respuesta de texto del modelo)", debug_log
