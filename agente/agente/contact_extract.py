"""Extracción determinística de teléfonos en texto libre (DM / chat web)."""

from __future__ import annotations

import re
from typing import Final

# Bloques con separadores habituales (+58, 0414-..., espacios).
_DIGIT_RUN: Final[re.Pattern[str]] = re.compile(
    r"(?:\+?\s*58\s*)?(?:\(?\s*0?\s*4\s*\d\s*\d\s*\)?[\s\-\.]*)?\d(?:[\s\-\.\/]*\d){8,13}"
)
_STRICT_VE: Final[re.Pattern[str]] = re.compile(
    r"(?:\+58|0058|58)[\s\-]?(?:4\d{2})[\s\-]?\d{7}|"
    r"(?:^|[^\d])0?4\d{2}[\s\-]?\d{3}[\s\-]?\d{4}(?:[^\d]|$)"
)


def _digits_only(s: str) -> str:
    return re.sub(r"\D", "", s)


def _normalize_ve_candidate(digits: str) -> str | None:
    """Devuelve dígitos normalizados (prefijo país 58 sin +) o None si no es plausible."""
    if not digits:
        return None
    d = digits
    if d.startswith("58") and len(d) >= 12:
        d = d[2:]
    if d.startswith("0") and len(d) >= 11:
        d = d[1:]
    # Móvil VE típico: 4 + área 2 dígitos + 7 dígitos → 10 dígitos tras quitar 0 inicial
    if len(d) == 10 and d[0] == "4":
        return "58" + d
    if len(d) == 11 and d.startswith("58"):
        return d
    if len(d) == 12 and d.startswith("584"):
        return d
    # Fijo u otros (02xx...) — conservar si longitud razonable
    if 10 <= len(d) <= 12:
        return d if d.startswith("58") else ("58" + d if len(d) == 10 and d[0] == "4" else d)
    return None


def extract_phone_candidates(*text_parts: str) -> list[str]:
    """
    Busca teléfonos en uno o varios fragmentos de texto (p. ej. último mensaje + historial usuario).

    Devuelve lista deduplicada de candidatos normalizados principalmente para VE (+58 móvil).
    Si detecta bloques internacionales genéricos de 10–15 dígitos también los incluye.
    """
    blob = "\n".join(t for t in text_parts if t)
    if not blob.strip():
        return []

    seen: set[str] = set()
    out: list[str] = []

    def add(raw_display: str, canonical: str | None) -> None:
        if canonical and canonical not in seen:
            seen.add(canonical)
            out.append(canonical)

    for m in _STRICT_VE.finditer(blob):
        frag = m.group(0)
        d = _digits_only(frag)
        norm = _normalize_ve_candidate(d)
        add(frag, norm or (d if 10 <= len(d) <= 12 else None))

    for m in _DIGIT_RUN.finditer(blob):
        frag = m.group(0)
        d = _digits_only(frag)
        if len(d) < 10:
            continue
        norm = _normalize_ve_candidate(d)
        if norm:
            add(frag, norm)
        elif 10 <= len(d) <= 15:
            add(frag, d)

    return out


def phones_from_chat(current_user_message: str, history: list[dict] | None) -> list[str]:
    """Revisa el mensaje actual y todos los turnos previos del usuario en el historial."""
    parts: list[str] = [current_user_message]
    if history:
        for h in history:
            if not isinstance(h, dict):
                continue
            if h.get("role") != "user":
                continue
            c = h.get("content")
            if isinstance(c, str) and c.strip():
                parts.append(c)
    return extract_phone_candidates(*parts)
