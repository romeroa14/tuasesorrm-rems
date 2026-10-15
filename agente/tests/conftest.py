import pytest

TEST_TOKEN = "test-agent-token"
AUTH = {"Authorization": f"Bearer {TEST_TOKEN}"}


@pytest.fixture(autouse=True)
def _agent_token(monkeypatch):
    monkeypatch.setenv("AGENT_API_TOKEN", TEST_TOKEN)
    monkeypatch.setenv("OPENROUTER_API_KEY", "test-openrouter-key")
    from agente.config import get_settings

    cache_clear = getattr(get_settings, "cache_clear", None)
    if cache_clear:
        cache_clear()
