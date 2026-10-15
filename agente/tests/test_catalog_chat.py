"""Catalog chat stays off the supervisor and uses the soul on that request."""

from langchain_core.messages import AIMessage
from fastapi.testclient import TestClient

from agente.server import app


def _fake_factory(recorded: dict):
    def create_react_agent(model, tools, prompt, **kwargs):
        recorded.setdefault("souls", []).append(prompt)
        recorded["tools"] = [getattr(tool, "name", str(tool)) for tool in tools]

        class Agent:
            def invoke(self, payload, config=None):
                return {"messages": [AIMessage(content=f"catalog:{prompt}")]}

        return Agent()

    return create_react_agent


def _patch_catalog(monkeypatch, recorded: dict):
    monkeypatch.setattr("agente.catalog_agent.create_react_agent", _fake_factory(recorded))
    monkeypatch.setattr("agente.catalog_agent.ChatOpenAI", lambda **kwargs: object())

    def forbid(*args, **kwargs):
        raise AssertionError("supervisor called")

    monkeypatch.setattr("agente.runner.run_agent_turn", forbid)
    monkeypatch.setattr("agente.graph_app.get_graph", forbid)


def test_catalog_chat_does_not_call_supervisor(monkeypatch):
    recorded: dict = {}
    _patch_catalog(monkeypatch, recorded)
    client = TestClient(app)
    response = client.post(
        "/v1/catalog/chat",
        json={"message": "busco un apartamento", "soul": "Eres el catalogo.", "debug": True},
    )
    assert response.status_code == 200
    body = response.json()
    assert body["reply"] == "catalog:Eres el catalogo."
    assert body["debug"] == []
    assert set(recorded["tools"]) == {"search_properties", "get_property_detail"}
    forbidden = {
        "finance_summary",
        "list_finance_income",
        "list_finance_expenses",
        "list_leads",
        "list_assignments",
        "list_sellers",
    }
    assert forbidden.isdisjoint(recorded["tools"])


def test_each_request_builds_an_agent_with_that_soul(monkeypatch):
    recorded: dict = {}
    _patch_catalog(monkeypatch, recorded)
    client = TestClient(app)
    first = client.post("/v1/catalog/chat", json={"message": "hola", "soul": "Soul uno"})
    second = client.post("/v1/catalog/chat", json={"message": "hola", "soul": "Soul dos"})
    assert first.status_code == 200
    assert second.status_code == 200
    assert recorded["souls"] == ["Soul uno", "Soul dos"]
    assert first.json()["reply"] == "catalog:Soul uno"
    assert second.json()["reply"] == "catalog:Soul dos"


def test_empty_message_or_soul_is_422():
    client = TestClient(app)
    empty_message = client.post("/v1/catalog/chat", json={"message": "", "soul": "alma"})
    empty_soul = client.post("/v1/catalog/chat", json={"message": "hola", "soul": ""})
    assert empty_message.status_code == 422
    assert empty_soul.status_code == 422


def test_missing_model_key_matches_supervisor_503(monkeypatch):
    class EmptyKey:
        openrouter_api_key = "  "

    monkeypatch.setattr("agente.server.get_settings", lambda: EmptyKey())
    client = TestClient(app)
    catalog = client.post("/v1/catalog/chat", json={"message": "hola", "soul": "alma"})
    supervisor = client.post("/v1/chat", json={"message": "hola"})
    assert catalog.status_code == 503
    assert supervisor.status_code == 503
    assert set(catalog.json()["detail"]) == set(supervisor.json()["detail"])
    assert catalog.json()["detail"]["error"] == "missing_openrouter_api_key"


def test_supervisor_route_still_calls_run_agent_turn(monkeypatch):
    class Ready:
        openrouter_api_key = "test-key"

    monkeypatch.setattr("agente.server.get_settings", lambda: Ready())
    called = {}

    def fake_turn(message, history, include_debug=False, finance=None):
        called["message"] = message
        return ("supervisor-reply", [])

    monkeypatch.setattr("agente.server.run_agent_turn", fake_turn)
    client = TestClient(app)
    response = client.post("/v1/chat", json={"message": "cuantas visitas"})
    assert response.status_code == 200
    assert response.json()["reply"] == "supervisor-reply"
    assert called["message"] == "cuantas visitas"
