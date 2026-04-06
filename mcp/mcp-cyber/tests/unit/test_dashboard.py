"""Dashboard HTML is served at / when enabled (no DB for GET /)."""

from fastapi.testclient import TestClient

from cyber_api.main import app


def test_dashboard_root_returns_html() -> None:
    client = TestClient(app)
    r = client.get("/")
    assert r.status_code == 200
    assert "text/html" in r.headers.get("content-type", "")
    body = r.text.lower()
    assert "mcp-cyber" in body
    assert "phase 2" in body or "authenticated_passive" in body
