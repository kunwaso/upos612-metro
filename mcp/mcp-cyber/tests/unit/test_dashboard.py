"""Dashboard HTML behavior at /."""

from fastapi.testclient import TestClient

from cyber_api.main import app
from cyber_api.settings import settings


def test_dashboard_root_redirects_to_docs_when_disabled(monkeypatch) -> None:
    monkeypatch.setattr(settings, "dashboard_enabled", False)
    client = TestClient(app)
    r = client.get("/", follow_redirects=False)
    assert r.status_code == 302
    assert r.headers.get("location") == "/docs"


def test_dashboard_root_returns_html_when_enabled(monkeypatch) -> None:
    monkeypatch.setattr(settings, "dashboard_enabled", True)
    client = TestClient(app)
    r = client.get("/")
    assert r.status_code == 200
    assert "text/html" in r.headers.get("content-type", "")
    body = r.text.lower()
    assert "mcp-cyber" in body
    assert "phase 2" in body or "authenticated_passive" in body
