import json
from datetime import datetime, timedelta

from agente import db_tools, pending_actions


def _capture(monkeypatch):
    rows = []
    monkeypatch.setattr(pending_actions, "_insert", lambda row: rows.append(row) or len(rows))
    return rows


CTX = {"conversation_id": 7, "lead_id": 3}


def test_phone_proposal_is_stored_pending(monkeypatch):
    rows = _capture(monkeypatch)
    out = json.loads(pending_actions.propose_register_phone({"phone": "0412-555 1234"}, CTX))
    assert out["status"] == "proposed"
    assert rows == [
        {"conversation_id": 7, "lead_id": 3, "action": "registrar_telefono", "payload": {"phone": "0412-555 1234"}}
    ]


def test_short_phone_is_not_stored(monkeypatch):
    rows = _capture(monkeypatch)
    out = json.loads(pending_actions.propose_register_phone({"phone": "123"}, CTX))
    assert out["status"] == "invalid"
    assert rows == []


def test_visit_needs_a_future_date(monkeypatch):
    rows = _capture(monkeypatch)
    past = json.loads(pending_actions.propose_create_visit({"date_at": "2020-01-01 10:00"}, CTX))
    junk = json.loads(pending_actions.propose_create_visit({"date_at": "mañana"}, CTX))
    assert past["status"] == junk["status"] == "invalid"
    assert rows == []

    future = (datetime.now() + timedelta(days=2)).strftime("%Y-%m-%d %H:%M")
    ok = json.loads(
        pending_actions.propose_create_visit({"date_at": future, "property_label": "Apto X", "notes": "n"}, CTX)
    )
    assert ok["status"] == "proposed"
    assert rows[0]["action"] == "crear_visita"
    assert rows[0]["payload"]["property_label"] == "Apto X"


def test_write_tools_require_conversation_context(monkeypatch):
    rows = _capture(monkeypatch)
    out = json.loads(db_tools.execute_tool("proponer_registrar_telefono", '{"phone":"04125551234"}', None))
    assert out["error"] == "no_conversation_context"
    assert rows == []


def test_write_tools_are_exposed_only_with_context():
    names = lambda tools: {t["function"]["name"] for t in tools}
    assert "proponer_crear_visita" not in names(db_tools.tools_for(None))
    assert {"proponer_registrar_telefono", "proponer_crear_visita"} <= names(db_tools.tools_for(CTX))
    assert "search_properties" in names(db_tools.tools_for(None))


def test_langchain_write_tools_need_the_turn_context(monkeypatch):
    from agente import tools_modules

    rows = _capture(monkeypatch)
    assert json.loads(tools_modules.proponer_registrar_telefono.invoke({"phone": "04125551234"}))["error"] == "no_conversation_context"
    assert rows == []

    token = pending_actions.set_conversation_context(CTX)
    try:
        out = json.loads(tools_modules.proponer_registrar_telefono.invoke({"phone": "04125551234"}))
    finally:
        pending_actions.reset_conversation_context(token)
    assert out["status"] == "proposed"
    assert rows[0]["conversation_id"] == 7 and rows[0]["action"] == "registrar_telefono"
    assert pending_actions.conversation_context() is None

