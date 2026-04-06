"""Resolve ScanProfile.credential_ref to secrets (worker only). Never log returned values."""

from __future__ import annotations

import json
import os
from pathlib import Path
from typing import Any

import httpx
import structlog

log = structlog.get_logger()

_file_cache: tuple[str, float, dict[str, Any]] | None = None


def _load_json_map() -> dict[str, Any]:
    raw = os.environ.get("CYBER_VAULT_JSON", "").strip()
    if not raw:
        return {}
    try:
        data = json.loads(raw)
        return data if isinstance(data, dict) else {}
    except json.JSONDecodeError:
        log.warning("cyber_vault_json_invalid")
        return {}


def _load_file_map() -> dict[str, Any]:
    global _file_cache
    path = os.environ.get("CYBER_VAULT_FILE", "").strip()
    if not path:
        return {}
    p = Path(path)
    if not p.is_file():
        return {}
    try:
        mtime = p.stat().st_mtime
    except OSError:
        return {}
    global _file_cache
    if _file_cache and _file_cache[0] == str(p.resolve()) and _file_cache[1] == mtime:
        return _file_cache[2]
    try:
        data = json.loads(p.read_text(encoding="utf-8"))
        if not isinstance(data, dict):
            data = {}
    except (OSError, json.JSONDecodeError):
        log.warning("cyber_vault_file_unreadable", path=str(p))
        data = {}
    _file_cache = (str(p.resolve()), mtime, data)
    return data


def _normalize_secret_blob(blob: Any) -> dict[str, str] | None:
    if not isinstance(blob, dict):
        return None
    out: dict[str, str] = {}
    for k in ("username", "password", "token"):
        v = blob.get(k)
        if v is not None and str(v) != "":
            out[k] = str(v)
    return out if out else None


def _from_vault_kv(ref: str) -> dict[str, str] | None:
    addr = os.environ.get("VAULT_ADDR", "").strip().rstrip("/")
    token = os.environ.get("VAULT_TOKEN", "").strip()
    if not addr or not token:
        return None
    mount = os.environ.get("CYBER_VAULT_KV_MOUNT", "secret").strip().strip("/")
    path = ref.strip().strip("/")
    url = f"{addr}/v1/{mount}/data/{path}"
    try:
        with httpx.Client(timeout=15.0) as client:
            r = client.get(url, headers={"X-Vault-Token": token})
            r.raise_for_status()
            body = r.json()
    except Exception as e:
        log.warning("vault_kv_fetch_failed", ref=ref, error=str(e))
        return None
    inner = body.get("data") or {}
    data = inner.get("data")
    return _normalize_secret_blob(data)


def resolve_credential_ref(ref: str | None) -> dict[str, str] | None:
    """
    Map credential_ref to username/password/token dict.
    Sources (first hit wins): CYBER_VAULT_JSON, CYBER_VAULT_FILE, HashiCorp KV v2 (VAULT_ADDR + VAULT_TOKEN).
    """
    if not ref or not str(ref).strip():
        return None
    key = str(ref).strip()

    blob = _load_json_map().get(key)
    got = _normalize_secret_blob(blob)
    if got:
        return got

    got = _normalize_secret_blob(_load_file_map().get(key))
    if got:
        return got

    got = _from_vault_kv(key)
    if got:
        return got

    log.warning("credential_ref_unresolved", ref=key)
    return None
