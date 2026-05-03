"""Consultas MySQL equivalentes a CrmAiPropertyToolRunner (solo lectura, status Aprobado)."""

from __future__ import annotations

import json
from typing import Any

import pymysql
from pymysql.cursors import DictCursor

from agente.config import get_settings


def _conn():
    s = get_settings()
    if not s.mysql_database or not s.mysql_user:
        raise RuntimeError(
            "Configura MYSQL_DATABASE y MYSQL_USER en agente/.env (y credenciales MySQL)."
        )
    return pymysql.connect(
        host=s.mysql_host,
        port=int(s.mysql_port),
        user=s.mysql_user,
        password=s.mysql_password,
        database=s.mysql_database,
        charset="utf8mb4",
        cursorclass=DictCursor,
    )


_SELECT_SEARCH = """
SELECT
    properties.id_properties,
    properties.price,
    properties.price_additional,
    properties.address,
    properties.bedrooms,
    properties.bathrooms,
    properties.meters_construction,
    properties.environments,
    municipality.name AS municipality_name,
    state.name AS state_name,
    housingtype.name AS housingtype_name,
    businessmodel.name AS business_model
FROM properties
INNER JOIN housingtype ON housingtype.id = properties.housing_type
INNER JOIN municipality ON municipality.id = properties.municipality
INNER JOIN state ON state.id = properties.state
INNER JOIN businessmodel ON businessmodel.id = properties.business_model
INNER JOIN status ON status.id = properties.status
WHERE status.name = %s
"""


_SELECT_DETAIL = """
SELECT
    properties.id_properties,
    properties.price,
    properties.price_additional,
    properties.address,
    properties.bedrooms,
    properties.bathrooms,
    properties.meters_construction,
    properties.meters_land,
    properties.environments,
    properties.amenities,
    properties.exterior,
    properties.adjacencies,
    municipality.name AS municipality_name,
    state.name AS state_name,
    housingtype.name AS housingtype_name,
    businessmodel.name AS business_model,
    markettype.name AS market_type
FROM properties
INNER JOIN housingtype ON housingtype.id = properties.housing_type
INNER JOIN municipality ON municipality.id = properties.municipality
INNER JOIN state ON state.id = properties.state
INNER JOIN businessmodel ON businessmodel.id = properties.business_model
INNER JOIN markettype ON markettype.id = properties.market_type
INNER JOIN status ON status.id = properties.status
WHERE status.name = %s AND properties.id_properties = %s
"""


def search_properties(args: dict[str, Any]) -> str:
    limit = int(args.get("limit", 5) or 5)
    limit = max(1, min(15, limit))

    def _opt_float(v: Any) -> float | None:
        if v is None or v == "":
            return None
        try:
            return float(v)
        except (TypeError, ValueError):
            return None

    params: list[Any] = ["Aprobado"]
    sql = _SELECT_SEARCH.strip()
    extra = []

    mn = _opt_float(args.get("min_price"))
    if mn is not None:
        extra.append("properties.price >= %s")
        params.append(mn)
    mx = _opt_float(args.get("max_price"))
    if mx is not None:
        extra.append("properties.price <= %s")
        params.append(mx)
    mb = args.get("min_bedrooms")
    if mb is not None and str(mb).isdigit():
        extra.append("properties.bedrooms >= %s")
        params.append(int(mb))
    loc = args.get("location_keyword")
    if loc and isinstance(loc, str) and loc.strip():
        kw = f"%{loc.strip()}%"
        extra.append("(municipality.name LIKE %s OR state.name LIKE %s OR properties.address LIKE %s)")
        params.extend([kw, kw, kw])

    if extra:
        sql += " AND " + " AND ".join(extra)
    sql += " ORDER BY properties.id_properties DESC LIMIT %s"
    params.append(limit)

    with _conn() as c:
        with c.cursor() as cur:
            cur.execute(sql, params)
            rows = cur.fetchall()

    # Serializar (Decimal/datetime) → tipos JSON
    def _norm(row: dict) -> dict:
        out = {}
        for k, v in row.items():
            if hasattr(v, "isoformat"):
                out[k] = str(v)
            elif hasattr(v, "__float__") and type(v).__name__ == "Decimal":
                out[k] = float(v)
            else:
                out[k] = v
        return out

    return json.dumps([_norm(r) for r in rows], ensure_ascii=False, indent=2)


def get_property_detail(id_properties: int) -> str:
    if id_properties < 1:
        return json.dumps({"error": "invalid_id_properties"}, ensure_ascii=False)

    with _conn() as c:
        with c.cursor() as cur:
            cur.execute(_SELECT_DETAIL.strip(), ("Aprobado", id_properties))
            row = cur.fetchone()

    if not row:
        return json.dumps(
            {"error": "not_found_or_not_approved", "id_properties": id_properties},
            ensure_ascii=False,
        )

    def _norm(v):
        if hasattr(v, "isoformat"):
            return str(v)
        if hasattr(v, "__float__") and type(v).__name__ == "Decimal":
            return float(v)
        return v

    return json.dumps({k: _norm(v) for k, v in row.items()}, ensure_ascii=False, indent=2)


def execute_tool(name: str, arguments_json: str) -> str:
    try:
        args = json.loads(arguments_json or "{}")
    except json.JSONDecodeError:
        args = {}
    if not isinstance(args, dict):
        args = {}

    if name == "search_properties":
        return search_properties(args)
    if name == "get_property_detail":
        return get_property_detail(int(args.get("id_properties") or 0))

    return json.dumps({"error": "unknown_tool", "name": name}, ensure_ascii=False)


OPENAI_TOOLS: list[dict[str, Any]] = [
    {
        "type": "function",
        "function": {
            "name": "search_properties",
            "description": (
                "Busca propiedades aprobadas en el catálogo con filtros opcionales "
                "(precio, dormitorios, ubicación por texto en municipio/estado/dirección)."
            ),
            "parameters": {
                "type": "object",
                "properties": {
                    "min_price": {"type": "number", "description": "Precio mínimo"},
                    "max_price": {"type": "number", "description": "Precio máximo"},
                    "min_bedrooms": {"type": "integer", "description": "Dormitorios mínimos"},
                    "location_keyword": {
                        "type": "string",
                        "description": "Texto en municipio, estado o dirección",
                    },
                    "limit": {
                        "type": "integer",
                        "description": "Máximo filas 1-15",
                        "default": 5,
                    },
                },
            },
        },
    },
    {
        "type": "function",
        "function": {
            "name": "get_property_detail",
            "description": "Detalle de una propiedad aprobada por id_properties.",
            "parameters": {
                "type": "object",
                "properties": {
                    "id_properties": {"type": "integer", "description": "ID interno"},
                },
                "required": ["id_properties"],
            },
        },
    },
]
