"""
Controlled fuzzing adapter (policy-gated, low-intensity).

Runs in ``active_controlled`` mode and performs safe robustness checks:
- benign query-parameter fuzz cases
- 5xx detection for malformed-but-non-destructive input
- backend error disclosure pattern checks
"""

from __future__ import annotations

import json
from collections.abc import Iterable
from urllib.parse import parse_qsl, urlencode, urlsplit, urlunsplit

import httpx
import structlog
from cyber_core.models.finding import RawFinding, RemediationBlock

from cyber_engine.adapters.base import Adapter, ScanContext
from cyber_engine.allowlist import parse_allowlist, url_allowed

log = structlog.get_logger()

_DEFAULT_PARAMS = ["q", "search", "id", "page"]
_DEFAULT_VALUES = [
    "__CYBER_FUZZ_LONG_" + ("A" * 128),
    "__CYBER_FUZZ_UTF8_kanji_check",
    "__CYBER_FUZZ_SYMBOLS_[]{}!@#$%^&*",
]
_ERROR_MARKERS = [
    "traceback (most recent call last)",
    "stack trace",
    "sqlstate",
    "sql syntax",
    "pdoexception",
    "fatal error",
    "uncaught exception",
    "nullpointerexception",
]


def _to_int(v: object, default: int, minimum: int, maximum: int) -> int:
    try:
        n = int(v)
    except (TypeError, ValueError):
        return default
    return max(minimum, min(maximum, n))


def _unique(items: Iterable[str]) -> list[str]:
    out: list[str] = []
    seen: set[str] = set()
    for item in items:
        s = str(item or "").strip()
        if not s or s in seen:
            continue
        seen.add(s)
        out.append(s)
    return out


def _extract_openapi_urls(base_url: str, raw_openapi: object, max_urls: int) -> list[str]:
    if not raw_openapi or not str(raw_openapi).strip():
        return []
    try:
        doc = json.loads(str(raw_openapi))
    except json.JSONDecodeError:
        return []
    if not isinstance(doc, dict):
        return []
    paths = doc.get("paths")
    if not isinstance(paths, dict):
        return []
    out: list[str] = []
    for path, ops in paths.items():
        if not isinstance(path, str) or "{" in path:
            continue
        if not isinstance(ops, dict):
            continue
        methods = {str(k).lower() for k in ops.keys()}
        if not methods.intersection({"get", "head", "options"}):
            continue
        out.append(base_url.rstrip("/") + "/" + path.lstrip("/"))
        if len(out) >= max_urls:
            break
    return out


def _append_query(url: str, key: str, value: str) -> str:
    parts = urlsplit(url)
    q = parse_qsl(parts.query, keep_blank_values=True)
    q = [(k, v) for k, v in q if k != key]
    q.append((key, value))
    return urlunsplit((parts.scheme, parts.netloc, parts.path, urlencode(q), parts.fragment))


def _contains_error_marker(text: str) -> bool:
    body = (text or "").lower()
    return any(marker in body for marker in _ERROR_MARKERS)


class FuzzHarnessStubAdapter(Adapter):
    id = "fuzz_harness_stub"

    async def run(self, ctx: ScanContext) -> list[RawFinding]:
        if ctx.mode != "active_controlled":
            return []
        client = ctx.http_client
        if not client:
            return []

        opts = ctx.options or {}
        max_urls = _to_int(opts.get("fuzz_max_urls"), default=8, minimum=1, maximum=40)
        max_params = _to_int(opts.get("fuzz_max_params"), default=4, minimum=1, maximum=12)
        max_values = _to_int(opts.get("fuzz_max_values"), default=3, minimum=1, maximum=8)
        params = _unique(opts.get("fuzz_params") or _DEFAULT_PARAMS)[:max_params]
        values = _unique(opts.get("fuzz_values") or _DEFAULT_VALUES)[:max_values]

        candidate_urls = _unique(
            list(ctx.target_urls or [])
            + [ctx.base_url.rstrip("/") + "/"]
            + _extract_openapi_urls(ctx.base_url, opts.get("openapi_json"), max_urls=max_urls)
        )
        candidate_urls = self._allowlisted(ctx, candidate_urls)[:max_urls]

        findings: list[RawFinding] = []
        emitted: set[tuple[str, str, str]] = set()
        for base_url in candidate_urls:
            baseline_status = await self._status(ctx, client, base_url)
            for param in params:
                for fuzz_value in values:
                    probe_url = _append_query(base_url, param, fuzz_value)
                    if ctx.rate_limiter:
                        await ctx.rate_limiter.acquire()
                    try:
                        resp = await client.get(probe_url, follow_redirects=True, timeout=20.0)
                    except Exception as e:
                        log.debug("fuzz_probe_failed", scan_id=ctx.scan_id, url=probe_url, error=str(e))
                        continue

                    status = int(resp.status_code)
                    body = ""
                    try:
                        body = resp.text[:4000]
                    except Exception:
                        body = ""

                    if status >= 500 and baseline_status < 500:
                        key = ("fuzz.robustness.server_error", base_url, param)
                        if key not in emitted:
                            emitted.add(key)
                            findings.append(
                                RawFinding(
                                    rule_id="fuzz.robustness.server_error",
                                    category="injection",
                                    title="Fuzz input caused server-side 5xx response",
                                    severity="high",
                                    confidence=0.7,
                                    url=base_url,
                                    parameter=param,
                                    evidence=[
                                        {"type": "probe_url", "value": probe_url},
                                        {"type": "status", "value": status},
                                    ],
                                    reproduction=f"GET {probe_url} returned HTTP {status}",
                                    root_cause="Input validation or exception handling appears fragile under malformed input.",
                                    remediation=RemediationBlock(
                                        summary="Harden input parsing and return controlled 4xx errors.",
                                        steps=[
                                            "Validate parameter type/length before processing",
                                            "Use centralized exception handlers to avoid 5xx on user input",
                                        ],
                                    ),
                                    tags=["fuzz", "active_controlled", "robustness"],
                                )
                            )

                    if _contains_error_marker(body):
                        key = ("fuzz.errors.disclosure", base_url, param)
                        if key not in emitted:
                            emitted.add(key)
                            findings.append(
                                RawFinding(
                                    rule_id="fuzz.errors.disclosure",
                                    category="injection",
                                    title="Fuzz response includes backend error detail",
                                    severity="medium",
                                    confidence=0.65,
                                    url=base_url,
                                    parameter=param,
                                    evidence=[{"type": "probe_url", "value": probe_url}],
                                    reproduction=f"GET {probe_url} returned body matching backend error markers",
                                    root_cause="Detailed exception text is exposed to clients.",
                                    remediation=RemediationBlock(
                                        summary="Hide stack traces and backend exception details from responses.",
                                        steps=[
                                            "Return generic client-facing errors",
                                            "Log full traces server-side only",
                                        ],
                                    ),
                                    tags=["fuzz", "active_controlled", "error_disclosure"],
                                )
                            )

        return findings

    def _allowlisted(self, ctx: ScanContext, urls: list[str]) -> list[str]:
        try:
            cfg = parse_allowlist(ctx.allowlist or {})
        except Exception:
            return urls
        return [u for u in urls if url_allowed(u, ctx.base_url, cfg)]

    async def _status(self, ctx: ScanContext, client: httpx.AsyncClient, url: str) -> int:
        if ctx.rate_limiter:
            await ctx.rate_limiter.acquire()
        try:
            resp = await client.get(url, follow_redirects=True, timeout=20.0)
            return int(resp.status_code)
        except Exception:
            return 0
