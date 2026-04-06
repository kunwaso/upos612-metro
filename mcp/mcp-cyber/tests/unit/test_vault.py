import json

import pytest

from cyber_engine.credentials.vault import resolve_credential_ref


def test_resolve_from_json_env(monkeypatch: pytest.MonkeyPatch) -> None:
    blob = {"demo": {"username": "alice", "password": "secret"}}
    monkeypatch.setenv("CYBER_VAULT_JSON", json.dumps(blob))
    monkeypatch.delenv("CYBER_VAULT_FILE", raising=False)
    out = resolve_credential_ref("demo")
    assert out == {"username": "alice", "password": "secret"}


def test_resolve_missing_ref(monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setenv("CYBER_VAULT_JSON", "{}")
    assert resolve_credential_ref("nope") is None


def test_resolve_empty_ref() -> None:
    assert resolve_credential_ref(None) is None
    assert resolve_credential_ref("") is None
