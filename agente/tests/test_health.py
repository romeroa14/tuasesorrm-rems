from fastapi.testclient import TestClient

from agente.server import app


def test_health():
    client = TestClient(app)
    r = client.get("/health")
    assert r.status_code == 200
    data = r.json()
    assert data["status"] == "ok"
    assert data["service"] == "agente-rems"


def test_chat_requires_token(monkeypatch):
    client = TestClient(app)
    body = {"message": "hola"}

    monkeypatch.delenv("AGENT_API_TOKEN", raising=False)
    assert client.post("/v1/chat", json=body).status_code == 401

    monkeypatch.setenv("AGENT_API_TOKEN", "s3cret")
    assert client.post("/v1/chat", json=body).status_code == 401
    r = client.post("/v1/chat", json=body, headers={"Authorization": "Bearer wrong"})
    assert r.status_code == 401
