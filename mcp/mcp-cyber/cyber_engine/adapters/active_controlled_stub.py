"""
Controlled-active probes (policy-gated).

This adapter keeps probes defensive and low impact:
- HTTP method exposure checks (OPTIONS/TRACE)
- Open redirect checks using external redirect parameters
- Error disclosure checks with benign malformed input

Runs only in ``active_controlled`` mode.
"""

from __future__ import annotations

from typing import Iterable
from urllib.parse import parse_qsl, urlencode, urlparse, urlsplit, urlunsplit

import httpx
import structlog
from cyber_core.models.finding import RawFinding, RemediationBlock

from cyber_engine.adapters.base import Adapter, ScanContext
from cyber_engine.allowlist import parse_allowlist, url_allowed

log = structlog.get_logger()

_DEFAULT_REDIRECT_PARAMS = ["next", "redirect", "return", "url", "continue"]
_PROBE_VALUE = "https://security-check.invalid/cyber"
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


def _unique(values: Iterable[str]) -> list[str]:
    out: list[str] = []
    seen: set[str] = set()
    for v in values:
        s = str(v or "").strip()
        if not s or s in seen:
            continue
        seen.add(s)
        out.append(s)
    return out


def _targets(ctx: ScanContext) -> list[str]:
    max_targets = _to_int((ctx.options or {}).get("active_max_targets"), default=5, minimum=1, maximum=20)
    raw = ctx.target_urls or [ctx.base_url.rstrip("/") + "/"]
    urls = _unique(raw)
    try:
        cfg = parse_allowlist(ctx.allowlist or {})
    except Exception:
        return urls[:max_targets]
    return [u for u in urls if url_allowed(u, ctx.base_url, cfg)][:max_targets]


def _append_query(url: str, key: str, value: str) -> str:
    parts = urlsplit(url)
    q = parse_qsl(parts.query, keep_blank_values=True)
    q = [(k, v) for k, v in q if k != key]
    q.append((key, value))
    return urlunsplit((parts.scheme, parts.netloc, parts.path, urlencode(q), parts.fragment))


def _location_external(location: str, src_url: str) -> bool:
    if not location:
        return False
    src = urlparse(src_url)
    dst = urlparse(location)
    if not dst.scheme and not dst.netloc:
        return False
    return (src.hostname or "").lower() != (dst.hostname or "").lower()


def _contains_error_marker(text: str) -> bool:
    body = (text or "").lower()
    return any(marker in body for marker in _ERROR_MARKERS)


class ActiveControlledStubAdapter(Adapter):
    id = "active_controlled_stub"

    async def run(self, ctx: ScanContext) -> list[RawFinding]:
        if ctx.mode != "active_controlled":
            return []
        client = ctx.http_client
        if not client:
            return []

        findings: list[RawFinding] = []
        for url in _targets(ctx):
            baseline_status = await self._baseline_status(ctx, client, url)
            findings.extend(await self._check_trace(ctx, client, url))
            findings.extend(await self._check_open_redirect(ctx, client, url))
            findings.extend(await self._check_error_disclosure(ctx, client, url, baseline_status))
        return findings

    async def _baseline_status(self, ctx: ScanContext, client: httpx.AsyncClient, url: str) -> int:
        if ctx.rate_limiter:
            await ctx.rate_limiter.acquire()
        try:
            resp = await client.get(url, follow_redirects=True, timeout=20.0)
            return int(resp.status_code)
        except Exception as e:
            log.warning("active_baseline_failed", scan_id=ctx.scan_id, url=url, error=str(e))
            return 0

    async def _check_trace(self, ctx: ScanContext, client: httpx.AsyncClient, url: str) -> list[RawFinding]:
        out: list[RawFinding] = []
        allow_header = ""
        if ctx.rate_limiter:
            await ctx.rate_limiter.acquire()
        try:
            opts = await client.request("OPTIONS", url, follow_redirects=False, timeout=15.0)
            allow_header = opts.headers.get("allow", "")
        except Exception as e:
            log.debug("active_options_failed", scan_id=ctx.scan_id, url=url, error=str(e))

        if "trace" in allow_header.lower():
            out.append(
                RawFinding(
                    rule_id="active.method.trace_advertised",
                    category="transport",
                    title="Server advertises TRACE in Allow methods",
                    severity="medium",
                    confidence=0.7,
                    url=url,
                    reproduction=f"OPTIONS {url} -> Allow: {allow_header}",
                    root_cause="TRACE should be disabled on public endpoints.",
                    remediation=RemediationBlock(
                        summary="Disable TRACE at reverse proxy/web server.",
                        steps=["Block TRACE in Nginx/Apache/IIS and upstream app gateway"],
                    ),
                    tags=["active_controlled", "method_exposure"],
                )
            )

        if ctx.rate_limiter:
            await ctx.rate_limiter.acquire()
        try:
            trace = await client.request("TRACE", url, follow_redirects=False, timeout=15.0)
        except Exception:
            return out

        if trace.status_code in (200, 201, 202, 204):
            out.append(
                RawFinding(
                    rule_id="active.method.trace_enabled",
                    category="transport",
                    title="TRACE method appears enabled",
                    severity="high",
                    confidence=0.8,
                    url=url,
                    reproduction=f"TRACE {url} returned HTTP {trace.status_code}",
                    root_cause="TRACE can enable cross-site tracing and diagnostic leakage.",
                    remediation=RemediationBlock(
                        summary="Disable TRACE method for internet-facing services.",
                        steps=["Return 405/501 for TRACE via proxy and app server"],
                    ),
                    tags=["active_controlled", "method_exposure"],
                )
            )
        return out

    async def _check_open_redirect(self, ctx: ScanContext, client: httpx.AsyncClient, url: str) -> list[RawFinding]:
        out: list[RawFinding] = []
        redirect_params = (ctx.options or {}).get("active_redirect_params") or _DEFAULT_REDIRECT_PARAMS
        params = _unique(redirect_params if isinstance(redirect_params, list) else [str(redirect_params)])
        for key in params[:10]:
            probe_url = _append_query(url, key, _PROBE_VALUE)
            if ctx.rate_limiter:
                await ctx.rate_limiter.acquire()
            try:
                resp = await client.get(probe_url, follow_redirects=False, timeout=15.0)
            except Exception as e:
                log.debug("active_redirect_probe_failed", scan_id=ctx.scan_id, url=probe_url, error=str(e))
                continue
            location = resp.headers.get("location", "")
            if 300 <= resp.status_code <= 399 and _location_external(location, url):
                out.append(
                    RawFinding(
                        rule_id="active.redirect.open_redirect",
                        category="authz",
                        title=f"Potential open redirect via '{key}' parameter",
                        severity="high",
                        confidence=0.75,
                        url=url,
                        parameter=key,
                        evidence=[
                            {"type": "request_url", "value": probe_url},
                            {"type": "location", "value": location},
                            {"type": "status", "value": resp.status_code},
                        ],
                        reproduction=f"GET {probe_url} returned {resp.status_code} with Location={location}",
                        root_cause="Redirect target appears to accept untrusted external URL input.",
                        remediation=RemediationBlock(
                            summary="Allowlist redirect targets or require relative-path redirects only.",
                            steps=[
                                "Reject external redirect hosts by default",
                                "Use server-side route IDs instead of raw URL parameters",
                            ],
                        ),
                        tags=["active_controlled", "redirect"],
                    )
                )
                break
        return out

    async def _check_error_disclosure(
        self,
        ctx: ScanContext,
        client: httpx.AsyncClient,
        url: str,
        baseline_status: int,
    ) -> list[RawFinding]:
        out: list[RawFinding] = []
        probe_url = _append_query(url, "__cyber_probe", "invalid-input-[]{}\"'")
        if ctx.rate_limiter:
            await ctx.rate_limiter.acquire()
        try:
            resp = await client.get(probe_url, follow_redirects=True, timeout=20.0)
        except Exception as e:
            log.debug("active_error_probe_failed", scan_id=ctx.scan_id, url=probe_url, error=str(e))
            return out

        text = ""
        try:
            text = resp.text[:4000]
        except Exception:
            text = ""

        if resp.status_code >= 500 and baseline_status < 500:
            out.append(
                RawFinding(
                    rule_id="active.robustness.unhandled_error",
                    category="injection",
                    title="Input probe triggered server error (5xx)",
                    severity="high",
                    confidence=0.7,
                    url=url,
                    parameter="__cyber_probe",
                    evidence=[{"type": "status", "value": resp.status_code}],
                    reproduction=f"GET {probe_url} returned HTTP {resp.status_code}",
                    root_cause="Unhandled or weakly validated input path may cause server exceptions.",
                    remediation=RemediationBlock(
                        summary="Harden input validation and error handling.",
                        steps=[
                            "Validate and normalize query parameters before processing",
                            "Return controlled 4xx responses for invalid input",
                        ],
                    ),
                    tags=["active_controlled", "robustness"],
                )
            )

        if _contains_error_marker(text):
            out.append(
                RawFinding(
                    rule_id="active.errors.stacktrace_disclosure",
                    category="injection",
                    title="Response contains stack-trace or backend error markers",
                    severity="medium",
                    confidence=0.65,
                    url=url,
                    parameter="__cyber_probe",
                    reproduction=f"GET {probe_url} returned text matching backend error markers",
                    root_cause="Detailed exception output is exposed to clients.",
                    remediation=RemediationBlock(
                        summary="Disable detailed error pages in non-local environments.",
                        steps=[
                            "Return generic error pages/messages to clients",
                            "Log full stack traces server-side only",
                        ],
                    ),
                    tags=["active_controlled", "error_disclosure"],
                )
            )
        return out
