"""Customer catalog agent. One react agent per request. Not the supervisor."""

from __future__ import annotations

import json
import re
from typing import Any, Callable

from langchain_core.messages import AIMessage, ToolMessage
from langchain_core.tools import tool
from langchain_openai import ChatOpenAI
from langgraph.prebuilt import create_react_agent

from agente.config import get_settings
from agente.contact_extract import phones_from_chat
from agente.db_tools import get_property_detail as _raw_detail
from agente.db_tools import search_properties as _raw_search

APPROVED = "Aprobado"
_CONTACT_MARKERS = ("phone", "email")


def _text(content: Any) -> str:
    if isinstance(content, str):
        return content.strip()
    if isinstance(content, list):
        parts: list[str] = []
        for block in content:
            if isinstance(block, str):
                parts.append(block)
            elif isinstance(block, dict):
                parts.append(str(block.get("text") or ""))
        return "\n".join(part for part in parts if part).strip()
    return str(content or "").strip()


def _strip_contacts(value: Any) -> Any:
    if isinstance(value, dict):
        cleaned: dict[str, Any] = {}
        for key, item in value.items():
            if any(marker in str(key).lower() for marker in _CONTACT_MARKERS):
                continue
            cleaned[str(key)] = _strip_contacts(item)
        return cleaned
    if isinstance(value, list):
        return [_strip_contacts(item) for item in value]
    return value


def _row_status(row: dict[str, Any]) -> str | None:
    for key in ("status", "status_name"):
        if key not in row or row[key] is None:
            continue
        text = str(row[key]).strip()
        if text != "":
            return text
    return None


def _approved(status: str | None) -> bool:
    return status is not None and status.casefold() == APPROVED.casefold()


def apply_search(args: dict[str, Any], raw_search: Callable[[dict[str, Any]], str]) -> str:
    """Force status Aprobado, drop other statuses, then strip seller contact."""
    forced = dict(args)
    forced["status"] = APPROVED
    try:
        payload = json.loads(raw_search(forced))
    except (TypeError, ValueError, json.JSONDecodeError):
        return "[]"
    rows = payload if isinstance(payload, list) else []
    kept = []
    for row in rows:
        if not isinstance(row, dict):
            continue
        status = _row_status(row)
        if status is not None and not _approved(status):
            continue
        kept.append(_strip_contacts(row))
    return json.dumps(kept, ensure_ascii=False)


def apply_detail(id_properties: int, raw_detail: Callable[[int], str]) -> str:
    """Reject a missing or non-approved detail and strip seller contact."""
    rejected = {"error": "not_found_or_not_approved", "id_properties": id_properties}
    try:
        payload = json.loads(raw_detail(id_properties))
    except (TypeError, ValueError, json.JSONDecodeError):
        return json.dumps(rejected, ensure_ascii=False)
    if not isinstance(payload, dict) or payload.get("error") or not _approved(_row_status(payload)):
        return json.dumps(rejected, ensure_ascii=False)
    return json.dumps(_strip_contacts(payload), ensure_ascii=False)


def _search_args(**kwargs: object) -> dict[str, Any]:
    return {key: value for key, value in kwargs.items() if value is not None}


@tool
def search_properties(
    location_keyword: str | None = None,
    min_price: float | None = None,
    max_price: float | None = None,
    min_bedrooms: int | None = None,
    seller_name: str | None = None,
    status: str | None = None,
    since_date: str | None = None,
    until_date: str | None = None,
    limit: int = 5,
) -> str:
    """Busca inmuebles aprobados. El estatus que pida el modelo se ignora."""
    return apply_search(
        _search_args(
            location_keyword=location_keyword,
            min_price=min_price,
            max_price=max_price,
            min_bedrooms=min_bedrooms,
            seller_name=seller_name,
            status=status,
            since_date=since_date,
            until_date=until_date,
            limit=limit,
        ),
        _raw_search,
    )


@tool
def get_property_detail(id_properties: int) -> str:
    """Ficha de un inmueble aprobado. Sin teléfono ni correo del vendedor."""
    return apply_detail(id_properties, _raw_detail)


def _catalog_model() -> ChatOpenAI:
    settings = get_settings()
    return ChatOpenAI(
        model=settings.openrouter_model,
        api_key=settings.openrouter_api_key,
        base_url=settings.openrouter_base_url,
        temperature=0.2,
        timeout=90,
        default_headers={
            "HTTP-Referer": settings.public_base_url,
            "X-Title": "Agente catalogo ATC",
        },
    )


def build_catalog_agent(soul: str):
    """A new agent for this soul. Nothing is cached."""
    return create_react_agent(
        model=_catalog_model(),
        tools=[search_properties, get_property_detail],
        prompt=soul,
    )


def _debug_entries(messages: list[Any]) -> list[dict[str, Any]]:
    entries: list[dict[str, Any]] = []
    for message in messages:
        if isinstance(message, AIMessage) and message.tool_calls:
            for call in message.tool_calls:
                entries.append({
                    "tool": call.get("name"),
                    "arguments": call.get("args") or {},
                    "result_preview": "",
                })
        elif isinstance(message, ToolMessage):
            preview = _text(message.content)[:500]
            attached = False
            for entry in reversed(entries):
                if entry["tool"] == message.name and entry["result_preview"] == "":
                    entry["result_preview"] = preview
                    attached = True
                    break
            if not attached:
                entries.append({
                    "tool": message.name,
                    "arguments": {},
                    "result_preview": preview,
                })
    return entries




_MD_IMAGE = re.compile(
    r"\[?!\[([^\]]*)\]\((https?://[^)\s]+)\)(?:\](?:\([^)]*\))?)?"
)
_MD_LINK = re.compile(r"\[([^\]]+)\]\((https?://[^)\s]+)\)")
_MD_BOLD = re.compile(r"\*\*([^*]+)\*\*|__([^_]+)__")
_MD_ITALIC = re.compile(r"(?<!\*)\*([^*\n]+)\*(?!\*)")
_MD_BULLET = re.compile(r"(?m)^[ \t]*[-*•][ \t]+")
_MD_HEADING = re.compile(r"(?m)^#{1,6}[ \t]+")


def plain_customer_reply(text: str) -> str:
    """Instagram reads plain text. Drop markdown and leave each photo URL on its own line."""
    if not text:
        return ""

    def image(match: re.Match) -> str:
        return "\n" + match.group(2) + "\n"

    def link(match: re.Match) -> str:
        label, url = match.group(1).strip(), match.group(2)
        if label == "" or label == url or label.startswith("http"):
            return url
        return label + "\n" + url

    cleaned = _MD_IMAGE.sub(image, text)
    cleaned = _MD_LINK.sub(link, cleaned)
    cleaned = _MD_BOLD.sub(lambda match: match.group(1) or match.group(2) or "", cleaned)
    cleaned = _MD_ITALIC.sub(r"\1", cleaned)
    cleaned = _MD_HEADING.sub("", cleaned)
    cleaned = _MD_BULLET.sub("", cleaned)
    cleaned = cleaned.replace("**", "").replace("__", "").replace("`", "")
    lines = [re.sub(r"[ \t]+", " ", line).strip() for line in cleaned.splitlines()]
    kept: list[str] = []
    blank = False
    for line in lines:
        if line == "":
            if kept and not blank:
                kept.append("")
            blank = True
            continue
        blank = False
        kept.append(line)
    return "\n".join(kept).strip()


def _reply_text(messages: list[Any]) -> str:
    reply = ""
    for message in messages:
        if not isinstance(message, AIMessage) or message.tool_calls:
            continue
        text = _text(message.content)
        if text:
            reply = text
    return reply


def run_catalog_chat(
    message: str,
    history: list[dict[str, Any]] | None,
    soul: str,
    *,
    include_debug: bool = False,
) -> dict[str, Any]:
    agent = build_catalog_agent(soul)
    messages: list[dict[str, str]] = []
    for item in history or []:
        role = item.get("role") if isinstance(item, dict) else None
        if role not in ("user", "assistant"):
            continue
        messages.append({"role": role, "content": str(item.get("content") or "")})
    messages.append({"role": "user", "content": message.strip()})
    result = agent.invoke({"messages": messages}, config={"recursion_limit": 12})
    out = result.get("messages") or []
    user_history = [item for item in messages if item["role"] == "user"][:-1]
    return {
        "reply": plain_customer_reply(_reply_text(out)),
        "debug": _debug_entries(out) if include_debug else None,
        "extracted_phones": phones_from_chat(message, user_history),
    }
