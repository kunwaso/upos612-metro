"""Allowlist enforcement for URLs (authorized targets only)."""

from __future__ import annotations

from urllib.parse import urlparse

from cyber_core.models.scan import AllowlistConfig


class AllowlistError(ValueError):
    pass


def parse_allowlist(raw: dict) -> AllowlistConfig:
    return AllowlistConfig.model_validate(raw)


def _normalize_prefix(prefix: str) -> str:
    p = (prefix or "").strip()
    if not p:
        return "/"
    if not p.startswith("/"):
        p = "/" + p
    if p != "/" and p.endswith("/"):
        p = p.rstrip("/")
    return p or "/"


def _path_prefix_match(path: str, prefix: str) -> bool:
    if prefix == "/":
        return True
    return path == prefix or path.startswith(prefix + "/")


def url_allowed(url: str, base_url: str, cfg: AllowlistConfig) -> bool:
    try:
        u = urlparse(url)
        b = urlparse(base_url)
    except Exception:
        return False
    if u.scheme not in ("http", "https"):
        return False
    host = (u.hostname or "").lower()
    base_host = (b.hostname or "").lower()
    if cfg.hosts:
        if host not in [h.lower() for h in cfg.hosts]:
            return False
    else:
        if host != base_host:
            return False
    path = u.path or "/"
    if cfg.path_prefixes:
        prefixes = [_normalize_prefix(p) for p in cfg.path_prefixes]
        if not any(_path_prefix_match(path, p) for p in prefixes):
            return False
    return True


def assert_urls_allowed(
    urls: list[str],
    base_url: str,
    allowlist_dict: dict,
    *,
    require_allowlist: bool = True,
) -> None:
    if not require_allowlist:
        return
    cfg = parse_allowlist(allowlist_dict)
    for u in urls:
        if not url_allowed(u, base_url, cfg):
            raise AllowlistError(f"URL not allowlisted: {u}")
