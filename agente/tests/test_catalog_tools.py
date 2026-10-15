"""Approved-only catalog wrappers hide seller phone and email."""

import json

from agente.catalog_agent import apply_detail, apply_search


def test_search_with_todos_returns_only_approved_and_strips_contact():
    seen = {}

    def raw(args):
        seen["status"] = args.get("status")
        return json.dumps([
            {
                "id_properties": 1,
                "status": "Aprobado",
                "address": "Valencia",
                "seller_phone": "04141234567",
                "seller_email": "seller@example.com",
                "primary_photo": "https://cdn.example/photo.jpg",
                "nested": {"captador_phone": "04140000000", "city": "Valencia"},
            },
            {
                "id_properties": 2,
                "status_name": "Borrador",
                "phone": "04241234567",
                "email": "hidden@example.com",
                "address": "No debe salir",
            },
            {"id_properties": 3, "address": "Sin estatus en la fila"},
        ])

    rows = json.loads(apply_search({"status": "todos", "limit": 5, "location_keyword": "Valencia"}, raw))
    assert seen["status"] == "Aprobado"
    ids = [row["id_properties"] for row in rows]
    assert ids == [1, 3]
    blob = json.dumps(rows)
    assert "04141234567" not in blob
    assert "seller@example.com" not in blob
    assert "04140000000" not in blob
    assert "04241234567" not in blob
    assert "hidden@example.com" not in blob
    assert "No debe salir" not in blob
    assert "https://cdn.example/photo.jpg" in blob
    assert rows[0]["nested"] == {"city": "Valencia"}
    assert "phone" not in json.dumps(list(rows[0].keys())).lower()


def test_detail_rejects_missing_and_non_approved_without_contact():
    def raw(id_properties: int) -> str:
        if id_properties == 5:
            return json.dumps({
                "error": "not_found_or_not_approved",
                "id_properties": 5,
                "phone": "04141111111",
                "email": "missing@example.com",
            })
        return json.dumps({
            "id_properties": id_properties,
            "status_name": "Inactivo",
            "address": "Secreto",
            "seller_phone": "04142222222",
            "captador_email": "cap@example.com",
        })

    missing = json.loads(apply_detail(5, raw))
    rejected = json.loads(apply_detail(7, raw))
    assert missing == {"error": "not_found_or_not_approved", "id_properties": 5}
    assert rejected == {"error": "not_found_or_not_approved", "id_properties": 7}
    assert "0414" not in json.dumps(missing)
    assert "Secreto" not in json.dumps(rejected)
    assert "cap@example.com" not in json.dumps(rejected)


def test_approved_detail_strips_contact_keys_and_keeps_photos():
    def raw(id_properties: int) -> str:
        return json.dumps({
            "id_properties": id_properties,
            "status_name": "Aprobado",
            "address": "Calle 1",
            "phone": "04143333333",
            "email": "a@b.c",
            "seller_phone": "04144444444",
            "seller_email": "s@b.c",
            "captador_phone": "04145555555",
            "captador_email": "c@b.c",
            "photos": ["https://cdn.example/fachada.jpg"],
        })

    item = json.loads(apply_detail(9, raw))
    assert item["id_properties"] == 9
    assert item["address"] == "Calle 1"
    assert item["photos"] == ["https://cdn.example/fachada.jpg"]
    assert item["status_name"] == "Aprobado"
    serialized = json.dumps(item)
    for secret in ("04143333333", "a@b.c", "04144444444", "s@b.c", "04145555555", "c@b.c"):
        assert secret not in serialized
    for key in item:
        lowered = key.lower()
        assert "phone" not in lowered
        assert "email" not in lowered
