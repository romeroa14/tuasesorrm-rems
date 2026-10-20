"""Consultas MySQL de solo lectura: catálogo aprobado, fotos y vendedores."""

from __future__ import annotations

import json
from datetime import datetime
from pathlib import Path
from typing import Any

import pymysql
from pymysql.cursors import DictCursor

from agente.config import get_settings

_APPROVED = "Aprobado"
_SELLER_ROLES = (1, 5, 6)  # agente, coordinador de ventas, gerente de ventas
_PHOTO_EXT = {".jpg", ".jpeg", ".png", ".webp", ".gif"}


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


def _norm(v: Any) -> Any:
    if hasattr(v, "isoformat"):
        return str(v)
    if type(v).__name__ == "Decimal":
        return float(v)
    return v


def _norm_row(row: dict) -> dict:
    return {k: _norm(v) for k, v in row.items()}


def _acea_names(cur, csv: Any) -> str:
    ids = []
    for part in str(csv or "").split(","):
        part = part.strip()
        if part.isdigit():
            ids.append(int(part))
    if not ids:
        return ""
    marks = ",".join(["%s"] * len(ids))
    cur.execute(f"SELECT name FROM acea WHERE id_acea IN ({marks})", ids)
    return ", ".join(r["name"] for r in cur.fetchall() if r.get("name"))



def _condition_names(cur, csv: Any) -> str:
    ids = []
    for part in str(csv or "").split(","):
        part = part.strip()
        if part.isdigit():
            ids.append(int(part))
    if not ids:
        return ""
    marks = ",".join(["%s"] * len(ids))
    cur.execute(f"SELECT name FROM business_conditions WHERE id IN ({marks})", ids)
    return ", ".join(r["name"] for r in cur.fetchall() if r.get("name"))


def _describe(row: dict, extras: dict[str, str] | None = None) -> str:
    extras = extras or {}
    bits = []
    kind = " ".join(
        p for p in (row.get("housingtype_name"), "en", row.get("business_model")) if p and p != "en"
    )
    if row.get("housingtype_name") and row.get("business_model"):
        bits.append(f"{row['housingtype_name']} en {row['business_model']}")
    elif kind:
        bits.append(kind)
    place = ", ".join(
        p for p in (row.get("address"), row.get("city_name"), row.get("municipality_name"), row.get("state_name")) if p
    )
    if place:
        bits.append(place.strip())
    rooms = []
    if row.get("bedrooms") is not None:
        rooms.append(f"{row['bedrooms']} hab")
    if row.get("bathrooms") is not None:
        rooms.append(f"{row['bathrooms']} baños")
    if row.get("meters_construction"):
        rooms.append(f"{row['meters_construction']} m²")
    if rooms:
        bits.append(", ".join(str(x) for x in rooms))
    if row.get("garages") is not None:
        bits.append(f"{row['garages']} puestos")
    if row.get("meters_land"):
        bits.append(f"terreno {row['meters_land']} m²")
    if row.get("price") is not None:
        bits.append(f"Precio para venta: ${row['price']}")
    if row.get("price_additional") is not None:
        bits.append(f"Precio para alquiler: ${row['price_additional']}")
    captador = row.get("captador_name") or row.get("seller_name")
    if captador:
        bits.append(f"Captador: {captador}")
    published = _published_label(row.get("created_at"))
    if published:
        bits.append(f"Publicado el: {published}")
    for label, key in (
        ("Ambientes", "environments"),
        ("Comodidades", "amenities"),
        ("Exteriores", "exterior"),
        ("Adyacencias", "adjacencies"),
        ("Condiciones", "business_conditions"),
    ):
        text = extras.get(key) or ""
        if text:
            bits.append(f"{label}: {text}")
    return ". ".join(bits)


def _photo_urls(property_id: int, filenames: list[str], limit: int) -> list[str]:
    s = get_settings()
    base = s.public_base_url.rstrip("/")
    folder = Path(s.properties_root) / f"RM00{property_id}" / "graphic"
    urls: list[str] = []
    for name in filenames:
        if not name:
            continue
        path = folder / name
        if path.is_file():
            urls.append(f"{base}/properties/RM00{property_id}/graphic/{name}")
        if len(urls) >= limit:
            return urls
    if urls or not folder.is_dir():
        return urls
    wasi = sorted(
        p.name
        for p in folder.iterdir()
        if p.is_file() and p.name.lower().startswith("wasi_") and p.suffix.lower() in _PHOTO_EXT
    )
    return [f"{base}/properties/RM00{property_id}/graphic/{name}" for name in wasi[:limit]]


def _image_names(cur, property_id: int) -> list[str]:
    cur.execute(
        "SELECT image FROM imageorderbyproperties WHERE property_id = %s ORDER BY id",
        (property_id,),
    )
    return [r["image"] for r in cur.fetchall() if r.get("image")]


_SELECT_SEARCH = """
SELECT
    properties.id_properties,
    properties.price,
    properties.price_additional,
    properties.created_at,
    properties.address,
    properties.bedrooms,
    properties.bathrooms,
    properties.meters_construction,
    municipality.name AS municipality_name,
    state.name AS state_name,
    city.name AS city_name,
    housingtype.name AS housingtype_name,
    businessmodel.name AS business_model,
    users.full_name AS seller_name,
    users.phone AS seller_phone
FROM properties
INNER JOIN housingtype ON housingtype.id = properties.housing_type
INNER JOIN municipality ON municipality.id = properties.municipality
INNER JOIN state ON state.id = properties.state
LEFT JOIN city ON city.id = properties.city
INNER JOIN businessmodel ON businessmodel.id = properties.business_model
INNER JOIN status ON status.id = properties.status
LEFT JOIN users ON users.id = properties.agent
WHERE status.name = %s
"""


_SELECT_DETAIL = """
SELECT
    properties.id_properties,
    properties.price,
    properties.price_additional,
    properties.created_at,
    properties.address,
    properties.bedrooms,
    properties.bathrooms,
    properties.garages,
    properties.meters_construction,
    properties.meters_land,
    properties.environments,
    properties.amenities,
    properties.exterior,
    properties.adjacencies,
    properties.business_conditions,
    municipality.name AS municipality_name,
    state.name AS state_name,
    city.name AS city_name,
    housingtype.name AS housingtype_name,
    businessmodel.name AS business_model,
    markettype.name AS market_type,
    status.name AS status_name,
    users.id AS seller_id,
    users.full_name AS seller_name,
    users.phone AS seller_phone,
    users.email AS seller_email
FROM properties
INNER JOIN housingtype ON housingtype.id = properties.housing_type
INNER JOIN municipality ON municipality.id = properties.municipality
INNER JOIN state ON state.id = properties.state
LEFT JOIN city ON city.id = properties.city
INNER JOIN businessmodel ON businessmodel.id = properties.business_model
INNER JOIN markettype ON markettype.id = properties.market_type
INNER JOIN status ON status.id = properties.status
LEFT JOIN users ON users.id = properties.agent
WHERE properties.id_properties = %s
"""



def _published_label(value: Any) -> str:
    raw = str(value or "").strip()
    if len(raw) < 10:
        return ""
    try:
        return datetime.strptime(raw[:10], "%Y-%m-%d").strftime("%d-%m-%Y")
    except ValueError:
        return ""


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


def search_properties(args: dict[str, Any]) -> str:
    since = _date_bound(args.get("since_date"))
    until = _date_bound(args.get("until_date"), end=True)
    limit = int(args.get("limit", 5) or 5)
    if since or until:
        limit = max(limit, 15)
    limit = max(1, min(30, limit))

    def _opt_float(v: Any) -> float | None:
        if v is None or v == "":
            return None
        try:
            return float(v)
        except (TypeError, ValueError):
            return None

    status = str(args.get("status") or _APPROVED).strip()
    params: list[Any] = []
    sql = _SELECT_SEARCH.strip()
    if status.lower() not in ("todos", "all", "*"):
        params.append(status)
    else:
        sql = sql.replace("WHERE status.name = %s", "WHERE 1=1")
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
        extra.append(
            "(municipality.name LIKE %s OR state.name LIKE %s OR city.name LIKE %s OR properties.address LIKE %s)"
        )
        params.extend([kw, kw, kw, kw])
    seller = args.get("seller_name")
    if seller and isinstance(seller, str) and seller.strip():
        extra.append("users.full_name LIKE %s")
        params.append(f"%{seller.strip()}%")
    if since:
        extra.append("properties.created_at >= %s")
        params.append(since)
    if until:
        extra.append("properties.created_at <= %s")
        params.append(until)

    if extra:
        sql += " AND " + " AND ".join(extra)
    sql += " ORDER BY properties.created_at DESC, properties.id_properties DESC LIMIT %s"
    params.append(limit)

    with _conn() as c:
        with c.cursor() as cur:
            cur.execute(sql, params)
            rows = cur.fetchall()
            out = []
            for row in rows:
                item = _norm_row(row)
                names = _image_names(cur, int(item["id_properties"]))
                photos = _photo_urls(int(item["id_properties"]), names, 1)
                item["description"] = _describe(item)
                item["primary_photo"] = photos[0] if photos else None
                out.append(item)

    return json.dumps(out, ensure_ascii=False, indent=2)


def get_property_detail(id_properties: int) -> str:
    if id_properties < 1:
        return json.dumps({"error": "invalid_id_properties"}, ensure_ascii=False)

    with _conn() as c:
        with c.cursor() as cur:
            cur.execute(_SELECT_DETAIL.strip(), (id_properties,))
            row = cur.fetchone()
            if not row:
                return json.dumps(
                    {"error": "not_found_or_not_approved", "id_properties": id_properties},
                    ensure_ascii=False,
                )
            item = _norm_row(row)
            extras = {
                "environments": _acea_names(cur, item.pop("environments", "")),
                "amenities": _acea_names(cur, item.pop("amenities", "")),
                "exterior": _acea_names(cur, item.pop("exterior", "")),
                "adjacencies": _acea_names(cur, item.pop("adjacencies", "")),
                "business_conditions": _condition_names(cur, item.pop("business_conditions", "")),
            }
            names = _image_names(cur, id_properties)
            item["captador_id"] = item.get("seller_id")
            item["captador_name"] = item.get("seller_name")
            item["captador_phone"] = item.get("seller_phone")
            item["captador_email"] = item.get("seller_email")
            item["description"] = _describe(item, extras)
            item["details"] = {k: v for k, v in extras.items() if v}
            item["photos"] = _photo_urls(id_properties, names, 8)

    return json.dumps(item, ensure_ascii=False, indent=2)


def list_sellers(args: dict[str, Any]) -> str:
    limit = int(args.get("limit", 20) or 20)
    limit = max(1, min(50, limit))
    name = args.get("name")
    sql = """
        SELECT users.id, users.full_name, users.phone, users.email,
            (
                SELECT COUNT(*)
                FROM properties
                INNER JOIN status ON status.id = properties.status
                WHERE properties.agent = users.id AND status.name = %s
            ) AS approved_listings
        FROM users
        WHERE users.status = 'activo' AND users.id_fk_rol IN (%s, %s, %s)
    """
    params = [_APPROVED, *_SELLER_ROLES]
    if name and isinstance(name, str) and name.strip():
        sql += " AND users.full_name LIKE %s"
        params.append(f"%{name.strip()}%")
    sql += " ORDER BY users.full_name LIMIT %s"
    params.append(limit)

    with _conn() as c:
        with c.cursor() as cur:
            cur.execute(sql, params)
            rows = [_norm_row(r) for r in cur.fetchall()]
    return json.dumps(rows, ensure_ascii=False, indent=2)


def get_seller(args: dict[str, Any]) -> str:
    seller_id = int(args.get("id") or 0)
    name = args.get("name")
    if seller_id < 1 and not (isinstance(name, str) and name.strip()):
        return json.dumps({"error": "id_or_name_required"}, ensure_ascii=False)

    sql = """
        SELECT users.id, users.full_name, users.phone, users.email, roles.name AS role
        FROM users
        LEFT JOIN roles ON roles.id = users.id_fk_rol
        WHERE users.status = 'activo'
    """
    params: list[Any] = []
    if seller_id > 0:
        sql += " AND users.id = %s"
        params.append(seller_id)
    else:
        sql += " AND users.full_name LIKE %s"
        params.append(f"%{name.strip()}%")
    sql += " LIMIT 5"

    with _conn() as c:
        with c.cursor() as cur:
            cur.execute(sql, params)
            people = [_norm_row(r) for r in cur.fetchall()]
            for person in people:
                cur.execute(
                    """
                    SELECT properties.id_properties, properties.price, properties.address,
                        municipality.name AS municipality_name
                    FROM properties
                    INNER JOIN status ON status.id = properties.status
                    INNER JOIN municipality ON municipality.id = properties.municipality
                    WHERE properties.agent = %s AND status.name = %s
                    ORDER BY properties.id_properties DESC
                    LIMIT 8
                    """,
                    (person["id"], _APPROVED),
                )
                person["approved_properties"] = [_norm_row(r) for r in cur.fetchall()]

    if not people:
        return json.dumps({"error": "seller_not_found"}, ensure_ascii=False)
    return json.dumps(people if len(people) > 1 else people[0], ensure_ascii=False, indent=2)


def _limit(args: dict[str, Any], default: int = 10, cap: int = 30) -> int:
    try:
        n = int(args.get("limit", default) or default)
    except (TypeError, ValueError):
        n = default
    return max(1, min(cap, n))


def list_visits(args: dict[str, Any]) -> str:
    limit = _limit(args)
    seller = str(args.get("seller_name") or "").strip()
    sql = """
        SELECT myvisits.id, myvisits.date_at, LEFT(myvisits.description, 280) AS description,
               users.full_name AS seller_name
        FROM myvisits
        LEFT JOIN users ON users.id = myvisits.id_user
    """
    params: list[Any] = []
    if seller:
        sql += " WHERE users.full_name LIKE %s"
        params.append(f"%{seller}%")
    sql += " ORDER BY myvisits.date_at DESC LIMIT %s"
    params.append(limit)
    with _conn() as conn:
        with conn.cursor() as cur:
            cur.execute(sql, params)
            rows = [_norm_row(r) for r in cur.fetchall()]
    return json.dumps({"visits": rows}, ensure_ascii=False, indent=2)


def list_searches(args: dict[str, Any]) -> str:
    limit = _limit(args)
    location = str(args.get("location") or "").strip()
    sql = """
        SELECT realstatesearches.id, realstatesearches.estimate_price,
               realstatesearches.location, LEFT(realstatesearches.description, 280) AS description,
               housingtype.name AS housing_type, businessmodel.name AS business_model,
               users.full_name AS seller_name, realstatesearches.created_at
        FROM realstatesearches
        LEFT JOIN users ON users.id = realstatesearches.id_user
        LEFT JOIN housingtype ON housingtype.id = realstatesearches.id_housingtype
        LEFT JOIN businessmodel ON businessmodel.id = realstatesearches.id_businessmodel
    """
    params: list[Any] = []
    if location:
        sql += " WHERE realstatesearches.location LIKE %s OR realstatesearches.description LIKE %s"
        params.extend([f"%{location}%", f"%{location}%"])
    sql += " ORDER BY realstatesearches.created_at DESC LIMIT %s"
    params.append(limit)
    with _conn() as conn:
        with conn.cursor() as cur:
            cur.execute(sql, params)
            rows = [_norm_row(r) for r in cur.fetchall()]
    return json.dumps({"searches": rows}, ensure_ascii=False, indent=2)


def list_leads(args: dict[str, Any]) -> str:
    limit = _limit(args)
    sql = """
        SELECT leads.id, leads.name, leads.phone, leads.email,
               leads.intention_label, leads.interest_type, leads.budget_detected,
               leads.zone_interest, leads.status, funnels.name AS funnel,
               users.full_name AS assigned_to, leads.created_at
        FROM leads
        LEFT JOIN funnels ON funnels.id = leads.id_funnel
        LEFT JOIN users ON users.id = leads.id_user
        WHERE 1=1
    """
    params: list[Any] = []
    label = str(args.get("intention") or "").strip().lower()
    if label in ("frio", "tibio", "caliente", "listo"):
        sql += " AND leads.intention_label = %s"
        params.append(label)
    zone = str(args.get("zone") or "").strip()
    if zone:
        sql += " AND (leads.zone_interest LIKE %s OR leads.name LIKE %s)"
        params.extend([f"%{zone}%", f"%{zone}%"])
    status = str(args.get("status") or "").strip()
    if status:
        sql += " AND leads.status LIKE %s"
        params.append(f"%{status}%")
    sql += " ORDER BY leads.created_at DESC LIMIT %s"
    params.append(limit)
    with _conn() as conn:
        with conn.cursor() as cur:
            cur.execute(sql, params)
            rows = [_norm_row(r) for r in cur.fetchall()]
    return json.dumps({"leads": rows}, ensure_ascii=False, indent=2)


def operational_summary(_args: dict[str, Any]) -> str:
    with _conn() as conn:
        with conn.cursor() as cur:
            cur.execute(
                """
                SELECT
                  (SELECT COUNT(*) FROM properties p JOIN status s ON s.id = p.status WHERE s.name = %s) AS approved_properties,
                  (SELECT COUNT(*) FROM properties) AS properties_all,
                  (SELECT COUNT(*) FROM users WHERE status = 'activo' AND id_fk_rol IN (1,5,6)) AS active_sellers,
                  (SELECT COUNT(*) FROM myvisits) AS visits,
                  (SELECT COUNT(*) FROM realstatesearches) AS searches,
                  (SELECT COUNT(*) FROM leads) AS leads
                """,
                (_APPROVED,),
            )
            row = _norm_row(cur.fetchone() or {})
    row["excluded_module"] = "finanzas"
    return json.dumps(row, ensure_ascii=False, indent=2)


def list_publications(args: dict[str, Any]) -> str:
    limit = _limit(args)
    sql = """
        SELECT rrsspublications.id, rrsspublications.property_id, rrsspublications.link,
               rrsspublications.status, rrsspublications.date_at, kindrrss.name AS channel
        FROM rrsspublications
        LEFT JOIN kindrrss ON kindrrss.id = rrsspublications.kindrrss_id
        ORDER BY rrsspublications.date_at DESC
        LIMIT %s
    """
    with _conn() as conn:
        with conn.cursor() as cur:
            cur.execute(sql, (limit,))
            rows = [_norm_row(r) for r in cur.fetchall()]
    return json.dumps({"publications": rows}, ensure_ascii=False, indent=2)


def list_assignments(args: dict[str, Any]) -> str:
    limit = _limit(args)
    sql = """
        SELECT assignedclients.id, leads.name AS lead_name, leads.phone,
               delegate.full_name AS delegate_name, assigned.full_name AS assigned_to,
               trackingstatus.name AS tracking_status, assignedclients.assignment_at,
               LEFT(assignedclients.observation, 200) AS observation
        FROM assignedclients
        LEFT JOIN leads ON leads.id = assignedclients.lead_id
        LEFT JOIN users delegate ON delegate.id = assignedclients.delegate_id
        LEFT JOIN users assigned ON assigned.id = assignedclients.assigned_id
        LEFT JOIN trackingstatus ON trackingstatus.id = assignedclients.trackingstatus_id
        ORDER BY assignedclients.assignment_at DESC
        LIMIT %s
    """
    with _conn() as conn:
        with conn.cursor() as cur:
            cur.execute(sql, (limit,))
            rows = [_norm_row(r) for r in cur.fetchall()]
    return json.dumps({"assignments": rows}, ensure_ascii=False, indent=2)


def list_wasi(args: dict[str, Any]) -> str:
    limit = _limit(args)
    property_id = args.get("property_id")
    sql = """
        SELECT servicewasi.property_id, servicewasi.code_wasi, servicewasi.status,
               users.full_name AS author_name
        FROM servicewasi
        LEFT JOIN users ON users.id = servicewasi.author_id
    """
    params: list[Any] = []
    if str(property_id or "").isdigit():
        sql += " WHERE servicewasi.property_id = %s"
        params.append(int(property_id))
    sql += " ORDER BY servicewasi.id DESC LIMIT %s"
    params.append(limit)
    with _conn() as conn:
        with conn.cursor() as cur:
            cur.execute(sql, params)
            rows = [_norm_row(r) for r in cur.fetchall()]
    return json.dumps({"wasi": rows}, ensure_ascii=False, indent=2)


def execute_tool(name: str, arguments_json: str, ctx: dict[str, Any] | None = None) -> str:
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
    if name == "list_sellers":
        return list_sellers(args)
    if name == "get_seller":
        return get_seller(args)
    if name == "list_visits":
        return list_visits(args)
    if name == "list_searches":
        return list_searches(args)
    if name == "list_leads":
        return list_leads(args)
    if name == "operational_summary":
        return operational_summary(args)
    if name == "list_publications":
        return list_publications(args)
    if name == "list_assignments":
        return list_assignments(args)
    if name == "list_wasi":
        return list_wasi(args)

    # Imported here: pending_actions imports _conn from this module.
    from agente.pending_actions import WRITE_TOOLS

    if name in WRITE_TOOLS:
        if not ctx or not ctx.get("conversation_id") or not ctx.get("lead_id"):
            return json.dumps({"error": "no_conversation_context"}, ensure_ascii=False)
        return WRITE_TOOLS[name](args, ctx)

    return json.dumps({"error": "unknown_tool", "name": name}, ensure_ascii=False)


OPENAI_TOOLS: list[dict[str, Any]] = [
    {
        "type": "function",
        "function": {
            "name": "search_properties",
            "description": (
                "Busca propiedades aprobadas. Devuelve precio, descripción corta, "
                "vendedor y la foto principal si existe."
            ),
            "parameters": {
                "type": "object",
                "properties": {
                    "min_price": {"type": "number", "description": "Precio mínimo"},
                    "max_price": {"type": "number", "description": "Precio máximo"},
                    "min_bedrooms": {"type": "integer", "description": "Dormitorios mínimos"},
                    "location_keyword": {
                        "type": "string",
                        "description": "Texto en municipio, ciudad, estado o dirección",
                    },
                    "seller_name": {"type": "string", "description": "Nombre del vendedor"},
                    "status": {
                        "type": "string",
                        "description": "Estatus del inmueble. Por defecto Aprobado. Use todos para cualquier estatus.",
                    },
                    "since_date": {
                        "type": "string",
                        "description": "Fecha de publicación desde, YYYY-MM-DD. Es properties.created_at, la misma del catálogo.",
                    },
                    "until_date": {
                        "type": "string",
                        "description": "Fecha de publicación hasta, YYYY-MM-DD.",
                    },
                    "limit": {"type": "integer", "description": "Máximo filas 1-30", "default": 5},
                },
            },
        },
    },
    {
        "type": "function",
        "function": {
            "name": "get_property_detail",
            "description": (
                "Ficha de un inmueble: precio, descripción, fotos, estatus y vendedor."
            ),
            "parameters": {
                "type": "object",
                "properties": {
                    "id_properties": {"type": "integer", "description": "ID interno"},
                },
                "required": ["id_properties"],
            },
        },
    },
    {
        "type": "function",
        "function": {
            "name": "list_sellers",
            "description": "Lista vendedores activos (nombre, teléfono, correo y cuántos inmuebles aprobados tienen).",
            "parameters": {
                "type": "object",
                "properties": {
                    "name": {"type": "string", "description": "Filtro opcional por nombre"},
                    "limit": {"type": "integer", "description": "Máximo filas 1-50", "default": 20},
                },
            },
        },
    },
    {
        "type": "function",
        "function": {
            "name": "get_seller",
            "description": "Ficha de un vendedor activo y hasta 8 inmuebles aprobados a su cargo.",
            "parameters": {
                "type": "object",
                "properties": {
                    "id": {"type": "integer", "description": "ID del usuario"},
                    "name": {"type": "string", "description": "Nombre, si no hay id"},
                },
            },
        },
    },
    {
        "type": "function",
        "function": {
            "name": "list_visits",
            "description": "Visitas agendadas o registradas, las más recientes primero.",
            "parameters": {
                "type": "object",
                "properties": {
                    "seller_name": {"type": "string"},
                    "limit": {"type": "integer", "default": 10},
                },
            },
        },
    },
    {
        "type": "function",
        "function": {
            "name": "list_searches",
            "description": "Búsquedas inmobiliarias de clientes (zona, presupuesto estimado, tipo).",
            "parameters": {
                "type": "object",
                "properties": {
                    "location": {"type": "string"},
                    "limit": {"type": "integer", "default": 10},
                },
            },
        },
    },
    {
        "type": "function",
        "function": {
            "name": "list_leads",
            "description": "Prospectos del CRM: nombre, contacto, intención, zona y embudo. Sin cédula.",
            "parameters": {
                "type": "object",
                "properties": {
                    "intention": {
                        "type": "string",
                        "description": "frio, tibio, caliente o listo",
                    },
                    "zone": {"type": "string"},
                    "status": {"type": "string"},
                    "limit": {"type": "integer", "default": 10},
                },
            },
        },
    },
    {
        "type": "function",
        "function": {
            "name": "operational_summary",
            "description": "Conteos de catálogo, vendedores, visitas, búsquedas y prospectos. No incluye finanzas.",
            "parameters": {"type": "object", "properties": {}},
        },
    },
    {
        "type": "function",
        "function": {
            "name": "list_publications",
            "description": "Publicaciones de marketing (red social, inmueble, enlace y estatus).",
            "parameters": {
                "type": "object",
                "properties": {"limit": {"type": "integer", "default": 10}},
            },
        },
    },
    {
        "type": "function",
        "function": {
            "name": "list_assignments",
            "description": "Clientes asignados en el CRM: prospecto, delegado, asesor y estatus de seguimiento.",
            "parameters": {
                "type": "object",
                "properties": {"limit": {"type": "integer", "default": 10}},
            },
        },
    },
    {
        "type": "function",
        "function": {
            "name": "list_wasi",
            "description": "Códigos Wasi vinculados a un inmueble.",
            "parameters": {
                "type": "object",
                "properties": {
                    "property_id": {"type": "integer"},
                    "limit": {"type": "integer", "default": 10},
                },
            },
        },
    },
]


def tools_for(ctx: dict[str, Any] | None) -> list[dict[str, Any]]:
    """Read tools always; propose-only write tools just when the turn belongs to a known conversation."""
    if ctx and ctx.get("conversation_id") and ctx.get("lead_id"):
        from agente.pending_actions import WRITE_TOOL_SCHEMAS

        return [*OPENAI_TOOLS, *WRITE_TOOL_SCHEMAS]
    return OPENAI_TOOLS
