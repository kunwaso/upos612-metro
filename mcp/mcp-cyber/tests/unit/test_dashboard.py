"""Dashboard HTML is served at / when enabled (no DB for GET /)."""

from fastapi.testclient import TestClient

from cyber_api.main import app


def test_dashboard_root_returns_html() -> None:
    client = TestClient(app)
    r = client.get("/")
    assert r.status_code == 200
    assert "text/html" in r.headers.get("content-type", "")
    assert "mcp-cyber" in r.text.lower()
