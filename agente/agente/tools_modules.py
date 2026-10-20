"""Herramientas del agente, agrupadas por módulo. La SQL sigue en db_tools."""

from __future__ import annotations

import json

from langchain_core.tools import tool

from agente.pending_actions import WRITE_TOOLS, conversation_context
from agente.db_tools import (
    get_property_detail as _detail,
    get_seller as _seller,
    list_assignments as _assignments,
    list_leads as _leads,
    list_publications as _publications,
    list_searches as _searches,
    list_sellers as _sellers,
    list_visits as _visits,
    list_wasi as _wasi,
    operational_summary as _summary,
    search_properties as _search,
)


def _args(**kwargs: object) -> dict:
    return {k: v for k, v in kwargs.items() if v is not None}


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
    """Busca inmuebles, las más nuevas primero. Para últimas o recientes pasa solo limit. since_date solo si el usuario nombra un día de inicio."""
    return _search(_args(
        location_keyword=location_keyword,
        min_price=min_price,
        max_price=max_price,
        min_bedrooms=min_bedrooms,
        seller_name=seller_name,
        status=status,
        since_date=since_date,
        until_date=until_date,
        limit=limit,
    ))


@tool
def get_property_detail(id_properties: int) -> str:
    """Ficha completa: habitaciones, baños, puestos, metros, ambientes, comodidades, exteriores, adyacencias, precios, fotos y captador."""
    return _detail(id_properties)


@tool
def list_sellers(name: str | None = None, limit: int = 20) -> str:
    """Lista captadores activos, con teléfono y cuántos inmuebles aprobados tienen."""
    return _sellers(_args(name=name, limit=limit))


@tool
def get_seller(id: int | None = None, name: str | None = None) -> str:
    """Ficha de un captador y hasta 8 inmuebles aprobados a su cargo."""
    return _seller(_args(id=id, name=name))


@tool
def list_visits(seller_name: str | None = None, limit: int = 10) -> str:
    """Visitas registradas, las más recientes primero."""
    return _visits(_args(seller_name=seller_name, limit=limit))


@tool
def list_searches(location: str | None = None, limit: int = 10) -> str:
    """Búsquedas inmobiliarias de clientes: zona, presupuesto y tipo."""
    return _searches(_args(location=location, limit=limit))


@tool
def list_leads(
    intention: str | None = None,
    zone: str | None = None,
    status: str | None = None,
    limit: int = 10,
) -> str:
    """Prospectos del CRM. Sin cédula."""
    return _leads(_args(intention=intention, zone=zone, status=status, limit=limit))


@tool
def list_assignments(limit: int = 10) -> str:
    """Clientes asignados: prospecto, delegado, asesor y estatus de seguimiento."""
    return _assignments(_args(limit=limit))


@tool
def list_publications(limit: int = 10) -> str:
    """Publicaciones de redes: canal, inmueble, enlace y estatus."""
    return _publications(_args(limit=limit))


@tool
def list_wasi(property_id: int | None = None, limit: int = 10) -> str:
    """Códigos Wasi vinculados a un inmueble."""
    return _wasi(_args(property_id=property_id, limit=limit))


@tool
def operational_summary() -> str:
    """Conteos de catálogo, captadores, visitas, búsquedas y prospectos. No incluye finanzas."""
    return _summary({})


def _propose(tool_name: str, **kwargs: object) -> str:
    """Propose-only: stores a pending action for an advisor to approve. Needs the turn's conversation."""
    ctx = conversation_context()
    if ctx is None:
        return json.dumps({"error": "no_conversation_context"}, ensure_ascii=False)
    return WRITE_TOOLS[tool_name](_args(**kwargs), ctx)


@tool
def proponer_registrar_telefono(phone: str) -> str:
    """Propone guardar el teléfono que el cliente dio. NO lo guarda: un asesor debe aprobarlo. Úsalo solo cuando el cliente escribió su número."""
    return _propose("proponer_registrar_telefono", phone=phone)


@tool
def proponer_crear_visita(date_at: str, property_label: str | None = None, notes: str | None = None) -> str:
    """Propone agendar una visita acordada con el cliente. NO la agenda: un asesor debe aprobarla. date_at futuro, formato YYYY-MM-DD HH:MM. Úsalo solo si el cliente confirmó día y hora."""
    return _propose("proponer_crear_visita", date_at=date_at, property_label=property_label, notes=notes)
